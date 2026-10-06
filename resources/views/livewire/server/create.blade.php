<div class="w-full">
    <x-slot:title>
        {{ __('server.create.title') }} | Coolify
    </x-slot>

    <div class="mb-5 flex min-h-9 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="min-w-0 text-[24px]! leading-7! font-semibold! tracking-tight!">{{ __('server.create.heading') }}</h1>
        <div class="flex flex-wrap items-center gap-2">
            @if ($selectedType)
                <a href="{{ route('server.create') }}" class="button" {{ wireNavigate() }}>
                    {{ __('server.create.change_method') }}
                </a>
            @endif
        </div>
    </div>

    @if (!$selectedType)
        <div class="application-settings-form flex flex-col gap-6">
            <section class="application-settings-section">
                <div class="application-settings-section-header">
                    <h2 class="application-settings-section-title">{{ __('server.create.add_title') }}</h2>
                    <p class="application-settings-section-description">{{ __('server.create.add_description') }}</p>
                </div>
                <div class="application-settings-section-body is-flush">
                    <div class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2 lg:grid-cols-4">
                        <a href="{{ route('server.create.type', ['type' => 'manual']) }}"
                            class="group flex min-h-32 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:no-underline hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]"
                            {{ wireNavigate() }}>
                            <div class="flex items-start">
                                <span
                                    class="flex size-8 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                                    <x-reicon name="servers" class="size-4" />
                                </span>
                            </div>
                            <div class="mt-auto pt-5">
                                <h3 class="text-[13px]! font-semibold! text-black dark:text-fg">{{ __('server.create.ip_card_title') }}</h3>
                                <p class="mt-1 text-[11px] leading-4 text-neutral-500 dark:text-fg-faint">
                                    {{ __('server.create.ip_card_description') }}
                                </p>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            @can('viewAny', App\Models\CloudProviderToken::class)
                <section class="application-settings-section">
                    <div class="application-settings-section-header">
                        <h2 class="application-settings-section-title">{{ __('server.create.provision_title') }}</h2>
                        <p class="application-settings-section-description">{{ __('server.create.provision_description') }}</p>
                    </div>
                    <div class="application-settings-section-body is-flush">
                        <div class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2 lg:grid-cols-3">
                            <a href="{{ route('server.create.type', ['type' => 'hetzner']) }}"
                                class="group flex min-h-32 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:no-underline hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]"
                                {{ wireNavigate() }}>
                                <div class="flex items-start">
                                    <img src="{{ asset('svgs/hetzner.svg') }}" alt="Hetzner" class="size-8">
                                </div>
                                <div class="mt-auto pt-5">
                                    <h3 class="text-[13px]! font-semibold! text-black dark:text-fg">Hetzner</h3>
                                    <p class="mt-1 text-[11px] leading-4 text-neutral-500 dark:text-fg-faint">
                                        {{ __('server.create.hetzner_card_description') }}
                                    </p>
                                </div>
                            </a>

                            <a href="{{ route('server.create.type', ['type' => 'vultr']) }}"
                                class="group flex min-h-32 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:no-underline hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]"
                                {{ wireNavigate() }}>
                                <div class="flex items-start">
                                    <img src="https://www.vultr.com/media/logo_ondark.svg" alt="Vultr"
                                        class="h-8 w-20 object-contain object-left">
                                </div>
                                <div class="mt-auto pt-5">
                                    <h3 class="text-[13px]! font-semibold! text-black dark:text-fg">Vultr</h3>
                                    <p class="mt-1 text-[11px] leading-4 text-neutral-500 dark:text-fg-faint">
                                        {{ __('server.create.vultr_card_description') }}
                                    </p>
                                </div>
                            </a>

                            <a href="{{ route('server.create.type', ['type' => 'digital-ocean']) }}"
                                class="group flex min-h-32 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:no-underline hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]"
                                {{ wireNavigate() }}>
                                <div class="flex items-start">
                                    <x-digital-ocean-icon class="size-8" />
                                </div>
                                <div class="mt-auto pt-5">
                                    <h3 class="text-[13px]! font-semibold! text-black dark:text-fg">DigitalOcean</h3>
                                    <p class="mt-1 text-[11px] leading-4 text-neutral-500 dark:text-fg-faint">
                                        {{ __('server.create.do_card_description') }}
                                    </p>
                                </div>
                            </a>

                        </div>
                    </div>
                </section>
            @endcan
        </div>
    @else
        <div class="application-settings-form">
            @if ($selectedType === 'hetzner')
                <livewire:server.new.by-hetzner :private_keys="$private_keys" :limit_reached="$limit_reached"
                    :selected-token-uuid="$selectedTokenUuid"
                    wire:key="new-server-hetzner-{{ $selectedTokenUuid ?? 'select' }}" />
            @elseif ($selectedType === 'vultr')
                <livewire:server.new.by-vultr :private_keys="$private_keys" :limit_reached="$limit_reached"
                    :selected-token-uuid="$selectedTokenUuid"
                    wire:key="new-server-vultr-{{ $selectedTokenUuid ?? 'select' }}" />
            @elseif ($selectedType === 'digital-ocean')
                <livewire:server.new.by-digital-ocean :private_keys="$private_keys" :limit_reached="$limit_reached"
                    :selected-token-uuid="$selectedTokenUuid"
                    wire:key="new-server-digital-ocean-{{ $selectedTokenUuid ?? 'select' }}" />
            @else
                <livewire:server.new.by-ip :private_keys="$private_keys" :limit_reached="$limit_reached"
                    key="new-server-manual" />
            @endif
        </div>
    @endif
</div>
