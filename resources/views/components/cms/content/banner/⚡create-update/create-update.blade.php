<div>
    <flux:modal name="defaultModal" class="w-full max-w-2xl md:min-w-2xl" flyout @close="closeModal">
        <form class="space-y-6" wire:submit.prevent="submit">
            <div>
                <flux:heading size="lg">{{ $isUpdate ? 'Update banner' : 'Banner baru' }}</flux:heading>
                <flux:text class="mt-2">Atur gambar promosi dan jadwal tampil di homepage.</flux:text>
            </div>

            <flux:field>
                <flux:label badge="Required">Judul</flux:label>
                <flux:input wire:model="form.title" />
                <flux:error name="form.title" />
            </flux:field>

            <flux:field>
                <flux:label>Subjudul</flux:label>
                <flux:text>Teks singkat untuk mendampingi judul banner.</flux:text>
                <flux:textarea wire:model="form.subtitle" />
                <flux:error name="form.subtitle" />
            </flux:field>

            <flux:field>
                <flux:label>Gambar</flux:label>
                <flux:text>JPG, PNG, atau WebP, maksimal 5 MB. Saat update, kosongkan untuk memakai gambar sebelumnya.</flux:text>
                <flux:input wire:model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <flux:error name="form.image" />
            </flux:field>

            @if($form->image || $imageUrl)
                <img src="{{ $form->image?->temporaryUrl() ?? $imageUrl }}" alt="Pratinjau banner" class="h-44 w-full rounded-lg object-cover" />
            @endif
            <flux:text wire:loading wire:target="form.image">Mengunggah gambar...</flux:text>

            <flux:field>
                <flux:label badge="Required">Teks alternatif gambar</flux:label>
                <flux:text>Jelaskan isi gambar untuk aksesibilitas.</flux:text>
                <flux:input wire:model="form.image_alt" />
                <flux:error name="form.image_alt" />
            </flux:field>

            <flux:field>
                <flux:label>Urutan</flux:label>
                <flux:text>Urutan terkecil ditampilkan lebih dulu.</flux:text>
                <flux:input wire:model="form.sort_order" type="number" min="0" />
                <flux:error name="form.sort_order" />
            </flux:field>

            <flux:field>
                <flux:label>Teks tombol</flux:label>
                <flux:text>Opsional. Isi teks dan URL bersama untuk menampilkan tombol.</flux:text>
                <flux:input wire:model="form.cta_label" />
                <flux:error name="form.cta_label" />
            </flux:field>

            <flux:field>
                <flux:label>URL tombol</flux:label>
                <flux:input wire:model="form.cta_url" type="url" placeholder="https://..." />
                <flux:error name="form.cta_url" />
            </flux:field>

            <div class="grid gap-6 md:grid-cols-2">
                <flux:field>
                    <flux:label>Mulai tampil</flux:label>
                    <flux:input wire:model="form.starts_at" type="datetime-local" />
                    <flux:error name="form.starts_at" />
                </flux:field>
                <flux:field>
                    <flux:label>Selesai tampil</flux:label>
                    <flux:input wire:model="form.ends_at" type="datetime-local" />
                    <flux:error name="form.ends_at" />
                </flux:field>
            </div>

            <flux:field>
                <flux:checkbox wire:model="form.active" label="Aktif" />
                <flux:text>Banner aktif ditampilkan sesuai jadwal. Kosongkan jadwal untuk tampil tanpa batas waktu.</flux:text>
                <flux:error name="form.active" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close><flux:button>Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="form.image,submit">Simpan banner</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
