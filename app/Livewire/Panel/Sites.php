<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Server;
use App\Models\Application;

class Sites extends Component
{
    public $sites = [];

    public function mount()
    {
        $this->sites = Application::ownedByCurrentTeam()->get();
    }

    public function render()
    {
        $server = Server::ownedByCurrentTeam()->first();

        return view('livewire.panel.sites')
            ->layout('layouts.panel', [
                'serverInfo' => [
                    'ip' => $server?->ip ?? 'localhost',
                    'os' => 'Linux',
                    'account' => auth()->user()->name,
                ],
            ]);
    }
}
