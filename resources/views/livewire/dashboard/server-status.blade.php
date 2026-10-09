<div
    class="relative z-10 mt-1 space-y-1"
    x-data="{
        stats: null,
        pollTimer: null,
        async refresh() {
            await $wire.loadData();
        },
        init() {
            $wire.loadData();
            this.pollTimer = window.setInterval(() => {
                if (!document.hidden) {
                    $wire.loadData();
                }
            }, 60000);
        },
        destroy() {
            window.clearInterval(this.pollTimer);
        },
        fmtPercent(v) {
            return Number.isFinite(v) ? `${Number(v.toFixed(1))}%` : '—';
        },
        fmtBytes(b) {
            if (!Number.isFinite(b) || b <= 0) return '—';
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            let i = 0, v = b;
            while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
            return `${v.toFixed(v >= 100 ? 0 : 1)}${units[i]}`;
        },
        memPct() {
            return this.stats && this.stats.mem_total > 0 ? (this.stats.mem_used / this.stats.mem_total * 100) : null;
        },
        diskPct() {
            return this.stats && this.stats.disk_total > 0 ? (this.stats.disk_used / this.stats.disk_total * 100) : null;
        },
        fmtUptime(s) {
            if (!Number.isFinite(s) || s <= 0) return '—';
            const d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
            if (d > 0) return `${d}天 ${h}时`;
            if (h > 0) return `${h}时 ${m}分`;
            return `${m}分`;
        },
        barColor(p) {
            if (p === null) return '';
            if (p >= 90) return 'bg-red-500';
            if (p >= 75) return 'bg-amber-500';
            return 'bg-emerald-500';
        },
    }"
    @dashboard-server-status-{{ $server->uuid }}.window="stats = $event.detail.stats">

    <template x-if="stats">
        <div class="space-y-1">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] uppercase tracking-wide text-neutral-400 dark:text-fg-faint">{{ __('dash_srv_cpu') }}</span>
                <span class="text-[11px] font-semibold tabular-nums text-neutral-700 dark:text-fg" x-text="fmtPercent(stats.cpu)"></span>
            </div>
            <div class="h-1 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-white/[0.08]">
                <div class="h-full rounded-full transition-all duration-500" :class="barColor(stats.cpu)" :style="`width:${Math.min(stats.cpu, 100)}%`"></div>
            </div>
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] uppercase tracking-wide text-neutral-400 dark:text-fg-faint">{{ __('dash_srv_memory') }}</span>
                <span class="text-[11px] font-semibold tabular-nums text-neutral-700 dark:text-fg" x-text="`${fmtPercent(memPct())} · ${fmtBytes(stats.mem_used)}/${fmtBytes(stats.mem_total)}`"></span>
            </div>
            <div class="h-1 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-white/[0.08]">
                <div class="h-full rounded-full transition-all duration-500" :class="barColor(memPct())" :style="`width:${Math.min(memPct() ?? 0, 100)}%`"></div>
            </div>
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] uppercase tracking-wide text-neutral-400 dark:text-fg-faint">{{ __('dash_srv_disk') }}</span>
                <span class="text-[11px] font-semibold tabular-nums text-neutral-700 dark:text-fg" x-text="`${fmtPercent(diskPct())} · ${fmtBytes(stats.disk_used)}/${fmtBytes(stats.disk_total)}`"></span>
            </div>
            <div class="h-1 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-white/[0.08]">
                <div class="h-full rounded-full transition-all duration-500" :class="barColor(diskPct())" :style="`width:${Math.min(diskPct() ?? 0, 100)}%`"></div>
            </div>
            <div class="flex items-center justify-between pt-0.5 text-[10px] text-neutral-400 dark:text-fg-faint">
                <span>{{ __('dash_srv_load') }} <span class="font-medium tabular-nums text-neutral-600 dark:text-fg-dim" x-text="stats.load.toFixed(2)"></span></span>
                <span>{{ __('dash_srv_uptime') }} <span class="font-medium text-neutral-600 dark:text-fg-dim" x-text="fmtUptime(stats.uptime)"></span></span>
            </div>
        </div>
    </template>
    <template x-if="!stats">
        <div class="flex items-center justify-between rounded-md border border-dashed border-neutral-200 px-2 py-1.5 text-[11px] text-neutral-400 dark:border-white/[0.08] dark:text-fg-faint">
            <span>{{ __('dash_srv_no_data') }}</span>
        </div>
    </template>
</div>
