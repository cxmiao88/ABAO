<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Server;

class Databases extends Component
{
    public $databases = [];

    public function mount()
    {
        $this->databases = collect()
            ->merge(\App\Models\StandalonePostgresql::ownedByCurrentTeam()->get()->map(fn ($d) => ['type' => 'PostgreSQL', 'name' => $d->name, 'status' => $d->status, 'uuid' => $d->uuid, 'model' => $d]))
            ->merge(\App\Models\StandaloneMysql::ownedByCurrentTeam()->get()->map(fn ($d) => ['type' => 'MySQL', 'name' => $d->name, 'status' => $d->status, 'uuid' => $d->uuid, 'model' => $d]))
            ->merge(\App\Models\StandaloneRedis::ownedByCurrentTeam()->get()->map(fn ($d) => ['type' => 'Redis', 'name' => $d->name, 'status' => $d->status, 'uuid' => $d->uuid, 'model' => $d]))
            ->merge(\App\Models\StandaloneMongodb::ownedByCurrentTeam()->get()->map(fn ($d) => ['type' => 'MongoDB', 'name' => $d->name, 'status' => $d->status, 'uuid' => $d->uuid, 'model' => $d]))
            ->merge(\App\Models\StandaloneMariadb::ownedByCurrentTeam()->get()->map(fn ($d) => ['type' => 'MariaDB', 'name' => $d->name, 'status' => $d->status, 'uuid' => $d->uuid, 'model' => $d]))
            ->values();
    }

    public function render()
    {
        $server = Server::ownedByCurrentTeam()->first();

        return view('livewire.panel.databases')
            ->layout('layouts.panel', [
                'serverInfo' => [
                    'ip' => $server?->ip ?? 'localhost',
                    'os' => 'Linux',
                    'account' => auth()->user()->name,
                ],
            ]);
    }
}
