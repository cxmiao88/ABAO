<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.metrics_title') }} | ABao
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="metrics" />

        <div class="application-settings-form flex w-full flex-col gap-6"
            @if ($server->isMetricsEnabled()) x-init="$wire.loadData()"
                @if ($poll) wire:poll.5000ms="pollData" @endif
            @endif>
            @if ($server->isMetricsEnabled())
                <form wire:submit.prevent="saveMetricsSettings" class="contents">
                    <x-unsaved-bar action="saveMetricsSettings"
                        targets="sentinelMetricsRefreshRateSeconds,sentinelMetricsHistoryDays,sentinelPushIntervalSeconds" />

                    <x-application.settings-section id="server-metrics-overview-section" :title="__('server.metrics_title')"
                        :helper="__('server.metrics_helper')">
                        <x-slot:actions>
                            <div class="flex items-center gap-2">
                                <x-status-badge :status="$poll ? __('server.metrics_live') : __('server.metrics_historical')"
                                    :type="$poll ? 'success' : 'neutral'" />
                                <x-forms.button canGate="update" :canResource="$server" wire:click="toggleMetrics">
                                    {{ __('server.metrics_disable') }}
                                </x-forms.button>
                            </div>
                        </x-slot:actions>

                        <div class="grid gap-4 lg:grid-cols-3">
                            <x-forms.input canGate="update" :canResource="$server" type="number" min="1"
                                id="sentinelMetricsRefreshRateSeconds" :label="__('server.metrics_collection_rate')" required
                                :helper="__('server.metrics_collection_rate_helper')" />
                            <x-forms.input canGate="update" :canResource="$server" type="number" min="1"
                                id="sentinelMetricsHistoryDays" :label="__('server.metrics_history_retention')" required
                                :helper="__('server.metrics_history_helper')" />
                            <x-forms.input canGate="update" :canResource="$server" type="number" min="10"
                                id="sentinelPushIntervalSeconds" :label="__('server.metrics_push_interval')" required
                                :helper="__('server.metrics_push_helper')" />
                        </div>

                        <div class="mt-4 max-w-xs">
                            <x-forms.listbox id="interval" :label="__('server.metrics_time_range')" onChange="setInterval" :options="[
                                ['value' => 5, 'label' => __('server.metrics_range_5m_live')],
                                ['value' => 10, 'label' => __('server.metrics_range_10m_live')],
                                ['value' => 30, 'label' => __('server.metrics_range_30m')],
                                ['value' => 60, 'label' => __('server.metrics_range_1h')],
                                ['value' => 720, 'label' => __('server.metrics_range_12h')],
                                ['value' => 10080, 'label' => __('server.metrics_range_1w')],
                                ['value' => 43200, 'label' => __('server.metrics_range_30d')],
                            ]" />
                        </div>
                        <p class="mt-3 text-xs leading-5 text-neutral-500 dark:text-fg-dim">
                            {{ __('server.metrics_live_refresh_note') }}
                        </p>
                    </x-application.settings-section>
                </form>

                <x-application.settings-section id="server-cpu-metrics-section" :title="__('server.metrics_cpu_title')"
                    :helper="__('server.metrics_cpu_helper')">
                    <div wire:ignore id="{!! $chartId !!}-cpu" class="min-h-[240px] w-full"></div>
                </x-application.settings-section>

                <x-application.settings-section id="server-memory-metrics-section" :title="__('server.metrics_memory_title')"
                    :helper="__('server.metrics_memory_helper')">
                    <div wire:ignore id="{!! $chartId !!}-memory" class="min-h-[240px] w-full"></div>
                </x-application.settings-section>

                @script
                    <script>
                        (() => {
                            checkTheme();

                            const formatPercent = value => {
                                const number = Number(value);
                                const precision = Math.abs(number) < 1 ? 2 : 1;

                                return `${Number(number.toFixed(precision))}%`;
                            };

                            const formatLocalTimestamp = timestamp => new Date(timestamp).toLocaleString(undefined, {
                                hour12: false,
                                timeZoneName: 'short',
                            });
                            const formatUtcTimestamp = timestamp => new Date(timestamp).toLocaleString(undefined, {
                                hour12: false,
                                timeZone: 'UTC',
                                timeZoneName: 'short',
                            });

                            const chartOptions = (name, color, loadingText) => ({
                                chart: {
                                    height: 240,
                                    type: 'area',
                                    toolbar: {
                                        show: false
                                    },
                                    zoom: {
                                        enabled: false
                                    },
                                    animations: {
                                        enabled: true
                                    },
                                    background: 'transparent',
                                },
                                series: [{
                                    name,
                                    data: [],
                                }],
                                colors: [color],
                                stroke: {
                                    curve: 'smooth',
                                    width: 2,
                                },
                                fill: {
                                    type: 'gradient',
                                    gradient: {
                                        opacityFrom: 0.28,
                                        opacityTo: 0.02,
                                        stops: [0, 90, 100],
                                    },
                                },
                                dataLabels: {
                                    enabled: false,
                                },
                                grid: {
                                    borderColor: 'rgba(128, 128, 128, 0.14)',
                                    strokeDashArray: 4,
                                },
                                legend: {
                                    show: false,
                                },
                                xaxis: {
                                    type: 'datetime',
                                    labels: {
                                        datetimeUTC: false,
                                        style: {
                                            colors: textColor,
                                        },
                                    },
                                },
                                yaxis: {
                                    min: 0,
                                    max: max => max > 0 ? max * 1.2 : 1,
                                    forceNiceScale: true,
                                    tickAmount: 4,
                                    labels: {
                                        style: {
                                            colors: textColor,
                                        },
                                        formatter: formatPercent,
                                    },
                                },
                                noData: {
                                    text: loadingText,
                                    style: {
                                        color: textColor,
                                    },
                                },
                                tooltip: {
                                    shared: false,
                                    intersect: false,
                                    followCursor: false,
                                    fixed: {
                                        enabled: false,
                                    },
                                    marker: {
                                        show: false,
                                    },
                                    custom: ({
                                        series,
                                        seriesIndex,
                                        dataPointIndex,
                                        w
                                    }) => {
                                        const value = series[seriesIndex][dataPointIndex];
                                        const timestamp = w.globals.seriesX[seriesIndex][dataPointIndex];

                                        return `<div class="apexcharts-tooltip-custom">
                                            <div class="apexcharts-tooltip-custom-value">${name}: <span class="apexcharts-tooltip-value-bold">${formatPercent(value)}</span></div>
                                            <div class="apexcharts-tooltip-custom-title">Your time: ${formatLocalTimestamp(timestamp)}</div>
                                            <div class="apexcharts-tooltip-custom-title">UTC: ${formatUtcTimestamp(timestamp)}</div>
                                        </div>`;
                                    },
                                },
                            });

                            const cpuChart = new ApexCharts(
                                document.getElementById('{!! $chartId !!}-cpu'),
                                chartOptions('{{ __('server.metrics_cpu') }}', cpuColor, '{{ __('server.metrics_loading_cpu') }}'),
                            );
                            const memoryChart = new ApexCharts(
                                document.getElementById('{!! $chartId !!}-memory'),
                                chartOptions('{{ __('server.metrics_memory') }}', ramColor, '{{ __('server.metrics_loading_memory') }}'),
                            );

                            cpuChart.render();
                            memoryChart.render();

                            Livewire.on('refreshChartData-{!! $chartId !!}-metrics', chartData => {
                                checkTheme();
                                const data = Array.isArray(chartData) ? chartData[0] : chartData;

                                cpuChart.updateOptions({
                                    colors: [cpuColor],
                                    series: [{
                                        name: 'CPU',
                                        data: data.cpuSeries,
                                    }],
                                    xaxis: {
                                        type: 'datetime',
                                        labels: {
                                            datetimeUTC: false,
                                            style: {
                                                colors: textColor,
                                            },
                                        },
                                    },
                                    yaxis: {
                                        min: 0,
                                        max: max => max > 0 ? max * 1.2 : 1,
                                        forceNiceScale: true,
                                        tickAmount: 4,
                                        labels: {
                                            style: {
                                                colors: textColor,
                                            },
                                            formatter: formatPercent,
                                        },
                                    },
                                    noData: {
                                        text: '{{ __('server.metrics_no_cpu_data') }}',
                                        style: {
                                            color: textColor,
                                        },
                                    },
                                });
                                memoryChart.updateOptions({
                                    colors: [ramColor],
                                    series: [{
                                        name: 'Memory',
                                        data: data.memorySeries,
                                    }],
                                    xaxis: {
                                        type: 'datetime',
                                        labels: {
                                            datetimeUTC: false,
                                            style: {
                                                colors: textColor,
                                            },
                                        },
                                    },
                                    yaxis: {
                                        min: 0,
                                        max: max => max > 0 ? max * 1.2 : 1,
                                        forceNiceScale: true,
                                        tickAmount: 4,
                                        labels: {
                                            style: {
                                                colors: textColor,
                                            },
                                            formatter: formatPercent,
                                        },
                                    },
                                    noData: {
                                        text: '{{ __('server.metrics_no_memory_data') }}',
                                        style: {
                                            color: textColor,
                                        },
                                    },
                                });
                            });
                        })();
                    </script>
                @endscript
            @elseif ($server->isSentinelEnabled())
                <x-application.settings-section id="server-metrics-overview-section" :title="__('server.metrics_title')"
                    :helper="__('server.metrics_helper')">
                    <x-empty size="sm" :title="__('server.metrics_disabled_title')"
                        :description="__('server.metrics_disabled_description')"
                        icon-name="dashboard">
                        <x-slot:contents>
                            <x-forms.button canGate="update" :canResource="$server" isHighlighted
                                wire:click="toggleMetrics">
                                {{ __('server.metrics_enable') }}
                            </x-forms.button>
                        </x-slot:contents>
                    </x-empty>
                </x-application.settings-section>
            @else
                <x-application.settings-section id="server-metrics-overview-section" :title="__('server.metrics_title')"
                    :helper="__('server.metrics_helper')">
                    <x-empty size="sm" :title="__('server.metrics_unavailable_title')"
                        :description="__('server.metrics_unavailable_description')"
                        icon-name="dashboard">
                        <x-slot:contents>
                            <a class="button"
                                href="{{ route('server.sentinel', ['server_uuid' => $server->uuid]) }}"
                                {{ wireNavigate() }}>
                                {{ __('server.metrics_view_sentinel') }}
                                <x-external-link />
                            </a>
                        </x-slot:contents>
                    </x-empty>
                </x-application.settings-section>
            @endif

        </div>
    </div>
</div>
