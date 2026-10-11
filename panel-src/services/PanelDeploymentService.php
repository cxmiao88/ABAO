<?php

namespace App\Services\Panel;

use App\Models\Application;
use App\Models\ApplicationDeploymentQueue;
use Illuminate\Support\Collection;

/**
 * ABao panel: deployment operations for the Vue frontend (baouit-panel-front).
 *
 * 阶段 3.2 W1. Mirrors the Livewire heading actions
 * (App\Livewire\Project\Application\Heading::deploy/restart/stop) so the
 * new frontend can trigger real deployments without touching Coolify's UI.
 *
 * Every query is scoped to the current team (ownedByCurrentTeam). Write
 * operations additionally require the 'deploy' policy (team admin), enforced
 * in the controller via $this->authorize().
 */
class PanelDeploymentService
{
    /**
     * Queue a new deployment. Returns the same status vocabulary the Livewire
     * heading uses ('ok' | 'queue_full' | 'skipped') plus the deployment uuid.
     */
    public function deploy(string $uuid, bool $forceRebuild = false): array
    {
        $application = $this->resolveApplication($uuid);
        if ($application instanceof Application) {
            $preconditionError = $this->preconditionError($application);
            if ($preconditionError !== null) {
                return ['status' => 'precondition', 'message' => $preconditionError];
            }

            $deploymentUuid = new_public_id();
            $result = queue_application_deployment(
                application: $application,
                deployment_uuid: $deploymentUuid,
                force_rebuild: $forceRebuild,
            );

            return [
                'status' => $result['status'] ?? 'ok',
                'message' => $result['message'] ?? null,
                'deployment_uuid' => $deploymentUuid,
            ];
        }

        return $application;
    }

    public function restart(string $uuid): array
    {
        $application = $this->resolveApplication($uuid);
        if ($application instanceof Application) {
            if ($application->additional_servers->count() > 0 && str($application->docker_registry_image_name)->isEmpty()) {
                return [
                    'status' => 'precondition',
                    'message' => '部署到多台服务器前，必须先设置 Docker 镜像名（General 标签页）。',
                ];
            }

            $deploymentUuid = new_public_id();
            $result = queue_application_deployment(
                application: $application,
                deployment_uuid: $deploymentUuid,
                restart_only: true,
            );

            return [
                'status' => $result['status'] ?? 'ok',
                'message' => $result['message'] ?? null,
                'deployment_uuid' => $deploymentUuid,
            ];
        }

        return $application;
    }

    public function stop(string $uuid): array
    {
        $application = $this->resolveApplication($uuid);
        if ($application instanceof Application) {
            \App\Actions\Application\StopApplication::dispatch($application, false, true);
            auditLog('ui.application.stopped', [
                'team_id' => $application->team()?->id,
                'application_uuid' => $application->uuid,
                'application_name' => $application->name,
            ]);

            return ['status' => 'ok'];
        }

        return $application;
    }

