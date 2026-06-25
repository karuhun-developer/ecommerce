<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('components.layouts.partials.head')
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 font-sans antialiased" x-data="{ cartOpen: false }">
    <!-- Topbar -->
    <div class="bg-gray-100 text-gray-500 text-xs py-1.5 hidden md:block border-b">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
            <div class="flex gap-4">
                <a href="#" class="hover:text-green-600 transition" wire:navigate>
                    Tentang {{ config('app.name') }}
                </a>
                <a href="#" class="hover:text-green-600 transition" wire:navigate>
                    Mitra {{ config('app.name') }}
                </a>
            </div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-green-600 transition" wire:navigate>
                    Promo
                </a>
                <a href="#" class="hover:text-green-600 transition" wire:navigate>
                    Bantuan
                </a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <livewire:ecommerce.component.header lazy />

    <main class="min-h-[70vh]">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <livewire:ecommerce.component.footer />

    <!-- Cart Slide-over (Flux Modal Flyout) -->
    <livewire:ecommerce.component.cart-flyout />

    @livewireScriptConfig
    @fluxScripts
</body>
</html>
