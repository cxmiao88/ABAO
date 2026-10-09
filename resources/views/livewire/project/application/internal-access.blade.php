@php
    $networkAliases = str($application->custom_network_aliases ?? '')
        ->explode(',')
        ->map(fn ($alias) => trim($alias))
        ->filter()
        ->values();
    $exposedPorts = str($application->ports_exposes ?? '')
        ->explode(',')
        ->map(fn ($port) => trim($port))
        ->filter()
        ->implode(', ');
@endphp

<section id="internal-access-section" class="pt-5" wire:init="loadCurrentInternalHostname">
    <h3 class="mb-4 text-sm font-semibold text-black dark:text-fg">{{ __('application.int_access') }}</h3>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @if ($currentInternalHostname)
            <x-forms.copy-input label="{{ __('application.int_hostname') }}" :text="$currentInternalHostname" />
        @else
            <div class="w-full">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-black dark:text-white">{{ __('application.int_hostname') }}</label>
                <input type="text"
                    value="{{ $currentInternalHostnameLoaded ? __('application.int_no_container') : __('application.int_loading') }}"
                    class="input input-with-copy-button bg-white dark:bg-coolgray-100 dark:read-only:bg-coolgray-100 dark:read-only:text-white"
                    readonly aria-live="polite">
            </div>
        @endif
        <x-forms.copy-input label="{{ __('application.int_network') }}" :text="$application->destination->network" />
        <x-forms.copy-input label="{{ __('application.int_ports') }}" :text="$exposedPorts ?: __('application.int_none')" />
        <x-forms.copy-input label="{{ __('application.int_aliases') }}" :text="$networkAliases->implode(', ') ?: __('application.int_none')" />
    </div>
    <div class="mt-4 flex flex-col gap-3 border-t border-neutral-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/[0.07]">
        <p class="text-sm text-neutral-500 dark:text-fg-dim">
            {{ __('application.int_helper') }}
        </p>
        <button type="button" class="button shrink-0"
            @click="window.scrollToSettingsSection?.('networking-section')">
            {{ __('application.int_edit') }}
        </button>
    </div>
</section>
