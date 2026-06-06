<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>

    <x-confirm-modal />

    @persist('toast')
        <flux:toast position="top end" />
    @endpersist
</x-layouts.app.sidebar>
