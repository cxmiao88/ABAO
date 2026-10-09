#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os

BASE = r"G:\xianmu\juqing\baoUIIT\resources\views"

def sub(path, pairs):
    p = os.path.join(BASE, path)
    c = open(p, encoding="utf-8").read()
    for old, new in pairs:
        if old not in c:
            print("MISS:", path, "->", old[:60])
        c = c.replace(old, new)
    open(p, "w", encoding="utf-8").write(c)
    print("OK:", path)

# ---------- analytics.blade.php ----------
sub(r"livewire\analytics.blade.php", [
    ("'referer' => 'Referrers',", "'referer' => __('anl_referrers'),"),
    ("'browser' => 'Browsers',", "'browser' => __('anl_browsers'),"),
    ("'os' => 'Operating systems',", "'os' => __('anl_os'),"),
    ("$approxBadge = fn (string $tooltip) => '<span title=\"'.e($tooltip).'\" class=\"ml-1.5 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-medium tracking-wide text-amber-700 uppercase dark:bg-amber-500/10 dark:text-amber-400\">~ approximate</span>';",
     "$approxBadge = fn (string $tooltip) => '<span title=\"'.e($tooltip).'\" class=\"ml-1.5 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-medium tracking-wide text-amber-700 uppercase dark:bg-amber-500/10 dark:text-amber-400\">'.__('anl_approx_badge').'</span>';"),
    ("[['value' => '', 'label' => 'All servers']]", "[['value' => '', 'label' => __('anl_all_servers')]]"),
    ("[['value' => '', 'label' => 'All resources']]", "[['value' => '', 'label' => __('anl_all_resources')]]"),
    ("            Analytics | ABao\n", "            {{ __('nav.analytics') }} | ABao\n"),
    ('<h1 class="min-w-0 text-[24px]! leading-7! font-semibold! tracking-tight!">Analytics</h1>',
     '<h1 class="min-w-0 text-[24px]! leading-7! font-semibold! tracking-tight!">{{ __(\'nav.analytics\') }}</h1>'),
    ("Request traffic across every application and server, reported by Sentinel.", "{{ __('anl_header_desc') }}"),
    ('placeholder="All servers"', 'placeholder="{{ __(\'anl_all_servers\') }}"'),
    ('placeholder="All resources"', 'placeholder="{{ __(\'anl_all_resources\') }}"'),
    ('<span wire:loading.class="invisible" wire:target="setRange(\'24h\')">24 hours</span>',
     '<span wire:loading.class="invisible" wire:target="setRange(\'24h\')">{{ __(\'anl_range_24h\') }}</span>'),
    ('<span wire:loading.class="invisible" wire:target="setRange(\'7d\')">7 days</span>',
     '<span wire:loading.class="invisible" wire:target="setRange(\'7d\')">{{ __(\'anl_range_7d\') }}</span>'),
    ('<span wire:loading.class="invisible" wire:target="setRange(\'30d\')">30 days</span>',
     '<span wire:loading.class="invisible" wire:target="setRange(\'30d\')">{{ __(\'anl_range_30d\') }}</span>'),
    ("{{ count($eligibleDisabledServers) === 1 ? '1 server can start collecting traffic analytics' : count($eligibleDisabledServers).' servers can start collecting traffic analytics' }}",
     "{{ count($eligibleDisabledServers) === 1 ? __('anl_nudge_1') : __('anl_nudge_n', ['count' => count($eligibleDisabledServers)]) }}"),
    ("Enabling regenerates the proxy config and restarts the proxy + Sentinel (a brief blip).\n                    Works with Traefik &amp; Caddy.",
     "{{ __('anl_nudge_desc') }}"),
    ("Set up on {{ \\Illuminate\\Support\\Str::limit($eligibleDisabledServers[0]['name'], 16) }}",
     "{{ __('anl_nudge_setup', ['server' => \\Illuminate\\Support\\Str::limit($eligibleDisabledServers[0]['name'], 16)]) }}"),
    ("View servers", "{{ __('nav.servers') }}"),
    ('title="Dismiss"', 'title="{{ __(\'anl_dismiss\') }}"'),
    ('<x-empty size="sm" title="Traffic analytics is not enabled"\n                description="Enable traffic analytics on a server to see request analytics here."',
     '<x-empty size="sm" title="{{ __(\'anl_empty_not_enabled\') }}"\n                description="{{ __(\'anl_empty_not_enabled_desc\') }}"'),
    ('<x-empty size="sm" title="No analytics data yet"\n            description="We could not load traffic analytics for the selected filters and range. Try a different range or check back shortly."',
     '<x-empty size="sm" title="{{ __(\'anl_empty_no_data\') }}"\n            description="{{ __(\'anl_empty_no_data_desc\') }}"'),
    ('<x-application.settings-section id="analytics-overview-section" title="Overview"\n            helper="Aggregate request volume for the selected filters and range.">',
     '<x-application.settings-section id="analytics-overview-section" title="{{ __(\'anl_overview\') }}"\n            helper="{{ __(\'anl_overview_helper\') }}">'),
    ('<span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">Requests</span>\n                    <span class="mt-1 text-xl font-semibold text-black tabular-nums dark:text-fg">{{ number_format($overview[\'requests\'] ?? 0) }}</span>',
     '<span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">{{ __(\'anl_requests\') }}</span>\n                    <span class="mt-1 text-xl font-semibold text-black tabular-nums dark:text-fg">{{ number_format($overview[\'requests\'] ?? 0) }}</span>'),
    ("Unique visitors\n", "{{ __('anl_unique_visitors') }}\n"),
    ("$approxBadge('Summed across servers or services; visitors seen on more than one may be double-counted.')",
     "$approxBadge(__('anl_approx_visitors_tip'))"),
    ('<span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">Bandwidth</span>',
     '<span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">{{ __(\'anl_bandwidth\') }}</span>'),
    ('<span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">Error rate</span>',
     '<span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">{{ __(\'anl_error_rate\') }}</span>'),
    ("p95 latency\n", "{{ __('anl_p95_latency') }}\n"),
    ("$approxBadge('Highest p95 latency across servers or services; not a true merged percentile.')",
     "$approxBadge(__('anl_p95_tip'))"),
    ('<x-application.settings-section id="analytics-requests-section" title="Requests" flush\n            helper="Total request volume over time for the selected range.">',
     '<x-application.settings-section id="analytics-requests-section" title="{{ __(\'anl_requests\') }}" flush\n            helper="{{ __(\'anl_requests_helper\') }}">'),
    ('<x-application.settings-section id="analytics-status-codes-section" title="Status codes"\n            helper="Share of responses by HTTP status class for the selected range.">',
     '<x-application.settings-section id="analytics-status-codes-section" title="{{ __(\'anl_status_codes\') }}"\n            helper="{{ __(\'anl_status_codes_helper\') }}">'),
    ('<x-application.settings-section id="analytics-hosts-section" title="Top hosts"\n                    helper="Served hostnames ranked by request volume." flush>',
     '<x-application.settings-section id="analytics-hosts-section" title="{{ __(\'anl_top_hosts\') }}"\n                    helper="{{ __(\'anl_top_hosts_helper\') }}" flush>'),
    ('<x-application.settings-section id="analytics-apps-section" title="Top applications"\n                    helper="Applications and services ranked by request volume. Open one for its analytics." flush>',
     '<x-application.settings-section id="analytics-apps-section" title="{{ __(\'anl_top_apps\') }}"\n                    helper="{{ __(\'anl_top_apps_helper\') }}" flush>'),
    ('<x-empty size="sm" title="No application data"\n                            description="No per-application requests were recorded for the selected range."',
     '<x-empty size="sm" title="{{ __(\'anl_no_app_data\') }}"\n                            description="{{ __(\'anl_no_app_data_desc\') }}"'),
    ('<span class="min-w-0 flex-1">Application / service</span>',
     '<span class="min-w-0 flex-1">{{ __(\'anl_app_service_col\') }}</span>'),
    ('<span class="hidden w-16 shrink-0 text-right sm:inline" title="Request volume relative to the busiest row in this list">Volume</span>',
     '<span class="hidden w-16 shrink-0 text-right sm:inline" title="{{ __(\'anl_volume_tip\') }}">{{ __(\'anl_volume_col\') }}</span>'),
    ('<span class="w-16 shrink-0 text-right">Requests</span>',
     '<span class="w-16 shrink-0 text-right">{{ __(\'anl_requests_col\') }}</span>'),
    ('<span class="hidden w-16 shrink-0 text-right sm:inline" title="Total response data sent">Bandwidth</span>',
     '<span class="hidden w-16 shrink-0 text-right sm:inline" title="{{ __(\'anl_bandwidth_tip\') }}">{{ __(\'anl_bandwidth_col\') }}</span>'),
    ('<span class="shrink-0 text-[10px] font-medium tracking-wide text-neutral-400 uppercase dark:text-fg-faint">Service</span>',
     '<span class="shrink-0 text-[10px] font-medium tracking-wide text-neutral-400 uppercase dark:text-fg-faint">{{ __(\'anl_service_badge\') }}</span>'),
    ('title="{{ number_format($row[\'requests\']) }} requests"',
     'title="{{ __(\'anl_requests_count\', [\'count\' => number_format($row[\'requests\'])]) }}"'),
    ('<x-application.settings-section id="analytics-paths-section" title="Top paths"\n            helper="Most requested paths for the selected range." flush>',
     '<x-application.settings-section id="analytics-paths-section" title="{{ __(\'anl_top_paths\') }}"\n            helper="{{ __(\'anl_top_paths_helper\') }}" flush>'),
    ('<x-application.settings-section id="analytics-country-section" title="Countries"\n            helper="Request volume by visitor country for the selected range." flush>',
     '<x-application.settings-section id="analytics-country-section" title="{{ __(\'anl_countries\') }}"\n            helper="{{ __(\'anl_countries_helper\') }}" flush>'),
    ('<x-application.settings-section id="analytics-device-section" title="Requests by device type"\n                helper="Share of requests by client device class for the selected range.">',
     '<x-application.settings-section id="analytics-device-section" title="{{ __(\'anl_by_device\') }}"\n                helper="{{ __(\'anl_by_device_helper\') }}" flush>'),
    ("'label' => 'Top HTTP versions',", "'label' => __('anl_top_http_versions'),"),
    ("'helper' => 'Request volume by negotiated HTTP protocol version.',", "'helper' => __('anl_top_http_versions_helper'),"),
    ("'label' => 'Top cache statuses',", "'label' => __('anl_top_cache_statuses'),"),
    ("'helper' => 'Reverse-proxy cache outcome (hit, miss, bypass, …) by request count.',", "'helper' => __('anl_top_cache_statuses_helper'),"),
    ("'label' => 'Top status codes',", "'label' => __('anl_top_status_codes'),"),
    ("'helper' => 'Most frequent HTTP response status codes for the selected range.',", "'helper' => __('anl_top_status_codes_helper'),"),
    ("'label' => 'AI agents & bots',", "'label' => __('anl_ai_agents'),"),
    ("'helper' => 'Bot and AI-crawler traffic (GPTBot, ClaudeBot, Googlebot, …) by request count.',", "'helper' => __('anl_ai_agents_helper'),"),
    ("'label' => 'Top IPs',", "'label' => __('anl_top_ips'),"),
    ("'helper' => 'Busiest client IPs (real visitor IP, resolved behind Cloudflare / reverse proxies).',", "'helper' => __('anl_top_ips_helper'),"),
    ("'label' => 'Top user agents',", "'label' => __('anl_top_uas'),"),
    ("'helper' => 'Most frequent raw User-Agent strings for the selected range.',", "'helper' => __('anl_top_uas_helper'),"),
])

