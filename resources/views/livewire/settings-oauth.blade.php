<div>
    <x-slot:title>
        {{ __('set_oauth_title') }}
    </x-slot>

    <x-settings.layout>
        <x-slot:submenu>
            <div
                x-data="{ activeProvider: location.hash.slice(1).replace('-oauth-section', '') || @js($selectedProvider ?? array_key_first($oauth_settings_map)) }"
                @hashchange.window="activeProvider = location.hash.slice(1).replace('-oauth-section', '')">
                <nav aria-label="{{ __('set_oauth_providers') }}" class="grid gap-0.5 py-1">
                    @foreach ($oauth_settings_map as $provider => $oauth_setting)
                        <a href="#{{ $provider }}-oauth-section" class="menu-item min-h-8! py-1! text-[12px]!"
                            :class="{ 'menu-item-active': activeProvider === '{{ $provider }}' }"
                            @click.prevent="activeProvider = '{{ $provider }}'; history.replaceState(null, '', '#{{ $provider }}-oauth-section'); window.scrollToSettingsSection?.('{{ $provider }}-oauth-section')">
                            <span class="menu-item-icon bg-current"
                                style="mask: url('{{ asset('svgs/' . $provider . '.svg') }}') center / contain no-repeat; -webkit-mask: url('{{ asset('svgs/' . $provider . '.svg') }}') center / contain no-repeat;"></span>
                            <span class="menu-item-label">{{ $oauth_setting['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </x-slot:submenu>

        <form wire:submit="submit" class="application-settings-form flex w-full min-w-0 flex-col gap-6">
            <x-unsaved-bar action="submit" />

            <x-application.settings-section :title="__('set_registration')"
                description="{{ __('set_oauth_registration_desc') }}">
                <x-forms.checkbox canGate="update" :canResource="$settings"
                    id="disable_registration_when_oauth_enabled"
                    :label="__('set_oauth_disable_pw')"
                    helper="{{ __('set_oauth_disable_pw_helper') }}"
                    instantSave="saveRegistrationPolicy" />
            </x-application.settings-section>

            @foreach ($oauth_settings_map as $provider => $oauth_setting)
                <x-application.settings-section id="{{ $provider }}-oauth-section" class="scroll-mt-28"
                    title="{{ $oauth_setting['label'] }}">
                    <x-slot:actions>
                        <div x-data="{ enabled: @js((bool) $oauth_setting['enabled']), provider: @js($provider) }">
                            <x-forms.button canGate="update" :canResource="$settings" type="button"
                                :isHighlighted="!$oauth_setting['enabled']"
                                x-on:click="
                                    if (!enabled) {
                                        const invalidField = [...$el.closest('section').querySelectorAll('[required]')]
                                            .find(field => !field.checkValidity());
                                        if (invalidField) { invalidField.reportValidity(); return; }
                                    }
                                    $wire.toggleProvider(provider);
                                ">
                                {{ $oauth_setting['enabled'] ? __('set_disable') : __('set_enable') }}
                            </x-forms.button>
                        </div>
                    </x-slot:actions>

                    <div class="grid gap-4 lg:grid-cols-2">
                        @if ($provider === 'oidc')
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.redirect_uri"
                                placeholder="{{ oauth_default_redirect_uri($provider) }}" :label="__('set_redirect_uri')" />
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.base_url" :label="__('set_issuer_url')" required
                                helper="{!! __('set_issuer_url_helper') !!}" />
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.client_id" :label="__('set_client_id')" required />
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.client_secret" type="password"
                                :label="__('set_client_secret')" autocomplete="new-password" required />
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.scopes" :label="__('set_scopes')"
                                helper="{{ __('set_scopes_helper') }}" />
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.clock_skew_seconds" type="number"
                                :label="__('set_clock_skew')" />
                            <div class="lg:col-span-2">
                                <x-forms.input canGate="update" :canResource="$settings"
                                    id="oauth_settings_map.{{ $provider }}.custom_label" :label="__('set_login_button_label')"
                                    placeholder="{{ __('set_login_with_sso') }}" />
                            </div>
                        @else
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.redirect_uri"
                                placeholder="{{ oauth_default_redirect_uri($provider) }}" :label="__('set_redirect_uri')" />
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.client_id" :label="__('set_client_id')" required />
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.client_secret" type="password"
                                :label="__('set_client_secret')" autocomplete="new-password" required />
                        @endif

                        @if ($provider === 'azure')
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.tenant" :label="__('set_tenant')" required />
                        @endif

                        @if ($provider === 'google')
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.tenant"
                                helper="{{ __('set_hosted_domain_helper') }}"
                                :label="__('set_hosted_domain')" />
                        @endif

                        @if (in_array($provider, ['authentik', 'clerk', 'zitadel', 'gitlab'], true))
                            <x-forms.input canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.base_url" :label="__('set_base_url')"
                                :required="in_array($provider, ['authentik', 'clerk'], true)" />
                        @endif

                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        @if ($provider === 'oidc')
                            <x-forms.checkbox canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.allow_registration"
                                :label="__('set_allow_oidc_user')"
                                helper="{{ __('set_allow_oidc_user_helper') }}" />
                            <x-forms.checkbox canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.require_email_verified"
                                :label="__('set_require_verified_email')" />
                            <x-forms.checkbox canGate="update" :canResource="$settings"
                                id="oauth_settings_map.{{ $provider }}.use_pkce" :label="__('set_use_pkce')" />
                        @endif
                        <x-forms.checkbox canGate="update" :canResource="$settings"
                            id="oauth_settings_map.{{ $provider }}.auto_join_root_team"
                            :label="__('set_auto_join_root')"
                            helper="{{ __('set_auto_join_root_helper') }}" />
                    </div>
                </x-application.settings-section>
            @endforeach
        </form>
    </x-settings.layout>
</div>
