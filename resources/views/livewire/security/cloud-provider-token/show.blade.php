<div>
    @if ($modalMode)
        <form wire:submit="save" class="flex flex-col gap-4">
            <div class="grid gap-4 lg:grid-cols-2">
                <x-forms.input canGate="update" :canResource="$cloudProviderToken" id="name" :label="__('sec_name')" required />
                <x-forms.input canGate="update" :canResource="$cloudProviderToken" id="description" :label="__('sec_description')" />
                <x-forms.input readonly :label="__('sec_provider')" :value="$this->providerName()" />
                <x-forms.input readonly :label="__('sec_created')" :value="$cloudProviderToken->created_at->format('Y-m-d H:i')" />
            </div>
            <div class="flex items-center justify-between gap-2 border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                <div class="flex items-center gap-2">
                    @can('delete', $cloudProviderToken)
                        <x-modal-confirmation title="{{ __('sec_confirm_token_delete') }}" isErrorButton buttonTitle="{{ __('sec_delete') }}"
                            submitAction="delete" :actions="[__('sec_token_delete_desc')]"
                            confirmationText="{{ $cloudProviderToken->name }}" :confirmWithPassword="false"
                            step2ButtonText="{{ __('sec_delete_token') }}" />
                    @endcan
                    <x-forms.button type="button" wire:click="validateToken">{{ __('sec_validate') }}</x-forms.button>
                </div>
                <x-forms.button type="submit" isHighlighted>{{ __('sec_save_changes') }}</x-forms.button>
            </div>
        </form>
    @else
    <x-slot:title>
        {{ $cloudProviderToken->name }} | {{ __('sec_cloud_title') }}
    </x-slot>

    <x-security.settings-layout>
        <x-slot:actions>
            <x-forms.button type="button" wire:click="validateToken">
                <x-reicon name="check-circle" class="size-3.5" />
                {{ __('sec_validate') }}
            </x-forms.button>
            @can('delete', $cloudProviderToken)
                <x-modal-confirmation title="{{ __('sec_confirm_token_delete') }}" isErrorButton buttonTitle="{{ __('sec_delete') }}"
                    submitAction="delete" :actions="[
                        __('sec_token_delete_desc'),
                        __('sec_token_delete_desc2'),
                    ]" confirmationText="{{ $cloudProviderToken->name }}"
                    confirmationLabel="{{ __('sec_confirm_token_label') }}"
                    shortConfirmationLabel="{{ __('sec_token_name_short') }}" :confirmWithPassword="false"
                    step2ButtonText="{{ __('sec_delete_token') }}" />
            @endcan
        </x-slot:actions>


    <form wire:submit="save" class="application-settings-form">
        <x-unsaved-bar action="save" />
        <x-application.settings-section title="{{ __('sec_general') }}"
            description="{{ __('sec_token_general_desc') }}">
            <div class="grid gap-4 lg:grid-cols-2">
                <x-forms.input canGate="update" :canResource="$cloudProviderToken" id="name"
                    :label="__('sec_name')" required />
                <x-forms.input canGate="update" :canResource="$cloudProviderToken" id="description"
                    :label="__('sec_description')" />
                <x-forms.input readonly :label="__('sec_provider')" :value="$this->providerName()" />
                <x-forms.input readonly :label="__('sec_created')" :value="$cloudProviderToken->created_at->format('Y-m-d H:i')" />
            </div>
        </x-application.settings-section>
    </form>
    </x-security.settings-layout>
    @endif
</div>