# ---------- _breakdown-section.blade.php ----------
sub(r"livewire\traffic\_breakdown-section.blade.php", [
    ("$helper = $helper ?? 'Top '.strtolower($label).' by request count for the selected range.';",
     "$helper = $helper ?? __('anl_section_helper_default', ['label' => strtolower($label)]);"),
    ('<x-empty size="sm" title="No data"', '<x-empty size="sm" title="{{ __(\'anl_no_data\') }}"'),
    (":description=\"'No '.strtolower($label).' data for the selected range.'\"", ":description=\"__('anl_no_data_desc', ['label' => strtolower($label)])\""),
    ("? 'Other'", "? __('anl_other')"),
    ("'referer' => $host ?? 'Direct / none',", "'referer' => $host ?? __('anl_direct_none'),"),
    ("'status' => $value === '0' ? 'No response status (0)' : ($value !== '' ? $value : 'Unknown'),",
     "'status' => $value === '0' ? __('anl_no_status') : ($value !== '' ? $value : __('anl_unknown')),"),
    ("default => $value !== '' ? $value : 'Unknown',", "default => $value !== '' ? $value : __('anl_unknown'),"),
    ('title="{{ number_format($requests) }} requests"', 'title="{{ __(\'anl_requests_count\', [\'count\' => number_format($requests)]) }}"'),
])

