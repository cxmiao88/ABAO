<x-layout-simple>
    <x-auth.shell title="ABao" description="{{ __('auth2_verify_desc') }}">
        <div class="flex flex-col gap-4">
            <div class="auth-guidance">
                <x-reicon name="mail" class="mt-0.5 size-4 shrink-0" />
                <p>{{ __('auth2_verify_sent') }}</p>
            </div>

            <livewire:verify-email />
        </div>

        <x-slot:footer>
            <span class="text-center">
                <span class="block sm:inline">{{ __('auth2_verify_not_received') }}</span>
                <span class="block sm:ml-1 sm:inline">{{ __('auth2_verify_check_spam') }}</span>
            </span>
        </x-slot:footer>
    </x-auth.shell>
</x-layout-simple>
