<div class="application-settings-form">
    <form class="flex flex-col gap-4" wire:submit="createPrivateKey">
        <div class="grid gap-4 lg:grid-cols-2">
            <x-forms.input id="name" :label="__('team_name')" required />
            <x-forms.input id="description" :label="__('team_description')" />
            <div class="lg:col-span-2">
                <x-forms.textarea realtimeValidation id="value" rows="10" monospace
                    placeholder="-----BEGIN OPENSSH PRIVATE KEY-----" :label="__('sec_private_key')" required />
            </div>
            <div class="lg:col-span-2">
                <x-forms.input id="publicKey" readonly :label="__('sec_public_key')"
                    helper="{{ __('sec_public_key_helper') }}" />
            </div>
        </div>
        <div class="flex justify-end border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
            <button type="submit"
                class="button button-highlighted">
                {{ __('sec_continue') }}
            </button>
        </div>
    </form>
</div>
