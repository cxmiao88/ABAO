<x-modal-confirmation title="{{ __('project.environment_delete_confirm_title') }}" buttonTitle="{{ __('project.delete_button') }}" isErrorButton
    submitAction="delete" :actions="[__('project.environment_delete_action')]"
    confirmationLabel="{{ __('project.environment_delete_confirm_label') }}"
    shortConfirmationLabel="{{ __('project.environment_name_label') }}" confirmationText="{{ $environmentName }}" :confirmWithPassword="false"
    step2ButtonText="{{ __('project.permanently_delete') }}" />
