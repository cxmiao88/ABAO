<div>
    <x-slot:title>
        {{ __('server.sub_tls_certificates') }} | ABao
    </x-slot>
    <livewire:server.navbar :server="$server" />
    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="proxy" activeSubMenu="certificates" />
        @if ($server->isFunctional() && $server->proxyType() === \App\Enums\ProxyTypes::TRAEFIK->value)
            <div class="w-full">
                <livewire:server.proxy.certificates :server="$server" />
            </div>
        @else
            <div class="application-settings-form w-full">
                <x-application.settings-section :title="__('server.sub_tls_certificates')"
                    :helper="__('server.cert_show_helper')">
                    <x-empty size="sm" :title="$server->isFunctional() ? __('server.cert_traefik_required') : __('server.status_validation_required')"
                        :description="$server->isFunctional() ? __('server.cert_traefik_only') : __('server.cert_needs_validation')"
                        icon-name="servers" />
                </x-application.settings-section>
            </div>
        @endif
    </div>
</div>
