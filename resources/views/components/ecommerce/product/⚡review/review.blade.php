<div>
    <div class="mt-16 pt-8 border-t w-full lg:w-[75%]">
        <h2 class="text-xl font-bold text-gray-900 mb-6">ULASAN PEMBELI</h2>
        <div class="flex flex-col md:flex-row gap-10 mb-8">
            <!-- Rating Summary -->
            <div class="flex flex-col gap-2 min-w-[200px]">
                <div class="flex items-baseline gap-2">
                    <flux:icon.star class="w-8 h-8 text-yellow-400 fill-yellow-400 mb-1" />
                    <span class="text-6xl font-black text-gray-900">
                        {{ number_format($product->rating, 1) }}
                    </span>
                    <span class="text-gray-500 font-medium">/ 5.0</span>
                </div>
                <div class="text-sm font-medium text-gray-900 mt-2">
                    @if($product->rating >= 4)
                        Sebagian besar pembeli merasa puas
                    @endif
                </div>
                <div class="text-xs text-gray-500">
                    Berdasarkan {{ $product->total_reviews }} ulasan
                </div>
            </div>
            <!-- Rating Distribution -->
            <div class="flex flex-col flex-1 justify-center gap-2 text-sm font-medium">
                @foreach([5,4,3,2,1] as $star)
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1 w-12 text-gray-600"><flux:icon.star class="w-3.5 h-3.5 text-yellow-400 fill-yellow-400"/> {{ $star }}</span>
                        <div class="h-2 flex-1 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gray-500 rounded-full" style="width: {{ $this->ratingDistribution['percentages'][$star] }}%"></div>
                        </div>
                        <span class="w-8 text-right text-gray-400">{{ $this->ratingDistribution['counts'][$star] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Review Filters -->
        <div class="mb-8">
            <h3 class="font-bold text-gray-900 mb-3 text-sm">Filter Ulasan</h3>
            <div class="flex flex-wrap gap-2">
                <button wire:click="setFilter('all')" class="{{ $filter === 'all' ? 'bg-gray-100 text-gray-900 border-gray-900' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100' }} border px-4 py-1.5 rounded-full text-sm font-semibold transition">Semua Ulasan</button>
                <button wire:click="setFilter('with_media')" class="{{ $filter === 'with_media' ? 'bg-gray-100 text-gray-900 border-gray-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }} border px-4 py-1.5 rounded-full text-sm font-semibold transition">Dengan Foto & Video</button>
            </div>
        </div>

        <!-- Review List -->
        <div class="flex flex-col gap-6">
            <div wire:loading.class="opacity-50" class="transition-opacity flex flex-col gap-6">
                @forelse($this->reviews as $review)
                    <div class="border-b pb-6">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-1">
                                @for($i=1; $i<=5; $i++)
                                    @if($i <= $review->rating)
                                        <flux:icon.star class="w-4 h-4 text-yellow-400 fill-yellow-400"/>
                                    @else
                                        <flux:icon.star class="w-4 h-4 text-gray-300"/>
                                    @endif
                                @endfor
                                <span class="text-gray-400 text-xs ml-2 font-medium">{{ $review->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($review->user->name ?? 'Guest') }}&background=random" alt="Avatar" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <span class="font-bold text-gray-900 text-sm block">{{ $review->user->name ?? 'Guest' }}</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 mb-4 leading-relaxed">{{ $review->comment }}</p>
                        
                        @if($review->hasMedia('review_images'))
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($review->getMedia('review_images') as $media)
                                    <div class="w-16 h-16 rounded-lg border overflow-hidden cursor-pointer hover:opacity-80 transition">
                                        <img src="{{ $media->getUrl() }}" class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-8">
                        <div class="text-gray-500 mb-2">Belum ada ulasan untuk produk ini.</div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $this->reviews->links() }}
            </div>
        </div>
    </div>
</div>