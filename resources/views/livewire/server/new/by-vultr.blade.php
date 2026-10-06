<div class="w-full">
    @if ($limit_reached)
        <x-limit-reached name="servers" />
    @elseif ($current_step === 1)
        <div class="flex flex-col gap-6">
            <x-server.provider-token-picker provider="vultr" providerLabel="Vultr"
                :tokens="$available_tokens" />
            <p class="text-[11px] text-neutral-500 dark:text-fg-faint">
                {{ __('server.vultr_new_to') }}
                <a href="https://coolify.io/vultr" target="_blank"
                    class="font-medium text-coollabs hover:underline dark:text-warning">{{ __('server.provider_create_account') }}</a>
                {{ __('server.provider_affiliate_suffix') }}
            </p>
        </div>
    @elseif ($current_step === 2)
        <div wire:init="loadVultrData">
            @if ($loading_data)
                <x-application.settings-section :title="__('server.vt_loading_title')"
                    :description="__('server.vt_loading_description')">
                    <div class="flex min-h-40 items-center justify-center">
                        <x-loading :text="__('server.vt_loading_text')" />
                    </div>
                </x-application.settings-section>
            @elseif ($provider_data_error)
                <x-application.settings-section :title="__('server.vt_unable_title')"
                    :description="__('server.hz_unable_description')">
                    <x-callout type="error" :title="__('server.hz_request_failed')">
                        <pre class="mt-2 whitespace-pre-wrap break-words text-[11px]">{{ $provider_data_error }}</pre>
                    </x-callout>
                    <div class="mt-4">
                        <a class="button" href="{{ route('server.create.type', ['type' => 'vultr']) }}"
                            {{ wireNavigate() }}>{{ __('server.hz_select_another_token') }}</a>
                    </div>
                </x-application.settings-section>
            @else
                @php
                    $regionOptions = collect($regions)->map(fn ($region) => [
                        'value' => $region['id'],
                        'label' => ($region['city'] ?? $region['id']) . ' · ' . ($region['country'] ?? $region['id']),
                    ])->values()->all();
                    $planOptions = collect($this->availablePlans)->map(function ($plan) {
                        $label = $plan['id']
                            . ' · ' . ($plan['vcpu_count'] ?? '?') . ' vCPU'
                            . ' · ' . (isset($plan['ram']) ? number_format($plan['ram'] / 1024, 1) : '?') . ' GB RAM'
                            . ' · ' . ($plan['disk'] ?? '?') . ' GB';
                        return [
                            'value' => $plan['id'],
                            'label' => isset($plan['monthly_cost'])
                                ? $label . ' · $' . number_format((float) $plan['monthly_cost'], 2) . '/mo'
                                : $label,
                        ];
                    })->values()->all();
                    $osOptions = collect($operatingSystems)->map(fn ($os) => [
                        'value' => $os['id'],
                        'label' => $os['name'],
                    ])->values()->all();
                    $privateKeyOptions = $private_keys->map(fn ($key) => [
                        'value' => $key->id,
                        'label' => $key->name,
                    ])->values()->all();
                    $scriptOptions = collect([
                        ['value' => '', 'label' => __('server.cloudinit_empty_script')],
                        ...$saved_cloud_init_scripts->map(fn ($script) => [
                            'value' => $script->id,
                            'label' => $script->name,
                        ])->all(),
                    ])->all();
                @endphp

                <form wire:submit="submit" class="flex flex-col gap-6">
                    <x-application.settings-section :title="__('server.vt_server_title')"
                        :description="__('server.vt_server_description')">
                        <x-slot:actions>
                            <button type="submit"
                                class="button button-highlighted"
                                @disabled(!$private_key_id)>
                                {{ __('server.hz_buy_and_create') }}
                                @if ($this->selectedServerPrice)
                                    <span class="opacity-70">· {{ $this->selectedServerPrice }}/mo</span>
                                @endif
                            </button>
                        </x-slot:actions>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="lg:col-span-2">
                                <x-forms.input id="server_name" :label="__('server.hz_server_name_label')"
                                    :helper="__('server.hz_server_name_helper')" />
                            </div>
                            <x-forms.listbox id="selected_region" :label="__('server.vt_region_label')" required live
                                :placeholder="__('server.vt_select_region')" :options="$regionOptions" />
                            <x-forms.listbox id="selected_plan" :label="__('server.vt_plan_label')" required live
                                :disabled="!$selected_region" :placeholder="__('server.vt_select_plan')"
                                :options="$planOptions" />
                            <x-forms.listbox id="selected_os_id" :label="__('server.vt_os_label')" required
                                :placeholder="__('server.vt_select_os')" :options="$osOptions" />
                            @if ($private_keys->isEmpty())
                                <div>
                                    <label class="mb-1.5 flex w-fit items-center gap-1.5">{{ __('server.byip.private_key_label') }}
                                        <x-highlighted text="*" />
                                    </label>
                                    <div
                                        class="flex min-h-8 items-center justify-between gap-3 rounded-lg border border-warning/30 bg-warning/5 px-3 py-2">
                                        <span class="text-[11px] text-neutral-600 dark:text-fg-dim">{{ __('server.pk_required') }}</span>
                                        <x-modal-input :title="__('server.hz_new_private_key')">
                                            <x-slot:content>
                                                <button type="button" class="button">{{ __('server.hz_create_key') }}</button>
                                            </x-slot:content>
                                            <livewire:security.private-key.create :modal_mode="true" from="server" />
                                        </x-modal-input>
                                    </div>
                                </div>
                            @else
                                <x-forms.listbox id="private_key_id" :label="__('server.byip.private_key_label')" required
                                    :placeholder="__('server.byip.select_private_key')" :options="$privateKeyOptions"
                                    :helper="__('server.vt_key_auto_added')" />
                            @endif
                        </div>
                    </x-application.settings-section>

                    <x-application.settings-section :title="__('server.hz_advanced_title')"
                        :description="__('server.vt_advanced_description')">
                        @if (count($this->advancedVultrOptionsSummary) > 0)
                            <div class="mb-4 flex flex-wrap gap-1.5">
                                @foreach ($this->advancedVultrOptionsSummary as $summaryItem)
                                    <span
                                        class="rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-medium text-neutral-600 dark:bg-white/[0.06] dark:text-fg-dim">
                                        {{ $summaryItem }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex flex-col gap-4">
                            <x-forms.datalist :label="__('server.hz_extra_ssh_keys')" id="selectedVultrSshKeyIds"
                                :helper="__('server.vt_extra_ssh_keys_helper')" :multiple="true"
                                :disabled="count($vultrSshKeys) === 0"
                                :placeholder="count($vultrSshKeys) ? __('server.hz_search_ssh_keys') : __('server.hz_no_account_keys')">
                                @foreach ($vultrSshKeys as $sshKey)
                                    <option value="{{ $sshKey['id'] }}">{{ $sshKey['name'] }}</option>
                                @endforeach
                            </x-forms.datalist>

                            <div class="grid gap-3 lg:grid-cols-2">
                                <x-forms.checkbox id="enable_ipv6" :label="__('server.hz_enable_ipv6')" fullWidth />
                                <x-forms.checkbox id="disable_public_ipv4" :label="__('server.vt_disable_public_ipv4')" fullWidth />
                            </div>

                            <div class="border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                                <div class="flex flex-col gap-4">
                                    <div class="grid items-end gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                                        <x-forms.listbox id="selected_cloud_init_script_id"
                                            :label="__('server.cloudinit_saved_label')" live :options="$scriptOptions" />
                                        <button type="button" class="button"
                                            wire:click="clearCloudInitScript">{{ __('server.cloudinit_clear') }}</button>
                                    </div>
                                    <x-forms.textarea id="cloud_init_script" :label="__('server.cloudinit_script_label')"
                                        rows="8" monospace />
                                    <div class="grid items-end gap-4 lg:grid-cols-2">
                                        <x-forms.checkbox id="save_cloud_init_script"
                                            :label="__('server.cloudinit_save_for_later')" />
                                        @if ($save_cloud_init_script)
                                            <x-forms.input id="cloud_init_script_name" :label="__('server.cloudinit_saved_name')" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-application.settings-section>
                </form>
            @endif
        </div>
    @endif
</div>
