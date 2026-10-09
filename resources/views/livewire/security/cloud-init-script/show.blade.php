<div>
    @if ($modalMode)
        <form wire:submit="save" class="flex flex-col gap-4">
            <x-forms.input canGate="update" :canResource="$cloudInitScript" id="name" :label="__('sec_script_name')" required />
            <x-forms.textarea canGate="update" :canResource="$cloudInitScript" id="script"
                :label="__('sec_script_content')" rows="16" monospace
                helper="{{ __('sec_script_content_helper') }}" required />
            <div class="flex items-center justify-between gap-2 border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                @can('delete', $cloudInitScript)
                    <x-modal-confirmation title="{{ __('sec_confirm_script_delete') }}" isErrorButton buttonTitle="{{ __('sec_delete') }}"
                        submitAction="delete" :actions="[__('sec_script_delete_desc')]"
                        confirmationText="{{ $cloudInitScript->name }}" :confirmWithPassword="false"
                        step2ButtonText="{{ __('sec_delete_script') }}" />
                @endcan
                <x-forms.button type="submit" isHighlighted>{{ __('sec_save_changes') }}</x-forms.button>
            </div>
        </form>
    @else
    <x-slot:title>
        {{ $cloudInitScript->name }} | {{ __('sec_cloud_init_title') }}
    </x-slot>

    <x-security.settings-layout>
        <x-slot:actions>
            @can('delete', $cloudInitScript)
                <x-modal-confirmation title="{{ __('sec_confirm_script_delete') }}" isErrorButton buttonTitle="{{ __('sec_delete') }}"
                    submitAction="delete" :actions="[
                        __('sec_script_delete_desc'),
                        __('sec_action_cannot_undo'),
                    ]" confirmationText="{{ $cloudInitScript->name }}"
                    confirmationLabel="{{ __('sec_confirm_script_label') }}"
                    shortConfirmationLabel="{{ __('sec_script_name_short') }}" :confirmWithPassword="false"
                    step2ButtonText="{{ __('sec_delete_script') }}" />
            @endcan
        </x-slot:actions>


    <form wire:submit="save" class="application-settings-form">
        <x-unsaved-bar action="save" />
        <x-application.settings-section title="{{ __('sec_general') }}"
            description="{{ __('sec_script_general_desc') }}">
            <div class="flex flex-col gap-4">
                <x-forms.input canGate="update" :canResource="$cloudInitScript" id="name"
                    :label="__('sec_script_name')" required />
                <x-forms.textarea canGate="update" :canResource="$cloudInitScript" id="script"
                    :label="__('sec_script_content')" rows="18" monospace
                    helper="{{ __('sec_script_content_helper') }}" required />
            </div>
        </x-application.settings-section>
    </form>
    </x-security.settings-layout>
    @endif
</div>
