<?php

use function Laravel\Folio\name;
name('checkout.index');
?>
<x-layouts.ecommerce>
    <div class="bg-gray-50 min-h-screen py-8">
        <div class="max-w-6xl mx-auto px-4 md:px-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Pengiriman</h1>
            
            <div class="flex flex-col lg:flex-row gap-8" x-data="{
                shippingCost: 20000,
                selectedCourier: 'Reguler',
                subtotal: $store.cart.total
            }">
                <div class="w-full lg:w-2/3 space-y-6">
                    <!-- Address Section -->
                    <div class="bg-white border rounded-2xl shadow-sm p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-bold text-gray-900">Alamat Pengiriman</h2>
                            <button class="text-sm font-bold text-green-600 hover:text-green-700 border border-green-600 px-3 py-1 rounded-lg">Pilih Alamat Lain</button>
                        </div>
                        <div class="border rounded-xl p-4 bg-green-50/50 border-green-200">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-bold text-gray-900">Budi Santoso</span>
                                <span class="text-xs font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded">Utama</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-1">081234567890</p>
                            <p class="text-sm text-gray-600 line-clamp-2">Jl. Jendral Sudirman No. 123, Komplek Mawar Blok B4, Kebayoran Baru, Jakarta Selatan, DKI Jakarta 12190</p>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="bg-white border rounded-2xl shadow-sm p-6">
                        <h2 class="font-bold text-gray-900 mb-4">Barang yang dibeli</h2>
                        
                        <div class="space-y-6">
                            <template x-for="item in $store.cart.items" :key="item.id">
                                <div class="flex gap-4">
                                    <img :src="item.image" alt="Product" class="w-16 h-16 rounded-xl object-cover border">
                                    <div class="flex-1">
                                        <h3 class="text-gray-900 font-medium line-clamp-2 mb-1 text-sm" x-text="item.name"></h3>
                                        <div class="font-bold text-gray-900 text-sm" x-text="item.qty + ' x Rp' + item.price.toLocaleString('id-ID')"></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Courier Selection -->
                        <div class="mt-6 border-t pt-6">
                            <h3 class="font-bold text-gray-900 mb-3 text-sm">Pilih Pengiriman</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label class="border rounded-xl p-3 cursor-pointer flex justify-between items-center" :class="selectedCourier === 'Reguler' ? 'border-green-500 bg-green-50' : 'hover:border-gray-300'">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="courier" value="Reguler" x-model="selectedCourier" @change="shippingCost = 20000" class="text-green-600 focus:ring-green-500">
                                        <div>
                                            <div class="font-bold text-gray-900 text-sm">Reguler</div>
                                            <div class="text-xs text-gray-500">Estimasi 2-3 hari</div>
                                        </div>
                                    </div>
                                    <div class="font-bold text-gray-900 text-sm">Rp20.000</div>
                                </label>
                                <label class="border rounded-xl p-3 cursor-pointer flex justify-between items-center" :class="selectedCourier === 'Next Day' ? 'border-green-500 bg-green-50' : 'hover:border-gray-300'">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="courier" value="Next Day" x-model="selectedCourier" @change="shippingCost = 35000" class="text-green-600 focus:ring-green-500">
                                        <div>
                                            <div class="font-bold text-gray-900 text-sm">Next Day</div>
                                            <div class="text-xs text-gray-500">Estimasi 1 hari</div>
                                        </div>
                                    </div>
                                    <div class="font-bold text-gray-900 text-sm">Rp35.000</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-white border rounded-2xl shadow-sm p-6 sticky top-24">
                        <h2 class="font-bold text-lg text-gray-900 mb-4">Ringkasan Belanja</h2>
                        
                        <div class="space-y-3 text-sm mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span x-text="'Total Harga (' + $store.cart.count + ' barang)'"></span>
                                <span x-text="'Rp' + subtotal.toLocaleString('id-ID')"></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Total Ongkos Kirim</span>
                                <span x-text="'Rp' + shippingCost.toLocaleString('id-ID')"></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Asuransi Pengiriman</span>
                                <span>Rp2.500</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Biaya Jasa Aplikasi</span>
                                <span>Rp1.000</span>
                            </div>
                        </div>

                        <div class="border-t pt-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-900">Total Tagihan</span>
                                <span class="font-black text-xl text-gray-900" x-text="'Rp' + (subtotal + shippingCost + 3500).toLocaleString('id-ID')"></span>
                            </div>
                        </div>

                        <a href="/payment" class="block w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl transition text-center">
                            Pilih Pembayaran
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.ecommerce>
