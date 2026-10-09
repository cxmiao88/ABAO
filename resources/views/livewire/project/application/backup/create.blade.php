<form class="application-settings-form flex w-full flex-col gap-4" wire:submit="submit">
    @if ($targets->isEmpty())
        <x-empty size="sm" title="{{ __('application.bk_no_targets') }}"
            description="{{ __('application.bk_no_targets_desc') }}"
            icon-name="storages" />
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            <x-forms.listbox id="targetKey" label="{{ __('application.bk_target') }}" required :options="$targets->map(fn ($target) => [
                'value' => $target['key'],
                'label' => $target['type'] . ': ' . $target['name'],
            ])->all()" x-bind:disabled="{{ $targetLocked ? 'true' : 'false' }}" />
            <x-forms.input id="frequency" placeholder="daily or 0 0 * * *"
                helper="{{ __('application.bk_frequency_helper') }}"
                label="{{ __('application.bk_frequency') }}" required />
        </div>

        <div class="mt-2 flex justify-end border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
            <x-forms.button type="submit"
                class="button-highlighted">
                {{ __('application.bk_create_schedule') }}
            </x-forms.button>
        </div>
    @endif
</form>
