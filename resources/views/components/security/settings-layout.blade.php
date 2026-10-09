@php
    $securityMenuItems = collect([
        [
            'label' => __('sec_menu_private_keys'),
            'route' => 'security.private-key.index',
            'active' => request()->routeIs('security.private-key.*'),
            'icon' => 'keys',
        ],
        auth()->user()?->can('viewAny', App\Models\CloudProviderToken::class) ? [
            'label' => __('sec_menu_cloud_tokens'),
            'route' => 'security.cloud-tokens',
            'active' => request()->routeIs('security.cloud-tokens*'),
            'icon' => 'cloud',
        ] : null,
        auth()->user()?->can('viewAny', App\Models\IntegrationToken::class) ? [
            'label' => __('sec_menu_integration_tokens'),
            'route' => 'security.integration-tokens',
            'active' => request()->routeIs('security.integration-tokens'),
            'icon' => 'network',
        ] : null,
        auth()->user()?->can('viewAny', App\Models\CloudInitScript::class) ? [
            'label' => __('sec_menu_cloud_init'),
            'route' => 'security.cloud-init-scripts',
            'active' => request()->routeIs('security.cloud-init-scripts*'),
            'icon' => 'file-content',
        ] : null,
        [
            'label' => __('sec_menu_api_tokens'),
            'route' => 'security.api-tokens',
            'active' => request()->routeIs('security.api-tokens'),
            'icon' => 'code',
        ],
    ])->filter();
@endphp

<section class="application-settings-workspace w-full max-w-none">
    <header class="settings-mobile-header xl:hidden">
        <h1 class="settings-mobile-title">{{ __('sec_keys_heading') }}</h1>
        <p class="settings-mobile-description">{{ __('sec_keys_desc') }}</p>
    </header>
    <div class="grid min-w-0 gap-8 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <aside class="application-settings-navigation min-w-0 xl:self-start">
            <nav aria-label="{{ __('sec_keys_aria') }}"
                class="grid grid-cols-2 gap-0.5 border-y border-neutral-200 py-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-1 xl:border-y-0 xl:py-0 dark:border-white/[0.06]">
                <div class="nav-section hidden xl:block">{{ __('sec_keys_heading') }}</div>
                @foreach ($securityMenuItems as $menuItem)
                    <a wire:key="security-settings-{{ str($menuItem['label'])->slug() }}"
                        @class(['menu-item', 'menu-item-active' => $menuItem['active']])
                        {{ wireNavigate() }} href="{{ route($menuItem['route']) }}">
                        <x-reicon :name="$menuItem['icon']" class="menu-item-icon" />
                        <span class="menu-item-label">{{ $menuItem['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0">
            @isset($actions)
                <div class="mb-3 flex min-h-8 flex-wrap items-center justify-end gap-2">
                    {{ $actions }}
                </div>
            @endisset
            {{ $slot }}
        </div>
    </div>
</section>
