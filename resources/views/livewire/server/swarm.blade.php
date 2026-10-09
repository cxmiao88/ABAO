<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.menu_swarm') }} | ABao
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="swarm" />

        <div class="application-settings-form w-full">
            <x-application.settings-section id="server-swarm-section" :title="__('server.docker_swarm')"
                :helper="__('server.swarm_helper')">
                <x-slot:actions>
                    <x-deprecated-badge />
                </x-slot:actions>

                <x-callout type="warning" :title="__('server.swarm_deprecated_title')">
                    {{ config('deprecations.swarm') }}
                    <a class="font-medium underline" href="https://coolify.io/docs/knowledge-base/docker/swarm"
                        target="_blank">{{ __('server.swarm_read_migration') }}</a>
                </x-callout>

                @if (!$canUseSwarm)
                    <x-callout type="info" :title="__('server.swarm_unavailable_title')" class="mt-4">
                        {{ __('server.swarm_unavailable_text') }}
                    </x-callout>
                @endif

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <x-forms.listbox canGate="update" :canResource="$server" id="isSwarmManager" :label="__('server.swarm_manager_label')"
                        :helper="__('server.swarm_manager_helper')" onChange="instantSave"
                        :options="[
                            ['value' => false, 'label' => __('server.swarm_not_manager')],
                            ['value' => true, 'label' => __('server.swarm_manager')],
                        ]"
                        :disabled="!$canUseSwarm || $server->settings->is_swarm_worker || !auth()->user()->can('update', $server)" />
                    <x-forms.listbox canGate="update" :canResource="$server" id="isSwarmWorker" :label="__('server.swarm_worker_label')"
                        :helper="__('server.swarm_worker_helper')" onChange="instantSave"
                        :options="[
                            ['value' => false, 'label' => __('server.swarm_not_worker')],
                            ['value' => true, 'label' => __('server.swarm_worker')],
                        ]"
                        :disabled="!$canUseSwarm || $server->settings->is_swarm_manager || !auth()->user()->can('update', $server)" />
                </div>
            </x-application.settings-section>
        </div>
    </div>
</div>
