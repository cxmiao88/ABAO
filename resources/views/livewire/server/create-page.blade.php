<div>
    <x-slot:title>
        {{ $title }} | ABao
    </x-slot>

    <livewire:server.create :selected-type="$type" :selected-token-uuid="$token_uuid" />
</div>
