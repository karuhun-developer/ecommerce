<?php

use function Laravel\Folio\name;
name('product.detail');
?>
<x-layouts.ecommerce>
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-8" x-data="{
        id: {{ $id }},
        qty: 1,
        activeImage: 0,
        images: [
            'https://images.unsplash.com/photo-1527443154391-42075928d114?auto=format&fit=crop&q=80&w=800&h=800',
            'https://images.unsplash.com/photo-1595225476474-87563907a212?auto=format&fit=crop&q=80&w=800&h=800',
            'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?auto=format&fit=crop&q=80&w=800&h=800'
        ],
        product: {
            id: {{ $id }},
            name: 'Logitech G Pro X Superlight Wireless Gaming Mouse',
            price: 1500000,
            original_price: 1800000,
            discount: 16,
            rating: 4.9,
            sold: 1200,
            reviews: 450,
            image: 'https://images.unsplash.com/photo-1527443154391-42075928d114?auto=format&fit=crop&q=80&w=400&h=400'
        }
    }">
        <div class="bg-white rounded-2xl shadow-sm border p-6 flex flex-col md:flex-row gap-8">
            <!-- Product Images -->
            <div class="w-full md:w-1/3 flex flex-col gap-4">
                <div class="aspect-square rounded-xl overflow-hidden border">
                    <img :src="images[activeImage]" alt="Product" class="w-full h-full object-cover">
                </div>
                <div class="flex gap-2 overflow-x-auto pb-2 hide-scrollbar">
                    <template x-for="(img, index) in images" :key="index">
                        <div @click="activeImage = index" :class="{'border-green-500 ring-1 ring-green-500': activeImage === index, 'border-gray-200 opacity-70': activeImage !== index}" class="w-16 h-16 rounded-lg overflow-hidden border cursor-pointer hover:opacity-100 transition shrink-0">
                            <img :src="img" alt="Thumbnail" class="w-full h-full object-cover">
                        </div>
                    </template>
                </div>
            </div>

            <!-- Product Info -->
            <div class="w-full md:w-1/3 flex flex-col gap-4">
                <h1 class="text-2xl font-bold text-gray-900 leading-tight" x-text="product.name"></h1>
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-1 text-gray-600">
                        <span class="font-bold text-gray-900">Terjual</span>
                        <span x-text="product.sold + '+'"></span>
                    </div>
                    <div class="flex items-center gap-1 text-gray-600">
                        <flux:icon.star class="w-4 h-4 text-yellow-400 fill-yellow-400" />
                        <span class="font-bold text-gray-900" x-text="product.rating"></span>
                        <span x-text="'(' + product.reviews + ' ulasan)'"></span>
                    </div>
                </div>

                <div class="text-3xl font-black text-gray-900 mt-2" x-text="'Rp' + product.price.toLocaleString('id-ID')"></div>
                <div class="flex items-center gap-2">
                    <span class="bg-red-100 text-red-600 text-xs font-bold px-1.5 py-0.5 rounded" x-text="product.discount + '%'"></span>
                    <span class="text-gray-400 line-through text-sm" x-text="'Rp' + product.original_price.toLocaleString('id-ID')"></span>
                </div>

                <div class="border-t border-b py-4 mt-2">
                    <h3 class="font-bold text-gray-800 mb-2">Pilih Varian</h3>
                    <div class="flex gap-2">
                        <button class="border border-green-500 bg-green-50 text-green-700 px-4 py-1.5 rounded-lg text-sm font-medium">Hitam</button>
                        <button class="border border-gray-200 text-gray-600 hover:border-green-500 px-4 py-1.5 rounded-lg text-sm font-medium">Putih</button>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-gray-800 mb-2">Detail Produk</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                    </p>
                </div>
            </div>

            <!-- Action Box -->
            <div class="w-full md:w-1/3">
                <div class="border rounded-2xl p-4 shadow-sm sticky top-24">
                    <h3 class="font-bold text-gray-900 mb-4">Atur jumlah dan catatan</h3>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex items-center border rounded-lg">
                            <button @click="qty = Math.max(1, qty - 1)" class="px-3 py-1.5 text-gray-500 hover:bg-gray-100 rounded-l-lg">-</button>
                            <span class="px-4 text-gray-900 font-medium" x-text="qty"></span>
                            <button @click="qty++" class="px-3 py-1.5 text-gray-500 hover:bg-gray-100 rounded-r-lg">+</button>
                        </div>
                        <span class="text-sm text-gray-500">Stok Total: <strong>Sisa 4</strong></span>
                    </div>

                    <div class="flex items-center justify-between text-gray-500 text-sm mb-6">
                        <span>Subtotal</span>
                        <span class="font-bold text-gray-900 text-lg" x-text="'Rp' + (product.price * qty).toLocaleString('id-ID')"></span>
                    </div>

                    <div class="flex flex-col gap-2">
                        <button @click="$store.cart.add({...product, qty: qty}); $flux.modal('cartModal').show()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded-xl transition">
                            + Keranjang
                        </button>
                        <a href="/checkout" class="w-full border border-green-600 text-green-600 hover:bg-green-50 font-bold py-2.5 rounded-xl transition text-center">
                            Beli Langsung
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.ecommerce>
