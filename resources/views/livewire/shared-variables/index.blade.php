<div>
    <x-slot:title>
        {{ __('sv.title') }} | ABao
    </x-slot>
    <x-shared-variables.layout>

    <div class="w-full">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <a class="group flex min-h-32 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:no-underline hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]"
            href="{{ route('shared-variables.team.index') }}" {{ wireNavigate() }}>
            <div
                class="flex size-8 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                <x-reicon name="teams" class="size-4" />
            </div>
            <div class="mt-auto pt-5">
                <h2 class="text-[13px]! leading-4! font-semibold! text-black dark:text-fg">{{ __('sv.team_wide') }}</h2>
                <p class="mt-1 text-[11px] leading-4 text-neutral-500 dark:text-fg-faint">
                    {{ __('sv.team_wide_helper') }}
                </p>
            </div>
        </a>

        <a class="group flex min-h-32 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:no-underline hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]"
            href="{{ route('shared-variables.project.index') }}" {{ wireNavigate() }}>
            <div
                class="flex size-8 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                <x-reicon name="projects" class="size-4" />
            </div>
            <div class="mt-auto pt-5">
                <h2 class="text-[13px]! leading-4! font-semibold! text-black dark:text-fg">{{ __('sv.project_wide') }}</h2>
                <p class="mt-1 text-[11px] leading-4 text-neutral-500 dark:text-fg-faint">
                    {{ __('sv.project_wide_helper') }}
                </p>
            </div>
        </a>

        <a class="group flex min-h-32 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:no-underline hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]"
            href="{{ route('shared-variables.environment.index') }}" {{ wireNavigate() }}>
            <div
                class="flex size-8 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                <x-reicon name="layers" class="size-4" />
            </div>
            <div class="mt-auto pt-5">
                <h2 class="text-[13px]! leading-4! font-semibold! text-black dark:text-fg">{{ __('sv.env_wide') }}</h2>
                <p class="mt-1 text-[11px] leading-4 text-neutral-500 dark:text-fg-faint">
                    {{ __('sv.env_wide_helper') }}
                </p>
            </div>
        </a>

        <a class="group flex min-h-32 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:no-underline hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]"
            href="{{ route('shared-variables.server.index') }}" {{ wireNavigate() }}>
            <div
                class="flex size-8 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                <x-reicon name="servers" class="size-4" />
            </div>
            <div class="mt-auto pt-5">
                <h2 class="text-[13px]! leading-4! font-semibold! text-black dark:text-fg">{{ __('sv.server_wide') }}</h2>
                <p class="mt-1 text-[11px] leading-4 text-neutral-500 dark:text-fg-faint">
                    {{ __('sv.server_wide_helper') }}
                </p>
            </div>
        </a>
    </div>
    </div>
    </x-shared-variables.layout>
</div>
