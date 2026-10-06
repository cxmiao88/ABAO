<div>
    <x-slot:title>
        {{ data_get_str($project, 'name')->limit(10) }} > {{ __('project.environments') }} | Coolify
    </x-slot>
    <div x-data="projectEnvironments()" class="w-full">
        <header class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-[24px]! leading-7! font-semibold! tracking-tight!">{{ $project->name }}</h1>
                <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-dim">
                    <span
                        x-text="`${environments.length} ${environments.length === 1 ? '{{ __('project.environment') }}' : '{{ __('project.environments') }}'}`"></span>
                    {{ __('project.in_this_project') }}
                </p>
            </div>

            @can('update', $project)
                <div class="flex w-fit shrink-0 items-center gap-2">
                    <a href="{{ route('project.edit', ['project_uuid' => $project->uuid]) }}"
                        {{ wireNavigate() }}
                        class="button"
                        title="{{ __('project.settings') }}"
                        aria-label="{{ __('project.open_settings_for') }} {{ $project->name }}">
                        <x-reicon name="settings" class="size-3.5" />
                        {{ __('project.settings_button') }}
                    </a>

                    <x-modal-input title="{{ __('project.new_environment') }}">
                        <x-slot:content>
                            <button type="button"
                                class="button button-highlighted">
                                <x-reicon name="plus" class="size-3.5" />
                                {{ __('project.new_environment') }}
                            </button>
                        </x-slot:content>

                        <form class="space-y-4" wire:submit="submit">
                            <x-forms.input placeholder="staging" id="name" label="{{ __('project.name_label') }}" required />

                            <footer
                                class="flex justify-end border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                                <x-forms.button type="submit"
                                    defaultClass="button button-highlighted">
                                    {{ __('project.create_environment') }}
                                </x-forms.button>
                            </footer>
                        </form>
                    </x-modal-input>
                </div>
            @endcan
        </header>

        @if ($project->environments->isEmpty())
            <x-empty title="{{ __('project.no_environments') }}"
                description="{{ __('project.no_environments_description') }}"
                icon-name="layers" />
        @else
            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-sm">
                    <x-reicon name="search"
                        class="pointer-events-none absolute top-1/2 left-2.5 z-10 size-3.5 -translate-y-1/2 text-neutral-400 dark:text-fg-faint" />
                    <input x-model.debounce.150ms="search" x-on:input="page = 1" type="search"
                        placeholder="{{ __('project.search_environments') }}"
                        class="h-8! w-full rounded-lg! border-neutral-200! bg-white! py-0! pr-8! pl-8! text-[12px]! shadow-none! placeholder:text-neutral-400 focus:border-accent! focus:ring-0! dark:border-white/[0.08]! dark:bg-white/[0.035]! dark:text-fg! dark:placeholder:text-fg-faint">
                    <button x-cloak x-show="search" x-on:click="search = ''; page = 1" type="button"
                        class="absolute top-1/2 right-2 flex size-5 -translate-y-1/2 items-center justify-center rounded text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-black dark:text-fg-faint dark:hover:bg-white/[0.07] dark:hover:text-fg"
                        aria-label="{{ __('project.clear_search') }}">
                        <span class="text-sm leading-none">×</span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <x-table.dropdown panel-class="w-48!">
                        <x-slot:trigger>
                            <button type="button" class="button" aria-haspopup="listbox" :aria-expanded="open">
                            <svg class="size-3.5 opacity-65" viewBox="0 0 24 24" fill="none"
                                aria-hidden="true">
                                <path d="M8 5v14m0 0-3-3m3 3 3-3M16 19V5m0 0-3 3m3-3 3 3"
                                    stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            {{ __('project.sort') }}
                            </button>
                        </x-slot:trigger>
                            <template x-for="option in sortOptions" :key="option.value">
                                <button type="button"
                                    class="flex h-9 w-full items-center rounded-md px-2 text-left text-[12px] text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-black dark:text-fg-dim dark:hover:bg-white/[0.06] dark:hover:text-fg"
                                    x-on:click="sortBy = option.value; close(); page = 1">
                                    <span class="flex-1" x-text="option.label"></span>
                                    <svg x-show="sortBy === option.value" class="size-3.5 text-black dark:text-fg"
                                        viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                        <path d="m2.5 6.25 2.1 2.1 4.9-5" stroke="currentColor"
                                            stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </template>
                    </x-table.dropdown>

                    <div
                        class="view-toggle">
                        <button type="button" x-on:click="setViewMode('table')"
                            class="flex size-7.5 items-center justify-center rounded-md transition-colors"
                            :class="viewMode === 'table'
                                ?
                                'control-selected' :
                                'text-neutral-400 hover:bg-neutral-100 hover:text-black dark:text-fg-faint dark:hover:bg-white/[0.06] dark:hover:text-fg'"
                            aria-label="{{ __('project.table_view') }}" title="{{ __('project.table_view') }}">
                            <x-reicon name="unordered-list" class="size-3.5" />
                        </button>
                        <button type="button" x-on:click="setViewMode('grid')"
                            class="flex size-7.5 items-center justify-center rounded-md transition-colors"
                            :class="viewMode === 'grid'
                                ?
                                'control-selected' :
                                'text-neutral-400 hover:bg-neutral-100 hover:text-black dark:text-fg-faint dark:hover:bg-white/[0.06] dark:hover:text-fg'"
                            aria-label="{{ __('project.grid_view') }}" title="{{ __('project.grid_view') }}">
                            <x-reicon name="grid" class="size-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <div x-cloak x-show="viewMode === 'grid'">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <template x-for="environment in paginatedEnvironments" :key="environment.uuid">
                        <article
                            class="group relative flex min-h-28 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]">
                            <a :href="environment.href" {{ wireNavigate() }} class="absolute inset-0 rounded-xl"
                                :aria-label="`{{ __('project.open') }} ${environment.name}`"></a>

                            <div class="flex items-start gap-3">
                                <div
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                                    <x-reicon name="layers" class="size-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h2
                                        class="truncate text-[13px]! leading-4! font-semibold! text-black dark:text-fg"
                                        x-text="environment.name"></h2>
                                    <p class="mt-0.5 truncate text-[11px] text-neutral-500 dark:text-fg-faint"
                                        x-text="environment.description || '{{ __('project.environment') }}'"></p>
                                </div>
                            </div>

                            <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                                <p class="min-w-0 truncate text-[11px] text-neutral-500 dark:text-fg-dim"
                                    x-text="`${environment.resourceCount} ${environment.resourceCount === 1 ? '{{ __('project.resource') }}' : '{{ __('project.resources') }}'}`">
                                </p>

                                <div class="relative z-10 flex shrink-0 items-center gap-0.5">
                                    <a x-show="environment.addResourceHref" :href="environment.addResourceHref"
                                        {{ wireNavigate() }}
                                        class="flex size-7.5 items-center justify-center rounded-md text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-black dark:text-fg-faint dark:hover:bg-white/[0.06] dark:hover:text-fg"
                                        title="{{ __('project.add_resource') }}" :aria-label="`{{ __('project.add_resource_to') }} ${environment.name}`">
                                        <x-reicon name="plus" class="size-3" />
                                    </a>
                                    <a x-show="environment.settingsHref" :href="environment.settingsHref"
                                        {{ wireNavigate() }}
                                        class="flex size-7.5 items-center justify-center rounded-md text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-black dark:text-fg-faint dark:hover:bg-white/[0.06] dark:hover:text-fg"
                                        title="{{ __('project.environment_settings') }}"
                                        :aria-label="`{{ __('project.open_settings_for') }} ${environment.name}`">
                                        <x-reicon name="settings" class="size-3" />
                                    </a>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
                <x-client-pagination x-show="filteredEnvironments.length > 0" class="mt-3 rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-white/[0.05]"
                    summary="`${rangeStart}-${rangeEnd} {{ __('project.pagination_of') }} ${filteredEnvironments.length}`" page-size-model="pageSize"
                    storage-key="coolify.page-size.project-environments" :options="[12, 24, 48, 96]" />
            </div>

            <div x-show="viewMode === 'table'"
                class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-white/[0.05]">
                <div
                    class="environments-table-grid border-b border-neutral-200 bg-neutral-50 px-4 py-2.5 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-fg-faint">
                    <div>{{ __('project.environment') }}</div>
                    <div class="environment-resource-count">{{ __('project.resources') }}</div>
                    <div class="environment-description">{{ __('project.description') }}</div>
                    <div></div>
                </div>

                <template x-for="environment in paginatedEnvironments" :key="environment.uuid">
                    <div
                        class="environments-table-grid group relative min-h-14 items-center border-b border-neutral-200 px-4 py-2.5 transition-colors last:border-b-0 hover:bg-neutral-50 dark:border-white/[0.07] dark:hover:bg-white/[0.025]">
                        <a :href="environment.href" {{ wireNavigate() }} class="absolute inset-0"
                            :aria-label="`Open ${environment.name}`"></a>
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg-dim">
                                <x-reicon name="layers" class="size-4" />
                            </div>
                            <a :href="environment.href" {{ wireNavigate() }}
                                class="relative truncate text-[13px] font-semibold text-black hover:underline dark:text-fg"
                                x-text="environment.name"></a>
                        </div>

                        <div class="environment-resource-count text-[12px] text-neutral-600 dark:text-fg-dim"
                            x-text="environment.resourceCount"></div>
                        <p class="environment-description truncate text-[12px] text-neutral-500 dark:text-fg-dim"
                            x-text="environment.description || '-'"></p>

                        <div class="relative flex items-center justify-end gap-0.5">
                            <a x-show="environment.addResourceHref" :href="environment.addResourceHref"
                                {{ wireNavigate() }}
                                class="flex size-7 items-center justify-center rounded-md text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-black dark:text-fg-faint dark:hover:bg-white/[0.06] dark:hover:text-fg"
                                title="{{ __('project.add_resource') }}" :aria-label="`{{ __('project.add_resource_to') }} ${environment.name}`">
                                <x-reicon name="plus" class="size-3.5" />
                            </a>
                            <a x-show="environment.settingsHref" :href="environment.settingsHref" {{ wireNavigate() }}
                                class="flex size-7 items-center justify-center rounded-md text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-black dark:text-fg-faint dark:hover:bg-white/[0.06] dark:hover:text-fg"
                                title="{{ __('project.environment_settings') }}"
                                :aria-label="`{{ __('project.open_settings_for') }} ${environment.name}`">
                                <x-reicon name="settings" class="size-3.5" />
                            </a>
                        </div>
                    </div>
                </template>
                <x-client-pagination x-show="filteredEnvironments.length > 0"
                    summary="`${rangeStart}-${rangeEnd} {{ __('project.pagination_of') }} ${filteredEnvironments.length}`" page-size-model="pageSize"
                    storage-key="coolify.page-size.project-environments" :options="[12, 24, 48, 96]" />
            </div>

            <div x-show="filteredEnvironments.length === 0"
                class="flex min-h-52 flex-col items-center justify-center rounded-xl border border-neutral-200 bg-white px-6 text-center dark:border-white/[0.08] dark:bg-white/[0.05]">
                <x-reicon name="search" class="mb-3 size-6 text-neutral-300 dark:text-fg-faint" />
                <p class="text-[13px] font-medium">{{ __('project.no_matching_environments') }}</p>
                <p class="mt-1 text-[12px] text-neutral-500 dark:text-fg-dim">
                    {{ __('project.try_different_search') }}
                </p>
            </div>
        @endif
    </div>
