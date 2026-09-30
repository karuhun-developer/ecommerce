<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('components.layouts.partials.head')
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 font-sans antialiased" x-data="{ cartOpen: false }">
    <livewire:ecommerce.component.topbar />

    <!-- Header -->
    <livewire:ecommerce.component.header lazy />

    <main class="min-h-[70vh]">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <livewire:ecommerce.component.footer />

    <!-- Cart Slide-over (Flux Modal Flyout) -->
    <livewire:ecommerce.component.cart-flyout />


    <x-confirm-modal />

    @persist('toast')
        <flux:toast position="top end" />
    @endpersist

    @livewireScriptConfig
    @fluxScripts
</body>
</html>
