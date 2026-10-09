@extends('layouts.base')
@section('body')
@parent
@php
    $panelNav = [
        ['label' => '首页', 'icon' => 'dashboard', 'route' => 'panel.home', 'active' => request()->routeIs('panel.home')],
        ['label' => '网站', 'icon' => 'globe', 'route' => 'panel.sites', 'active' => request()->routeIs('panel.sites')],
        ['label' => '数据库', 'icon' => 'database', 'route' => 'panel.databases', 'active' => request()->routeIs('panel.databases')],
        ['label' => 'Docker', 'icon' => 'layers', 'route' => 'panel.docker', 'active' => request()->routeIs('panel.docker')],
        ['label' => '监控', 'icon' => 'analytics', 'route' => 'panel.monitoring', 'active' => request()->routeIs('panel.monitoring')],
        ['label' => '系统', 'icon' => 'cpu', 'route' => 'panel.system', 'active' => request()->routeIs('panel.system')],
        ['label' => '安全中心', 'icon' => 'shield-alert', 'route' => null],
        ['label' => 'WAF', 'icon' => 'shield-star', 'route' => null],
        ['label' => '文件', 'icon' => 'folder', 'route' => null],
        ['label' => '日志', 'icon' => 'file-content', 'route' => null],
        ['label' => '域名', 'icon' => 'network', 'route' => null],
        ['label' => 'SSL', 'icon' => 'shield-star', 'route' => null],
        ['label' => '终端', 'icon' => 'terminal', 'route' => null],
        ['label' => 'AI', 'icon' => 'code', 'route' => null],
        ['label' => '节点管理', 'icon' => 'servers', 'route' => null],
        ['label' => '计划任务', 'icon' => 'calendar', 'route' => null],
        ['label' => '软件商店', 'icon' => 'grid', 'route' => null],
        ['label' => '设置', 'icon' => 'settings', 'route' => null],
        ['label' => '退出', 'icon' => 'logout', 'route' => null],
    ];
@endphp
<div class="text-neutral-100">
    <div class="relative flex h-screen overflow-hidden bg-[#0a0e17]">
        {{-- 科技感背景：光晕（无网格） --}}
        <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden">
            <div class="absolute -right-40 -top-40 size-[560px] rounded-full bg-violet-600/[0.14] blur-[130px]"></div>
            <div class="absolute -bottom-40 left-1/4 size-[480px] rounded-full bg-cyan-500/[0.10] blur-[130px]"></div>
            <div class="absolute left-1/2 top-0 size-[300px] -translate-x-1/2 rounded-full bg-blue-500/[0.06] blur-[100px]"></div>
        </div>

        {{-- ===== 左侧科技导航 ===== --}}
        <aside class="relative z-10 flex w-44 shrink-0 flex-col border-r border-white/[0.06] bg-[#0c111e]/90 backdrop-blur-xl">
            <div class="flex items-center gap-2.5 border-b border-white/[0.06] px-4 py-4">
                <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-400 to-violet-500 text-xs font-bold text-white shadow-lg shadow-cyan-500/30">B</div>
                <div class="leading-tight">
                    <div class="text-sm font-bold tracking-wide text-white">ABao 面板</div>
                    <div class="text-[11px] text-neutral-500">宝塔式管理</div>
                </div>
            </div>
            <nav class="flex-1 overflow-y-auto px-2 py-2">
                @foreach ($panelNav as $item)
                    @if ($item['route'])
                        <a href="{{ route($item['route']) }}" {{ request()->routeIs($item['route']) ? '' : 'wire:navigate' }}
                            class="{{ $item['active'] ? 'bg-cyan-500/10 text-cyan-300' : 'text-neutral-400 hover:bg-white/[0.05] hover:text-white' }} relative flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors duration-150">
                            @if ($item['active'])
                                <span class="absolute left-0 top-1/2 h-4 w-0.5 -translate-y-1/2 rounded-full bg-gradient-to-b from-cyan-300 to-violet-400 shadow-[0_0_8px_rgba(34,211,238,0.6)]"></span>
                            @endif
                            <x-reicon name="{{ $item['icon'] }}" class="size-4 {{ $item['active'] ? 'text-cyan-300' : '' }}" />
                            <span class="flex-1">{{ $item['label'] }}</span>
                        </a>
                    @else
                        <div class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium text-neutral-600" title="模块开发中">
                            <x-reicon name="{{ $item['icon'] }}" class="size-4" />
                            <span class="flex-1">{{ $item['label'] }}</span>
                            <span class="rounded bg-white/[0.04] px-1.5 py-0.5 text-[10px] text-neutral-600">开发中</span>
                        </div>
                    @endif
                @endforeach
            </nav>
            <div class="border-t border-white/[0.06] p-2">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium text-neutral-400 hover:bg-white/[0.05] hover:text-white transition-colors">
                    <x-reicon name="servers" class="size-4" />
                    <span>返回原版面板</span>
                </a>
            </div>
        </aside>

        {{-- ===== 右侧内容区 ===== --}}
        <div class="relative z-10 flex min-w-0 flex-1 flex-col">
            {{-- 顶部栏 --}}
            <header class="flex h-11 shrink-0 items-center justify-between border-b border-white/[0.06] bg-[#0c111e]/90 px-4 backdrop-blur-xl">
                <div class="flex items-center gap-2.5 text-[13px]">
                    <span class="flex size-5 items-center justify-center rounded bg-cyan-500/15 text-cyan-300">
                        <x-reicon name="servers" class="size-3" />
                    </span>
                    <span class="font-medium text-neutral-100">{{ $serverInfo['ip'] ?? 'localhost' }}</span>
                    <span class="text-neutral-700">|</span>
                    <span class="text-neutral-400">{{ $serverInfo['account'] ?? 'Preview' }}</span>
                    <span class="text-neutral-700">|</span>
                    <span class="text-neutral-400">{{ $serverInfo['os'] ?? 'Linux' }}</span>
                    <span class="rounded border border-cyan-500/30 bg-cyan-500/10 px-1.5 py-0.5 text-[10px] text-cyan-300">企业版</span>
                </div>
                <div class="flex items-center gap-3 text-[12px] text-neutral-400">
                    <button type="button" title="通知" class="relative text-neutral-400 hover:text-cyan-300 transition-colors">
                        <x-reicon name="notifications" class="size-4" />
                    </button>
                    <span class="rounded border border-white/[0.08] bg-white/[0.04] px-2 py-0.5 text-[11px] text-neutral-300">v2.1</span>
                    <button type="button" class="rounded-md border border-white/[0.08] px-2.5 py-1 text-[12px] text-neutral-300 transition-colors hover:border-cyan-400/40 hover:bg-cyan-500/10 hover:text-cyan-200">更新</button>
                    <button type="button" class="rounded-md border border-white/[0.08] px-2.5 py-1 text-[12px] text-neutral-300 transition-colors hover:border-cyan-400/40 hover:bg-cyan-500/10 hover:text-cyan-200">修复</button>
                    <button type="button" class="rounded-md border border-white/[0.08] px-2.5 py-1 text-[12px] text-neutral-300 transition-colors hover:border-cyan-400/40 hover:bg-cyan-500/10 hover:text-cyan-200">重启</button>
                </div>
            </header>
            <main class="flex-1 overflow-y-auto p-4">
                {{ $slot }}
            </main>
        </div>
    </div>
</div>
@endsection
