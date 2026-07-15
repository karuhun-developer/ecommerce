<div>
    <div class="flex items-center justify-between mt-5 mb-4 gap-4">
        <div class="flex gap-2 overflow-x-auto hide-scrollbar flex-1">
            <flux:button wire:click="setStatus('semua')" variant="{{ $status === 'semua' ? 'primary' : 'outline' }}" size="sm">Semua</flux:button>
            <flux:button wire:click="setStatus('pending')" variant="{{ $status === 'pending' ? 'primary' : 'outline' }}" size="sm">Pending</flux:button>
            <flux:button wire:click="setStatus('approved')" variant="{{ $status === 'approved' ? 'primary' : 'outline' }}" size="sm">Approved</flux:button>
            <flux:button wire:click="setStatus('rejected')" variant="{{ $status === 'rejected' ? 'primary' : 'outline' }}" size="sm">Rejected</flux:button>
        </div>
        
        <div class="flex items-center gap-2">
            <p class="text-sm text-gray-600">Show</p>
            <flux:select size="sm" wire:model.live.debounce="paginate" placeholder="Per Page">
                <option value="10">10 Per Page</option>
                <option value="25">25 Per Page</option>
                <option value="50">50 Per Page</option>
                <option value="100">100 Per Page</option>
            </flux:select>
        </div>

        <div class="flex items-center gap-2">
            <flux:input.group>
                <flux:input
                    size="sm"
                    icon="magnifying-glass"
                    type="text"
                    placeholder="Search user or comment..."
                    wire:model.live.debounce="search"
                    class="max-w-xs"
                />
            </flux:input.group>
        </div>
    </div>

    <flux:table :paginate="$this->data" class="min-w-full">
        <flux:table.columns>
            <flux:table.column>Actions</flux:table.column>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Target</flux:table.column>
            <flux:table.column>Rating</flux:table.column>
            <flux:table.column>Comment</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>
        
        <flux:table.rows>
            @forelse($this->data as $review)
                <flux:table.row>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button icon:trailing="chevron-down" size="sm">Options</flux:button>
                            <flux:menu>
                                @if($review->status !== 'approved')
                                    <flux:menu.item
                                        icon="check"
                                        @click="$wire.dispatch('confirm', {
                                            function: 'accept',
                                            id: '{{ $review->id }}',
                                            title: 'Accept Review',
                                            description: 'Are you sure you want to accept this review?'
                                        })">
                                        Accept
                                    </flux:menu.item>
                                @endif
                                
                                @if($review->status !== 'rejected')
                                    <flux:menu.item
                                        icon="x-mark"
                                        @click="$wire.dispatch('confirm', {
                                            function: 'reject',
                                            id: '{{ $review->id }}',
                                            title: 'Reject Review',
                                            description: 'Are you sure you want to reject this review?'
                                        })">
                                        Reject
                                    </flux:menu.item>
                                @endif
                                
                                <flux:menu.item
                                    variant="danger"
                                    icon="trash"
                                    @click="$wire.dispatch('confirm', {
                                            function: 'delete',
                                            id: '{{ $review->id }}',
                                            title: 'Delete Review',
                                            description: 'Are you sure you want to delete this review? This action cannot be undone.'
                                    })">
                                    Delete
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <div class="font-medium text-gray-900">{{ $review->user->name ?? '-' }}</div>
                        <div class="text-xs text-gray-500">{{ $review->user->email ?? '-' }}</div>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        @if($review->reviewable_type === \App\Models\Product\Product::class && $review->reviewable)
                            <div class="text-sm font-medium">Produk: {{ $review->reviewable->name }}</div>
                        @elseif($review->reviewable_type === \App\Models\Shop\Shop::class && $review->reviewable)
                            <div class="text-sm font-medium">Toko: {{ $review->reviewable->name }}</div>
                        @else
                            <div class="text-sm text-gray-500">Target tidak ditemukan</div>
                        @endif
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <div class="flex items-center gap-1">
                            <flux:icon.star variant="solid" class="w-4 h-4 text-yellow-400" />
                            <span class="font-bold">{{ number_format($review->rating, 1) }}</span>
                        </div>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <div class="max-w-xs whitespace-normal">
                            @if($review->comment)
                                <p class="text-sm text-gray-700 mb-2">{{ $review->comment }}</p>
                            @else
                                <span class="text-sm text-gray-400 italic">Tanpa komentar</span>
                            @endif
                            
                            @if($review->hasMedia('review_images'))
                                <div class="flex gap-2 flex-wrap mt-2">
                                    @foreach($review->getMedia('review_images') as $media)
                                        <a href="{{ $media->getUrl() }}" target="_blank">
                                            <img src="{{ $media->getUrl() }}" class="w-12 h-12 object-cover rounded border">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        @if($review->status === 'approved')
                            <flux:badge color="green" size="sm">Approved</flux:badge>
                        @elseif($review->status === 'rejected')
                            <flux:badge color="red" size="sm">Rejected</flux:badge>
                        @else
                            <flux:badge color="yellow" size="sm">Pending</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="999" align="center" variant="strong">
                        No reviews found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
