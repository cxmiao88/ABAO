<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.menu_docker_cleanup') }} | Coolify
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="docker-cleanup" />

        <div class="application-settings-form flex w-full flex-col gap-6">
            <form wire:submit="submit" class="contents">
                {{-- Scope to cron/threshold fields; listboxes use instantSave and would flash the bar. --}}
                <x-unsaved-bar action="submit"
                    targets="dockerCleanupFrequency,dockerCleanupThreshold" />

                <x-application.settings-section id="docker-cleanup-overview-section" :title="__('server.menu_docker_cleanup')"
                    :helper="__('server.dc_overview_helper')">
                    <x-slot:actions>
                        @can('update', $server)
                            <x-modal-confirmation :title="__('server.dc_confirm_title')"
                                :buttonTitle="__('server.dc_run_cleanup')" isHighlightedButton submitAction="manualCleanup"
                                :actions="[
                                    __('server.dc_action_1'),
                                    __('server.dc_action_2'),
                                    __('server.dc_action_3'),
                                    __('server.dc_action_4'),
                                ]" :confirmWithText="false" :confirmWithPassword="false"
                                :step2ButtonText="__('server.dc_step2_button')" />
                        @endcan
                    </x-slot:actions>

                    @if (!isCloud() && $this->isCleanupStale)
                        <x-callout type="warning" :title="__('server.dc_stalled_title')">
                            {{ __('server.dc_stalled_last_run', ['time' => $this->lastExecutionTime ?? __('server.dc_unknown_time')]) }}
                            @if (!$this->isSchedulerHealthy)
                                {{ __('server.dc_scheduler_inactive') }}
                            @endif
                            {{ __('server.dc_stalled_command_prefix') }}
                            <code class="rounded bg-black/10 px-1 dark:bg-white/10">php artisan cleanup:redis --clear-locks</code>
                            {{ __('server.dc_stalled_command_suffix') }}
                        </x-callout>
                    @else
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-500 dark:bg-white/[0.06] dark:text-fg-dim">
                                <x-reicon name="storages" class="size-4" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-950 dark:text-fg">{{ __('server.dc_scheduled_title') }}</p>
                                <p class="mt-1 text-xs leading-5 text-neutral-500 dark:text-fg-dim">
                                    {{ __('server.dc_scheduled_description') }}
                                </p>
                            </div>
                        </div>
                    @endif
                </x-application.settings-section>

                <x-application.settings-section id="docker-cleanup-configuration-section"
                    :title="__('server.dc_config_title')"
                    :helper="__('server.dc_config_helper')">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-forms.input canGate="update" :canResource="$server" placeholder="0 0 * * *"
                            id="dockerCleanupFrequency" :label="__('server.dc_frequency_label')" required
                            :helper="__('server.adv_check_frequency_helper')" />
                        @if (!$forceDockerCleanup)
                            <x-forms.input canGate="update" :canResource="$server" id="dockerCleanupThreshold"
                                type="number" min="1" max="99" :label="__('server.dc_threshold_label')" required
                                :helper="__('server.dc_threshold_helper')" />
                        @endif
                        <x-forms.listbox id="forceDockerCleanup" :label="__('server.dc_trigger_label')"
                            :helper="__('server.dc_trigger_helper')"
                            onChange="instantSave" :options="[
                                ['value' => false, 'label' => __('server.dc_trigger_threshold')],
                                ['value' => true, 'label' => __('server.dc_trigger_schedule')],
                            ]" />
                    </div>
                </x-application.settings-section>

                <x-application.settings-section id="docker-cleanup-advanced-section" :title="__('server.dc_advanced_title')"
                    :helper="__('server.dc_advanced_helper')">
                    <x-callout type="warning" :title="__('server.dc_destructive_warning_title')">
                        {{ __('server.dc_destructive_warning_text') }}
                    </x-callout>

                    <div class="mt-4 grid gap-4 lg:grid-cols-3">
                        <x-forms.listbox id="deleteUnusedVolumes" :label="__('server.dc_volumes_label')"
                            :helper="__('server.dc_volumes_helper')"
                            onChange="instantSave" :options="[
                                ['value' => false, 'label' => __('server.dc_volumes_keep')],
                                ['value' => true, 'label' => __('server.dc_volumes_delete')],
                            ]" />
                        <x-forms.listbox id="deleteUnusedNetworks" :label="__('server.dc_networks_label')"
                            :helper="__('server.dc_networks_helper')"
                            onChange="instantSave" :options="[
                                ['value' => false, 'label' => __('server.dc_networks_keep')],
                                ['value' => true, 'label' => __('server.dc_networks_delete')],
                            ]" />
                        <x-forms.listbox id="disableApplicationImageRetention" :label="__('server.dc_images_label')"
                            :helper="__('server.dc_images_helper')"
                            onChange="instantSave" :options="[
                                ['value' => false, 'label' => __('server.dc_images_keep')],
                                ['value' => true, 'label' => __('server.dc_images_delete')],
                            ]" />
                    </div>
                </x-application.settings-section>
            </form>

            <x-application.settings-section id="docker-cleanup-executions-section" :title="__('server.dc_executions_title')"
                :helper="__('server.dc_executions_helper')" flush>
                <livewire:server.docker-cleanup-executions :server="$server" />
            </x-application.settings-section>
        </div>
    </div>
</div>
