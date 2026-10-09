@props([
    'count' => 5,
    'grid' => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
    'rounded' => 'rounded-lg',
    'labels' => [__('anl_requests'), __('anl_unique_visitors'), __('anl_bandwidth'), __('anl_error_rate'), __('anl_p95_latency')],
])

{{-- KPI stat-tile grid skeleton, mirroring an analytics Overview tile grid. --}}
<div class="grid {{ $grid }} gap-px overflow-hidden {{ $rounded }} bg-neutral-200 dark:bg-white/[0.07]">
    @for ($i = 0; $i < (int) $count; $i++)
        <div class="flex flex-col bg-[var(--coollabs-base)] px-4 py-3">
            <span class="text-[11px] font-medium tracking-wide text-neutral-500 uppercase dark:text-fg-dim">
                {{ $labels[$i] ?? __('anl_metric') }}
            </span>
            <x-skeleton class="mt-2 h-6 w-20" />
            <div class="mt-auto pt-3">
                <x-skeleton class="h-9 w-full rounded" />
            </div>
        </div>
    @endfor
</div>
