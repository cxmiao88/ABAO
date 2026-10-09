<div>
    <x-slot:title>
        {{ $github_app->name ?: __('src.github_app') }} | {{ __('src.resources') }} | ABao
    </x-slot>

    @if (data_get($github_app, 'app_id'))
        @php
            $githubAppRouteParameters = ['github_app_uuid' => $github_app->uuid];
            $showSettingsSidebar = in_array($activeTab, ['general', 'permissions', 'resources', 'danger'], true);
            $settingsMenuItems = [
                [
                    'label' => __('src.general'),
                    'route' => 'source.github.show',
                    'active' => $activeTab === 'general',
                    'icon' => 'settings',
                ],
                [
                    'label' => __('src.permissions'),
                    'route' => 'source.github.permissions',
                    'active' => $activeTab === 'permissions',
                    'icon' => 'keys',
                ],
                [
                    'label' => __('src.resources'),
                    'route' => 'source.github.resources',
                    'active' => $activeTab === 'resources',
                    'icon' => 'grid',
                ],
                [
                    'label' => __('src.danger_zone'),
                    'route' => 'source.github.danger',
                    'active' => $activeTab === 'danger',
                    'icon' => 'shield-alert',
                ],
            ];
        @endphp

        <x-dashboard.navbar section="source" :parameters="$githubAppRouteParameters"
            :title="$name ?: __('src.github_app')"
            :subtitle="filled($organization) ? __('src.github_for_org', ['org' => $organization]) : __('src.private_github_source')"
            :mobileTitleOnly="true" />

        @if ($showSettingsSidebar)
            <section class="application-settings-workspace mt-4 w-full max-w-none lg:mt-0">
                <div class="grid min-w-0 gap-8 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
                    <aside class="application-settings-navigation min-w-0 xl:self-start">
                        <nav aria-label="{{ __('src.github_app_settings') }}"
                            class="grid grid-cols-2 gap-0.5 border-y border-neutral-200 py-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-1 xl:border-y-0 xl:py-0 dark:border-white/[0.06]">
                            <div class="nav-section hidden xl:block">{{ __('src.settings') }}</div>
                            @foreach ($settingsMenuItems as $menuItem)
                                <a wire:key="github-app-settings-{{ str($menuItem['label'])->slug() }}"
                                    @class([
                                        'menu-item',
                                        'menu-item-active' => $menuItem['active'],
                                    ])
                                    {{ wireNavigate() }}
                                    href="{{ route($menuItem['route'], $githubAppRouteParameters) }}">
                                    <x-reicon :name="$menuItem['icon']" class="menu-item-icon" />
                                    <span class="menu-item-label">{{ $menuItem['label'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </aside>

                    <div class="min-w-0">
                        @if (!data_get($github_app, 'installation_id') && $activeTab === 'general')
                            <div class="application-settings-form">
                                <x-application.settings-section title="{{ __('src.complete_installation') }}"
                                    description="{{ __('src.complete_installation_helper') }}">
                                    <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-warning/10 text-warning">
                                                <x-reicon name="alert-triangle" class="size-4" />
                                            </div>
                                            <p class="max-w-xl text-[12px] leading-5 text-neutral-500 dark:text-fg-dim">
                                                {{ __('src.repo_access_not_installed') }}
                                            </p>
                                        </div>
                                        <a class="button shrink-0 button-highlighted"
                                            href="{{ getInstallationPath($github_app) }}">
                                            {{ __('src.install_repos') }}
                                            <x-external-link />
                                        </a>
                                    </div>
                                </x-application.settings-section>
                            </div>
                        @elseif ($activeTab === 'general')
                            @php
                                $privateKeyOptions = collect([
                                    blank($github_app->private_key_id)
                                        ? ['value' => 0, 'label' => __('src.select_private_key')]
                                        : null,
                                    ...$privateKeys->map(fn ($privateKey) => [
                                        'value' => $privateKey->id,
                                        'label' => $privateKey->name,
                                    ])->all(),
                                ])->filter()->values()->all();
                            @endphp

                            <form wire:submit="submit" class="application-settings-form">
                                <x-unsaved-bar action="submit" />
                                <x-application.settings-section title="{{ __('src.general') }}"
                                    description="{{ __('src.general_gh_helper') }}">
                                    <x-slot:actions>
                                        @if ($isConnected)
                                            <x-forms.button type="button" canGate="view" :canResource="$github_app"
                                                wire:click.prevent="testConnection">
                                                {{ __('src.test_connection') }}
                                            </x-forms.button>
                                        @endif
                                        <x-forms.button type="button" canGate="update" :canResource="$github_app"
                                            wire:click.prevent="updateGithubAppName">
                                            <x-reicon name="refresh" class="size-3.5" />
                                            {{ __('src.sync_name') }}
                                        </x-forms.button>
                                        @can('update', $github_app)
                                            <a href="{{ $this->getGithubAppNameUpdatePath() }}" class="button">
                                                {{ __('src.rename') }}
                                                <x-external-link />
                                            </a>
                                            <a href="{{ getInstallationPath($github_app) }}" class="button">
                                                {{ __('src.repositories') }}
                                                <x-external-link />
                                            </a>
                                        @endcan
                                    </x-slot:actions>

                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <x-forms.input canGate="update" :canResource="$github_app" id="name" label="{{ __('src.app_name') }}" />
                                        <x-forms.input canGate="update" :canResource="$github_app" id="organization"
                                            label="{{ __('src.organization') }}" placeholder="{{ __('src.personal_placeholder') }}" />

                                        @if (!isCloud())
                                            <div class="lg:col-span-2">
                                                <x-forms.listbox canGate="update" :canResource="$github_app" id="isSystemWide" label="{{ __('src.availability') }}" :options="[
                                                    ['value' => false, 'label' => __('src.only_this_team')],
                                                    ['value' => true, 'label' => __('src.every_team')],
                                                ]"
                                                    helper="{{ __('src.system_wide_helper') }}"
                                                    :disabled="!auth()->user()->can('update', $github_app)" />
                                            </div>
                                            @if ($isSystemWide)
                                                <div class="lg:col-span-2">
                                                    <x-callout type="warning" title="{{ __('src.shared_every_team') }}">
                                                        {{ __('src.team_specific_advice') }}
                                                    </x-callout>
                                                </div>
                                            @endif
                                        @endif

                                        <x-forms.input canGate="update" :canResource="$github_app" id="htmlUrl"
                                            label="{{ __('src.html_url') }}" />
                                        <x-forms.input canGate="update" :canResource="$github_app" id="apiUrl"
                                            label="{{ __('src.api_url') }}" />
                                        <x-forms.input canGate="update" :canResource="$github_app" id="customUser"
                                            label="{{ __('src.user') }}" required />
                                        <x-forms.input canGate="update" :canResource="$github_app" type="number"
                                            id="customPort" label="{{ __('src.port') }}" required />
                                        <x-forms.input canGate="update" :canResource="$github_app" type="number" id="appId"
                                            label="{{ __('src.app_id') }}" required />
                                        <x-forms.input canGate="update" :canResource="$github_app" type="number"
                                            id="installationId" label="{{ __('src.installation_id') }}" required />
                                        <x-forms.input canGate="update" :canResource="$github_app" id="clientId"
                                            label="{{ __('src.client_id') }}" type="password" required />
                                        <x-forms.input canGate="update" :canResource="$github_app" id="clientSecret"
                                            label="{{ __('src.client_secret') }}" type="password" required />
                                        <x-forms.input canGate="update" :canResource="$github_app" id="webhookSecret"
                                            label="{{ __('src.webhook_secret') }}" type="password" required />
                                        <x-forms.listbox canGate="update" :canResource="$github_app" id="privateKeyId" label="{{ __('src.private_key') }}" required
                                            :options="$privateKeyOptions" :disabled="!auth()->user()->can('update', $github_app)" />
                                    </div>
                                </x-application.settings-section>
                            </form>
                        @elseif ($activeTab === 'danger')
                            <div class="application-settings-form">
                                <x-application.settings-section id="github-app-danger-section" title="{{ __('src.danger_zone') }}"
                                    helper="{{ __('src.danger_helper') }}">
                                    <x-danger-zone title="{{ __('src.delete_github_app') }}">
                                                <p>
                                                    {{ __('src.delete_github_app_body', ['name' => $name ?: __('src.this_github_app')]) }}
                                                </p>
                                                <ul class="space-y-1 text-xs">
                                                    <li>• {{ __('src.delete_note1') }}</li>
                                                    <li>• {{ __('src.delete_note2') }}</li>
                                                    <li>• {{ __('src.delete_note3') }}</li>
                                                </ul>
                                            <x-slot:action>
                                                @can('delete', $github_app)
                                                    <x-modal-confirmation title="{{ __('src.confirm_delete_q') }}" isErrorButton
                                                        buttonTitle="{{ __('src.delete') }}" submitAction="delete"
                                                        :actions="[__('src.confirm_delete_action')]"
                                                        confirmationText="{{ data_get($github_app, 'name') }}"
                                                        confirmationLabel="{{ __('src.confirm_label') }}"
                                                        shortConfirmationLabel="{{ __('src.github_app_name') }}" :confirmWithPassword="false"
                                                        step2ButtonText="{{ __('src.permanently_delete') }}" />
                                                @else
                                                    <x-forms.button isError disabled canGate="delete" :canResource="$github_app"
                                                        tooltip="{{ __('src.no_delete_perm') }}">
                                                        {{ __('src.delete') }}
                                                    </x-forms.button>
                                                @endcan
                                            </x-slot:action>
                                    </x-danger-zone>

                                    @cannot('delete', $github_app)
                                        <div class="mt-4">
                                            <x-callout type="danger" title="{{ __('src.insufficient_permissions') }}">
                                                {{ __('src.no_delete_perm_helper') }}
                                            </x-callout>
                                        </div>
                                    @endcannot
                                </x-application.settings-section>
                            </div>
                        @elseif ($activeTab === 'permissions')
                            @include('livewire.source.github.permissions')
                        @elseif ($activeTab === 'resources')
                            @include('livewire.source.github.resources')
                        @endif
                    </div>
                </div>
            </section>
        @endif
    @else
        <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-[24px]! leading-7! font-semibold! tracking-tight!">
                    {{ $name ?: __('src.github_app') }}
                </h1>
                <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-dim">
                    {{ __('src.finish_register') }}
                </p>
            </div>
            @can('delete', $github_app)
                <div class="shrink-0">
                    <x-modal-confirmation title="{{ __('src.confirm_delete_q') }}" isErrorButton
                        buttonTitle="{{ __('src.delete') }}" submitAction="delete"
                        :actions="[__('src.confirm_delete_action')]"
                        confirmationText="{{ data_get($github_app, 'name') }}"
                        confirmationLabel="{{ __('src.confirm_label') }}"
                        shortConfirmationLabel="{{ __('src.github_app_name') }}" :confirmWithPassword="false"
                        step2ButtonText="{{ __('src.permanently_delete') }}" />
                </div>
            @endcan
        </header>

        @can('create', $github_app)
            @php
                $endpointOptions = collect([
                    $fqdn ? ['value' => $fqdn, 'label' => __('src.use_endpoint', ['endpoint' => $fqdn])] : null,
                    $ipv4 ? ['value' => $ipv4, 'label' => __('src.use_endpoint', ['endpoint' => $ipv4])] : null,
                    $ipv6 ? ['value' => $ipv6, 'label' => __('src.use_endpoint', ['endpoint' => $ipv6])] : null,
                    config('app.url')
                        ? ['value' => config('app.url'), 'label' => __('src.use_endpoint', ['endpoint' => config('app.url')])]
                        : null,
                ])->filter()->values()->all();
            @endphp

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="application-settings-form flex"
                    x-data="{
                        webhookEndpoint: $wire.entangle('webhook_endpoint').live,
                        useCustomWebhookEndpoint: $wire.entangle('use_custom_webhook_endpoint').live,
                        customWebhookEndpoint: $wire.entangle('custom_webhook_endpoint').live,
                    }">
                    <x-application.settings-section title="{{ __('src.automated_installation') }}" class="[&>.application-settings-section-body]:flex [&>.application-settings-section-body]:flex-1 [&>.application-settings-section-body]:flex-col"
                        description="{{ __('src.automated_helper') }}">
                        <x-slot:actions>
                            <x-status-badge :label="__('src.recommended')" type="success" />
                        </x-slot:actions>

                        <div class="flex min-h-[24rem] flex-1 flex-col gap-4">
                            @if (!isCloud() || isDev())
                                <x-forms.listbox id="use_custom_webhook_endpoint" label="{{ __('src.webhook_endpoint') }}"
                                    :live="true" :options="[
                                        ['value' => false, 'label' => __('src.use_instance_endpoint')],
                                        ['value' => true, 'label' => __('src.use_custom_endpoint')],
                                    ]"
                                    x-model="useCustomWebhookEndpoint"
                                    helper="{{ __('src.custom_endpoint_helper') }}" />
                                <div x-show="!useCustomWebhookEndpoint">
                                    <x-forms.listbox id="webhook_endpoint" label="{{ __('src.instance_endpoint') }}"
                                        :options="$endpointOptions" x-model="webhookEndpoint" />
                                </div>
                                <div x-cloak x-show="useCustomWebhookEndpoint">
                                    <x-forms.input canGate="create" :canResource="$github_app"
                                        x-model="customWebhookEndpoint" id="custom_webhook_endpoint" type="url"
                                        label="{{ __('src.custom_endpoint') }}" placeholder="{{ __('src.custom_endpoint_placeholder') }}"
                                        helper="{{ __('src.no_webhooks_suffix') }}" />
                                </div>
                            @else
                                <p class="text-[12px] leading-5 text-neutral-500 dark:text-fg-dim">
                                    {{ __('src.register_before_use') }}
                                </p>
                            @endif

                            <div
                                class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-[12px] leading-5 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-fg-dim">
                                <p class="font-medium text-black dark:text-fg">{{ __('src.mandatory_permissions') }}</p>
                                <p class="mt-1">{{ __('src.mandatory_perm_detail') }}</p>
                            </div>

                            <x-forms.listbox id="preview_deployment_permissions"
                                label="{{ __('src.preview_deployment_access') }}" :options="[
                                    ['value' => false, 'label' => __('src.do_not_update_pr')],
                                    ['value' => true, 'label' => __('src.read_update_pr')],
                                ]"
                                helper="{{ __('src.preview_access_helper') }}" />

                            <x-forms.listbox id="github_runners" label="{{ __('src.gh_actions_runners') }}"
                                :disabled="blank($github_app->organization)"
                                :options="[
                                    ['value' => false, 'label' => __('src.do_not_run_jobs')],
                                    ['value' => true, 'label' => __('src.run_jobs_on_build')],
                                ]"
                                :helper="filled($github_app->organization)
                                    ? __('src.runners_org_only_helper')
                                    : __('src.runners_org_needed')" />

                            <button type="button"
                                class="button mt-auto w-full justify-center button-highlighted"
                                x-on:click.prevent="createGithubApp(webhookEndpoint, useCustomWebhookEndpoint, customWebhookEndpoint, $wire.preview_deployment_permissions, {{ Illuminate\Support\Js::from($administration) }}, $wire.github_runners)">
                                {{ __('src.register_with_github') }}
                            </button>
                        </div>
                    </x-application.settings-section>
                </div>

                <div class="application-settings-form flex">
                    <x-application.settings-section title="{{ __('src.manual_installation') }}" class="[&>.application-settings-section-body]:flex [&>.application-settings-section-body]:flex-1 [&>.application-settings-section-body]:flex-col"
                        description="{{ __('src.manual_helper') }}">
                        <x-slot:actions>
                            <x-status-badge :label="__('src.advanced')" type="neutral" />
                        </x-slot:actions>

                        <div class="flex min-h-[24rem] flex-1 flex-col">
                            <div
                                class="flex size-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg-dim">
                                <x-reicon name="settings" class="size-5" />
                            </div>
                            <p class="mt-4 max-w-md text-[12px] leading-5 text-neutral-500 dark:text-fg-dim">
                                {{ __('src.manual_body') }}
                            </p>
                            <button type="button" class="button mt-auto w-fit"
                                wire:click.prevent="createGithubAppManually">
                                {{ __('src.continue_manually') }}
                                <x-reicon name="arrow-right" class="size-3.5" />
                            </button>
                        </div>
                    </x-application.settings-section>
                </div>
            </div>
        @else
            <x-callout type="danger" title="{{ __('src.insufficient_permissions') }}">
                {{ __('src.github_no_perm_create') }}
            </x-callout>
        @endcan

        <script>
            function createGithubApp(webhook_endpoint, use_custom_webhook_endpoint, custom_webhook_endpoint,
                preview_deployment_permissions, administration, github_runners) {
                const {
                    organization,
                    html_url
                } = @js($github_app->only(['organization', 'html_url']));
                const selectedEndpoint = webhook_endpoint ? webhook_endpoint.trim() : '';
                const customEndpoint = custom_webhook_endpoint ? custom_webhook_endpoint.trim() : '';
                if (use_custom_webhook_endpoint && !customEndpoint) {
                    window.toast('Error', { type: 'danger', description: '{{ __("src.toast_webhook_endpoint") }}' });
                    return;
                }
                if (!use_custom_webhook_endpoint && !selectedEndpoint) {
                    window.toast('Error', { type: 'danger', description: '{{ __("src.toast_endpoint") }}' });
                    return;
                }
                let baseUrl = (use_custom_webhook_endpoint ? customEndpoint : selectedEndpoint).replace(/\/+$/, '');
                const name = @js($name);
                const manifestState = @js($manifestState);
                const isDev = @js(config('app.env')) === 'local';
                const devWebhook = @js(config('constants.webhooks.dev_webhook'));
                if (isDev && devWebhook) {
                    baseUrl = devWebhook;
                }
                const webhookBaseUrl = `${baseUrl}/webhooks`;
                const organizationPath = organization ? encodeURIComponent(organization.replace(/^\/+|\/+$/g, '')) : '';
                const path = organizationPath ? `organizations/${organizationPath}/settings/apps/new` : 'settings/apps/new';
                const default_permissions = {
                    contents: 'read',
                    metadata: 'read',
                    emails: 'read',
                    administration: 'read'
                };
                const default_events = ['push'];
                if (preview_deployment_permissions) {
                    default_permissions.pull_requests = 'write';
                    default_events.push('pull_request');
                }
                if (administration) {
                    default_permissions.administration = 'write';
                }
                if (github_runners && organization) {
                    default_permissions.organization_self_hosted_runners = 'write';
                    default_permissions.actions = 'read';
                    default_events.push('workflow_job');
                }

                const data = {
                    name,
                    url: baseUrl,
                    hook_attributes: {
                        url: `${webhookBaseUrl}/source/github/events`,
                        active: true,
                    },
                    redirect_url: `${webhookBaseUrl}/source/github/redirect`,
                    callback_urls: [`${baseUrl}/login/github/app`],
                    public: false,
                    request_oauth_on_install: false,
                    setup_url: `${webhookBaseUrl}/source/github/install`,
                    setup_on_update: true,
                    default_permissions,
                    default_events
                };
                const form = document.createElement('form');
                form.setAttribute('method', 'post');
                form.setAttribute('action', `${html_url}/${path}?state=${manifestState}`);
                const input = document.createElement('input');
                input.setAttribute('id', 'manifest');
                input.setAttribute('name', 'manifest');
                input.setAttribute('type', 'hidden');
                input.setAttribute('value', JSON.stringify(data));
                form.appendChild(input);
                document.getElementsByTagName('body')[0].appendChild(form);
                form.submit();
            }
        </script>
    @endif
</div>
