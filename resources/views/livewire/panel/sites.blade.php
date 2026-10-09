<div>
    {{-- 网站列表 --}}
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">网站</h2>
            <p class="text-[12px] text-neutral-500">共 {{ $sites->count() }} 个站点</p>
        </div>
        @if ($firstProject = \App\Models\Project::ownedByCurrentTeam()->first())
            @if ($firstProject->environments->first())
                <a href="{{ route('project.resource.create', ['project_uuid' => $firstProject->uuid, 'environment_uuid' => $firstProject->environments->first()->uuid]) }}" wire:navigate
                    class="rounded-lg bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-[13px] font-semibold text-white shadow-lg shadow-cyan-500/25 transition-all hover:brightness-110">添加网站</a>
            @endif
        @endif
    </div>

    <div class="relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] backdrop-blur-xl">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/30 to-transparent"></div>
        @if ($sites->isEmpty())
            <div class="flex flex-col items-center justify-center py-16">
                <x-reicon name="globe" class="size-10 text-neutral-600" />
                <div class="mt-3 text-[14px] font-medium text-neutral-300">暂无网站</div>
                <div class="mt-1 text-[12px] text-neutral-500">点击右上角"添加网站"创建第一个站点</div>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-white/[0.06] text-[11px] uppercase tracking-wider text-neutral-500">
                        <th class="px-4 py-3 font-semibold">站点名称</th>
                        <th class="px-4 py-3 font-semibold">域名</th>
                        <th class="px-4 py-3 font-semibold">仓库</th>
                        <th class="px-4 py-3 font-semibold">分支</th>
                        <th class="px-4 py-3 font-semibold">环境</th>
                        <th class="px-4 py-3 text-right font-semibold">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sites as $site)
                        <tr class="border-b border-white/[0.04] transition-colors last:border-0 hover:bg-white/[0.03]">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex size-8 items-center justify-center rounded-lg border border-cyan-400/20 bg-cyan-500/10 text-cyan-300">
                                        <x-reicon name="globe" class="size-4" />
                                    </span>
                                    <span class="text-[13px] font-medium text-neutral-100">{{ $site->name }}</span>
                                </div>
                            </td>
                            <td class="max-w-[180px] truncate px-4 py-3 text-[12px] text-neutral-400">{{ $site->fqdn ?: '—' }}</td>
                            <td class="max-w-[160px] truncate px-4 py-3 text-[12px] text-neutral-400">{{ $site->git_repository ?: '—' }}</td>
                            <td class="px-4 py-3 text-[12px] text-neutral-400">{{ $site->git_branch ?: '—' }}</td>
                            <td class="px-4 py-3 text-[12px] text-neutral-400">{{ $site->environment?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($site->environment?->project)
                                    <a href="{{ route('project.application.configuration', ['project_uuid' => $site->environment->project->uuid, 'environment_uuid' => $site->environment->uuid, 'application_uuid' => $site->uuid]) }}" wire:navigate
                                        class="rounded-md border border-white/[0.1] px-3 py-1.5 text-[11px] font-medium text-neutral-300 transition-colors hover:border-cyan-400/50 hover:bg-cyan-500/15 hover:text-cyan-200">管理</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
