<div x-data
    x-init="@if ($server->hetzner_server_id && $server->cloudProviderToken && !$hetznerServerStatus) $wire.checkHetznerServerStatus(); @endif @if ($server->vultr_instance_id && $server->cloudProviderToken) $wire.checkVultrInstanceStatus(); @endif @if ($server->digitalocean_droplet_id && $server->cloudProviderToken && !$digitalOceanDropletStatus) $wire.checkDigitalOceanDropletStatus(); @endif">
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(24) }} | {{ __('server.page_title') }} | Coolify
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="general" />
        <div class="w-full min-w-0">
            @if ($server->isLocalhost())
                @include('livewire.server.partials.localhost-general')
            @else
                @php
                    $provider = match (true) {
                        filled($server->hetzner_server_id) => 'Hetzner',
                        filled($server->digitalocean_droplet_id) => 'DigitalOcean',
                        filled($server->vultr_instance_id) => 'Vultr',
                        default => null,
                    };
                    $providerStatus = match ($provider) {
                        'Hetzner' => $hetznerServerStatus,
                        'DigitalOcean' => $digitalOceanDropletStatus,
                        'Vultr' => $vultrInstanceStatus,
                        default => null,
                    };
                    $providerStatusType = match (true) {
                        in_array($providerStatus, ['running', 'active']) => 'success',
                        in_array($providerStatus, ['starting', 'initializing', 'pending', 'new']) => 'warning',
                        in_array($providerStatus, ['off', 'stopped', 'suspended', 'archive', 'deleted']) => 'error',
                        default => 'neutral',
                    };
                    $hasLinkableCloudProviders = (!$server->hetzner_server_id && $availableHetznerTokens->isNotEmpty())
                        || (!$server->vultr_instance_id && $availableVultrTokens->isNotEmpty())
                        || (!$server->digitalocean_droplet_id && $availableDigitalOceanTokens->isNotEmpty());
                    $hetznerMatch = $matchedHetznerServer
                        ? [
                            'name' => $matchedHetznerServer['name'] ?? 'Hetzner server',
                            'id' => $matchedHetznerServer['id'] ?? null,
                            'status' => $matchedHetznerServer['status'] ?? null,
                        ]
                        : null;
                    $digitalOceanMatch = $matchedDigitalOceanDroplet
                        ? [
                            'name' => $matchedDigitalOceanDroplet['name'] ?? 'DigitalOcean Droplet',
                            'id' => $matchedDigitalOceanDroplet['id'] ?? null,
                            'status' => $matchedDigitalOceanDroplet['status'] ?? null,
                        ]
                        : null;
                    $vultrMatch = $matchedVultrInstance
                        ? [
                            'name' => $matchedVultrInstance['label'] ?? $matchedVultrInstance['hostname'] ?? 'Vultr instance',
                            'id' => $matchedVultrInstance['id'] ?? null,
                            'status' => $matchedVultrInstance['status'] ?? null,
                        ]
                        : null;
                @endphp

                <form wire:submit.prevent="submit" class="application-settings-form flex flex-col gap-6">
                    {{-- Server role saves separately; keep dirty tracking on explicit-save fields. --}}
                    <x-unsaved-bar action="submit"
                        targets="name,description,ip,user,port,connectionTimeout,serverTimezone,wildcardDomain" />

                    <x-application.settings-section id="server-connection-section" :title="__('server.connection_title')"
                        :helper="__('server.connection_helper')">
                        <x-slot:actions>
                            @if ($hasLinkableCloudProviders)
                                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                                    <button type="button" class="button" @click="open = !open">
                                        <x-reicon name="plus" class="size-3.5" />
                                        {{ __('server.link_provider') }}
                                    </button>
                                    <div x-cloak x-show="open" x-transition.origin.top.right
                                        class="absolute top-9 right-0 z-50 w-56 rounded-lg border border-neutral-200 bg-white p-1 shadow-dropdown dark:border-white/[0.1] dark:bg-raised">
                                        @if (!$server->hetzner_server_id && $availableHetznerTokens->isNotEmpty())
                                            <x-server.provider-link-modal :server="$server" provider="hetzner"
                                                providerLabel="Hetzner" tokenModel="selectedHetznerTokenId"
                                                :tokens="$availableHetznerTokens" manualModel="manualHetznerServerId"
                                                manualLabel="{{ __('server.provider_server_id') }}" manualPlaceholder="12345678"
                                                searchByIdMethod="searchHetznerServerById"
                                                searchByIpMethod="searchHetznerServer" linkMethod="linkToHetzner"
                                                :searchError="$hetznerSearchError" :noMatch="$hetznerNoMatchFound"
                                                :matched="$hetznerMatch" />
                                        @endif
                                        @if (!$server->digitalocean_droplet_id && $availableDigitalOceanTokens->isNotEmpty())
                                            <x-server.provider-link-modal :server="$server" provider="digitalocean"
                                                providerLabel="DigitalOcean"
                                                tokenModel="selectedDigitalOceanTokenId"
                                                :tokens="$availableDigitalOceanTokens"
                                                manualModel="manualDigitalOceanDropletId"
                                                manualLabel="{{ __('server.provider_droplet_id') }}" manualPlaceholder="12345678"
                                                searchByIdMethod="searchDigitalOceanDropletById"
                                                searchByIpMethod="searchDigitalOceanDroplet"
                                                linkMethod="linkToDigitalOcean"
                                                :searchError="$digitalOceanSearchError"
                                                :noMatch="$digitalOceanNoMatchFound" :matched="$digitalOceanMatch" />
                                        @endif
                                        @if (!$server->vultr_instance_id && $availableVultrTokens->isNotEmpty())
                                            <x-server.provider-link-modal :server="$server" provider="vultr"
                                                providerLabel="Vultr" tokenModel="selectedVultrTokenId"
                                                :tokens="$availableVultrTokens" manualModel="manualVultrInstanceId"
                                                manualLabel="{{ __('server.provider_instance_id') }}" manualPlaceholder="6d4b…"
                                                searchByIdMethod="searchVultrInstanceById"
                                                searchByIpMethod="searchVultrInstance" linkMethod="linkToVultr"
                                                :searchError="$vultrSearchError" :noMatch="$vultrNoMatchFound"
                                                :matched="$vultrMatch" />
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($server->canBeValidated())
                                <x-process-dialog closeWithX mobileFullscreen size="xl" :open="$isValidating">
                                    <x-slot:title>{{ __('server.validate_and_configure') }}</x-slot:title>
                                    <x-slot:content>
                                        <livewire:server.validate-and-install :server="$server"
                                            :ask="$server->isFunctional() && ! $isValidating" />
                                    </x-slot:content>
                                    <x-forms.button type="button" :isHighlighted="! $server->isFunctional()"
                                        @click="processDialogOpen = true" wire:click.prevent="validateServer">
                                        <x-reicon :name="$server->isFunctional() ? 'refresh' : 'alert-circle'" class="size-3.5" />
                                        {{ $server->isFunctional() ? __('server.revalidate_connection') : __('server.validate_connection') }}
                                    </x-forms.button>
                                </x-process-dialog>
                            @endif
                            @if (isDev())
                                <div wire:key="server-management-{{ $server->isTransferredAway() ? 'enable' : 'disable' }}">
                                    @if ($server->isTransferredAway())
                                        <x-modal-confirmation :title="__('server.enable_management_title')"
                                            submitAction="toggleManagement" :confirmWithText="false"
                                            :confirmWithPassword="false" :step2ButtonText="__('server.enable_management')"
                                            :warningMessage="__('server.enable_management_warning')"
                                            :actions="[__('server.enable_management_action')]">
                                            <x-slot:trigger>
                                                <x-forms.button type="button" canGate="update" :canResource="$server">
                                                    <x-reicon name="play-circle" class="size-3.5" />
                                                    {{ __('server.enable_management') }}
                                                </x-forms.button>
                                            </x-slot:trigger>
                                        </x-modal-confirmation>
                                    @else
                                        <x-modal-confirmation :title="__('server.disable_management_title')"
                                            submitAction="toggleManagement" :confirmWithText="false"
                                            :confirmWithPassword="false" :step2ButtonText="__('server.disable_management')"
                                            :actions="[
                                                __('server.disable_management_action_1'),
                                                __('server.disable_management_action_2'),
                                            ]">
                                            <x-slot:trigger>
                                                <x-forms.button type="button" canGate="update" :canResource="$server">
                                                    <x-reicon name="stop-circle" class="size-3.5" />
                                                    {{ __('server.disable_management') }}
                                                </x-forms.button>
                                            </x-slot:trigger>
                                        </x-modal-confirmation>
                                    @endif
                                </div>
                            @endif
                            @if (isDev() && $server->isTransferredAway())
                                @unless ($server->isManagementDisabled())
                                    <x-status-badge :label="__('server.status_transferred_away')" type="warning" />
                                @endunless
                            @else
                                <x-status-badge :label="$server->isFunctional() ? __('server.status_ready') : __('server.status_validation_required')"
                                    :type="$server->isFunctional() ? 'success' : 'warning'" />
                            @endif
                        </x-slot:actions>

                        @if (isDev() && $server->isManagementDisabled())
                            <x-callout type="warning" :title="__('server.transferable_title')" class="mb-4">
                                {{ __('server.transferable_description_before') }}
                                <a href="{{ route('server.transfer', ['server_uuid' => $server->uuid]) }}"
                                    {{ wireNavigate() }} class="underline">{{ __('server.transferable_link') }}</a>{{ __('server.transferable_description_after') }}
                            </x-callout>
                        @elseif (isDev() && $server->isTransferredAway())
                            <x-callout type="warning" :title="__('server.transferred_title')" class="mb-4">
                                {{ __('server.transferred_description') }}
                            </x-callout>
                        @endif

                        @if ($this->limaStartCommand)
                            <x-callout type="info" :title="__('server.lima_start_title')" class="mb-4">
                                <code
                                    class="mt-2 block overflow-x-auto rounded-lg bg-neutral-950 px-3 py-2 font-mono text-[11px] text-neutral-200">{{ $this->limaStartCommand }}</code>
                            </x-callout>
                        @endif

                        @if ($server->isForceDisabled() && isCloud())
                            <x-callout type="danger" :title="__('server.disabled_title')" class="mb-4">
                                {{ __('server.disabled_description') }}
                            </x-callout>
                        @endif

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-forms.input canGate="update" :canResource="$server" id="name" :label="__('server.name_label')"
                                required :disabled="$isValidating" />
                            <x-forms.input canGate="update" :canResource="$server" id="description"
                                :label="__('server.description_label')" :disabled="$isValidating" />
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-3">
                            <x-forms.input canGate="update" :canResource="$server" type="password" id="ip"
                                :label="__('server.ip_label')"
                                :helper="__('server.ip_helper')"
                                required :disabled="$isValidating" />
                            <x-forms.input canGate="update" :canResource="$server" id="user" :label="__('server.ssh_user_label')"
                                required :disabled="$isValidating" />
                            <x-forms.input canGate="update" :canResource="$server" type="number" id="port"
                                :label="__('server.ssh_port_label')" required :disabled="$isValidating" />
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-3">
                            <x-forms.input canGate="update" :canResource="$server" type="number"
                                id="connectionTimeout" :label="__('server.connection_timeout_label')"
                                :helper="__('server.connection_timeout_helper')" min="1" max="300"
                                required :disabled="$isValidating" />
                            <x-forms.searchable-listbox id="serverTimezone" :label="__('server.timezone_label')"
                                :helper="__('server.timezone_helper')"
                                :searchPlaceholder="__('server.search_timezones')" :emptyText="__('server.no_matching_timezone')"
                                :options="collect($this->timezones)->map(fn ($timezone) => [
                                    'value' => $timezone,
                                    'label' => $timezone,
                                ])->all()" :disabled="$isValidating || !auth()->user()->can('update', $server)" />
                            @if (!$isSwarmWorker && $serverRole !== 'build')
                                <x-forms.input canGate="update" :canResource="$server"
                                    placeholder="https://example.com" id="wildcardDomain" :label="__('server.wildcard_domain_label')"
                                    :helper="__('server.wildcard_domain_helper')"
                                    :disabled="$isValidating" />
                            @endif
                        </div>

                        @if (!$server->isLocalhost())
                            <div class="mt-4 border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                                <x-forms.listbox canGate="update" :canResource="$server" id="serverRole"
                                    :label="__('server.role_label')" onChange="requestServerRoleChange"
                                    :helper="__('server.role_helper')"
                                    :disabled="$isValidating" :options="[
                                        ['value' => 'deployment', 'label' => __('server.role_deployment'), 'description' => __('server.role_deployment_desc')],
                                        ['value' => 'build', 'label' => __('server.role_build'), 'description' => __('server.role_build_desc')],
                                        ['value' => 'both', 'label' => __('server.role_both'), 'description' => __('server.role_both_desc')],
                                    ]" />
                            </div>
                        @endif
                    </x-application.settings-section>

                    <x-application.settings-section id="server-overview-section" :title="__('server.overview_title')"
                        :helper="__('server.overview_helper')">
                        <x-slot:actions>
                            @if ($provider)
                                <x-status-badge :label="$provider . ($providerStatus ? ' · ' . ucfirst($providerStatus) : '')"
                                    :type="$providerStatusType" />
                                @if ($provider === 'Hetzner')
                                    <x-forms.button type="button" class="size-8! px-0!"
                                        wire:click.prevent="checkHetznerServerStatus(true)"
                                        :title="__('server.refresh_provider_status')">
                                        <x-reicon name="refresh" class="size-3.5" />
                                    </x-forms.button>
                                @elseif ($provider === 'DigitalOcean')
                                    <x-forms.button type="button" class="size-8! px-0!"
                                        wire:click.prevent="checkDigitalOceanDropletStatus(true)"
                                        :title="__('server.refresh_provider_status')">
                                        <x-reicon name="refresh" class="size-3.5" />
                                    </x-forms.button>
                                @elseif ($provider === 'Vultr')
                                    <x-forms.button type="button" class="size-8! px-0!"
                                        wire:click.prevent="checkVultrInstanceStatus(true)"
                                        :title="__('server.refresh_provider_status')">
                                        <x-reicon name="refresh" class="size-3.5" />
                                    </x-forms.button>
                                @endif
                                @if ($server->cloudProviderToken)
                                    @if ($provider === 'Hetzner' && !$server->isFunctional() && $hetznerServerStatus === 'off')
                                        <x-forms.button type="button" wire:click.prevent="startHetznerServer" isHighlighted
                                            canGate="update" :canResource="$server">
                                            {{ __('server.power_on') }}
                                        </x-forms.button>
                                    @elseif ($provider === 'DigitalOcean' && $digitalOceanDropletStatus === 'off')
                                        <x-forms.button type="button" wire:click.prevent="startDigitalOceanDroplet"
                                            isHighlighted canGate="update" :canResource="$server">
                                            {{ __('server.power_on') }}
                                        </x-forms.button>
                                    @elseif ($provider === 'Vultr' && $vultrInstanceStatus === 'stopped')
                                        <x-forms.button type="button" wire:click.prevent="startVultrInstance" isHighlighted
                                            canGate="update" :canResource="$server">
                                            {{ __('server.power_on') }}
                                        </x-forms.button>
                                    @endif
                                @endif
                            @endif
                            @if ($server->server_metadata)
                                <x-forms.button type="button" class="size-8! px-0!"
                                    wire:click="refreshServerMetadata" :title="__('server.refresh_server_details')">
                                    <x-reicon name="refresh" class="size-3.5" />
                                </x-forms.button>
                            @endif
                        </x-slot:actions>

                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600 dark:bg-white/[0.06] dark:text-fg-dim">
                                <x-reicon name="servers" class="size-4.5" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-neutral-950 dark:text-fg">
                                    {{ $server->name }}
                                </p>
                                <p class="mt-1 text-xs leading-5 text-neutral-500 dark:text-fg-dim">
                                    @if (isDev() && $server->isManagementDisabled())
                                        {{ __('server.overview_management_disabled') }}
                                    @elseif (isDev() && $server->isTransferredAway())
                                        {{ __('server.overview_migrated_away') }}
                                    @elseif ($server->isFunctional())
                                        {{ __('server.overview_functional') }}
                                    @else
                                        {{ __('server.overview_needs_validation') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($server->server_metadata)
                            @include('livewire.server.partials.server-details', ['server' => $server])
                        @else
                            <div class="mt-4 border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                                <x-forms.button type="button" wire:click="refreshServerMetadata">
                                    <x-reicon name="refresh" class="size-3.5" />
                                    {{ __('server.fetch_server_details') }}
                                </x-forms.button>
                            </div>
                        @endif
                    </x-application.settings-section>

                    @if ($server->validation_logs)
                        <x-application.settings-section :title="__('server.previous_validation_title')"
                            :helper="__('server.previous_validation_helper')">
                            <div
                                class="max-h-72 overflow-auto rounded-lg bg-neutral-950 p-4 font-mono text-xs leading-5 text-neutral-300">
                                {!! $server->validation_logs !!}
                            </div>
                        </x-application.settings-section>
                    @endif
                </form>
            @endif
        </div>
    </div>

    <x-modal-confirmation :title="__('server.role_change_title')"
        submitAction="confirmServerRoleChange" :confirmWithText="false" :confirmWithPassword="false"
        :step2ButtonText="__('server.role_change_button')"
        :warningMessage="__('server.role_change_warning')"
        :actions="[__('server.role_change_action')]">
        <x-slot:trigger>
            <button id="server-role-confirmation-trigger" type="button" class="hidden" aria-hidden="true"></button>
        </x-slot:trigger>
    </x-modal-confirmation>

    @script
        <script>
            $wire.on('open-server-role-confirmation', () => {
                document.getElementById('server-role-confirmation-trigger')?.click();
            });
        </script>
    @endscript
</div>
