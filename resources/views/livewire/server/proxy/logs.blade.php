<div>
    <x-slot:title>
        {{ __('server.px_logs_title') }} | Coolify
    </x-slot>
    <livewire:server.navbar :server="$server" />
    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="proxy" activeSubMenu="logs" />
        <div class="application-settings-form w-full">
            <x-application.settings-section :title="__('server.px_logs_title')"
                :helper="__('server.px_logs_helper')"
                flush class="logs-settings-section">
                <x-slot:actions>
                    <x-status-badge :status="str($server->proxy->status)->headline()"
                        :type="str($server->proxy->status)->contains('running') ? 'success' : 'neutral'"
                        class="logs-section-status-badge" />
                </x-slot:actions>
                <div class="settings-log-panel">
                    <livewire:project.shared.get-logs :server="$server" container="coolify-proxy"
                        :displayName="__('server.px_logs_container_name')" :collapsible="false" />
                </div>
            </x-application.settings-section>
        </div>
    </div>
</div>
