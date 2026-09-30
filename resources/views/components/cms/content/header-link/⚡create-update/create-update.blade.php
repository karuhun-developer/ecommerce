<div>
    <flux:modal name="defaultModal" class="w-full max-w-2xl md:min-w-2xl" flyout @close="closeModal">
        <form class="space-y-6" wire:submit.prevent="submit">
            <div>
                <flux:heading size="lg">{{ $isUpdate ? 'Update tautan header' : 'Tambah tautan header' }}</flux:heading>
                <flux:text class="mt-2">Atur menu di bagian atas homepage. Halaman draft tidak ditampilkan.</flux:text>
            </div>
            <flux:field>
                <flux:label badge="Required">Label</flux:label>
                <flux:input wire:model="form.label" />
                <flux:error name="form.label" />
            </flux:field>
            <flux:field>
                <flux:label>Posisi</flux:label>
                <flux:select wire:model="form.position"><option value="left">Kiri</option><option value="right">Kanan</option></flux:select>
                <flux:error name="form.position" />
            </flux:field>
            <flux:field>
                <flux:label>Jenis tujuan</flux:label>
                <flux:select wire:model.live="form.destination"><option value="page">Halaman website</option><option value="url">URL</option></flux:select>
                <flux:error name="form.destination" />
            </flux:field>
            @if($form->destination === 'page')
                <flux:field>
                    <flux:label badge="Required">Halaman</flux:label>
                    <flux:select wire:model="form.page_id">
                        <option value="">Pilih halaman</option>
                        @foreach($this->pages as $page)
                            <option value="{{ $page->id }}">{{ $page->title }}{{ $page->published ? '' : ' (Draft)' }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.page_id" />
                </flux:field>
            @else
                <flux:field>
                    <flux:label badge="Required">URL</flux:label>
                    <flux:input wire:model="form.url" type="url" placeholder="https://..." />
                    <flux:error name="form.url" />
                </flux:field>
            @endif
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
                <flux:button type="submit" variant="primary">Simpan tautan header</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
