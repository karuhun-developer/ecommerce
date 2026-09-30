<div>
    <flux:modal name="defaultModal" class="w-full max-w-2xl md:min-w-2xl" flyout @close="closeModal">
        <form class="space-y-6" wire:submit.prevent="submit">
            <div>
                <flux:heading size="lg">{{ $isUpdate ? 'Update menu footer' : 'Menu footer baru' }}</flux:heading>
                <flux:text class="mt-2">Buat tautan beserta isi halamannya, lalu pilih grup footer. Halaman ini juga tersedia sebagai tujuan Menu Header.</flux:text>
            </div>

            @if($formReady)
                <flux:field>
                    <flux:label badge="Required">Judul</flux:label>
                    <flux:input wire:model="form.title" />
                    <flux:error name="form.title" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Required">Slug</flux:label>
                    <flux:text>Huruf kecil, angka, dan tanda hubung. Contoh: tentang-kami.</flux:text>
                    <flux:input wire:model="form.slug" />
                    <flux:error name="form.slug" />
                </flux:field>

                <flux:field>
                    <flux:label>Grup footer</flux:label>
                    <flux:text>Pilih lokasi tautan halaman di footer.</flux:text>
                    <flux:select wire:model="form.footer_group">
                        <option value="">Tidak tampil di footer</option>
                        @foreach($this->footerGroups as $group)
                            <option value="{{ $group->key }}">{{ $group->name }}{{ $group->active ? '' : ' (Nonaktif)' }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.footer_group" />
                </flux:field>

                <flux:field>
                    <flux:label>Urutan</flux:label>
                    <flux:text>Urutan terkecil ditampilkan lebih dulu.</flux:text>
                    <flux:input wire:model="form.sort_order" type="number" min="0" />
                    <flux:error name="form.sort_order" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Required">Isi halaman</flux:label>
                    <livewire:jodit-text-editor
                        wire:model="form.body"
                        :value="$form->body"
                        identifier="custom-page-body"
                        :key="'page-editor-'.$editorRevision"
                    />
                    <flux:error name="form.body" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="form.published" label="Publish halaman" />
                    <flux:text>Draft hanya terlihat di admin. Publish agar halaman dapat dibuka dari website.</flux:text>
                    <flux:error name="form.published" />
                </flux:field>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close><flux:button>Batal</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary">Simpan menu footer</flux:button>
                </div>
            @endif
        </form>
    </flux:modal>
</div>
