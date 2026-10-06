@php($meta = $server->server_metadata)
<dl
    class="mt-4 grid gap-x-6 gap-y-5 border-t border-neutral-200 pt-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-white/[0.08]">
    @foreach ([
        __('server.meta_os') => $meta['os'] ?? 'N/A',
        __('server.meta_arch') => $meta['arch'] ?? 'N/A',
        __('server.meta_kernel') => $meta['kernel'] ?? 'N/A',
        __('server.meta_cpus') => $meta['cpus'] ?? 'N/A',
        __('server.meta_memory') => isset($meta['memory_bytes']) ? round($meta['memory_bytes'] / 1073741824, 1) . ' GB' : 'N/A',
        __('server.meta_docker_version') => $server->dockerVersion() ?? 'N/A',
        __('server.meta_compose_version') => $server->composeVersion() ?? 'N/A',
        __('server.meta_up_since') => $meta['uptime_since'] ?? 'N/A',
    ] as $detailLabel => $detailValue)
        <div>
            <dt class="text-xs font-medium text-neutral-500 dark:text-fg-dim">
                {{ $detailLabel }}
            </dt>
            <dd class="mt-1 text-sm font-medium text-neutral-950 dark:text-fg">
                {{ $detailValue }}
            </dd>
        </div>
    @endforeach
</dl>
