@props([
    'provider',
    'providerLabel',
    'routeType' => null,
    'tokens',
])

<x-application.settings-section :title="__('server.token_picker_account', ['provider' => $providerLabel])"
    :description="__('server.token_picker_description')" flush>
    @if ($tokens->isEmpty())
        <x-empty :title="__('server.token_picker_no_tokens', ['provider' => $providerLabel])"
            :description="__('server.token_picker_add_token_hint')" icon-name="keys" size="sm">
            <x-slot:actions>
                <x-modal-input :title="__('server.token_picker_add_token', ['provider' => $providerLabel])">
                    <x-slot:content>
                        <button type="button"
                            class="button button-highlighted">
                            <x-reicon name="plus" class="size-3.5" />
                            {{ __('server.token_picker_add_token_button') }}
                        </button>
                    </x-slot:content>
                    <livewire:security.cloud-provider-token-form :modal_mode="true" :provider="$provider"
                        wire:key="new-server-provider-token-{{ $provider }}" />
                </x-modal-input>
            </x-slot:actions>
        </x-empty>
    @else
        <div class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($tokens as $token)
                <a wire:key="{{ $provider }}-token-{{ $token->id }}"
                    class="group flex min-h-28 min-w-0 flex-col rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:no-underline hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]"
                    href="{{ route('server.create.token', ['type' => $routeType ?: $provider, 'token_uuid' => $token->uuid]) }}"
                    {{ wireNavigate() }}>
                    <div class="flex items-start gap-3">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                            <x-reicon name="keys" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-[13px]! font-semibold! text-black dark:text-fg">
                                {{ $token->name ?? $providerLabel . ' token' }}
                            </h3>
                            <p class="mt-1 line-clamp-2 text-[11px] leading-4 text-neutral-500 dark:text-fg-faint">
                                {{ $token->description ?: __('server.token_picker_use_token') }}
                            </p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</x-application.settings-section>
