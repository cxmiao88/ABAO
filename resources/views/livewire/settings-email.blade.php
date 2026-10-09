<div>
    <x-slot:title>
        {{ __('set_email_title') }}
    </x-slot>

    <x-settings.layout>
    <div class="application-settings-form mx-auto flex w-full max-w-none min-w-0 flex-col gap-6">
        {{-- One bar for the whole page. Three stacked bars made Save run
             submitResend(), which required an API key even when Resend was off. --}}
        <x-unsaved-bar action="submit"
            targets="smtpFromName,smtpFromAddress,smtpHost,smtpPort,smtpEncryption,smtpUsername,smtpPassword,smtpTimeout,smtpEhloDomain,resendApiKey" />

        <form wire:submit="submit">
            <x-application.settings-section :title="__('set_sender')">
                <x-slot:actions>
                    @include('livewire.partials.settings-email-send-test')
                </x-slot:actions>
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.input required id="smtpFromName" helper="{{ __('set_from_name_helper') }}"
                        :label="__('set_from_name')" />
                    <x-forms.input required id="smtpFromAddress" helper="{{ __('set_from_address_helper') }}"
                        :label="__('set_from_address')" />
                </div>
            </x-application.settings-section>
        </form>

        <form wire:submit.prevent="submitSmtp">
            <x-application.settings-section :title="__('set_smtp_server')">
                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="lg:col-span-3">
                        <div class="w-full sm:w-72">
                            <x-forms.listbox id="smtpEnabled" :label="__('set_smtp_delivery')"
                                onChange="instantSaveSmtp" :options="[
                                    ['value' => true, 'label' => __('set_enabled')],
                                    ['value' => false, 'label' => __('set_disabled')],
                                ]" />
                        </div>
                    </div>
                    <x-forms.input required id="smtpHost" placeholder="smtp.mailgun.org" :label="__('set_host')" />
                    <x-forms.input required id="smtpPort" type="number" placeholder="587" :label="__('set_port')" />
                    <x-forms.listbox required id="smtpEncryption" :label="__('set_encryption')" :options="[
                        ['value' => 'starttls', 'label' => __('set_starttls')],
                        ['value' => 'tls', 'label' => __('set_tls_ssl')],
                        ['value' => 'none', 'label' => __('set_none')],
                    ]" />
                    <x-forms.input id="smtpUsername" :label="__('set_username')" />
                    <x-forms.input id="smtpPassword" type="password" :label="__('set_password')"
                        autocomplete="new-password" />
                    <x-forms.input id="smtpTimeout" type="number"
                        helper="{{ __('set_timeout_helper') }}" :label="__('set_timeout')" />
                    <x-forms.input id="smtpEhloDomain" placeholder="coolify.example.com"
                        helper="{{ __('set_ehlo_helper') }}"
                        :label="__('set_ehlo_domain')" />
                </div>
            </x-application.settings-section>
        </form>

        <form wire:submit.prevent="submitResend">
            <x-application.settings-section :title="__('set_resend')">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.listbox id="resendEnabled" :label="__('set_resend_delivery')"
                        onChange="instantSaveResend" :options="[
                            ['value' => true, 'label' => __('set_enabled')],
                            ['value' => false, 'label' => __('set_disabled')],
                        ]" />
                    <x-forms.input type="password" id="resendApiKey" placeholder="{{ __('set_api_key') }}"
                        :required="$resendEnabled" :label="__('set_api_key')" autocomplete="new-password" />
                </div>
            </x-application.settings-section>
        </form>
    </div>
    </x-settings.layout>
</div>
