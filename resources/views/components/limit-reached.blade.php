<div class="flex flex-col items-center justify-center h-32">
    <span class="text-xl font-bold dark:text-white">{{ __('limit.reached', ['name' => $name]) }}</span>
    <span>{{ __('limit.upgrade_before') }}<a class="dark:text-white underline" {{ wireNavigate() }}
            href="{{ route('subscription.show') }}">{{ __('limit.upgrade_link') }}</a>{{ __('limit.upgrade_after', ['name' => $name]) }}</span>
</div>
