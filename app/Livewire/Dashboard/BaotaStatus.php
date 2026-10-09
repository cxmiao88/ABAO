<?php

namespace App\Livewire\Dashboard;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BaotaStatus extends Component
{
    public Server $server;

    public function loadData(): void
    {
        try {
            $stats = $this->server->getSystemStats();
            $this->dispatch("dashboard-baota-status-{$this->server->uuid}", ['stats' => $stats]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.baota-status');
    }
}
