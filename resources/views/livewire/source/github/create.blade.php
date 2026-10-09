@can('createAnyResource')
    <form wire:submit="createGitHubApp" class="flex w-full flex-col gap-4">
        <p class="text-[12px] leading-5 text-neutral-500 dark:text-fg-dim">
            Connect a GitHub App for private repositories, webhooks, and commit / pull request deployments.
        </p>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-forms.input id="name" label="{{ __('src.name') }}" required />
            <x-forms.input id="organization" label="{{ __('src.organization') }}" :required="$use_for_github_runners"
                :helper="$use_for_github_runners
                    ? __('src.runners_org_helper')
                    : __('src.org_empty_helper')"
                :placeholder="$use_for_github_runners ? __('src.org_placeholder') : __('src.personal_placeholder')" />
        </div>

        @if (! isCloud())
            <div x-data="{ showWarning: @entangle('is_system_wide') }">
                <div class="max-w-xs">
                    <x-forms.checkbox id="is_system_wide" label="{{ __('src.system_wide') }}"
                        helper="{{ __('src.system_wide_github_helper') }}" />
                </div>
                <div x-cloak x-show="showWarning" x-transition class="mt-3">
                    <x-callout type="warning" title="{{ __('src.shared_every_team') }}">
                        {{ __('src.system_wide_github_warning') }}
                    </x-callout>
                </div>
            </div>
        @endif

        <div>
            <p class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">{{ __('src.use_app_for') }}</p>
            <p class="mt-1 text-[12px] leading-5 text-neutral-500 dark:text-fg-dim">
                {{ __('src.use_app_helper') }}
            </p>
            <div class="mt-2 flex max-w-xs flex-col gap-1">
                <x-forms.checkbox id="use_for_pull_request_previews" label="{{ __('src.pr_previews') }}"
                    helper="{{ __('src.pr_previews_helper') }}" />
                <x-forms.checkbox id="use_for_github_runners" live label="{{ __('src.gh_actions_runners') }}"
                    helper="{{ __('src.runners_helper') }}" />
            </div>
        </div>

        <div x-data="{
            open: false,
        }" class="rounded-lg border border-neutral-200 dark:border-white/[0.08]">
            <button type="button" @click="open = !open"
                class="flex w-full items-center justify-between px-3 py-2.5 text-left text-[12px] font-medium text-neutral-700 transition-colors hover:bg-neutral-50 dark:text-fg-dim dark:hover:bg-white/[0.03]">
                {{ __('src.self_hosted_enterprise') }}
                <svg class="size-3.5 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div x-cloak x-show="open" x-collapse.duration.200ms class="border-t border-neutral-200 px-3 py-3 dark:border-white/[0.08]">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-forms.input id="html_url" label="{{ __('src.html_url') }}" required
                        helper="{{ __('src.html_url_helper') }}" />
                    <x-forms.input id="api_url" label="{{ __('src.api_url') }}" required
                        helper="{{ __('src.api_url_helper') }}" />
                    <x-forms.input id="custom_user" label="{{ __('src.custom_user') }}" required />
                    <x-forms.input id="custom_port" type="number" label="{{ __('src.custom_port') }}" required />
                </div>
            </div>
        </div>

        <x-forms.button class="mt-1 w-full justify-center" type="submit">
            {{ __('src.continue') }}
        </x-forms.button>
    </form>
@else
    <x-callout type="danger" title="{{ __('src.insufficient_permissions') }}">
        {{ __('src.github_no_perm') }}
    </x-callout>
@endcan
