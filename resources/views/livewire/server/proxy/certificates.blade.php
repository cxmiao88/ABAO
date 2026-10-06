@php use App\Enums\ProxyTypes; @endphp

<div class="application-settings-form flex w-full flex-col gap-6">
    @if ($server->hasPendingProxyConfiguration())
        <x-callout type="warning" :title="__('server.px_restart_required_title')">
            {{ __('server.px_restart_required_text') }}
        </x-callout>
    @endif
    @if ($server->proxyType() === ProxyTypes::TRAEFIK->value)
        <x-application.settings-section id="server-proxy-certificates-section" :title="__('server.sub_tls_certificates')"
            x-init="$wire.loadTraefikCertificates()"
            :helper="__('server.cert_helper')">
            <x-slot:actions>
                <x-forms.button type="button" wire:click="loadTraefikCertificates"
                    wire:loading.attr="disabled" wire:target="loadTraefikCertificates">
                    {{ __('server.refresh') }}
                </x-forms.button>
            </x-slot:actions>

            <div wire:loading.flex wire:target="loadTraefikCertificates"
                class="min-h-24 items-center justify-center">
                <x-loading :text="__('server.cert_loading')" />
            </div>

            <div wire:loading.remove wire:target="loadTraefikCertificates">
                @if ($traefikCertificatesLoaded && count($traefikCertificates) === 0)
                    <x-empty size="sm" :title="__('server.cert_empty_title')"
                        :description="__('server.cert_empty_description')"
                        icon-name="shield-star" />
                @elseif (count($traefikCertificates) > 0)
                    <div class="overflow-hidden rounded-lg ring-1 ring-neutral-200 dark:ring-white/[0.08]">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-3xl">
                                <thead>
                                    <tr>
                                        <th>{{ __('server.cert_col_domain') }}</th>
                                        <th>{{ __('server.cert_col_resolver') }}</th>
                                        <th>{{ __('server.cert_col_sans') }}</th>
                                        <th>{{ __('server.cert_col_expires') }}</th>
                                        <th><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($traefikCertificates as $certificate)
                                        <tr wire:key="traefik-certificate-{{ $certificate['id'] }}">
                                            <td class="font-medium text-neutral-950 dark:text-fg">
                                                {{ $certificate['main_domain'] }}
                                            </td>
                                            <td>
                                                <span class="font-mono text-xs">{{ $certificate['resolver'] }}</span>
                                                @if ($certificate['store'])
                                                    <span class="text-xs text-neutral-500 dark:text-fg-dim">
                                                        ({{ $certificate['store'] }})
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (count($certificate['sans']) > 0)
                                                    <div class="flex max-w-md flex-wrap gap-1">
                                                        @foreach ($certificate['sans'] as $domain)
                                                            <span
                                                                class="rounded bg-neutral-100 px-1.5 py-0.5 font-mono text-xs text-neutral-700 dark:bg-white/[0.06] dark:text-fg-dim">
                                                                {{ $domain }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-neutral-500 dark:text-fg-dim">{{ __('server.cert_none') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $certificate['expires_at'] ?? __('server.cert_unknown') }}
                                            </td>
                                            <td class="text-right">
                                                @can('update', $server)
                                                    <x-modal-confirmation :title="__('server.cert_delete_title')"
                                                        :buttonTitle="__('server.image_delete_button')"
                                                        submitAction="deleteTraefikCertificate({{ $certificate['id'] }})"
                                                        :actions="[
                                                            __('server.cert_delete_action_1'),
                                                            __('server.cert_delete_action_2', ['domain' => $certificate['main_domain']]),
                                                        ]"
                                                        :warningMessage="__('server.cert_delete_warning')"
                                                        confirmationText="{{ $certificate['main_domain'] }}"
                                                        :confirmationLabel="__('server.cert_delete_confirm_label')"
                                                        :shortConfirmationLabel="__('server.cert_delete_short_label')"
                                                        :step2ButtonText="__('server.cert_delete_step2')"
                                                        isErrorButton :confirmWithPassword="false"
                                                        :confirmWithText="true" />
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @can('manageProxy', $server)
                    @if (count($traefikAcmeBackups) > 0)
                        <div class="mt-6 flex flex-col gap-2">
                            <div>
                                <h4 class="text-sm font-medium text-neutral-950 dark:text-fg">{{ __('server.cert_backups_title') }}</h4>
                                <p class="mt-1 text-xs text-neutral-500 dark:text-fg-dim">
                                    {{ __('server.cert_backups_description', ['count' => \App\Actions\Proxy\ListTraefikAcmeBackups::KEEP]) }}
                                </p>
                            </div>
                            <div class="overflow-hidden rounded-lg ring-1 ring-neutral-200 dark:ring-white/[0.08]">
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-2xl">
                                        <thead>
                                            <tr>
                                                <th>{{ __('server.cert_col_backup') }}</th>
                                                <th>{{ __('server.cert_col_created') }}</th>
                                                <th>{{ __('server.cert_col_size') }}</th>
                                                <th><span class="sr-only">Actions</span></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($traefikAcmeBackups as $backup)
                                                <tr wire:key="traefik-acme-backup-{{ $backup['name'] }}">
                                                    <td class="font-mono text-xs text-neutral-950 dark:text-fg">
                                                        {{ $backup['name'] }}
                                                    </td>
                                                    <td>{{ $backup['created_at'] }}</td>
                                                    <td>{{ formatBytes($backup['size']) }}</td>
                                                    <td>
                                                        <div class="flex justify-end gap-2">
                                                            <x-modal-confirmation :title="__('server.cert_restore_title')"
                                                                :buttonTitle="__('server.cert_restore_button')"
                                                                submitAction="restoreTraefikAcmeBackup('{{ $backup['name'] }}')"
                                                                :checkboxes="[
                                                                    ['id' => 'restartProxyAfterAcmeRestore', 'label' => __('server.cert_restore_restart_label')],
                                                                ]"
                                                                :actions="[
                                                                    __('server.cert_restore_action_1'),
                                                                    __('server.cert_restore_action_2', ['time' => $backup['created_at']]),
                                                                ]"
                                                                :warningMessage="__('server.cert_restore_warning')"
                                                                :step2ButtonText="__('server.cert_restore_step2')"
                                                                :confirmWithPassword="false"
                                                                :confirmWithText="false" />
                                                            <x-modal-confirmation :title="__('server.cert_backup_delete_title')"
                                                                :buttonTitle="__('server.image_delete_button')"
                                                                submitAction="deleteTraefikAcmeBackup('{{ $backup['name'] }}')"
                                                                :actions="[__('server.cert_backup_delete_action', ['name' => $backup['name']])]"
                                                                :step2ButtonText="__('server.cert_backup_delete_step2')"
                                                                isErrorButton :confirmWithPassword="false"
                                                                :confirmWithText="false" />
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                @endcan
            </div>
        </x-application.settings-section>
    @endif

</div>
