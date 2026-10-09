<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.menu_log_drains') }} | ABao
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="log-drains" />

        <div class="application-settings-form flex w-full flex-col gap-6">
            @if ($server->isFunctional())
                <x-application.settings-section id="server-log-drains-overview-section" :title="__('server.menu_log_drains')"
                    :helper="__('server.ld_helper')">
                    <x-slot:actions>
                        <x-status-badge :status="$server->isLogDrainEnabled() ? __('server.ld_active') : __('server.ld_not_configured')"
                            :type="$server->isLogDrainEnabled() ? 'success' : 'neutral'" />
                    </x-slot:actions>
                    <p class="text-sm leading-6 text-neutral-600 dark:text-fg-dim">
                        {{ __('server.ld_only_one') }}
                    </p>
                </x-application.settings-section>

                <form wire:submit="submit" class="contents">
                    <x-unsaved-bar action="submit" />

                    <x-application.settings-section id="server-new-relic-drain-section" title="New Relic"
                        :helper="__('server.ld_newrelic_helper')">
                        <div class="grid gap-4 lg:grid-cols-3">
                            <x-forms.listbox canGate="update" :canResource="$server" id="isLogDrainNewRelicEnabled" :label="__('server.ld_status_label')"
                                onChange="instantSave" :options="[
                                    ['value' => false, 'label' => __('server.ld_disabled')],
                                    ['value' => true, 'label' => __('server.ld_enabled')],
                                ]"
                                :disabled="$isLogDrainAxiomEnabled || $isLogDrainCustomEnabled || !auth()->user()->can('update', $server)" />
                            <x-forms.input canGate="update" :canResource="$server" type="password" required
                                id="logDrainNewRelicLicenseKey" :label="__('server.ld_license_key')"
                                :disabled="$server->isLogDrainEnabled()" />
                            <x-forms.input canGate="update" :canResource="$server" required
                                id="logDrainNewRelicBaseUri" :label="__('server.ld_endpoint')"
                                placeholder="https://log-api.eu.newrelic.com/log/v1"
                                :helper="__('server.ld_newrelic_endpoint_helper')"
                                :disabled="$server->isLogDrainEnabled()" />
                        </div>
                    </x-application.settings-section>
                    <x-application.settings-section id="server-axiom-drain-section" title="Axiom"
                        :helper="__('server.ld_axiom_helper')">
                        <div class="grid gap-4 lg:grid-cols-3">
                            <x-forms.listbox canGate="update" :canResource="$server" id="isLogDrainAxiomEnabled" :label="__('server.ld_status_label')"
                                onChange="instantSave" :options="[
                                    ['value' => false, 'label' => __('server.ld_disabled')],
                                    ['value' => true, 'label' => __('server.ld_enabled')],
                                ]"
                                :disabled="$isLogDrainNewRelicEnabled || $isLogDrainCustomEnabled || !auth()->user()->can('update', $server)" />
                            <x-forms.input canGate="update" :canResource="$server" type="password" required
                                id="logDrainAxiomApiKey" :label="__('server.ld_api_key')"
                                :disabled="$server->isLogDrainEnabled()" />
                            <x-forms.input canGate="update" :canResource="$server" required
                                id="logDrainAxiomDatasetName" :label="__('server.ld_dataset_name')"
                                :disabled="$server->isLogDrainEnabled()" />
                        </div>
                    </x-application.settings-section>
                    <x-application.settings-section id="server-custom-drain-section" :title="__('server.ld_custom_title')"
                        :helper="__('server.ld_custom_helper')">
                        <div class="mb-4 max-w-sm">
                            <x-forms.listbox canGate="update" :canResource="$server" id="isLogDrainCustomEnabled" :label="__('server.ld_status_label')"
                                onChange="instantSave" :options="[
                                    ['value' => false, 'label' => __('server.ld_disabled')],
                                    ['value' => true, 'label' => __('server.ld_enabled')],
                                ]"
                                :disabled="$isLogDrainNewRelicEnabled || $isLogDrainAxiomEnabled || !auth()->user()->can('update', $server)" />
                        </div>
                        <div class="grid gap-4 lg:grid-cols-2">
                            <x-forms.textarea canGate="update" :canResource="$server" rows="8" required
                                id="logDrainCustomConfig" :label="__('server.ld_fluentbit_config')"
                                :disabled="$server->isLogDrainEnabled()" />
                            <x-forms.textarea canGate="update" :canResource="$server" rows="8"
                                id="logDrainCustomConfigParser" :label="__('server.ld_parser_config')"
                                :disabled="$server->isLogDrainEnabled()" />
                        </div>
                    </x-application.settings-section>
                </form>
            @else
                <x-application.settings-section :title="__('server.menu_log_drains')"
                    :helper="__('server.ld_helper_alt')">
                    <x-empty size="sm" :title="__('server.status_validation_required')"
                        :description="__('server.ld_needs_validation')"
                        icon-name="notifications" />
                </x-application.settings-section>
            @endif
        </div>
    </div>
</div>
