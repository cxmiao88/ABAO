<form wire:submit="submit" class="application-settings-form flex flex-col">
    <x-unsaved-bar action="submit" />
    <x-application.settings-section id="preview-template-section" title="{{ __('application.pv_template_title') }}"
        helper="{{ __('application.pv_template_helper') }}">
        <x-slot:actions>
            @can('update', $application)
                <x-forms.button type="button" wire:click="resetToDefault">
                    {{ __('application.pv_reset_template') }}
                </x-forms.button>
            @endcan
        </x-slot:actions>

        <x-forms.input id="previewUrlTemplate" label="{{ __('application.pv_url_template') }}"
            helper="{{ __('application.pv_url_template_helper') }}"
            canGate="update" :canResource="$application" />

        @if ($previewUrlTemplate)
            <div
                class="mt-4 flex items-center justify-between gap-3 rounded-lg bg-neutral-100 px-3 py-2.5 ring-1 ring-neutral-200 dark:bg-white/[0.04] dark:ring-white/[0.07]">
                <span class="text-[13px] text-neutral-500 dark:text-fg-dim">{{ __('application.pv_generated_pattern') }}</span>
                <code class="break-all text-right font-mono text-xs text-neutral-700 dark:text-fg">
                    {{ $previewUrlTemplate }}
                </code>
            </div>
        @endif
    </x-application.settings-section>
</form>
