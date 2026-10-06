                <form wire:submit.prevent="submit" class="application-settings-form flex flex-col gap-6">
                    <x-unsaved-bar action="submit" />
                    <x-application.settings-section id="server-overview-section" :title="__('server.overview_title')"
                        :helper="__('server.localhost_overview_helper')">
                        <x-slot:actions>
                            @if ($server->server_metadata)
                                <x-forms.button type="button" class="size-8! px-0!"
                                    wire:click="refreshServerMetadata" :title="__('server.refresh_server_details')">
                                    <x-reicon name="refresh" class="size-3.5" />
                                </x-forms.button>
                            @endif
                            <x-status-badge :status="$server->isFunctional() ? __('server.status_ready') : __('server.status_validation_required')"
                                :type="$server->isFunctional() ? 'success' : 'warning'" />
                        </x-slot:actions>

                        @if ($this->limaStartCommand)
                            <x-callout type="info" :title="__('server.lima_start_title')" class="mb-4">
                                <code
                                    class="mt-2 block overflow-x-auto rounded-lg bg-neutral-950 px-3 py-2 font-mono text-[11px] text-neutral-200">{{ $this->limaStartCommand }}</code>
                            </x-callout>
                        @endif

                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600 dark:bg-white/[0.06] dark:text-fg-dim">
                                <x-reicon name="servers" class="size-4.5" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-950 dark:text-fg">{{ __('server.localhost') }}</p>
                                <p class="mt-1 text-xs leading-5 text-neutral-500 dark:text-fg-dim">
                                    @if ($server->isFunctional())
                                        {{ __('server.overview_functional') }}
                                    @else
                                        {{ __('server.localhost_needs_validation') }}
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

                    <x-application.settings-section id="server-connection-section" :title="__('server.connection_title')"
                        :helper="__('server.localhost_connection_helper')">
                        <x-slot:actions>
                            <x-forms.button type="button" wire:click.prevent="checkLocalhostConnection"
                                canGate="update" :canResource="$server">
                                <x-reicon name="refresh" class="size-3.5" />
                                {{ __('server.validate_connection') }}
                            </x-forms.button>
                        </x-slot:actions>

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
                            <x-forms.input canGate="update" :canResource="$server"
                                placeholder="https://example.com" id="wildcardDomain" :label="__('server.wildcard_domain_label')"
                                :helper="__('server.wildcard_domain_helper')"
                                :disabled="$isValidating" />
                        </div>
                    </x-application.settings-section>
                </form>
