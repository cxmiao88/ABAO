<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('sec_ca_title') }} | ABao
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="ca-certificate" />

        <div class="application-settings-form flex w-full flex-col gap-6">
            <x-application.settings-section id="server-ca-overview-section" title="{{ __('sec_ca_section') }}"
                helper="{{ __('sec_ca_helper') }}">
                <x-slot:actions>
                    @if ($certificateValidUntil)
                        <x-status-badge
                            :status="now()->gt($certificateValidUntil)
                                ? __('sec_expired')
                                : (now()->addDays(30)->gt($certificateValidUntil) ? __('sec_ca_status_expiring') : __('sec_ca_status_valid'))"
                            :type="now()->gt($certificateValidUntil) || now()->addDays(30)->gt($certificateValidUntil)
                                ? 'error'
                                : 'success'" />
                    @endif
                </x-slot:actions>

                <x-callout type="info" title="{{ __('sec_ca_using') }}">
                    {{ __('sec_ca_using_desc') }}
                    <a class="font-medium underline" href="https://coolify.io/docs/databases/ssl" target="_blank">
                        {{ __('sec_ca_read_ssl_guide') }}
                    </a>
                </x-callout>

                <div class="mt-4">
                    <p class="mb-1.5 text-xs font-medium text-neutral-500 dark:text-fg-dim">{{ __('sec_ca_bind_mount') }}</p>
                    <x-forms.copy-input
                        text="- /data/coolify/ssl/coolify-ca.crt:/etc/ssl/certs/coolify-ca.crt:ro" />
                </div>
            </x-application.settings-section>

            <x-application.settings-section id="server-ca-content-section" title="{{ __('sec_ca_content') }}"
                helper="{{ __('sec_ca_content_helper') }}">
                <x-slot:actions>
                    <div class="flex items-center gap-2">
                        @can('view', $server)
                            <x-forms.button wire:click="toggleCertificate" type="button">
                                {{ $showCertificate ? __('sec_ca_hide') : __('sec_ca_show') }}
                            </x-forms.button>
                        @endcan
                        @can('update', $server)
                            <x-modal-confirmation title="{{ __('sec_ca_confirm_change') }}"
                                buttonTitle="{{ __('sec_ca_save') }}" submitAction="saveCaCertificate" :actions="[
                                    __('sec_ca_save_action1'),
                                    __('sec_ca_save_action2'),
                                    __('sec_ca_redeploy'),
                                ]" confirmationText="/data/coolify/ssl/coolify-ca.crt"
                                shortConfirmationLabel="{{ __('sec_ca_path_label') }}"
                                step3ButtonText="{{ __('sec_ca_save') }}" />
                            <x-modal-confirmation title="{{ __('sec_ca_confirm_regenerate') }}"
                                buttonTitle="{{ __('sec_ca_regenerate') }}" submitAction="regenerateCaCertificate" :actions="[
                                    __('sec_ca_regenerate_action1'),
                                    __('sec_ca_regenerate_action2'),
                                    __('sec_ca_redeploy'),
                                ]" confirmationText="/data/coolify/ssl/coolify-ca.crt"
                                shortConfirmationLabel="{{ __('sec_ca_path_label') }}"
                                step3ButtonText="{{ __('sec_ca_regenerate_btn') }}" />
                        @endcan
                    </div>
                </x-slot:actions>

                @if ($showCertificate)
                    <x-forms.textarea canGate="update" :canResource="$server" id="certificateContent"
                        rows="15" label="{{ __('sec_ca_pem_label') }}"
                        placeholder="{{ __('sec_ca_pem_placeholder') }}" />
                @else
                    <div
                        class="flex min-h-72 flex-col items-center justify-center rounded-lg bg-neutral-100/70 px-6 text-center ring-1 ring-neutral-200 dark:bg-black/20 dark:ring-white/[0.08]">
                        <x-reicon name="keys" class="size-8 text-neutral-300 dark:text-fg-faint" />
                        <p class="mt-3 text-sm font-medium text-neutral-950 dark:text-fg">{{ __('sec_ca_hidden') }}</p>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-fg-dim">
                            {{ __('sec_ca_hidden_desc') }}
                        </p>
                    </div>
                @endif
            </x-application.settings-section>
        </div>
    </div>
</div>
