<div>
    <x-slot:title>
        {{ __('server.px_config_title') }} | ABao
    </x-slot>
    <livewire:server.navbar :server="$server" />
    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="proxy" activeSubMenu="configuration" />
        @if ($server->isFunctional())
            <div class="w-full">
                <livewire:server.proxy :server="$server" />
            </div>
        @else
            <div class="application-settings-form w-full">
                <x-application.settings-section :title="__('server.menu_proxy')"
                    :helper="__('server.px_show_helper')">
                    <x-empty size="sm" :title="__('server.status_validation_required')"
                        :description="__('server.px_needs_validation')"
                        icon-name="servers" />
                </x-application.settings-section>
            </div>
        @endif
    </div>
</div>
