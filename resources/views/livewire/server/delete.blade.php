<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.delete_title') }} | ABao
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="danger" />

        <div class="application-settings-form w-full">
            @if (! $server->is_coolify_host)
                <x-application.settings-section id="server-danger-section" :title="__('server.delete_section_title')"
                    :helper="__('server.delete_section_helper')"
                    class="server-danger-section">
                    <x-danger-zone :title="__('server.delete_cannot_undo')">
                        <p>
                        {{ __('server.delete_will_remove') }}
                        @if ($server->definedResources()->count() > 0)
                            {{ __('server.delete_has_resources') }}
                        @endif
                        </p>
                        <p>{{ __('server.delete_type_name_hint') }}</p>
                        <x-slot:action>
                        <x-modal-confirmation :title="__('server.delete_confirm_title')" isErrorButton
                            :buttonTitle="__('server.delete_confirm_button')" submitAction="delete"
                            :actions="[__('server.delete_confirm_action')]"
                            :checkboxes="$checkboxes" confirmationText="{{ $server->name }}"
                            :confirmationLabel="__('server.delete_confirm_label')"
                            :shortConfirmationLabel="__('server.delete_confirm_short_label')" />
                        </x-slot:action>
                    </x-danger-zone>
                </x-application.settings-section>
            @endif
        </div>
    </div>
</div>
