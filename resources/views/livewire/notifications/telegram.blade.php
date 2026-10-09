<div>
    <x-slot:title>
        {{ __('not_telegram_title') }}
    </x-slot>

    <x-notification.settings-layout>
    <div class="application-settings-form flex flex-col gap-6">
        <form wire:submit="submit">
            <x-unsaved-bar action="submit" />
            <x-application.settings-section :title="__('not_telegram')"
                description="{{ __('not_telegram_desc') }}">
                <x-slot:actions>
                    <x-notification.channel-actions :enabled="$telegramEnabled" enabledProperty="telegramEnabled"
                        toggleMethod="instantSaveTelegramEnabled" :canUpdate="auth()->user()->can('update', $settings)"
                        :canResource="$settings" />
                </x-slot:actions>

                <div class="grid gap-4 lg:grid-cols-2">
                    @can('update', $settings)
                        <x-forms.input type="password" autocomplete="new-password" required id="telegramToken"
                            :label="__('not_bot_api_token')" helper="{{ __('not_bot_api_token_helper') }}" />
                        <x-forms.input type="password" autocomplete="new-password" required id="telegramChatId"
                            :label="__('not_chat_id')" helper="{{ __('not_chat_id_helper') }}" />
                    @else
                        <x-forms.input disabled :label="__('not_bot_api_token')" value="{{ __('not_hidden_admin') }}" />
                        <x-forms.input disabled :label="__('not_chat_id')" value="{{ __('not_hidden_admin') }}" />
                    @endcan
                </div>
            </x-application.settings-section>
        </form>

        <x-notification.event-grid :settings="$settings" channel="telegram" threaded />
    </div>
    </x-notification.settings-layout>
</div>