# ---------- _live-toggle.blade.php ----------
sub(r"livewire\traffic\_live-toggle.blade.php", [
    ('title="Toggle realtime refresh (updates every 60s)"', 'title="{{ __(\'anl_live_toggle_tip\') }}"'),
    ("<span>Live Refresh</span>", "<span>{{ __('anl_live_refresh') }}</span>"),
])

# ---------- _pager.blade.php ----------
sub(r"livewire\traffic\_pager.blade.php", [
    ("x-text=\"`${page * per + 1}–${Math.min((page + 1) * per, total)} of ${total.toLocaleString()}`\"",
     "x-text=\"`${page * per + 1}–${Math.min((page + 1) * per, total)} {{ __('anl_of') }} ${total.toLocaleString()}`\""),
    (">\n            Prev\n        </button>", ">\n            {{ __('anl_prev') }}\n        </button>"),
    (">\n            Next\n        </button>", ">\n            {{ __('anl_next') }}\n        </button>"),
])

# ---------- _hosts-list.blade.php ----------
sub(r"livewire\traffic\_hosts-list.blade.php", [
    ('<x-empty size="sm" title="No host data" description="No hostnames were recorded for the selected range."',
     '<x-empty size="sm" title="{{ __(\'anl_no_host_data\') }}" description="{{ __(\'anl_no_host_data_desc\') }}"'),
    ('<span class="min-w-0 flex-1">Host</span>', '<span class="min-w-0 flex-1">{{ __(\'anl_host_col\') }}</span>'),
    ('<span class="hidden w-16 shrink-0 text-right sm:inline" title="Request volume relative to the busiest row in this list">Volume</span>',
     '<span class="hidden w-16 shrink-0 text-right sm:inline" title="{{ __(\'anl_volume_tip\') }}">{{ __(\'anl_volume_col\') }}</span>'),
    ('<span class="w-16 shrink-0 text-right">Requests</span>', '<span class="w-16 shrink-0 text-right">{{ __(\'anl_requests_col\') }}</span>'),
    ('<span class="hidden w-16 shrink-0 text-right sm:inline" title="Total response data sent">Bandwidth</span>',
     '<span class="hidden w-16 shrink-0 text-right sm:inline" title="{{ __(\'anl_bandwidth_tip\') }}">{{ __(\'anl_bandwidth_col\') }}</span>'),
    ("Unknown host", "{{ __('anl_unknown_host') }}"),
    ('title="{{ number_format($requests) }} requests"', 'title="{{ __(\'anl_requests_count\', [\'count\' => number_format($requests)]) }}"'),
])

