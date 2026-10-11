<?php

namespace App\Services\Panel;

use App\Enums\ProcessStatus;
use App\Models\Environment;
use App\Models\EnvironmentVariable;
use App\Models\Project;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Actions\Service\StartService;
use App\Actions\Service\StopService;
use App\Actions\Service\RestartService;
use App\Jobs\DeleteResourceJob;

class PanelServiceService
{
    /**
     * 服务模板列表（精简字段）。
     */
    public function templates(): array
    {
        return get_service_templates()
            ->map(fn ($t, $k) => [
                'type' => $k,
                'documentation' => data_get($t, 'documentation'),
                'slogan' => data_get($t, 'slogan'),
            ])
            ->values()
            ->all();
    }

    /**
     * 服务实例列表（团队隔离 + 脱敏）。
     */
    public function services(int $teamId): array
    {
        return Project::whereTeamId($teamId)
            ->get()
            ->flatMap(fn (Project $project) => $project->services()->get())
            ->map(fn (Service $service) => $this->serialize($service))
            ->values()
            ->all();
    }

    /**
     * 服务详情（含子应用/子数据库，脱敏）。
     */
    public function show(string $uuid): Service
    {
        $service = Service::whereUuid($uuid)->firstOrFail();
        $service->load(['applications', 'databases']);

        return $service;
    }

    /**
     * 一键部署（复用官方 create_service 模板逻辑）。
     * 简化：单服务器/单 destination 环境，project/environment/server/destination 可缺省（取团队默认）。
     */
    public function create(array $data, int $teamId): Service
    {
        $allowedFields = ['type', 'name', 'description', 'project_uuid', 'environment_uuid', 'environment_name', 'server_uuid', 'destination_uuid', 'instant_deploy'];
        $extraFields = array_diff(array_keys($data), $allowedFields);
        if (! empty($extraFields)) {
            throw ValidationException::withMessages([implode(',', $extraFields) => 'This field is not allowed.']);
        }
        if (blank(data_get($data, 'type'))) {
            throw ValidationException::withMessages(['type' => 'type is required.']);
        }

        $project = null;
        if (filled(data_get($data, 'project_uuid'))) {
            $project = Project::whereTeamId($teamId)->whereUuid($data['project_uuid'])->first();
            if (! $project) {
                throw ValidationException::withMessages(['project_uuid' => 'Project not found.']);
            }
        } else {
            $project = Project::whereTeamId($teamId)->first();
            if (! $project) {
                throw ValidationException::withMessages(['project_uuid' => 'No project available. Create a project first.']);
            }
        }

        $environment = null;
        if (filled(data_get($data, 'environment_uuid'))) {
            $environment = $project->environments()->whereUuid($data['environment_uuid'])->first();
        } elseif (filled(data_get($data, 'environment_name'))) {
            $environment = $project->environments()->whereName($data['environment_name'])->first();
        }
        if (! $environment) {
            $environment = $project->environments()->first();
        }
        if (! $environment) {
            throw ValidationException::withMessages(['environment_uuid' => 'Environment not found.']);
        }

        $server = null;
        if (filled(data_get($data, 'server_uuid'))) {
            $server = Server::whereTeamId($teamId)->whereUuid($data['server_uuid'])->first();
            if (! $server) {
                throw ValidationException::withMessages(['server_uuid' => 'Server not found.']);
            }
        } else {
            $server = Server::whereTeamId($teamId)->where('id', 0)->first() ?? Server::whereTeamId($teamId)->first();
        }
        if (! $server) {
            throw ValidationException::withMessages(['server_uuid' => 'No server available.']);
        }
        if (! $server->canHostResources()) {
            throw ValidationException::withMessages(['server_uuid' => 'The specified server is configured as a build server and cannot host resources.']);
        }

        $destinations = $server->destinations();
        if ($destinations->count() === 0) {
            throw ValidationException::withMessages(['server_uuid' => 'Server has no destinations.']);
        }
        $destination = $destinations->first();

        $services = get_service_templates();
        $type = resolve_service_template_key($data['type'], $services);
        $oneClickService = data_get($services, "$type.compose");
        if (! $oneClickService) {
            throw ValidationException::withMessages(['type' => "Unknown service type: {$data['type']}."]);
        }
        $oneClickDotEnvs = data_get($services, "$type.envs", null);

        $dockerComposeRaw = base64_decode($oneClickService);
        try {
            validateDockerComposeForInjection($dockerComposeRaw);
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['docker_compose_raw' => $e->getMessage()]);
        }

