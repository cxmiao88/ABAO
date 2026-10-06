@props(['server', 'activeMenu', 'activeSubMenu' => null])

@php
    $serverRouteParameters = ['server_uuid' => $server->uuid];
    $sentinelStatus = $server->sentinelStatus();
    $proxyNotRunning = $server->proxySet() && ($server->proxy->status ?? 'unknown') !== 'running';
    $sentinelStatusStartedAt = $server->sentinel_waiting_since ?? \Illuminate\Support\Carbon::parse($server->sentinel_updated_at);
    $sentinelTimeoutSeconds = $server->sentinel_waiting_since !== null
        ? $server->firstSentinelReportTimeoutSeconds()
        : $server->waitBeforeDoingSshCheck();
    $sentinelExpiresInMilliseconds = max(
        0,
        ($sentinelStatusStartedAt->copy()->addSeconds($sentinelTimeoutSeconds)->timestamp - now()->timestamp) * 1000,
    );
    $serverMenuItems = [
        [
            'label' => __('server.menu_general'),
            'route' => 'server.show',
            'active' => $activeMenu === 'general',
            'icon' => 'settings',
            'group' => __('server.group_settings'),
        ],
        [
            'label' => __('server.menu_advanced'),
            'route' => 'server.advanced',
            'active' => $activeMenu === 'advanced',
            'icon' => 'grid',
            'group' => __('server.group_settings'),
            'visible' => $server->isFunctional(),
        ],
        [
            'label' => __('server.menu_private_key'),
            'route' => 'server.private-key',
            'active' => $activeMenu === 'private-key',
            'icon' => 'keys',
            'group' => __('server.group_settings'),
        ],
        [
            'label' => __('server.menu_cloud_token'),
            'route' => 'server.cloud-provider-token',
            'active' => $activeMenu === 'cloud-provider-token',
            'icon' => 'subscription',
            'group' => __('server.group_settings'),
            'visible' => (bool) ($server->hetzner_server_id || $server->vultr_instance_id),
        ],
        [
            'label' => __('server.menu_ca_certificate'),
            'route' => 'server.ca-certificate',
            'active' => $activeMenu === 'ca-certificate',
            'icon' => 'file',
            'group' => __('server.group_settings'),
        ],
        [
            'label' => __('server.menu_cloudflare_tunnel'),
            'route' => 'server.cloudflare-tunnel',
            'active' => $activeMenu === 'cloudflare-tunnel',
            'icon' => 'globe',
            'group' => __('server.group_networking'),
            'visible' => ! $server->isLocalhost(),
        ],
        [
            'label' => __('server.menu_proxy'),
            'route' => 'server.proxy',
            'active' => $activeMenu === 'proxy',
            'icon' => 'network',
            'group' => __('server.group_platform'),
            'visible' => ! $server->isSwarmWorker() && $server->canHostResources(),
            'warning' => $server->hasCurrentTraefikOutdatedInfo(),
            'tracks_proxy_configuration' => true,
            'children' => [
                ['label' => __('server.sub_configuration'), 'route' => 'server.proxy', 'active' => $activeSubMenu === 'configuration', 'icon' => 'settings'],
                ['label' => __('server.sub_dynamic_configurations'), 'route' => 'server.proxy.dynamic-confs', 'active' => $activeSubMenu === 'dynamic-confs', 'icon' => 'sliders', 'visible' => $server->proxySet()],
                ['label' => __('server.sub_tls_certificates'), 'route' => 'server.proxy.certificates', 'active' => $activeSubMenu === 'certificates', 'icon' => 'shield-star', 'visible' => $server->proxyType() === \App\Enums\ProxyTypes::TRAEFIK->value],
                ['label' => __('server.sub_logs'), 'route' => 'server.proxy.logs', 'active' => $activeSubMenu === 'logs', 'icon' => 'file-content', 'visible' => $server->proxySet(), 'navigate' => false],
            ],
        ],
        [
            'label' => __('server.menu_sentinel'),
            'route' => 'server.sentinel',
            'active' => request()->routeIs('server.sentinel', 'server.sentinel.*'),
            'icon' => 'shield-star',
            'group' => __('server.group_platform'),
            'visible' => $server->isFunctional() && ! $server->isSwarm() && $server->canHostResources() && auth()->user()?->can('viewSentinel', $server),
            'warning' => $server->isSentinelEnabled() && $sentinelStatus === 'out_of_sync',
            'tracks_sentinel_status' => true,
            'children' => [
                ['label' => __('server.sub_configuration'), 'route' => 'server.sentinel', 'active' => request()->routeIs('server.sentinel'), 'icon' => 'settings'],
                ['label' => __('server.sub_logs'), 'route' => 'server.sentinel.logs', 'active' => request()->routeIs('server.sentinel.logs'), 'icon' => 'file-content'],
            ],
        ],
        [
            'label' => __('server.menu_resources'),
            'route' => 'server.resources',
            'active' => $activeMenu === 'resources',
            'icon' => 'projects',
            'group' => __('server.group_platform'),
        ],
        [
            'label' => __('server.menu_terminal'),
            'route' => 'server.command',
            'active' => $activeMenu === 'terminal',
            'icon' => 'browser-terminal',
            'group' => __('server.group_operations'),
            'navigate' => false,
            'visible' => auth()->user()?->can('canAccessTerminal'),
        ],
        [
            'label' => __('server.menu_destinations'),
            'route' => 'server.destinations',
            'active' => $activeMenu === 'destinations',
            'icon' => 'destinations',
            'group' => __('server.group_networking'),
            'visible' => $server->isFunctional(),
        ],
        [
            'label' => __('server.menu_swarm'),
            'route' => 'server.swarm',
            'active' => $activeMenu === 'swarm',
            'icon' => 'layers',
            'group' => __('server.group_networking'),
            'visible' => $server->team->usesSwarm() && ! $server->isBuildServer() && ! $server->settings->is_cloudflare_tunnel,
        ],
        [
            'label' => __('server.menu_images'),
            'route' => 'server.docker-images',
            'active' => $activeMenu === 'docker-images',
            'icon' => 'layers',
            'group' => __('server.group_operations'),
            'visible' => $server->isFunctional(),
        ],
        [
            'label' => __('server.menu_docker_cleanup'),
            'route' => 'server.docker-cleanup',
            'active' => $activeMenu === 'docker-cleanup',
            'icon' => 'broom',
            'group' => __('server.group_operations'),
            'visible' => $server->isFunctional(),
        ],
        [
            'label' => __('server.menu_github_runners'),
            'route' => 'server.github-runners',
            'active' => $activeMenu === 'github-runners',
            'icon' => 'play-circle',
            'group' => __('server.group_operations'),
            'visible' => ! $server->isLocalhost(),
            'beta' => true,
        ],
        [
            'label' => __('server.menu_registries'),
            'route' => 'server.registries',
            'active' => $activeMenu === 'registries',
            'icon' => 'layers',
            'group' => __('server.group_operations'),
            'visible' => auth()->user()?->can('update', $server),
        ],
        [
            'label' => __('server.menu_log_drains'),
            'route' => 'server.log-drains',
            'active' => $activeMenu === 'log-drains',
            'icon' => 'notifications',
            'group' => __('server.group_operations'),
            'visible' => $server->isFunctional(),
        ],
        [
            'label' => __('server.menu_metrics'),
            'route' => 'server.metrics',
            'active' => $activeMenu === 'metrics',
            'icon' => 'graph',
            'group' => __('server.group_operations'),
            'visible' => $server->isFunctional(),
        ],
        [
            'label' => __('server.menu_analytics'),
            'route' => 'server.analytics',
            'active' => $activeMenu === 'analytics',
            'icon' => 'analytics',
            'group' => __('server.group_operations'),
            'visible' => $server->isFunctional() && ! $server->isSwarm() && ! $server->isBuildServer(),
        ],
        [
            'label' => __('server.menu_security'),
            'route' => 'server.security.patches',
            'active' => request()->routeIs('server.security.*'),
            'icon' => 'shield-alert',
            'group' => __('server.group_security'),
            'visible' => auth()->user()?->can('update', $server),
            'children' => [
                ['label' => __('server.sub_server_patching'), 'route' => 'server.security.patches', 'active' => request()->routeIs('server.security.patches'), 'icon' => 'bandage'],
                ['label' => __('server.sub_terminal_access'), 'route' => 'server.security.terminal-access', 'active' => request()->routeIs('server.security.terminal-access'), 'icon' => 'browser-terminal', 'navigate' => false],
            ],
        ],
        [
            'label' => __('server.menu_transfer'),
            'route' => 'server.transfer',
            'active' => $activeMenu === 'transfer',
            'icon' => 'arrow-right',
            'group' => __('server.group_operations'),
            'visible' => isDev() && ! $server->isLocalhost() && auth()->user()?->can('view', $server),
        ],
        [
            'label' => __('server.menu_danger'),
            'route' => 'server.delete',
            'active' => $activeMenu === 'danger',
            'icon' => 'shield-alert',
            'group' => __('server.group_danger_zone'),
            // Coolify host (id 0) cannot be deleted. Other servers may still use
            // host.docker.internal (e.g. Lima VMs) and must keep the Danger menu.
            'visible' => ! $server->is_coolify_host,
        ],
    ];

    $serverMenuItems = collect($serverMenuItems)
        ->filter(fn (array $item): bool => $item['visible'] ?? true)
        ->values();
    $groupedServerMenuItems = $serverMenuItems->groupBy('group');

    // Group that holds the current page (item or nested child) — always kept
    // open, even if collapsed before.
    $activeGroup = (string) $groupedServerMenuItems->search(fn ($items) => $items->contains(
        fn ($item) => ($item['active'] ?? false)
            || collect($item['children'] ?? [])->contains(fn ($child) => $child['active'] ?? false)
    ));