</div>

<script>
    function projectEnvironments() {
        return {
            search: '',
            sortBy: 'name-asc',
            sortOpen: false,
            viewMode: localStorage.getItem('project-environments-view') || 'grid',
            page: 1,
            pageSize: 12,
            environments: @js($environmentsJs),
            sortOptions: [{
                    value: 'name-asc',
                    label: '{{ __("project.sort_name_asc") }}'
                },
                {
                    value: 'name-desc',
                    label: '{{ __("project.sort_name_desc") }}'
                },
                {
                    value: 'resources',
                    label: '{{ __("project.sort_most_resources") }}'
                },
            ],
            get filteredEnvironments() {
                const query = this.search.trim().toLowerCase();
                const environments = this.environments.filter((environment) => {
                    const searchable = [environment.name, environment.description]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase();

                    return !query || searchable.includes(query);
                });

                return environments.sort((first, second) => {
                    if (this.sortBy === 'name-desc') {
                        return second.name.localeCompare(first.name);
                    }
                    if (this.sortBy === 'resources') {
                        return second.resourceCount - first.resourceCount ||
                            first.name.localeCompare(second.name);
                    }

                    return first.name.localeCompare(second.name);
                });
            },
            get totalPages() {
                return Math.max(1, Math.ceil(this.filteredEnvironments.length / this.pageSize));
            },
            get paginatedEnvironments() {
                if (this.page > this.totalPages) {
                    this.page = this.totalPages;
                }

                const start = (this.page - 1) * this.pageSize;
                return this.filteredEnvironments.slice(start, start + this.pageSize);
            },
            get rangeStart() {
                return this.filteredEnvironments.length === 0 ? 0 : ((this.page - 1) * this.pageSize) + 1;
            },
            get rangeEnd() {
                return Math.min(this.page * this.pageSize, this.filteredEnvironments.length);
            },
            setViewMode(mode) {
                this.viewMode = mode;
                this.page = 1;
                localStorage.setItem('project-environments-view', mode);
            },
            previousPage() {
                this.page = Math.max(1, this.page - 1);
            },
            nextPage() {
                this.page = Math.min(this.totalPages, this.page + 1);
            },
        };
    }
</script>
