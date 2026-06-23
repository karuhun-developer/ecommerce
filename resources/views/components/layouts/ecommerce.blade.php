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
    <flux:modal
        name="cartModal"
        flyout
    >
        <div class="flex h-full flex-col bg-white">
            <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                <div class="flex items-start justify-between">
                    <flux:heading size="xl" id="slide-over-title">Keranjang Belanja</flux:heading>
                </div>

                <div class="mt-8">
                    <div class="flow-root">
                        <template x-if="$store.cart.items.length === 0">
                            <div class="text-center py-12">
                                <flux:icon.shopping-bag class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                                <flux:text class="text-gray-500 font-medium">Keranjangmu kosong.</flux:text>
                                <flux:button @click="$flux.modal('cartModal').close()" variant="primary" color="green" class="mt-4">
                                    Mulai Belanja
                                </flux:button>
                            </div>
                        </template>
                        <ul role="list" class="-my-6 divide-y divide-gray-200">
                            <template x-for="item in $store.cart.items" :key="item.id">
                                <li class="flex py-6">
                                    <div class="h-24 w-24 shrink-0 overflow-hidden rounded-md border border-gray-200">
                                        <img :src="item.image" alt="Product image" class="h-full w-full object-cover object-center">
                                    </div>

                                    <div class="ml-4 flex flex-1 flex-col">
                                        <div>
                                            <div class="flex justify-between">
                                                <flux:heading size="sm" class="line-clamp-2"><a href="#" x-text="item.name"></a></flux:heading>
                                            </div>
                                            <flux:text class="mt-1 font-bold text-orange-500" x-text="'Rp ' + item.price.toLocaleString('id-ID')"></flux:text>
                                        </div>
                                        <div class="flex flex-1 items-end justify-between text-sm mt-4">
                                            <div class="flex items-center gap-2">
                                                <flux:button size="sm" variant="subtle" icon="minus" @click="$store.cart.updateQty(item.id, item.qty - 1)" />
                                                <flux:text class="font-medium w-4 text-center" x-text="item.qty"></flux:text>
                                                <flux:button size="sm" variant="subtle" icon="plus" @click="$store.cart.updateQty(item.id, item.qty + 1)" />
                                            </div>

                                            <div class="flex">
                                                <flux:button size="sm" variant="subtle" class="text-red-500 hover:text-red-600" @click="$store.cart.remove(item.id)">Hapus</flux:button>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 px-4 py-6 sm:px-6" x-show="$store.cart.items.length > 0">
                <div class="flex justify-between text-base mb-4">
                    <flux:text class="font-bold">Total Harga</flux:text>
                    <flux:text class="font-bold" x-text="'Rp ' + $store.cart.total.toLocaleString('id-ID')"></flux:text>
                </div>
                <div class="mt-6">
                    <flux:button href="/cart" variant="primary" color="green" class="w-full justify-center text-base py-3" wire:navigate>
                        Lihat Keranjang
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>

    @livewireScriptConfig
    @fluxScripts
</body>
</html>
