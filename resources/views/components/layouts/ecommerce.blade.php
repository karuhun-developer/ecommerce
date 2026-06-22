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
                <a href="#" class="hover:text-green-600 transition">
                    Tentang Nexa
                </a>
                <a href="#" class="hover:text-green-600 transition">
                    Mitra Nexa
                </a>
            </div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-green-600 transition">
                    Promo
                </a>
                <a href="#" class="hover:text-green-600 transition">
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
    <footer class="bg-white border-t mt-16 pb-16 md:pb-8">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <flux:heading size="lg" class="mb-4">Tokopedia</flux:heading>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li><a href="#" class="hover:text-green-600">Tentang Nexa</a></li>
                    <li><a href="#" class="hover:text-green-600">Hak Kekayaan Intelektual</a></li>
                    <li><a href="#" class="hover:text-green-600">Karir</a></li>
                    <li><a href="#" class="hover:text-green-600">Blog</a></li>
                </ul>
            </div>
            <div>
                <flux:heading size="lg" class="mb-4">Beli</flux:heading>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li><a href="#" class="hover:text-green-600">Tagihan & Top Up</a></li>
                    <li><a href="#" class="hover:text-green-600">Tukar Tambah Handphone</a></li>
                    <li><a href="#" class="hover:text-green-600">Nexa COD</a></li>
                </ul>
            </div>
            <div>
                <flux:heading size="lg" class="mb-4">Jual</flux:heading>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li><a href="#" class="hover:text-green-600">Pusat Edukasi Seller</a></li>
                    <li><a href="#" class="hover:text-green-600">Mitra Toppers</a></li>
                    <li><a href="#" class="hover:text-green-600">Daftar Official Store</a></li>
                </ul>
            </div>
            <div>
                <a href="/" class="text-3xl font-black text-green-600 tracking-tight block mb-4">
                    nexa<span class="text-gray-800">.</span>
                </a>
                <flux:text class="mb-4">Download aplikasi Nexa sekarang juga!</flux:text>
                <div class="flex gap-4">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Play Store" class="h-10">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" class="h-10">
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-6 pt-8 border-t flex flex-col md:flex-row items-center justify-between gap-4">
            <flux:text>© 2026 Nexa. All rights reserved.</flux:text>
            <div class="flex gap-4 text-gray-400">
                <flux:icon.chat-bubble-oval-left class="w-5 h-5 hover:text-gray-600 cursor-pointer" />
                <flux:icon.camera class="w-5 h-5 hover:text-gray-600 cursor-pointer" />
                <flux:icon.globe-alt class="w-5 h-5 hover:text-gray-600 cursor-pointer" />
            </div>
        </div>
    </footer>

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

    <script>
        document.addEventListener('alpine:init', () => {
            // Cart store with localStorage persistence
            const savedCart = localStorage.getItem('cart');
            const initialItems = savedCart ? JSON.parse(savedCart) : [];

            // Cart store definition
            Alpine.store('cart', {
                items: initialItems,
                save() {
                    localStorage.setItem('cart', JSON.stringify(this.items));
                },
                get count() {
                    return this.items.reduce((total, item) => total + item.qty, 0);
                },
                get total() {
                    return this.items.reduce((total, item) => total + (item.price * item.qty), 0);
                },
                add(product) {
                    let existing = this.items.find(i => i.id === product.id);
                    if(existing) {
                        existing.qty += (product.qty || 1);
                    } else {
                        this.items.push({...product, qty: product.qty || 1});
                    }
                    this.save();
                },
                remove(id) {
                    this.items = this.items.filter(i => i.id !== id);
                    this.save();
                },
                updateQty(id, qty) {
                    let item = this.items.find(i => i.id === id);
                    if(item) {
                        item.qty = Math.max(1, qty);
                        this.save();
                    }
                },
                clear() {
                    this.items = [];
                    this.save();
                }
            });
        });
    </script>
    @fluxScripts
</body>
</html>
