@php
    $sentinelStatusLabel = match ($sentinelStatus) {
        'restarting' => __('server.st_restarting'),
        'waiting' => __('server.st_waiting'),
        'in_sync' => __('server.st_in_sync'),
        default => __('server.st_out_of_sync'),
    };
    $sentinelStatusType = match ($sentinelStatus) {
        'in_sync' => 'success',
        'out_of_sync' => 'warning',
        default => 'neutral',
    };
@endphp

<div class="application-settings-form flex w-full flex-col gap-6" wire:poll.10s="refreshSentinelStatus">
    <form wire:submit.prevent="submit" class="contents">
        {{-- Scope dirty tracking to savable form fields only. Without wire:target,
             Livewire compares the entire component snapshot — so dev-only x-init
             `$wire.set('sentinelCustomDockerImage', …)` (and similar) briefly
             flashes this bar on every page open. --}}
        <x-unsaved-bar action="submit"
            targets="sentinelCustomUrl,sentinelToken" />

        <x-application.settings-section id="server-sentinel-overview-section" :title="__('server.menu_sentinel')"
            :helper="__('server.st_helper')">
            <x-slot:actions>
                <div class="flex items-center gap-2">
                    <x-status-badge :status="$sentinelStatusLabel" :type="$sentinelStatusType" />
                    <x-forms.button wire:click="restartSentinel" canGate="update"
                        :canResource="$server">
                        <x-reicon name="refresh" class="size-3.5" />
                        {{ $sentinelStatus === 'in_sync' ? __('server.st_restart') : __('server.st_sync') }}
                    </x-forms.button>
                </div>
            </x-slot:actions>

            @if ($sentinelStatus === 'out_of_sync')
                <x-callout type="warning" :title="__('server.st_out_of_sync')">
                    <div class="space-y-3">
                        <p>{{ __('server.st_oos_intro') }}</p>
                        @if ($sentinelPushProblem = $server->sentinelPushProblem())
                            <p><span class="font-medium">{{ __('server.st_last_error') }}:</span> <code class="break-all">{{ $sentinelPushProblem }}</code></p>
                        @endif
                        <ul class="list-disc space-y-1 pl-4">
                            <li>{!! __('server.st_oos_check_container') !!}</li>
                            <li>
                                <a class="font-medium underline underline-offset-2"
                                    href="{{ route('server.sentinel.logs', ['server_uuid' => $server->uuid]) }}"
                                    wire:navigate>{{ __('server.st_oos_open_logs') }}</a>
                                {{ __('server.st_oos_review_errors') }}
                            </li>
                            <li>{{ __('server.st_oos_check_config') }}</li>
                        </ul>

                        @if ($server->isLocalhost())
                            <p>{{ __('server.st_oos_localhost_hint') }}</p>
                        @else
                            <div class="space-y-2">
                                <p>{{ __('server.st_oos_outbound_hint') }}</p>
                                @if (filled($sentinelCustomUrl))
                                    <p>
                                        {{ __('server.st_oos_test_prefix') }}
                                        <code class="break-all">curl -fsS {{ escapeshellarg(rtrim($sentinelCustomUrl, '/') . '/api/health') }}</code>.
                                    </p>
                                @else
                                    <p>{{ __('server.st_oos_set_url_first') }}</p>
                                @endif
                                <p>{{ __('server.st_oos_check_network') }}</p>
                            </div>
                        @endif
                    </div>
                </x-callout>
            @elseif ($sentinelStatus === 'in_sync')
                <p class="text-sm text-neutral-500 dark:text-fg-dim">
                    {{ __('server.st_in_sync_text') }}
                </p>
            @elseif ($sentinelStatus === 'restarting')
                <p class="text-sm text-neutral-500 dark:text-fg-dim">{{ __('server.st_restarting_text') }}</p>
            @else
                <p class="text-sm text-neutral-500 dark:text-fg-dim">{{ __('server.st_waiting_text') }}</p>
            @endif
        </x-application.settings-section>

        @if ($server->isSentinelEnabled())
            <x-application.settings-section id="server-sentinel-connection-section" :title="__('server.connection_title')"
                :helper="__('server.st_connection_helper')">
                <x-slot:actions>
                    <div class="flex items-center gap-2">
                        @can('manageSentinel', $server)
                            <x-modal-confirmation :title="__('server.st_restore_title')"
                                :buttonTitle="__('server.st_restore_button')" submitAction="restoreDefaultConfiguration"
                                :actions="[
                                    __('server.st_restore_action_1'),
                                    __('server.st_restore_action_2'),
                                    __('server.st_restore_action_3'),
                                    __('server.st_restore_action_4'),
                                ]" :warningMessage="__('server.st_restore_warning')"
                                :confirmWithText="false" :confirmWithPassword="false"
                                :step2ButtonText="__('server.st_restore_button')" />
                        @endcan
                        <x-forms.button canGate="update" :canResource="$server"
                            wire:click="regenerateSentinelToken">
                            {{ __('server.st_regenerate_token') }}
                        </x-forms.button>
                    </div>
                </x-slot:actions>
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.input canGate="update" :canResource="$server" id="sentinelCustomUrl"
                        required :label="__('server.st_coolify_url_label')"
                        :helper="__('server.st_coolify_url_helper')" />
                    <x-forms.input canGate="update" :canResource="$server" type="password"
                        id="sentinelToken" :label="__('server.st_token_label')" required
                        :helper="__('server.st_token_helper')" />
                </div>
            </x-application.settings-section>

            @if (isDev())
                <x-application.settings-section id="server-sentinel-development-section"
                    :title="__('server.st_dev_title')"
                    :helper="__('server.st_dev_helper')">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-forms.listbox id="isSentinelDebugEnabled" :label="__('server.st_debug_label')"
                            onChange="instantSave" :options="[
                                ['value' => false, 'label' => __('server.st_debug_standard')],
                                ['value' => true, 'label' => __('server.st_debug_enabled')],
                            ]" />
                        <div x-data="{
                            customImage: localStorage.getItem('sentinel_custom_docker_image_{{ $server->uuid }}') || '',
                            async applyCustomImage() {
                                localStorage.setItem('sentinel_custom_docker_image_{{ $server->uuid }}', this.customImage);
                                await $wire.set('sentinelCustomDockerImage', this.customImage || null);
                                await $wire.restartSentinel();
                            }
                        }"
                            @sentinel-defaults-restored.window="localStorage.removeItem('sentinel_custom_docker_image_{{ $server->uuid }}'); customImage = ''"
                            {{-- Only hydrate Livewire when a real override exists. Unconditional
                                 $wire.set('', null→'') on every open marks the component dirty and
                                 flashes the unsaved bar until the round-trip completes. --}}
                            x-init="if (customImage) { $wire.set('sentinelCustomDockerImage', customImage) }">
                            <div class="flex items-end gap-2">
                                <div class="min-w-0 flex-1">
                                    <x-forms.input canGate="update" :canResource="$server" x-model="customImage"
                                        placeholder="sentinel:latest" :label="__('server.st_custom_image_label')"
                                        :helper="__('server.st_custom_image_helper')" />
                                </div>
                                <x-forms.button canGate="update" :canResource="$server"
                                    x-on:click="applyCustomImage()">
                                    {{ __('server.st_apply_and_restart') }}
                                </x-forms.button>
                            </div>
                        </div>
                    </div>
                </x-application.settings-section>
            @endif
        @endif
    </form>
</div>
