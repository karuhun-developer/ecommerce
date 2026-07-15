<flux:modal name="review-modal-{{ $orderShop->id }}" class="min-w-full md:min-w-[600px] max-w-4xl" @close-modal-{{ $orderShop->id }}.window="$flux.modal('review-modal-{{ $orderShop->id }}').close()">
    <div class="p-6">
        <h2 class="text-xl font-bold mb-4">Tulis Ulasan</h2>
        
        @if($this->hasAlreadyReviewed)
            <div class="bg-green-50 text-green-700 p-4 rounded-lg flex items-center gap-3">
                <flux:icon.check-circle class="w-6 h-6" />
                <div>
                    <h3 class="font-bold">Terima kasih!</h3>
                    <p>Anda sudah memberikan ulasan untuk pesanan ini. Ulasan sedang menunggu persetujuan admin.</p>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <flux:button @click="$flux.modal('review-modal-{{ $orderShop->id }}').close()">Tutup</flux:button>
            </div>
        @else
            <form wire:submit.prevent="submit" class="space-y-8">
                
                <!-- Items Review -->
                <div class="space-y-6">
                    <h3 class="font-semibold text-lg border-b pb-2">Ulasan Produk</h3>
                    
                    @foreach($orderShop->items as $item)
                        @php $key = "App\Models\Order\OrderShopItem__{$item->id}"; @endphp
                        <flux:card>
                            <div class="font-medium text-gray-900 mb-4">{{ $item->product_data['name'] ?? 'Produk' }}</div>
                            
                            <div class="space-y-4">
                                <!-- Rating -->
                                <div>
                                    <label class="block text-sm font-medium mb-2">Rating</label>
                                    <div x-data="{
                                            rating: @entangle('reviewData.'.$key.'.rating'),
                                            hoverRating: 0,
                                            stars: [1, 2, 3, 4, 5],
                                            setRating(star, event) {
                                                const rect = event.currentTarget.getBoundingClientRect();
                                                const x = event.clientX - rect.left;
                                                const isHalf = x < (rect.width / 2);
                                                this.rating = isHalf ? star - 0.5 : star;
                                            },
                                            setHoverRating(star, event) {
                                                const rect = event.currentTarget.getBoundingClientRect();
                                                const x = event.clientX - rect.left;
                                                const isHalf = x < (rect.width / 2);
                                                this.hoverRating = isHalf ? star - 0.5 : star;
                                            },
                                            resetHover() {
                                                this.hoverRating = 0;
                                            },
                                            getDisplayRating() {
                                                return this.hoverRating > 0 ? this.hoverRating : this.rating;
                                            }
                                        }"
                                        class="flex gap-2 items-center"
                                        @mouseleave="resetHover"
                                    >
                                        <template x-for="star in stars" :key="star">
                                            <div class="cursor-pointer relative w-8 h-8"
                                                 @mousemove="setHoverRating(star, $event)"
                                                 @click="setRating(star, $event)">
                                                <!-- Empty Star -->
                                                <flux:icon.star class="w-8 h-8 text-gray-300 absolute inset-0 pointer-events-none" />
                                                
                                                <!-- Full Star -->
                                                <div x-show="getDisplayRating() >= star" class="absolute inset-0 overflow-hidden pointer-events-none">
                                                    <flux:icon.star variant="solid" class="w-8 h-8 text-yellow-400" />
                                                </div>
                                                
                                                <!-- Half Star -->
                                                <div x-show="getDisplayRating() == star - 0.5" class="absolute inset-0 overflow-hidden w-1/2 pointer-events-none">
                                                    <flux:icon.star variant="solid" class="w-8 h-8 text-yellow-400" />
                                                </div>
                                            </div>
                                        </template>
                                        <span class="ml-3 font-bold text-gray-700" x-text="Number(rating).toFixed(1)"></span>
                                    </div>
                                    @error('reviewData.'.$key.'.rating') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Comment -->
                                <div>
                                    <flux:textarea wire:model="reviewData.{{ $key }}.comment" label="Komentar" placeholder="Bagaimana kualitas produk ini?" rows="3" />
                                </div>
                                
                                <!-- Images -->
                                <div>
                                    <label class="block text-sm font-medium mb-2">Foto (Max 5)</label>
                                    
                                    <div class="flex flex-wrap gap-4 items-start">
                                        @if(isset($images[$key]))
                                            @foreach($images[$key] as $index => $image)
                                                <div class="relative group w-24 h-24 rounded-lg overflow-hidden border bg-white">
                                                    <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover">
                                                    <button type="button" wire:click="removeImage('{{ addslashes($key) }}', {{ $index }})" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                                        <flux:icon.trash class="w-6 h-6 text-white" />
                                                    </button>
                                                </div>
                                            @endforeach
                                        @endif
                                        
                                        @if(!isset($images[$key]) || count($images[$key]) < 5)
                                            <div class="relative w-24 h-24 rounded-lg border-2 border-dashed border-gray-300 hover:border-gray-400 flex flex-col items-center justify-center bg-gray-50 cursor-pointer overflow-hidden transition">
                                                <flux:icon.plus class="w-6 h-6 text-gray-400" />
                                                <span class="text-xs text-gray-500 mt-1">Upload</span>
                                                <input type="file" wire:model="images.{{ $key }}" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div wire:loading wire:target="images.{{ $key }}" class="text-sm text-gray-500 mt-2">
                                        Uploading...
                                    </div>
                                    @error('images.'.$key.'.*') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                                    @error('images.'.$key) <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </flux:card>
                    @endforeach
                </div>
                
                <!-- Shop Review -->
                @if($orderShop->shop)
                    <div class="space-y-6">
                        <h3 class="font-semibold text-lg border-b pb-2">Ulasan Toko</h3>
                        
                        @php $shopKey = "App\Models\Shop\Shop__{$orderShop->shop_id}"; @endphp
                        <flux:card>
                            <div class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                                <flux:icon.building-storefront class="w-5 h-5 text-gray-500" />
                                {{ $orderShop->shop->name }}
                            </div>
                            
                            <div class="space-y-4">
                                <!-- Rating -->
                                <div>
                                    <label class="block text-sm font-medium mb-2">Rating Pelayanan Toko</label>
                                    <div x-data="{
                                            rating: @entangle('reviewData.'.$shopKey.'.rating'),
                                            hoverRating: 0,
                                            stars: [1, 2, 3, 4, 5],
                                            setRating(star, event) {
                                                const rect = event.currentTarget.getBoundingClientRect();
                                                const x = event.clientX - rect.left;
                                                const isHalf = x < (rect.width / 2);
                                                this.rating = isHalf ? star - 0.5 : star;
                                            },
                                            setHoverRating(star, event) {
                                                const rect = event.currentTarget.getBoundingClientRect();
                                                const x = event.clientX - rect.left;
                                                const isHalf = x < (rect.width / 2);
                                                this.hoverRating = isHalf ? star - 0.5 : star;
                                            },
                                            resetHover() {
                                                this.hoverRating = 0;
                                            },
                                            getDisplayRating() {
                                                return this.hoverRating > 0 ? this.hoverRating : this.rating;
                                            }
                                        }"
                                        class="flex gap-2 items-center"
                                        @mouseleave="resetHover"
                                    >
                                        <template x-for="star in stars" :key="star">
                                            <div class="cursor-pointer relative w-8 h-8"
                                                 @mousemove="setHoverRating(star, $event)"
                                                 @click="setRating(star, $event)">
                                                <!-- Empty Star -->
                                                <flux:icon.star class="w-8 h-8 text-gray-300 absolute inset-0 pointer-events-none" />
                                                
                                                <!-- Full Star -->
                                                <div x-show="getDisplayRating() >= star" class="absolute inset-0 overflow-hidden pointer-events-none">
                                                    <flux:icon.star variant="solid" class="w-8 h-8 text-yellow-400" />
                                                </div>
                                                
                                                <!-- Half Star -->
                                                <div x-show="getDisplayRating() == star - 0.5" class="absolute inset-0 overflow-hidden w-1/2 pointer-events-none">
                                                    <flux:icon.star variant="solid" class="w-8 h-8 text-yellow-400" />
                                                </div>
                                            </div>
                                        </template>
                                        <span class="ml-3 font-bold text-gray-700" x-text="Number(rating).toFixed(1)"></span>
                                    </div>
                                    @error('reviewData.'.$shopKey.'.rating') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Comment -->
                                <div>
                                    <flux:textarea wire:model="reviewData.{{ $shopKey }}.comment" label="Komentar" placeholder="Bagaimana pelayanan toko ini?" rows="3" />
                                </div>
                                
                                <!-- Images -->
                                <div>
                                    <label class="block text-sm font-medium mb-2">Foto (Max 5)</label>
                                    
                                    <div class="flex flex-wrap gap-4 items-start">
                                        @if(isset($images[$shopKey]))
                                            @foreach($images[$shopKey] as $index => $image)
                                                <div class="relative group w-24 h-24 rounded-lg overflow-hidden border bg-white">
                                                    <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover">
                                                    <button type="button" wire:click="removeImage('{{ addslashes($shopKey) }}', {{ $index }})" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                                        <flux:icon.trash class="w-6 h-6 text-white" />
                                                    </button>
                                                </div>
                                            @endforeach
                                        @endif
                                        
                                        @if(!isset($images[$shopKey]) || count($images[$shopKey]) < 5)
                                            <div class="relative w-24 h-24 rounded-lg border-2 border-dashed border-gray-300 hover:border-gray-400 flex flex-col items-center justify-center bg-gray-50 cursor-pointer overflow-hidden transition">
                                                <flux:icon.plus class="w-6 h-6 text-gray-400" />
                                                <span class="text-xs text-gray-500 mt-1">Upload</span>
                                                <input type="file" wire:model="images.{{ $shopKey }}" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div wire:loading wire:target="images.{{ $shopKey }}" class="text-sm text-gray-500 mt-2">
                                        Uploading...
                                    </div>
                                    @error('images.'.$shopKey.'.*') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                                    @error('images.'.$shopKey) <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </flux:card>
                    </div>
                @endif
                
                <div class="pt-4 flex justify-end gap-3 border-t">
                    <flux:button @click="$flux.modal('review-modal-{{ $orderShop->id }}').close()">Batal</flux:button>
                    <flux:button variant="primary" type="submit">
                        <span wire:loading.remove wire:target="submit">Kirim Ulasan</span>
                        <span wire:loading wire:target="submit">Mengirim...</span>
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
</flux:modal>
