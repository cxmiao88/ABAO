<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.menu_cloudflare_tunnel') }} | Coolify
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="cloudflare-tunnel" />

        <div class="application-settings-form flex w-full flex-col gap-6">
            <x-application.settings-section id="server-cloudflare-overview-section" :title="__('server.menu_cloudflare_tunnel')"
                :helper="__('server.cf_helper')">
                <x-slot:actions>
                    <x-status-badge :status="$isCloudflareTunnelsEnabled ? __('server.ld_enabled') : __('server.ld_disabled')"
                        :type="$isCloudflareTunnelsEnabled ? 'success' : 'neutral'" />
                </x-slot:actions>

                @if ($isCloudflareTunnelsEnabled)
                    <x-callout type="warning" :title="__('server.cf_disable_warning_title')">
                        {{ __('server.cf_disable_warning_text') }}
                    </x-callout>
                    <div class="mt-4">
                        <x-modal-confirmation :title="__('server.cf_disable_confirm_title')"
                            :buttonTitle="__('server.cf_disable_button')" isErrorButton
                            submitAction="toggleCloudflareTunnels" :actions="$server->ip_previous
                                ? [
                                    __('server.cf_disable_action_1'),
                                    __('server.cf_disable_action_restore'),
                                ]
                                : [
                                    __('server.cf_disable_action_1'),
                                    __('server.cf_disable_action_manual'),
                                    __('server.cf_disable_action_unreachable'),
                                ]"
                            confirmationText="DISABLE CLOUDFLARE TUNNEL"
                            :confirmationLabel="__('server.cf_disable_confirm_label')"
                            :shortConfirmationLabel="__('server.cf_short_label')" />
                    </div>
                @elseif (!$server->isFunctional())
                    <x-callout type="info" :title="__('server.cf_validate_title')">
                        {{ __('server.cf_validate_text_before') }}
                        <button type="button" wire:click="manualCloudflareConfig" class="font-medium underline">
                            {{ __('server.cf_manual_config_link') }}
                        </button>{{ __('server.cf_manual_config_after') }}
                    </x-callout>
                @else
                    <p class="text-sm leading-6 text-neutral-600 dark:text-fg-dim">
                        {{ __('server.cf_overview_text') }}
                    </p>
                @endif
            </x-application.settings-section>

            @if (!$isCloudflareTunnelsEnabled && $server->isFunctional())
                <x-application.settings-section id="server-cloudflare-automated-section" :title="__('server.cf_automated_title')"
                    :helper="__('server.cf_automated_helper')">
                    <x-slot:actions>
                        <a class="button"
                            href="https://coolify.io/docs/knowledge-base/cloudflare/tunnels/server-ssh"
                            target="_blank">
                            {{ __('server.cf_documentation') }}
                            <x-external-link />
                        </a>
                    </x-slot:actions>

                    @cannot('update', $server)
                        <x-callout type="danger" :title="__('server.px_no_permission_title')">
                            {{ __('server.cf_no_permission_text') }}
                        </x-callout>
                    @else
                        <x-process-dialog @automated.window="processDialogOpen = true" closeWithX size="xl">
                            <x-slot:title>{{ __('server.cf_config_dialog_title') }}</x-slot:title>
                            <x-slot:content>
                                <livewire:activity-monitor :header="__('server.sub_logs')" fullHeight />
                            </x-slot:content>
                        </x-process-dialog>
                        <form @submit.prevent="$wire.dispatch('automatedCloudflareConfig')">
                            <div class="grid gap-4 lg:grid-cols-2">
                                <x-forms.input id="cloudflare_token" required :label="__('server.cf_token_label')"
                                    type="password" />
                                <x-forms.input id="ssh_domain" :label="__('server.cf_ssh_domain_label')" required
                                    :helper="__('server.cf_ssh_domain_helper')" />
                            </div>
                            <div class="mt-4 flex justify-end">
                                <x-forms.button type="submit" isHighlighted>{{ __('server.cf_configure_button') }}</x-forms.button>
                            </div>
                        </form>
                    @endcannot
                </x-application.settings-section>

                <x-application.settings-section id="server-cloudflare-manual-section" :title="__('server.cf_manual_title')"
                    :helper="__('server.cf_manual_helper')">
                    @can('update', $server)
                        <x-modal-confirmation :title="__('server.cf_manual_confirm_title')"
                            :buttonTitle="__('server.cf_manual_confirm_button')"
                            submitAction="manualCloudflareConfig" :actions="[
                                __('server.cf_manual_action_1'),
                                __('server.cf_manual_action_2'),
                            ]" confirmationText="I manually configured Cloudflare Tunnel"
                            :confirmationLabel="__('server.cf_type_confirm_label')"
                            :shortConfirmationLabel="__('server.cf_short_label')" />
                    @endcan
                </x-application.settings-section>

                @script
                    <script>
                        $wire.$on('automatedCloudflareConfig', () => {
                            window.dispatchEvent(new CustomEvent('automated'));
                            $wire.$call('automatedCloudflareConfig');
                        });
                    </script>
                @endscript
            @endif
        </div>
    </div>
</div>
