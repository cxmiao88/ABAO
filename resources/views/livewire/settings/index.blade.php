<div>
    <x-slot:title>
        {{ __('set_settings_title') }}
    </x-slot>

    <x-settings.layout>
        <form wire:submit="submit" class="application-settings-form flex w-full min-w-0 flex-col gap-6">
            {{-- instance_timezone auto-saves via $wire.set + submit; exclude it so
                 the bar does not flash while the snapshot catches up. --}}
            <x-unsaved-bar action="submit"
                targets="fqdn,instance_name,public_ipv4,public_ipv6,dev_helper_version" />
            <x-application.settings-section :title="__('set_general')">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div @class([
                        'lg:col-span-2' => !str_starts_with(strtolower($fqdn ?? ''), 'https://'),
                    ])>
                        <x-forms.input canGate="update" :canResource="$settings" id="fqdn" :label="__('set_url')"
                            helper="{!! __('set_url_helper') !!}"
                            placeholder="https://coolify.yourdomain.com" />
                    </div>

                    @if (str_starts_with(strtolower($fqdn ?? ''), 'https://'))
                        <div>
                            <x-forms.listbox canGate="update" :canResource="$settings"
                                id="is_dashboard_force_https_enabled" :label="__('set_redirect_https')"
                                onChange="submit"
                                helper="{{ __('set_redirect_https_helper') }}"
                                :options="[
                                    ['value' => true, 'label' => __('set_enabled')],
                                    ['value' => false, 'label' => __('set_disabled')],
                                ]" />
                        </div>
                    @endif

                    <x-forms.input canGate="update" :canResource="$settings" id="instance_name" :label="__('set_name')"
                        placeholder="Coolify" helper="{{ __('set_name_helper') }}" />

                    {{-- Use searchable-listbox so the label row (h-4) and control height match
                         sibling x-forms.input fields (Name). onChange auto-saves like before. --}}
                    <x-forms.searchable-listbox id="instance_timezone" :label="__('set_instance_timezone')"
                        helper="{{ __('set_instance_timezone_helper') }}"
                        searchPlaceholder="{{ __('set_search_timezones') }}" emptyText="{{ __('set_no_timezone') }}"
                        onChange="submit" :options="collect($this->timezones)->map(fn ($timezone) => [
                            'value' => $timezone,
                            'label' => $timezone,
                        ])->all()" :disabled="! auth()->user()->can('update', $settings)" />
                </div>
            </x-application.settings-section>

            <x-application.settings-section :title="__('set_network_addresses')">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.input canGate="update" :canResource="$settings" id="public_ipv4" type="password"
                        :label="__('set_public_ipv4')"
                        helper="{{ __('set_public_ipv4_helper') }}"
                        placeholder="1.2.3.4" autocomplete="new-password" />
                    <x-forms.input canGate="update" :canResource="$settings" id="public_ipv6" type="password"
                        :label="__('set_public_ipv6')"
                        helper="{{ __('set_public_ipv6_helper') }}"
                        placeholder="2001:db8::1" autocomplete="new-password" />
                </div>
            </x-application.settings-section>

            @if (isDev())
                <x-application.settings-section :title="__('set_dev_helper')">
                    <x-forms.input canGate="update" :canResource="$settings" id="dev_helper_version"
                        :label="__('set_version_override')"
                        helper="{{ __('set_version_override_helper', ['version' => config('constants.coolify.helper_version')]) }}"
                        placeholder="{{ config('constants.coolify.helper_version') }}" />
                </x-application.settings-section>
            @endif
        </form>

    <x-domain-conflict-modal :conflicts="$domainConflicts" :showModal="$showDomainConflictModal"
        confirmAction="confirmDomainUsage">
        <x-slot:consequences>
            <ul class="mt-2 ml-4 list-disc">
                <li>{{ __('set_domain_conflict_1') }}</li>
                <li>{{ __('set_domain_conflict_2') }}</li>
                <li>{{ __('set_domain_conflict_3') }}</li>
                <li>{{ __('set_domain_conflict_4') }}</li>
            </ul>
        </x-slot:consequences>
    </x-domain-conflict-modal>
    </x-settings.layout>
</div>
