<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Server;
use App\Models\Application;
use App\Models\StandalonePostgresql;
use App\Models\StandaloneMysql;
use App\Models\StandaloneRedis;
use App\Models\StandaloneMongodb;
use App\Models\StandaloneMariadb;

class Docker extends Component
{
    public $server = null;
    public $containers = null;
    public $resources = [];

    public function mount()
    {
        $this->server = Server::ownedByCurrentTeam()->first();

        $this->resources = collect()
            ->merge(Application::ownedByCurrentTeam()->get()->map(fn ($a) => ['name' => $a->name, 'type' => '应用', 'status' => null, 'uuid' => $a->uuid]))
            ->merge(StandalonePostgresql::ownedByCurrentTeam()->get()->map(fn ($d) => ['name' => $d->name, 'type' => 'PostgreSQL', 'status' => $d->status, 'uuid' => $d->uuid]))
            ->merge(StandaloneMysql::ownedByCurrentTeam()->get()->map(fn ($d) => ['name' => $d->name, 'type' => 'MySQL', 'status' => $d->status, 'uuid' => $d->uuid]))
            ->merge(StandaloneRedis::ownedByCurrentTeam()->get()->map(fn ($d) => ['name' => $d->name, 'type' => 'Redis', 'status' => $d->status, 'uuid' => $d->uuid]))
            ->merge(StandaloneMongodb::ownedByCurrentTeam()->get()->map(fn ($d) => ['name' => $d->name, 'type' => 'MongoDB', 'status' => $d->status, 'uuid' => $d->uuid]))
            ->merge(StandaloneMariadb::ownedByCurrentTeam()->get()->map(fn ($d) => ['name' => $d->name, 'type' => 'MariaDB', 'status' => $d->status, 'uuid' => $d->uuid]))
            ->values();

        if ($this->server && $this->server->isFunctional()) {
            try {
                $this->containers = $this->server->getContainers();
            } catch (\Throwable $e) {
                $this->containers = null;
            }
        }
    }

    public function render()
    {
        return view('livewire.panel.docker')
            ->layout('layouts.panel', [
                'serverInfo' => [
                    'ip' => $this->server?->ip ?? 'localhost',
                    'os' => 'Linux',
                    'account' => auth()->user()->name,
                ],
            ]);
    }
}
