<div class="domains-overview-container flex flex-col gap-3" x-data="{
        editOpen: false,
        domainSearch: '',
        editingDomainBaseline: null,
        get hasAddressChanges() {
            return this.editOpen && this.editingDomainBaseline !== null
                && JSON.stringify($wire.editingDomainParts) !== this.editingDomainBaseline
                && !$wire.showPortWarningModal;
        },
        editingServiceLabel: '',
        openEditDomain(index, domain, parts, service) {
            if (index !== undefined) {
                $wire.set('editingIndex', index, false);
                $wire.set('editingDomainParts', parts, false);
            }
            this.editingServiceLabel = service || '';
            this.editingDomainBaseline = JSON.stringify($wire.editingDomainParts);
            this.editOpen = true;
            this.$nextTick(() => this.$refs.editForm.querySelector('input[required]')?.focus());
        },
        closeEditDomain(discardDraft = true) {
            this.editOpen = false;
            this.editingDomainBaseline = null;
            if (discardDraft) this.$wire.cancelEdit();
        },
        matchesDomainSearch(value) { return !this.domainSearch.trim() || value.toLowerCase().includes(this.domainSearch.trim().toLowerCase()); },
    }"
    @open-preview-domain-edit.window="if ($event.detail.previewId === {{ $preview->id }}) openEditDomain()"
    @close-preview-domain-edit.window="if ($event.detail.previewId === {{ $preview->id }}) closeEditDomain(false)"
    @keydown.escape.window="if (editOpen && !$wire.showPortWarningModal) closeEditDomain()">
    @if (collect($domainRows)->contains(fn ($row) => $row['dns_status'] === 'checking'))
        <div class="hidden" wire:poll.2000ms="pollDnsChecks" aria-hidden="true"></div>
    @endif
    <div class="flex flex-wrap items-center gap-2">
        <p class="min-w-0 flex-1 truncate text-[13px] text-neutral-500 dark:text-fg-dim">
            {{ __('application.pvd_domain_count', ['count' => count($domainRows)]) }}
        </p>
        @if (count($domainRows) > 0)
            <input type="search" x-model="domainSearch" aria-label="{{ __('application.pvd_search_domains') }}"
                class="input h-8! w-full sm:w-64!" placeholder="{{ __('application.pvd_search_placeholder') }}" />
        @endif
        @can('update', $preview->application)
            @if (count($domainRows) > 0)
                <x-forms.button wire:click="checkAllDns" :showLoadingIndicator="false" wire:loading.attr="disabled" wire:target="checkAllDns,checkDomainDns">
                    <x-reicon name="refresh" class="size-3.5" />
                    {{ __('application.pvd_check_all_dns') }}
                </x-forms.button>
            @endif
            <x-modal-input title="{{ __('application.pvd_add_domain') }}" :closeOutside="false" :wireIgnore="false"
                canGate="update" :canResource="$preview->application"
                @close-preview-domain-add.window="if ($event.detail.previewId === {{ $preview->id }}) modalOpen = false">
                <x-slot:content>
                    <button type="button" class="button button-highlighted">
                        <x-reicon name="plus" class="size-3.5" />
                        {{ __('application.pvd_add_domain') }}
                    </button>
                </x-slot:content>
                <form wire:submit="addDomain" class="application-settings-form flex flex-col gap-4">
                    @if ($isCompose && count($composeServices) > 0)
                        <x-forms.listbox id="newDomainService" label="{{ __('application.pvd_service') }}" required
                            :options="collect($composeServices)->map(fn ($service) => ['value' => $service, 'label' => $service])->all()" />
                    @endif
                    <x-forms.domain-input id="newDomainParts" />

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                        <x-forms.button type="button" wire:click="generateDomain">{{ __('application.pvd_generate_domain') }}</x-forms.button>
                        <x-forms.button type="submit" isHighlighted>{{ __('application.pvd_save') }}</x-forms.button>
                    </div>
                </form>
            </x-modal-input>
        @endcan
    </div>

    @if (count($domainRows) === 0)
        <div class="application-settings-section-body">
            <x-empty size="sm" title="{{ __('application.pvd_no_domains') }}"
                description="{{ __('application.pvd_no_domains_desc') }}" icon-name="globe" />
        </div>
    @else
        <div class="application-settings-section-body is-flush overflow-visible">
            <div class="data-table-header service-domains-overview-grid">
                <span>{{ __('application.dns_col_domain') }}</span>
                <span>{{ __('application.dns_protocol_redirect') }}</span>
                <span>{{ __('application.dns_domain_redirect') }}</span>
                <span>{{ __('application.dns_internal_port') }}</span>
                <span>{{ __('application.dns_search_indexing') }}</span>
                <span>{{ __('application.dns_status_col') }}</span>
                <span class="text-right">{{ __('application.dns_actions') }}</span>
            </div>
            @foreach (collect($domainRows)->groupBy(fn ($row) => $row['service'] ?? '', preserveKeys: true) as $serviceName => $rows)
                @if ($isCompose)
                    <div wire:key="preview-domain-service-{{ md5($serviceName) }}"
                        x-show="matchesDomainSearch(@js($serviceName.' '.$rows->pluck('url')->implode(' ')))"
                        class="border-b border-neutral-200 bg-neutral-50 px-4 py-3 text-sm font-medium dark:border-white/10 dark:bg-white/[0.04]">{{ $serviceName }}</div>
                @endif
                @foreach ($rows as $index => $row)
                    @php
                        $dnsType = match ($row['dns_status']) {
                            'ok' => 'success',
                            'failed' => 'error',
                            'skipped' => 'warning',
                            default => 'neutral',
                        };
                        $dnsLabel = match ($row['dns_status']) {
                            'ok' => __('application.dns_matches'),
                            'failed' => __('application.dns_mismatch'),
                            'skipped' => __('application.dns_skipped'),
                            'checking' => __('application.dns_checking'),
                            'pending' => __('application.dns_not_checked'),
                            default => __('application.dns_unknown'),
                        };
                        $domainKey = hash('sha256', $row['url'].'|'.($row['service'] ?? ''));
                        $editingParts = \App\Support\DomainUrlParts::split($row['url']);
                        if ($row['has_port_override'] ?? false) {
                            $editingParts['port'] = (string) $row['internal_port'];
                        }
                    @endphp
                    <div wire:key="preview-domain-{{ md5(($row['service'] ?? '') . $row['url']) }}" x-show="matchesDomainSearch(@js(($row['service'] ?? '').' '.$row['url']))" class="env-table-item">
                        <div class="data-table-row service-domains-overview-grid">
                            <div class="flex min-w-0 flex-col gap-1">
                                <div class="flex min-w-0 items-center gap-2">
                                    <x-reicon name="globe" class="size-4 shrink-0 text-neutral-400 dark:text-fg-faint" />
                                    <a href="{{ getFqdnWithoutPort($row['url']) }}" target="_blank" rel="noopener noreferrer"
                                        class="min-w-0 flex-1 truncate text-[13px] text-black underline decoration-neutral-300 underline-offset-2 hover:decoration-coollabs sm:truncate dark:text-fg dark:decoration-white/20 dark:hover:decoration-warning"
                                        title="{{ getFqdnWithoutPort($row['url']) }}">{{ getFqdnWithoutPort($row['url']) }}</a>
                                </div>
                            </div>
                            <div class="service-domain-detail" title="{{ __('application.dns_protocol_redirect') }}">
                                <span class="service-domain-detail-label">{{ __('application.dns_protocol_redirect') }}</span>
                                <span>{{ str_starts_with($row['url'], 'https://') && $preview->application->isForceHttpsEnabled() ? __('application.dns_http_https') : __('application.dns_disabled') }}</span>
                            </div>
                            <div class="service-domain-detail" title="{{ __('application.dns_domain_redirect') }}">
                                <span class="service-domain-detail-label">{{ __('application.dns_domain_redirect') }}</span>
                                <span>{{ match (($row['redirect'] ?? 'both')) { 'www' => __('application.dns_nonwww_www'), 'non-www' => __('application.dns_www_nonwww'), default => __('application.dns_disabled') } }}</span>
                            </div>
                            <div class="service-domain-detail"
                                title="{{ ($row['has_port_override'] ?? false) ? __('application.dns_custom_port') : __('application.dns_inherited_port') }}">
                                <span class="service-domain-detail-label">{{ __('application.dns_internal_port') }}</span>
                                @if (filled($row['internal_port'] ?? null))
                                    <span aria-label="{{ __('application.dns_internal_port') }} {{ $row['internal_port'] }}">{{ $row['internal_port'] }}</span>
                                @else
                                    <span role="img" aria-label="{{ __('application.dns_no_port') }}" title="{{ __('application.dns_no_port_title') }}" class="text-red-500 dark:text-red-400">
                                        <x-reicon name="alert-triangle" class="size-4" />
                                    </span>
                                @endif
                            </div>
                            <div class="service-domain-detail">
                                <span class="service-domain-detail-label">{{ __('application.dns_search_indexing') }}</span>
                                <span role="img" aria-label="{{ __('application.dns_noindexed') }}"
                                    title="{{ __('application.dns_noindexed') }}">
                                    <x-reicon name="x" class="size-4" />
                                </span>
                            </div>

                            <div class="service-domain-mobile-summary" aria-label="{{ __('application.dns_routing_summary') }}">
                                @if (str_starts_with($row['url'], 'https://') && $preview->application->isForceHttpsEnabled())
                                    <span>{{ __('application.dns_http_https') }}</span>
                                @endif
                                @if (in_array($row['redirect'] ?? 'both', ['www', 'non-www'], true))
                                    <span>{{ ($row['redirect'] ?? 'both') === 'www' ? __('application.dns_nonwww_www') : __('application.dns_www_nonwww') }}</span>
                                @elseif (! str_starts_with($row['url'], 'https://') || ! $preview->application->isForceHttpsEnabled())
                                    <span>{{ __('application.dns_no_redirects') }}</span>
                                @endif
                                <span>{{ __('application.dns_port') }} {{ $row['internal_port'] ?? __('application.dns_missing') }}</span>
                                <span>{{ __('application.dns_noindex_short') }}</span>
                            </div>

                            <div class="service-domain-dns flex min-w-0 items-center">
                                @if ($row['dns_status'] === 'checking')
                                    <x-status-badge dynamic :title="$row['dns_message']">
                                        <x-loading compact aria-label="{{ __('application.dns_checking') }}" />
                                        <span class="truncate">{{ __('application.dns_checking') }}</span>
                                    </x-status-badge>
                                @else
                                    <x-status-badge :status="$dnsLabel" :type="$dnsType" :title="$row['dns_message']" />
                                @endif
                            </div>
                            <div class="service-domain-actions flex items-center justify-end gap-1">
                                @can('update', $preview->application)
                                    <button type="button" wire:click="checkDomainDns({{ $index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="checkDomainDns({{ $index }}),checkAllDns"
                                        class="icon-button shrink-0" title="{{ __('application.dns_check') }}" aria-label="{{ __('application.dns_check') }}">
                                        <x-reicon name="refresh" class="size-3.5" />
                                    </button>
                                    <button type="button"
                                        @click="openEditDomain(@js($index), @js($row['url']), @js($editingParts), @js($row['service']))"
                                        class="icon-button shrink-0" title="{{ __('application.dns_settings') }}" aria-label="{{ __('application.dns_settings_for', ['url' => getFqdnWithoutPort($row['url'])]) }}">
                                        <x-reicon name="settings" class="size-3.5" />
                                    </button>
                                    <x-modal-confirmation class="!w-auto shrink-0" title="{{ __('application.dns_remove_title') }}"
                                        buttonTitle="{{ __('application.dns_remove') }}" isErrorButton
                                        submitAction="removeDomainByKey({{ $domainKey }})"
                                        :actions="[
                                            __('application.pvd_remove_action1'),
                                            __('application.pvd_remove_action2'),
                                        ]"
                                        :confirmWithPassword="false" :confirmWithText="false"
                                        step2ButtonText="{{ __('application.dns_remove') }}">
                                        <x-slot:trigger>
                                            <button type="button"
                                                class="icon-button shrink-0 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                                title="{{ __('application.dns_remove') }}" aria-label="{{ __('application.dns_remove') }}">
                                                <x-reicon name="trash" class="size-3.5" />
                                            </button>
                                        </x-slot:trigger>
                                    </x-modal-confirmation>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
            <div x-cloak x-show="domainSearch.trim() && !@js(collect($domainRows)->map(fn ($row) => ($row['service'] ?? '').' '.$row['url'])->values()).some(value => matchesDomainSearch(value))" class="px-4 py-8">
                <x-empty size="sm" title="{{ __('application.pvd_no_domains_found') }}" description="{{ __('application.pvd_no_domains_found_desc') }}" icon-name="search" />
            </div>
        </div>
    @endif

    <template x-teleport="body">
        <div x-show="editOpen" x-cloak class="fixed inset-0 z-99 overflow-y-auto">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="closeEditDomain()"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div x-show="editOpen" x-trap.inert.noscroll="editOpen"
                    data-preview-domain-dialog class="application-settings-form application-settings-section relative w-full max-w-3xl">
                    <header>
                        <h3>{{ __('application.dns_settings') }}</h3>
                        <button type="button" @click="closeEditDomain()" class="icon-button" aria-label="{{ __('application.pvd_close') }}">
                            <x-reicon name="x" class="size-4" />
                        </button>
                    </header>
                    <div class="application-settings-section-body">
                        <form x-ref="editForm" wire:submit="updateDomain" class="flex flex-col gap-4">
                            <div x-show="editingServiceLabel" x-cloak>
                                <div class="mb-1.5 flex h-4 items-center">
                                    <label class="mb-0! leading-4">{{ __('application.pvd_service') }}</label>
                                </div>
                                <input type="text" class="input" readonly x-bind:value="editingServiceLabel" />
                            </div>
                            <x-forms.domain-input id="editingDomainParts" />
                            <div class="grid grid-cols-1 gap-4 border-t border-neutral-200 pt-4 sm:grid-cols-2 dark:border-white/10">
                                <x-forms.listbox id="preview-domain-indexing-{{ $preview->id }}" label="{{ __('application.pvd_search_indexing_label') }}"
                                    :wire="false" value="noindex" disabled
                                    helper="{{ __('application.pvd_noindex_helper') }}"
                                    :options="[['value' => 'noindex', 'label' => __('application.dns_noindex_short')]]" />
                                <x-forms.listbox id="preview-domain-direction-{{ $preview->id }}" label="{{ __('application.pvd_www_redirect') }}"
                                    :wire="false" :value="$editingIndex !== null ? ($domainRows[$editingIndex]['redirect'] ?? 'both') : 'both'" disabled
                                    helper="{{ __('application.pvd_route_helper') }}"
                                    :options="[
                                        ['value' => 'both', 'label' => __('application.pvd_no_redirect')],
                                        ['value' => 'www', 'label' => __('application.pvd_redirect_www')],
                                        ['value' => 'non-www', 'label' => __('application.pvd_redirect_nonwww')],
                                    ]" />
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-neutral-200 pt-4 dark:border-white/10">
                                <x-forms.button type="button" wire:click="regenerateEditingDomain">{{ __('application.pvd_regenerate_hostname') }}</x-forms.button>
                                <x-forms.button type="submit" isHighlighted>{{ __('application.pvd_save') }}</x-forms.button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @if ($showPortWarningModal)
        <div x-data="{ modalOpen: true }"
            @keydown.escape.window="modalOpen = false; $wire.call('cancelUseUnknownPort')"
            class="relative z-40">
            <template x-teleport="body">
                <div x-show="modalOpen"
                    class="fixed inset-0 z-99 flex min-h-full items-center justify-center overflow-y-auto p-4" x-cloak>
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"></div>
                    <div x-show="modalOpen" x-trap.inert.noscroll="modalOpen"
                        class="application-settings-form application-settings-section relative w-full lg:min-w-[36rem] lg:max-w-2xl"
                        style="box-shadow: 0 0 0 1px var(--coollabs-hairline), var(--shadow-modal)">
                        <header>
                            <h3>{{ __('application.pvd_port_modal_title') }}</h3>
                            <button type="button"
                                @click="modalOpen = false; $wire.call('cancelUseUnknownPort')"
                                class="icon-button" aria-label="{{ __('application.pvd_close') }}">
                                <x-reicon name="x" class="size-4" />
                            </button>
                        </header>
                        <div class="application-settings-section-body">
                            <x-callout type="warning" title="{{ __('application.pvd_unrecognized_port') }}" class="mb-4">
                                {!! __('application.pvd_unrecognized_port_body', ['port' => $unrecognizedPort]) !!}
                            </x-callout>

                            <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                                <x-forms.button type="button" canGate="update" :canResource="$preview->application"
                                    @click="modalOpen = false; $wire.call('cancelUseUnknownPort')">
                                    {{ __('application.pvd_cancel') }}
                                </x-forms.button>
                                <x-forms.button type="button" wire:click="confirmUseUnknownPort" canGate="update"
                                    :canResource="$preview->application"
                                    @click="modalOpen = false" isError>
                                    {{ __('application.pvd_use_port_anyway') }}
                                </x-forms.button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
