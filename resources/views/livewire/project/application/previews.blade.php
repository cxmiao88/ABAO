<div class="flex flex-col gap-6">
    @if ($application->git_based())
        @php
            $canUpdate = auth()->user()->can('update', $application);
        @endphp
        <x-application.settings-section id="preview-settings-section" title="{{ __('application.pv_settings_title') }}"
            helper="{{ __('application.pv_settings_helper') }}">
            <x-slot:actions>
                @can('update', $application)
                    @if ($application->isGithubAppSource())
                        <x-modal-input title="{{ __('application.pv_prs_title') }}"
                            subtitle="{{ __('application.pv_prs_subtitle') }}"
                            :wireIgnore="false" :isLarge="true">
                            <x-slot:content>
                                <x-forms.button wire:click="load_prs">
                                    {{ __('application.pv_load_prs') }}
                                </x-forms.button>
                            </x-slot:content>
                            <x-slot:headerActions>
                                @isset($rate_limit_remaining)
                                    <span class="text-xs text-neutral-500 dark:text-fg-dim">
                                        {{ __('application.pv_requests_remaining', ['count' => $rate_limit_remaining]) }}
                                    </span>
                                @endisset
                                <x-forms.button wire:click="load_prs">
                                    {{ __('application.pv_refresh') }}
                                </x-forms.button>
                            </x-slot:headerActions>

                            <div class="flex min-h-48 items-center justify-center" wire:loading wire:target="load_prs">
                                <x-loading text="{{ __('application.pv_loading_prs') }}" />
                            </div>

                            <div class="-m-4" wire:loading.remove wire:target="load_prs">
                                @forelse ($pull_requests as $pull_request)
                                    <div
                                        class="flex flex-col gap-3 border-b border-neutral-200 px-4 py-3.5 last:border-b-0 sm:flex-row sm:items-center dark:border-white/[0.07]">
                                        <div
                                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 font-mono text-xs font-semibold text-neutral-600 ring-1 ring-neutral-200 dark:bg-white/[0.05] dark:text-fg-dim dark:ring-white/[0.07]">
                                            #{{ data_get($pull_request, 'number') }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="truncate text-sm font-semibold text-black dark:text-fg">
                                                {{ data_get($pull_request, 'title') }}
                                            </h4>
                                            <a target="_blank"
                                                class="mt-1 inline-flex items-center gap-1 text-xs text-neutral-500 hover:text-coollabs dark:text-fg-dim dark:hover:text-warning"
                                                href="{{ data_get($pull_request, 'html_url') }}">
                                                {{ __('application.pv_open_github') }}
                                                <x-external-link />
                                            </a>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <x-forms.button
                                                wire:click="add('{{ data_get($pull_request, 'number') }}', '{{ data_get($pull_request, 'html_url') }}')">
                                                {{ __('application.pv_configure') }}
                                            </x-forms.button>
                                            @can('deploy', $application)
                                                <x-forms.button
                                                    wire:click="add_and_deploy('{{ data_get($pull_request, 'number') }}', '{{ data_get($pull_request, 'html_url') }}')">
                                                    {{ __('application.pv_deploy_preview') }}
                                                </x-forms.button>
                                            @endcan
                                        </div>
                                    </div>
                                @empty
                                    <x-empty size="sm" title="{{ __('application.pv_no_prs') }}"
                                        description="{{ __('application.pv_no_prs_desc') }}"
                                        icon-name="sources" />
                                @endforelse
                            </div>
                        </x-modal-input>
                    @endif
                    @if ($isPreviewDeploymentsEnabled)
                        <x-forms.button wire:click="togglePreviewDeployments" wire:target="togglePreviewDeployments">
                            {{ __('application.pv_disable') }}
                        </x-forms.button>
                    @else
                        <x-forms.button wire:click="togglePreviewDeployments" wire:target="togglePreviewDeployments"
                            isHighlighted>
                            {{ __('application.pv_enable') }}
                        </x-forms.button>
                    @endif
                @endcan
            </x-slot:actions>

            <div class="w-full">
                <x-forms.listbox id="isPrDeploymentsPublicEnabled" label="{{ __('application.pv_pr_access') }}" onChange="savePreviewSettings"
                    helper="{{ __('application.pv_pr_access_helper') }}"
                    :options="[
                        ['value' => false, 'label' => __('application.pv_members_only')],
                        ['value' => true, 'label' => __('application.pv_public_fork')],
                    ]" :disabled="! $canUpdate || ! $isPreviewDeploymentsEnabled" />
            </div>
        </x-application.settings-section>
    @endif

    <livewire:project.application.preview.form :application="$application" />

    @if (count($application->additional_servers) > 0)
        <x-callout type="info" title="{{ __('application.pv_server_title') }}">
            {{ __('application.pv_server_body', ['name' => $application->destination->server->name]) }}
        </x-callout>
    @endif

    @if ($application->build_pack === 'dockerimage')
        <x-application.settings-section id="manual-preview-section" title="{{ __('application.pv_manual_title') }}"
            helper="{{ __('application.pv_manual_helper') }}">
            <form wire:submit.prevent="addDockerImagePreview"
                class="grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                <x-forms.input id="manualPullRequestId" label="{{ __('application.pv_preview_id') }}"
                    helper="{{ __('application.pv_preview_id_helper') }}" />
                <x-forms.input id="manualDockerTag" label="{{ __('application.pv_docker_tag') }}"
                    helper="{{ __('application.pv_docker_tag_helper') }}" />
                @can('deploy', $application)
                    <x-forms.button type="submit">{{ __('application.pv_deploy_preview') }}</x-forms.button>
                @endcan
            </form>
        </x-application.settings-section>
    @endif

    <x-application.settings-section id="preview-deployments-section" title="{{ __('application.pv_deployments_title') }}"
        helper="{{ __('application.pv_deployments_helper') }}" flush>
        @forelse (data_get($application, 'previews') as $previewName => $preview)
            @php
                $previewStatus = str(data_get($preview, 'status'));
                $previewIsStopped = $previewStatus->startsWith('exited');
            @endphp
            <section class="border-b border-neutral-200 p-4 last:border-b-0 dark:border-white/[0.07]"
                wire:key="preview-container-{{ $preview->pull_request_id }}">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 font-mono text-xs font-semibold text-neutral-600 ring-1 ring-neutral-200 dark:bg-white/[0.05] dark:text-fg-dim dark:ring-white/[0.07]">
                            #{{ data_get($preview, 'pull_request_id') }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-sm font-semibold text-black dark:text-fg">
                                    {{ __('application.pv_preview_number', ['id' => data_get($preview, 'pull_request_id')]) }}
                                </h4>
                                <x-status-summary :status="data_get($preview, 'status')" title="{{ __('application.pv_status') }}" />
                                <x-application.restart-limit-warning :application="$preview" />
                            </div>
                        </div>
                    </div>

                    <div id="preview-header-controls-{{ data_get($preview, 'pull_request_id') }}"
                        class="flex shrink-0 flex-wrap items-center gap-2 lg:justify-end">
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false"
                            @keydown.escape.window="open = false">
                            <button type="button" class="button gap-1.5" title="{{ __('application.pv_links_title') }}" @click="open = !open"
                                :aria-expanded="open" aria-haspopup="menu">
                                <x-reicon name="external-link" class="size-3.5 opacity-70" />
                                {{ __('application.pv_links') }}
                                <x-reicon name="chevron-down" class="size-3 opacity-55" />
                            </button>
                            <div x-cloak x-show="open" x-transition.origin.top.right
                                class="listbox-panel top-full! right-0! left-auto! z-[90]! mt-1! w-56! min-w-56!"
                                role="menu">
                                @if (!$previewIsStopped && filled(data_get($preview, 'fqdn')))
                                    <a target="_blank" title="{{ __('application.pv_open_preview_title') }}"
                                        class="listbox-option justify-start! gap-2.5!"
                                        href="{{ data_get($preview, 'fqdn') }}" @click="open = false" role="menuitem">
                                        <x-reicon name="external-link" class="size-3.5 opacity-70" />
                                        <span class="min-w-0 truncate">{{ __('application.pv_open_preview') }}</span>
                                    </a>
                                @endif
                                @if (filled(data_get($preview, 'pull_request_html_url')))
                                    <a target="_blank" title="{{ __('application.pv_open_pr_title') }}"
                                        class="listbox-option justify-start! gap-2.5!"
                                        href="{{ data_get($preview, 'pull_request_html_url') }}" @click="open = false"
                                        role="menuitem">
                                        <x-reicon name="external-link" class="size-3.5 opacity-70" />
                                        <span class="min-w-0 truncate">{{ __('application.pv_open_pull_request') }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if (count($parameters) > 0)
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false"
                                @keydown.escape.window="open = false">
                                <button type="button" class="button gap-1.5" title="{{ __('application.pv_logs_title') }}" @click="open = !open"
                                    :aria-expanded="open" aria-haspopup="menu">
                                    <x-reicon name="browser-terminal" class="size-3.5 opacity-70" />
                                    {{ __('application.pv_logs') }}
                                    <x-reicon name="chevron-down" class="size-3 opacity-55" />
                                </button>
                                <div x-cloak x-show="open" x-transition.origin.top.right
                                    class="listbox-panel top-full! right-0! left-auto! z-[90]! mt-1! w-44! min-w-44!"
                                    role="menu">
                                    <a {{ wireNavigate() }} class="listbox-option justify-start! gap-2.5!"
                                        href="{{ route('project.application.deployment.index', [...$parameters, 'pull_request_id' => data_get($preview, 'pull_request_id')]) }}"
                                        @click="open = false" role="menuitem">
                                        <x-reicon name="graph" class="size-3.5 opacity-70" />
                                        {{ __('application.pv_deployment_logs') }}
                                    </a>
                                    <a {{ wireNavigate() }} class="listbox-option justify-start! gap-2.5!"
                                        href="{{ route('project.application.logs', [...$parameters, 'pull_request_id' => data_get($preview, 'pull_request_id')]) }}"
                                        @click="open = false" role="menuitem">
                                        <x-reicon name="browser-terminal" class="size-3.5 opacity-70" />
                                        {{ __('application.pv_runtime_logs') }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div class="relative" x-data="{ open: false }" @click.outside="open = false"
                        @keydown.escape.window="open = false">
                        <button type="button" class="button gap-1.5" title="{{ __('application.pv_actions_title') }}" @click="open = !open"
                            :aria-expanded="open" aria-haspopup="menu">
                            {{ __('application.pv_actions') }}
                            <span class="inline-flex transition-transform" :class="open && 'rotate-180'">
                                <x-reicon name="chevron-down" class="size-3 opacity-55" />
                            </span>
                        </button>
                        <div x-cloak x-show="open" x-transition.origin.top.right
                            class="listbox-panel top-full! right-0! left-auto! z-[90]! mt-1! w-52! min-w-52!"
                            role="menu">
                            @can('deploy', $application)
                                <button type="button" class="listbox-option justify-start! gap-2.5!"
                                    wire:click="force_deploy_without_cache({{ data_get($preview, 'pull_request_id') }})"
                                    @click="open = false" role="menuitem">
                                    <x-reicon name="refresh" class="size-3.5 opacity-70" />
                                    {{ __('application.pv_rebuild') }}
                                </button>
                                <button type="button" class="listbox-option justify-start! gap-2.5!"
                                    wire:click="deploy({{ data_get($preview, 'pull_request_id') }}, null, false, '{{ data_get($preview, 'docker_registry_image_tag') }}')"
                                    @click="open = false" role="menuitem">
                                    <x-reicon name="play-circle" class="size-3.5 opacity-70" />
                                    {{ $previewIsStopped ? __('application.pv_deploy') : __('application.pv_redeploy') }}
                                </button>
                                @if (!$previewIsStopped)
                                    <button type="button"
                                        class="listbox-option justify-start! gap-2.5! text-error!"
                                        @click="open = false; document.getElementById('preview-stop-trigger-{{ data_get($preview, 'pull_request_id') }}')?.click()"
                                        role="menuitem">
                                        <x-reicon name="stop" class="size-3.5" />
                                        {{ __('application.pv_stop') }}
                                    </button>
                                @endif
                            @endcan
                            @can('delete', $application)
                                <button type="button"
                                    class="listbox-option justify-start! gap-2.5! text-error!"
                                    @click="open = false; document.getElementById('preview-delete-trigger-{{ data_get($preview, 'pull_request_id') }}')?.click()"
                                    role="menuitem">
                                    <x-reicon name="trash" class="size-3.5" />
                                    {{ __('application.pv_delete') }}
                                </button>
                            @endcan
                        </div>
                    </div>
                    </div>

                    <div class="hidden" aria-hidden="true">
                        @if (!$previewIsStopped)
                            @can('deploy', $application)
                                <x-modal-confirmation title="{{ __('application.pv_stop_title') }}" buttonTitle="{{ __('application.pv_stop') }}"
                                    submitAction="stop({{ data_get($preview, 'pull_request_id') }})"
                                    :actions="[
                                        __('application.pv_stop_action1'),
                                        __('application.pv_stop_action2'),
                                    ]"
                                    :confirmWithText="false" :confirmWithPassword="false"
                                    step2ButtonText="{{ __('application.pv_stop_step2') }}">
                                    <x-slot:trigger>
                                        <button id="preview-stop-trigger-{{ data_get($preview, 'pull_request_id') }}"
                                            type="button"></button>
                                    </x-slot:trigger>
                                </x-modal-confirmation>
                            @endcan
                        @endif
                        @can('delete', $application)
                            <x-modal-confirmation title="{{ __('application.pv_delete_title') }}" buttonTitle="{{ __('application.pv_delete') }}"
                                isErrorButton submitAction="delete({{ data_get($preview, 'pull_request_id') }})"
                                :actions="[__('application.pv_delete_action1')]"
                                confirmationText="{{ data_get($preview, 'fqdn') . '/' }}"
                                confirmationLabel="{{ __('application.pv_delete_confirm_label') }}"
                                shortConfirmationLabel="{{ __('application.pv_delete_confirm_short') }}" :confirmWithPassword="false">
                                <x-slot:trigger>
                                    <button id="preview-delete-trigger-{{ data_get($preview, 'pull_request_id') }}"
                                        type="button"></button>
                                </x-slot:trigger>
                            </x-modal-confirmation>
                        @endcan
                    </div>
                </div>

                <div class="mt-4 border-t border-neutral-200 pt-4 dark:border-white/[0.07]">
                    <livewire:project.application.preview-domains
                        wire:key="preview-domains-{{ $preview->id }}"
                        :preview="$preview" />

                    @if ($application->build_pack === 'dockerimage')
                        <form wire:submit="save_preview('{{ $preview->id }}')"
                            class="application-settings-section-body is-flush mt-3 overflow-visible">
                            <div class="data-table-header grid-cols-1"><span>{{ __('application.pv_docker_tag') }}</span></div>
                            <div class="p-3">
                                <x-forms.input id="previewDockerTags.{{ $previewName }}" canGate="update"
                                    :canResource="$application"
                                    wire:change="save_preview('{{ $preview->id }}')" />
                            </div>
                        </form>
                    @endif
                </div>
            </section>
        @empty
            <x-empty title="{{ __('application.pv_none') }}"
                description="{{ __('application.pv_none_desc') }}"
                icon-name="eye" />
        @endforelse
    </x-application.settings-section>

</div>
