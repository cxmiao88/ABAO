<x-layout-simple>
    <x-auth.shell title="{{ __('auth.reset_password') }}"
        description="{{ __('auth2_reset_desc') }}">
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

            <form action="/reset-password" method="POST" class="flex flex-col gap-4">
                @csrf
                <input hidden id="token" name="token" value="{{ request()->route('token') }}">
                <input hidden value="{{ request()->query('email') }}" type="email" name="email" />
                <x-forms.input required type="password" id="password" name="password" autocomplete="new-password"
                    autofocus label="{{ __('input.password') }}" />
                <x-forms.input required type="password" id="password_confirmation" name="password_confirmation"
                    autocomplete="new-password" label="{{ __('input.password.again') }}" />

                <div class="auth-guidance">
                    <x-reicon name="info-circle" class="mt-0.5 size-4 shrink-0" />
                    <p>{{ __('auth2_password_guidance') }}</p>
                </div>

                <x-forms.button class="w-full justify-center" type="submit" isHighlighted>
                    {{ __('auth.reset_password') }}
                </x-forms.button>
            </form>
        </div>

        <x-slot:footer>
            <span>{{ __('auth2_remember_password') }}</span>
            <a href="/login" class="auth-text-link">{{ __('auth2_back_to_login') }}</a>
        </x-slot:footer>
    </x-auth.shell>
</x-layout-simple>
