<?php

namespace App\Livewire\Server;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SystemOverview extends Component
{
    public Server $server;

    public ?array $stats = null;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        try {
            $this->stats = $this->server->getSystemStats();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render(): View
    {
        return view('livewire.server.system-overview');
    }
}
