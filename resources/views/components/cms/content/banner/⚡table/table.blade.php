<div>
    <div class="flex items-center justify-between mb-4">
        @can('manageWebsiteContent')
            <flux:button variant="primary" icon="plus" @click="$wire.dispatch('set-action')">
                Tambah banner
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
                    placeholder="Cari banner..."
                    wire:model.live.debounce="search"
                    aria-label="Cari banner"
                    class="max-w-xs"
                />
            </flux:input.group>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($data as $record)
            <article wire:key="banner-{{ $record->id }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                @if($record->hasMedia('banner'))
                    <img src="{{ $record->getFirstMediaUrl('banner') }}" alt="{{ $record->image_alt }}" class="h-40 w-full object-cover" />
                @else
                    <div class="flex h-40 items-center justify-center bg-zinc-100 dark:bg-zinc-800"><flux:icon.photo class="size-10 text-zinc-400" /></div>
                @endif
                <div class="flex flex-col gap-3 p-5">
                    <div class="flex justify-between gap-3">
                        <flux:heading class="min-w-0 break-words">{{ $record->title }}</flux:heading>
                        <flux:badge :color="$record->active ? 'green' : 'zinc'" size="sm">{{ $record->active ? 'Aktif' : 'Nonaktif' }}</flux:badge>
                    </div>
                    @if($record->subtitle)<flux:text>{{ $record->subtitle }}</flux:text>@endif
                    <flux:text>Urutan {{ $record->sort_order }}</flux:text>
                    <flux:text>{{ $record->starts_at?->format('d M Y H:i') ?? 'Tanpa batas mulai' }} &ndash; {{ $record->ends_at?->format('d M Y H:i') ?? 'Tanpa batas akhir' }}</flux:text>
                    <div class="flex gap-2">
                        <flux:button size="sm" icon="pencil" @click="$wire.dispatch('set-action', { id: {{ $record->id }} })">Edit</flux:button>
                        <flux:button size="sm" variant="danger" icon="trash" @click="$wire.dispatch('confirm', { function: 'delete', id: {{ $record->id }} })">Hapus</flux:button>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center md:col-span-2 xl:col-span-3 dark:border-zinc-700">
                <flux:heading>Belum ada banner</flux:heading>
                <flux:text class="mt-2">Tambah gambar dan informasi promosi untuk homepage.</flux:text>
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
    <livewire:cms.content.banner.create-update />
</div>
