<div>
    <x-slot:title>
        {{ __('not_pushover_title') }}
    </x-slot>

    <x-notification.settings-layout>
    <div class="application-settings-form flex flex-col gap-6">
        <form wire:submit="submit">
            <x-unsaved-bar action="submit" />
            <x-application.settings-section :title="__('not_pushover')"
                description="{{ __('not_pushover_desc') }}">
                <x-slot:actions>
                    <x-notification.channel-actions :enabled="$pushoverEnabled" enabledProperty="pushoverEnabled"
                        toggleMethod="instantSavePushoverEnabled" :canUpdate="auth()->user()->can('update', $settings)"
                        :canResource="$settings" />
                </x-slot:actions>

                <div class="grid gap-4 lg:grid-cols-2">
                    @can('update', $settings)
                        <x-forms.input type="password" required id="pushoverUserKey" :label="__('not_user_key')"
                            helper="{{ __('not_user_key_helper') }}" />
                        <x-forms.input type="password" required id="pushoverApiToken" :label="__('not_api_token')"
                            helper="{{ __('not_api_token_helper') }}" />
                    @else
                        <x-forms.input disabled :label="__('not_user_key')" value="{{ __('not_hidden_admin') }}" />
                        <x-forms.input disabled :label="__('not_api_token')" value="{{ __('not_hidden_admin') }}" />
                    @endcan
                </div>
            </x-application.settings-section>
        </form>

        <x-notification.event-grid :settings="$settings" channel="pushover" />
    </div>
    </x-notification.settings-layout>
</div>
