<form class="application-settings-form flex w-full flex-col gap-4" wire:submit="submit">
    <x-forms.input id="name" label="{{ __('team_name') }}" required />
    <x-forms.input id="description" label="{{ __('team_description') }}" />
    <div class="flex justify-end">
        <x-forms.button type="submit"
            defaultClass="button button-highlighted">
            {{ __('team_create') }}
        </x-forms.button>
    </div>
</form>
