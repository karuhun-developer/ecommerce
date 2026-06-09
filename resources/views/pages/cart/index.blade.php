<?php

use function Laravel\Folio\name;
name('cart.index');
?>
<x-layouts.ecommerce>
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Keranjang Belanja</h1>
        
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items -->
            <div class="w-full lg:w-2/3">
                <div class="bg-white border rounded-2xl shadow-sm p-6" x-data>
                    <div class="flex items-center gap-4 border-b pb-4 mb-4">
                        <input type="checkbox" class="w-5 h-5 text-green-600 rounded border-gray-300 focus:ring-green-500" checked>
                        <span class="font-bold text-gray-800">Pilih Semua Item</span>
                        <button class="ml-auto text-sm font-bold text-green-600">Hapus</button>
                    </div>

                    <template x-if="$store.cart.items.length === 0">
                        <div class="text-center py-12">
                            <flux:icon.shopping-bag class="w-20 h-20 text-gray-200 mx-auto mb-4" />
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Keranjangmu masih kosong</h2>
                            <p class="text-gray-500 mb-6">Yuk, mulai penuhi dengan barang-barang impianmu!</p>
                            <a href="/" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-8 rounded-xl transition">Mulai Belanja</a>
                        </div>
                    </template>

                    <div class="space-y-6">
                        <template x-for="item in $store.cart.items" :key="item.id">
                            <div class="flex gap-4">
                                <input type="checkbox" class="w-5 h-5 mt-2 text-green-600 rounded border-gray-300 focus:ring-green-500" checked>
                                <img :src="item.image" alt="Product" class="w-20 h-20 rounded-xl object-cover border">
                                <div class="flex-1">
                                    <h3 class="text-gray-900 font-medium line-clamp-2 mb-1" x-text="item.name"></h3>
                                    <div class="text-gray-500 text-xs mb-2">Varian: Hitam</div>
                                    <div class="font-bold text-gray-900" x-text="'Rp' + item.price.toLocaleString('id-ID')"></div>
                                </div>
                                <div class="flex flex-col justify-end items-end gap-3 shrink-0">
                                    <button @click="$store.cart.remove(item.id)" class="text-gray-400 hover:text-red-500 transition">
                                        <flux:icon.trash class="w-5 h-5" />
                                    </button>
                                    <div class="flex items-center border rounded-lg">
                                        <button @click="$store.cart.updateQty(item.id, item.qty - 1)" class="px-2 py-1 text-gray-500 hover:bg-gray-100 rounded-l-lg">-</button>
                                        <span class="px-4 text-gray-900 font-medium text-sm" x-text="item.qty"></span>
                                        <button @click="$store.cart.updateQty(item.id, item.qty + 1)" class="px-2 py-1 text-gray-500 hover:bg-gray-100 rounded-r-lg">+</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="w-full lg:w-1/3">
                <div class="bg-white border rounded-2xl shadow-sm p-6 sticky top-24" x-data>
                    <h2 class="font-bold text-lg text-gray-900 mb-4">Ringkasan Belanja</h2>
                    
                    <div class="space-y-3 text-sm mb-6">
                        <div class="flex justify-between text-gray-600">
                            <span x-text="'Total Harga (' + $store.cart.count + ' barang)'"></span>
                            <span x-text="'Rp' + $store.cart.total.toLocaleString('id-ID')"></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Total Diskon Barang</span>
                            <span class="text-green-600">-Rp0</span>
                        </div>
                    </div>

                    <div class="border-t pt-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-900">Total Harga</span>
                            <span class="font-black text-xl text-gray-900" x-text="'Rp' + $store.cart.total.toLocaleString('id-ID')"></span>
                        </div>
                    </div>

                    <a href="/checkout" :class="$store.cart.items.length === 0 ? 'opacity-50 cursor-not-allowed' : ''" class="block w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl transition text-center">
                        Beli (<span x-text="$store.cart.count"></span>)
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.ecommerce>
