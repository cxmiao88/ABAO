<div>
    <x-slot:title>
        {{ __('sv.team_variables_title') }} | ABao
    </x-slot>

    <x-shared-variables.editor :resource="$team" :variables="$team->environment_variables"
        type="team" title="{{ __('sv.team_variables') }}" :view="$view" variablesLabel="{{ __('sv.team_shared_variables') }}" />
</div>
