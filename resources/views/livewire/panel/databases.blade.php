<div>
    {{-- 数据库列表 --}}
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">数据库</h2>
            <p class="text-[12px] text-neutral-500">共 {{ $databases->count() }} 个数据库实例</p>
        </div>
        @if ($firstProject = \App\Models\Project::ownedByCurrentTeam()->first())
            @if ($firstProject->environments->first())
                <a href="{{ route('project.resource.create', ['project_uuid' => $firstProject->uuid, 'environment_uuid' => $firstProject->environments->first()->uuid]) }}" wire:navigate
                    class="rounded-lg bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-[13px] font-semibold text-white shadow-lg shadow-cyan-500/25 transition-all hover:brightness-110">添加数据库</a>
            @endif
        @endif
    </div>

    <div class="relative overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.03] backdrop-blur-xl">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-400/30 to-transparent"></div>
        @if ($databases->isEmpty())
            <div class="flex flex-col items-center justify-center py-16">
                <x-reicon name="database" class="size-10 text-neutral-600" />
                <div class="mt-3 text-[14px] font-medium text-neutral-300">暂无数据库</div>
                <div class="mt-1 text-[12px] text-neutral-500">点击右上角"添加数据库"创建数据库实例</div>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-white/[0.06] text-[11px] uppercase tracking-wider text-neutral-500">
                        <th class="px-4 py-3 font-semibold">数据库名称</th>
                        <th class="px-4 py-3 font-semibold">类型</th>
                        <th class="px-4 py-3 font-semibold">状态</th>
                        <th class="px-4 py-3 text-right font-semibold">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($databases as $db)
                        <tr class="border-b border-white/[0.04] transition-colors last:border-0 hover:bg-white/[0.03]">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex size-8 items-center justify-center rounded-lg border border-emerald-400/20 bg-emerald-500/10 text-emerald-300">
                                        <x-reicon name="database" class="size-4" />
                                    </span>
                                    <span class="text-[13px] font-medium text-neutral-100">{{ $db['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-md border border-white/[0.08] bg-white/[0.04] px-2 py-0.5 text-[11px] text-neutral-300">{{ $db['type'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($db['status'])
                                    <span class="flex items-center gap-1.5 text-[12px] text-emerald-400">
                                        <span class="size-1.5 rounded-full bg-emerald-400"></span>运行中
                                    </span>
                                @else
                                    <span class="flex items-center gap-1.5 text-[12px] text-neutral-500">
                                        <span class="size-1.5 rounded-full bg-neutral-600"></span>未部署
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($db['model']->environment?->project)
                                    <a href="{{ route('project.database.configuration', ['project_uuid' => $db['model']->environment->project->uuid, 'environment_uuid' => $db['model']->environment->uuid, 'database_uuid' => $db['uuid']]) }}" wire:navigate
                                        class="rounded-md border border-white/[0.1] px-3 py-1.5 text-[11px] font-medium text-neutral-300 transition-colors hover:border-emerald-400/50 hover:bg-emerald-500/15 hover:text-emerald-200">管理</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
