<div class="flex flex-col gap-6" x-init="$wire.loadImages">
    <form wire:submit="saveSettings" class="application-settings-form flex flex-col">
        <x-unsaved-bar action="saveSettings" />
        <x-application.settings-section id="rollback-retention-section" title="{{ __('application.rb_retention') }}"
            helper="{{ __('application.rb_retention_helper') }}">
            @if ($serverRetentionDisabled)
                <div class="mb-4">
                    <x-callout type="warning" title="{{ __('application.rb_disabled_title') }}">
                        {{ __('application.rb_disabled_body') }}
                    </x-callout>
                </div>
            @endif

            <div class="max-w-sm">
                <x-forms.input id="dockerImagesToKeep" type="number" min="0" max="100"
                    label="{{ __('application.rb_images_to_keep') }}"
                    helper="{{ __('application.rb_images_helper') }}"
                    canGate="update" :canResource="$application" :disabled="$serverRetentionDisabled" />
            </div>
        </x-application.settings-section>
    </form>

    <x-application.settings-section id="rollback-images-section" title="{{ __('application.rb_available') }}"
        helper="{{ __('application.rb_available_helper') }}" flush>
        <x-slot:actions>
            @can('view', $application)
                <x-forms.button wire:click="loadImages(true)">
                    {{ __('application.rb_reload') }}
                </x-forms.button>
            @endcan
        </x-slot:actions>

        <div wire:target="loadImages" wire:loading.remove>
            @forelse ($images as $image)
                @php
                    $tag = data_get($image, 'tag');
                    $date = data_get($image, 'created_at');
                    $createdAt = \Illuminate\Support\Carbon::parse($date);
                    $isCommitSha = preg_match('/^[0-9a-f]{7,128}$/i', $tag);
                    $isPrTag = preg_match('/^pr-\d+$/', $tag);
                    $isRollbackable = $isCommitSha || $isPrTag;
                    $isCurrent = data_get($image, 'is_current');
                @endphp
                <div
                    class="flex flex-col gap-3 border-b border-neutral-200 px-4 py-3.5 last:border-b-0 sm:flex-row sm:items-center dark:border-white/[0.07]">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-500 ring-1 ring-neutral-200 dark:bg-white/[0.05] dark:text-fg-dim dark:ring-white/[0.07]">
                        <x-reicon name="layers" class="size-[18px]" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <code class="truncate font-mono text-[13px] font-semibold text-black dark:text-fg">
                                {{ $tag }}
                            </code>
                            @if ($isCurrent)
                                <x-status-badge status="{{ __('application.rb_running_image') }}" type="success" />
                            @elseif (!$isRollbackable)
                                <x-status-badge status="{{ __('application.rb_unavailable') }}" type="neutral" />
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-fg-dim">
                            {{ __('application.rb_built', ['time' => $createdAt->diffForHumans()]) }}
                            <span class="mx-1 text-neutral-300 dark:text-fg-faint">·</span>
                            {{ $date }}
                        </p>
                    </div>
                    @can('deploy', $application)
                        @if ($isCurrent)
                            <x-forms.button disabled tooltip="{{ __('application.rb_tooltip_running') }}">
                                {{ __('application.rb_rollback') }}
                            </x-forms.button>
                        @elseif (!$isRollbackable)
                            <x-forms.button disabled
                                tooltip="{{ __('application.rb_tooltip_only_tags') }}">
                                {{ __('application.rb_rollback') }}
                            </x-forms.button>
                        @else
                            <x-forms.button wire:click="rollbackImage('{{ $tag }}')">
                                {{ __('application.rb_rollback_to') }}
                            </x-forms.button>
                        @endif
                    @endcan
                </div>
            @empty
                <x-empty title="{{ __('application.rb_no_images') }}"
                    description="{{ __('application.rb_no_images_desc') }}"
                    icon-name="layers" />
            @endforelse
        </div>

        <div class="w-full" wire:target="loadImages" wire:loading>
            <div class="flex items-center justify-center gap-2 px-4 py-10 text-[13px] text-neutral-500 dark:text-fg-dim">
                <x-loading class="size-4" />
                {{ __('application.rb_loading') }}
            </div>
        </div>
    </x-application.settings-section>
</div>
