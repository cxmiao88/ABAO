<div class="application-settings-form w-full">
    <x-slot:title>
        {{ __('nav.dashboard') }} | ABao
    </x-slot>

    @if (session('error'))
        <span x-data x-init="$wire.dispatch('error', @js(session('error')))" />
    @endif

    @php
        $dashboardItemLimit = 8;
        // Compatible with older images that do not pass these in yet.
        $pendingInvitations = isset($pendingInvitations) && $pendingInvitations !== null ? $pendingInvitations : collect();
        $dashboardProjects = $projects->sortBy('name', SORT_NATURAL)->take($dashboardItemLimit);
        $dashboardServers = $servers->sortBy('name', SORT_NATURAL)->take($dashboardItemLimit);
        $hasTrafficAnalytics = $servers->contains(
            fn ($server) => method_exists($server, 'isTrafficAnalyticsEnabled') && $server->isTrafficAnalyticsEnabled()
        );
    @endphp
    {{-- 仪表盘模式切换 --}}
    <div class="mb-2 flex min-w-0 items-center gap-2">
        <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-white p-1 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.04]">
            <button type="button" wire:click="switchDashboardMode('baota')"
                @class([
                    'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-[13px] font-medium transition-colors',
                    'bg-emerald-600 text-white shadow-sm' => $dashboardMode === 'baota',
                    'text-neutral-500 hover:bg-neutral-100 dark:text-fg-dim dark:hover:bg-white/[0.06]' => $dashboardMode !== 'baota',
                ])>
                <x-reicon name="servers" class="size-3.5" />
                {{ __('dash_mode_baota') }}
            </button>
            <button type="button" wire:click="switchDashboardMode('classic')"
                @class([
                    'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-[13px] font-medium transition-colors',
                    'bg-neutral-800 text-white shadow-sm dark:bg-white dark:text-neutral-900' => $dashboardMode === 'classic',
                    'text-neutral-500 hover:bg-neutral-100 dark:text-fg-dim dark:hover:bg-white/[0.06]' => $dashboardMode !== 'classic',
                ])>
                <x-reicon name="grid" class="size-3.5" />
                {{ __('dash_mode_classic') }}
            </button>
        </div>
    </div>

    @if ($dashboardMode === 'baota')
        @include('livewire.dashboard.baota', [
            'servers' => $servers,
            'projects' => $projects,
            'dashboardServers' => $dashboardServers,
        ])
    @else

    <div class="flex min-w-0 flex-col gap-8">
        @if ($pendingInvitations->isNotEmpty())
            <div class="flex min-w-0 flex-col gap-2">
                @foreach ($pendingInvitations as $invitation)
                    <x-callout type="info" title="{{ __('dashboard.pending_team_invitation') }}"
                        wire:key="dashboard-invitation-{{ $invitation->uuid }}">
                        <div class="flex min-w-0 flex-wrap items-center justify-between gap-2">
                            <span class="min-w-0">{!! __('dashboard.invitation_text', [
                                'team' => '<span class="font-semibold">' . e($invitation->team->name) . '</span>',
                                'role' => e(ucfirst($invitation->role instanceof \App\Enums\Role ? $invitation->role->value : $invitation->role)),
                            ]) !!}</span>
                            <a href="{{ route('team.invitation.show', $invitation->uuid) }}" class="button shrink-0">
                                {{ __('dashboard.review_invitation') }}
                            </a>
                        </div>
                    </x-callout>
                @endforeach
            </div>
        @endif

        <livewire:dashboard.active-deployments />

        @if ($hasTrafficAnalytics)
            <livewire:dashboard.traffic-analytics />
        @endif

        <section class="mb-0! min-w-0">
            <x-section-heading title="{{ __('nav.projects') }}" subtitle="{{ __('dashboard.projects_subtitle') }}"
                :href="route('project.index')" />

            @if ($dashboardProjects->isEmpty())
                <x-empty title="{{ __('dashboard.no_projects') }}"
                    description="{{ __('dashboard.no_projects_description') }}"
                    icon-name="projects" size="sm" />
            @else
                <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($dashboardProjects as $project)
                        @php
                            $firstEnvironment = $project->environments->first();
                            $resourceCount = collect([
                                $project->applications_count,
                                $project->services_count,
                                $project->postgresqls_count,
                                $project->redis_count,
                                $project->keydbs_count,
                                $project->dragonflies_count,
                                $project->clickhouses_count,
                                $project->mongodbs_count,
                                $project->mysqls_count,
                                $project->mariadbs_count,
                                $project->sqlites_count,
                            ])->sum();
                        @endphp

                        <article
                            class="group relative flex min-h-28 min-w-0 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]">
                            <a href="{{ $project->navigateTo() }}" {{ wireNavigate() }}
                                class="absolute inset-0 rounded-xl"
                                aria-label="{{ __('project.open') }} {{ $project->name }}"></a>

                            <div class="flex min-w-0 items-start gap-3">
                                <div
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                                    @if ($project->icon_path)
                                        <img src="{{ project_icon_url($project) }}"
                                            alt="{{ $project->name }} icon"
                                            class="h-full w-full rounded-lg object-cover">
                                    @else
                                        <x-reicon name="projects" class="size-4" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="truncate text-[13px]! leading-4! font-semibold! text-black dark:text-fg">
                                        {{ $project->name }}
                                    </h3>
                                    <p class="mt-0.5 truncate text-[11px] text-neutral-500 dark:text-fg-faint">
                                        {{ $project->description }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-auto flex items-center justify-between gap-3 border-t border-neutral-100 pt-2.5 dark:border-white/[0.06]">
                                <div class="relative z-10 flex min-w-0 items-center gap-3 text-[11px] font-medium text-neutral-500 dark:text-fg-dim">
                                    <span class="inline-flex items-center gap-1" data-tooltip="{{ __('project.environments') }}"
                                        aria-label="{{ __('project.environments') }}">
                                        <x-reicon name="layers" class="size-3.5 text-neutral-400 dark:text-fg-faint" />
                                        {{ $project->environments->count() }}
                                    </span>
                                    <span class="inline-flex items-center gap-1" data-tooltip="{{ __('project.resources') }}"
                                        aria-label="{{ __('project.resources') }}">
                                        <x-reicon name="grid" class="size-3.5 text-neutral-400 dark:text-fg-faint" />
                                        {{ $resourceCount }}
                                    </span>
                                </div>

                                <div class="relative z-10 flex shrink-0 items-center gap-0.5">
                                    @if ($firstEnvironment)
                                        @can('createAnyResource')
                                            <a href="{{ route('project.resource.create', [
                                                'project_uuid' => $project->uuid,
                                                'environment_uuid' => $firstEnvironment->uuid,
                                            ]) }}"
                                                {{ wireNavigate() }}
                                                class="flex size-6.5 items-center justify-center rounded-md text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-black dark:text-fg-faint dark:hover:bg-white/[0.06] dark:hover:text-fg"
                                                title="{{ __('project.add_resource') }}"
                                                aria-label="{{ __('project.add_resource_to') }} {{ $project->name }}">
                                                <x-reicon name="plus" class="size-3" />
                                            </a>
                                        @endcan
                                    @endif
                                    @can('update', $project)
                                        <a href="{{ route('project.edit', ['project_uuid' => $project->uuid]) }}"
                                            {{ wireNavigate() }}
                                            class="flex size-6.5 items-center justify-center rounded-md text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-black dark:text-fg-faint dark:hover:bg-white/[0.06] dark:hover:text-fg"
                                            title="{{ __('project.settings') }}"
                                            aria-label="{{ __('project.open_settings_for') }} {{ $project->name }}">
                                            <x-reicon name="settings" class="size-3" />
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mb-0! min-w-0">
            <x-section-heading title="{{ __('nav.servers') }}" subtitle="{{ __('dashboard.servers_subtitle') }}"
                :href="route('server.index')" />

            @if ($dashboardServers->isEmpty())
                @if ($privateKeys->isEmpty())
                    <x-empty title="{{ __('dashboard.private_key_required') }}"
                        description="{{ __('dashboard.private_key_description') }}"
                        icon-name="keys" size="sm">
                        @can('create', App\Models\PrivateKey::class)
                            <x-slot:contents>
                                <a href="{{ route('security.private-key.index') }}" {{ wireNavigate() }}
                                    class="button button-highlighted">
                                    <x-reicon name="plus" class="size-3.5" />
                                    {{ __('dashboard.add_private_key') }}
                                </a>
                            </x-slot:contents>
                        @endcan
                    </x-empty>
                @else
                    <x-empty title="{{ __('dashboard.no_servers') }}"
                        description="{{ __('dashboard.no_servers_description') }}"
                        icon-name="servers" size="sm">
                        @can('createAnyResource')
                            <x-slot:contents>
                                <a href="{{ route('server.create') }}" {{ wireNavigate() }}
                                    class="button button-highlighted">
                                    <x-reicon name="plus" class="size-3.5" />
                                    {{ __('dashboard.new_server') }}
                                </a>
                            </x-slot:contents>
                        @endcan
                    </x-empty>
                @endif
            @else
                <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($dashboardServers as $server)
                        @php
                            $proxyNeedsAttention = $server->proxySet() && ($server->proxy->status !== 'running' || $server->hasCurrentTraefikOutdatedInfo());
                            $sentinelNeedsAttention = $server->isSentinelEnabled() && $server->sentinelStatus() === 'out_of_sync';

                            [$serverStatus, $serverStatusType] = match (true) {
                                $server->settings->force_disabled => [__('server.status_disabled'), 'error'],
                                ! $server->settings->is_reachable && ! $server->settings->is_usable => [__('server.status_unavailable'), 'error'],
                                ! $server->settings->is_reachable => [__('server.status_unreachable'), 'error'],
                                ! $server->settings->is_usable => [__('server.status_not_ready'), 'warning'],
                                $proxyNeedsAttention || $sentinelNeedsAttention => [__('server.status_attention'), 'warning'],
                                default => [__('server.status_ready'), 'success'],
                            };
                        @endphp

                        <a href="{{ route('server.show', ['server_uuid' => $server->uuid]) }}"
                            {{ wireNavigate() }} aria-label="{{ __('project.open') }} {{ $server->name }}"
                            class="group relative flex min-h-44 min-w-0 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]">
                            @if ($server->isMetricsEnabled())
                                <livewire:dashboard.server-metrics-chart :server="$server"
                                    :key="'dashboard-server-metrics-'.$server->uuid" />
                            @endif

                            <div class="relative z-10 flex min-w-0 items-start gap-3">
                                <div
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.1] dark:bg-white/[0.04] dark:text-fg-dim">
                                    <x-reicon name="servers" class="size-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="truncate text-[13px]! leading-4! font-semibold! text-black dark:text-fg">
                                        {{ $server->name }}
                                    </h3>
                                    <p class="mt-0.5 truncate text-[11px] text-neutral-500 dark:text-fg-faint">
                                        {{ $server->description }}
                                    </p>
                                </div>
                                @if ($serverStatusType !== 'success')
                                    <span data-tooltip="{{ $serverStatus }}"
                                        aria-label="{{ __('server.status_label') }}: {{ $serverStatus }}"
                                        @class([
                                            'flex size-6 shrink-0 items-center justify-center rounded-md',
                                            'text-orange-500 dark:text-warning' => $serverStatusType === 'warning',
                                            'text-red-500 dark:text-red-400' => $serverStatusType === 'error',
                                        ])>
                                        <x-reicon name="alert-triangle" class="size-4" />
                                    </span>
                                @endif
                            </div>

                            <livewire:dashboard.server-status :server="$server"
                                :key="'dashboard-server-status-'.$server->uuid" />
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
        </div>
    @endif
</div>