@endphp

<aside class="application-settings-navigation min-w-0 xl:self-start"
    x-data="{
        proxyConfigurationPending: @js($server->hasPendingProxyConfiguration()),
        traefikOutdated: @js($server->hasCurrentTraefikOutdatedInfo()),
        proxyNotRunning: @js($proxyNotRunning),
        sentinelOutOfSync: @js($server->isSentinelEnabled() && $sentinelStatus === 'out_of_sync'),
        sentinelExpiryTimer: null,
        scheduleSentinelExpiry(delay) {
            clearTimeout(this.sentinelExpiryTimer);
            if (!this.sentinelOutOfSync) {
                this.sentinelExpiryTimer = setTimeout(() => this.sentinelOutOfSync = true, delay);
            }
        }
    }"
    x-init="scheduleSentinelExpiry(@js($sentinelExpiresInMilliseconds))"
    @proxy-configuration-state-changed.window="
        proxyConfigurationPending = $event.detail.pending;
        traefikOutdated = $event.detail.traefikOutdated;
        proxyNotRunning = $event.detail.proxyNotRunning;
    "
    @sentinel-status-changed.window="
        sentinelOutOfSync = $event.detail.outOfSync;
        scheduleSentinelExpiry($event.detail.expiresInMilliseconds);
    ">
    <nav aria-label="{{ __('server.sidebar_aria') }}"
        x-data="settingsSidebarAccordion({ activeGroup: @js($activeGroup), storageKey: 'coolify.settings-sidebar.server' })"
        class="grid grid-cols-2 gap-0.5 border-y border-neutral-200 py-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-1 xl:border-y-0 xl:py-0 dark:border-white/[0.06]">
        @foreach ($groupedServerMenuItems as $groupLabel => $groupItems)
            @unless ($loop->first)
                <div class="my-2 hidden border-t border-neutral-200 xl:block dark:border-white/[0.06]"
                    aria-hidden="true"></div>
            @endunless
            <button type="button" class="nav-section-toggle hidden xl:flex" @click="toggle(@js($groupLabel))"
                :aria-expanded="isOpen(@js($groupLabel))">
                <span>{{ $groupLabel }}</span>
                <svg class="size-3 shrink-0 opacity-60 transition-transform"
                    :class="!isOpen(@js($groupLabel)) && '-rotate-90'" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>
            <div class="contents" :class="isOpen(@js($groupLabel)) ? 'xl:block' : 'xl:hidden'">
            @foreach ($groupItems as $menuItem)
                <a wire:key="server-settings-link-{{ $menuItem['route'] }}"
                    @class([
                        'menu-item',
                        'menu-item-active' => $menuItem['active'],
                    ])
                    @if ($menuItem['navigate'] ?? true) {{ wireNavigate() }} @endif
                    href="{{ route($menuItem['route'], $serverRouteParameters) }}">
                    <x-reicon :name="$menuItem['icon']" class="menu-item-icon" />
                    <span class="menu-item-label">{{ $menuItem['label'] }}</span>
                    @if ($menuItem['tracks_proxy_configuration'] ?? false)
                        <x-reicon name="alert-triangle" x-cloak
                            x-show="proxyConfigurationPending || traefikOutdated || proxyNotRunning"
                            class="ml-auto size-3.5 shrink-0 text-orange-500 dark:text-warning" />
                    @elseif ($menuItem['tracks_sentinel_status'] ?? false)
                        <x-reicon name="alert-triangle" x-cloak x-show="sentinelOutOfSync"
                            class="ml-auto size-3.5 shrink-0 text-orange-500 dark:text-warning" />
                    @elseif ($menuItem['warning'] ?? false)
                        <x-reicon name="alert-triangle"
                            class="ml-auto size-3.5 shrink-0 text-orange-500 dark:text-warning" />
                    @elseif ($menuItem['beta'] ?? false)
                        <x-beta-badge class="ml-auto shrink-0" />
                    @endif
                </a>
                @if ($menuItem['active'] && isset($menuItem['children']))
                    <div class="col-span-full grid grid-cols-2 gap-0.5 border-l border-neutral-200 pl-2 sm:grid-cols-3 xl:grid-cols-1 dark:border-white/[0.08]">
                        @foreach (collect($menuItem['children'])->filter(fn (array $child): bool => $child['visible'] ?? true) as $child)
                            <a wire:key="server-settings-child-{{ $child['route'] }}"
                                @class(['menu-item', 'menu-item-active' => $child['active']])
                                @if ($child['navigate'] ?? true) {{ wireNavigate() }} @endif
                                href="{{ route($child['route'], $serverRouteParameters) }}">
                                <x-reicon :name="$child['icon']" class="menu-item-icon" />
                                <span class="menu-item-label">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
            </div>
        @endforeach
    </nav>
</aside>