        $service = new Service([
            'name' => "$type-".Str::random(10),
            'docker_compose_raw' => $dockerComposeRaw,
            'environment_id' => $environment->id,
            'service_type' => $type,
            'server_id' => $server->id,
            'destination_id' => $destination->id,
            'destination_type' => $destination->getMorphClass(),
        ]);
        if (in_array($type, NEEDS_TO_CONNECT_TO_PREDEFINED_NETWORK)) {
            $service->connect_to_docker_network = true;
        }
        $service->save();
        $service->name = data_get($data, 'name') ?? "$type-".$service->uuid;
        $service->description = data_get($data, 'description');
        $service->save();

        if ($oneClickDotEnvs) {
            Str::of(base64_decode($oneClickDotEnvs))->split('/\r\n|\r|\n/')->filter(fn ($v) => filled($v))->each(function ($value) use ($service) {
                $key = Str::before($value, '=');
                $val = Str::after($value, '=');
                $generatedValue = $val;
                if ($val->contains('SERVICE_')) {
                    $command = $val->after('SERVICE_')->beforeLast('_');
                    $generatedValue = generateEnvValue($command->value(), $service);
                }
                EnvironmentVariable::create([
                    'key' => $key,
                    'value' => $generatedValue,
                    'resourceable_id' => $service->id,
                    'resourceable_type' => $service->getMorphClass(),
                    'is_preview' => false,
                ]);
            });
        }

        $service->parse(isNew: true);
        applyServiceApplicationPrerequisites($service);

        if (filter_var(data_get($data, 'instant_deploy', false), FILTER_VALIDATE_BOOLEAN)) {
            StartService::dispatch($service);
        }

        return $service->refresh();
    }

    /**
     * 操作：deploy / stop / restart（异步 job）。
     */
    public function action(string $uuid, string $op): array
    {
        $service = Service::whereUuid($uuid)->firstOrFail();

        return match ($op) {
            'deploy' => $this->deploy($service),
            'stop' => $this->stop($service),
            'restart' => $this->restart($service),
            default => throw ValidationException::withMessages(['op' => 'Unsupported action.']),
        };
    }

    private function deploy(Service $service): array
    {
        if (str($service->status)->contains('running')) {
            return ['status' => 'error', 'message' => 'Service is already running.', 'http' => 400];
        }
        StartService::dispatch($service);

        return ['status' => 'ok', 'message' => 'Service starting request queued.'];
    }

    private function stop(Service $service): array
    {
        StopService::dispatch($service, false, true);

        return ['status' => 'ok', 'message' => 'Service stop request queued.'];
    }

    private function restart(Service $service): array
    {
        RestartService::dispatch($service, true);

        return ['status' => 'ok', 'message' => 'Service restart request queued.'];
    }

    /**
     * 删除（软删 + DeleteResourceJob 异步清理）。
     */
    public function destroy(string $uuid): array
    {
        $service = Service::whereUuid($uuid)->firstOrFail();

        $service->delete();

        DeleteResourceJob::dispatch(
            resource: $service,
            deleteVolumes: true,
            deleteConnectedNetworks: true,
            deleteConfigurations: true,
            dockerCleanup: true,
            deleteFromCoolifyOnly: ! $service->server?->isFunctional(),
        );

        return ['status' => 'ok', 'message' => 'Service deletion request queued.'];
    }

    /**
     * 脱敏序列化（不含 compose/密码/私钥等敏感字段）。
     */
    private function serialize(Service $service): array
    {
        return [
            'uuid' => $service->uuid,
            'name' => $service->name,
            'description' => $service->description,
            'service_type' => $service->service_type,
            'status' => $service->status,
            'environment_id' => $service->environment_id,
        ];
    }
}
