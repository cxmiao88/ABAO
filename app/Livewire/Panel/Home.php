<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Server;

class Home extends Component
{
    public $servers = [];
    public $selectedServerUuid = null;
    public $stats = null;
    public $overview = [
        'servers' => 0,
        'applications' => 0,
        'databases' => 0,
        'running' => 0,
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $servers = Server::ownedByCurrentTeam()->get();
        $this->servers = $servers;

        $this->overview['servers'] = $servers->count();
        $this->overview['running'] = $servers->filter(fn ($s) => $s->isFunctional())->count();

        $this->overview['applications'] = \App\Models\Application::ownedByCurrentTeam()->count();
        $this->overview['databases'] = \App\Models\StandalonePostgresql::ownedByCurrentTeam()->count()
            + \App\Models\StandaloneMysql::ownedByCurrentTeam()->count()
            + \App\Models\StandaloneRedis::ownedByCurrentTeam()->count()
            + \App\Models\StandaloneMongodb::ownedByCurrentTeam()->count()
            + \App\Models\StandaloneMariadb::ownedByCurrentTeam()->count();

        if ($servers->count() > 0) {
            $selected = $servers->firstWhere('uuid', $this->selectedServerUuid) ?? $servers->first();
            $this->selectedServerUuid = $selected->uuid;
            $this->stats = $selected->getSystemStats();
        } else {
            $this->stats = null;
        }
    }

    public function selectServer($uuid)
    {
        $this->selectedServerUuid = $uuid;
        $server = Server::ownedByCurrentTeam()->firstWhere('uuid', $uuid);
        $this->stats = $server ? $server->getSystemStats() : null;
    }

    public function render()
    {
        $server = $this->servers->firstWhere('uuid', $this->selectedServerUuid) ?? $this->servers->first();

        return view('livewire.panel.home')
            ->layout('layouts.panel', [
                'serverInfo' => [
                    'ip' => $server?->ip ?? 'localhost',
                    'os' => 'Linux',
                ],
            ]);
    }
}
