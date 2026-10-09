<form wire:submit="submit" class="application-settings-form flex flex-col gap-6">
    <x-unsaved-bar action="submit" />

    <x-application.settings-section title="{{ __('application.swarm_configuration') }}"
        description="{{ __('application.swarm_configuration_desc') }}">
        <x-slot:actions>
            <x-deprecated-badge />
        </x-slot:actions>

        <x-callout type="warning" title="{{ __('application.swarm_deprecated') }}">
            {{ config('deprecations.swarm') }}
        </x-callout>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <x-forms.input id="swarmReplicas" label="{{ __('application.swarm_replicas') }}" required canGate="update"
                :canResource="$application" />
            <x-forms.listbox canGate="update" :canResource="$application" id="isSwarmOnlyWorkerNodes" label="{{ __('application.swarm_node_placement') }}" live onChange="instantSave"
                :disabled="! auth()->user()->can('update', $application)" :options="[
                    ['value' => true, 'label' => __('application.swarm_opt_worker_only')],
                    ['value' => false, 'label' => __('application.swarm_opt_manager_worker')],
                ]" />
            <div class="lg:col-span-2">
                <x-forms.textarea id="swarmPlacementConstraints" rows="8" label="{{ __('application.swarm_placement_constraints') }}"
                    placeholder="placement:
    constraints:
        - 'node.role == worker'" canGate="update" :canResource="$application" />
            </div>
        </div>
    </x-application.settings-section>
</form>
