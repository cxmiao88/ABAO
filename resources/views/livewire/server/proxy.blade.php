@php use App\Enums\ProxyTypes; @endphp

<div class="application-settings-form flex w-full flex-col gap-6">
    @if ($server->proxyType())
        @if ($selectedProxy !== 'NONE')
            <form wire:submit="submit" class="contents">
                <x-unsaved-bar action="submit" />

                <fieldset class="contents" wire:loading.attr="disabled"
                    wire:target="submit,resetProxyConfiguration">

                <x-application.settings-section id="server-proxy-overview-section" :title="__('server.px_config_title')"
                    :helper="__('server.px_config_helper')">
                    <x-slot:actions>
                        <div class="flex items-center gap-2">
                            <x-status-badge :status="str($server->proxy->status)->headline()"
                                :type="str($server->proxy->status)->contains('running') ? 'success' : 'neutral'" />
                            @if ($server->proxy->status === 'exited' || $server->proxy->status === 'removing')
                                @can('update', $server)
                                    <x-modal-confirmation :title="__('server.px_switch_confirm_title')"
                                        :buttonTitle="__('server.px_switch_button')" submitAction="changeProxy"
                                        :actions="[__('server.px_switch_action')]"
                                        :warningMessage="__('server.px_switch_warning')"
                                        :step2ButtonText="__('server.px_switch_button')" :confirmWithText="false"
                                        :confirmWithPassword="false" />
                                @endcan
                            @else
                                <x-forms.button canGate="update" :canResource="$server"
                                    wire:click="$dispatch('error', '{{ __('server.px_switch_must_stop') }}')">
                                    {{ __('server.px_switch_button') }}
                                </x-forms.button>
                            @endif
                        </div>
                    </x-slot:actions>

                    @if (
                        $server->proxy->last_applied_settings &&
                            $server->proxy->last_saved_settings !== $server->proxy->last_applied_settings)
                        <x-callout type="warning" :title="__('server.px_config_changed')" />
                    @elseif ($server->hasPendingProxyConfiguration())
                        <x-callout type="warning" :title="__('server.px_restart_required_title')">
                            {{ __('server.px_restart_required_text') }}
                        </x-callout>
                    @else
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-500 dark:bg-white/[0.06] dark:text-fg-dim">
                                <x-reicon name="servers" class="size-4" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-950 dark:text-fg">
                                    {{ str($server->proxyType())->title() }}
                                </p>
                                <p class="mt-1 text-xs text-neutral-500 dark:text-fg-dim">
                                    {{ __('server.px_synchronized') }}
                                </p>
                            </div>
                        </div>
                    @endif
                </x-application.settings-section>

                <x-application.settings-section id="server-proxy-routing-section" :title="__('server.px_routing_title')"
                    :helper="__('server.px_routing_helper')">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-forms.listbox id="generateExactLabels" :label="__('server.px_labels_label')"
                            :helper="__('server.px_labels_helper')"
                            onChange="instantSave" :options="[
                                ['value' => false, 'label' => __('server.px_labels_all')],
                                ['value' => true, 'label' => __('server.px_labels_active_only')],
                            ]" />
                        <x-forms.listbox id="redirectEnabled" :label="__('server.px_unknown_requests_label')"
                            :helper="__('server.px_unknown_requests_helper')"
                            onChange="instantSaveRedirect" :options="[
                                ['value' => false, 'label' => __('server.px_unknown_default_503')],
                                ['value' => true, 'label' => __('server.px_unknown_custom')],
                            ]" />
                        @if ($redirectEnabled)
                            <x-forms.input canGate="update" :canResource="$server"
                                placeholder="https://app.coolify.io" id="redirectUrl"
                                :label="__('server.px_redirect_url_label')"
                                :helper="__('server.px_redirect_url_helper')" />
                        @endif
                    </div>
                </x-application.settings-section>

                @php
                    $proxyTitle =
                        $server->proxyType() === ProxyTypes::TRAEFIK->value
                            ? __('server.px_traefik_title')
                            : __('server.px_caddy_title');
                @endphp

                @if ($server->proxyType() === ProxyTypes::TRAEFIK->value || $server->proxyType() === 'CADDY')
                    <x-application.settings-section id="server-proxy-file-section" :title="$proxyTitle"
                        x-init="$wire.loadProxyConfiguration()"
                        :helper="__('server.px_file_helper')">
                        <x-slot:actions>
                            @can('update', $server)
                                @if ($proxySettings)
                                    <x-modal-confirmation :title="__('server.px_reset_confirm_title')"
                                        :buttonTitle="__('server.px_reset_button')"
                                        submitAction="resetProxyConfiguration" :actions="[
                                            __('server.px_reset_action_1'),
                                            __('server.px_reset_action_2'),
                                        ]" confirmationText="{{ $server->name }}"
                                        :confirmationLabel="__('server.delete_confirm_label')"
                                        :shortConfirmationLabel="__('server.delete_confirm_short_label')"
                                        :step2ButtonText="__('server.px_reset_step2')"
                                        :confirmWithPassword="false" :confirmWithText="true" />
                                @endif
                            @endcan
                        </x-slot:actions>

                        @if ($server->proxyType() === ProxyTypes::TRAEFIK->value)
                            @if ($this->traefikVersionForWarning === 'latest')
                                <x-callout type="warning" :title="__('server.px_unpinned_title')">
                                    {!! __('server.px_unpinned_text', ['version' => $this->latestTraefikVersion]) !!}
                                </x-callout>
                            @endif
                            @if ($this->isTraefikOutdated)
                                <x-callout type="warning" :title="__('server.px_patch_available_title')">
                                    {{ __('server.px_patch_available_text', [
                                        'source' => $server->detected_traefik_version ? __('server.px_running_version') : __('server.px_configured_image'),
                                        'current' => ltrim($this->traefikVersionForWarning, 'v'),
                                        'latest' => $this->latestTraefikVersion,
                                    ]) }}
                                </x-callout>
                            @endif
                            @if ($this->newerTraefikBranchAvailable)
                                <x-callout type="info" :title="__('server.px_minor_available_title')">
                                    {{ __('server.px_minor_available_text', [
                                        'branch' => $this->newerTraefikBranchAvailable,
                                        'patch' => $this->latestNewerTraefikVersion,
                                    ]) }}
                                </x-callout>
                            @endif
                        @elseif ($this->outdatedCaddyImage)
                            <x-server.caddy-image-outdated-callout :image="$this->outdatedCaddyImage" />
                        @endif

                        <div wire:loading.flex wire:target="loadProxyConfiguration"
                            class="min-h-32 items-center justify-center">
                            <x-loading :text="__('server.px_loading')" />
                        </div>

                        @if ($proxySettings)
                            <div class="relative mt-4" wire:loading.class="pointer-events-none opacity-50"
                                wire:target="submit,resetProxyConfiguration" aria-live="polite">
                                <div wire:loading.flex wire:target="submit,resetProxyConfiguration"
                                    class="absolute inset-0 z-20 hidden items-center justify-center rounded-lg bg-white/75 backdrop-blur-[1px] dark:bg-black/55">
                                    <div
                                        class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-medium text-neutral-700 shadow-sm ring-1 ring-neutral-200 dark:bg-coolgray-100 dark:text-fg dark:ring-white/10">
                                        <x-loading />
                                        {{ __('server.px_updating') }}
                                    </div>
                                </div>
                                <x-forms.textarea canGate="update" :canResource="$server" useMonacoEditor
                                    monacoEditorLanguage="yaml"
                                    :label="__('server.px_config_file_label', ['path' => $this->configurationFilePath])"
                                    name="proxySettings" id="proxySettings" rows="30" />
                            </div>
                        @endif
                    </x-application.settings-section>
                @endif
                </fieldset>
            </form>
        @elseif($selectedProxy === 'NONE')
            <x-application.settings-section :title="__('server.px_custom_title')"
                :helper="__('server.px_custom_helper')">
                <x-slot:actions>
                    @can('update', $server)
                        <x-forms.button wire:click.prevent="changeProxy">{{ __('server.px_switch_button') }}</x-forms.button>
                    @endcan
                </x-slot:actions>
                <x-callout type="info" :title="__('server.px_custom_selected_title')">
                    {{ __('server.px_custom_selected_text') }}
                </x-callout>
            </x-application.settings-section>
        @else
            <x-application.settings-section :title="__('server.px_config_title')"
                :helper="__('server.px_choose_helper')">
                @can('update', $server)
                    <div class="grid gap-3 lg:grid-cols-3">
                        @foreach ([
                            ['value' => 'NONE', 'title' => __('server.px_option_custom'), 'description' => __('server.px_option_custom_desc')],
                            ['value' => 'TRAEFIK', 'title' => 'Traefik', 'description' => __('server.px_option_traefik_desc')],
                            ['value' => 'CADDY', 'title' => 'Caddy', 'description' => __('server.px_option_caddy_desc')],
                        ] as $proxyOption)
                            <button type="button" wire:click="selectProxy('{{ $proxyOption['value'] }}')"
                                class="rounded-lg p-4 text-left ring-1 ring-neutral-200 transition-colors hover:bg-neutral-50 dark:ring-white/[0.08] dark:hover:bg-white/[0.04]">
                                <p class="text-sm font-medium text-neutral-950 dark:text-fg">
                                    {{ $proxyOption['title'] }}
                                </p>
                                <p class="mt-1 text-xs leading-5 text-neutral-500 dark:text-fg-dim">
                                    {{ $proxyOption['description'] }}
                                </p>
                            </button>
                        @endforeach
                    </div>
                @else
                    <x-callout type="danger" :title="__('server.px_no_permission_title')">
                        {{ __('server.px_no_permission_text') }}
                    </x-callout>
                @endcan
            </x-application.settings-section>
        @endif
    @else
        <x-application.settings-section :title="__('server.px_config_title')"
            :helper="__('server.px_choose_helper')">
            @can('update', $server)
                <div class="grid gap-3 lg:grid-cols-3">
                    @foreach ([
                        ['value' => 'NONE', 'title' => __('server.px_option_custom'), 'description' => __('server.px_option_custom_desc')],
                        ['value' => 'TRAEFIK', 'title' => 'Traefik', 'description' => __('server.px_option_traefik_desc')],
                        ['value' => 'CADDY', 'title' => 'Caddy', 'description' => __('server.px_option_caddy_desc')],
                    ] as $proxyOption)
                        <button type="button" wire:click="selectProxy('{{ $proxyOption['value'] }}')"
                            class="rounded-lg p-4 text-left ring-1 ring-neutral-200 transition-colors hover:bg-neutral-50 dark:ring-white/[0.08] dark:hover:bg-white/[0.04]">
                            <p class="text-sm font-medium text-neutral-950 dark:text-fg">{{ $proxyOption['title'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-neutral-500 dark:text-fg-dim">
                                {{ $proxyOption['description'] }}
                            </p>
                        </button>
                    @endforeach
                </div>
            @endcan
        </x-application.settings-section>
    @endif
</div>
