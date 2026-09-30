<div>
    <div class="flex items-center justify-between mb-4">
        @can('manageWebsiteContent')
            <flux:button variant="primary" icon="plus" @click="$wire.dispatch('set-action')">
                Tambah grup footer
            </flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 mt-5 mb-4">
        <div class="flex items-center gap-2">
            <flux:text>Tampilkan</flux:text>
            <flux:select size="sm" wire:model.live="paginate" aria-label="Jumlah per grup footer">
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
                    placeholder="Cari grup footer..."
                    wire:model.live.debounce="search"
                    aria-label="Cari grup footer"
                    class="max-w-xs"
                />
            </flux:input.group>
        </div>
    </div>

    <flux:table :paginate="$data" class="min-w-full">
        <flux:table.columns>
            <flux:table.column>Actions</flux:table.column>
            <x-loop-th :$searchBy :$paginationOrder :$paginationOrderBy />
            <flux:table.column>Halaman</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($data as $record)
                <flux:table.row wire:key="footer-group-{{ $record->id }}">
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button icon:trailing="chevron-down" size="sm">Options</flux:button>
                            <flux:menu>
                                <flux:menu.item icon="pencil" @click="$wire.dispatch('set-action', { id: {{ $record->id }} })">Update</flux:menu.item>
                                <flux:menu.item variant="danger" icon="trash" @click="$wire.dispatch('confirm', { function: 'delete', id: {{ $record->id }} })">Delete</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                    <flux:table.cell>{{ $record->name }}</flux:table.cell>

                    <flux:table.cell><flux:badge :color="$record->active ? 'green' : 'zinc'" size="sm">{{ $record->active ? 'Aktif' : 'Nonaktif' }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $record->sort_order }}</flux:table.cell>
                    <flux:table.cell class="max-w-xs whitespace-normal">{{ $record->pages_count }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5" align="center" variant="strong">Belum ada grup footer.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:cms.content.footer-group.create-update />
</div>
