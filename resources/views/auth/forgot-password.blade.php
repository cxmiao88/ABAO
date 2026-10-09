<x-layout-simple>
    <x-auth.shell title="ABao"
        description="{{ __('auth2_forgot_desc') }}">
        <div class="flex flex-col gap-4">
            @if (session('status'))
                <x-auth.alert type="success">{{ session('status') }}</x-auth.alert>
            @endif

            @if ($errors->any())
                <x-auth.alert type="error">
                    <div class="flex flex-col gap-1">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </x-auth.alert>
            @endif

            @if (is_transactional_emails_enabled())
                <form action="/forgot-password" method="POST" class="flex flex-col gap-4">
                    @csrf
                    <x-forms.input required type="email" name="email" autocomplete="email" autofocus
                        label="{{ __('input.email') }}" />
                    <x-forms.button class="w-full justify-center" type="submit" isHighlighted>
                        {{ __('auth.forgot_password_send_email') }}
                    </x-forms.button>
                </form>
            @else
                <x-auth.alert type="warning">
                    <p class="font-medium">{{ __('auth2_txn_not_configured') }}</p>
                    <p class="mt-0.5 text-black/70 dark:text-white/70">
                        {{ __('auth2_txn_guide_pre') }}
                        <a class="font-medium underline underline-offset-2" target="_blank" rel="noopener noreferrer"
                            href="{{ config('constants.urls.docs') }}">{{ __('auth2_txn_guide_link') }}</a>{{ __('auth2_txn_guide_post') }}
                    </p>
                </x-auth.alert>
            @endif
        </div>

        <x-slot:footer>
            <span>{{ __('auth2_remember_password') }}</span>
            <a href="/login" class="auth-text-link">{{ __('auth2_back_to_login') }}</a>
        </x-slot:footer>
    </x-auth.shell>
</x-layout-simple>
