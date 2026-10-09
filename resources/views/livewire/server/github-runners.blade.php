<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.menu_github_runners') }} | ABao
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="github-runners" />

        <div class="application-settings-form flex w-full flex-col gap-6">
            @if (!$server->isBuildServer())
                <x-application.settings-section id="github-runners-section" :title="__('server.gh_title')"
                    :helper="__('server.gh_helper')">
                    <x-slot:actions>
                        <x-beta-badge />
                        <a class="button" href="{{ route('server.show', ['server_uuid' => $server->uuid]) }}"
                            {{ wireNavigate() }}>
                            {{ __('server.gh_general_settings') }}
                            <x-external-link />
                        </a>
                    </x-slot:actions>
                    <x-empty size="sm" :title="__('server.gh_not_build_server_title')"
                        :description="__('server.gh_not_build_server_description')"
                        icon-name="play-circle" />
                </x-application.settings-section>
            @elseif ($this->githubApps->isEmpty())
                <x-callout type="info" :title="__('server.gh_no_app_title')">
                    {{ __('server.gh_no_app_description') }}
                </x-callout>
            @elseif (! $this->config?->is_enabled)
                <x-application.settings-section id="github-runners-section" :title="__('server.gh_title')"
                    :helper="__('server.gh_helper')">
                    <x-slot:actions>
                        <x-beta-badge />
                    </x-slot:actions>
                    <x-empty size="sm" :title="__('server.gh_disabled_title')"
                        :description="__('server.gh_disabled_description')"
                        icon-name="play-circle">
                        <x-slot:contents>
                            <x-forms.button canGate="update" :canResource="$server" isHighlighted
                                wire:click="toggleEnabled" wire:loading.attr="disabled" wire:target="toggleEnabled">
                                {{ __('server.gh_enable_runners') }}
                            </x-forms.button>
                        </x-slot:contents>
                    </x-empty>
                </x-application.settings-section>
            @else
                <form wire:submit="submit" class="contents">
                    <x-unsaved-bar action="submit"
                        targets="githubAppId,labels,maxRunners,dockerMode,runnerImage,cpuLimit,memoryLimit,capacityWaitTimeout,idleTimeout,jobTimeout,isDedicated,allowPullRequests" />
                    <x-application.settings-section id="github-runners-section" :title="__('server.gh_title')"
                        :helper="__('server.gh_helper_runs_on')">
                        <x-slot:actions>
                            <x-beta-badge />
                            @can('update', $server)
                                <x-forms.button wire:click="toggleEnabled" wire:loading.attr="disabled"
                                    wire:target="toggleEnabled">
                                    {{ __('server.gh_disable_runners') }}
                                </x-forms.button>
                            @endcan
                        </x-slot:actions>

                        <div x-cloak x-show="$wire.dockerMode === 'dind'">
                            <x-callout type="warning" :title="__('server.gh_dind_warning_title')">
                                {{ __('server.gh_dind_warning_text') }}
                            </x-callout>
                        </div>
                        <div x-cloak x-show="$wire.allowPullRequests" class="mt-4">
                            <x-callout type="warning" :title="__('server.gh_pr_warning_title')">
                                {{ __('server.gh_pr_warning_text') }}
                            </x-callout>
                        </div>

                        @if ($this->selectedApp && $this->selectedApp->missingRunnerRequirements() !== [])
                            <x-callout type="danger" :title="__('server.gh_app_not_ready_title')" class="mt-4">
                                {{ __('server.gh_app_not_ready_text') }}
                                <ul class="mt-1 list-disc pl-4">
                                    @foreach ($this->selectedApp->missingRunnerRequirements() as $requirement)
                                        <li>{{ $requirement }}</li>
                                    @endforeach
                                </ul>
                            </x-callout>
                        @endif

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <x-forms.listbox id="githubAppId" :label="__('server.gh_app_label')" required canGate="update"
                                :canResource="$server" :options="$this->githubApps
                                    ->map(fn($app) => ['value' => $app->id, 'label' => $app->name . ' (' . $app->organization . ')'])
                                    ->values()
                                    ->all()"
                                :helper="__('server.gh_app_helper')" />
                            <x-forms.input id="labels" :label="__('server.gh_labels_label')" required canGate="update" :canResource="$server"
                                placeholder="coolify"
                                :helper="__('server.gh_labels_helper')" />
                            <x-forms.listbox id="isDedicated" :label="__('server.gh_builds_label')" canGate="update"
                                :canResource="$server" :options="[
                                    ['value' => false, 'label' => __('server.gh_builds_also')],
                                    ['value' => true, 'label' => __('server.gh_builds_dedicated')],
                                ]"
                                :helper="__('server.gh_builds_helper')" />
                            <x-forms.listbox id="allowPullRequests" :label="__('server.gh_pr_label')" canGate="update"
                                :canResource="$server" :options="[
                                    ['value' => false, 'label' => __('server.gh_pr_refuse')],
                                    ['value' => true, 'label' => __('server.gh_pr_run')],
                                ]"
                                :helper="__('server.gh_pr_helper')" />
                        </div>
                    </x-application.settings-section>

                    <x-application.settings-section id="github-runners-resources-section" :title="__('server.menu_resources')"
                        :helper="__('server.gh_resources_helper')">
                        <div class="grid gap-4 lg:grid-cols-3">
                            <x-forms.input id="maxRunners" type="number" min="1" max="32" :label="__('server.gh_parallel_label')"
                                required canGate="update" :canResource="$server"
                                :helper="__('server.gh_parallel_helper')" />
                            <x-forms.input id="cpuLimit" :label="__('server.gh_cpu_limit')" placeholder="2" canGate="update"
                                :canResource="$server" :helper="__('server.gh_cpu_limit_helper')" />
                            <x-forms.input id="memoryLimit" :label="__('server.gh_memory_limit')" placeholder="4g" canGate="update"
                                :canResource="$server" :helper="__('server.gh_memory_limit_helper')" />
                            <x-forms.listbox id="dockerMode" :label="__('server.gh_docker_mode_label')" canGate="update"
                                :canResource="$server" :options="[
                                    ['value' => 'dind', 'label' => __('server.gh_docker_dind')],
                                    ['value' => 'sysbox', 'label' => __('server.gh_docker_sysbox')],
                                    ['value' => 'none', 'label' => __('server.gh_docker_none')],
                                ]"
                                :helper="__('server.gh_docker_mode_helper')" />
                            <x-forms.input id="runnerImage" :label="__('server.gh_runner_image')" canGate="update" :canResource="$server"
                                :placeholder="config('constants.github_runner.image')"
                                :helper="__('server.gh_runner_image_helper')" />
                        </div>
                        <div x-cloak x-show="$wire.dockerMode === 'sysbox'" class="mt-4"
                            x-effect="if ($wire.dockerMode === 'sysbox' && $wire.isSysboxInstalled === null) $wire.checkSysbox()">
                            @if ($isSysboxInstalled === true)
                                <x-callout type="success" :title="__('server.gh_sysbox_installed_title')">
                                    {{ __('server.gh_sysbox_installed_text') }}
                                </x-callout>
                            @elseif ($isSysboxInstalled === false)
                                <x-callout type="warning" :title="__('server.gh_sysbox_missing_title')">
                                    {{ __('server.gh_sysbox_missing_text', ['version' => config('constants.github_runner.sysbox.version')]) }}
                                    @can('update', $server)
                                        <div class="mt-3">
                                            <x-forms.button type="button" wire:click="installSysbox"
                                                wire:loading.attr="disabled" wire:target="installSysbox">
                                                {{ __('server.gh_install_sysbox') }}
                                            </x-forms.button>
                                        </div>
                                    @endcan
                                </x-callout>
                            @else
                                <x-callout type="info" :title="__('server.gh_sysbox_checking_title')">
                                    {{ __('server.gh_sysbox_checking_text') }}
                                </x-callout>
                            @endif
                        </div>
                    </x-application.settings-section>

                    <x-application.settings-section id="github-runners-timeouts-section" :title="__('server.gh_timeouts_title')"
                        :helper="__('server.gh_timeouts_helper')">
                        <div class="grid gap-4 lg:grid-cols-3">
                            <x-forms.input id="capacityWaitTimeout" type="number" min="1" max="1440"
                                :label="__('server.gh_queue_wait')" required canGate="update" :canResource="$server"
                                :helper="__('server.gh_queue_wait_helper')" />
                            <x-forms.input id="idleTimeout" type="number" min="1" max="1440" :label="__('server.gh_idle_runner')"
                                required canGate="update" :canResource="$server"
                                :helper="__('server.gh_idle_runner_helper')" />
                            <x-forms.input id="jobTimeout" type="number" min="1" max="7200" :label="__('server.gh_job_label')" required
                                canGate="update" :canResource="$server"
                                :helper="__('server.gh_job_helper')" />
                        </div>
                    </x-application.settings-section>
                </form>
                @can('update', $server)
                    <x-process-dialog @sysbox-install-started.window="processDialogOpen = true" closeWithX size="xl">
                        <x-slot:title>{{ __('server.gh_install_sysbox') }}</x-slot:title>
                        <x-slot:content>
                            <livewire:activity-monitor :header="__('server.sub_logs')" fullHeight />
                        </x-slot:content>
                    </x-process-dialog>
                @endcan
            @endif

            @if ($server->isBuildServer() && $this->config?->is_enabled)
                <x-application.settings-section id="github-runners-executions-section" :title="__('server.gh_recent_title')"
                    :helper="__('server.gh_recent_helper')" flush>
                    <livewire:server.github-runner-executions :server="$server" />
                </x-application.settings-section>
            @endif
        </div>
    </div>
</div>
