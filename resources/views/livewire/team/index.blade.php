<div>
    <x-slot:title>
        {{ __('team_index_title') }}
    </x-slot>

    <x-team.settings-layout>
    <div class="flex flex-col gap-6">
        <form wire:submit="submit" class="application-settings-form">
            <x-unsaved-bar action="submit" />
            <x-application.settings-section title="{{ __('team_general') }}"
                description="{{ __('team_general_desc') }}">
                <x-slot:actions>
                    <x-modal-input buttonTitle="{{ __('team_new_team') }}" title="{{ __('team_new_team') }}">
                        <livewire:team.create />
                    </x-modal-input>
                </x-slot:actions>
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.input id="name" label="{{ __('team_name') }}" required canGate="update" :canResource="$team" />
                    <x-forms.input id="description" label="{{ __('team_description') }}" canGate="update" :canResource="$team" />
                    <div class="lg:col-span-2">
                        <x-forms.listbox canGate="update" :canResource="$team" id="is_mcp_server_enabled" label="{{ __('team_mcp_server') }}"
                            helper="{{ __('team_mcp_helper') }}"
                            :disabled="! auth()->user()->can('update', $team)" :options="[
                                ['value' => false, 'label' => __('team_disabled_for_team')],
                                ['value' => true, 'label' => __('team_enabled_for_team')],
                            ]" />
                    </div>
                    <div class="lg:col-span-2">
                        <x-forms.listbox canGate="update" :canResource="$team"
                            id="is_build_server_fallback_enabled" label="{{ __('team_build_fallback') }}"
                            helper="{{ __('team_build_fallback_helper') }}"
                            :disabled="! auth()->user()->can('update', $team)" :options="[
                                ['value' => true, 'label' => __('team_build_on_deploy')],
                                ['value' => false, 'label' => __('team_fail_deployment')],
                            ]" />
                    </div>
                </div>
            </x-application.settings-section>
        </form>

    </div>
    </x-team.settings-layout>
</div>
