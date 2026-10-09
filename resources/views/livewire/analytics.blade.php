<?php
$tabButtonBase = 'relative inline-flex h-7 items-center justify-center rounded-md px-2.5 text-[12px] font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40';
$tabButtonActive = 'bg-white text-black shadow-sm ring-1 ring-neutral-200 dark:bg-white/[0.09] dark:text-fg dark:ring-white/[0.08]';
$tabButtonInactive = 'text-neutral-500 hover:text-black dark:text-fg-faint dark:hover:text-fg';

$dimensionLabels = [
    'referer' => __('anl_referrers'),
    'browser' => __('anl_browsers'),
    'os' => __('anl_os'),
];

$approxBadge = fn (string $tooltip) => '<span title="'.e($tooltip).'" class="ml-1.5 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-medium tracking-wide text-amber-700 uppercase dark:bg-amber-500/10 dark:text-amber-400">'.__('anl_approx_badge').'</span>';

$serverListboxOptions = array_merge(
    [['value' => '', 'label' => __('anl_all_servers')]],
    collect($serverOptions)->map(fn ($name, $uuid) => ['value' => $uuid, 'label' => $name])->values()->all(),
);
$appListboxOptions = array_merge(
    [['value' => '', 'label' => __('anl_all_resources')]],
    $appGroupedOptions,
);
?>
<div class="flex w-full min-w-0 flex-col gap-6">
    @if ($scopedServerUuid === null)
        <x-slot:title>
            {{ __('nav.analytics') }} | ABao
        </x-slot>
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-4">
        @if ($scopedServerUuid === null)
            <div class="min-w-0">
                <h1 class="min-w-0 text-[24px]! leading-7! font-semibold! tracking-tight!">{{ __('nav.analytics') }}</h1>
                <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-dim">
                    {{ __('anl_header_desc') }}
                </p>
            </div>
        @endif

        @if ($servers->isNotEmpty() && $overview)
            <div class="flex flex-wrap items-center gap-2">
                @if ($scopedServerUuid === null)
                    <div class="relative w-full transition-opacity sm:w-52"
                        wire:loading.class="pointer-events-none opacity-60" wire:target="serverUuid">
                        <x-forms.listbox id="serverUuid" live :options="$serverListboxOptions" placeholder="{{ __('anl_all_servers') }}" />
                        <div class="absolute inset-0 hidden items-center justify-center rounded-lg bg-white/70 dark:bg-base/70"
                            wire:loading.flex wire:target="serverUuid">
                            <x-loading compact aria-label="Loading analytics" />
                        </div>
                    </div>
                @endif
                {{-- Re-key on the server filter so the application listbox re-initializes with the
                     newly-scoped options (and reset value) instead of showing stale Alpine state. --}}
                <div class="relative w-full transition-opacity sm:w-52" wire:key="app-filter-{{ $serverUuid }}"
                    wire:loading.class="pointer-events-none opacity-60" wire:target="appUuid">
                    <x-forms.listbox id="appUuid" live :options="$appListboxOptions" placeholder="{{ __('anl_all_resources') }}" />
                    <div class="absolute inset-0 hidden items-center justify-center rounded-lg bg-white/70 dark:bg-base/70"
                        wire:loading.flex wire:target="appUuid">
                        <x-loading compact aria-label="Loading analytics" />
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:ml-auto">
                    @include('livewire.traffic._live-toggle')
                    <div class="inline-flex items-center gap-0.5 rounded-lg bg-neutral-100 p-1 dark:bg-white/[0.04]">
                        <button type="button" wire:click="setRange('24h')"
                            wire:loading.attr="disabled" wire:target="setRange"
                            @class([$tabButtonBase, $range === '24h' ? $tabButtonActive : $tabButtonInactive])>
                            <span wire:loading.class="invisible" wire:target="setRange('24h')">{{ __('anl_range_24h') }}</span>
                            <x-loading compact class="absolute" wire:loading wire:target="setRange('24h')" aria-label="Loading analytics" />
                        </button>
                        <button type="button" wire:click="setRange('7d')"
                            wire:loading.attr="disabled" wire:target="setRange"
                            @class([$tabButtonBase, $range === '7d' ? $tabButtonActive : $tabButtonInactive])>
                            <span wire:loading.class="invisible" wire:target="setRange('7d')">{{ __('anl_range_7d') }}</span>
                            <x-loading compact class="absolute" wire:loading wire:target="setRange('7d')" aria-label="Loading analytics" />
                        </button>
                        <button type="button" wire:click="setRange('30d')"
                            wire:loading.attr="disabled" wire:target="setRange"
                            @class([$tabButtonBase, $range === '30d' ? $tabButtonActive : $tabButtonInactive])>
                            <span wire:loading.class="invisible" wire:target="setRange('30d')">{{ __('anl_range_30d') }}</span>
                            <x-loading compact class="absolute" wire:loading wire:target="setRange('30d')" aria-label="Loading analytics" />
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Nudge: enabled-eligible servers that haven't turned traffic analytics on yet. --}}
    @if ($scopedServerUuid === null && ! empty($eligibleDisabledServers))
        <div wire:key="analytics-traffic-nudge" x-data="{ dismissed: localStorage.getItem('traffic-nudge-{{ $nudgeKey }}') === '1' }" x-show="!dismissed" x-cloak
            class="flex items-start gap-3 rounded-xl border border-neutral-200 bg-white px-4 py-3 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.025]">
            <div class="min-w-0 flex-1">
                <p class="text-[12px] font-semibold text-black dark:text-fg">
                    {{ count($eligibleDisabledServers) === 1 ? __('anl_nudge_1') : __('anl_nudge_n', ['count' => count($eligibleDisabledServers)]) }}
                </p>
                <p class="mt-0.5 text-[11px] text-neutral-500 dark:text-fg-dim">
                    {{ __('anl_nudge_desc') }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @if (count($eligibleDisabledServers) === 1)
                    <a class="button" href="{{ route('server.analytics', ['server_uuid' => $eligibleDisabledServers[0]['uuid']]) }}" {{ wireNavigate() }}>
                        {{ __('anl_nudge_setup', ['server' => \Illuminate\Support\Str::limit($eligibleDisabledServers[0]['name'], 16)]) }}
                    </a>
                @else
                    <a class="button" href="{{ route('server.index') }}" {{ wireNavigate() }}>
                        {{ __('nav.servers') }}
                    </a>
                @endif
                <button type="button" title="{{ __('anl_dismiss') }}"
                    @click="dismissed = true; localStorage.setItem('traffic-nudge-{{ $nudgeKey }}', '1')"
                    class="flex h-6 w-6 items-center justify-center rounded-md text-neutral-400 transition-colors hover:text-black dark:text-fg-faint dark:hover:text-fg">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if ($servers->isEmpty())
        @if ($scopedServerUuid === null)
            <x-empty size="sm" title="{{ __('anl_empty_not_enabled') }}"
                description="{{ __('anl_empty_not_enabled_desc') }}"
                icon-name="analytics">
                <x-slot:contents>
                    <a class="button" href="{{ route('server.index') }}" {{ wireNavigate() }}>
                        {{ __('nav.servers') }}
                    </a>
                </x-slot:contents>
            </x-empty>
        @endif
    @elseif (! $overview)
        <x-empty size="sm" title="{{ __('anl_empty_no_data') }}"
            description="{{ __('anl_empty_no_data_desc') }}"
            icon-name="analytics" />
    @else
        @if ($this->isLivePollable())
            <div wire:poll.60s="loadData" class="hidden"></div>
        @endif

        {{-- KPIs --}}
        <x-application.settings-section id="analytics-overview-section" title="{{ __('anl_overview') }}"
            helper="{{ __('anl_overview_helper') }}">
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-lg bg-neutral-200 sm:grid-cols-3 lg:grid-cols-5 dark:bg-white/[0.07]">
                <div class="flex flex-col bg-[var(--coollabs-base)] px-4 py-3">
                    <span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">{{ __('anl_requests') }}</span>
                    <span class="mt-1 text-xl font-semibold text-black tabular-nums dark:text-fg">{{ number_format($overview['requests'] ?? 0) }}</span>
                    <div class="mt-auto pt-3">
                        @include('livewire.traffic._sparkline', [
                            'id' => $chartId.'-spark-requests',
                            'initial' => $this->requestsSpark(),
                            'colorVar' => '--chart-status-3xx',
                            'event' => 'refreshChartData-'.$chartId.'-status',
                            'key' => 'requestsSpark',
                        ])
                    </div>
                </div>
                <div class="flex flex-col bg-[var(--coollabs-base)] px-4 py-3">
                    <span class="flex items-center text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">
                        {{ __('anl_unique_visitors') }}
                        @if ($uniquesApproximate)
                            {!! $approxBadge(__('anl_approx_visitors_tip')) !!}
                        @endif
                    </span>
                    <span class="mt-1 text-xl font-semibold text-black tabular-nums dark:text-fg">{{ number_format($overview['uniqueVisitors'] ?? 0) }}</span>
                    <div class="mt-auto pt-3">
                        @include('livewire.traffic._sparkline', [
                            'id' => $chartId.'-spark-visitors',
                            'initial' => $this->uniquesSpark(),
                            'colorVar' => '--chart-status-2xx',
                            'event' => 'refreshChartData-'.$chartId.'-status',
                            'key' => 'uniquesSpark',
                        ])
                    </div>
                </div>
                <div class="flex flex-col bg-[var(--coollabs-base)] px-4 py-3">
                    <span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">{{ __('anl_bandwidth') }}</span>
                    <span class="mt-1 text-xl font-semibold text-black tabular-nums dark:text-fg">{{ formatBytes($this->bandwidthBytes()) }}</span>
                    <div class="mt-auto pt-3">
                        @include('livewire.traffic._sparkline', [
                            'id' => $chartId.'-spark-bandwidth',
                            'initial' => $this->bandwidthSpark(),
                            'colorVar' => '--chart-spark-bandwidth',
                            'event' => 'refreshChartData-'.$chartId.'-status',
                            'key' => 'bandwidthSpark',
                        ])
                    </div>
                </div>
                <div class="flex flex-col bg-[var(--coollabs-base)] px-4 py-3">
                    <span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">{{ __('anl_error_rate') }}</span>
                    <span class="mt-1 text-xl font-semibold text-black tabular-nums dark:text-fg">{{ $this->errorRate() }}%</span>
                    <div class="mt-auto pt-3">
                        @include('livewire.traffic._sparkline', [
                            'id' => $chartId.'-spark-errors',
                            'initial' => $this->errorsSpark(),
                            'colorVar' => '--chart-status-5xx',
                            'event' => 'refreshChartData-'.$chartId.'-status',
                            'key' => 'errorsSpark',
                        ])
                    </div>
                </div>
                <div class="col-span-2 flex flex-col bg-[var(--coollabs-base)] px-4 py-3 sm:col-span-1">
                    <span class="flex items-center text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">
                        {{ __('anl_p95_latency') }}
                        @if ($latencyApproximate)
                            {!! $approxBadge(__('anl_p95_tip')) !!}
                        @endif
                    </span>
                    <span class="mt-1 text-xl font-semibold text-black tabular-nums dark:text-fg">{{ number_format($overview['latencyP95'] ?? 0, 1) }} ms</span>
                    <div class="mt-auto pt-3">
                        @include('livewire.traffic._sparkline', [
                            'id' => $chartId.'-spark-latency',
                            'initial' => $this->latencySpark(),
                            'colorVar' => '--chart-status-4xx',
                            'event' => 'refreshChartData-'.$chartId.'-status',
                            'key' => 'latencySpark',
                        ])
                    </div>
                </div>
            </div>
        </x-application.settings-section>

        {{-- Requests over time (single area series). --}}
        <x-application.settings-section id="analytics-requests-section" title="{{ __('anl_requests') }}" flush
            helper="{{ __('anl_requests_helper') }}">
            @include('livewire.traffic._requests-chart')
        </x-application.settings-section>

        {{-- Status codes: stacked bar of responses by status class. --}}
        <x-application.settings-section id="analytics-status-codes-section" title="{{ __('anl_status_codes') }}"
            helper="{{ __('anl_status_codes_helper') }}">
            @include('livewire.traffic._status-codes')
        </x-application.settings-section>

        {{-- Top applications + Top hosts side by side; Top paths spans full width below.
             When filtered to one app, only Top paths applies. --}}
        <div @class(['grid grid-cols-1 gap-6', 'lg:grid-cols-2' => $appUuid === ''])>
            @if ($appUuid === '')
                <x-application.settings-section id="analytics-hosts-section" title="{{ __('anl_top_hosts') }}"
                    helper="{{ __('anl_top_hosts_helper') }}" flush>
                    @include('livewire.traffic._hosts-list', ['hosts' => $topHosts])
                </x-application.settings-section>

                <x-application.settings-section id="analytics-apps-section" title="{{ __('anl_top_apps') }}"
                    helper="{{ __('anl_top_apps_helper') }}" flush>
                    @if (empty($topApps))
                        <x-empty size="sm" title="{{ __('anl_no_app_data') }}"
                            description="{{ __('anl_no_app_data_desc') }}"
                            icon-name="unordered-list" />
                    @else
                        @php $maxAppRequests = max(1, (int) collect($topApps)->max('requests')); @endphp
                        <div x-data="{ page: 0, per: 10, total: {{ count($topApps) }} }">
                            <div class="flex items-center gap-3 border-b border-neutral-200 px-4 py-2 text-[11px] font-medium text-neutral-500 dark:border-white/[0.07] dark:text-fg-dim">
                                <span class="min-w-0 flex-1">{{ __('anl_app_service_col') }}</span>
                                <span class="hidden w-16 shrink-0 text-right sm:inline" title="{{ __('anl_volume_tip') }}">{{ __('anl_volume_col') }}</span>
                                <span class="w-16 shrink-0 text-right">{{ __('anl_requests_col') }}</span>
                                <span class="hidden w-16 shrink-0 text-right sm:inline" title="{{ __('anl_bandwidth_tip') }}">{{ __('anl_bandwidth_col') }}</span>
                                <span class="size-3.5 shrink-0" aria-hidden="true"></span>
                            </div>
                            @foreach ($topApps as $row)
                                @php
                                    $appHref = $row['link'] ?? null;
                                    $appWidth = min(100, round(($row['requests'] / $maxAppRequests) * 100, 1));
                                @endphp
                                <{{ $appHref ? 'a' : 'div' }} wire:key="analytics-app-{{ $row['uuid'] }}"
                                    @if ($appHref) href="{{ $appHref }}" {{ wireNavigate() }} @endif
                                    x-show="{{ $loop->index }} >= page * per && {{ $loop->index }} < (page + 1) * per"
                                    @class([
                                        'flex min-h-11 items-center gap-3 border-b border-neutral-200 px-4 py-2 last:border-b-0 dark:border-white/[0.07]',
                                        'transition-colors hover:bg-neutral-50 dark:hover:bg-white/[0.03]' => $appHref,
                                    ])>
                                    <span class="flex min-w-0 flex-1 items-baseline gap-1.5">
                                        <span class="truncate text-[12px] text-black dark:text-fg">{{ $row['name'] }}</span>
                                        @if (! empty($row['isService']))
                                            <span class="shrink-0 text-[10px] font-medium tracking-wide text-neutral-400 uppercase dark:text-fg-faint">{{ __('anl_service_badge') }}</span>
                                        @endif
                                        @if (! empty($row['domain']))
                                            <span class="hidden truncate text-[11px] text-neutral-400 sm:inline dark:text-fg-faint">{{ $row['domain'] }}</span>
                                        @endif
                                    </span>
                                    <div class="hidden h-1 w-16 shrink-0 overflow-hidden rounded-full bg-neutral-100 sm:block dark:bg-white/[0.06]">
                                        <div class="h-full rounded-full bg-[var(--chart-status-3xx)]" style="width: {{ $appWidth }}%;"></div>
                                    </div>
                                    <span class="w-16 shrink-0 text-right text-[12px] font-medium tabular-nums text-black dark:text-fg"
                                        title="{{ __('anl_requests_count', ['count' => number_format($row['requests'])]) }}">{{ compactNumber($row['requests']) }}</span>
                                    <span class="hidden w-16 shrink-0 text-right text-[11px] tabular-nums text-neutral-400 sm:inline dark:text-fg-faint">{{ formatBytes($row['bandwidth']) }}</span>
                                    @if ($appHref)
                                        <svg class="size-3.5 shrink-0 text-neutral-300 dark:text-fg-faint" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    @else
                                        <span class="size-3.5 shrink-0" aria-hidden="true"></span>
                                    @endif
                                </{{ $appHref ? 'a' : 'div' }}>
                            @endforeach
                            @include('livewire.traffic._pager')
                        </div>
                    @endif
                </x-application.settings-section>
            @endif
        </div>

        {{-- Top paths (full width). --}}
        <x-application.settings-section id="analytics-paths-section" title="{{ __('anl_top_paths') }}"
            helper="{{ __('anl_top_paths_helper') }}" flush>
            @include('livewire.traffic._paths-list', ['paths' => $topPaths])
        </x-application.settings-section>

        {{-- Countries --}}
        <x-application.settings-section id="analytics-country-section" title="{{ __('anl_countries') }}"
            helper="{{ __('anl_countries_helper') }}" flush>
            @include('livewire.traffic._geo', [
                'countries' => data_get($breakdowns, 'country', []),
                'attribution' => $attribution,
            ])
        </x-application.settings-section>

        {{-- Requests by device type (donut) + HTTP versions / cache / status. --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-application.settings-section id="analytics-device-section" title="{{ __('anl_by_device') }}"
                helper="{{ __('anl_by_device_helper') }}" flush>
                @php $deviceChart = $this->deviceChartData(); @endphp
                @include('livewire.traffic._device-chart', [
                    'labels' => $deviceChart['labels'],
                    'series' => $deviceChart['series'],
                ])
            </x-application.settings-section>

            @include('livewire.traffic._breakdown-section', [
                'dimension' => 'protocol',
                'label' => __('anl_top_http_versions'),
                'rows' => data_get($breakdowns, 'protocol', []),
                'helper' => __('anl_top_http_versions_helper'),
            ])
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @include('livewire.traffic._breakdown-section', [
                'dimension' => 'cache',
                'label' => __('anl_top_cache_statuses'),
                'rows' => data_get($breakdowns, 'cache', []),
                'helper' => __('anl_top_cache_statuses_helper'),
            ])
            @include('livewire.traffic._breakdown-section', [
                'dimension' => 'status',
                'label' => __('anl_top_status_codes'),
                'rows' => data_get($breakdowns, 'status', []),
                'helper' => __('anl_top_status_codes_helper'),
            ])
        </div>

        {{-- Referrers / browsers / OS / AI agents / IPs, two per row. --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @foreach ($dimensionLabels as $dimension => $label)
                @include('livewire.traffic._breakdown-section', [
                    'dimension' => $dimension,
                    'label' => $label,
                    'rows' => data_get($breakdowns, $dimension, []),
                ])
            @endforeach
            @include('livewire.traffic._breakdown-section', [
                'dimension' => 'agent',
                'label' => __('anl_ai_agents'),
                'rows' => data_get($breakdowns, 'agent', []),
                'helper' => __('anl_ai_agents_helper'),
            ])
            @include('livewire.traffic._breakdown-section', [
                'dimension' => 'ip',
                'label' => __('anl_top_ips'),
                'rows' => data_get($breakdowns, 'ip', []),
                'helper' => __('anl_top_ips_helper'),
            ])
        </div>

        {{-- User agents (full width — raw UA strings are long). --}}
        @include('livewire.traffic._breakdown-section', [
            'dimension' => 'useragent',
            'label' => __('anl_top_uas'),
            'rows' => data_get($breakdowns, 'useragent', []),
            'helper' => __('anl_top_uas_helper'),
        ])
    @endif
</div>
