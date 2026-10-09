<div x-data="{
    emailModalOpen: @js($show_verification),
    openEmailModal() {
        this.emailModalOpen = true;
        this.$nextTick(() => this.$refs.newEmailInput?.focus());
    },
}"
    @close-email-change-modal.window="emailModalOpen = false">
    <x-slot:title>{{ __('prof_title') }}</x-slot>
    <div class="mt-8 flex w-full max-w-none flex-col gap-6 lg:mt-3">
        <section class="application-settings-section" x-data="{
            preview: null,
            processing: false,
            uploadError: null,
            async prepareAvatar(event) {
                const file = event.target.files?.[0];
                if (!file) return;
                this.processing = true;
                this.uploadError = null;

                try {
                    const dataUrl = await new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(reader.result);
                        reader.onerror = reject;
                        reader.readAsDataURL(file);
                    });
                    const image = await new Promise((resolve, reject) => {
                        const element = new Image();
                        element.onload = () => resolve(element);
                        element.onerror = reject;
                        element.src = dataUrl;
                    });
                    const cropSize = Math.min(image.naturalWidth, image.naturalHeight);
                    const canvas = document.createElement('canvas');
                    canvas.width = 256;
                    canvas.height = 256;
                    const context = canvas.getContext('2d');
                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, 256, 256);
                    context.drawImage(
                        image,
                        (image.naturalWidth - cropSize) / 2,
                        (image.naturalHeight - cropSize) / 2,
                        cropSize,
                        cropSize,
                        0,
                        0,
                        256,
                        256,
                    );
                    const blob = await new Promise((resolve, reject) => {
                        canvas.toBlob(value => value ? resolve(value) : reject(new Error('JPEG compression failed')), 'image/jpeg', 0.8);
                    });
                    const previewUrl = URL.createObjectURL(blob);
                    const compressed = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                    this.$wire.upload('avatar', compressed, async () => {
                        try {
                            const uploaded = await this.$wire.uploadAvatar();
                            if (uploaded) {
                                if (this.preview) URL.revokeObjectURL(this.preview);
                                this.preview = previewUrl;
                            } else {
                                URL.revokeObjectURL(previewUrl);
                            }
                        } catch (error) {
                            URL.revokeObjectURL(previewUrl);
                            this.uploadError = '{{ __("prof_upload_error") }}';
                        } finally {
                            this.processing = false;
                        }
                    }, () => {
                        URL.revokeObjectURL(previewUrl);
                        this.processing = false;
                        this.uploadError = '{{ __("prof_upload_error") }}';
                    });
                } catch (error) {
                    this.processing = false;
                    this.uploadError = '{{ __("prof_process_error") }}';
                }
            },
        }">
            <div class="application-settings-section-header">
                <div>
                    <h2>{{ __('prof_picture') }}</h2>
                    <p>{{ __('prof_picture_desc') }}</p>
                </div>
            </div>
            <div class="application-settings-section-body flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-neutral-200 text-2xl font-semibold text-neutral-700 dark:bg-white/[0.1] dark:text-fg">
                    <img x-cloak x-show="preview" :src="preview" alt="{{ __('prof_picture_preview') }}"
                        class="h-full w-full object-cover">
                    @if (auth()->user()->avatar_path)
                        <img src="{{ profile_avatar_url(auth()->user()) }}"
                            x-show="!preview" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                    @else
                        <span x-show="!preview">
                            {{ strtoupper(mb_substr(auth()->user()->name ?: auth()->user()->email, 0, 1)) }}
                        </span>
                    @endif
                </div>
                <div class="flex min-w-0 flex-1 flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <input x-ref="avatarInput" type="file" x-on:change="prepareAvatar($event)"
                            accept="image/jpeg,image/png,image/webp" class="hidden">
                        <x-forms.button type="button" x-on:click="$refs.avatarInput.click()"
                            x-bind:disabled="processing">
                            <span x-text="processing ? '{{ __("prof_uploading") }}' : '{{ __("prof_browse") }}'"></span>
                        </x-forms.button>
                        @if (auth()->user()->avatar_path)
                            <x-forms.button type="button" wire:click="removeAvatar" x-bind:disabled="processing"
                                isError>{{ __('prof_remove') }}</x-forms.button>
                        @endif
                    </div>
                    <p x-cloak x-show="uploadError" x-text="uploadError" class="text-xs text-red-500"></p>
                    @error('avatar')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <form wire:submit="submit">
            <x-unsaved-bar action="submit" />
            <section class="application-settings-section">
                <div class="application-settings-section-header">
                    <div>
                        <h2>{{ __('prof_details') }}</h2>
                        <p>{{ __('prof_details_desc') }}</p>
                    </div>
                </div>
                <div class="application-settings-section-body grid gap-4 sm:grid-cols-2">
                    <x-forms.input id="name" :label="__('prof_name')" required />
                    <div class="flex items-end gap-2">
                        <x-forms.input id="email" :label="__('prof_email')" readonly />
                        <x-forms.button @click="openEmailModal()" type="button"
                            :disabled="$uses_sso" x-bind:disabled="emailModalOpen || @js($uses_sso)">
                            {{ __('prof_change') }}
                        </x-forms.button>
                    </div>
                </div>
             </section>
         </form>

         <section class="application-settings-section">
             <div class="application-settings-section-header">
                 <div>
                     <h2>{{ __('prof_language') }}</h2>
                     <p>{{ __('prof_language_desc') }}</p>
                 </div>
             </div>
             <div class="application-settings-section-body">
                 <livewire:language-switcher />
             </div>
         </section>

         @if ($uses_sso)
             <x-callout type="info" :title="__('prof_sso_title')">
                 {!! __('prof_sso_desc', ['provider' => $sso_provider_label ?: __('prof_sso_provider')]) !!}
             </x-callout>
         @endif

         @if (! $uses_sso)
         <template x-teleport="body">
            <div x-show="emailModalOpen" x-cloak
                class="fixed inset-0 z-99 flex h-screen w-screen items-center justify-center p-4">
                <div class="absolute inset-0 h-full w-full bg-black/55 backdrop-blur-[3px]"></div>
                <div x-show="emailModalOpen" x-trap.inert.noscroll="emailModalOpen"
                    class="application-settings-form application-settings-section relative w-full max-w-xl overflow-hidden"
                    style="box-shadow: 0 0 0 1px var(--coollabs-hairline), var(--shadow-modal)">
                    <header>
                        <div>
                            <h3>{{ $show_verification ? __('prof_verify_new_email') : __('prof_change_email') }}</h3>
                            <p class="mt-1 text-xs text-neutral-500 dark:text-fg-dim">
                                @if ($show_verification)
                                    {{ __('prof_code_sent', ['email' => $new_email ?: auth()->user()->pending_email]) }}
                                @else
                                    {{ __('prof_code_will_send') }}
                                @endif
                            </p>
                        </div>
                        <button type="button"
                            @click="@if ($show_verification) $wire.cancelEmailChange().then(() => emailModalOpen = false) @else emailModalOpen = false @endif"
                            class="icon-button shrink-0" aria-label="{{ __('prof_close') }}">
                            <x-reicon name="x" class="size-4" />
                        </button>
                    </header>

                    @if ($show_verification)
                        <form wire:submit="verifyEmailChange" class="application-settings-section-body space-y-4">
                            <x-forms.input id="email_verification_code" :label="__('prof_verification_code')" required
                                inputmode="numeric" maxlength="6" />
                            <p class="text-xs text-neutral-500 dark:text-fg-dim">
                                {{ __('prof_code_expires', ['minutes' => config('constants.email_change.verification_code_expiry_minutes', 10)]) }}
                            </p>
                            <div class="flex justify-end gap-2">
                                <x-forms.button wire:click="resendVerificationCode" type="button">{{ __('prof_resend_code') }}</x-forms.button>
                                <x-forms.button type="submit" isHighlighted>{{ __('prof_verify_email') }}</x-forms.button>
                            </div>
                        </form>
                    @else
                        <form wire:submit="requestEmailChange" class="application-settings-section-body space-y-4">
                            <x-forms.input id="new_email" :label="__('prof_new_email')" required type="email"
                                x-ref="newEmailInput" />
                            <div class="flex justify-end">
                                <x-forms.button type="submit" isHighlighted>{{ __('prof_send_code') }}</x-forms.button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
         </template>
         @endif

        <form wire:submit="resetPassword">
            <section class="application-settings-section">
                <div class="application-settings-section-header">
                    <div>
                        <h2>{{ __('prof_password') }}</h2>
                        <p>{{ __('prof_password_desc') }}</p>
                    </div>
                    <x-forms.button type="submit">{{ __('prof_change_password') }}</x-forms.button>
                </div>
                <div class="application-settings-section-body grid gap-4 sm:grid-cols-2">
                    <x-forms.input class="sm:col-span-2" id="current_password" :label="__('prof_current_password')"
                        required type="password" />
                    <x-forms.input id="new_password" :label="__('prof_new_password')" required type="password" />
                    <x-forms.input id="new_password_confirmation" :label="__('prof_confirm_password')" required
                        type="password" />
                </div>
            </section>
        </form>

        <section class="application-settings-section">
            <div class="application-settings-section-header">
                <div>
                    <h2>{{ __('prof_2fa') }}</h2>
                    <p>{{ __('prof_2fa_desc') }}</p>
                </div>
                @if (request()->user()->two_factor_confirmed_at)
                    <x-status-badge :status="__('prof_enabled')" type="success" />
                @elseif (session('status') !== 'two-factor-authentication-enabled')
                    <form action="/user/two-factor-authentication" method="POST">
                        @csrf
                        <x-forms.button type="submit">{{ __('prof_configure_2fa') }}</x-forms.button>
                    </form>
                @endif
            </div>
            <div class="application-settings-section-body">
                @if (session('status') === 'two-factor-authentication-enabled')
                    <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
                        <div
                            class="flex aspect-square items-center justify-center rounded-[10px] border border-neutral-200 bg-white p-5 dark:border-white/[0.07] dark:bg-white/[0.06]">
                            {!! request()->user()->twoFactorQrCodeSvg() !!}
                        </div>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-semibold text-black dark:text-fg">{{ __('prof_finish_setup') }}</h3>
                                <p class="mt-1 text-sm text-neutral-500 dark:text-fg-dim">
                                    {{ __('prof_finish_setup_desc') }}
                                </p>
                            </div>
                            <form action="/user/confirmed-two-factor-authentication" method="POST"
                                class="flex items-end gap-2"
                                x-init="$nextTick(() => $el.querySelector('input[name=code]')?.focus())">
                                @csrf
                                <x-forms.input name="code" type="text" inputmode="numeric" pattern="[0-9]*" id="code"
                                    :label="__('prof_one_time_code')" required />
                                <x-forms.button type="submit">{{ __('prof_validate_2fa') }}</x-forms.button>
                            </form>
                            <div x-data="{ showCode: false }">
                                <div x-cloak x-show="showCode" class="space-y-2 pb-3">
                                    <x-forms.copy-input
                                        :text="decrypt(request()->user()->two_factor_secret)" />
                                    <x-forms.copy-input :text="request()->user()->twoFactorQrCodeUrl()" />
                                </div>
                                <x-forms.button type="button" x-on:click="showCode = !showCode">
                                    <span x-text="showCode ? '{{ __("prof_hide_manual") }}' : '{{ __("prof_show_manual") }}'"></span>
                                </x-forms.button>
                            </div>
                        </div>
                    </div>
                @elseif (request()->user()->two_factor_confirmed_at)
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <form action="/user/two-factor-recovery-codes" method="POST">
                                @csrf
                                <x-forms.button type="submit">{{ __('prof_regenerate_codes') }}</x-forms.button>
                            </form>
                            <form action="/user/two-factor-authentication" method="POST">
                                @csrf
                                @method('DELETE')
                                <x-forms.button type="submit" isError>{{ __('prof_disable_2fa') }}</x-forms.button>
                            </form>
                        </div>
                        @if (session('status') === 'two-factor-authentication-confirmed'
                                || session('status') === 'recovery-codes-generated')
                            <div
                                class="grid gap-2 rounded-lg border border-neutral-200 bg-neutral-50 p-4 font-mono text-xs text-neutral-700 sm:grid-cols-2 dark:border-white/[0.07] dark:bg-white/[0.05] dark:text-fg-dim">
                                @foreach (request()->user()->recoveryCodes() as $code)
                                    <div>{{ $code }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <x-empty size="sm" :title="__('prof_2fa_off')"
                        :description="__('prof_2fa_off_desc')"
                        icon-name="keys" />
                @endif
            </div>
        </section>

        <section class="application-settings-section">
            <div class="application-settings-section-header">
                <div>
                    <h2>{{ __('prof_danger_zone') }}</h2>
                    <p>{{ __('prof_danger_zone_desc') }}</p>
                </div>
            </div>
            <div class="application-settings-section-body">
                <x-danger-zone :title="__('prof_delete_account')">
                    @if ($accountDeletionBlockers === [])
                        <p>
                            {{ __('prof_delete_account_desc') }}
                        </p>
                        <ul class="space-y-1 text-xs">
                            <li>{{ __('prof_delete_li1') }}</li>
                            <li>{{ __('prof_delete_li2') }}</li>
                            <li>{{ __('prof_delete_li3') }}</li>
                        </ul>
                    @else
                        <p>{{ __('prof_delete_blocked') }}</p>
                        <ul class="space-y-1">
                            @foreach ($accountDeletionBlockers as $blocker)
                                <li>{{ $blocker }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <x-slot:action>
                        @if ($accountDeletionBlockers === [])
                            <x-modal-confirmation :title="__('prof_confirm_delete')" :buttonTitle="__('prof_delete_account')"
                                isErrorButton submitAction="deleteAccount"
                                :actions="$accountDeletionActions"
                                confirmationText="{{ $email }}"
                                :confirmationLabel="__('prof_confirm_delete_label')"
                                :shortConfirmationLabel="__('prof_email_short')" :step3ButtonText="__('prof_permanently_delete')" />
                        @else
                            <x-forms.button isError disabled
                                :tooltip="__('prof_delete_tooltip')">
                                {{ __('prof_delete_account') }}
                            </x-forms.button>
                        @endif
                    </x-slot:action>
                </x-danger-zone>
            </div>
        </section>

        @if (session()->has('errors'))
            <x-callout type="danger" :title="__('prof_update_failed')">
                {{ __('prof_update_failed_desc') }}
            </x-callout>
        @endif
    </div>
</div>
