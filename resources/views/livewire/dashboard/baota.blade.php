<div class="space-y-6">
    @php
        $panelFeatures = [
            ['label' => '网站', 'icon' => 'globe', 'desc' => '站点与应用管理'],
            ['label' => '数据库', 'icon' => 'database', 'desc' => '数据库实例管理'],
            ['label' => '文件', 'icon' => 'folder', 'desc' => '服务器文件管理'],
            ['label' => '安全中心', 'icon' => 'shield-alert', 'desc' => '防火墙与端口'],
            ['label' => '监控', 'icon' => 'analytics', 'desc' => '资源历史趋势'],
            ['label' => '终端', 'icon' => 'terminal', 'desc' => '在线 Web 终端'],
            ['label' => '日志', 'icon' => 'file-content', 'desc' => '系统与容器日志'],
            ['label' => '计划任务', 'icon' => 'calendar', 'desc' => '定时任务管理'],
        ];
    @endphp

    {{-- 入口横幅 --}}
    <div class="relative overflow-hidden rounded-2xl border border-violet-500/30 bg-gradient-to-br from-violet-600/15 via-transparent to-transparent p-8 dark:border-violet-400/20">
        <div class="flex flex-col items-start gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-2xl font-bold text-neutral-900 dark:text-white">宝塔面板</div>
                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">ABao 平台内置的宝塔式服务器管理面板，与 Coolify 原版功能并存，同一账号统一管理。</div>
            </div>
            <a href="{{ route('panel.home') }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-violet-500 hover:shadow-md">
                进入宝塔面板
                <x-reicon name="arrow-right" class="size-4" />
            </a>
        </div>
    </div>

    {{-- 功能模块预览 --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
        @foreach ($panelFeatures as $f)
            <div class="rounded-xl border border-neutral-200 bg-white p-4 transition-colors hover:border-violet-400/40 dark:border-white/[0.08] dark:bg-white/[0.04]">
                <div class="flex size-9 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400">
                    <x-reicon name="{{ $f['icon'] }}" class="size-4.5" />
                </div>
                <div class="mt-3 text-sm font-semibold text-neutral-900 dark:text-white">{{ $f['label'] }}</div>
                <div class="mt-0.5 text-[12px] text-neutral-500 dark:text-neutral-400">{{ $f['desc'] }}</div>
            </div>
        @endforeach
    </div>
</div>
