<div class="max-w-7xl mx-auto px-4 md:px-6 py-6">

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-800">Jelajahi Produk</h1>
        <p class="text-sm text-gray-500 mt-1">Temukan produk terbaik dari berbagai kategori</p>
    </div>

    {{-- Search Bar --}}
    <div class="mb-6 flex gap-3 items-center">
        <div class="relative flex-1">
            <flux:icon.magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari produk..."
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
            />
            @if ($search)
                <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <flux:icon.x-mark class="w-4 h-4" />
                </button>
            @endif
        </div>

        {{-- Filter toggle (mobile) --}}
        <button
            wire:click="$toggle('showFilter')"
            class="md:hidden flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium shadow-sm hover:border-green-500 transition"
        >
            <flux:icon.adjustments-horizontal class="w-4 h-4" />
            Filter
        </button>
    </div>

    <div class="flex gap-6">

        {{-- Sidebar Filter --}}
        <aside class="hidden md:block w-56 shrink-0 space-y-6">

            {{-- Sort --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Urutkan</p>
                <div class="space-y-1">
                    @foreach ([
                        'latest'     => 'Terbaru',
                        'popular'    => 'Terlaris',
                        'rating'     => 'Rating Tertinggi',
                        'price_asc'  => 'Harga Terendah',
                        'price_desc' => 'Harga Tertinggi',
                    ] as $value => $label)
                        <button
                            wire:click="$set('sortBy', '{{ $value }}')"
                            class="w-full text-left text-sm px-3 py-2 rounded-lg transition {{ $sortBy === $value ? 'bg-green-50 text-green-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Kategori --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Kategori</p>
                <div class="space-y-1 max-h-64 overflow-y-auto">
                    <button
                        wire:click="selectCategory(null)"
                        class="w-full text-left text-sm px-3 py-2 rounded-lg transition {{ is_null($categoryId) ? 'bg-green-50 text-green-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        Semua Kategori
                    </button>
                    @foreach ($this->categories as $category)
                        <button
                            wire:click="selectCategory({{ $category->id }})"
                            class="w-full text-left text-sm px-3 py-2 rounded-lg transition flex justify-between items-center {{ $categoryId === $category->id ? 'bg-green-50 text-green-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}"
                        >
                            <span class="truncate">{{ $category->name }}</span>
                            <span class="text-[10px] text-gray-400 ml-1 shrink-0">({{ $category->products_count }})</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Harga --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Rentang Harga</p>
                <div class="space-y-2">
                    <input
                        type="number"
                        wire:model.live.debounce.600ms="minPrice"
                        placeholder="Harga minimum"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    />
                    <input
                        type="number"
                        wire:model.live.debounce.600ms="maxPrice"
                        placeholder="Harga maksimum"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    />
                </div>
            </div>

            {{-- Reset --}}
            @if ($search || $categoryId || $minPrice || $maxPrice || $sortBy !== 'latest')
                <button
                    wire:click="resetFilters"
                    class="w-full text-sm text-center text-red-500 hover:text-red-600 font-medium py-2 rounded-xl border border-red-200 hover:bg-red-50 transition"
                >
                    Reset Filter
                </button>
            @endif
        </aside>

        {{-- Mobile Filter Drawer --}}
        @if ($showFilter)
            <div class="md:hidden fixed inset-0 z-50 flex" x-data>
                <div class="absolute inset-0 bg-black/40" wire:click="$set('showFilter', false)"></div>
                <div class="relative ml-auto w-72 h-full bg-white shadow-xl p-5 overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-bold text-gray-800">Filter & Urutkan</span>
                        <button wire:click="$set('showFilter', false)">
                            <flux:icon.x-mark class="w-5 h-5 text-gray-400" />
                        </button>
                    </div>

                    <div class="space-y-5">
                        {{-- Sort --}}
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Urutkan</p>
                            <div class="space-y-1">
                                @foreach ([
                                    'latest'     => 'Terbaru',
                                    'popular'    => 'Terlaris',
                                    'rating'     => 'Rating Tertinggi',
                                    'price_asc'  => 'Harga Terendah',
                                    'price_desc' => 'Harga Tertinggi',
                                ] as $value => $label)
                                    <button
                                        wire:click="$set('sortBy', '{{ $value }}'); $set('showFilter', false)"
                                        class="w-full text-left text-sm px-3 py-2 rounded-lg transition {{ $sortBy === $value ? 'bg-green-50 text-green-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}"
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Kategori --}}
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Kategori</p>
                            <div class="space-y-1">
                                <button
                                    wire:click="selectCategory(null); $set('showFilter', false)"
                                    class="w-full text-left text-sm px-3 py-2 rounded-lg transition {{ is_null($categoryId) ? 'bg-green-50 text-green-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}"
                                >
                                    Semua
                                </button>
                                @foreach ($this->categories as $category)
                                    <button
                                        wire:click="selectCategory({{ $category->id }}); $set('showFilter', false)"
                                        class="w-full text-left text-sm px-3 py-2 rounded-lg transition flex justify-between items-center {{ $categoryId === $category->id ? 'bg-green-50 text-green-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}"
                                    >
                                        <span class="truncate">{{ $category->name }}</span>
                                        <span class="text-[10px] text-gray-400 ml-1">({{ $category->products_count }})</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Harga --}}
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Rentang Harga</p>
                            <div class="space-y-2">
                                <input
                                    type="number"
                                    wire:model.live.debounce.600ms="minPrice"
                                    placeholder="Harga minimum"
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500"
                                />
                                <input
                                    type="number"
                                    wire:model.live.debounce.600ms="maxPrice"
                                    placeholder="Harga maksimum"
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500"
                                />
                            </div>
                        </div>

                        @if ($search || $categoryId || $minPrice || $maxPrice || $sortBy !== 'latest')
                            <button
                                wire:click="resetFilters"
                                class="w-full text-sm text-center text-red-500 font-medium py-2 rounded-xl border border-red-200 hover:bg-red-50 transition"
                            >
                                Reset Filter
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Main Content --}}
        <div class="flex-1 min-w-0">

            {{-- Active Filters Badges --}}
            @if ($search || $categoryId || $minPrice || $maxPrice)
                <div class="flex flex-wrap gap-2 mb-4">
                    @if ($search)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                            "{{ $search }}"
                            <button wire:click="$set('search', '')" class="hover:text-green-900">
                                <flux:icon.x-mark class="w-3 h-3" />
                            </button>
                        </span>
                    @endif
                    @if ($categoryId)
                        @php $activeCat = $this->categories->firstWhere('id', $categoryId) @endphp
                        @if ($activeCat)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                {{ $activeCat->name }}
                                <button wire:click="selectCategory(null)" class="hover:text-green-900">
                                    <flux:icon.x-mark class="w-3 h-3" />
                                </button>
                            </span>
                        @endif
                    @endif
                    @if ($minPrice)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                            Min Rp{{ number_format($minPrice, 0, ',', '.') }}
                            <button wire:click="$set('minPrice', null)" class="hover:text-green-900">
                                <flux:icon.x-mark class="w-3 h-3" />
                            </button>
                        </span>
                    @endif
                    @if ($maxPrice)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                            Max Rp{{ number_format($maxPrice, 0, ',', '.') }}
                            <button wire:click="$set('maxPrice', null)" class="hover:text-green-900">
                                <flux:icon.x-mark class="w-3 h-3" />
                            </button>
                        </span>
                    @endif
                </div>
            @endif

            {{-- Result count + sort (mobile) --}}
            <div class="flex items-center justify-between mb-4 gap-2">
                <p class="text-sm text-gray-500">
                    <span class="font-semibold text-gray-800">{{ $this->products->total() }}</span> produk ditemukan
                </p>
                {{-- Sort dropdown (desktop shortcut) --}}
                <div class="hidden md:flex items-center gap-2 text-sm text-gray-500">
                    <span>Urutkan:</span>
                    <select
                        wire:model.live="sortBy"
                        class="text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-green-500"
                    >
                        <option value="latest">Terbaru</option>
                        <option value="popular">Terlaris</option>
                        <option value="rating">Rating Tertinggi</option>
                        <option value="price_asc">Harga Terendah</option>
                        <option value="price_desc">Harga Tertinggi</option>
                    </select>
                </div>
            </div>

            {{-- Loading State --}}
            <div wire:loading.delay class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                @foreach (range(1, 8) as $i)
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden animate-pulse">
                        <div class="aspect-square bg-gray-100"></div>
                        <div class="p-3 space-y-2">
                            <div class="h-3 bg-gray-100 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-100 rounded w-1/2"></div>
                            <div class="h-4 bg-gray-100 rounded w-2/3"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Product Grid --}}
            <div wire:loading.remove>
                @if ($this->products->isEmpty())
                    <div class="flex flex-col items-center justify-center py-24 text-center">
                        <flux:icon.face-frown class="w-16 h-16 text-gray-300 mb-4" />
                        <p class="text-lg font-bold text-gray-700 mb-1">Produk tidak ditemukan</p>
                        <p class="text-sm text-gray-400 mb-4">Coba ubah kata kunci atau filter pencarian</p>
                        <button
                            wire:click="resetFilters"
                            class="px-5 py-2 rounded-xl bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition"
                        >
                            Reset Filter
                        </button>
                    </div>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                        @foreach ($this->products as $product)
                            <div wire:key="product-{{ $product->id }}" class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition flex flex-col h-full overflow-hidden relative group">
                                <!-- Discount Badge -->
                                @if ($product->discount)
                                    <div class="absolute top-2 left-2 z-10 bg-red-100 text-red-600 text-[10px] font-bold px-2 py-1 rounded">
                                        {{ $product->discount }}%
                                    </div>
                                @endif

                                <!-- Product Image -->
                                <a href="{{ route('product.detail', ['slug' => $product->slug]) }}" class="block aspect-square overflow-hidden bg-gray-50 relative" wire:navigate>
                                    @if ($product->mainProductFlat && $product->mainProductFlat->getFirstMediaUrl('image_slot_0') !== '')
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
                                                {{ $product->shop->name ?? 'Toko' }}
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
                                    <!-- Add to Cart (kept from explore UI) -->
                                    <div class="mt-3">
                                        <button
                                            @click=""
                                            class="w-full bg-white border border-green-600 text-green-600 font-bold py-1.5 rounded-lg text-xs hover:bg-green-50 transition"
                                        >
                                            + Keranjang
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if ($this->products->hasPages())
                        <div class="flex justify-center mt-4">
                            {{ $this->products->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>