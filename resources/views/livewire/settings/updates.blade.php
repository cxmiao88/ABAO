<div>
    <x-slot:title>
        {{ __('set_updates_title') }}
    </x-slot>

    <x-settings.layout>
        <form wire:submit="submit" class="application-settings-form flex min-w-0 flex-col gap-6">
            {{-- Exclude is_auto_update_enabled (instantSave) so the bar does not flash. --}}
            <x-unsaved-bar action="submit" targets="update_check_frequency,auto_update_frequency" />

            <x-application.settings-section :title="__('set_update_coolify')"
                helper="{{ __('set_update_coolify_helper') }}">
                <livewire:upgrade :full-button="true" key="settings-upgrade" />
            </x-application.settings-section>

            <x-application.settings-section :title="__('set_update_checks')">
                <x-slot:actions>
                    <x-forms.button type="button" wire:click="checkManually">
                        <x-reicon name="refresh" class="size-3.5" />
                        {{ __('set_check_now') }}
                    </x-forms.button>
                </x-slot:actions>
                <x-forms.input required id="update_check_frequency" :label="__('set_check_frequency')"
                    placeholder="0 * * * *"
                    helper="{{ __('set_check_frequency_helper') }}" />
            </x-application.settings-section>

            <x-application.settings-section :title="__('set_auto_updates')">
                <div class="grid gap-4 lg:grid-cols-2">
                    @if (!is_null(config('constants.coolify.autoupdate', null)))
                        <x-forms.listbox disabled id="is_auto_update_enabled" :label="__('set_auto_updates_label')"
                            helper="{{ __('set_auto_updates_env_helper') }}" :options="[
                                ['value' => true, 'label' => __('set_enabled')],
                                ['value' => false, 'label' => __('set_disabled')],
                            ]" />
                    @else
                        <x-forms.listbox id="is_auto_update_enabled" :label="__('set_auto_updates_label')"
                            onChange="instantSave" :options="[
                                ['value' => true, 'label' => __('set_enabled')],
                                ['value' => false, 'label' => __('set_disabled')],
                            ]" />
                    @endif

                    @if (is_null(config('constants.coolify.autoupdate', null)) && $is_auto_update_enabled)
                        <x-forms.input required id="auto_update_frequency" :label="__('set_update_frequency')"
                            placeholder="0 0 * * *"
                            helper="{{ __('set_update_frequency_helper') }}" />
                    @else
                        <x-forms.input :label="__('set_update_frequency')" disabled placeholder="{{ __('set_disabled') }}" />
                    @endif
                </div>
            </x-application.settings-section>

            <x-application.settings-section :title="__('set_image_registry')">
                <div class="max-w-md">
                    <x-forms.listbox id="docker_registry_url" :label="__('set_docker_registry')" :options="[
                        ['value' => 'docker.io', 'label' => __('set_docker_hub')],
                        ['value' => 'ghcr.io', 'label' => __('set_ghcr')],
                    ]"
                        helper="{{ __('set_docker_registry_helper') }}" />
                </div>
            </x-application.settings-section>
        </form>
    </x-settings.layout>
</div>
