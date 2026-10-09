<div>
    <x-slot:title>
        {{ __('not_discord_title') }}
    </x-slot>

    <x-notification.settings-layout>
    <div class="application-settings-form flex flex-col gap-6">
        <form wire:submit="submit">
            <x-unsaved-bar action="submit" />
            <x-application.settings-section :title="__('not_discord')"
                description="{{ __('not_discord_desc') }}">
                <x-slot:actions>
                    <x-notification.channel-actions :enabled="$discordEnabled" enabledProperty="discordEnabled"
                        toggleMethod="instantSaveDiscordEnabled" :canUpdate="auth()->user()->can('update', $settings)"
                        :canResource="$settings" />
                </x-slot:actions>

                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.listbox canGate="update" :canResource="$settings" id="discordPingEnabled" :label="__('not_critical_mention')"
                        helper="{{ __('not_critical_mention_helper') }}"
                        onChange="instantSaveDiscordPingEnabled"
                        :disabled="!auth()->user()->can('update', $settings)" :options="[
                            ['value' => true, 'label' => __('not_mention_here')],
                            ['value' => false, 'label' => __('not_do_not_mention')],
                        ]" />
                    <div class="lg:col-span-2">
                        @can('update', $settings)
                            <x-forms.input type="password" required id="discordWebhookUrl" :label="__('not_webhook_url')"
                                helper="{{ __('not_webhook_url_helper_discord') }}" />
                        @else
                            <x-forms.input disabled :label="__('not_webhook_url')" value="{{ __('not_hidden_admin') }}" />
                        @endcan
                    </div>
                </div>
            </x-application.settings-section>
        </form>

        <x-notification.event-grid :settings="$settings" channel="discord" />
    </div>
    </x-notification.settings-layout>
</div>
