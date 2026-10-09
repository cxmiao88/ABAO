<div>
    @if ($modalMode)
        <div class="relative">
        <form wire:submit="changePrivateKey" class="flex flex-col gap-4" x-data="{ showPrivateKey: false }"
            wire:loading.class="pointer-events-none opacity-50" wire:target="delete">
            <div class="grid gap-4 lg:grid-cols-2">
                <x-forms.input canGate="update" :canResource="$private_key" id="name" :label="__('team_name')" required />
                <x-forms.input canGate="update" :canResource="$private_key" id="description" :label="__('team_description')" />
                <div class="lg:col-span-2">
                    <x-forms.input canGate="update" :canResource="$private_key" readonly id="public_key"
                        :label="__('sec_public_key')" helper="{{ __('sec_public_key_helper') }}" />
                </div>
                <div class="lg:col-span-2">
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <label class="text-[13px] font-medium">{{ __('sec_private_key') }} <span class="text-helper">*</span></label>
                        <button type="button" class="text-[11px] font-medium text-coollabs hover:underline dark:text-warning"
                            x-on:click="showPrivateKey = !showPrivateKey" x-text="showPrivateKey ? '{{ __('sec_hide_editor') }}' : '{{ __('sec_edit_key_btn') }}'"></button>
                    </div>
                    <div x-show="!showPrivateKey">
                        <x-forms.input canGate="update" :canResource="$private_key" allowToPeak="false"
                            type="password" id="privateKeyValue" required disabled />
                    </div>
                    <div x-cloak x-show="showPrivateKey">
                        <x-forms.textarea canGate="update" :canResource="$private_key" rows="10"
                            id="privateKeyValue" required monospace />
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                @can('delete', $private_key)
                    <x-modal-confirmation title="{{ __('sec_confirm_delete_key') }}" isErrorButton buttonTitle="{{ __('sec_delete') }}"
                        submitAction="delete" :disabled="$isInUse" :disabledTooltip="$deleteDisabledReason"
                        :actions="[__('sec_delete_desc')]" confirmationText="{{ $private_key->name }}"
                        :confirmWithPassword="false" step2ButtonText="{{ __('sec_delete_key_btn') }}" />
                @endcan
                <x-forms.button type="submit" isHighlighted>{{ __('sec_save_changes') }}</x-forms.button>
            </div>
        </form>
        <div wire:loading.flex wire:target="delete"
            class="absolute inset-0 z-10 items-center justify-center rounded-lg bg-white/50 dark:bg-black/40">
            <x-loading :text="__('sec_deleting_key')" />
        </div>
        </div>
    @else
    <x-slot:title>
        {{ $private_key->name }} | Private Keys | ABao
    </x-slot>

    <x-security.settings-layout>
        <x-slot:actions>
            @if ($isGitRelated)
                <x-status-badge :label="__('sec_used_by_github')" type="neutral" />
            @endif
            @if (data_get($private_key, 'id') > 0)
                @can('delete', $private_key)
                    <x-modal-confirmation title="{{ __('sec_confirm_delete_key') }}" isErrorButton buttonTitle="{{ __('sec_delete') }}"
                        submitAction="delete({{ $private_key->id }})" :disabled="$isInUse"
                        :disabledTooltip="$deleteDisabledReason" :actions="[
                            __('sec_delete_desc'),
                            __('sec_delete_desc2'),
                        ]"
                        confirmationText="{{ $private_key->name }}"
                        confirmationLabel="{{ __('sec_confirm_key_label') }}"
                        shortConfirmationLabel="{{ __('sec_key_name_short') }}" :confirmWithPassword="false"
                        step2ButtonText="{{ __('sec_delete_key_btn') }}" />
                @endcan
            @endif
        </x-slot:actions>


    <form wire:submit="changePrivateKey" class="application-settings-form" x-data="{ showPrivateKey: false }">
        <x-unsaved-bar action="changePrivateKey" />
        <x-application.settings-section title="{{ __('sec_general') }}">
            <div class="grid gap-4 lg:grid-cols-2">
                <x-forms.input canGate="update" :canResource="$private_key" id="name" :label="__('team_name')" required />
                <x-forms.input canGate="update" :canResource="$private_key" id="description"
                    :label="__('team_description')" />
                <div class="lg:col-span-2">
                    <x-forms.input canGate="update" :canResource="$private_key" readonly id="public_key"
                        :label="__('sec_public_key')"
                        helper="{{ __('sec_public_key_helper') }}" />
                </div>
                <div class="lg:col-span-2">
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <label class="text-[13px] font-medium">{{ __('sec_private_key') }} <span class="text-helper">*</span></label>
                        <button type="button"
                            class="text-[11px] font-medium text-coollabs hover:underline dark:text-warning"
                            x-on:click="showPrivateKey = !showPrivateKey"
                            x-text="showPrivateKey ? '{{ __('sec_hide_editor') }}' : '{{ __('sec_edit_key_btn') }}'"></button>
                    </div>
                    <div x-show="!showPrivateKey">
                        <x-forms.input canGate="update" :canResource="$private_key" allowToPeak="false"
                            type="password" id="privateKeyValue" required disabled />
                    </div>
                    <div x-cloak x-show="showPrivateKey">
                        <x-forms.textarea canGate="update" :canResource="$private_key" rows="12"
                            id="privateKeyValue" required monospace />
                    </div>
                </div>
            </div>
        </x-application.settings-section>
    </form>
    </x-security.settings-layout>
    @endif
</div>
