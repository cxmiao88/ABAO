<div>
    <x-slot:title>
        {{ __('set_backup_title') }}
    </x-slot>

    <x-settings.layout>
    <div class="application-settings-form mx-auto flex w-full max-w-none min-w-0 flex-col gap-6">
        @if ($server->isFunctional())
            @if (isset($database) && isset($backup))
                <form wire:submit="submit">
                    <x-unsaved-bar action="submit" />

                    <x-application.settings-section :title="__('set_instance_database')">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <x-forms.input :label="__('set_name')" readonly id="name" />
                            <x-forms.input :label="__('set_description')" id="description" />
                            <div class="lg:col-span-2">
                                <x-forms.input :label="__('set_uuid')" readonly id="uuid" />
                            </div>
                            <x-forms.input :label="__('set_user')" readonly id="postgres_user" />
                            <x-forms.input type="password" :label="__('set_password')" readonly id="postgres_password" />
                        </div>
                    </x-application.settings-section>
                </form>

                <livewire:project.database.backup-edit :backup="$backup" :available-s3-storages="$s3s"
                    :status="data_get($database, 'status')" />

                <livewire:project.database.backup-executions :backup="$backup" />
            @else
                <x-application.settings-section :title="__('set_instance_backup')">
                    <x-empty :title="__('set_backup_not_configured')"
                        description="{{ __('set_backup_not_configured_desc') }}"
                        icon-name="database" size="sm">
                        <x-slot:actions>
                            <x-forms.button wire:click="addCoolifyDatabase" isHighlighted>
                                {{ __('set_configure_backup') }}
                            </x-forms.button>
                        </x-slot:actions>
                    </x-empty>
                </x-application.settings-section>
            @endif
        @else
            <x-application.settings-section :title="__('set_instance_backup')">
                <x-callout type="danger" :title="__('set_localhost_not_ready')">
                    {{ __('set_localhost_not_ready_desc') }}
                    <a href="{{ route('server.show', [$server->uuid]) }}" class="font-medium underline"
                        {{ wireNavigate() }}>{{ __('set_open_server_settings') }}</a>
                </x-callout>
            </x-application.settings-section>
        @endif
    </div>
    </x-settings.layout>
</div>
