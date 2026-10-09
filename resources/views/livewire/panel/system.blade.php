<div>
    @php
        $st = $stats;
        $srv = $server;
    @endphp

    <div class="mb-4">
        <h2 class="text-lg font-bold text-white">系统</h2>
        <p class="text-[12px] text-neutral-500">服务器系统信息</p>
    </div>

    <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
        {{-- 服务器信息 --}}
        <div class="relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] p-5 backdrop-blur-xl">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/30 to-transparent"></div>
            <div class="mb-4 text-[13px] font-semibold text-neutral-100">服务器信息</div>
            <div class="space-y-3">
                @foreach ([
                    ['label' => '服务器名称', 'val' => $srv?->name ?? '—'],
                    ['label' => 'IP 地址', 'val' => $srv?->ip ?? '—'],
                    ['label' => 'SSH 端口', 'val' => $srv?->port ?? '—'],
                    ['label' => '登录用户', 'val' => $srv?->user ?? '—'],
                    ['label' => '描述', 'val' => $srv?->description ?? '—'],
                    ['label' => '代理类型', 'val' => $srv?->proxy_type ?? '—'],
                ] as $item)
                    <div class="flex items-center justify-between rounded-lg border border-white/[0.06] bg-white/[0.02] px-4 py-2.5">
                        <span class="text-[12px] text-neutral-500">{{ $item['label'] }}</span>
                        <span class="text-[13px] font-medium text-neutral-100">{{ $item['val'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 运行状态 --}}
        <div class="relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] p-5 backdrop-blur-xl">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-400/30 to-transparent"></div>
            <div class="mb-4 text-[13px] font-semibold text-neutral-100">运行状态</div>
            @if ($srv)
                <div class="space-y-3">
                    <div class="flex items-center justify-between rounded-lg border border-white/[0.06] bg-white/[0.02] px-4 py-2.5">
                        <span class="text-[12px] text-neutral-500">可达性</span>
                        <span class="flex items-center gap-1.5 text-[12px] {{ $srv->settings->is_reachable ? 'text-emerald-400' : 'text-amber-400' }}">
                            <span class="size-1.5 rounded-full {{ $srv->settings->is_reachable ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                            {{ $srv->settings->is_reachable ? '可达' : '不可达' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-white/[0.06] bg-white/[0.02] px-4 py-2.5">
                        <span class="text-[12px] text-neutral-500">可用性</span>
                        <span class="flex items-center gap-1.5 text-[12px] {{ $srv->settings->is_usable ? 'text-emerald-400' : 'text-amber-400' }}">
                            <span class="size-1.5 rounded-full {{ $srv->settings->is_usable ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                            {{ $srv->settings->is_usable ? '可用' : '不可用' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-white/[0.06] bg-white/[0.02] px-4 py-2.5">
                        <span class="text-[12px] text-neutral-500">运行时间</span>
                        <span class="text-[13px] font-medium text-neutral-100">{{ $st ? ($st['uptime'] >= 86400 ? round($st['uptime'] / 86400, 1) . ' 天' : round($st['uptime'] / 3600, 1) . ' 小时') : '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-white/[0.06] bg-white/[0.02] px-4 py-2.5">
                        <span class="text-[12px] text-neutral-500">CPU 核心</span>
                        <span class="text-[13px] font-medium text-neutral-100">{{ $st ? '—' : '等待服务器连接' }}</span>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12">
                    <x-reicon name="cpu" class="size-10 text-neutral-600" />
                    <div class="mt-3 text-[13px] text-neutral-400">暂无服务器</div>
                </div>
            @endif
        </div>
    </div>
</div>
