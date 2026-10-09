            <div class="application-settings-form flex flex-col gap-6">
                <x-application.settings-section title="{{ __('src.permissions') }}"
                    description="{{ __('src.permissions_helper') }}">
                    <x-slot:actions>
                        @can('view', $github_app)
                            <x-forms.button type="button" wire:click.prevent="checkPermissions">
                                <x-reicon name="refresh" class="size-3.5" />
                                {{ __('src.refetch') }}
                            </x-forms.button>
                            <a href="{{ getPermissionsPath($github_app) }}" class="button">
                                {{ __('src.update_on_github') }}
                                <x-external-link />
                            </a>
                        @endcan
                    </x-slot:actions>

                    <div class="grid gap-4 lg:grid-cols-3">
                        <x-forms.input canGate="view" :canResource="$github_app" id="contents"
                            helper="{{ __('src.read_mandatory') }}" label="{{ __('src.contents') }}" readonly placeholder="{{ __('src.na') }}" />
                        <x-forms.input canGate="view" :canResource="$github_app" id="metadata"
                            helper="{{ __('src.read_mandatory') }}" label="{{ __('src.metadata') }}" readonly placeholder="{{ __('src.na') }}" />
                        <x-forms.input canGate="view" :canResource="$github_app" id="pullRequests"
                            helper="{{ __('src.pr_write_helper') }}"
                            label="{{ __('src.pull_requests') }}" readonly placeholder="{{ __('src.na') }}" />
                    </div>
                </x-application.settings-section>

                @php($missingRunnerRequirements = $github_app->missingRunnerRequirements())
                <x-application.settings-section title="{{ __('src.gh_actions_runners') }}"
                    description="{{ __('src.runners_desc') }}">
                    <div class="grid gap-4 lg:grid-cols-3">
                        <x-forms.input canGate="view" :canResource="$github_app"
                            label="{{ __('src.self_hosted_runners') }}" readonly placeholder="{{ __('src.na') }}"
                            :value="$github_app->organization_self_hosted_runners"
                            helper="{{ __('src.self_hosted_runner_helper') }}" />
                        <x-forms.input canGate="view" :canResource="$github_app"
                            label="{{ __('src.actions') }}" readonly placeholder="{{ __('src.na') }}" :value="$github_app->actions"
                            helper="{{ __('src.actions_helper') }}" />
                        <x-forms.input canGate="view" :canResource="$github_app"
                            label="{{ __('src.webhook_events') }}" readonly placeholder="{{ __('src.na') }}"
                            :value="implode(', ', $github_app->webhook_events ?? [])"
                            helper="{{ __('src.webhook_events_helper') }}" />
                    </div>
                    @if ($missingRunnerRequirements !== [])
                        <x-callout type="info" title="{{ __('src.not_ready_runners') }}" class="mt-4">
                            {{ __('src.not_ready_runners_body') }}
                            <ul class="mt-1 list-disc pl-4">
                                @foreach ($missingRunnerRequirements as $requirement)
                                    <li>{{ $requirement }}</li>
                                @endforeach
                            </ul>
                        </x-callout>
                    @endif
                </x-application.settings-section>
            </div>
