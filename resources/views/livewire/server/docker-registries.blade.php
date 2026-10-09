<div class="w-full">
    <x-slot:title>
        {{ __('server.registries_title') }} | ABao
    </x-slot>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 flex-col gap-1">
            <h1 class="min-w-0 text-[24px]! leading-7! font-semibold! tracking-tight!">{{ __('server.registries_title') }}</h1>
            <p class="text-[12px] text-neutral-500 dark:text-fg-dim">
                {{ __('server.registries_description_before') }} <b>{{ __('server.registries_multi_login') }}</b>{{ __('server.registries_description_mid') }}<code>docker login &lt;registry&gt;</code>{{ __('server.registries_description_after') }}
            </p>
        </div>
        @if ($servers->isNotEmpty())
            <x-modal-input :buttonTitle="__('server.registries_multi_login')" :title="__('server.registries_login_title')" isHighlightedButton
                :subtitle="__('server.registries_login_subtitle')">
                <livewire:server.docker-registries.login key="registry-login-overview" />
            </x-modal-input>
        @endif
    </div>

    @if ($servers->isEmpty())
        <x-empty :title="__('server.empty_title')"
            :description="__('server.registries_empty_description')"
            icon-name="servers" />
    @else
        <div class="flex flex-col gap-4">
            @foreach ($servers as $server)
                <livewire:server.docker-registries.server-registries :server="$server"
                    :key="'docker-registries-'.$server->uuid" />
            @endforeach
        </div>
    @endif
</div>
