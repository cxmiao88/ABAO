<div class="mt-4">
    @if ($stats)
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {{-- CPU --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-white/[0.08] dark:bg-white/[0.04]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-neutral-500 dark:text-fg-dim">{{ __('srv_st_cpu') }}</span>
                    <span class="text-lg font-semibold tabular-nums text-neutral-900 dark:text-fg">{{ round($stats['cpu'], 1) }}%</span>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-white/[0.08]">
                    <div class="h-full rounded-full @if ($stats['cpu'] >= 90) bg-red-500 @elseif ($stats['cpu'] >= 75) bg-amber-500 @else bg-emerald-500 @endif"
                        style="width: {{ min($stats['cpu'], 100) }}%"></div>
                </div>
            </div>

            {{-- Memory --}}
            @php
                $memPct = $stats['mem_total'] > 0 ? $stats['mem_used'] / $stats['mem_total'] * 100 : 0;
            @endphp
            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-white/[0.08] dark:bg-white/[0.04]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-neutral-500 dark:text-fg-dim">{{ __('srv_st_memory') }}</span>
                    <span class="text-right">
                        <span class="text-lg font-semibold tabular-nums text-neutral-900 dark:text-fg">{{ round($memPct, 1) }}%</span>
                        <span class="ml-1 text-[11px] tabular-nums text-neutral-400 dark:text-fg-faint">{{ formatBytes($stats['mem_used']) }}/{{ formatBytes($stats['mem_total']) }}</span>
                    </span>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-white/[0.08]">
                    <div class="h-full rounded-full @if ($memPct >= 90) bg-red-500 @elseif ($memPct >= 75) bg-amber-500 @else bg-emerald-500 @endif"
                        style="width: {{ min($memPct, 100) }}%"></div>
                </div>
            </div>

            {{-- Disk --}}
            @php
                $diskPct = $stats['disk_total'] > 0 ? $stats['disk_used'] / $stats['disk_total'] * 100 : 0;
            @endphp
            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-white/[0.08] dark:bg-white/[0.04]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-neutral-500 dark:text-fg-dim">{{ __('srv_st_disk') }}</span>
                    <span class="text-right">
                        <span class="text-lg font-semibold tabular-nums text-neutral-900 dark:text-fg">{{ round($diskPct, 1) }}%</span>
                        <span class="ml-1 text-[11px] tabular-nums text-neutral-400 dark:text-fg-faint">{{ formatBytes($stats['disk_used']) }}/{{ formatBytes($stats['disk_total']) }}</span>
                    </span>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-white/[0.08]">
                    <div class="h-full rounded-full @if ($diskPct >= 90) bg-red-500 @elseif ($diskPct >= 75) bg-amber-500 @else bg-emerald-500 @endif"
                        style="width: {{ min($diskPct, 100) }}%"></div>
                </div>
            </div>

            {{-- Load & uptime --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-white/[0.08] dark:bg-white/[0.04]">
                <span class="text-xs font-medium text-neutral-500 dark:text-fg-dim">{{ __('srv_st_load') }}</span>
                <div class="mt-1 text-lg font-semibold tabular-nums text-neutral-900 dark:text-fg">
                    {{ number_format($stats['load'], 2) }}
                    <span class="text-[11px] font-normal text-neutral-400 dark:text-fg-faint">1 / {{ number_format($stats['load5'], 2) }} / {{ number_format($stats['load15'], 2) }}</span>
                </div>
                <div class="mt-2 text-[11px] text-neutral-400 dark:text-fg-faint">
                    {{ __('srv_st_uptime') }}：{{ formatUptime($stats['uptime']) }}
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-neutral-200 px-4 py-3 text-sm text-neutral-400 dark:border-white/[0.08] dark:text-fg-faint">
            {{ __('srv_st_unavailable') }}
        </div>
    @endif
</div>
