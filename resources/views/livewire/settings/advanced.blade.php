<div>
    <x-slot:title>
        {{ __('set_advanced_title') }}
    </x-slot>

    <x-settings.layout>
        <form wire:submit="submit" class="application-settings-form flex min-w-0 flex-col gap-6">
            {{-- Scope dirty tracking to fields that need an explicit Save. Instant-save
                 listboxes (API, MCP, telemetry, …) update the snapshot on the server
                 immediately; without wire:target they briefly flash this bar. --}}
            <x-unsaved-bar action="submit"
                targets="custom_dns_servers,allowed_ips,webhook_allowed_internal_hosts,webhook_allow_localhost,domain_connect_private_key,image_cdn_url" />

            <x-application.settings-section id="access-section" :title="__('set_access')">
                <div class="grid gap-4 lg:grid-cols-2">
                     <x-forms.listbox id="is_registration_enabled" :label="__('set_registration')"
                        helper="{{ __('set_registration_helper') }}"
                        onChange="instantSave" :options="[
                            ['value' => true, 'label' => __('set_anyone_register')],
                            ['value' => false, 'label' => __('set_registration_disabled')],
                         ]" />
                     <x-forms.listbox canGate="update" :canResource="$settings"
                         id="disable_registration_when_oauth_enabled" :label="__('set_pw_registration_oauth')"
                         helper="{{ __('set_pw_registration_oauth_helper') }}"
                         onChange="instantSave" :options="[
                             ['value' => false, 'label' => __('set_allow_pw_registration')],
                             ['value' => true, 'label' => __('set_disable_when_oauth')],
                         ]" />
                    <x-forms.listbox id="disable_two_step_confirmation" :label="__('set_destructive_confirm')"
                        helper="{{ __('set_destructive_confirm_helper') }}"
                        onChange="instantSave" :options="[
                            ['value' => false, 'label' => __('set_require_two_step')],
                            ['value' => true, 'label' => __('set_skip_two_step')],
                        ]" />
                </div>
            </x-application.settings-section>

            <x-application.settings-section id="dns-section" :title="__('set_dns_validation')">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.listbox id="is_dns_validation_enabled" :label="__('set_dns_validation')"
                        helper="{{ __('set_dns_validation_helper') }}" onChange="instantSave" :options="[
                            ['value' => true, 'label' => __('set_enabled')],
                            ['value' => false, 'label' => __('set_disabled')],
                        ]" />
                    <x-forms.input id="custom_dns_servers" :label="__('set_custom_dns_servers')"
                        helper="{{ __('set_custom_dns_servers_helper') }}"
                        placeholder="1.1.1.1, 8.8.8.8" />
                </div>
            </x-application.settings-section>

            @if (isCloud())
                <x-application.settings-section id="domain-connect-section" :title="__('set_domain_connect')"
                    helper="{{ __('set_domain_connect_helper') }}">
                    <div class="grid gap-4">
                        <x-forms.input id="domain_connect_private_key" type="password" allowToPeak
                            :label="__('set_dc_private_key')"
                            helper="{{ __('set_dc_private_key_helper') }}"
                            placeholder="-----BEGIN PRIVATE KEY-----" />
                        @if (filled(data_get($settings, 'domain_connect_private_key')))
                            <div class="flex flex-wrap items-center gap-2">
                                <x-status-badge status="{{ __('set_key_configured') }}" type="success" />
                                <x-forms.button type="button" wire:click="clearDomainConnectPrivateKey" isError>
                                    {{ __('set_remove_key') }}
                                </x-forms.button>
                            </div>
                        @elseif (filled(config('services.domain_connect.private_key')))
                            <x-status-badge status="{{ __('set_env_key_status') }}" type="neutral" />
                        @else
                            <x-callout type="info" :title="__('set_not_configured')">
                                {{ __('set_dc_not_configured_desc') }}
                            </x-callout>
                        @endif
                    </div>
                </x-application.settings-section>
            @endif

            <x-application.settings-section id="api-section" :title="__('set_api_mcp')">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.listbox id="is_api_enabled" :label="__('set_api_access')"
                        helper="{{ __('set_api_access_helper') }}" onChange="instantSave"
                        :options="[
                            ['value' => true, 'label' => __('set_enabled')],
                            ['value' => false, 'label' => __('set_disabled')],
                        ]" />
                    <x-forms.listbox id="is_mcp_server_enabled" :label="__('set_mcp_server')"
                        helper="{{ __('set_mcp_server_helper') }}" onChange="instantSave"
                        :options="[
                            ['value' => true, 'label' => __('set_enabled')],
                            ['value' => false, 'label' => __('set_disabled')],
                        ]" />
                    <div class="lg:col-span-2">
                        <x-forms.input id="allowed_ips" :label="__('set_allowed_api_ips')"
                            helper="{{ __('set_allowed_api_ips_helper') }}"
                            placeholder="192.168.1.100, 10.0.0.0/8" />
                    </div>
                </div>
                @if ($is_api_enabled && (empty($allowed_ips) || in_array('0.0.0.0', array_map('trim', explode(',', $allowed_ips ?? '')))))
                    <x-callout type="warning" :title="__('set_api_open_warning')" class="mt-4">
                        {{ __('set_api_open_warning_desc') }}
                    </x-callout>
                @endif
                @if ($is_mcp_server_enabled)
                    <x-callout type="info" :title="__('set_mcp_endpoint')" class="mt-4">
                        <code>{{ url('/mcp') }}</code> {{ __('set_mcp_endpoint_desc') }}
                    </x-callout>
                @endif
            </x-application.settings-section>

            <x-application.settings-section id="endpoint-section" :title="__('set_outbound_endpoints')">
                <div class="flex flex-col gap-4">
                    <x-forms.textarea id="webhook_allowed_internal_hosts" rows="4"
                        :label="__('set_allowed_internal_targets')"
                        helper="{{ __('set_allowed_internal_targets_helper') }}"
                        placeholder="hooks.company.local, 10.50.0.0/16" />
                    <div class="max-w-md">
                        <x-forms.listbox id="webhook_allow_localhost" :label="__('set_localhost_targets')"
                            helper="{{ __('set_localhost_targets_helper') }}" :options="[
                                ['value' => true, 'label' => __('set_allowed')],
                                ['value' => false, 'label' => __('set_blocked')],
                            ]" />
                    </div>
                </div>
            </x-application.settings-section>

            <x-application.settings-section id="interface-section" :title="__('set_interface_telemetry')">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.listbox id="is_wire_navigate_enabled" :label="__('set_navigation')"
                        helper="{{ __('set_navigation_helper') }}" onChange="instantSave" :options="[
                            ['value' => true, 'label' => __('set_spa_navigation')],
                            ['value' => false, 'label' => __('set_full_page_navigation')],
                        ]" />
                    <x-forms.listbox id="do_not_track" :label="__('set_anonymous_telemetry')"
                        helper="{{ __('set_anonymous_telemetry_helper') }}" onChange="instantSave" :options="[
                            ['value' => false, 'label' => __('set_enabled')],
                            ['value' => true, 'label' => __('set_disabled')],
                        ]" />
                    <div class="flex flex-col gap-2">
                        <x-forms.listbox id="is_sponsorship_popup_enabled" :label="__('set_sponsorship_reminders')"
                            helper="{{ __('set_sponsorship_reminders_helper') }}" onChange="instantSave" :options="[
                                ['value' => true, 'label' => __('set_enabled')],
                                ['value' => false, 'label' => __('set_disabled')],
                            ]" />
                        @if (isDev())
                            <x-forms.button type="button" @click="$dispatch('show-sponsorship-reminder')">
                                {{ __('set_show_sponsorship_reminder') }}
                            </x-forms.button>
                        @endif
                    </div>
                </div>
            </x-application.settings-section>

            <x-application.settings-section id="avatar-storage-section" :title="__('set_image_storage')"
                helper="{{ __('set_image_storage_helper') }}">
                <div class="flex max-w-md flex-col gap-4">
                    <x-forms.listbox id="avatar_storage" :label="__('set_storage_destination')" onChange="instantSave"
                        :options="$avatar_storage_options" />
                    <x-forms.input id="image_cdn_url" :label="__('set_image_cdn_url')"
                        helper="{{ __('set_image_cdn_url_helper') }}" placeholder="https://images.example.com" />
                </div>
                @if (count($avatar_storage_options) === 1)
                    <x-callout type="info" :title="__('set_no_s3_storage')" class="mt-4">
                        {{ __('set_no_s3_storage_desc') }}
                    </x-callout>
                @endif
            </x-application.settings-section>
        </form>
    </x-settings.layout>
</div>
