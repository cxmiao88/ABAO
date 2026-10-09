<div>
    <x-slot:title>
        {{ __('server.st_logs_title') }} | ABao
    </x-slot>
    <livewire:server.navbar :server="$server" />
    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="sentinel" />
        <div class="application-settings-form w-full">
            <x-application.settings-section :title="__('server.st_logs_title')"
                :helper="__('server.st_logs_helper')"
                flush class="logs-settings-section">
                @if ($server->isSentinelEnabled())
                    @php
                        $sentinelStatus = $server->sentinelStatus();
                        [$sentinelStatusLabel, $sentinelStatusType] = match ($sentinelStatus) {
                            'waiting' => [__('server.st_waiting'), 'neutral'],
                            'in_sync' => [__('server.st_in_sync'), 'success'],
                            default => [__('server.st_out_of_sync'), 'warning'],
                        };
                    @endphp
                    <x-slot:actions>
                        <x-status-badge :status="$sentinelStatusLabel" :type="$sentinelStatusType"
                            class="logs-section-status-badge" />
                    </x-slot:actions>
                    <div class="settings-log-panel">
                        <livewire:project.shared.get-logs :server="$server" container="coolify-sentinel"
                            displayName="Sentinel" :collapsible="false" />
                    </div>
                @else
                    <x-empty size="sm" :title="__('server.st_unavailable_title')"
                        :description="__('server.st_unavailable_description')"
                        icon-name="dashboard" />
                @endif
            </x-application.settings-section>
        </div>
    </div>
</div>
