<div>
    <x-slot:title>{{ data_get_str($environment, 'name')->limit(10) }} > {{ __('project.edit') }} | ABao</x-slot>
    <div class="w-full max-w-none">
        <header class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-[24px]! leading-7! font-semibold! tracking-tight!">{{ $environment->name }}</h1>
                <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-dim">
                    {{ __('project.environment_settings_in', ['project' => $project->name]) }}
                </p>
            </div>
            @can('createAnyResource')
                <div class="flex w-fit shrink-0 items-center gap-2">
                    <a class="button whitespace-nowrap" {{ wireNavigate() }}
                        href="{{ route('project.clone-me', ['project_uuid' => $project->uuid, 'environment_uuid' => $environment->uuid]) }}">
                        <x-reicon name="layers" class="size-3.5 opacity-70" />
                        {{ __('project.clone_environment') }}
                    </a>
                </div>
            @endcan
        </header>

        <div class="flex flex-col gap-6">
        <form wire:submit="submit">
            <x-unsaved-bar action="submit" />
            <section class="application-settings-section">
                <div class="application-settings-section-header">
                    <div>
                        <h2>{{ __('project.environment_details_title') }}</h2>
                        <p>{{ __('project.environment_details_description', ['project' => $project->name]) }}</p>
                    </div>
                </div>
                <div class="application-settings-section-body grid gap-4 sm:grid-cols-2">
                    <x-forms.input label="{{ __('project.name_label') }}" id="name" canGate="update" :canResource="$environment" />
                    <x-forms.input label="{{ __('project.description') }}" id="description" canGate="update"
                        :canResource="$environment" />
                </div>
            </section>
        </form>

        @can('delete', $environment)
            <section
                class="overflow-hidden rounded-[10px] border border-red-300 bg-red-50/80 dark:border-red-500/25 dark:bg-red-500/[0.06]">
                <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-red-800 dark:text-red-300">{{ __('project.delete_environment_title') }}</h2>
                        <p class="mt-1 max-w-2xl text-sm text-red-700/80 dark:text-red-200/70">
                            {{ __('project.delete_environment_description') }}
                        </p>
                    </div>
                    <div class="shrink-0 sm:pt-0.5">
                        <livewire:project.delete-environment :disabled="! $environment->isEmpty()"
                            :environment_id="$environment->id" />
                    </div>
                </div>
            </section>
        @endcan
        </div>
    </div>
</div>
