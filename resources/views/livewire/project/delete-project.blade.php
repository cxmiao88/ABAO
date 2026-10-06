<x-modal-confirmation title="{{ __('project.delete_confirm_title') }}" buttonTitle="{{ __('project.delete_button') }}" isErrorButton submitAction="delete"
    :actions="[
        __('project.delete_action_1'),
        __('project.delete_action_2'),
    ]" confirmationLabel="{{ __('project.delete_confirm_label') }}"
    shortConfirmationLabel="{{ __('project.name_label') }}" confirmationText="{{ $projectName }}" :confirmWithPassword="false"
    step2ButtonText="{{ __('project.permanently_delete') }}" />
