<div>
    <div class="mb-4">
        <h2 class="text-lg font-bold text-white">Docker 容器</h2>
        <p class="text-[12px] text-neutral-500">{{ $server?->name ?? '—' }} · {{ $server?->ip ?? '—' }}</p>
    </div>

    {{-- 实时容器列表（需 SSH） --}}
    <div class="relative mb-4 overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] backdrop-blur-xl">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-sky-400/30 to-transparent"></div>
        <div class="flex items-center justify-between px-4 py-3">
            <div class="text-[13px] font-semibold text-neutral-100">实时容器</div>
            @if ($containers !== null)
                <span class="rounded-md border border-sky-400/20 bg-sky-500/10 px-2 py-0.5 text-[11px] text-sky-300">{{ count($containers) }} 个运行中</span>
            @else
                <span class="text-[11px] text-neutral-600">需要连接服务器</span>
            @endif
        </div>
        @if ($containers === null)
            <div class="flex flex-col items-center justify-center py-12">
                <x-reicon name="layers" class="size-10 text-neutral-600" />
                <div class="mt-3 text-[13px] text-neutral-400">接入真实服务器后可查看实时容器状态</div>
                <div class="mt-1 text-[11px] text-neutral-600">本地开发环境未开启 SSH 访问</div>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-white/[0.06] text-[11px] uppercase tracking-wider text-neutral-500">
                        <th class="px-4 py-3 font-semibold">容器名称</th>
                        <th class="px-4 py-3 font-semibold">镜像</th>
                        <th class="px-4 py-3 font-semibold">状态</th>
                        <th class="px-4 py-3 font-semibold">端口</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($containers as $c)
                        <tr class="border-b border-white/[0.04] last:border-0 hover:bg-white/[0.03]">
                            <td class="px-4 py-3 text-[13px] text-neutral-100">{{ $c['name'] ?? '—' }}</td>
                            <td class="max-w-[200px] truncate px-4 py-3 text-[12px] text-neutral-400">{{ $c['image'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if (($c['state'] ?? '') === 'running')
                                    <span class="flex items-center gap-1.5 text-[12px] text-emerald-400"><span class="size-1.5 rounded-full bg-emerald-400"></span>运行中</span>
                                @else
                                    <span class="flex items-center gap-1.5 text-[12px] text-neutral-500"><span class="size-1.5 rounded-full bg-neutral-600"></span>{{ $c['state'] ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[12px] text-neutral-400">{{ $c['ports'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- 预期容器（已部署资源） --}}
    <div class="relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] backdrop-blur-xl">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-400/30 to-transparent"></div>
        <div class="px-4 py-3 text-[13px] font-semibold text-neutral-100">已部署资源（{{ $resources->count() }}）</div>
        @if ($resources->isEmpty())
            <div class="px-4 pb-6 text-[12px] text-neutral-500">暂无已部署资源</div>
        @else
            <div class="grid grid-cols-1 gap-2.5 px-4 pb-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($resources as $r)
                    <div class="flex items-center gap-2.5 rounded-lg border border-white/[0.06] bg-white/[0.02] px-3 py-2.5">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-violet-400/20 bg-violet-500/10 text-violet-300">
                            <x-reicon name="layers" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[13px] font-medium text-neutral-100">{{ $r['name'] }}</div>
                            <div class="text-[10px] text-neutral-500">{{ $r['type'] }}</div>
                        </div>
                        @if ($r['status'])
                            <span class="size-1.5 rounded-full bg-emerald-400"></span>
                        @else
                            <span class="size-1.5 rounded-full bg-neutral-600"></span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