    /**
     * Recent deployment records for an application (newest first).
     */
    public function deployments(string $uuid, int $limit = 20): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        return ApplicationDeploymentQueue::query()
            ->where('application_id', $application->id)
            ->orderByDesc('id')
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(fn (ApplicationDeploymentQueue $deployment) => $this->serializeDeployment($deployment, false))
            ->values()
            ->all();
    }

    /**
     * Rollback images for an application: the docker image tag history plus
     * the current running tag, retention settings (O17a, mirrors
     * App\Livewire\Project\Application\Rollback::loadImages + mount).
     */
    public function rollbackImages(string $uuid): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        $server = $application->destination->server;
        $retentionDisabled = (bool) data_get($server->settings, 'disable_application_image_retention', false);
        $dockerImagesToKeep = data_get($application->settings, 'docker_images_to_keep', 2);
        $image = $application->docker_registry_image_name ?: $application->uuid;
        $images = [];
        $current = null;

        if ($server->isFunctional()) {
            $output = instant_remote_process([
                "docker inspect --format='{{.Config.Image}}' {$application->uuid}",
            ], $server, throwError: false);
            $currentTag = str($output)->trim()->explode(':');
            $current = data_get($currentTag, 1);

            $output = instant_remote_process([
                "docker images --format '{{.Repository}}#{{.Tag}}#{{.CreatedAt}}'",
            ], $server);
            $images = str($output)->trim()->explode("\n")
                ->filter(fn ($item) => str($item)->contains($image))
                ->map(function ($item) use ($current) {
                    $item = str($item)->explode('#');

                    return [
                        'tag' => $item[1],
                        'created_at' => $item[2],
                        'is_current' => $item[1] === $current,
                    ];
                })->values()->all();
        }

        return [
            'status' => 'ok',
            'images' => $images,
            'current' => $current,
            'docker_images_to_keep' => $dockerImagesToKeep,
            'server_retention_disabled' => $retentionDisabled,
        ];
    }

    /**
     * Rollback an application to a previous docker image tag (O17a, mirrors
     * App\Livewire\Project\Application\Rollback::rollbackImage).
     */
    public function rollback(string $uuid, string $commit): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        $commit = validateGitRef($commit, 'rollback commit');
        $deploymentUuid = new_public_id();
        $result = queue_application_deployment(
            application: $application,
            deployment_uuid: $deploymentUuid,
            commit: $commit,
            rollback: true,
            force_rebuild: false,
        );

        return [
            'status' => $result['status'] ?? 'ok',
            'message' => $result['message'] ?? null,
            'deployment_uuid' => $deploymentUuid,
        ];
    }

    /**
     * A single deployment record including its (possibly large) log output.
     */
    public function deployment(string $uuid, string $deploymentUuid): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        $deployment = ApplicationDeploymentQueue::query()
            ->where('application_id', $application->id)
            ->where('deployment_uuid', $deploymentUuid)
            ->first();

        if (! $deployment) {
            return ['status' => 'not_found', 'message' => 'Deployment not found.'];
        }

        return $this->serializeDeployment($deployment, true);
    }

    /**
     * @return Application|array{status:string,message:string}
     */
    private function resolveApplication(string $uuid): Application|array
    {
        $application = Application::ownedByCurrentTeam()->where('uuid', $uuid)->first();
        if (! $application) {
            return ['status' => 'not_found', 'message' => 'Application not found.'];
        }

        return $application;
    }

    /**
     * Same preconditions the Livewire heading checks before queueing a deploy.
     */
    private function preconditionError(Application $application): ?string
    {
        if ($application->build_pack === 'dockercompose' && is_null($application->docker_compose_raw)) {
            return '部署失败：请先加载 Compose 文件。';
        }
        if ($application->destination->server->isSwarm() && str($application->docker_registry_image_name)->isEmpty()) {
            return '部署失败：Swarm 集群部署前必须先设置 Docker 镜像名。';
        }
        if (data_get($application, 'settings.is_build_server_enabled') && str($application->docker_registry_image_name)->isEmpty()) {
            return '部署失败：使用构建服务器前必须先设置 Docker 镜像。';
        }
        if ($application->additional_servers->count() > 0 && str($application->docker_registry_image_name)->isEmpty()) {
            return '部署失败：部署到多台服务器前，必须先设置 Docker 镜像名（General 标签页）。';
        }

        return null;
    }

    private function serializeDeployment(ApplicationDeploymentQueue $deployment, bool $withLogs): array
    {
        $data = [
            'deployment_uuid' => $deployment->deployment_uuid,
            'status' => $deployment->status,
            'commit' => $deployment->commit,
            'commit_message' => $deployment->commitMessage(),
            'restart_only' => (bool) $deployment->restart_only,
            'is_api' => (bool) $deployment->is_api,
            'deployment_url' => $deployment->deployment_url,
            'created_at' => $deployment->created_at?->toIso8601String(),
            'finished_at' => $deployment->finished_at?->toIso8601String(),
            'application_name' => $deployment->application_name,
            'server_name' => $deployment->server_name,
        ];

        if ($withLogs) {
            $data['logs'] = $this->parseLogs($deployment->logs);
        }

        return $data;
    }

    /**
     * Logs are stored as a JSON array of steps: [{name, output, type, ...}].
     * Invalid or empty payloads degrade to an empty list instead of erroring.
     *
     * @return array<int, mixed>
     */
    private function parseLogs(?string $logs): array
    {
        if (empty($logs)) {
            return [];
        }

        $decoded = json_decode($logs, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($step) => is_array($step)));
    }
}
