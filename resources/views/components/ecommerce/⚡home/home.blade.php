<div class="max-w-7xl mx-auto px-4 md:px-6 py-6" x-data="{
    products: [
        { id: 1, name: 'Logitech G Pro X Superlight', price: 1500000, original_price: 1800000, discount: 16, rating: 4.9, sold: 1200, location: 'Jakarta Pusat', image: 'https://images.unsplash.com/photo-1527443154391-42075928d114?auto=format&fit=crop&q=80&w=400&h=400' },
        { id: 2, name: 'Keychron K2 Wireless Mechanical Keyboard', price: 1200000, original_price: null, discount: null, rating: 4.8, sold: 850, location: 'Bandung', image: 'https://images.unsplash.com/photo-1595225476474-87563907a212?auto=format&fit=crop&q=80&w=400&h=400' },
        { id: 3, name: 'Sony WH-1000XM4 Noise Canceling Headphones', price: 3499000, original_price: 4000000, discount: 12, rating: 4.9, sold: 2100, location: 'Surabaya', image: 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?auto=format&fit=crop&q=80&w=400&h=400' },
        { id: 4, name: 'Apple MacBook Air M2 2022', price: 15999000, original_price: 17500000, discount: 8, rating: 5.0, sold: 500, location: 'Jakarta Selatan', image: 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?auto=format&fit=crop&q=80&w=400&h=400' },
        { id: 5, name: 'LG 27GL850-B 27 Inch Ultragear QHD IPS', price: 5500000, original_price: 6000000, discount: 8, rating: 4.7, sold: 320, location: 'Medan', image: 'https://images.unsplash.com/photo-1527443195645-1133f7f28990?auto=format&fit=crop&q=80&w=400&h=400' },
        { id: 6, name: 'Razer DeathAdder V3 Pro', price: 2100000, original_price: null, discount: null, rating: 4.8, sold: 450, location: 'Jakarta Utara', image: 'https://images.unsplash.com/photo-1615663245857-ac9310d5b1ff?auto=format&fit=crop&q=80&w=400&h=400' }
    ]
}">
    <!-- Banner Carousel (Static for now) -->
    <div class="rounded-2xl overflow-hidden mb-8 relative bg-gray-900 h-[200px] md:h-[350px]">
        <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&q=80&w=2000&h=600" class="w-full h-full object-cover opacity-80" alt="Banner">
        <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent flex items-center">
            <div class="px-8 md:px-16">
                <h2 class="text-3xl md:text-5xl font-black text-white mb-2">Mega Sale 12.12</h2>
                <p class="text-white/90 text-lg mb-4">Diskon hingga 90% untuk semua produk elektronik!</p>
                <button class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-6 rounded-lg transition shadow-lg">Cek Sekarang</button>
            </div>
        </div>
    </div>

    <!-- Category Icons -->
    <div class="mb-12">
        <h3 class="text-xl font-bold mb-4 text-gray-800">Kategori Pilihan</h3>
        <div class="flex gap-4 overflow-x-auto pb-4 hide-scrollbar">
            <template x-for="i in 8">
                <div class="flex flex-col items-center gap-2 min-w-[80px] cursor-pointer group">
                    <div class="w-16 h-16 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center group-hover:border-green-500 group-hover:shadow-md transition">
                        <flux:icon.device-phone-mobile class="w-8 h-8 text-green-600" />
                    </div>
                    <span class="text-xs font-medium text-gray-600 text-center">Gadget</span>
                </div>
            </template>
        </div>
    </div>

    <!-- Product Grid -->
    <div>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-800">Kejar Diskon</h3>
            <a href="#" class="text-green-600 font-bold text-sm hover:text-green-700">Lihat Semua</a>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <template x-for="product in products" :key="product.id">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition flex flex-col h-full overflow-hidden relative group">
                    
                    <!-- Discount Badge -->
                    <template x-if="product.discount">
                        <div class="absolute top-2 left-2 z-10 bg-red-100 text-red-600 text-[10px] font-bold px-2 py-1 rounded">
                            <span x-text="product.discount + '%'"></span>
                        </div>
                    </template>

                    <!-- Product Image -->
                    <a :href="'/product/' + product.id" class="block aspect-square overflow-hidden bg-gray-50 relative">
                        <img :src="product.image" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition duration-300"></div>
                    </a>

                    <!-- Product Details -->
                    <div class="p-3 flex flex-col flex-1">
                        <a :href="'/product/' + product.id" class="text-sm text-gray-800 line-clamp-2 mb-2 hover:text-green-600 transition" x-text="product.name"></a>
                        
                        <div class="mt-auto">
                            <div class="font-bold text-gray-900 leading-tight mb-1" x-text="'Rp' + product.price.toLocaleString('id-ID')"></div>
                            
                            <!-- Original Price -->
                            <template x-if="product.original_price">
                                <div class="text-[10px] text-gray-400 line-through mb-1" x-text="'Rp' + product.original_price.toLocaleString('id-ID')"></div>
                            </template>
                            
                            <!-- Location & Rating -->
                            <div class="flex items-center gap-1 text-gray-500 text-[10px] mt-2 mb-1">
                                <flux:icon.map-pin class="w-3 h-3" />
                                <span class="truncate" x-text="product.location"></span>
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500">
                                <flux:icon.star class="w-3 h-3 text-yellow-400 fill-yellow-400" />
                                <span x-text="product.rating"></span>
                                <span>|</span>
                                <span x-text="'Terjual ' + product.sold"></span>
                            </div>
                        </div>

                        <!-- Add to Cart Button (Visible on hover on desktop, always on mobile) -->
                        <div class="mt-3">
                            <button @click="$store.cart.add(product); $flux.modal('cartModal').show()" class="w-full bg-white border border-green-600 text-green-600 font-bold py-1.5 rounded-lg text-xs hover:bg-green-50 transition">
                                + Keranjang
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>