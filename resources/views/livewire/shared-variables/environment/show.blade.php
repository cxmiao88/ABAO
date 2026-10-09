<div>
    <x-slot:title>
        {{ __('sv.environment_variables_title') }} | ABao
    </x-slot>

    <x-shared-variables.editor :resource="$environment"
        :variables="$environment->environment_variables" type="environment"
        title="{{ $project->name }} / {{ $environment->name }}"
        :view="$view" variablesLabel="{{ __('sv.environment_shared_variables') }}" />
</div>