# ---------- _paths-list.blade.php ----------
sub(r"livewire\traffic\_paths-list.blade.php", [
    ('<x-empty size="sm" title="No path data" description="No requests were recorded for the selected range."',
     '<x-empty size="sm" title="{{ __(\'anl_no_path_data\') }}" description="{{ __(\'anl_no_path_data_desc\') }}"'),
    ('<span class="min-w-0 flex-1">Path</span>', '<span class="min-w-0 flex-1">{{ __(\'anl_path_col\') }}</span>'),
    ('<span class="hidden w-16 shrink-0 text-right sm:inline" title="Request volume relative to the busiest row in this list">Volume</span>',
     '<span class="hidden w-16 shrink-0 text-right sm:inline" title="{{ __(\'anl_volume_tip\') }}">{{ __(\'anl_volume_col\') }}</span>'),
    ('<span class="w-16 shrink-0 text-right">Requests</span>', '<span class="w-16 shrink-0 text-right">{{ __(\'anl_requests_col\') }}</span>'),
    ('<span class="hidden w-14 shrink-0 text-right sm:inline" title="HTTP 4xx client-error responses">4xx errors</span>',
     '<span class="hidden w-14 shrink-0 text-right sm:inline" title="{{ __(\'anl_4xx_tip\') }}">{{ __(\'anl_4xx_col\') }}</span>'),
    ('<span class="w-14 shrink-0 text-right" title="HTTP 5xx server-error responses">5xx errors</span>',
     '<span class="w-14 shrink-0 text-right" title="{{ __(\'anl_5xx_tip\') }}">{{ __(\'anl_5xx_col\') }}</span>'),
    ('<span class="hidden w-12 shrink-0 text-right lg:inline" title="Percentage of requests with a 4xx or 5xx response">Error %</span>',
     '<span class="hidden w-12 shrink-0 text-right lg:inline" title="{{ __(\'anl_error_pct_tip\') }}">{{ __(\'anl_error_pct_col\') }}</span>'),
    ('<span class="hidden w-16 shrink-0 text-right sm:inline" title="Total response data sent">Bandwidth</span>',
     '<span class="hidden w-16 shrink-0 text-right sm:inline" title="{{ __(\'anl_bandwidth_tip\') }}">{{ __(\'anl_bandwidth_col\') }}</span>'),
    ('<span class="hidden w-16 shrink-0 text-right md:inline" title="95% of requests completed within this response time">p95 latency</span>',
     '<span class="hidden w-16 shrink-0 text-right md:inline" title="{{ __(\'anl_p95_tip_col\') }}">{{ __(\'anl_p95_latency_col\') }}</span>'),
    ('title="{{ number_format($requests) }} requests"', 'title="{{ __(\'anl_requests_count\', [\'count\' => number_format($requests)]) }}"'),
    ('title="{{ number_format($s4xx) }} client-error responses"', 'title="{{ __(\'anl_4xx_count_tip\', [\'count\' => number_format($s4xx)]) }}"'),
    ('title="{{ number_format($s5xx) }} server-error responses"', 'title="{{ __(\'anl_5xx_count_tip\', [\'count\' => number_format($s5xx)]) }}"'),
    ('title="Combined 4xx and 5xx response rate"', 'title="{{ __(\'anl_error_rate_tip\') }}"'),
    ('title="p95 latency"', 'title="{{ __(\'anl_p95_row_tip\') }}"'),
])

