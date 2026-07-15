<div class="flex items-start max-md:flex-col">
    <x-ecommerce.account-sidebar active="reviews" />

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading size="xl" class="mb-6">Ulasan Saya</flux:heading>

        <div class="space-y-4">
            @forelse ($this->reviews as $review)
                <flux:card>
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            @if($review->reviewable_type === \App\Models\Order\OrderShopItem::class && $review->reviewable)
                                <div class="font-bold text-gray-900">{{ $review->reviewable->product_data['name'] ?? 'Produk' }}</div>
                                <div class="text-sm text-gray-500">Ulasan Produk</div>
                            @elseif($review->reviewable_type === \App\Models\Shop\Shop::class && $review->reviewable)
                                <div class="font-bold text-gray-900">{{ $review->reviewable->name ?? 'Toko' }}</div>
                                <div class="text-sm text-gray-500">Ulasan Toko</div>
                            @else
                                <div class="font-bold text-gray-900">Ulasan</div>
                            @endif
                            <div class="text-xs text-gray-400 mt-1">{{ $review->created_at->format('d M Y, H:i') }}</div>
                        </div>
                        <div>
                            @if($review->status === 'approved')
                                <flux:badge color="green" size="sm">Disetujui</flux:badge>
                            @elseif($review->status === 'rejected')
                                <flux:badge color="red" size="sm">Ditolak</flux:badge>
                            @else
                                <flux:badge color="yellow" size="sm">Menunggu Persetujuan</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3 flex items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $review->rating)
                                <flux:icon.star variant="solid" class="w-5 h-5 text-yellow-400" />
                            @elseif($i - 0.5 == $review->rating)
                                <div class="relative w-5 h-5">
                                    <flux:icon.star class="w-5 h-5 text-gray-300 absolute inset-0" />
                                    <div class="absolute inset-0 overflow-hidden w-1/2">
                                        <flux:icon.star variant="solid" class="w-5 h-5 text-yellow-400" />
                                    </div>
                                </div>
                            @else
                                <flux:icon.star class="w-5 h-5 text-gray-300" />
                            @endif
                        @endfor
                        <span class="ml-2 font-bold text-gray-700">{{ number_format($review->rating, 1) }}</span>
                    </div>

                    @if($review->comment)
                        <p class="text-gray-700 text-sm mb-4">{{ $review->comment }}</p>
                    @endif

                    @if($review->hasMedia('review_images'))
                        <div class="flex gap-2 flex-wrap">
                            @foreach($review->getMedia('review_images') as $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" class="w-20 h-20 object-cover rounded-lg border">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </flux:card>
            @empty
                <div class="text-center py-12 border rounded-xl bg-gray-50">
                    <flux:icon.star class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-lg font-bold text-gray-900">Belum ada ulasan</h3>
                    <p class="text-gray-500 mt-1">Anda belum memberikan ulasan untuk pesanan manapun.</p>
                </div>
            @endforelse

            <div class="mt-6">
                {{ $this->reviews->links() }}
            </div>
        </div>
    </div>
</div>
