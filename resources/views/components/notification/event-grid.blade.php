@props([
    'settings',
    'channel',
    'threaded' => false,
])

@php
    $eventGroups = [
        __('not_deployments') => [
            ['key' => 'deploymentSuccess', 'label' => __('not_deployment_success')],
            ['key' => 'deploymentFailure', 'label' => __('not_deployment_failure')],
        ],
        __('not_resources') => [
            [
                'key' => 'statusChange',
                'label' => __('not_resource_status_changes'),
                'helper' => __('not_resource_status_changes_helper'),
            ],
            [
                'key' => 'restartLimitReached',
                'label' => __('not_restart_limit_reached'),
                'helper' => __('not_restart_limit_reached_helper'),
            ],
        ],
        __('not_backups') => [
            ['key' => 'backupSuccess', 'label' => __('not_backup_success')],
            ['key' => 'backupFailure', 'label' => __('not_backup_failure')],
        ],
        __('not_scheduled_tasks') => [
            ['key' => 'scheduledTaskSuccess', 'label' => __('not_scheduled_task_success')],
            ['key' => 'scheduledTaskFailure', 'label' => __('not_scheduled_task_failure')],
        ],
        __('not_servers') => [
            ['key' => 'dockerCleanupSuccess', 'label' => __('not_docker_cleanup_success')],
            ['key' => 'dockerCleanupFailure', 'label' => __('not_docker_cleanup_failure')],
            ['key' => 'serverDiskUsage', 'label' => __('not_disk_usage_warning')],
            ['key' => 'serverReachable', 'label' => __('not_server_reachable')],
            ['key' => 'serverUnreachable', 'label' => __('not_server_unreachable')],
            ['key' => 'serverPatch', 'label' => __('not_server_patching')],
            ['key' => 'traefikOutdated', 'label' => __('not_traefik_outdated')],
        ],
    ];

    $enabledThreadEvents = [];
    if ($threaded) {
        foreach ($eventGroups as $group => $events) {
            foreach ($events as $event) {
                $enabled = (bool) data_get(
                    $settings,
                    Str::snake($event['key'] . '_' . $channel . '_notifications'),
                );

                if (! $enabled) {
                    continue;
                }

                $enabledThreadEvents[] = [
                    'group' => $group,
                    'key' => $event['key'],
                    'label' => $event['label'],
                    'threadModel' => Str::camel(
                        Str::studly($channel) . 'Notifications' . Str::studly($event['key']) . 'ThreadId',
                    ),
                ];
            }
        }

        $enabledThreadEventsByGroup = collect($enabledThreadEvents)->groupBy('group');
    }
@endphp

<div class="flex flex-col gap-6">
    <x-application.settings-section :title="__('not_notification_events')"
        description="{{ __('not_events_desc') }}">
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($eventGroups as $group => $events)
                @php
                    $multiselectEvents = collect($events)
                        ->map(fn ($event) => [
                            'property' => $event['key'] . Str::studly($channel) . 'Notifications',
                            'label' => $event['label'],
                            'enabled' => (bool) data_get(
                                $settings,
                                Str::snake($event['key'] . '_' . $channel . '_notifications'),
                            ),
                        ])
                        ->all();
                    $groupId = Str::slug($channel . '-' . $group . '-events');
                @endphp
                <div class="min-w-0">
                    <x-notification.event-multiselect :settings="$settings" :id="$groupId"
                        :label="$group" :events="$multiselectEvents" />
                </div>
            @endforeach
        </div>
    </x-application.settings-section>

    @if ($threaded)
        <x-application.settings-section :title="__('not_forum_topics')"
            description="{{ __('not_forum_topics_desc') }}">
            @if ($enabledThreadEvents === [])
                <p class="text-[13px] leading-relaxed text-neutral-500 dark:text-fg-dim">
                    {{ __('not_enable_events_hint') }}
                </p>
            @else
                <div class="flex flex-col gap-5">
                    @foreach ($enabledThreadEventsByGroup as $group => $events)
                        <div class="min-w-0">
                            <div
                                class="mb-2 text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">
                                {{ $group }}
                            </div>
                            <div
                                class="divide-y divide-neutral-200 overflow-hidden rounded-xl border border-neutral-200 dark:divide-white/[0.06] dark:border-white/[0.08]">
                                @foreach ($events as $event)
                                    <div
                                        class="grid gap-2 px-3.5 py-3 sm:grid-cols-[minmax(0,1fr)_minmax(10rem,14rem)] sm:items-center sm:gap-4"
                                        wire:key="{{ $channel }}-thread-row-{{ $event['key'] }}">
                                        <div class="min-w-0">
                                            <div
                                                class="truncate text-[13px] font-medium text-black dark:text-fg">
                                                {{ $event['label'] }}
                                            </div>
                                            <div class="text-[11px] text-neutral-500 dark:text-fg-dim">
                                                {{ __('not_topic_id') }}
                                            </div>
                                        </div>
                                        <x-forms.input wire:key="{{ $channel }}-thread-{{ $event['key'] }}"
                                            canGate="update" :canResource="$settings" type="password"
                                            :id="$event['threadModel']" :label="null"
                                            :placeholder="__('not_optional')"
                                            :aria-label="__('not_topic_id_aria', ['label' => $event['label']])" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-application.settings-section>
    @endif
</div>