# ---------- _requests-chart.blade.php ----------
sub(r"livewire\traffic\_requests-chart.blade.php", [
    ('<x-empty size="sm" title="No requests in this range"\n            description="No request traffic was recorded for the selected filters and range. Try a wider range or check back later."',
     '<x-empty size="sm" title="{{ __(\'anl_no_requests\') }}"\n            description="{{ __(\'anl_no_requests_desc\') }}"'),
    ("series: [{ name: 'Requests', data: initialPoints }],", "series: [{ name: @js(__('anl_requests')), data: initialPoints }],"),
    ("chart.updateSeries([{ name: 'Requests', data: points }]);", "chart.updateSeries([{ name: @js(__('anl_requests')), data: points }]);"),
    ("text: 'Loading requests…',", "text: @js(__('anl_loading_requests')),"),
    ("<div class=\"apexcharts-tooltip-custom-value\">Requests: <span class=\"apexcharts-tooltip-value-bold\">${requests.toLocaleString()}</span></div>",
     "<div class=\"apexcharts-tooltip-custom-value\">{{ __('anl_tooltip_requests') }} <span class=\"apexcharts-tooltip-value-bold\">${requests.toLocaleString()}</span></div>"),
    ("<div class=\"apexcharts-tooltip-custom-title\">Your time: ${formatLocalTimestamp(timestamp)}</div>",
     "<div class=\"apexcharts-tooltip-custom-title\">{{ __('anl_tooltip_your_time') }} ${formatLocalTimestamp(timestamp)}</div>"),
    ("<div class=\"apexcharts-tooltip-custom-title\">UTC: ${formatUtcTimestamp(timestamp)}</div>",
     "<div class=\"apexcharts-tooltip-custom-title\">{{ __('anl_tooltip_utc') }} ${formatUtcTimestamp(timestamp)}</div>"),
])

