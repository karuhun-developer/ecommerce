<x-layouts.auth.split :title="$title ?? null">
    {{ $slot }}


    <x-confirm-modal />

    @persist('toast')
        <flux:toast position="top end" />
    @endpersist
</x-layouts.auth.split>
