<div>
    <x-slot:title>
        {{ __('sv.project_variables_title') }} | ABao
    </x-slot>

    <x-shared-variables.editor :resource="$project" :variables="$project->environment_variables"
        type="project" title="{{ $project->name }}" :view="$view" variablesLabel="{{ __('sv.project_shared_variables') }}" />
</div>
