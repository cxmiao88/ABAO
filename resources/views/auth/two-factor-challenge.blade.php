<x-layout-simple>
    <x-auth.shell title="ABao" description="{{ __('auth2_2fa_desc') }}">
        <div class="flex flex-col gap-4" x-data="{
            showRecovery: false,
            submitting: false,
            handleSubmit(event) {
                if (this.submitting) {
                    event.preventDefault();
                }

                this.submitting = true;
            },
            submitAuthenticatorCode(event) {
                event.target.value = event.target.value.replace(/\D/g, '').slice(0, 6);

                if (event.target.value.length === 6) {
                    this.$nextTick(() => this.$refs.challengeForm.requestSubmit());
                }
            },
        }">
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

            <div class="auth-guidance">
                <x-reicon name="info-circle" class="mt-0.5 size-4 shrink-0" />
                <p x-show="!showRecovery">{{ __('auth2_2fa_code') }}</p>
                <p x-show="showRecovery" x-cloak>{{ __('auth2_2fa_recovery') }}</p>
            </div>

            <form x-ref="challengeForm" action="/two-factor-challenge" method="POST" class="flex flex-col gap-4"
                @submit="handleSubmit($event)">
                @csrf

                <div x-show="!showRecovery" class="flex flex-col gap-3">
                    <input x-ref="authenticatorCode" type="text" name="code" inputmode="numeric"
                        pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" autofocus
                        aria-label="{{ __('auth2_2fa_aria') }}" :disabled="showRecovery"
                        @input="submitAuthenticatorCode($event)"
                        class="mx-auto h-14 w-64 rounded-md border border-neutral-300 bg-white px-4 text-center text-xl font-semibold tracking-[0.5em] text-neutral-900 transition-colors focus:border-warning focus:outline-none focus:ring-1 focus:ring-warning dark:border-white/10 dark:bg-coolgray-100 dark:text-white" />
                    <button type="button" class="auth-text-link self-center"
                        x-on:click="showRecovery = true; $nextTick(() => $refs.recoveryCode.focus())">
                        {{ __('auth2_use_recovery') }}
                    </button>
                </div>

                <div x-show="showRecovery" x-cloak class="flex flex-col gap-3">
                    <x-forms.input x-ref="recoveryCode" name="recovery_code" autocomplete="one-time-code"
                        x-bind:disabled="!showRecovery" label="{{ __('input.recovery_code') }}" />
                    <button type="button" class="auth-text-link self-center"
                        x-on:click="showRecovery = false; $nextTick(() => $refs.authenticatorCode.focus())">
                        {{ __('auth2_use_authenticator') }}
                    </button>
                </div>

                <x-forms.button class="w-full justify-center" type="submit" x-bind:disabled="submitting" isHighlighted>
                    {{ __('auth2_verify_continue') }}
                </x-forms.button>
            </form>
        </div>

        <x-slot:footer>
            <span>{{ __('auth2_not_account') }}</span>
            <a href="/login" class="auth-text-link">{{ __('auth2_back_to_login') }}</a>
        </x-slot:footer>
    </x-auth.shell>
</x-layout-simple>
