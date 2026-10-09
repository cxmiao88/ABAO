<div>
    @php
        $canUpdate = auth()->user()->can('update', $application);
        $labelsManagedByCoolify = $application->settings->is_container_label_readonly_enabled;
        // Use model UUIDs: Livewire update requests do not carry page route params.
        $generalRouteParameters = [
            'project_uuid' => data_get($application, 'environment.project.uuid'),
            'environment_uuid' => data_get($application, 'environment.uuid'),
            'application_uuid' => $application->uuid,
        ];
    @endphp

    <div class="flex flex-col gap-6">
        <x-application.settings-section id="advanced-build-section" title="{{ __('application.adv_build_title') }}"
            helper="{{ __('application.adv_build_helper') }}">
            <div class="grid w-full gap-4 sm:grid-cols-2">
                <x-forms.listbox id="disableBuildCache" label="{{ __('application.adv_build_cache') }}" onChange="instantSave"
                    helper="{{ __('application.adv_build_cache_helper') }}"
                    :options="[
                        ['value' => false, 'label' => __('application.adv_opt_use_cache')],
                        ['value' => true, 'label' => __('application.adv_opt_rebuild_scratch')],
                    ]" :disabled="! $canUpdate" />
                <x-forms.listbox id="injectBuildArgsToDockerfile" label="{{ __('application.adv_build_args') }}" onChange="instantSave"
                    helper="{{ __('application.adv_build_args_helper') }}"
                    :options="[
                        ['value' => true, 'label' => __('application.adv_opt_inject_args')],
                        ['value' => false, 'label' => __('application.adv_opt_manual_dockerfile')],
                    ]" :disabled="! $canUpdate" />
                <x-forms.listbox id="includeSourceCommitInBuild" label="{{ __('application.adv_commit_availability') }}" onChange="instantSave"
                    helper="{{ __('application.adv_commit_availability_helper') }}"
                    :options="[
                        ['value' => false, 'label' => __('application.adv_opt_runtime_only')],
                        ['value' => true, 'label' => __('application.adv_opt_during_build')],
                    ]" :disabled="! $canUpdate" />
            </div>
        </x-application.settings-section>

        <x-application.settings-section id="advanced-container-section" title="{{ __('application.adv_container_title') }}"
            helper="{{ __('application.adv_container_helper') }}">
            <div class="grid w-full gap-4 sm:grid-cols-2">
                <x-forms.listbox id="isConsistentContainerNameEnabled" label="{{ __('application.adv_container_naming') }}" onChange="instantSave"
                    helper="{{ __('application.adv_container_naming_helper', ['uuid' => $application->uuid]) }}"
                    :options="[
                        ['value' => false, 'label' => __('application.adv_opt_generated_name')],
                        ['value' => true, 'label' => __('application.adv_opt_consistent_name')],
                    ]" :disabled="! $canUpdate" />
                @if ($isConsistentContainerNameEnabled === true)
                    <form wire:submit="saveCustomName" class="w-full">
                        <x-unsaved-bar action="saveCustomName" targets="customInternalName" />
                        <x-forms.input
                            helper="{{ __('application.adv_custom_name_helper') }}"
                            id="customInternalName" label="{{ __('application.adv_custom_name') }}" canGate="update"
                            :canResource="$application" />
                    </form>
                @else
                    <form wire:submit="saveCustomNamePrefix" class="w-full">
                        <x-unsaved-bar action="saveCustomNamePrefix" targets="customContainerNamePrefix" />
                        <x-forms.input
                            helper="{{ __('application.adv_name_prefix_helper', ['prefix_timestamp' => 'prefix-timestamp', 'example' => 'shop-api-20260908T141530', 'uuid' => $application->uuid, 'max' => \App\Models\ApplicationSetting::MAX_CONTAINER_NAME_PREFIX_LENGTH]) }}"
                            id="customContainerNamePrefix" label="{{ __('application.adv_name_prefix') }}" placeholder="{{ __('application.adv_name_prefix_placeholder') }}"
                            canGate="update" :canResource="$application" />
                    </form>
                @endif
            </div>
        </x-application.settings-section>

        @if ($application->git_based())
            <x-application.settings-section id="advanced-deployment-section" title="{{ __('application.adv_deployment_title') }}"
                helper="{{ __('application.adv_deployment_helper') }}">
                <div class="grid w-full gap-4 sm:grid-cols-2">
                    <x-forms.listbox id="isAutoDeployEnabled" label="{{ __('application.adv_auto_deploy') }}" onChange="instantSave"
                        helper="{{ __('application.adv_auto_deploy_helper') }}"
                        :options="[
                            ['value' => true, 'label' => __('application.adv_opt_deploy_on_push')],
                            ['value' => false, 'label' => __('application.adv_opt_manual_only')],
                        ]" :disabled="! $canUpdate" />
                </div>
            </x-application.settings-section>

            <x-application.settings-section id="advanced-git-section" title="{{ __('application.adv_git_title') }}"
                helper="{{ __('application.adv_git_helper') }}">
                <div class="grid w-full gap-4 sm:grid-cols-2">
                    <x-forms.listbox id="isGitSubmodulesEnabled" label="{{ __('application.adv_submodules') }}" onChange="instantSave"
                        helper="{{ __('application.adv_submodules_helper') }}"
                        :options="[
                            ['value' => true, 'label' => __('application.adv_opt_clone_submodules')],
                            ['value' => false, 'label' => __('application.adv_opt_skip_submodules')],
                        ]" :disabled="! $canUpdate" />
                    <x-forms.listbox id="isGitLfsEnabled" label="{{ __('application.adv_git_lfs') }}" onChange="instantSave"
                        helper="{{ __('application.adv_git_lfs_helper') }}"
                        :options="[
                            ['value' => true, 'label' => __('application.adv_opt_enabled')],
                            ['value' => false, 'label' => __('application.adv_opt_disabled')],
                        ]" :disabled="! $canUpdate" />
                    <x-forms.listbox id="isGitShallowCloneEnabled" label="{{ __('application.adv_clone_depth') }}" onChange="instantSave"
                        helper="{{ __('application.adv_clone_depth_helper') }}"
                        :options="[
                            ['value' => false, 'label' => __('application.adv_opt_full_history')],
                            ['value' => true, 'label' => __('application.adv_opt_shallow')],
                        ]" :disabled="! $canUpdate" />
                </div>
            </x-application.settings-section>
        @endif

        @if ($application->build_pack === 'dockercompose')
            <x-application.settings-section id="advanced-compose-section" title="{{ __('application.adv_compose_title') }}"
                helper="{{ __('application.adv_compose_helper') }}">
                <div class="grid w-full gap-4 sm:grid-cols-2">
                    <x-forms.listbox id="isRawComposeDeploymentEnabled" label="{{ __('application.adv_compose_deployment') }}" onChange="instantSave"
                        helper="{!! __('application.adv_compose_deployment_helper') !!}"
                        :options="[
                            ['value' => false, 'label' => __('application.adv_opt_managed')],
                            ['value' => true, 'label' => __('application.adv_opt_raw')],
                        ]" :disabled="! $canUpdate" />
                    <x-forms.listbox id="isConnectToDockerNetworkEnabled" label="{{ __('application.adv_predefined_network') }}" onChange="instantSave"
                        helper="{!! __('application.adv_predefined_network_helper') !!}"
                        :options="[
                            ['value' => false, 'label' => __('application.adv_opt_isolated')],
                            ['value' => true, 'label' => __('application.adv_opt_connect_predefined')],
                        ]" :disabled="! $canUpdate" />
                </div>
            </x-application.settings-section>
        @endif

        <x-application.settings-section id="advanced-proxy-section" title="{{ __('application.adv_proxy_title') }}"
            helper="{{ __('application.adv_proxy_helper') }}">
            @if ($labelsManagedByCoolify)
                <div class="grid w-full gap-4 sm:grid-cols-2">
                    <x-forms.listbox id="isGzipEnabled" label="{{ __('application.adv_gzip') }}" onChange="instantSave"
                        helper="{{ __('application.adv_gzip_helper') }}"
                        :options="[
                            ['value' => true, 'label' => __('application.adv_opt_enabled')],
                            ['value' => false, 'label' => __('application.adv_opt_disabled')],
                        ]" :disabled="! $canUpdate" />
                    <x-forms.listbox id="isStripprefixEnabled" label="{{ __('application.adv_path_prefixes') }}" onChange="instantSave"
                        helper="{{ __('application.adv_path_prefixes_helper') }}"
                        :options="[
                            ['value' => true, 'label' => __('application.adv_opt_strip')],
                            ['value' => false, 'label' => __('application.adv_opt_keep_paths')],
                        ]" :disabled="! $canUpdate" />
                </div>
            @else
                <x-empty size="sm" title="{{ __('application.adv_labels_managed') }}"
                    description="{{ __('application.adv_labels_managed_desc') }}"
                    icon-name="globe">
                    <x-slot:contents>
                        <a class="button"
                            href="{{ route('project.application.configuration', $generalRouteParameters) }}#container-labels-section"
                            {{ wireNavigate() }}>
                            {{ __('application.gen_go_labels') }}
                        </a>
                    </x-slot:contents>
                </x-empty>
            @endif
        </x-application.settings-section>

        <x-application.settings-section id="advanced-operations-section" title="{{ __('application.adv_operations_title') }}"
            helper="{{ __('application.adv_operations_helper') }}">
            <div class="grid w-full gap-4 lg:grid-cols-2">
                <x-forms.input type="number" id="stopGracePeriod" label="{{ __('application.adv_stop_grace') }}"
                    placeholder="{{ DEFAULT_STOP_GRACE_PERIOD_SECONDS }}" wire:change="saveStopGracePeriod"
                    helper="{{ __('application.adv_stop_grace_helper', ['default' => DEFAULT_STOP_GRACE_PERIOD_SECONDS, 'min' => MIN_STOP_GRACE_PERIOD_SECONDS, 'max' => MAX_STOP_GRACE_PERIOD_SECONDS]) }}"
                    min="{{ MIN_STOP_GRACE_PERIOD_SECONDS }}" max="{{ MAX_STOP_GRACE_PERIOD_SECONDS }}"
                    canGate="update" :canResource="$application" />
                <x-forms.input type="number" min="0" id="maxRestartCount" label="{{ __('application.adv_max_restart') }}"
                    wire:change="saveMaxRestartCount"
                    helper="{{ __('application.adv_max_restart_helper') }}"
                    canGate="update" :canResource="$application" />
            </div>
        </x-application.settings-section>

        <x-application.settings-section id="advanced-logs-section" title="{{ __('application.adv_logs_title') }}"
            helper="{{ __('application.adv_logs_helper') }}">
            <div class="grid w-full gap-4 sm:grid-cols-2">
                <x-forms.listbox id="isLogDrainEnabled" label="{{ __('application.adv_log_drain') }}" onChange="instantSave"
                    helper="{{ __('application.adv_log_drain_helper') }}"
                    :options="[
                        ['value' => false, 'label' => __('application.adv_opt_disabled')],
                        ['value' => true, 'label' => __('application.adv_opt_send_logs')],
                    ]" :disabled="! $canUpdate" />
            </div>
        </x-application.settings-section>

        @if ($application->build_pack !== 'dockercompose')
            <x-application.settings-section id="advanced-gpu-section" title="{{ __('application.adv_gpu_title') }}"
                helper="{!! __('application.adv_gpu_helper') !!}">
                <div class="grid w-full gap-4 sm:grid-cols-2">
                    <x-forms.listbox id="isGpuEnabled" label="{{ __('application.adv_gpu_access') }}" onChange="instantSave"
                        :options="[
                            ['value' => false, 'label' => __('application.adv_opt_disabled')],
                            ['value' => true, 'label' => __('application.adv_opt_enabled')],
                        ]" :disabled="! $canUpdate" />
                </div>
                @if ($isGpuEnabled)
                    <form id="gpu-settings-form" wire:submit="submit"
                        class="mt-5 flex w-full flex-col gap-4 border-t border-neutral-200 pt-5 dark:border-white/[0.07]">
                        {{-- Scope to GPU form fields; sibling instantSave listboxes share this component. --}}
                        <x-unsaved-bar action="submit"
                            targets="gpuDriver,gpuCount,gpuDeviceIds,gpuOptions" />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-forms.input label="{{ __('application.adv_gpu_driver') }}" id="gpuDriver" canGate="update" :canResource="$application" />
                            <x-forms.input label="{{ __('application.adv_gpu_count') }}" placeholder="{{ __('application.adv_gpu_count_placeholder') }}" id="gpuCount"
                                canGate="update" :canResource="$application" />
                        </div>
                        <x-forms.input label="{{ __('application.adv_gpu_device_ids') }}" placeholder="0,2"
                            helper="{!! __('application.adv_gpu_device_ids_helper') !!}"
                            id="gpuDeviceIds" canGate="update" :canResource="$application" />
                        <x-forms.textarea rows="6" label="{{ __('application.adv_gpu_options') }}" id="gpuOptions" canGate="update"
                            :canResource="$application" />
                    </form>
                @endif
            </x-application.settings-section>
        @endif
    </div>
</div>
