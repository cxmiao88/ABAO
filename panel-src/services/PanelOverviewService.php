<?php

namespace App\Services\Panel;

use App\Models\Application;
use App\Models\Server;
use App\Models\StandaloneMariadb;
use App\Models\StandaloneMongodb;
use App\Models\StandaloneMysql;
use App\Models\StandalonePostgresql;
use App\Models\StandaloneRedis;
use Illuminate\Support\Collection;

/**
 * ABao panel: JSON data source for the Vue frontend (baouit-panel-front).
 *
 * Mirrors the data the Livewire panel components show, so both surfaces stay
 * on the same口径:
 *   - App\Livewire\Panel\Home::loadData()         -> overview()
 *   - App\Livewire\Dashboard\BaotaStatus         -> stats via Server::getSystemStats()
 *   - App\Livewire\Server\SystemOverview         -> stats via Server::getSystemStats()
 *   - App\Livewire\Panel\Docker                  -> containers via Server::getContainers()
 *
 * Every query is scoped to the current team (ownedByCurrentTeam).
 */
class PanelOverviewService
{
    public function overview(): array
    {
        $servers = Server::ownedByCurrentTeam()->get();

        $overview = [
            'servers' => $servers->count(),
            'running' => $servers->filter(fn (Server $server) => $server->isFunctional())->count(),
            'applications' => Application::ownedByCurrentTeam()->count(),
            'databases' => $this->databaseCount(),
        ];

        // Prefer a functional server for the stats payload; fall back to the first one.
        $server = $servers->first(fn (Server $server) => $server->isFunctional()) ?? $servers->first();

        return [
            'overview' => $overview,
            'servers' => $servers->map(fn (Server $server) => $this->serializeServer($server, false))->values(),
            'selected_server_uuid' => $server?->uuid,
            'server' => $server ? $this->serializeServer($server, true) : null,
        ];
    }

    public function servers(): array
    {
        return Server::ownedByCurrentTeam()->get()
            ->map(fn (Server $server) => $this->serializeServer($server, false))
            ->values()
            ->all();
    }

    public function server(string $uuid): ?array
    {
        $server = Server::ownedByCurrentTeam()->where('uuid', $uuid)->first();
        if (! $server) {
            return null;
        }

        return $this->serializeServer($server, true);
    }

    public function containers(?string $serverUuid = null): array
    {
        $server = $this->resolveServer($serverUuid);
        if (! $server) {
            return ['server_uuid' => null, 'containers' => collect()];
        }

        try {
            $data = $server->getContainers();
            $containers = $data['containers'] ?? collect();
            $replicates = $data['containerReplicates'] ?? collect();
        } catch (\Throwable $e) {
            report($e);
            $containers = collect();
            $replicates = collect();
        }

        return [
            'server_uuid' => $server->uuid,
            'containers' => $containers->map(fn ($container) => $this->serializeContainer($container))->values(),
            'container_replicates' => $replicates->values(),
        ];
    }

    public function applications(): array
    {
        return Application::ownedByCurrentTeam()->get()
            ->map(fn (Application $app) => [
                'uuid' => $app->uuid,
                'name' => $app->name,
                'fqdn' => $app->fqdn,
                'status' => $app->status,
                'description' => $app->description,
            ])
            ->values()
            ->all();
    }

    public function databases(): array
    {
        return collect()
            ->merge(StandalonePostgresql::ownedByCurrentTeam()->get()->map(fn ($database) => $this->serializeDatabase('PostgreSQL', $database)))
            ->merge(StandaloneMysql::ownedByCurrentTeam()->get()->map(fn ($database) => $this->serializeDatabase('MySQL', $database)))
            ->merge(StandaloneRedis::ownedByCurrentTeam()->get()->map(fn ($database) => $this->serializeDatabase('Redis', $database)))
            ->merge(StandaloneMongodb::ownedByCurrentTeam()->get()->map(fn ($database) => $this->serializeDatabase('MongoDB', $database)))
            ->merge(StandaloneMariadb::ownedByCurrentTeam()->get()->map(fn ($database) => $this->serializeDatabase('MariaDB', $database)))
            ->values()
            ->all();
    }

    private function databaseCount(): int
    {
        return StandalonePostgresql::ownedByCurrentTeam()->count()
            + StandaloneMysql::ownedByCurrentTeam()->count()
            + StandaloneRedis::ownedByCurrentTeam()->count()
            + StandaloneMongodb::ownedByCurrentTeam()->count()
            + StandaloneMariadb::ownedByCurrentTeam()->count();
    }

    private function resolveServer(?string $serverUuid = null): ?Server
    {
        $servers = Server::ownedByCurrentTeam()->get();
        if ($serverUuid) {
            return $servers->firstWhere('uuid', $serverUuid);
        }

        return $servers->first(fn (Server $server) => $server->isFunctional()) ?? $servers->first();
    }

    private function serializeServer(Server $server, bool $withStats): array
    {
        $data = [
            'uuid' => $server->uuid,
            'name' => $server->name,
            'ip' => $server->ip,
            'port' => $server->port,
            'description' => $server->description,
            'is_functional' => $server->isFunctional(),
            'server_type' => data_get($server, 'settings.server_type'),
        ];

        if ($withStats) {
            $data['stats'] = $server->getSystemStats();
        }

        return $data;
    }

    private function serializeContainer(mixed $container): array
    {
        $state = data_get($container, 'State');

        return [
            'id' => substr((string) data_get($container, 'Id', ''), 0, 12),
            'name' => ltrim((string) data_get($container, 'Name', ''), '/'),
            'image' => data_get($container, 'Config.Image') ?? data_get($container, 'Image'),
            'status' => data_get($state, 'Status'),
            'health' => data_get($state, 'Health.Status'),
            'created' => data_get($container, 'Created'),
        ];
    }

    private function serializeDatabase(string $type, mixed $database): array
    {
        return [
            'type' => $type,
            'uuid' => $database->uuid,
            'name' => $database->name,
            'status' => $database->status,
        ];
    }
}
