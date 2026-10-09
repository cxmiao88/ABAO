<div>
    @php
        $st = $stats;
        $memPct = $st ? round($st['mem_total'] > 0 ? $st['mem_used'] / $st['mem_total'] * 100 : 0, 1) : 0;
        $diskPct = $st ? round($st['disk_total'] > 0 ? $st['disk_used'] / $st['disk_total'] * 100 : 0, 1) : 0;
        $cpuPct = $st ? round($st['cpu']) : 0;
        $loadPct = $st ? min(100, round($st['load'] * 20)) : 0;
        $fmtBytes = fn ($b) => $b >= 1073741824 ? round($b / 1073741824, 1) . ' GB' : ($b >= 1048576 ? round($b / 1048576, 1) . ' MB' : round($b / 1024, 1) . ' KB');
    @endphp

    <div class="mb-4">
        <h2 class="text-lg font-bold text-white">监控</h2>
        <p class="text-[12px] text-neutral-500">{{ $server?->name ?? '—' }} · {{ $server?->ip ?? '—' }}</p>
    </div>

    @if ($st)
        {{-- 大号环形监控面板 --}}
        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $rings = [
                    ['label' => '负载', 'pct' => $loadPct, 'val' => number_format($st['load'], 2), 'gid' => 'mon-load', 'c1' => '#a78bfa', 'c2' => '#7c3aed', 'glow' => 'rgba(139,92,246,0.55)'],
                    ['label' => 'CPU', 'pct' => $cpuPct, 'val' => number_format($st['cpu'], 1) . '%', 'gid' => 'mon-cpu', 'c1' => '#22d3ee', 'c2' => '#0ea5e9', 'glow' => 'rgba(34,211,238,0.55)'],
                    ['label' => '内存', 'pct' => $memPct, 'val' => $fmtBytes($st['mem_used']) . ' / ' . $fmtBytes($st['mem_total']), 'gid' => 'mon-mem', 'c1' => '#34d399', 'c2' => '#10b981', 'glow' => 'rgba(52,211,153,0.55)'],
                    ['label' => '磁盘', 'pct' => $diskPct, 'val' => $fmtBytes($st['disk_used']) . ' / ' . $fmtBytes($st['disk_total']), 'gid' => 'mon-disk', 'c1' => '#fbbf24', 'c2' => '#f59e0b', 'glow' => 'rgba(251,191,36,0.55)'],
                ];
            @endphp
            @foreach ($rings as $ring)
                <div class="relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] p-6 backdrop-blur-xl">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/25 to-transparent"></div>
                    <div class="flex flex-col items-center gap-3">
                        <div class="relative size-28">
                            <svg viewBox="0 0 42 42" class="size-28 -rotate-90">
                                <defs>
                                    <linearGradient id="{{ $ring['gid'] }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="{{ $ring['c1'] }}" />
                                        <stop offset="100%" stop-color="{{ $ring['c2'] }}" />
                                    </linearGradient>
                                </defs>
                                <circle cx="21" cy="21" r="17" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="4.5" />
                                <circle cx="21" cy="21" r="17" fill="none" stroke="url(#{{ $ring['gid'] }})" stroke-width="4.5"
                                    stroke-linecap="round" stroke-dasharray="{{ max($ring['pct'], 2) }} {{ 100 - $ring['pct'] }}"
                                    style="transition: stroke-dasharray .8s ease; filter: drop-shadow(0 0 8px {{ $ring['glow'] }})" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center text-[22px] font-bold text-white" style="text-shadow: 0 0 14px {{ $ring['glow'] }}">{{ $ring['pct'] }}%</div>
                        </div>
                        <div class="text-center">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-neutral-500">{{ $ring['label'] }}</div>
                            <div class="mt-1 text-[15px] font-semibold text-neutral-100">{{ $ring['val'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 负载详情 --}}
        <div class="relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] p-5 backdrop-blur-xl">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-400/30 to-transparent"></div>
            <div class="mb-4 text-[13px] font-semibold text-neutral-100">负载与运行详情</div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => '负载（1 分钟）', 'val' => number_format($st['load'], 2)],
                    ['label' => '负载（5 分钟）', 'val' => number_format($st['load5'], 2)],
                    ['label' => '负载（15 分钟）', 'val' => number_format($st['load15'], 2)],
                    ['label' => '运行时间', 'val' => $st['uptime'] >= 86400 ? round($st['uptime'] / 86400, 1) . ' 天' : round($st['uptime'] / 3600, 1) . ' 小时'],
                ] as $item)
                    <div class="rounded-lg border border-white/[0.06] bg-white/[0.02] px-4 py-3">
                        <div class="text-[11px] text-neutral-500">{{ $item['label'] }}</div>
                        <div class="mt-1 text-[18px] font-bold text-white">{{ $item['val'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center rounded-xl border border-white/[0.08] bg-white/[0.03] py-20 backdrop-blur-xl">
            <x-reicon name="analytics" class="size-10 text-neutral-600" />
            <div class="mt-3 text-[14px] font-medium text-neutral-300">服务器暂时无法连接</div>
            <div class="mt-1 text-[12px] text-neutral-500">接入真实服务器后显示实时监控数据</div>
        </div>
    @endif
</div>
