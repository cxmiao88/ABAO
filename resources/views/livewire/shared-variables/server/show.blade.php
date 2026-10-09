<div>
    <x-slot:title>
        {{ __('sv.server_variables_title') }} | ABao
    </x-slot>

    <x-shared-variables.editor :resource="$server"
        :variables="$server->environment_variables"
        :readOnlyKeys="['COOLIFY_SERVER_UUID', 'COOLIFY_SERVER_NAME']"
        type="server" title="{{ $server->name }}"
        :view="$view" variablesLabel="{{ __('sv.server_shared_variables') }}" />
</div>
