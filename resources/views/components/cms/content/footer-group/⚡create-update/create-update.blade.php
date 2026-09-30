<div>
    <flux:modal name="defaultModal" class="w-full max-w-2xl md:min-w-2xl" flyout @close="closeModal">
        <form class="space-y-6" wire:submit.prevent="submit">
            <div>
                <flux:heading size="lg">{{ $isUpdate ? 'Update grup footer' : 'Tambah grup footer' }}</flux:heading>
                <flux:text class="mt-2">Atur judul kolom footer. Pilih grup ini saat mengedit halaman. Menghapus grup hanya melepas tautan halaman dari footer.</flux:text>
            </div>
            <flux:field>
                <flux:label badge="Required">Nama grup</flux:label>
                <flux:input wire:model="form.name" />
                <flux:error name="form.name" />
            </flux:field>

            <flux:field>
                <flux:label>Urutan</flux:label>
                <flux:input wire:model="form.sort_order" type="number" min="0" />
                <flux:error name="form.sort_order" />
            </flux:field>
            <flux:field>
                <flux:checkbox wire:model="form.active" label="Aktif" />
                <flux:error name="form.active" />
            </flux:field>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close><flux:button>Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan grup footer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
