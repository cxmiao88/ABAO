<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.destinations_title') }} | ABao
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="destinations" />

        <div class="application-settings-form flex w-full flex-col gap-6">
            @if ($server->isSwarm())
                <x-callout type="warning" :title="__('server.swarm_deprecated_title')">
                    {{ config('deprecations.swarm') }}
                </x-callout>
            @endif

            @if ($server->isFunctional())
                <x-application.settings-section id="server-destinations-section" :title="__('server.menu_destinations')"
                    :helper="__('server.destinations_helper')" flush>
                    <x-slot:actions>
                        <div class="flex items-center gap-2">
                            <x-forms.button canGate="update" :canResource="$server" wire:click="scan">
                                <x-reicon name="refresh" class="size-3.5" />
                                {{ __('server.scan_networks') }}
                            </x-forms.button>
                            @can('update', $server)
                                <x-modal-input :buttonTitle="__('server.add_plus')" :title="__('server.new_destination')">
                                    <livewire:destination.new.docker :server_id="$server->id" />
                                </x-modal-input>
                            @endcan
                        </div>
                    </x-slot:actions>

                    @forelse ($server->standaloneDockers->concat($server->swarmDockers) as $destination)
                        <a href="{{ route('destination.show', ['destination_uuid' => data_get($destination, 'uuid')]) }}"
                            {{ wireNavigate() }}
                            class="flex items-center gap-4 border-b border-neutral-200 px-4 py-3 transition-colors last:border-b-0 hover:bg-neutral-50 dark:border-white/[0.08] dark:hover:bg-white/[0.03]">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-500 dark:bg-white/[0.06] dark:text-fg-dim">
                                <x-reicon name="destinations" class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-neutral-950 dark:text-fg">
                                    {{ data_get($destination, 'network') }}
                                </p>
                                <p class="mt-0.5 text-xs text-neutral-500 dark:text-fg-dim">
                                    {{ $server->swarmDockers->contains('id', data_get($destination, 'id')) ? __('server.docker_swarm') : __('server.standalone_docker') }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <x-empty size="sm" :title="__('server.no_destinations')"
                            :description="__('server.no_destinations_description')"
                            icon-name="destinations" />
                    @endforelse
                </x-application.settings-section>

                @if ($networks->count() > 0)
                    <x-application.settings-section id="server-found-networks-section" :title="__('server.discovered_networks')"
                        :helper="__('server.discovered_networks_helper')"
                        flush>
                        @foreach ($networks as $network)
                            <div
                                class="flex items-center justify-between gap-4 border-b border-neutral-200 px-4 py-3 last:border-b-0 dark:border-white/[0.08]">
                                <div>
                                    <p class="text-sm font-medium text-neutral-950 dark:text-fg">
                                        {{ data_get($network, 'Name') }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-fg-dim">{{ __('server.docker_network') }}</p>
                                </div>
                                <x-forms.button canGate="update" :canResource="$server"
                                    wire:click="add('{{ data_get($network, 'Name') }}')">
                                    {{ __('server.add_destination') }}
                                </x-forms.button>
                            </div>
                        @endforeach
                    </x-application.settings-section>
                @endif
            @else
                <x-application.settings-section :title="__('server.menu_destinations')"
                    :helper="__('server.destinations_helper')">
                    <x-empty size="sm" :title="__('server.status_validation_required')"
                        :description="__('server.destinations_needs_validation')"
                        icon-name="destinations" />
                </x-application.settings-section>
            @endif
        </div>
    </div>
</div>
