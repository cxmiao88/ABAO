<div>
    <x-slot:title>
        {{ __('not_slack_title') }}
    </x-slot>

    <x-notification.settings-layout>
    <div class="application-settings-form flex flex-col gap-6">
        <form wire:submit="submit">
            <x-unsaved-bar action="submit" />
            <x-application.settings-section :title="__('not_slack')"
                description="{{ __('not_slack_desc') }}">
                <x-slot:actions>
                    <x-notification.channel-actions :enabled="$slackEnabled" enabledProperty="slackEnabled"
                        toggleMethod="instantSaveSlackEnabled" :canUpdate="auth()->user()->can('update', $settings)"
                        :canResource="$settings" />
                </x-slot:actions>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        @can('update', $settings)
                            <x-forms.input type="password" required id="slackWebhookUrl" :label="__('not_webhook_url')"
                                helper="{{ __('not_webhook_url_helper_slack') }}" />
                        @else
                            <x-forms.input disabled :label="__('not_webhook_url')" value="{{ __('not_hidden_admin') }}" />
                        @endcan
                    </div>
                </div>
            </x-application.settings-section>
        </form>

        <x-notification.event-grid :settings="$settings" channel="slack" />
    </div>
    </x-notification.settings-layout>
</div>
