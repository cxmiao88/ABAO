<div class="relative">
    @php
        $st = $stats;
        $memPct = $st ? round($st['mem_total'] > 0 ? $st['mem_used'] / $st['mem_total'] * 100 : 0, 1) : 0;
        $diskPct = $st ? round($st['disk_total'] > 0 ? $st['disk_used'] / $st['disk_total'] * 100 : 0, 1) : 0;
        $cpuPct = $st ? round($st['cpu']) : 0;
        $loadPct = $st ? min(100, round($st['load'] * 20)) : 0;
        $fmtBytes = fn ($b) => $b >= 1073741824 ? round($b / 1073741824, 1) . ' GB' : ($b >= 1048576 ? round($b / 1048576, 1) . ' MB' : round($b / 1024, 1) . ' KB');
        $loadDesc = $st ? ($st['load'] < 1 ? '运行流畅' : ($st['load'] < 2 ? '负载正常' : '负载偏高')) : '等待服务器';
    @endphp

    {{-- ===== 环形状态条：负载 / CPU / 内存 / 磁盘 ===== --}}
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $rings = [
                ['label' => '负载', 'pct' => $loadPct, 'big' => $st ? number_format($st['load'], 1) . '%' : '--', 'sub' => $loadDesc, 'gid' => 'ring-load', 'c1' => '#a78bfa', 'c2' => '#7c3aed', 'glow' => 'rgba(139,92,246,0.55)', 'txt' => 'rgba(167,139,250,0.6)'],
                ['label' => 'CPU', 'pct' => $cpuPct, 'big' => $st ? number_format($st['cpu'], 1) . '%' : '--', 'sub' => $st ? '当前使用率' : '等待服务器', 'gid' => 'ring-cpu', 'c1' => '#22d3ee', 'c2' => '#0ea5e9', 'glow' => 'rgba(34,211,238,0.55)', 'txt' => 'rgba(34,211,238,0.6)'],
                ['label' => '内存', 'pct' => $memPct, 'big' => $st ? round($memPct) . '%' : '--', 'sub' => $st ? $fmtBytes($st['mem_used']) . ' / ' . $fmtBytes($st['mem_total']) : '等待服务器', 'gid' => 'ring-mem', 'c1' => '#34d399', 'c2' => '#10b981', 'glow' => 'rgba(52,211,153,0.55)', 'txt' => 'rgba(52,211,153,0.6)'],
                ['label' => '磁盘', 'pct' => $diskPct, 'big' => $st ? round($diskPct) . '%' : '--', 'sub' => $st ? $fmtBytes($st['disk_used']) . ' / ' . $fmtBytes($st['disk_total']) : '等待服务器', 'gid' => 'ring-disk', 'c1' => '#fbbf24', 'c2' => '#f59e0b', 'glow' => 'rgba(251,191,36,0.55)', 'txt' => 'rgba(251,191,36,0.6)'],
            ];
        @endphp
        @foreach ($rings as $ring)
            <div class="group relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] backdrop-blur-xl transition-all duration-300 hover:-translate-y-0.5 hover:border-cyan-400/30 hover:shadow-[0_0_36px_rgba(34,211,238,0.10)]">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/40 to-transparent"></div>
                <div class="absolute -right-10 -top-10 size-32 rounded-full bg-cyan-500/[0.07] blur-3xl transition-opacity duration-300 group-hover:opacity-100"></div>
                <div class="relative flex items-center gap-4">
                    <div class="relative size-[76px] shrink-0">
                        <svg viewBox="0 0 42 42" class="size-[76px] -rotate-90">
                            <defs>
                                <linearGradient id="{{ $ring['gid'] }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="{{ $ring['c1'] }}" />
                                    <stop offset="100%" stop-color="{{ $ring['c2'] }}" />
                                </linearGradient>
                            </defs>
                            <circle cx="21" cy="21" r="17" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="4.5" />
                            <circle cx="21" cy="21" r="17" fill="none" stroke="url(#{{ $ring['gid'] }})" stroke-width="4.5"
                                stroke-linecap="round" stroke-dasharray="{{ max($ring['pct'], 2) }} {{ 100 - $ring['pct'] }}"
                                style="transition: stroke-dasharray .8s ease; filter: drop-shadow(0 0 7px {{ $ring['glow'] }})" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center text-[17px] font-bold tracking-tight text-white" style="text-shadow: 0 0 12px {{ $ring['txt'] }}">{{ $ring['pct'] }}%</div>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-neutral-500">{{ $ring['label'] }}</div>
                        <div class="mt-1 text-[26px] font-bold leading-none tracking-tight text-white">{{ $ring['big'] }}</div>
                        <div class="mt-1.5 truncate text-[11px] text-neutral-500">{{ $ring['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== 概览：网站 / 数据库 / 安全风险 / 备忘录 ===== --}}
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $ov = [
                ['label' => '网站', 'val' => $overview['applications'], 'icon' => 'globe', 'cls' => 'bg-violet-500/15 text-violet-300 border-violet-400/20'],
                ['label' => '数据库', 'val' => $overview['databases'], 'icon' => 'database', 'cls' => 'bg-sky-500/15 text-sky-300 border-sky-400/20'],
                ['label' => '安全风险', 'val' => 0, 'icon' => 'shield-alert', 'cls' => 'bg-amber-500/15 text-amber-300 border-amber-400/20'],
            ];
        @endphp
        @foreach ($ov as $o)
            <div class="group relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] backdrop-blur-xl transition-all duration-300 hover:-translate-y-0.5 hover:border-white/[0.14] hover:shadow-[0_0_30px_rgba(255,255,255,0.05)]">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/25 to-transparent"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-neutral-500">{{ $o['label'] }}</div>
                        <div class="mt-2 bg-gradient-to-b from-white via-white to-cyan-100/60 bg-clip-text text-[34px] font-bold leading-none tracking-tight text-transparent drop-shadow-[0_0_14px_rgba(34,211,238,0.25)]">{{ $o['val'] }}</div>
                    </div>
                    <div class="flex size-9 items-center justify-center rounded-lg border {{ $o['cls'] }}">
                        <x-reicon name="{{ $o['icon'] }}" class="size-4.5" />
                    </div>
                </div>
            </div>
        @endforeach
        {{-- 备忘录 --}}
        <div class="group relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] backdrop-blur-xl transition-all duration-300 hover:border-white/[0.14]" x-data="panelMemo()">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/25 to-transparent"></div>
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-neutral-500">备忘录</div>
                <button type="button" class="text-[11px] font-medium text-cyan-400 opacity-0 transition-opacity group-hover:opacity-100 hover:underline" x-show="!editing" @click="editing = true">编辑</button>
            </div>
            <template x-if="!editing">
                <div class="mt-2 cursor-pointer truncate text-[13px] text-neutral-300 transition-colors hover:text-white" @click="editing = true" x-text="memo || '当前内容为空，点击编辑'"></div>
            </template>
            <template x-if="editing">
                <textarea x-model="memo" @blur="save" @keydown.enter.prevent="save"
                    class="mt-2 w-full resize-none rounded-lg border border-white/[0.1] bg-[#0d1220]/80 px-2.5 py-2 text-[12px] text-neutral-200 outline-none transition-colors focus:border-cyan-400/50"
                    rows="2" placeholder="记录点什么..."></textarea>
            </template>
        </div>
    </div>

    {{-- ===== 流量 + 软件推荐 ===== --}}
    <div class="grid grid-cols-1 gap-3 xl:grid-cols-3">
        {{-- 流量 --}}
        <div class="relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] backdrop-blur-xl xl:col-span-2">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/30 to-transparent"></div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-[13px]">
                    <span class="font-semibold text-white">流量</span>
                    <span class="text-neutral-700">|</span>
                    <span class="text-neutral-500">磁盘IO</span>
                    <span class="text-neutral-700">|</span>
                    <span class="text-neutral-500">网卡</span>
                    <span class="rounded-md border border-cyan-400/20 bg-cyan-500/10 px-2 py-0.5 text-[11px] text-cyan-300">所有</span>
                </div>
                <span class="text-[11px] text-neutral-600">{{ $st ? '实时采样' : '等待服务器连接' }}</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @php
                    $flows = [
                        ['label' => '上行', 'icon' => 'arrow-right', 'cls' => 'bg-violet-500/15 text-violet-300 border-violet-400/20'],
                        ['label' => '下行', 'icon' => 'arrow-right', 'cls' => 'bg-sky-500/15 text-sky-300 border-sky-400/20 rotate-180'],
                        ['label' => '总发送', 'icon' => 'upload', 'cls' => 'bg-emerald-500/15 text-emerald-300 border-emerald-400/20'],
                        ['label' => '总接收', 'icon' => 'download', 'cls' => 'bg-amber-500/15 text-amber-300 border-amber-400/20'],
                    ];
                @endphp
                @foreach ($flows as $f)
                    <div class="flex items-center gap-2.5 rounded-lg border border-white/[0.06] bg-white/[0.02] px-3 py-2.5 backdrop-blur-sm">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-md border {{ $f['cls'] }}">
                            <x-reicon name="{{ $f['icon'] }}" class="size-3.5 {{ str_contains($f['cls'], 'rotate-180') ? 'rotate-180' : '' }}" />
                        </span>
                        <div class="min-w-0">
                            <div class="text-[10px] text-neutral-500">{{ $f['label'] }}</div>
                            <div class="text-[13px] font-semibold text-neutral-100">--</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div wire:ignore x-data="trafficChart()" class="mt-3">
                <div x-ref="chart" class="h-48"></div>
            </div>
        </div>

        {{-- 软件推荐 --}}
        <div class="relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] backdrop-blur-xl">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-400/30 to-transparent"></div>
            <div class="flex items-center gap-3 text-[13px]">
                <span class="font-semibold text-white">软件</span>
                <span class="rounded-md border border-violet-400/20 bg-violet-500/10 px-2 py-0.5 text-[11px] text-violet-300">推荐</span>
            </div>
            <div class="mt-4 space-y-2.5">
                @php
                    $softwares = [
                        ['name' => 'WAF', 'desc' => '网站防火墙', 'status' => '开发中', 'btn' => '预览', 'href' => null],
                        ['name' => '网站监控报表', 'desc' => '访问统计报表', 'status' => '开发中', 'btn' => '预览', 'href' => null],
                        ['name' => '企业级防篡改', 'desc' => '文件防篡改', 'status' => '开发中', 'btn' => '购买', 'href' => null],
                        ['name' => '防入侵', 'desc' => '入侵检测防护', 'status' => '开发中', 'btn' => '购买', 'href' => null],
                        ['name' => '网站统计', 'desc' => '数据分析报表', 'status' => '已上线', 'btn' => '进入', 'href' => route('analytics')],
                    ];
                @endphp
                @foreach ($softwares as $sw)
                    <div class="group/sw flex items-center justify-between rounded-lg border border-white/[0.06] bg-white/[0.02] px-3 py-2.5 backdrop-blur-sm transition-all duration-200 hover:border-cyan-400/30 hover:bg-cyan-500/[0.06]">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-violet-400/20 bg-violet-500/10 text-violet-300">
                                <x-reicon name="grid" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <div class="truncate text-[13px] font-medium text-neutral-100">{{ $sw['name'] }}</div>
                                <div class="truncate text-[10px] text-neutral-500">{{ $sw['desc'] }}</div>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="text-[10px] {{ $sw['status'] === '已上线' ? 'text-emerald-400' : 'text-neutral-600' }}">{{ $sw['status'] }}</span>
                            @if ($sw['href'])
                                <a href="{{ $sw['href'] }}" wire:navigate class="rounded-md border border-white/[0.1] px-2.5 py-1 text-[11px] font-medium text-neutral-200 transition-colors hover:border-cyan-400/50 hover:bg-cyan-500/15 hover:text-cyan-200">{{ $sw['btn'] }}</a>
                            @else
                                <button type="button" class="rounded-md border border-white/[0.1] px-2.5 py-1 text-[11px] font-medium text-neutral-300 transition-colors hover:border-white/[0.2] hover:bg-white/[0.06] hover:text-white" title="模块开发中">{{ $sw['btn'] }}</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== 底部特权条 ===== --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 overflow-hidden rounded-xl border border-cyan-400/20 bg-gradient-to-r from-cyan-500/[0.10] via-violet-500/[0.06] to-transparent px-5 py-3.5 backdrop-blur-xl">
        <div class="flex flex-wrap items-center gap-3 text-[12px] text-neutral-300">
            <button type="button" class="rounded-lg bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-1.5 text-[12px] font-semibold text-white shadow-lg shadow-cyan-500/30 transition-all hover:shadow-cyan-400/40 hover:brightness-110">立即开通</button>
            <span class="font-semibold text-white">专业版VIP特权</span>
            <span class="hidden text-neutral-700 sm:inline">|</span>
            @foreach (['全部宝塔式功能解锁', '7×24 极速响应', '专属技术支持', '优先功能迭代'] as $i => $vip)
                <span class="text-neutral-400">{{ $vip }}</span>
                @if ($i < 3)<span class="hidden text-neutral-700 sm:inline">|</span>@endif
            @endforeach
        </div>
        <span class="text-[11px] text-neutral-600">ABao 面板 © 2026</span>
    </div>

    @script
    <script>
        Alpine.data('trafficChart', () => ({
            init() {
                const el = this.$refs.chart;
                if (!el) return;
                new ApexCharts(el, {
                    chart: {
                        type: 'area',
                        height: 195,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        background: 'transparent',
                        foreColor: '#8a8a8a',
                        fontFamily: 'inherit',
                        animations: { enabled: true, speed: 800 },
                    },
                    series: [{ name: '上行', data: [] }, { name: '下行', data: [] }],
                    colors: ['#22d3ee', '#8b5cf6'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] } },
                    grid: { borderColor: 'rgba(255,255,255,0.05)', strokeDashArray: 4 },
                    xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { show: false } },
                    legend: { show: false },
                    tooltip: { theme: 'dark' },
                    noData: { text: '接入服务器后显示实时流量趋势', align: 'center', verticalAlign: 'middle', style: { color: '#666666', fontSize: '12px', fontWeight: 500 } },
                }).render();
            }
        }));

        Alpine.data('panelMemo', () => ({
            memo: localStorage.getItem('panel_memo') || '',
            editing: false,
            save() {
                this.editing = false;
                localStorage.setItem('panel_memo', this.memo || '');
            }
        }));
    </script>
    @endscript
</div>
