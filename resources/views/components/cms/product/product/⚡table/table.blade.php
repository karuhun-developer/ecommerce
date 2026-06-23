<div>
    <div class="flex items-center justify-between mb-4">
        @can('create' . $this->modelInstance)
            <flux:button
                variant="primary"
                icon="plus"
                @click="
                    $flux.modal('defaultModal').show();
                    $wire.dispatch('reset-form');
                "
            >
                Create
            </flux:button>
        @endcan
    </div>
    <div class="flex items-center justify-between mt-5 mb-4 gap-4">
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
                    placeholder="Search ...."
                    wire:model.live.debounce="search"
                    class="max-w-xs"
                />
            </flux:input.group>
        </div>
    </div>

    <flux:table :paginate="$data" class="min-w-full">
        <flux:table.columns>
            <flux:table.column>Actions</flux:table.column>
            <flux:table.column>Shop</flux:table.column>
            <flux:table.column>Category</flux:table.column>
            <flux:table.column>Image</flux:table.column>
            <x-loop-th :$searchBy :$paginationOrder :$paginationOrderBy />
        </flux:table.columns>
        <flux:table.rows>
            @forelse($data as $d)
                <flux:table.row>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button icon:trailing="chevron-down" size="sm">Options</flux:button>
                            <flux:menu>
                                @can('update' . $this->modelInstance)
                                    <flux:menu.item
                                        variant="default"
                                        icon="pencil"
                                        href="{{ route('cms.product.edit', ['product_id' => $d->id]) }}"
                                        wire:navigate
                                    >
                                        Update
                                    </flux:menu.item>
                                @endcan
                                @can('delete' . $this->modelInstance)
                                    <flux:menu.item
                                        variant="danger"
                                        icon="trash"
                                        @click="$wire.dispatch('confirm', {
                                                function: 'delete',
                                                id: '{{ $d->id }}',
                                        })">
                                        Delete
                                    </flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                    <flux:table.cell>{{ $d->shop->name ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $d->category->name ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($d->mainProductFlat->getFirstMediaUrl('image_slot_0') !== '')
                            <img src="{{ $d->mainProductFlat->getFirstMediaUrl('image_slot_0') }}" alt="{{ $d->name }}" class="w-12 h-12 object-cover rounded" />
                        @else
                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                <flux:icon.photo class="w-6 h-6 text-gray-400" />
                            </div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $d->name }}</flux:table.cell>
                    <flux:table.cell>{{ ucfirst($d->type) }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="999" align="center" variant="strong">
                        No data found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:cms.product.product.create lazy />
</div>