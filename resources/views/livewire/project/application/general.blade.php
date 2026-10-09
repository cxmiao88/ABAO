<div x-data="{
    initLoadingCompose: $wire.entangle('initLoadingCompose'),
    canUpdate: @js(auth()->user()->can('update', $application)),
    shouldDisable() {
        return this.initLoadingCompose || !this.canUpdate;
    }
}">
    <form wire:submit='submit' class="application-settings-form flex flex-col">
        <x-unsaved-bar action="submit"
            targets="name,description,buildPack,staticImage,baseDirectory,dockerComposeLocation,dockerComposeCustomBuildCommand,dockerComposeCustomStartCommand,watchPaths,dockerfileLocation,dockerfileTargetBuild,publishDirectory,installCommand,buildCommand,startCommand,customNginxConfiguration,dockerfile,dockerRegistryImageName,dockerRegistryImageTag,portsExposes,portsMappings,customNetworkAliases,customDockerRunOptions,httpBasicAuthUsername,httpBasicAuthPassword,preDeploymentCommand,preDeploymentCommandContainer,postDeploymentCommand,postDeploymentCommandContainer,isContainerLabelReadonlyEnabled,isContainerLabelEscapeEnabled,customLabels" />
        <div class="application-settings-grid flex flex-col gap-6">
            <x-application.settings-section id="application-details-section" title="{{ __('application.gen_details_title') }}" helper="{{ __('application.gen_details_helper') }}" class="application-details-card">
            @if ($buildPack === 'dockercompose')
                <x-slot:actions>
                    <x-forms.button canGate="update" :canResource="$application" wire:target='initLoadingCompose'
                        x-on:click="$wire.dispatch('loadCompose', false)">
                        {{ $application->docker_compose_raw ? __('application.gen_reload_compose') : __('application.gen_load_compose') }}
                    </x-forms.button>
                </x-slot:actions>
            @endif
            <div class="grid gap-4">
                <x-forms.input x-bind:disabled="shouldDisable()" id="name" label="{{ __('application.gen_name') }}" required />
                <x-forms.input x-bind:disabled="shouldDisable()" id="description" label="{{ __('application.gen_description') }}" />
            </div>

            </x-application.settings-section>

            <x-application.settings-section id="access-section" title="{{ __('application.gen_access_title') }}" helper="{{ __('application.gen_access_helper') }}">
            <section id="public-access-section" @class([
                'border-b border-neutral-200 pb-5 dark:border-white/[0.07]' => $buildPack !== 'dockercompose',
            ])>
            <h3 class="mb-3 text-sm font-semibold text-black dark:text-fg">{{ __('application.gen_public_access') }}</h3>
            @php
                $domainCount = 0;
                $primaryDomain = null;
                if ($buildPack === 'dockercompose') {
                    $composeDomains = $application->docker_compose_domains
                        ? json_decode($application->docker_compose_domains, true)
                        : null;
                    if (is_array($composeDomains)) {
                        foreach ($composeDomains as $serviceDomain) {
                            $domainString = data_get($serviceDomain, 'domain');
                            if (filled($domainString)) {
                                $domainCount += countDomains($domainString);
                                $primaryDomain ??= collect(explode(',', $domainString))
                                    ->map(fn ($domain) => trim($domain))
                                    ->first(fn ($domain) => filled($domain));
                            }
                        }
                    }
                } elseif (filled($fqdn)) {
                    $domainCount = countDomains($fqdn);
                    $primaryDomain = collect(explode(',', $fqdn))
                        ->map(fn ($domain) => trim($domain))
                        ->first(fn ($domain) => filled($domain));
                }
                $additionalDomainCount = max(0, $domainCount - 1);
            @endphp
            @php
                $applicationDomainsUrl = route('project.application.domains', [
                    'project_uuid' => $application->environment->project->uuid,
                    'environment_uuid' => $application->environment->uuid,
                    'application_uuid' => $application->uuid,
                ]);
            @endphp
            <div class="group relative flex items-center gap-3 rounded-lg border border-neutral-200 bg-neutral-50/60 px-4 py-3 transition-colors hover:bg-neutral-100 focus-within:ring-2 focus-within:ring-coollabs/40 dark:border-white/[0.07] dark:bg-white/[0.05] dark:hover:bg-white/[0.08] dark:focus-within:ring-warning/40">
                <a class="flex min-w-0 flex-1 items-center gap-3 after:absolute after:inset-0 after:content-[''] focus-visible:outline-none"
                    aria-label="{{ $domainCount > 0 ? __('application.gen_manage_domains') : __('application.gen_add_domain_aria') }}"
                    href="{{ $applicationDomainsUrl }}" {{ wireNavigate() }}>
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-neutral-200/70 text-neutral-600 dark:bg-white/[0.07] dark:text-fg-dim">
                        <x-reicon name="globe" class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-black dark:text-fg">
                            @if ($primaryDomain)
                                <span class="block truncate">{{ $primaryDomain }}</span>
                            @else
                                {{ __('application.gen_no_domain') }}
                            @endif
                        </p>
                        <p class="text-xs text-neutral-500 dark:text-fg-dim">
                            @if ($additionalDomainCount > 0)
                                +{{ $additionalDomainCount }} {{ __('application.gen_more_domains') }}
                            @elseif ($domainCount === 0)
                                {{ __('application.gen_make_available') }}
                            @else
                                {{ __('application.gen_manage_dns') }}
                            @endif
                        </p>
                    </div>
                </a>
                <a class="button relative z-10 ml-auto shrink-0" aria-label="{{ $domainCount > 0 ? __('application.gen_manage_domains') : __('application.gen_add_domain_aria') }}"
                    href="{{ $applicationDomainsUrl }}" {{ wireNavigate() }}>
                    {{ $domainCount > 0 ? __('application.gen_manage_domains_btn') : __('application.gen_add_domain_btn') }}
                    <x-reicon name="arrow-right" class="size-4" />
                </a>
            </div>
            </section>

            @if ($buildPack !== 'dockercompose')
                <livewire:project.application.internal-access :application="$application"
                    :key="'application-internal-access-'.$application->id" />
            @endif
            </x-application.settings-section>

            <x-application.settings-section id="build-pipeline-section" title="{{ __('application.gen_build_title') }}" helper="{{ __('application.gen_build_helper') }}">
            @if (!$application->dockerfile && $application->build_pack !== 'dockerimage')
                <div class="application-build-pack-options mb-5 border-b border-neutral-200 pb-5 dark:border-white/[0.07]">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-forms.listbox id="buildPack" label="{{ __('application.gen_build_strategy') }}" live :options="[
                            ['value' => 'railpack', 'label' => 'Railpack'],
                            ['value' => 'nixpacks', 'label' => 'Nixpacks'],
                            ['value' => 'static', 'label' => __('application.gen_opt_static')],
                            ['value' => 'dockerfile', 'label' => 'Dockerfile'],
                            ['value' => 'dockercompose', 'label' => 'Compose'],
                        ]" x-bind:disabled="shouldDisable()" />
                        @if ($isStatic || $buildPack === 'static')
                            <x-forms.listbox id="staticImage" label="{{ __('application.gen_web_server') }}" required :options="[
                                ['value' => 'nginx:alpine', 'label' => 'nginx:alpine'],
                                ['value' => 'apache:alpine', 'label' => 'apache:alpine', 'disabled' => true],
                            ]" x-bind:disabled="!canUpdate" />
                        @endif
                    </div>
                </div>
            @endif
            @if ($application->could_set_build_commands() || ($isStatic && $buildPack !== 'static'))
                <div class="mb-5 w-full border-b border-neutral-200 pb-5 dark:border-white/[0.07]">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-forms.listbox id="siteType" label="{{ __('application.gen_site_type') }}" onChange="setSiteType" :options="[
                            ['value' => 'dynamic', 'label' => __('application.gen_opt_dynamic')],
                            ['value' => 'static', 'label' => __('application.gen_opt_static')],
                            ['value' => 'spa', 'label' => __('application.gen_opt_spa')],
                        ]"
                            helper="{{ __('application.gen_site_type_helper') }}"
                            x-bind:disabled="!canUpdate" />
                    </div>
                </div>
            @endif
            <div class="flex flex-col gap-5">
                @if ($application->build_pack === 'dockerimage')
                    <p class="text-sm text-neutral-500 dark:text-fg-dim">{{ __('application.gen_nothing_to_build') }}</p>
                @else
                    <div class="flex flex-col gap-5">
                        @if ($buildPack === 'dockercompose')
                            <div class="flex flex-col gap-2">
                                <div x-data="{
                                    baseDir: @entangle('baseDirectory'),
                                    composeLocation: @entangle('dockerComposeLocation'),
                                    normalizePath(path) {
                                        if (!path || path.trim() === '') return '/';
                                        path = path.trim();
                                        path = path.replace(/\/+$/, '');
                                        if (!path.startsWith('/')) {
                                            path = '/' + path;
                                        }
                                        return path;
                                    },
                                    normalizeBaseDir() {
                                        this.baseDir = this.normalizePath(this.baseDir);
                                    },
                                    normalizeComposeLocation() {
                                        this.composeLocation = this.normalizePath(this.composeLocation);
                                    }
                                }" class="grid gap-4 lg:grid-cols-2">
                                    <x-forms.input x-bind:disabled="shouldDisable()" placeholder="/"
                                        label="{{ __('application.gen_base_dir') }}"
                                        helper="{{ __('application.gen_base_dir_helper') }}" x-model="baseDir"
                                        @blur="normalizeBaseDir()" />
                                    <x-forms.input x-bind:disabled="shouldDisable()"
                                        placeholder="/docker-compose.yaml"
                                        label="{{ __('application.gen_compose_location') }}"
                                        helper="{{ __('application.gen_compose_location_helper', ['path' => Str::start($baseDirectory . $dockerComposeLocation, '/')]) }}"
                                        x-model="composeLocation" @blur="normalizeComposeLocation()" />
                                </div>
                                <div class="w-full sm:w-96">
                                    <x-forms.checkbox instantSave id="isPreserveRepositoryEnabled"
                                        label="{{ __('application.gen_preserve_repo') }}"
                                        helper="{{ __('application.gen_preserve_repo_helper') }}"
                                        x-bind:disabled="shouldDisable()" />
                                </div>
                                <div class="grid gap-4 pt-4">
                                        <div class="grid gap-4 lg:grid-cols-2">
                                            <x-forms.input x-bind:disabled="shouldDisable()"
                                                placeholder="docker compose build" id="dockerComposeCustomBuildCommand"
                                                helper="{{ __('application.gen_custom_cmd_helper_build') }}"
                                                label="{{ __('application.gen_custom_build_command') }}" />
                                            <x-forms.input x-bind:disabled="shouldDisable()"
                                                placeholder="docker compose up -d" id="dockerComposeCustomStartCommand"
                                                helper="{{ __('application.gen_custom_cmd_helper_start') }}"
                                                label="{{ __('application.gen_custom_start_command') }}" />
                                        </div>
                                        @if ($this->dockerComposeCustomBuildCommand)
                                            <div wire:key="docker-compose-build-preview">
                                                <x-forms.input readonly value="{{ $this->dockerComposeBuildCommandPreview }}"
                                                    label="{{ __('application.gen_final_build_preview') }}"
                                                    helper="{{ __('application.gen_final_preview_helper') }}" />
                                            </div>
                                        @endif
                                        @if ($this->dockerComposeCustomStartCommand)
                                            <div wire:key="docker-compose-start-preview">
                                                <x-forms.input readonly value="{{ $this->dockerComposeStartCommandPreview }}"
                                                    label="{{ __('application.gen_final_start_preview') }}"
                                                    helper="{{ __('application.gen_final_preview_helper') }}" />
                                            </div>
                                        @endif
                                </div>
                                @if ($this->application->is_github_based() && !$this->application->is_public_repository())
                                    <div class="pt-4">
                                        <x-forms.textarea
                                            helper="{{ __('application.gen_watch_paths_helper') }}"
                                            placeholder="services/api/**" id="watchPaths" label="{{ __('application.gen_watch_paths') }}"
                                            x-bind:disabled="shouldDisable()" />
                                    </div>
                                @endif
                            </div>
                        @else
                            <div x-data="{
                                baseDir: @entangle('baseDirectory'),
                                dockerfileLocation: @entangle('dockerfileLocation'),
                                normalizePath(path) {
                                    if (!path || path.trim() === '') return '/';
                                    path = path.trim();
                                    path = path.replace(/\/+$/, '');
                                    if (!path.startsWith('/')) {
                                        path = '/' + path;
                                    }
                                    return path;
                                },
                                normalizeBaseDir() {
                                    this.baseDir = this.normalizePath(this.baseDir);
                                },
                                normalizeDockerfileLocation() {
                                    this.dockerfileLocation = this.normalizePath(this.dockerfileLocation);
                                }
                            }" class="grid gap-4 lg:grid-cols-2">
                                <x-forms.input placeholder="/"
                                    label="{{ __('application.gen_base_dir') }}" helper="{{ __('application.gen_base_dir_helper') }}"
                                    x-bind:disabled="!canUpdate" x-model="baseDir" @blur="normalizeBaseDir()" />
                                @if ($buildPack === 'dockerfile' && !$application->dockerfile)
                                    <x-forms.input placeholder="/Dockerfile"
                                        label="{{ __('application.gen_dockerfile_location') }}"
                                        helper="{{ __('application.gen_dockerfile_location_helper', ['path' => Str::start($application->base_directory . $application->dockerfile_location, '/')]) }}"
                                        x-bind:disabled="!canUpdate" x-model="dockerfileLocation"
                                        @blur="normalizeDockerfileLocation()" />
                                @endif

                                @if ($buildPack === 'dockerfile')
                                    <x-forms.input id="dockerfileTargetBuild" label="{{ __('application.gen_docker_target') }}"
                                        helper="{{ __('application.gen_docker_target_helper') }}"
                                        x-bind:disabled="!canUpdate" />
                                @endif
                                @if ($application->could_set_build_commands())
                                    @if ($application->settings->is_static)
                                        <x-forms.input placeholder="/dist" id="publishDirectory"
                                            label="{{ __('application.gen_publish_dir') }}" required x-bind:disabled="!canUpdate" />
                                    @else
                                        <x-forms.input placeholder="/" id="publishDirectory"
                                            label="{{ __('application.gen_publish_dir') }}" x-bind:disabled="!canUpdate" />
                                    @endif
                                @endif

                            </div>
                            @if ($this->application->is_github_based() && !$this->application->is_public_repository())
                                <div class="pb-4">
                                    <x-forms.textarea
                                        helper="{{ __('application.gen_watch_paths_helper') }}"
                                        placeholder="src/pages/**" id="watchPaths" label="{{ __('application.gen_watch_paths') }}"
                                        x-bind:disabled="!canUpdate" />
                                </div>
                            @endif
                            @if ($application->could_set_build_commands() && ($buildPack === 'nixpacks' || $buildPack === 'railpack'))
                                <div class="grid gap-4 lg:grid-cols-3">
                                    <x-forms.input helper="{{ __('application.gen_command_helper', ['file' => $buildPack === 'railpack' ? 'railpack.json' : 'nixpacks.toml']) }}"
                                        id="installCommand" label="{{ __('application.gen_install_command') }}" x-bind:disabled="!canUpdate" />
                                    <x-forms.input helper="{{ __('application.gen_command_helper', ['file' => $buildPack === 'railpack' ? 'railpack.json' : 'nixpacks.toml']) }}"
                                        id="buildCommand" label="{{ __('application.gen_build_command_lbl') }}" x-bind:disabled="!canUpdate" />
                                    <x-forms.input helper="{{ __('application.gen_command_helper', ['file' => $buildPack === 'railpack' ? 'railpack.json' : 'nixpacks.toml']) }}"
                                        id="startCommand" label="{{ __('application.gen_start_command_lbl') }}" x-bind:disabled="!canUpdate" />
                                </div>
                            @endif
                            @if ($buildPack !== 'dockercompose')
                                @php
                                    $hasBuildServers = \App\Models\Server::buildServers($application->team()?->id)->exists();
                                    $buildServerOptions = [
                                        ['value' => false, 'label' => __('application.gen_opt_deployment_server')],
                                        $hasBuildServers
                                            ? ['value' => true, 'label' => __('application.gen_opt_build_server_auto')]
                                            : ['value' => true, 'label' => __('application.gen_opt_no_build_servers'), 'disabled' => true],
                                    ];
                                    $buildServerFallbackPolicy = $application->environment->project->team->is_build_server_fallback_enabled
                                        ? __('application.gen_build_fallback_enabled')
                                        : __('application.gen_build_fallback_disabled');
                                @endphp
                                <div class="grid gap-4 pt-2 sm:grid-cols-2">
                                    <x-forms.listbox id="isBuildServerEnabled" label="{{ __('application.gen_builder_selection') }}"
                                        onChange="instantSave" :options="$buildServerOptions"
                                        helper="{{ __('application.gen_builder_helper', ['policy' => $buildServerFallbackPolicy]) }}"
                                        x-bind:disabled="!canUpdate" />
                                </div>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
            @if ($isStatic || $buildPack === 'static')
                <div class="mt-5 border-t border-neutral-200 pt-5 dark:border-white/[0.07]">
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <label class="flex w-fit items-center gap-1.5" style="margin-bottom: 0">
                            {{ __('application.gen_nginx_title') }}
                            <x-helper helper="{{ __('application.gen_nginx_helper') }}" />
                        </label>
                        @can('update', $application)
                            <x-modal-confirmation title="{{ __('application.gen_nginx_confirm_title') }}"
                                buttonTitle="{{ __('application.gen_nginx_generate') }}"
                                submitAction="generateNginxConfiguration('{{ $application->settings->is_spa ? 'spa' : 'static' }}')"
                                :actions="[
                                    __('application.gen_nginx_action_overwrite'),
                                    __('application.gen_nginx_action_type', ['type' => $application->settings->is_spa ? 'SPA' : 'static']),
                                ]" />
                        @endcan
                    </div>
                    <x-forms.textarea id="customNginxConfiguration"
                        placeholder="{{ __('application.gen_nginx_placeholder') }}" rows="10"
                        monacoEditorLanguage="nginx" useMonacoEditor x-bind:disabled="!canUpdate" />
                </div>
            @endif
            @if ($buildPack === 'dockercompose')
                <div x-data="{ showRaw: true }" class="mt-5">
                    @can('update', $application)
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <h3>Docker Compose</h3>
                            <x-forms.button x-show="{{ $application->settings->is_raw_compose_deployment_enabled ? 'false' : 'true' }}"
                                @click.prevent="showRaw = !showRaw"
                                x-text="showRaw ? '{{ __('application.gen_show_deployable') }}' : '{{ __('application.gen_show_raw') }}'"></x-forms.button>
                        </div>
                        @if ($application->settings->is_raw_compose_deployment_enabled)
                            <x-forms.textarea rows="10" readonly id="dockerComposeRaw"
                                label="{{ __('application.gen_compose_content_id', ['id' => $application->id]) }}"
                                helper="{{ __('application.gen_modify_in_repo') }}"
                                monacoEditorLanguage="yaml" useMonacoEditor />
                        @else
                            @if ((int) $application->compose_parsing_version >= 3)
                                <div x-show="showRaw">
                                    <x-forms.textarea rows="10" readonly id="dockerComposeRaw"
                                        label="{{ __('application.gen_compose_content_raw') }}"
                                        helper="{{ __('application.gen_modify_in_repo') }}"
                                        monacoEditorLanguage="yaml" useMonacoEditor />
                                </div>
                            @endif
                            <div x-show="showRaw === false">
                                <x-forms.textarea rows="10" readonly id="dockerCompose"
                                    label="{{ __('application.gen_compose_content') }}"
                                    helper="{{ __('application.gen_modify_in_repo') }}"
                                    monacoEditorLanguage="yaml" useMonacoEditor />
                            </div>
                        @endif
                    @else
                        <h3 class="mb-2">Docker Compose</h3>
                        <x-callout type="info" title="{{ __('application.gen_compose_hidden') }}" class="mb-4">
                            {{ __('application.gen_compose_secrets') }}
                        </x-callout>
                    @endcan
                    <div class="w-full sm:w-96">
                        <x-forms.checkbox label="{{ __('application.gen_escape_labels') }}"
                            helper="{{ __('application.gen_escape_labels_helper') }}"
                            id="isContainerLabelEscapeEnabled" instantSave
                            x-bind:disabled="!canUpdate"></x-forms.checkbox>
                        {{-- <x-forms.checkbox label="Readonly labels"
                            helper="Labels are readonly by default. Readonly means that edits you do to the labels could be lost and Coolify will autogenerate the labels for you. If you want to edit the labels directly, disable this option. <br><br>Be careful, it could break the proxy configuration after you restart the container as Coolify will now NOT autogenerate the labels for you (ofc you can always reset the labels to the coolify defaults manually)."
                            id="isContainerLabelReadonlyEnabled" instantSave></x-forms.checkbox> --}}
                    </div>
                </div>
            @endif
            @if ($application->dockerfile)
                <div class="mt-6">
                    <x-forms.textarea label="Dockerfile" id="dockerfile" monacoEditorLanguage="dockerfile"
                        useMonacoEditor rows="6" x-bind:disabled="!canUpdate"> </x-forms.textarea>
                </div>
            @endif
            </x-application.settings-section>
            @if ($buildPack !== 'dockercompose')
                <x-application.settings-section id="container-image-section" title="{{ __('application.gen_image_title') }}" helper="{{ __('application.gen_image_helper') }}">
                @if ($application->destination->server->isSwarm())
                    @if ($application->build_pack !== 'dockerimage')
                        <div>{!! __('application.gen_swarm_registry') !!}</div>
                    @endif
                @endif
                <div class="grid gap-4 lg:grid-cols-2">
                    @if ($application->build_pack === 'dockerimage')
                        @if ($application->destination->server->isSwarm())
                            <x-forms.input required id="dockerRegistryImageName" label="{{ __('application.gen_image_label') }}" placeholder="nginx"
                                x-bind:disabled="!canUpdate" />
                            <x-forms.input id="dockerRegistryImageTag" label="{{ __('application.gen_tag_label') }}" placeholder="alpine"
                                helper="{{ __('application.gen_tag_helper_prebuilt') }}"
                                x-bind:disabled="!canUpdate" />
                        @else
                            <x-forms.input id="dockerRegistryImageName" label="{{ __('application.gen_image_label') }}" placeholder="nginx"
                                x-bind:disabled="!canUpdate" />
                            <x-forms.input id="dockerRegistryImageTag" label="{{ __('application.gen_tag_label') }}" placeholder="alpine"
                                helper="{{ __('application.gen_tag_helper_prebuilt') }}"
                                x-bind:disabled="!canUpdate" />
                        @endif
                    @else
                        @if (
                            $application->destination->server->isSwarm() ||
                                $application->additional_servers->count() > 0 ||
                                $application->settings->is_build_server_enabled ||
                                ! $application->destination->server->canBuildApplications())
                            <x-forms.input id="dockerRegistryImageName" required label="{{ __('application.gen_image_label') }}"
                                placeholder="ghcr.io/your-org/your-app" x-bind:disabled="!canUpdate" />
                            <x-forms.input id="dockerRegistryImageTag"
                                helper="{{ __('application.gen_tag_helper_built') }}"
                                placeholder="latest" label="{{ __('application.gen_tag_label') }}"
                                x-bind:disabled="!canUpdate" />
                        @else
                            <x-forms.input id="dockerRegistryImageName"
                                helper="{{ __('application.gen_image_push_helper') }}"
                                placeholder="ghcr.io/your-org/your-app"
                                label="{{ __('application.gen_image_label') }}" x-bind:disabled="!canUpdate" />
                            <x-forms.input id="dockerRegistryImageTag"
                                placeholder="latest"
                                helper="{{ __('application.gen_tag_helper_built') }}"
                                label="{{ __('application.gen_tag_label') }}" x-bind:disabled="!canUpdate" />
                        @endif
                    @endif
                </div>
                </x-application.settings-section>
            @endif

            @if ($buildPack !== 'dockercompose')
                @php
                    $applicationDomainsUrl = route('project.application.domains', [
                        'project_uuid' => $application->environment->project->uuid,
                        'environment_uuid' => $application->environment->uuid,
                        'application_uuid' => $application->uuid,
                    ]);
                    $portsExposesDomainHint = __('application.gen_ports_domain_hint', ['url' => $applicationDomainsUrl]);
                @endphp
                <x-application.settings-section id="networking-section" title="{{ __('application.gen_network_title') }}" helper="{{ __('application.gen_network_helper') }}">
                @if ($this->detectedPortInfo)
                    @if ($this->detectedPortInfo['isEmpty'])
                        <div
                            class="flex items-start gap-2 p-4 mb-4 text-sm rounded-lg bg-warning-50 dark:bg-warning-900/20 text-warning-800 dark:text-warning-300 border border-warning-200 dark:border-warning-800">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div>
                                <span class="font-semibold">{{ __('application.gen_port_detected') }}
                                    ({{ $this->detectedPortInfo['port'] }})</span>
                                <p class="mt-1">{!! __('application.gen_port_detected_empty', ['port' => $this->detectedPortInfo['port']]) !!}</p>
                            </div>
                        </div>
                    @elseif (!$this->detectedPortInfo['matches'])
                        <div
                            class="flex items-start gap-2 p-4 mb-4 text-sm rounded-lg bg-warning-50 dark:bg-warning-900/20 text-warning-800 dark:text-warning-300 border border-warning-200 dark:border-warning-800">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div>
                                <span class="font-semibold">{{ __('application.gen_port_mismatch') }}</span>
                                <p class="mt-1">{!! __('application.gen_port_mismatch_body', ['port' => $this->detectedPortInfo['port']]) !!}</p>
                            </div>
                        </div>
                    @else
                        <div
                            class="flex items-start gap-2 p-4 mb-4 text-sm rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div>
                                <span class="font-semibold">{{ __('application.gen_port_configured') }}</span>
                                <p class="mt-1">{!! __('application.gen_port_configured_body', ['port' => $this->detectedPortInfo['port']]) !!}</p>
                            </div>
                        </div>
                    @endif
                @endif
                @if ((empty($portsExposes) || $portsExposes === '0') && !empty($fqdn))
                    <x-callout type="info" title="{{ __('application.gen_no_ports_title') }}" class="mb-4">
                        {{ __('application.gen_no_ports_body') }}
                    </x-callout>
                @endif
                <div class="grid gap-4 lg:grid-cols-[14rem_16rem_minmax(0,1fr)]">
                    <div class="min-w-0">
                    @if ($isStatic || $buildPack === 'static')
                        <x-forms.input id="portsExposes" label="{{ __('application.gen_ports_exposes') }}" readonly
                            :helper="$portsExposesDomainHint"
                            canGate="update" :canResource="$application"
                            x-bind:disabled="!canUpdate" />
                    @else
                        @if ($application->settings->is_container_label_readonly_enabled === false)
                            <x-forms.input placeholder="3000,3001" id="portsExposes" label="{{ __('application.gen_ports_exposes') }}" readonly
                                :helper="__('application.gen_ports_readonly_html', ['hint' => $portsExposesDomainHint])"
                                canGate="update" :canResource="$application"
                                x-bind:disabled="!canUpdate" />
                        @else
                            <x-forms.input placeholder="3000,3001" id="portsExposes" label="{{ __('application.gen_ports_exposes') }}"
                                :helper="__('application.gen_ports_helper_html', ['hint' => $portsExposesDomainHint])"
                                canGate="update" :canResource="$application"
                                x-bind:disabled="!canUpdate" />
                        @endif
                    @endif
                    <p class="mt-1.5 text-xs text-neutral-500 dark:text-fg-dim">
                        {!! __('application.gen_ports_per_domain', ['url' => $applicationDomainsUrl]) !!}
                    </p>
                    </div>
                    @if (!$application->destination->server->isSwarm())
                        <x-forms.input placeholder="3000:3000" id="portsMappings" label="{{ __('application.gen_port_mappings') }}"
                            helper="{{ __('application.gen_port_mappings_helper') }}"
                            x-bind:disabled="!canUpdate" />
                    @endif
                    @if (!$application->destination->server->isSwarm())
                        <x-forms.input id="customNetworkAliases" label="{{ __('application.gen_network_aliases') }}"
                            helper="{{ __('application.gen_network_aliases_helper') }}"
                            wire:model="customNetworkAliases" x-bind:disabled="!canUpdate" />
                    @endif
                </div>
                </x-application.settings-section>

                <x-application.settings-section id="runtime-section" title="{{ __('application.gen_runtime_title') }}" helper="{{ __('application.gen_runtime_helper') }}">
                    <x-forms.input
                        helper="{{ __('application.gen_custom_docker_helper') }}"
                        placeholder="--cap-add SYS_ADMIN --device=/dev/fuse --security-opt apparmor:unconfined --ulimit nofile=1024:1024 --tmpfs /run:rw,noexec,nosuid,size=65536k --hostname=myapp"
                        id="customDockerRunOptions" label="{{ __('application.gen_custom_docker_label') }}" x-bind:disabled="!canUpdate" />
                </x-application.settings-section>

                <x-application.settings-section id="security-section" title="{{ __('application.gen_security_title') }}" helper="{{ __('application.gen_security_helper') }}">
                    @if ($application->settings->is_container_label_readonly_enabled == false)
                    <x-empty size="sm" title="{{ __('application.gen_auth_labels_title') }}"
                        description="{{ __('application.gen_auth_labels_desc') }}"
                        icon-name="admin">
                        <x-slot:contents>
                            <button type="button" class="button"
                                @click="window.scrollToSettingsSection?.('container-labels-section')">
                                {{ __('application.gen_go_labels') }}
                            </button>
                        </x-slot:contents>
                    </x-empty>
                    @else
                    <x-forms.listbox id="isHttpBasicAuthEnabled" label="{{ __('application.gen_auth_label') }}" onChange="instantSave"
                        helper="{{ __('application.gen_auth_helper') }}"
                        :options="[
                            ['value' => false, 'label' => __('application.gen_auth_none')],
                            ['value' => true, 'label' => __('application.gen_auth_basic')],
                        ]" x-bind:disabled="!canUpdate" />
                    @if ($isHttpBasicAuthEnabled)
                        <div class="mt-5 grid w-full gap-4 border-t border-neutral-200 pt-5 sm:grid-cols-2 dark:border-white/[0.07]">
                            <x-forms.input id="httpBasicAuthUsername" label="{{ __('application.gen_username') }}" required
                                x-bind:disabled="!canUpdate" />
                            <x-forms.input id="httpBasicAuthPassword" type="password" label="{{ __('application.gen_password') }}" required
                                x-bind:disabled="!canUpdate" />
                        </div>
                    @endif
                    @endif
                </x-application.settings-section>
            @endif

            <x-application.settings-section id="deployment-lifecycle-section" title="{{ __('application.gen_lifecycle_title') }}" helper="{{ __('application.gen_lifecycle_helper') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-4">
                    <x-forms.input x-bind:disabled="shouldDisable()" placeholder="php artisan migrate"
                        id="preDeploymentCommand" label="{{ __('application.gen_pre_deployment') }}"
                        helper="{{ __('application.gen_pre_deployment_helper') }}" />
                    @if ($buildPack === 'dockercompose')
                        <x-forms.input x-bind:disabled="shouldDisable()" id="preDeploymentCommandContainer"
                            label="{{ __('application.gen_container_name') }}"
                            helper="{{ __('application.gen_container_name_helper') }}" />
                    @endif
                </div>
                <div class="flex flex-col gap-4">
                    <x-forms.input x-bind:disabled="shouldDisable()" placeholder="php artisan migrate"
                        id="postDeploymentCommand" label="{{ __('application.gen_post_deployment') }}"
                        helper="{{ __('application.gen_post_deployment_helper') }}" />
                    @if ($buildPack === 'dockercompose')
                        <x-forms.input x-bind:disabled="shouldDisable()" id="postDeploymentCommandContainer"
                            label="{{ __('application.gen_container_name') }}"
                            helper="{{ __('application.gen_container_name_helper') }}" />
                    @endif
                </div>
            </div>
            </x-application.settings-section>

            @if ($buildPack !== 'dockercompose')
                <x-application.settings-section id="container-labels-section" title="{{ __('application.gen_labels_title') }}" helper="{{ __('application.gen_labels_helper') }}">
                <div class="grid w-full gap-4 sm:grid-cols-2">
                    <x-forms.listbox id="isContainerLabelReadonlyEnabled" label="{{ __('application.gen_label_mgmt') }}"
                        onChange="instantSave"
                        helper="{{ __('application.gen_label_mgmt_helper') }}"
                        :options="[
                            ['value' => true, 'label' => __('application.gen_label_opt_managed')],
                            ['value' => false, 'label' => __('application.gen_label_opt_manual')],
                        ]" x-bind:disabled="!canUpdate" />
                    <x-forms.listbox id="isContainerLabelEscapeEnabled" label="{{ __('application.gen_special_chars') }}"
                        onChange="instantSave"
                        helper="{{ __('application.gen_special_chars_helper') }}"
                        :options="[
                            ['value' => true, 'label' => __('application.gen_escape_opt_on')],
                            ['value' => false, 'label' => __('application.gen_escape_opt_off')],
                        ]" x-bind:disabled="!canUpdate" />
                </div>
                <div class="mt-5 border-t border-neutral-200 pt-5 dark:border-white/[0.07]">
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <label class="flex w-fit items-center gap-1.5" style="margin-bottom: 0">{{ __('application.gen_active_labels') }}</label>
                        @can('update', $application)
                            <x-modal-confirmation title="{{ __('application.gen_labels_reset_title') }}"
                                buttonTitle="{{ __('application.gen_labels_reset_btn') }}" submitAction="resetDefaultLabels(true)"
                                :actions="[
                                    __('application.gen_labels_reset_action1'),
                                    __('application.gen_labels_reset_action2'),
                                ]" confirmationText="{{ $application->fqdn . '/' }}"
                                confirmationLabel="{{ __('application.gen_labels_reset_confirm_label') }}"
                                shortConfirmationLabel="{{ __('application.gen_labels_reset_confirm_short') }}" :confirmWithPassword="false"
                                step2ButtonText="{{ __('application.gen_labels_reset_step2') }}" />
                        @endcan
                    </div>
                    @if ($application->settings->is_container_label_readonly_enabled)
                        <x-forms.textarea readonly disabled rows="15" id="customLabels"
                            monacoEditorLanguage="ini" useMonacoEditor x-bind:disabled="!canUpdate"></x-forms.textarea>
                    @else
                        <x-forms.textarea rows="15" id="customLabels"
                            monacoEditorLanguage="ini" useMonacoEditor x-bind:disabled="!canUpdate"></x-forms.textarea>
                    @endif
                </div>
                </x-application.settings-section>
            @endif
        </div>
    </form>

    <x-domain-conflict-modal :conflicts="$domainConflicts" :showModal="$showDomainConflictModal" confirmAction="confirmDomainUsage" />

    @script
        <script>
            $wire.$on('loadCompose', (isInit = true) => {
                // Only load compose file if user has permission (this event should only be dispatched when authorized)
                $wire.initLoadingCompose = true;
                $wire.loadComposeFile(isInit);
            });
        </script>
    @endscript
</div>
