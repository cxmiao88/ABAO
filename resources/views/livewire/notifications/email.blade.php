<div>
    <x-slot:title>
        {{ __('not_email_title') }}
    </x-slot>

    <x-notification.settings-layout>
    <div class="flex flex-col gap-6">
        <form wire:submit="submit" class="application-settings-form">
            <x-unsaved-bar action="submit" />
            <x-application.settings-section :title="__('not_email_delivery')">
                <x-slot:actions>
                    @if (auth()->user()->isAdminFromSession())
                        @can('sendTest', $settings)
                            @if ($team->isNotificationEnabled('email'))
                                <x-modal-input :title="__('not_send_test_email')">
                                    <x-slot:content>
                                        <button type="button" class="button">
                                            <x-reicon name="notifications" class="size-3.5" />
                                            {{ __('not_send_test') }}
                                        </button>
                                    </x-slot:content>
                                    <form wire:submit.prevent="sendTestEmail" class="flex w-full flex-col gap-4">
                                        <x-forms.input wire:model="testEmailAddress" placeholder="test@example.com"
                                            id="testEmailAddress" :label="__('not_recipient')" required />
                                        <div class="flex justify-end border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                                            <button type="submit" @click="modalOpen=false"
                                                class="button button-highlighted">
                                                {{ __('not_send_email') }}
                                            </button>
                                        </div>
                                    </form>
                                </x-modal-input>
                            @else
                                <button type="button" class="button" disabled>{{ __('not_send_test') }}</button>
                            @endif
                        @endcan
                    @endif
                </x-slot:actions>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        @if (isCloud())
                            <div class="w-full sm:w-72">
                                <x-forms.listbox canGate="update" :canResource="$settings" id="useInstanceEmailSettings" :label="__('not_email_service')"
                                    onChange="instantSave"
                                    :disabled="!auth()->user()->can('update', $settings)" :options="[
                                        ['value' => true, 'label' => __('not_use_hosted_email')],
                                        ['value' => false, 'label' => __('not_use_team_email')],
                                    ]" />
                            </div>
                        @else
                            <div class="w-full sm:w-72">
                                <x-forms.listbox canGate="update" :canResource="$settings" id="useInstanceEmailSettings" :label="__('not_email_service')"
                                    onChange="instantSave"
                                    :disabled="!auth()->user()->can('update', $settings)" :options="[
                                        ['value' => true, 'label' => __('not_use_system_email')],
                                        ['value' => false, 'label' => __('not_use_team_email')],
                                    ]" />
                            </div>
                        @endif
                    </div>

                    @if (!$useInstanceEmailSettings)
                        <x-forms.input canGate="update" :canResource="$settings" required id="smtpFromName"
                            helper="{{ __('not_from_name_helper') }}" :label="__('not_from_name')" />
                        <x-forms.input canGate="update" :canResource="$settings" required id="smtpFromAddress"
                            helper="{{ __('not_from_address_helper') }}" :label="__('not_from_address')" />

                        @if (isInstanceAdmin())
                            <div class="lg:col-span-2">
                                <x-forms.button type="button" wire:click="copyFromInstanceSettings">
                                    {{ __('not_copy_from_instance') }}
                                </x-forms.button>
                            </div>
                        @endif
                    @endif
                </div>
            </x-application.settings-section>
        </form>

        @if (!$useInstanceEmailSettings)
            <div class="application-settings-form">
                <x-application.settings-section :title="__('not_smtp_server')"
                    description="{{ __('not_smtp_server_desc') }}">
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div class="lg:col-span-3">
                            <div class="w-full sm:w-72">
                                <x-forms.listbox canGate="update" :canResource="$settings" id="smtpEnabled" :label="__('not_smtp_delivery')"
                                    onChange="submitSmtp"
                                    :disabled="!auth()->user()->can('update', $settings)" :options="[
                                        ['value' => true, 'label' => __('not_enabled')],
                                        ['value' => false, 'label' => __('not_disabled')],
                                    ]" />
                            </div>
                        </div>
                        <x-forms.input canGate="update" :canResource="$settings" required id="smtpHost"
                            placeholder="smtp.mailgun.org" :label="__('not_host')" />
                        <x-forms.input canGate="update" :canResource="$settings" required id="smtpPort"
                            type="number" placeholder="587" :label="__('not_port')" />
                        <x-forms.listbox canGate="update" :canResource="$settings" id="smtpEncryption" :label="__('not_encryption')" required
                            :disabled="!auth()->user()->can('update', $settings)" :options="[
                            ['value' => 'starttls', 'label' => __('not_starttls')],
                            ['value' => 'tls', 'label' => __('not_tls_ssl')],
                            ['value' => 'none', 'label' => __('not_none')],
                        ]" />
                        <x-forms.input canGate="update" :canResource="$settings" id="smtpUsername"
                            :label="__('not_smtp_username')" />
                        @can('update', $settings)
                            <x-forms.input canGate="update" :canResource="$settings" id="smtpPassword" type="password"
                                :label="__('not_smtp_password')" />
                        @else
                            <x-forms.input disabled :label="__('not_smtp_password')" value="{{ __('not_hidden_admin') }}" />
                        @endcan
                        <x-forms.input canGate="update" :canResource="$settings" id="smtpTimeout" type="number"
                            helper="{{ __('not_timeout_helper') }}" :label="__('not_timeout')" />
                        <x-forms.input canGate="update" :canResource="$settings" id="smtpEhloDomain"
                            placeholder="coolify.example.com"
                            helper="{{ __('not_ehlo_helper') }}"
                            :label="__('not_ehlo_domain')" />
                    </div>
                </x-application.settings-section>
            </div>

            <div class="application-settings-form">
                <x-application.settings-section :title="__('not_resend')">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-forms.listbox canGate="update" :canResource="$settings" id="resendEnabled" :label="__('not_resend_delivery')"
                            onChange="submitResend"
                            :disabled="!auth()->user()->can('update', $settings)" :options="[
                                ['value' => true, 'label' => __('not_enabled')],
                                ['value' => false, 'label' => __('not_disabled')],
                            ]" />
                        @can('update', $settings)
                            <x-forms.input canGate="update" :canResource="$settings" :required="$resendEnabled"
                                type="password" id="resendApiKey" placeholder="{{ __('not_api_token') }}" :label="__('not_api_token')"
                                autocomplete="new-password" />
                        @else
                            <x-forms.input disabled :label="__('not_api_token')" value="{{ __('not_hidden_admin') }}" />
                        @endcan
                    </div>
                </x-application.settings-section>
            </div>
        @endif

        <div class="application-settings-form">
            <x-application.settings-section :title="__('not_notification_events')">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-notification.event-multiselect :settings="$settings" id="deployment-email-events" :label="__('not_deployments')"
                        :events="[
                            ['property' => 'deploymentSuccessEmailNotifications', 'label' => __('not_deployment_success'), 'enabled' => $deploymentSuccessEmailNotifications],
                            ['property' => 'deploymentFailureEmailNotifications', 'label' => __('not_deployment_failure'), 'enabled' => $deploymentFailureEmailNotifications],
                        ]" />
                    <x-notification.event-multiselect :settings="$settings" id="resource-email-events" :label="__('not_resources')"
                        :events="[
                            ['property' => 'statusChangeEmailNotifications', 'label' => __('not_resource_status_changes'), 'enabled' => $statusChangeEmailNotifications],
                            ['property' => 'restartLimitReachedEmailNotifications', 'label' => __('not_restart_limit_reached'), 'enabled' => $restartLimitReachedEmailNotifications],
                        ]" />
                    <x-notification.event-multiselect :settings="$settings" id="backup-email-events" :label="__('not_backups')"
                        :events="[
                            ['property' => 'backupSuccessEmailNotifications', 'label' => __('not_backup_success'), 'enabled' => $backupSuccessEmailNotifications],
                            ['property' => 'backupFailureEmailNotifications', 'label' => __('not_backup_failure'), 'enabled' => $backupFailureEmailNotifications],
                        ]" />
                    <x-notification.event-multiselect :settings="$settings" id="scheduled-task-email-events"
                        :label="__('not_scheduled_tasks')" :events="[
                            ['property' => 'scheduledTaskSuccessEmailNotifications', 'label' => __('not_scheduled_task_success'), 'enabled' => $scheduledTaskSuccessEmailNotifications],
                            ['property' => 'scheduledTaskFailureEmailNotifications', 'label' => __('not_scheduled_task_failure'), 'enabled' => $scheduledTaskFailureEmailNotifications],
                        ]" />
                    <x-notification.event-multiselect :settings="$settings" id="server-email-events" :label="__('not_servers')"
                        :events="[
                            ['property' => 'dockerCleanupSuccessEmailNotifications', 'label' => __('not_docker_cleanup_success'), 'enabled' => $dockerCleanupSuccessEmailNotifications],
                            ['property' => 'dockerCleanupFailureEmailNotifications', 'label' => __('not_docker_cleanup_failure'), 'enabled' => $dockerCleanupFailureEmailNotifications],
                            ['property' => 'serverDiskUsageEmailNotifications', 'label' => __('not_disk_usage_warning'), 'enabled' => $serverDiskUsageEmailNotifications],
                            ['property' => 'serverReachableEmailNotifications', 'label' => __('not_server_reachable'), 'enabled' => $serverReachableEmailNotifications],
                            ['property' => 'serverUnreachableEmailNotifications', 'label' => __('not_server_unreachable'), 'enabled' => $serverUnreachableEmailNotifications],
                            ['property' => 'serverPatchEmailNotifications', 'label' => __('not_server_patching'), 'enabled' => $serverPatchEmailNotifications],
                            ['property' => 'traefikOutdatedEmailNotifications', 'label' => __('not_traefik_outdated'), 'enabled' => $traefikOutdatedEmailNotifications],
                        ]" />
                </div>
            </x-application.settings-section>
        </div>
    </div>
    </x-notification.settings-layout>
</div>
