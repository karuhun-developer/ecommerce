<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Nexa - Belanja Online Aman & Nyaman' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine JS Store for Mock Cart -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('cart', {
                items: [
                    { id: 1, name: 'Logitech G Pro X Superlight', price: 1500000, qty: 1, image: 'https://images.unsplash.com/photo-1527443154391-42075928d114?auto=format&fit=crop&q=80&w=200&h=200' },
                    { id: 2, name: 'Keychron K2 Wireless Mechanical Keyboard', price: 1200000, qty: 1, image: 'https://images.unsplash.com/photo-1595225476474-87563907a212?auto=format&fit=crop&q=80&w=200&h=200' }
                ],
                get count() {
                    return this.items.reduce((total, item) => total + item.qty, 0);
                },
                get total() {
                    return this.items.reduce((total, item) => total + (item.price * item.qty), 0);
                },
                add(product) {
                    let existing = this.items.find(i => i.id === product.id);
                    if(existing) {
                        existing.qty++;
                    } else {
                        this.items.push({...product, qty: 1});
                    }
                },
                remove(id) {
                    this.items = this.items.filter(i => i.id !== id);
                },
                updateQty(id, qty) {
                    let item = this.items.find(i => i.id === id);
                    if(item) {
                        item.qty = Math.max(1, qty);
                    }
                }
            });
        });
    </script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 font-sans antialiased" x-data="{ cartOpen: false }">

    <!-- Topbar -->
    <div class="bg-gray-100 text-gray-500 text-xs py-1.5 hidden md:block border-b">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
            <div class="flex gap-4">
                <a href="#" class="hover:text-green-600 transition">Download App</a>
                <a href="#" class="hover:text-green-600 transition">Tentang Nexa</a>
                <a href="#" class="hover:text-green-600 transition">Mitra Nexa</a>
                <a href="#" class="hover:text-green-600 transition">Mulai Berjualan</a>
            </div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-green-600 transition">Promo</a>
                <a href="#" class="hover:text-green-600 transition">Bantuan</a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="bg-white border-b sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 md:px-6 py-4 flex items-center justify-between gap-4 md:gap-8">
            <!-- Logo -->
            <a href="/" class="text-2xl md:text-3xl font-black text-green-600 tracking-tight shrink-0">
                nexa<span class="text-gray-800">.</span>
            </a>

            <!-- Search -->
            <div class="hidden md:flex flex-1 items-center max-w-3xl relative">
                <flux:input 
                    placeholder="Cari barang, merek, atau toko..." 
                    class="w-full pl-4 pr-12 py-2.5 rounded-xl border-gray-300 focus:ring-green-500 focus:border-green-500"
                />
                <button class="absolute right-3 p-1 text-gray-400 hover:text-green-600 bg-white">
                    <flux:icon.magnifying-glass class="w-5 h-5" />
                </button>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-1 md:gap-3 shrink-0">
                <button @click="cartOpen = true" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition relative">
                    <flux:icon.shopping-cart class="w-6 h-6" />
                    <!-- Badge -->
                    <span x-show="$store.cart.count > 0" x-text="$store.cart.count" class="absolute top-0.5 right-0.5 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white"></span>
                </button>
                <div class="w-px h-6 bg-gray-200 mx-2 hidden md:block"></div>
                <flux:button href="{{ route('login') }}" variant="primary" class="hidden md:flex bg-green-600 hover:bg-green-700 text-white border-0 font-semibold px-6 rounded-lg">Masuk</flux:button>
                <flux:button href="{{ route('register') }}" variant="outline" class="hidden md:flex font-semibold px-6 rounded-lg text-green-600 border-green-600 hover:bg-green-50">Daftar</flux:button>
            </div>
        </div>
        
        <!-- Mobile Search (Visible on small screens) -->
        <div class="md:hidden px-4 pb-3">
             <flux:input 
                placeholder="Cari di Nexa..." 
                icon="magnifying-glass"
                class="w-full rounded-xl"
            />
        </div>

        <!-- Categories Menu -->
        <div class="hidden md:flex max-w-7xl mx-auto px-6 py-2 gap-6 text-sm text-gray-600 font-medium">
            <a href="#" class="hover:text-green-600 transition whitespace-nowrap">Elektronik</a>
            <a href="#" class="hover:text-green-600 transition whitespace-nowrap">Komputer & Laptop</a>
            <a href="#" class="hover:text-green-600 transition whitespace-nowrap">Handphone & Tablet</a>
            <a href="#" class="hover:text-green-600 transition whitespace-nowrap">Pakaian Pria</a>
            <a href="#" class="hover:text-green-600 transition whitespace-nowrap">Pakaian Wanita</a>
            <a href="#" class="hover:text-green-600 transition whitespace-nowrap">Kecantikan</a>
            <a href="#" class="hover:text-green-600 transition whitespace-nowrap">Rumah Tangga</a>
        </div>
    </header>

    <main class="min-h-[70vh]">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-16 pb-16 md:pb-8">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="font-bold text-gray-900 mb-4">Tokopedia</h3>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li><a href="#" class="hover:text-green-600">Tentang Nexa</a></li>
                    <li><a href="#" class="hover:text-green-600">Hak Kekayaan Intelektual</a></li>
                    <li><a href="#" class="hover:text-green-600">Karir</a></li>
                    <li><a href="#" class="hover:text-green-600">Blog</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 mb-4">Beli</h3>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li><a href="#" class="hover:text-green-600">Tagihan & Top Up</a></li>
                    <li><a href="#" class="hover:text-green-600">Tukar Tambah Handphone</a></li>
                    <li><a href="#" class="hover:text-green-600">Nexa COD</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 mb-4">Jual</h3>
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
                <p class="text-sm text-gray-500 mb-4">Download aplikasi Nexa sekarang juga!</p>
                <div class="flex gap-4">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Play Store" class="h-10">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" class="h-10">
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-6 pt-8 border-t flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-500">© 2026 Nexa. All rights reserved.</p>
            <div class="flex gap-4 text-gray-400">
                <flux:icon.chat-bubble-oval-left class="w-5 h-5 hover:text-gray-600 cursor-pointer" />
                <flux:icon.camera class="w-5 h-5 hover:text-gray-600 cursor-pointer" />
                <flux:icon.globe-alt class="w-5 h-5 hover:text-gray-600 cursor-pointer" />
            </div>
        </div>
    </footer>

    <!-- Cart Slide-over (Alpine.js) -->
    <div x-show="cartOpen" class="relative z-[100]" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" style="display: none;">
        <!-- Backdrop -->
        <div x-show="cartOpen" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="cartOpen = false"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <!-- Slide-over panel -->
                    <div 
                        x-show="cartOpen" 
                        x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500" 
                        x-transition:enter-start="translate-x-full" 
                        x-transition:enter-end="translate-x-0" 
                        x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500" 
                        x-transition:leave-start="translate-x-0" 
                        x-transition:leave-end="translate-x-full" 
                        class="pointer-events-auto w-screen max-w-md"
                    >
                        <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                            <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                                <div class="flex items-start justify-between">
                                    <h2 class="text-lg font-bold text-gray-900" id="slide-over-title">Keranjang Belanja</h2>
                                    <div class="ml-3 flex h-7 items-center">
                                        <button @click="cartOpen = false" type="button" class="relative -m-2 p-2 text-gray-400 hover:text-gray-500">
                                            <span class="absolute -inset-0.5"></span>
                                            <span class="sr-only">Tutup panel</span>
                                            <flux:icon.x-mark class="h-6 w-6" />
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <div class="flow-root">
                                        <template x-if="$store.cart.items.length === 0">
                                            <div class="text-center py-12">
                                                <flux:icon.shopping-bag class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                                                <p class="text-gray-500 font-medium">Keranjangmu kosong.</p>
                                                <button @click="cartOpen = false" class="mt-4 text-green-600 font-bold hover:text-green-700">Mulai Belanja</button>
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
                                                            <div class="flex justify-between text-sm font-medium text-gray-900">
                                                                <h3 class="line-clamp-2"><a href="#" x-text="item.name"></a></h3>
                                                            </div>
                                                            <p class="mt-1 text-sm font-bold text-orange-500" x-text="'Rp ' + item.price.toLocaleString('id-ID')"></p>
                                                        </div>
                                                        <div class="flex flex-1 items-end justify-between text-sm">
                                                            <div class="flex items-center border rounded-lg">
                                                                <button @click="$store.cart.updateQty(item.id, item.qty - 1)" class="px-2 py-1 text-gray-500 hover:bg-gray-100 rounded-l-lg">-</button>
                                                                <span class="px-2 text-gray-700 font-medium" x-text="item.qty"></span>
                                                                <button @click="$store.cart.updateQty(item.id, item.qty + 1)" class="px-2 py-1 text-gray-500 hover:bg-gray-100 rounded-r-lg">+</button>
                                                            </div>

                                                            <div class="flex">
                                                                <button @click="$store.cart.remove(item.id)" type="button" class="font-medium text-red-500 hover:text-red-600">Hapus</button>
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
                                <div class="flex justify-between text-base font-bold text-gray-900 mb-4">
                                    <p>Total Harga</p>
                                    <p x-text="'Rp ' + $store.cart.total.toLocaleString('id-ID')"></p>
                                </div>
                                <div class="mt-6">
                                    <a href="/cart" class="flex items-center justify-center rounded-xl border border-transparent bg-green-600 px-6 py-3 text-base font-bold text-white shadow-sm hover:bg-green-700">Lihat Keranjang</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @fluxScripts
</body>
</html>
