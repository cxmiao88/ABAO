<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Server;

class System extends Component
{
    public $server = null;
    public $stats = null;

    public function mount()
    {
        $this->server = Server::ownedByCurrentTeam()->first();
        if ($this->server) {
            $this->stats = $this->server->getSystemStats();
        }
    }

    public function render()
    {
        return view('livewire.panel.system')
            ->layout('layouts.panel', [
                'serverInfo' => [
                    'ip' => $this->server?->ip ?? 'localhost',
                    'os' => 'Linux',
                    'account' => auth()->user()->name,
                ],
            ]);
    }
}