# ---------- _status-codes.blade.php ----------
sub(r"livewire\traffic\_status-codes.blade.php", [
    ('title="{{ number_format($code[\'count\']) }} responses"', 'title="{{ __(\'anl_responses_tip\', [\'count\' => number_format($code[\'count\'])]) }}"'),
    ('<span x-text="tip.count"></span> responses · <span x-text="tip.pct"></span>',
     '<span x-text="tip.count"></span> {{ __(\'anl_responses_word\') }} · <span x-text="tip.pct"></span>'),
    ('<x-empty size="sm" title="No status data" description="No responses were recorded for the selected range."',
     '<x-empty size="sm" title="{{ __(\'anl_no_status_data\') }}" description="{{ __(\'anl_no_status_data_desc\') }}"'),
])

# ---------- _geo.blade.php ----------
sub(r"livewire\traffic\_geo.blade.php", [
    ('<x-empty size="sm" title="No data" description="No country data for the selected range."',
     '<x-empty size="sm" title="{{ __(\'anl_no_data\') }}" description="{{ __(\'anl_no_country_data\') }}"'),
    ("{{ $isUnknown ? 'Unknown' : countryName($row['value']) }}",
     "{{ $isUnknown ? __('anl_unknown') : countryName($row['value']) }}"),
    ('title="{{ number_format($row[\'requests\']) }} requests"', 'title="{{ __(\'anl_requests_count\', [\'count\' => number_format($row[\'requests\'])]) }}"'),
])

# ---------- _device-chart.blade.php ----------
sub(r"livewire\traffic\_device-chart.blade.php", [
    ('<x-empty size="sm" title="No device data" description="No device data for the selected range."',
     '<x-empty size="sm" title="{{ __(\'anl_no_device_data\') }}" description="{{ __(\'anl_no_device_data_desc\') }}"'),
    ("text: 'Loading devices…',", "text: @js(__('anl_loading_devices')),"),
    ("<div class=\"apexcharts-tooltip-custom-value\">${label}: <span class=\"apexcharts-tooltip-value-bold\">${requests} requests</span></div>",
     "<div class=\"apexcharts-tooltip-custom-value\">${label}: <span class=\"apexcharts-tooltip-value-bold\">${requests} {{ __('anl_requests_suffix') }}</span></div>"),
])

# ---------- _sparkline.blade.php ----------
sub(r"livewire\traffic\_sparkline.blade.php", [
    ("'requestsSpark' => 'Requests',", "'requestsSpark' => __('anl_spark_requests'),"),
    ("'uniquesSpark' => 'Visitors',", "'uniquesSpark' => __('anl_spark_visitors'),"),
    ("'bandwidthSpark' => 'Bandwidth',", "'bandwidthSpark' => __('anl_spark_bandwidth'),"),
    ("'errorsSpark' => 'Errors',", "'errorsSpark' => __('anl_spark_errors'),"),
    ("'latencySpark' => 'p95 latency',", "'latencySpark' => __('anl_spark_latency'),"),
    ("default => 'Value',", "default => __('anl_spark_value'),"),
])

print("ALL DONE")
