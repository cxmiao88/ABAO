<?php

namespace App\Services\Panel;

use App\Enums\ProxyTypes;
use App\Enums\ServerRole;
use App\Models\Application;
use App\Models\Project;
use App\Models\Server;
use App\Models\Team;
use App\Rules\ValidGitRepositoryUrl;
use App\Rules\ValidServerIp;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * O2: 新建应用 / 服务器 / 数据库 —— 对齐原生 Livewire 创建链路
 * （Project\New\PublicGitRepository::submit / Server\New\ByIp::submit / Project\Resource\Create）
 */
class PanelCreateService
{
    /**
     * 新建服务器（对齐 Server\New\ByIp::submit）。
     */
    public function createServer(array $data, int $teamId): Server
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'ip' => ['required', 'string', new ValidServerIp],
            'user' => 'required|string|max:255',
            'port' => 'required|integer|between:1,65535',
            'private_key_id' => ['required', 'integer', Rule::exists('private_keys', 'id')->where('team_id', $teamId)],
            'server_role' => ['required', 'string', Rule::in(['deployment', 'build', 'both'])],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $ip = $data['ip'];
        $foundServer = Server::whereIp($ip)->first();
        if ($foundServer) {
            throw ValidationException::withMessages([
                'ip' => 'A server with this IP/Domain already exists.',
            ]);
        }

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'ip' => $ip,
            'user' => $data['user'],
            'port' => $data['port'],
            'team_id' => $teamId,
            'private_key_id' => $data['private_key_id'],
        ];
        if ($data['server_role'] === ServerRole::BUILD->value) {
            data_forget($payload, 'proxy');
        }

        $server = Team::createServerWithinLimit($teamId, $payload);
        $server->proxy->set('status', 'exited');
        $server->proxy->set('type', ProxyTypes::TRAEFIK->value);
        $server->save();

        $server->settings->server_role = ServerRole::from($data['server_role']);
        $server->settings->is_build_server = $data['server_role'] === ServerRole::BUILD->value;
        $server->settings->save();

        return $server->fresh(['settings']);
    }

    /**
     * 解析目标（standalone docker）——destination_uuid 可选，缺省取当前团队第一个可用 destination。
     */
    private function resolveDestination(?string $destinationUuid, int $teamId): mixed
    {
        if ($destinationUuid) {
            $destination = find_resource_destination_for_current_team($destinationUuid);
            if (! $destination) {
                throw ValidationException::withMessages(['destination_uuid' => 'Destination not found.']);
            }

            return $destination;
        }

        $server = Server::where('team_id', $teamId)->first();
        $destination = $server ? $server->destinations()->first() : null;

        if (! $destination) {
            throw ValidationException::withMessages(['destination_uuid' => 'No destination available for this team.']);
        }

        return $destination;
    }

    /**
     * 新建应用（对齐 Project\New\PublicGitRepository::submit，git 源走 'other' 简化路径；
     * mode=dockerimage 时对齐 Project\New\DockerImage::submit）。
     */
    public function createApplication(array $data, int $teamId): Application
    {
        $mode = $data['mode'] ?? 'git';
        if (! in_array($mode, ['git', 'dockerimage'], true)) {
            throw ValidationException::withMessages(['mode' => 'Invalid mode.']);
        }

        $destination = $this->resolveDestination($data['destination_uuid'] ?? null, $teamId);

        $project = Project::ownedByCurrentTeam()->where('uuid', $data['project_uuid'] ?? null)->first();
        if (! $project) {
            throw ValidationException::withMessages(['project_uuid' => 'Project not found.']);
        }
        $environment = $project->environments()->where('uuid', $data['environment_uuid'] ?? null)->first();
        if (! $environment) {
            throw ValidationException::withMessages(['environment_uuid' => 'Environment not found.']);
        }

        if ($mode === 'git') {            $validator = Validator::make($data, [
                'repository_url' => ['required', 'string', new ValidGitRepositoryUrl],
                'git_branch' => 'required|string|max:255',
                'port' => 'required|numeric|min:1|max:65535',
                'build_pack' => ['required', 'string', Rule::in(['nixpacks', 'railpack', 'static', 'dockerfile', 'dockercompose'])],
                'is_static' => 'required|boolean',
                'publish_directory' => 'nullable|string|max:255',
                'base_directory' => 'nullable|string|max:255',
                'docker_compose_location' => 'nullable|string|max:255',
            ]);
            if ($validator->fails()) {
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            $application_init = [
                'name' => generate_random_name(),
                'git_repository' => $data['repository_url'],
                'git_branch' => $data['git_branch'],
                'ports_exposes' => $data['port'],
                'publish_directory' => $data['publish_directory'] ?? null,
                'environment_id' => $environment->id,
                'destination_id' => $destination->id,
                'destination_type' => $destination->getMorphClass(),
                'build_pack' => $data['build_pack'],
                'base_directory' => $data['base_directory'] ?? '/',
            ];
            if (in_array($data['build_pack'], ['dockerfile', 'dockercompose'], true)) {
                $application_init['health_check_enabled'] = false;
            }
            if ($data['build_pack'] === 'dockercompose') {
                $application_init['docker_compose_location'] = $data['docker_compose_location'] ?? '/docker-compose.yaml';
            }
            $application = new Application($application_init);
            $application->save();
            $application->settings->is_static = $data['is_static'];
            $application->settings->save();
        } else {
            $validator = Validator::make($data, [
                'image_name' => 'required|string|max:255',
                'image_tag' => 'nullable|string|max:255',
                'image_sha256' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/i'],
            ]);
            if ($validator->fails()) {
                throw ValidationException::withMessages($validator->errors()->toArray());
            }
            if (! empty($data['image_tag']) && ! empty($data['image_sha256'])) {
                throw ValidationException::withMessages([
                    'image_tag' => 'Provide either a tag or SHA256 digest, not both.',
                ]);
            }

            $imageName = $data['image_name'];
            $imageTag = $data['image_tag'] ?: 'latest';
            if (! empty($data['image_sha256'])) {
                $sha = preg_replace('/^sha256:/i', '', trim($data['image_sha256']));
                $imageName = $data['image_name'].'@sha256';
                $imageTag = 'sha256-'.$sha;
            }

            $application = new Application([
                'name' => 'docker-image-'.new_public_id(),
                'repository_project_id' => 0,
                'git_repository' => 'coollabsio/coolify',
                'git_branch' => 'main',
                'build_pack' => 'dockerimage',
                'ports_exposes' => 80,
                'docker_registry_image_name' => $imageName,
                'docker_registry_image_tag' => $imageTag,
                'environment_id' => $environment->id,
                'destination_id' => $destination->id,
                'destination_type' => $destination->getMorphClass(),
                'health_check_enabled' => false,
            ]);
            $application->save();
            $application->update(['name' => 'docker-image-'.$application->uuid]);
        }

        $fqdn = generateUrl(server: $destination->server, random: $application->uuid);
        $application->fqdn = $fqdn;
        $application->save();

        return $application->fresh();
    }

    /**
     * 新建独立数据库（对齐 Project\Resource\Create 的 create_standalone_* 链路）。
     */
    public function createDatabase(array $data, int $teamId): mixed
    {
        $type = $data['type'] ?? null;
        if (! in_array($type, DATABASE_TYPES, true)) {
            throw ValidationException::withMessages(['type' => 'Invalid database type.']);
        }

        $validator = Validator::make($data, [
            'type' => 'required|string',
            'database_image' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $destination = $this->resolveDestination($data['destination_uuid'] ?? null, $teamId);

        $project = Project::ownedByCurrentTeam()->where('uuid', $data['project_uuid'] ?? null)->first();
        if (! $project) {
            throw ValidationException::withMessages(['project_uuid' => 'Project not found.']);
        }
        $environment = $project->environments()->where('uuid', $data['environment_uuid'] ?? null)->first();
        if (! $environment) {
            throw ValidationException::withMessages(['environment_uuid' => 'Environment not found.']);
        }

        $databaseImage = $data['database_image'] ?? null;
        $database = match ($type) {
            'postgresql' => $databaseImage
                ? create_standalone_postgresql(environmentId: $environment->id, destination: $destination, databaseImage: $databaseImage)
                : create_standalone_postgresql(environmentId: $environment->id, destination: $destination),
            'redis' => create_standalone_redis($environment->id, $destination),
            'mongodb' => create_standalone_mongodb($environment->id, $destination),
            'mysql' => create_standalone_mysql($environment->id, $destination),
            'mariadb' => create_standalone_mariadb($environment->id, $destination),
            'keydb' => create_standalone_keydb($environment->id, $destination),
            'dragonfly' => create_standalone_dragonfly($environment->id, $destination),
            'clickhouse' => create_standalone_clickhouse($environment->id, $destination),
            'sqlite' => create_standalone_sqlite($environment->id, $destination),
            default => throw ValidationException::withMessages(['type' => 'Invalid database type.']),
        };

        return $database;
    }
}
