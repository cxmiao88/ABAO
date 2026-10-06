<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.menu_advanced') }} | Coolify
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="advanced" />

        <form wire:submit="submit" class="application-settings-form flex w-full flex-col gap-6">
            <x-unsaved-bar action="submit" />

            <x-application.settings-section id="server-disk-usage-section" :title="__('server.adv_disk_title')"
                :helper="__('server.adv_disk_helper')">
                <div class="grid gap-4 lg:grid-cols-3">
                    <x-forms.input canGate="update" :canResource="$server" placeholder="0 23 * * *"
                        id="serverDiskUsageCheckFrequency" :label="__('server.adv_check_frequency')" required
                        :helper="__('server.adv_check_frequency_helper')" />
                    <x-forms.input canGate="update" :canResource="$server"
                        id="serverDiskUsageNotificationThreshold" type="number" min="1" max="99"
                        :label="__('server.adv_notification_threshold')" required
                        :helper="__('server.adv_notification_threshold_helper')" />
                    <x-forms.input canGate="update" :canResource="$server"
                        id="serverDiskUsageNotificationIntervalHours" type="number" min="1" max="720"
                        :label="__('server.adv_notification_interval')" required
                        :helper="__('server.adv_notification_interval_helper')" />
                </div>
            </x-application.settings-section>

            <x-application.settings-section id="server-backups-section" :title="__('server.adv_backups_title')"
                :helper="__('server.adv_backups_helper')">
                <x-forms.listbox canGate="update" :canResource="$server" id="backupCompressionCpuPercentage"
                    :label="__('server.adv_backup_cpu_label')" onChange="instantSave"
                    :helper="__('server.adv_backup_cpu_helper')" :options="[
                        ['value' => 25, 'label' => __('server.adv_backup_cpu_low')],
                        ['value' => 50, 'label' => __('server.adv_backup_cpu_balanced')],
                        ['value' => 75, 'label' => __('server.adv_backup_cpu_high')],
                        ['value' => 100, 'label' => __('server.adv_backup_cpu_max')],
                    ]" />
            </x-application.settings-section>

            <x-application.settings-section id="server-builds-section" :title="__('server.adv_builds_title')"
                :helper="__('server.adv_builds_helper')">
                <div class="grid gap-4 lg:grid-cols-3">
                    <x-forms.input canGate="update" :canResource="$server" id="concurrentBuilds"
                        type="number" min="1" :label="__('server.adv_concurrent_builds')" required
                        :helper="__('server.adv_concurrent_builds_helper')" />
                    <x-forms.input canGate="update" :canResource="$server" id="dynamicTimeout"
                        type="number" min="1" :label="__('server.adv_deployment_timeout')" required
                        :helper="__('server.adv_deployment_timeout_helper')" />
                    <x-forms.input canGate="update" :canResource="$server" id="deploymentQueueLimit"
                        type="number" min="1" :label="__('server.adv_queue_limit')" required
                        :helper="__('server.adv_queue_limit_helper')" />
                </div>
            </x-application.settings-section>
        </form>
    </div>
</div>
