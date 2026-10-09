<div class="w-full">
    <form class="application-settings-form flex w-full flex-col gap-4" wire:submit="addToken"
        x-data="{
            selectedProvider: $wire.entangle('provider'),
            get providerName() {
                return this.selectedProvider === 'digitalocean' ? 'DigitalOcean' :
                    this.selectedProvider.charAt(0).toUpperCase() + this.selectedProvider.slice(1);
            },
            get providerConsoleUrl() {
                if (this.selectedProvider === 'hetzner') return 'https://console.hetzner.com/projects';
                if (this.selectedProvider === 'vultr') return 'https://console.vultr.com/user/apiaccess/';
                return 'https://cloud.digitalocean.com/account/api/tokens';
            }
        }">
        @if (!$provider_locked)
            <x-forms.listbox required id="provider" :label="__('sec_provider')" :wire="false" :value="$provider"
                x-model="selectedProvider" :options="[
                ['value' => 'hetzner', 'label' => 'Hetzner'],
                ['value' => 'digitalocean', 'label' => 'DigitalOcean'],
                ['value' => 'vultr', 'label' => 'Vultr'],
            ]" />
        @else
            <input type="hidden" wire:model="provider" />
        @endif

        <div
            class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-[11px] leading-5 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-fg-dim">
            {!! __('sec_create_in_console', ['link' => '<a :href="providerConsoleUrl" target="_blank" class="font-medium text-coollabs hover:underline dark:text-warning"><span x-text="providerName + \' \' + \''.__('sec_console').'\'"></span></a>']) !!}
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-forms.input required id="name" :label="__('sec_token_name')"
                x-bind:placeholder="`{{ __('sec_production_token_placeholder') }} ${providerName} {{ __('sec_token') }}`" />
            <x-forms.input required type="password" id="token" :label="__('sec_api_token')"
                placeholder="{{ __('sec_paste_provider_token') }}" />
            <div class="lg:col-span-2">
                <x-forms.textarea id="description" :label="__('sec_description')" rows="3"
                    placeholder="{{ __('sec_optional_notes') }}" />
            </div>
        </div>

        <div class="flex justify-end border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
            <x-forms.button type="submit"
                class="button-highlighted"
                wire:target="addToken">
                {{ __('sec_validate_add') }}
            </x-forms.button>
        </div>
    </form>
</div>
