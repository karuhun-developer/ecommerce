<div>
    <div class="flex items-center justify-between mb-4">
        @can('manageWebsiteContent')
            <flux:button variant="primary" icon="plus" @click="$wire.dispatch('set-action')">
                Tambah menu footer
            </flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 mt-5 mb-4">
        <div class="flex items-center gap-2">
            <flux:text>Tampilkan</flux:text>
            <flux:select size="sm" wire:model.live="paginate" aria-label="Jumlah per halaman">
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
                    placeholder="Cari menu footer..."
                    wire:model.live.debounce="search"
                    aria-label="Cari menu footer"
                    class="max-w-xs"
                />
            </flux:input.group>
        </div>
    </div>

    <flux:table :paginate="$data" class="min-w-full">
        <flux:table.columns>
            <flux:table.column>Actions</flux:table.column>
            <x-loop-th :$searchBy :$paginationOrder :$paginationOrderBy />
        </flux:table.columns>
        <flux:table.rows>
            @forelse($data as $record)
                <flux:table.row wire:key="page-{{ $record->id }}">
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button icon:trailing="chevron-down" size="sm">Options</flux:button>
                            <flux:menu>
                                @can('manageWebsiteContent')
                                    <flux:menu.item
                                        icon="pencil"
                                        @click="$wire.dispatch('set-action', { id: {{ $record->id }} })"
                                    >
                                        Update
                                    </flux:menu.item>
                                    @if($record->published)
                                        <flux:menu.item icon="arrow-top-right-on-square" href="{{ route('content.page', ['slug' => $record->slug]) }}" target="_blank">
                                            Lihat halaman
                                        </flux:menu.item>
                                    @endif
                                    <flux:menu.item
                                        variant="danger"
                                        icon="trash"
                                        @click="$wire.dispatch('confirm', { function: 'delete', id: {{ $record->id }} })"
                                    >
                                        Delete
                                    </flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                    <flux:table.cell class="max-w-xs whitespace-normal">{{ $record->title }}</flux:table.cell>
                    <flux:table.cell>{{ $record->slug }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$record->published ? 'green' : 'zinc'" size="sm">
                            {{ $record->published ? 'Published' : 'Draft' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $record->footerGroup?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $record->sort_order }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" align="center" variant="strong">Belum ada menu footer.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:cms.content.page.create-update />
</div>
