<div class="w-full">
    @if ($limit_reached)
        <x-limit-reached name="servers" />
    @else
        @php
            $privateKeyOptions = $private_keys
                ->map(fn ($key) => ['value' => $key->id, 'label' => $key->name])
                ->values()
                ->all();
        @endphp

        <form wire:submit="submit">
            <x-application.settings-section :title="__('server.byip.title')"
                :description="__('server.byip.description')">
                <x-slot:actions>
                    <button type="submit"
                        class="button button-highlighted">
                        {{ __('server.byip.continue') }}
                        <x-reicon name="arrow-right" class="size-3.5" />
                    </button>
                </x-slot:actions>

                <div class="mb-5">
                    <x-forms.input id="ip" :label="__('server.ip_label')" required
                        :helper="__('server.byip.ip_helper')" />
                </div>

                <div class="mb-5">
                    <div class="flex items-end gap-3">
                        <div class="min-w-0 flex-1">
                            <x-forms.listbox id="private_key_id" :label="__('server.byip.private_key_label')"
                                :placeholder="__('server.byip.select_private_key')" :options="$privateKeyOptions" />
                        </div>
                        @can('create', App\Models\PrivateKey::class)
                            <div x-data="{ dropdownOpen: false }" class="relative shrink-0"
                                @click.outside="dropdownOpen = false"
                                @keydown.escape.window="dropdownOpen = false">
                                <button type="button" class="button" @click="dropdownOpen = !dropdownOpen"
                                    aria-haspopup="menu" :aria-expanded="dropdownOpen">
                                    <x-reicon name="plus" class="size-3.5" />
                                    {{ __('server.byip.new_key') }}
                                    <x-reicon name="chevron-down" class="size-3 opacity-55" />
                                </button>
                                <div x-cloak x-show="dropdownOpen" x-transition.origin.top.right role="menu"
                                    class="listbox-panel left-auto! right-0! z-[90]! w-52! min-w-52!">
                                    <button type="button" class="listbox-option justify-start! gap-2.5!"
                                        wire:click="generatePrivateKey('ed25519')"
                                        @click="dropdownOpen = false" role="menuitem">
                                        <x-reicon name="keys" class="size-3.5 shrink-0 opacity-70" />
                                        {{ __('server.byip.generate_ed25519') }}
                                    </button>
                                    <button type="button" class="listbox-option justify-start! gap-2.5!"
                                        wire:click="generatePrivateKey('rsa')" @click="dropdownOpen = false"
                                        role="menuitem">
                                        <x-reicon name="keys" class="size-3.5 shrink-0 opacity-70" />
                                        {{ __('server.byip.generate_rsa') }}
                                    </button>
                                    <x-modal-input :title="__('server.byip.add_manually_title')">
                                        <x-slot:content>
                                            <button type="button" @click="dropdownOpen = false"
                                                class="listbox-option justify-start! gap-2.5!" role="menuitem">
                                                <x-reicon name="plus" class="size-3.5 shrink-0 opacity-70" />
                                                {{ __('server.byip.add_manually') }}
                                            </button>
                                        </x-slot:content>
                                        <livewire:security.private-key.create :modal_mode="true" from="server" />
                                    </x-modal-input>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>

                <div class="grid gap-4 border-t border-neutral-200 pt-4 lg:grid-cols-2 dark:border-white/[0.08]">
                    <x-forms.input id="name" :label="__('server.name_label')" required />
                    <x-forms.input id="description" :label="__('server.description_label')" />
                </div>

                <x-forms.collapsible class="mt-5 border-t border-neutral-200 pt-4 dark:border-white/[0.08]"
                    content-class="flex flex-col gap-4">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-forms.input id="user" :label="__('server.byip.user_label')" required
                            :helper="__('server.byip.user_helper')" />
                        <x-forms.input type="number" id="port" :label="__('server.byip.port_label')" required />
                    </div>
                    <x-forms.listbox id="server_role"
                        :helper="__('server.byip.role_helper')"
                        :label="__('server.role_label')" :options="[
                            ['value' => 'deployment', 'label' => __('server.role_deployment'), 'description' => __('server.role_deployment_desc')],
                            ['value' => 'build', 'label' => __('server.role_build'), 'description' => __('server.role_build_desc')],
                            ['value' => 'both', 'label' => __('server.role_both'), 'description' => __('server.role_both_desc')],
                        ]" />
                </x-forms.collapsible>
            </x-application.settings-section>
        </form>
    @endif
</div>
