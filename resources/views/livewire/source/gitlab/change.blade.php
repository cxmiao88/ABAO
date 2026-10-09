<div>
    <x-slot:title>
        {{ $name ?: __('src.gitlab_app') }} | {{ __('src.resources') }} | ABao
    </x-slot>

    @if ($isConnected)
        <header class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="truncate text-[24px]! leading-7! font-semibold! tracking-tight!">
                        {{ $name ?: __('src.gitlab_app') }}
                    </h1>
                    <x-status-badge :label="__('src.connected')" type="success" />
                </div>
                <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-dim">
                    {{ filled($groupName) ? __('src.gitlab_for_group', ['group' => $groupName]) : __('src.private_gitlab_source') }}
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2 sm:ml-auto">
                @can('view', $gitlab_app)
                    <x-forms.button type="button" wire:click.prevent="testConnection">
                        {{ __('src.test_connection') }}
                    </x-forms.button>
                @endcan
                @can('delete', $gitlab_app)
                    <x-modal-confirmation title="{{ __('src.confirm_gitlab_delete_q') }}" isErrorButton buttonTitle="{{ __('src.delete') }}"
                        submitAction="delete" :actions="[__('src.confirm_gitlab_action')]"
                        confirmationText="{{ data_get($gitlab_app, 'name') }}"
                        confirmationLabel="{{ __('src.confirm_gitlab_label') }}"
                        shortConfirmationLabel="{{ __('src.gitlab_app_name') }}" :confirmWithPassword="false"
                        step2ButtonText="{{ __('src.permanently_delete') }}" />
                @endcan
            </div>
        </header>

        <form wire:submit="submit" class="application-settings-form">
            <x-unsaved-bar action="submit" />

            <x-application.settings-section title="{{ __('src.general') }}"
                description="{{ __('src.general_gl_helper') }}">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.input canGate="update" :canResource="$gitlab_app" id="name" label="{{ __('src.name') }}" />

                    @if (! isCloud())
                        <div class="lg:col-span-2 max-w-xs">
                            <x-forms.checkbox canGate="update" :canResource="$gitlab_app" label="{{ __('src.system_wide') }}"
                                helper="{{ __('src.system_wide_gitlab_helper') }}"
                                instantSave id="isSystemWide" />
                        </div>
                        @if ($isSystemWide)
                            <div class="lg:col-span-2">
                                <x-callout type="warning" title="{{ __('src.shared_every_team') }}">
                                    {{ __('src.system_wide_gitlab_warning') }}
                                </x-callout>
                            </div>
                        @endif
                    @endif
                </div>
            </x-application.settings-section>

            <x-application.settings-section title="{{ __('src.oauth_credentials') }}"
                description="{{ __('src.oauth_credentials_helper') }}">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.input canGate="update" :canResource="$gitlab_app" id="clientId"
                        label="{{ __('src.application_id') }}" />
                    <x-forms.input canGate="update" :canResource="$gitlab_app" id="clientSecretInput"
                        label="{{ __('src.application_secret') }}" type="password"
                        helper="{{ __('src.secret_helper') }}" />
                    <x-forms.input canGate="update" :canResource="$gitlab_app" id="groupName" label="{{ __('src.group_name') }}"
                        helper="{{ __('src.group_name_helper2') }}" />
                </div>
            </x-application.settings-section>

            <x-application.settings-section title="{{ __('src.self_hosted_advanced') }}"
                description="{{ __('src.self_hosted_advanced_helper') }}">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.input canGate="update" :canResource="$gitlab_app" id="htmlUrl"
                        label="{{ __('src.gitlab_url') }}" />
                    <x-forms.input canGate="update" :canResource="$gitlab_app" id="apiUrl" label="{{ __('src.api_url') }}" />
                    <x-forms.input canGate="update" :canResource="$gitlab_app" id="customUser"
                        label="{{ __('src.ssh_user') }}" />
                    <x-forms.input canGate="update" :canResource="$gitlab_app" type="number" id="customPort"
                        label="{{ __('src.ssh_port') }}" />
                    <div class="lg:col-span-2">
                        <x-forms.listbox canGate="update" :canResource="$gitlab_app" id="privateKeyId" label="{{ __('src.ssh_private_key') }}"
                            :options="collect($privateKeys)->map(fn ($key) => [
                                'value' => $key->id,
                                'label' => $key->name,
                            ])->prepend(['value' => null, 'label' => __('src.none')])->values()->all()"
                            :disabled="! auth()->user()->can('update', $gitlab_app)" />
                    </div>
                </div>
            </x-application.settings-section>

            <x-application.settings-section title="{{ __('src.webhook') }}"
                description="{{ __('src.webhook_helper') }}">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <x-forms.input readonly label="{{ __('src.webhook_url') }}"
                            value="{{ rtrim($this->resolvePublicBaseUrl(), '/') }}/webhooks/source/gitlab/events" />
                    </div>
                    <x-forms.input canGate="update" :canResource="$gitlab_app" id="webhookToken"
                        label="{{ __('src.webhook_secret_token') }}" type="password"
                        helper="{{ __('src.webhook_token_helper') }}" />
                </div>
            </x-application.settings-section>
        </form>

        <div x-data="{ search: '' }" class="application-settings-form mt-6">
            <x-application.settings-section title="{{ __('src.resources') }}"
                description="{{ __('src.resources_gl_helper') }}" flush>
                @if ($applications->isEmpty())
                    <x-empty title="{{ __('src.no_resources_source') }}"
                        description="{{ __('src.no_resources_source_helper_gl') }}"
                        icon-name="sources" size="sm" />
                @else
                    <div class="border-b border-neutral-200 p-3 dark:border-white/[0.08]">
                        <div class="relative w-full max-w-sm">
                            <x-reicon name="search"
                                class="pointer-events-none absolute top-1/2 left-2.5 z-10 size-3.5 -translate-y-1/2 text-neutral-400 dark:text-fg-faint" />
                            <input x-model.debounce.150ms="search" type="search" placeholder="{{ __('src.search_resources') }}"
                                class="h-8! w-full rounded-lg! border-neutral-200! bg-white! py-0! pr-3! pl-8! text-[12px]! shadow-none! placeholder:text-neutral-400 focus:border-accent! focus:ring-0! dark:border-white/[0.08]! dark:bg-white/[0.035]! dark:text-fg! dark:placeholder:text-fg-faint">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <div
                            class="grid min-w-[680px] grid-cols-[minmax(10rem,.8fr)_minmax(10rem,.8fr)_minmax(12rem,1fr)_8rem] border-b border-neutral-200 bg-neutral-50 px-4 py-2.5 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-fg-faint">
                            <div>{{ __('src.project') }}</div>
                            <div>{{ __('src.environment') }}</div>
                            <div>{{ __('src.resource') }}</div>
                            <div>{{ __('src.repository') }}</div>
                        </div>
                        @foreach ($applications->sortBy('name', SORT_NATURAL) as $application)
                            @php
                                $projectName = (string) data_get($application, 'environment.project.name');
                                $environmentName = (string) data_get($application, 'environment.name');
                                $resourceName = (string) $application->name;
                                $repoLabel = $application->git_repository.':'.$application->git_branch;
                                $searchValue = strtolower(
                                    $projectName.' '.$environmentName.' '.$resourceName.' '.$repoLabel,
                                );
                            @endphp
                            <a {{ wireNavigate() }}
                                href="{{ route('project.application.configuration', [
                                    'project_uuid' => data_get($application, 'environment.project.uuid'),
                                    'environment_uuid' => data_get($application, 'environment.uuid'),
                                    'application_uuid' => data_get($application, 'uuid'),
                                ]) }}"
                                x-show="search === '' || '{{ addslashes($searchValue) }}'.includes(search.toLowerCase())"
                                class="grid min-h-13 min-w-[680px] grid-cols-[minmax(10rem,.8fr)_minmax(10rem,.8fr)_minmax(12rem,1fr)_8rem] items-center border-b border-neutral-200 px-4 py-2.5 text-[12px] transition-colors last:border-b-0 hover:bg-neutral-50 hover:no-underline dark:border-white/[0.07] dark:hover:bg-white/[0.025]">
                                <span class="truncate text-neutral-500 dark:text-fg-dim">{{ $projectName }}</span>
                                <span class="truncate text-neutral-500 dark:text-fg-dim">{{ $environmentName }}</span>
                                <span class="truncate font-medium text-black dark:text-fg">{{ $resourceName }}</span>
                                <span class="truncate text-neutral-500 dark:text-fg-dim">{{ $repoLabel }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-application.settings-section>
        </div>
    @else
        <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-[24px]! leading-7! font-semibold! tracking-tight!">
                    {{ $name ?: __('src.gitlab_app') }}
                </h1>
                <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-dim">
                    {{ __('src.finish_connect') }}
                </p>
            </div>
            @can('delete', $gitlab_app)
                <div class="shrink-0 sm:ml-auto">
                    <x-modal-confirmation title="{{ __('src.confirm_gitlab_delete_q') }}" isErrorButton buttonTitle="{{ __('src.delete') }}"
                        submitAction="delete" :actions="[__('src.confirm_gitlab_action')]"
                        confirmationText="{{ data_get($gitlab_app, 'name') }}"
                        confirmationLabel="{{ __('src.confirm_gitlab_label') }}"
                        shortConfirmationLabel="{{ __('src.gitlab_app_name') }}" :confirmWithPassword="false"
                        step2ButtonText="{{ __('src.permanently_delete') }}" />
                </div>
            @endcan
        </header>

        <div class="application-settings-form flex flex-col gap-6"
            x-data="{
                webhookEndpoint: $wire.entangle('webhook_endpoint').live,
                useCustomWebhookEndpoint: $wire.entangle('use_custom_webhook_endpoint').live,
                customWebhookEndpoint: $wire.entangle('custom_webhook_endpoint').live,
                redirectPath: '/webhooks/source/gitlab/redirect',
                get redirectUri() {
                    const base = (this.useCustomWebhookEndpoint ? this.customWebhookEndpoint : this.webhookEndpoint) || '';
                    return base ? base.replace(/\/+$/, '') + this.redirectPath : '';
                }
            }">
            <x-application.settings-section title="{{ __('src.step1') }}"
                description="{{ __('src.step1_helper') }}">
                <div class="flex flex-col gap-3 text-[12px] leading-5 text-neutral-600 dark:text-fg-dim">
                    <a href="{{ rtrim($htmlUrl, '/') }}/-/profile/applications" target="_blank"
                        class="inline-flex w-fit items-center gap-1 font-medium text-black underline-offset-2 hover:underline dark:text-fg">
                        {{ rtrim($htmlUrl, '/') }}/-/profile/applications
                        <x-external-link />
                    </a>
                    <ul class="list-inside list-disc space-y-1.5">
                        <li>
                            {!! __('src.set_redirect', ['strong' => '<strong class="text-black dark:text-fg">'.__('src.redirect_uri').'</strong>']) !!}
                            <code class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] dark:bg-white/[0.06]"
                                x-text="redirectUri || @js($redirectUri)">{{ $redirectUri }}</code>
                        </li>
                        <li>
                            {{ __('src.enable_scopes') }} <code class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] dark:bg-white/[0.06]">api</code>,
                            <code class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] dark:bg-white/[0.06]">read_user</code>,
                            <code class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] dark:bg-white/[0.06]">read_repository</code>
                        </li>
                        <li>{!! __('src.uncheck_confidential', ['strong' => '<strong class="text-black dark:text-fg">'.__('src.confidential').'</strong>']) !!}</li>
                    </ul>
                </div>
            </x-application.settings-section>

            <form wire:submit="submit" class="contents">
                <x-application.settings-section title="{{ __('src.step2') }}"
                    description="{{ __('src.step2_helper') }}">
                    <x-slot:actions>
                        <x-forms.button type="submit">{{ __('src.save') }}</x-forms.button>
                    </x-slot:actions>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-forms.input id="name" label="{{ __('src.name') }}" />
                        <x-forms.input id="clientId" label="{{ __('src.application_id') }}" required
                            helper="{{ __('src.app_id_helper') }}" />
                        <x-forms.input id="clientSecretInput" label="{{ __('src.application_secret') }}" type="password"
                            :required="blank($clientSecretInput) && blank(data_get($gitlab_app, 'client_secret'))"
                            helper="{{ __('src.app_secret_helper') }}" />
                        <x-forms.input id="groupName" label="{{ __('src.group_name') }}"
                            helper="{{ __('src.group_name_helper3') }}" />
                    </div>

                    @if (! isCloud() || isDev())
                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div class="lg:col-span-2 text-[12px] text-neutral-500 dark:text-fg-dim">
                                {{ __('src.redirect_note') }}
                            </div>
                            <div class="lg:col-span-2 max-w-md">
                                <x-forms.listbox id="use_custom_webhook_endpoint" label="{{ __('src.webhook_endpoint') }}"
                                    :live="true" :options="[
                                        ['value' => false, 'label' => __('src.use_instance_endpoint')],
                                        ['value' => true, 'label' => __('src.use_custom_endpoint')],
                                    ]"
                                    x-model="useCustomWebhookEndpoint"
                                    helper="{{ __('src.custom_endpoint_helper') }}" />
                            </div>
                            <div class="lg:col-span-2" x-show="!useCustomWebhookEndpoint">
                                <x-forms.listbox id="webhook_endpoint" x-model="webhookEndpoint"
                                    label="{{ __('src.selected_endpoint') }}"
                                    helper="{{ __('src.selected_endpoint_helper') }}"
                                    :options="collect([$fqdn, $ipv4, $ipv6, config('app.url')])
                                        ->filter()->unique()->map(fn ($endpoint) => [
                                            'value' => $endpoint,
                                            'label' => __('src.use_endpoint', ['endpoint' => $endpoint]),
                                        ])->values()->all()" />
                            </div>
                            <div class="lg:col-span-2" x-cloak x-show="useCustomWebhookEndpoint">
                                <x-forms.input x-model="customWebhookEndpoint" id="custom_webhook_endpoint"
                                    type="url" label="{{ __('src.custom_endpoint') }}"
                                    placeholder="{{ __('src.custom_endpoint_placeholder') }}"
                                    helper="{{ __('src.custom_endpoint_gl_helper') }}" />
                            </div>
                        </div>
                    @endif

                    <div class="mt-4" x-data="{ open: false }">
                        <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between rounded-lg border border-neutral-200 px-3 py-2.5 text-left text-[12px] font-medium text-neutral-700 transition-colors hover:bg-neutral-50 dark:border-white/[0.08] dark:text-fg-dim dark:hover:bg-white/[0.03]">
                            {{ __('src.advanced_self_hosted') }}
                            <svg class="size-3.5 transition-transform" :class="{ 'rotate-180': open }"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div x-cloak x-show="open" x-collapse.duration.200ms
                            class="mt-3 grid gap-4 rounded-lg border border-neutral-200 p-3 lg:grid-cols-2 dark:border-white/[0.08]">
                            <x-forms.input id="htmlUrl" label="{{ __('src.gitlab_url') }}"
                                helper="{{ __('src.gitlab_url_change_helper') }}" />
                            <x-forms.input id="apiUrl" label="{{ __('src.api_url') }}"
                                helper="{{ __('src.api_url_v4_helper') }}" />
                            <x-forms.input id="customUser" label="{{ __('src.ssh_user') }}" />
                            <x-forms.input type="number" id="customPort" label="{{ __('src.ssh_port') }}" />
                            @if (! isCloud())
                                <div class="max-w-xs lg:col-span-2">
                                    <x-forms.checkbox label="{{ __('src.system_wide') }}" id="isSystemWide"
                                        helper="{{ __('src.system_wide_gitlab_helper') }}" />
                                </div>
                            @endif
                        </div>
                    </div>
                </x-application.settings-section>
            </form>

            @if ($clientId)
                <x-application.settings-section title="{{ __('src.step3') }}"
                    description="{{ __('src.step3_helper') }}">
                    <a href="{{ $this->getOAuthUrl() }}" wire:key="oauth-url-{{ md5((string) $redirectUri) }}"
                        class="button button-highlighted">
                        {{ __('src.connect_to_gitlab') }}
                        <x-external-link />
                    </a>
                </x-application.settings-section>
            @endif
        </div>
    @endif
</div>
