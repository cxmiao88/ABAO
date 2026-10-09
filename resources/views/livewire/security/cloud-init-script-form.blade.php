<form wire:submit="save" class="application-settings-form flex w-full flex-col gap-4">
    <x-forms.input id="name" :label="__('sec_script_name')" helper="{{ __('sec_script_name_helper') }}" required />
    <x-forms.textarea id="script" :label="__('sec_script_content')" rows="12" monospace
        helper="{{ __('sec_script_content_helper') }}" required />
    <div class="flex justify-end border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
        <button type="submit"
            class="button button-highlighted">
            {{ $scriptId ? __('sec_update_script') : __('sec_create_script') }}
        </button>
    </div>
</form>
