<div>
    @can('manageInvitations', currentTeam())
        <form wire:submit="viaLink">
            <x-application.settings-section title="{{ __('team_invite_member') }}"
                description="{{ __('team_invite_desc') }}">
                <x-slot:actions>
                    @if (is_transactional_emails_enabled())
                        <x-forms.button type="button" wire:click.prevent="viaEmail">
                            <x-reicon name="notifications" class="size-3.5" />
                            {{ __('team_send_email') }}
                        </x-forms.button>
                    @endif
                    <x-forms.button type="submit" wire:target="viaLink"
                        defaultClass="button button-highlighted">
                        <x-reicon name="plus" class="size-3.5" />
                        {{ __('team_generate_link') }}
                    </x-forms.button>
                </x-slot:actions>

                @if (!is_transactional_emails_enabled() && isInstanceAdmin())
                    <x-callout type="warning" title="{{ __('team_email_not_configured') }}">
                        {{ __('team_email_not_configured_helper') }}
                    </x-callout>
                @endif

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <x-forms.input id="email" type="email" label="{{ __('team_email_address') }}"
                        placeholder="{{ __('team_email_placeholder') }}" required />
                    <x-forms.listbox id="role" label="{{ __('team_role') }}" :options="array_values(array_filter([
                        auth()->user()->role() === 'owner' ? ['value' => 'owner', 'label' => __('team_owner')] : null,
                        ['value' => 'admin', 'label' => __('team_admin')],
                        ['value' => 'member', 'label' => __('team_member')],
                    ]))" />
                </div>
            </x-application.settings-section>
        </form>
    @endcan
</div>
