<div class="w-full">
    <form class="application-settings-form flex w-full flex-col gap-4" wire:submit="save">
        <div class="grid gap-4 lg:grid-cols-2">
            <x-forms.input required id="name" :label="__('sec_token_name')" />
            <x-forms.input readonly :label="__('sec_provider')" value="{{ $integrationToken->providerName() }}" />
            <div class="lg:col-span-2">
                <x-forms.input type="password" id="newToken"
                    :label="$integrationToken->provider === 'infisical' ? __('sec_new_client_secret') : __('sec_new_api_token')"
                    placeholder="{{ __('sec_leave_blank') }}"
                    helper="{{ match ($integrationToken->provider) {
                        'infisical' => __('sec_rotate_helper_infisical'),
                        'vault' => __('sec_rotate_helper_vault'),
                        default => __('sec_rotate_helper_default'),
                    } }}" />
            </div>
        </div>

        @if ($integrationToken->provider === 'infisical')
            <div class="grid gap-4 lg:grid-cols-2">
                <x-forms.input required id="metadata.base_url" :label="__('sec_base_url')"
                    placeholder="https://app.infisical.com" />
                <x-forms.input required id="metadata.client_id" :label="__('sec_client_id')" />
            </div>
        @elseif ($integrationToken->provider === 'vault')
            <div class="grid gap-4 lg:grid-cols-2">
                <x-forms.input required id="metadata.base_url" :label="__('sec_base_url')"
                    placeholder="https://vault.example.com:8200" />
                <x-forms.input id="metadata.namespace" :label="__('sec_namespace_optional')" />
            </div>
        @endif

        @if ($integrationToken->provider === 'cloudflare')
            <fieldset>
                <legend class="text-sm font-medium text-black dark:text-fg">{{ __('sec_capabilities') }}</legend>
                <div class="mt-3 rounded-lg border border-neutral-200 p-1 dark:border-white/[0.08]">
                    <x-forms.checkbox id="edit-dns-capability" :label="__('sec_dns')" domValue="dns" fullWidth
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
                <x-forms.checkbox id="edit-automatic-dns" :label="__('sec_auto_dns_config')" fullWidth
                    wire:model.live="automaticDns" canGate="update" :canResource="$integrationToken" />
                <p class="px-2.5 pb-2 text-[11px] text-neutral-500 dark:text-fg-dim">
                    {{ __('sec_auto_dns_helper') }}
                </p>
            </div>
        @else
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-[11px] leading-5 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.025] dark:text-fg-dim">
                <div class="font-medium text-black dark:text-fg">{{ __('sec_secrets_readonly') }}</div>
                <p>{{ __('sec_secrets_readonly_desc2') }}</p>
            </div>
        @endif

        @if ($integrationToken->provider === 'cloudflare' && in_array('dns', $capabilities, true))
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-[11px] leading-5 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.025] dark:text-fg-dim">
                <div class="font-medium text-black dark:text-fg">{{ __('sec_cf_permissions') }}</div>
                <ul class="list-inside list-disc">
                    <li>Zone - DNS - Edit</li>
                    <li>Zone - Zone - Read</li>
                </ul>
                <a href="https://dash.cloudflare.com/profile/api-tokens?permissionGroupKeys=%5B%7B%22key%22%3A%22dns%22%2C%22type%22%3A%22edit%22%7D%5D&amp;accountId=%2A&amp;zoneId=all&amp;name=Coolify%20DNS%20Management"
                    target="_blank" rel="noopener noreferrer"
                    class="font-medium text-coollabs hover:underline dark:text-warning">
                    {{ __('sec_cf_replacement_token') }}
                </a>
            </div>
            <div class="rounded-lg border border-neutral-200 dark:border-white/[0.08]">
                <div class="flex items-center justify-between gap-3 p-3">
                    <div class="text-xs text-neutral-600 dark:text-fg-dim">
                        <div class="font-medium text-black dark:text-fg">{{ __('sec_token_domains') }}</div>
                        <div>{{ __('sec_accessible_zones', ['count' => $zoneCount, 'word' => __('sec_zone_word')]) }}</div>
                    @if (data_get($integrationToken->metadata, 'zones_synced_at'))
                        <div>{{ __('sec_last_refreshed', ['time' => \Carbon\Carbon::parse(data_get($integrationToken->metadata, 'zones_synced_at'))->diffForHumans()]) }}</div>
                    @endif
                    </div>
                    <x-forms.button type="button" wire:click="refreshZones" wire:target="refreshZones"
                        canGate="update" :canResource="$integrationToken">{{ __('sec_refresh_zones') }}</x-forms.button>
                </div>
                @if ($zones !== [])
                    <div class="max-h-48 overflow-y-auto border-t border-neutral-200 dark:border-white/[0.08]">
                        @foreach ($zones as $zone)
                            <div wire:key="integration-token-zone-{{ $zone['id'] }}"
                                class="flex items-center justify-between gap-3 border-b border-neutral-200 px-3 py-2.5 last:border-b-0 dark:border-white/[0.08]">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-black dark:text-fg">{{ $zone['name'] }}</div>
                                    @if ($zone['account_name'])
                                        <div class="truncate text-[11px] text-neutral-500 dark:text-fg-dim">{{ $zone['account_name'] }}</div>
                                    @endif
                                </div>
                                @if ($zone['managed_records_count'] > 0)
                                    <div class="shrink-0 text-[11px] text-neutral-500 dark:text-fg-dim">
                                        {{ __('sec_managed_records', ['count' => $zone['managed_records_count'], 'word' => __('sec_record_word')]) }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="flex items-center justify-between gap-2 border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
            <x-modal-confirmation title="{{ __('sec_confirm_delete_integration') }}" isErrorButton buttonTitle="{{ __('sec_delete') }}"
                submitAction="delete" :actions="[__('sec_integration_delete_desc')]"
                confirmationText="{{ $integrationToken->name }}" :confirmWithPassword="false"
                step2ButtonText="{{ __('sec_delete_token') }}" />
            <x-forms.button type="submit" wire:target="save" isHighlighted>
                {{ __('sec_validate_save') }}
            </x-forms.button>
        </div>
    </form>
</div>
