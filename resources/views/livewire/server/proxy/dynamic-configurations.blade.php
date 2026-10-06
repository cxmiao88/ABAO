<div>
    <x-slot:title>
        {{ __('server.pxdc_title') }} | Coolify
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="proxy" activeSubMenu="dynamic-confs" />

        <div class="application-settings-form flex w-full flex-col gap-6">
            @if ($server->isFunctional())
                <div class="flex flex-wrap items-start justify-between gap-3 px-1">
                    <div>
                        <h2 class="text-sm! font-medium text-neutral-950 dark:text-fg">
                            {{ __('server.pxdc_heading') }}
                        </h2>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-fg-dim">
                            {{ __('server.pxdc_description') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-forms.button wire:click="loadDynamicConfigurations">
                            <x-reicon name="refresh" class="size-3.5" />
                            {{ __('server.pxdc_reload') }}
                        </x-forms.button>
                        @can('update', $server)
                            <x-modal-input :buttonTitle="__('server.add_plus')" :title="__('server.pxdc_new_title')">
                                <livewire:server.proxy.new-dynamic-configuration :server_id="$server->id" />
                            </x-modal-input>
                        @endcan
                    </div>
                </div>

                <div x-init="$wire.initLoadDynamicConfigurations" class="contents">
                    <div wire:loading wire:target="initLoadDynamicConfigurations"
                        class="rounded-lg border border-neutral-200 p-6 dark:border-white/[0.08]">
                        <x-loading :text="__('server.pxdc_loading')" />
                    </div>

                    @if ($contents?->isNotEmpty())
                        @foreach ($contents as $fileName => $value)
                            @php
                                $displayName = str_replace('|', '.', $fileName);
                                $isManagedConfiguration = in_array($displayName, [
                                    'coolify.yaml',
                                    'Caddyfile',
                                    'coolify.caddy',
                                    'default_redirect_503.yaml',
                                    'default_redirect_503.caddy',
                                ]);
                            @endphp
                            <x-application.settings-section :title="$displayName"
                                wire:key="proxy-dynamic-configuration-{{ $fileName }}">
                                <x-slot:actions>
                                    @if ($isManagedConfiguration)
                                        <x-status-badge :status="__('server.pxdc_managed')" type="neutral" />
                                    @else
                                        <livewire:server.proxy.dynamic-configuration-navbar
                                            :server_id="$server->id" :server="$server" :fileName="$fileName"
                                            :value="$value ?? ''" :newFile="false"
                                            wire:key="proxy-navbar-{{ $fileName }}" />
                                    @endif
                                </x-slot:actions>
                                @can('update', $server)
                                    <x-forms.textarea disabled wire:model="contents.{{ $fileName }}"
                                        rows="8" />
                                @else
                                    <p class="text-xs text-neutral-500 dark:text-fg-dim">
                                        {{ __('server.pxdc_no_permission') }}
                                    </p>
                                @endcan
                            </x-application.settings-section>
                        @endforeach
                    @else
                        <x-application.settings-section wire:loading.remove :title="__('server.pxdc_heading')">
                            <x-empty size="sm" :title="__('server.pxdc_empty_title')"
                                :description="__('server.pxdc_empty_description')"
                                icon-name="file-content" />
                        </x-application.settings-section>
                    @endif
                </div>
            @else
                <x-application.settings-section :title="__('server.pxdc_heading')"
                    :helper="__('server.pxdc_helper')">
                    <x-empty size="sm" :title="__('server.status_validation_required')"
                        :description="__('server.pxdc_needs_validation')"
                        icon-name="file-content" />
                </x-application.settings-section>
            @endif
        </div>
    </div>
</div>
