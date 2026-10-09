<div class="w-full">
    <form class="application-settings-form flex w-full flex-col gap-4" wire:submit="addToken">
        <x-forms.listbox required id="provider" :label="__('sec_provider')" :live="true" :options="[
            ['value' => 'cloudflare', 'label' => 'Cloudflare'],
            ['value' => 'doppler', 'label' => 'Doppler'],
            ['value' => 'infisical', 'label' => 'Infisical'],
            ['value' => 'vault', 'label' => 'HashiCorp Vault'],
        ]" />

        @if ($provider === 'infisical')
            <div class="grid gap-4 lg:grid-cols-2">
                <x-forms.input required id="name" :label="__('sec_token_name')" placeholder="{{ __('sec_production_secrets') }}" />
                <x-forms.input required id="metadata.client_id" :label="__('sec_client_id')"
                    placeholder="{{ __('sec_client_id_placeholder') }}" />
                <x-forms.input required type="password" id="token" :label="__('sec_client_secret')"
                    placeholder="{{ __('sec_client_secret_placeholder') }}" />
                <x-forms.input required id="metadata.base_url" :label="__('sec_base_url')"
                    placeholder="https://app.infisical.com"
                    helper="{{ __('sec_infisical_helper') }}" />
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                <x-forms.input required id="name" :label="__('sec_token_name')"
                    placeholder="{{ $provider === 'cloudflare' ? __('sec_production_dns') : __('sec_production_secrets') }}" />
                <x-forms.input required type="password" id="token"
                    :label="$provider === 'vault' ? __('sec_vault_token') : __('sec_api_token')"
                    placeholder="{{ __('sec_paste_provider_token') }}" />
            </div>
        @endif

        @if ($provider === 'vault')
            <div class="grid gap-4 lg:grid-cols-2">
                <x-forms.input required id="metadata.base_url" :label="__('sec_base_url')"
                    placeholder="https://vault.example.com:8200" />
                <x-forms.input id="metadata.namespace" :label="__('sec_namespace_optional')"
                    placeholder="admin/team-a" helper="{{ __('sec_vault_namespace_helper') }}" />
            </div>
        @endif

        @if ($provider === 'cloudflare')
            <fieldset>
                <legend class="text-sm font-medium text-black dark:text-fg">{{ __('sec_capabilities') }}</legend>
                <div class="mt-3 rounded-lg border border-neutral-200 p-1 dark:border-white/[0.08]">
                    <x-forms.checkbox id="dns-capability" :label="__('sec_dns')" domValue="dns" fullWidth
                        wire:model.live="capabilities" />
                    <p class="px-2.5 pb-2 text-[11px] text-neutral-500 dark:text-fg-dim">
                        {{ __('sec_cloudflare_dns_helper') }}
                    </p>
                </div>
                @error('capabilities')
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </fieldset>
            <div class="rounded-lg border border-neutral-200 p-1 dark:border-white/[0.08]">
                <x-forms.checkbox id="automatic-dns" :label="__('sec_auto_dns_config')" fullWidth
                    wire:model.live="automaticDns" canGate="create" :canResource="\App\Models\IntegrationToken::class" />
                <p class="px-2.5 pb-2 text-[11px] text-neutral-500 dark:text-fg-dim">
                    {{ __('sec_auto_dns_helper') }}
                </p>
            </div>
        @else
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-[11px] leading-5 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.025] dark:text-fg-dim">
                <div class="font-medium text-black dark:text-fg">{{ __('sec_secrets_readonly') }}</div>
                <p>{{ __('sec_secrets_readonly_desc') }}</p>
            </div>
        @endif

        @if ($provider === 'cloudflare' && in_array('dns', $capabilities, true))
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-[11px] leading-5 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.025] dark:text-fg-dim">
                <div class="font-medium text-black dark:text-fg">{{ __('sec_cf_permissions') }}</div>
                <ul class="list-inside list-disc">
                    <li>Zone - DNS - Edit</li>
                    <li>Zone - Zone - Read</li>
                </ul>
                <p>{{ __('sec_cf_limit_zones') }}</p>
                <a href="https://dash.cloudflare.com/profile/api-tokens?permissionGroupKeys=%5B%7B%22key%22%3A%22dns%22%2C%22type%22%3A%22edit%22%7D%5D&amp;accountId=%2A&amp;zoneId=all&amp;name=Coolify%20DNS%20Management"
                    target="_blank" rel="noopener noreferrer"
                    class="button mt-2">
                    {{ __('sec_cf_create_token') }}
                    <x-external-link />
                </a>
            </div>
        @elseif ($provider === 'doppler')
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-[11px] leading-5 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.025] dark:text-fg-dim">
                <div class="font-medium text-black dark:text-fg">{{ __('sec_doppler_recommended') }}</div>
                <p>{!! __('sec_doppler_desc', ['strong' => '<span class="font-medium">'.__('sec_service_token').'</span>']) !!}</p>
            </div>
        @elseif ($provider === 'infisical')
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-[11px] leading-5 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.025] dark:text-fg-dim">
                <div class="font-medium text-black dark:text-fg">{{ __('sec_infisical_identity') }}</div>
                <p>{{ __('sec_infisical_identity_desc') }}</p>
            </div>
        @elseif ($provider === 'vault')
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-[11px] leading-5 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.025] dark:text-fg-dim">
                <div class="font-medium text-black dark:text-fg">{{ __('sec_vault_token') }}</div>
                <p>{{ __('sec_vault_token_desc') }}</p>
            </div>
        @endif

        <div class="flex justify-end border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
            <x-forms.button type="submit" wire:target="addToken" isHighlighted>
                {{ __('sec_validate_add') }}
            </x-forms.button>
        </div>
    </form>
</div>
