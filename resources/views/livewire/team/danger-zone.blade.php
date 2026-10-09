<div>
    @php
        $deletionBlockers = currentTeam()->deletionBlockers();
        $blockerDetails = [
            'projects' => ['label' => __('team_project'), 'route' => 'project.index'],
            'servers' => ['label' => __('team_server'), 'route' => 'server.index'],
            'sources' => ['label' => __('team_git_source'), 'route' => 'source.all'],
        ];
    @endphp
    <x-slot:title>
        {{ __('team_danger_title') }}
    </x-slot>

    <x-team.settings-layout>
        <div class="application-settings-form">
            <x-application.settings-section id="team-danger-zone" title="{{ __('team_danger_zone') }}"
                helper="{{ __('team_danger_helper') }}">
                <x-danger-zone title="{{ __('team_delete_team') }}">
                            @if (auth()->user()->roleInTeam(currentTeam()->id) !== 'owner')
                                <p>
                                    {{ __('team_only_owner') }}
                                </p>
                            @elseif (session('currentTeam.id') === 0)
                                <p>
                                    {{ __('team_default_team') }}
                                </p>
                            @elseif(auth()->user()->teams()->count() === 1 || auth()->user()->currentTeam()->personal_team)
                                <p>
                                    {{ __('team_last_personal') }}
                                </p>
                            @elseif(currentTeam()->subscription)
                                <p>
                                    {!! __('team_cancel_subscription', ['link' => '<a class="font-medium text-coollabs hover:underline dark:text-warning" {{ wireNavigate() }} href="'.route('subscription.show').'">'.__('team_subscription').'</a>']) !!}
                                </p>
                            @elseif($deletionBlockers === [])
                                <p>
                                    {!! __('team_permanently_delete', ['name' => '<strong class="font-semibold text-black dark:text-fg">'.e(currentTeam()->name).'</strong>']) !!}
                                </p>
                                <ul class="space-y-1 text-xs">
                                    <li>• {{ __('team_members_lose_access') }}</li>
                                    <li>• {{ __('team_cannot_restore') }}</li>
                                </ul>
                            @else
                                <p>
                                    {{ __('team_still_owns') }}
                                </p>
                                <ul class="space-y-1">
                                    @foreach ($deletionBlockers as $type => $count)
                                        <li>
                                            <a class="font-medium text-coollabs hover:underline dark:text-warning"
                                                {{ wireNavigate() }} href="{{ route($blockerDetails[$type]['route']) }}">
                                                {{ $count }} {{ str($blockerDetails[$type]['label'])->plural($count) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <p>
                                    {{ __('team_remove_or_move') }}
                                </p>
                            @endif
                        <x-slot:action>
                            @if (
                                session('currentTeam.id') !== 0 &&
                                    auth()->user()->roleInTeam(currentTeam()->id) === 'owner' &&
                                    auth()->user()->teams()->count() > 1 &&
                                    !auth()->user()->currentTeam()->personal_team &&
                                    !currentTeam()->subscription &&
                                    $deletionBlockers === [])
                                <x-modal-confirmation title="{{ __('team_confirm_team_deletion') }}" buttonTitle="{{ __('team_delete_team') }}"
                                    isErrorButton submitAction="delete"
                                    :actions="[__('team_team_deletion_desc')]"
                                    confirmationText="{{ currentTeam()->name }}"
                                    confirmationLabel="{{ __('team_confirm_team_name') }}"
                                    shortConfirmationLabel="{{ __('team_team_name') }}" :confirmWithPassword="false"
                                    step2ButtonText="{{ __('team_permanently_delete_btn') }}" canGate="delete"
                                    :canResource="$team" />
                            @else
                                <x-forms.button isError disabled tooltip="{{ __('team_resolve_requirements') }}">
                                    {{ __('team_delete_team') }}
                                </x-forms.button>
                            @endif
                        </x-slot:action>
                </x-danger-zone>

                @if (session('currentTeam.id') !== 0 && !currentTeam()->subscription && (currentTeam()->projects->isNotEmpty() || currentTeam()->servers->isNotEmpty()))
                    <div class="mt-4 overflow-hidden rounded-lg border border-neutral-200 dark:border-white/[0.08]">
                        <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-3 py-2 dark:border-white/[0.08]">
                            <h5 class="text-sm font-medium text-black dark:text-fg">{{ __('team_resources') }}</h5>
                            <x-forms.button type="button" wire:click="refreshResources">
                                <x-reicon name="refresh" class="size-3.5" />
                                {{ __('team_refresh') }}
                            </x-forms.button>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead class="bg-neutral-50 text-[11px] uppercase tracking-wide text-neutral-500 dark:bg-coolgray-100 dark:text-fg-dim">
                                <tr>
                                    <th class="px-3 py-2 font-medium">{{ __('team_resource') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('team_name') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 dark:divide-white/[0.08]">
                                @foreach (currentTeam()->projects as $project)
                                    <tr class="text-[13px] text-neutral-600 hover:bg-neutral-50 dark:text-fg-dim dark:hover:bg-white/[0.03]">
                                        <td>
                                            <a class="block px-3 py-2.5" href="{{ route('project.show', ['project_uuid' => $project->uuid]) }}"
                                                target="_blank" rel="noopener noreferrer">{{ __('team_project') }}</a>
                                        </td>
                                        <td>
                                            <a class="block px-3 py-2.5 font-medium text-black dark:text-fg"
                                                href="{{ route('project.show', ['project_uuid' => $project->uuid]) }}"
                                                target="_blank" rel="noopener noreferrer">{{ $project->name }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach (currentTeam()->servers as $server)
                                    <tr class="text-[13px] text-neutral-600 hover:bg-neutral-50 dark:text-fg-dim dark:hover:bg-white/[0.03]">
                                        <td>
                                            <a class="block px-3 py-2.5" href="{{ route('server.show', ['server_uuid' => $server->uuid]) }}"
                                                target="_blank" rel="noopener noreferrer">{{ __('team_server') }}</a>
                                        </td>
                                        <td>
                                            <a class="block px-3 py-2.5 font-medium text-black dark:text-fg"
                                                href="{{ route('server.show', ['server_uuid' => $server->uuid]) }}"
                                                target="_blank" rel="noopener noreferrer">{{ $server->name }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-application.settings-section>
        </div>
    </x-team.settings-layout>
</div>
