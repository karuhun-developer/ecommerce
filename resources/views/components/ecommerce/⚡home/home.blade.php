<div class="max-w-7xl mx-auto px-4 md:px-6 py-6">
    <livewire:ecommerce.component.banner />

    <!-- Category Icons -->
    <div class="mb-12">
        <h3 class="text-xl font-bold mb-4 text-gray-800">Kategori Pilihan</h3>
        <div class="flex gap-4 overflow-x-auto pb-4 hide-scrollbar">
            @foreach ($this->categories as $category)
                <a href="{{ route('explore.category', ['category' => $category->slug]) }}" class="flex flex-col items-center gap-2 min-w-[80px] cursor-pointer group" wire:navigate>
                    <div class="w-16 h-16 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center group-hover:border-green-500 group-hover:shadow-md transition">
                        @if ($category->getFirstMediaUrl('image') !== '')
                            <img src="{{ $category->getFirstMediaUrl('image') }}" alt="{{ $category->name }}" class="w-8 h-8" />
                        @else
                            <flux:icon.photo class="w-8 h-8 text-gray-400" />
                        @endif
                    </div>
                    <span class="text-xs font-medium text-gray-600 text-center">
                        {{ $category->name }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Product Grid -->
    <div>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-800">Kejar Diskon</h3>
            <a href="{{ route('explore.index') }}" class="text-green-600 font-bold text-sm hover:text-green-700" wire:navigate>
                Lihat Semua
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach ($this->products as $product)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition flex flex-col h-full overflow-hidden relative group">
                    <!-- Discount Badge -->
                    @if ($product->discount)
                        <div class="absolute top-2 left-2 z-10 bg-red-100 text-red-600 text-[10px] font-bold px-2 py-1 rounded">
                            {{ $product->discount }}%
                        </div>
                    @endif
                    <!-- Product Image -->
                    <a href="{{ route('product.detail', ['slug' => $product->slug]) }}" class="block aspect-square overflow-hidden bg-gray-50 relative" wire:navigate>
                        @if ($product->mainProductFlat->getFirstMediaUrl('image_slot_0') !== '')
                            <img src="{{ $product->mainProductFlat->getFirstMediaUrl('image_slot_0') }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        @else
                            <flux:icon.photo class="w-16 h-16 text-gray-400 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" />
                        @endif
                        <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition duration-300"></div>
                    </a>

                    <!-- Product Details -->
                    <div class="p-3 flex flex-col flex-1">
                        <a href="{{ route('product.detail', ['slug' => $product->slug]) }}" class="text-sm text-gray-800 line-clamp-2 mb-2 hover:text-green-600 transition" wire:navigate>
                            {{ $product->name }}
                        </a>
                        <div class="mt-auto">
                            <div class="font-bold text-gray-900 leading-tight mb-1">
                                Rp{{ numberToCurrency($product->price) }}
                            </div>
                            <!-- Location & Rating -->
                            <div class="flex items-center gap-1 text-gray-500 text-[10px] mt-2 mb-1">
                                <flux:icon.building-storefront class="w-3 h-3" />
                                <span class="truncate">
                                    {{ $product->shop->name }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500">
                                <flux:icon.star class="w-3 h-3 text-yellow-400 fill-yellow-400" />
                                <span>
                                    @if ($product->rating == 0)
                                        Belum ada rating
                                    @else
                                        {{ $product->rating }}
                                    @endif
                                </span>
                                <span>|</span>
                                <span>Terjual 
                                    {{ $product->total_sales }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>