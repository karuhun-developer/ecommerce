
<div>
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-8" x-data="{
        activeTab: 'description',
        activeFlatProduct: {{ $product->productFlats->first()->id }},
        activeImage: 0,
        images: [
            @foreach ($product->productFlats as $flat)
                @foreach ($flat->getMedia('*') as $media)
                    {
                        id: '{{ $flat->id }}',
                        url: '{{ $media->getUrl() }}',
                    },
                @endforeach
            @endforeach
        ],
        setActiveImageBasedOnFlat(flatId) {
            const index = this.images.findIndex(img => img.id == flatId);
            if (index !== -1) {
                this.activeImage = index;
            }
        },
        // Cart functionality
        qty: 1,
    }">
        <div class="mb-4">
            <a href="{{ route('explore.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-green-600 transition font-medium" wire:navigate>
                <flux:icon.arrow-left class="w-4 h-4" />
                Kembali
            </a>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Left: Images -->
            <div class="w-full lg:w-[30%] flex flex-col gap-4">
                <div class="aspect-square rounded-2xl overflow-hidden border">
                    <img :src="images[activeImage].url" alt="Product" class="w-full h-full object-cover" lazy>
                </div>
                <div class="w-full max-h-20 overflow-hidden">
                    <div class="flex gap-2 overflow-x-auto pb-2 hide-scrollbar flex-nowrap items-center overscroll-contain">
                        <template x-for="(img, index) in images" :key="index">
                            <div @click="activeImage = index; activeFlatProduct = img.id"
                                :class="{'border-green-500 ring-2 ring-green-500': activeImage === index, 'border-gray-200 opacity-70': activeImage !== index}"
                                class="w-16 h-16 rounded-xl overflow-hidden border cursor-pointer hover:opacity-100 transition shrink-0">
                                <img :src="img.url" alt="Thumbnail" class="w-full h-full object-cover" lazy>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Middle: Product Info -->
            <div class="w-full lg:w-[45%] flex flex-col gap-6">
                @foreach ($product->productFlats as $flat)
                    <div x-show="activeFlatProduct === {{ $flat->id }}" x-cloak>
                        <h1 class="text-2xl font-bold text-gray-900 leading-tight mb-2">
                            {{ $flat->name }}
                        </h1>
                        <div class="flex items-center gap-4 text-sm mb-4">
                            <div class="flex items-center gap-1 text-gray-600">
                                <span class="text-gray-500">Terjual</span>
                                <span class="font-medium text-gray-900">
                                    {{ $product->total_sales ==0 ? 'Belum ada penjualan' : $product->total_sales . ' Terjual' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 text-gray-600 border-l pl-4">
                                <flux:icon.star class="w-4 h-4 text-yellow-400 fill-yellow-400" />
                                @if ($product->rating == 0)
                                    <span class="font-medium text-gray-900">Belum ada rating</span>
                                @else
                                    <span class="font-medium text-gray-900">{{ $product->rating }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="text-3xl font-black text-gray-900">
                            Rp{{ numberToCurrency($flat->price) }}
                        </div>
                        @if ($flat->discount > 0)
                            <div class="flex items-center gap-2 mt-1">
                                <span class="bg-red-100 text-red-600 text-xs font-bold px-1.5 py-0.5 rounded">
                                    Diskon {{ $flat->discount }}%
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 line-through">
                                Rp{{ numberToCurrency($flat->price + ($flat->price * $flat->discount / 100)) }}
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="border-t border-b py-4">
                    <h3 class="font-bold text-gray-800 mb-3 text-sm">Pilih Varian</h3>
                    <div class="flex gap-2 flex-wrap">
                        @foreach ($variants as $variant)
                            <button
                                :class="{
                                    'border-green-500 bg-green-50 text-green-700': activeFlatProduct == {{ $variant['product_flat_id'] }},
                                    'border-gray-200 text-gray-600 hover:border-green-500': activeFlatProduct != {{ $variant['product_flat_id'] }}
                                }"
                                @click="activeFlatProduct = {{ $variant['product_flat_id'] }}; setActiveImageBasedOnFlat({{ $variant['product_flat_id'] }})"
                                class="border px-4 py-1.5 rounded-xl text-sm font-medium transition">
                                {{ $variant['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Tabs -->
                <div>
                    <div class="border-b mb-4">
                        <nav class="flex gap-6 text-sm font-bold">
                            <button @click="activeTab = 'description'" :class="{'text-green-600 border-b-2 border-green-600 pb-3': activeTab === 'description', 'text-gray-500 hover:text-green-600 pb-3': activeTab !== 'description'}">Detail</button>
                            <button @click="activeTab = 'specification'" :class="{'text-green-600 border-b-2 border-green-600 pb-3': activeTab === 'specification', 'text-gray-500 hover:text-green-600 pb-3': activeTab !== 'specification'}">Spesifikasi</button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="text-sm text-gray-700 leading-relaxed mb-6">
                        @foreach ($product->productFlats as $flat)
                            <div x-show="activeFlatProduct == {{ $flat->id }}" x-cloak>
                                <div x-show="activeTab == 'description'" x-cloak>
                                    {!! $flat->description !!}
                                </div>
                                <div x-show="activeTab === 'specification'" x-cloak class="flex flex-col gap-3">
                                    <div class="flex">
                                        <span class="w-36 text-gray-500">Berat</span>
                                        <span class="font-medium text-gray-900">{{ $flat->weight }} gram</span>
                                    </div>
                                    <div class="flex">
                                        <span class="w-36 text-gray-500">Dimensi</span>
                                        <span class="font-medium text-gray-900">{{ $flat->length }} x {{ $flat->width }} x {{ $flat->height }} cm</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Store Info -->
                <div class="border-t pt-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center overflow-hidden border">
                            <flux:icon.building-storefront class="w-6 h-6 text-gray-500"/>
                        </div>
                        <div>
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-gray-900">
                                    {{ $product->shop->name }}
                                </span>
                                <flux:icon.check-badge class="w-4 h-4 text-purple-600" />
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                <span class="flex items-center gap-1 font-medium">
                                    <flux:icon.star class="w-3 h-3 text-yellow-400 fill-yellow-400" />
                                    <span>
                                        {{ $product->shop->rating == 0 ? 'Belum ada rating' : $product->shop->rating }}
                                    </span>
                                </span>
                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                <span>
                                    {{ $product->shop->total_sales == 0 ? 'Belum ada penjualan' : $product->shop->total_sales . ' Terjual' }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <span>
                                    {{ $product->shop->location->area_string ?? 'Lokasi tidak tersedia' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Action Box -->
            <div class="w-full lg:w-[25%]">
                @foreach ($product->productFlats as $flat)
                    <div class="border rounded-2xl p-4 shadow-sm sticky top-24 bg-white" x-show="activeFlatProduct === {{ $flat->id }}" x-cloak>
                        <h3 class="font-bold text-gray-900 mb-4 text-base">Atur jumlah dan catatan</h3>
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex items-center border rounded-xl overflow-hidden">
                                <button @click="qty = Math.max(1, qty - 1)" class="px-3 py-1.5 text-gray-500 hover:bg-gray-100 font-bold transition">-</button>
                                <input type="number" x-model="qty" class="w-12 text-center text-gray-900 font-medium border-0 focus:ring-0 p-0 text-sm" />
                                <button @click="qty++" class="px-3 py-1.5 text-green-600 hover:bg-green-50 font-bold transition">+</button>
                            </div>
                            @if ( !$flat->is_unlimited_stock)
                                <span class="text-sm text-gray-500">Stok Total:
                                    <strong class="text-gray-900">
                                        {{ $flat->stock }} Lagi
                                    </strong>
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between text-gray-500 text-sm mb-6">
                            <span>Subtotal</span>
                            <span class="font-bold text-gray-900 text-xl" x-text="'Rp' + ({{ $flat->price }} * qty).toLocaleString('id-ID')"></span>
                        </div>

                        <div class="flex flex-col gap-2">
                            <button @click="$store.cart.add({
                                id: {{ $flat->id }},
                                name: '{{ $flat->name }}',
                                price: {{ $flat->price }},
                                image: '{{ $flat->getFirstMediaUrl('image_slot_0') }}',
                                qty: qty,
                            }); $flux.modal('cartModal').show()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded-xl transition text-sm">
                                + Keranjang
                            </button>
                            <a href="/checkout" class="w-full border border-green-600 text-green-600 hover:bg-green-50 font-bold py-2.5 rounded-xl transition text-center text-sm">
                                Beli Langsung
                            </a>
                        </div>
                        <!-- Chat | Wishlist | Share -->
                        <div class="flex items-center justify-between mt-5 pt-4 border-t text-sm font-bold text-gray-600">
                            <button class="flex items-center gap-1.5 hover:text-green-600 transition">
                                <flux:icon.chat-bubble-left-ellipsis class="w-5 h-5"/> Chat
                            </button>
                            <div class="w-px h-4 bg-gray-300"></div>
                            <button class="flex items-center gap-1.5 hover:text-green-600 transition">
                                <flux:icon.heart class="w-5 h-5"/> Wishlist
                            </button>
                            <div class="w-px h-4 bg-gray-300"></div>
                            <button class="flex items-center gap-1.5 hover:text-green-600 transition">
                                <flux:icon.share class="w-5 h-5"/> Share
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Reviews Section -->
        <livewire:ecommerce.product.review :$product lazy />
    </div>
</div>