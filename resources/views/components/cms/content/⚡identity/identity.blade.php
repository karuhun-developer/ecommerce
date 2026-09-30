<form wire:submit="save" class="w-full space-y-6">
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="min-w-0 space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">Identitas brand</flux:heading>
                <flux:text class="mt-2">Nama dan deskripsi yang ditampilkan di footer website.</flux:text>
            </div>

            <flux:field>
                <flux:label badge="Required">Nama brand</flux:label>
                <flux:input wire:model="form.brand_name" />
                <flux:error name="form.brand_name" />
            </flux:field>

            <flux:field>
                <flux:label>Deskripsi singkat</flux:label>
                <flux:text>Ceritakan brand secara singkat.</flux:text>
                <flux:textarea wire:model="form.tagline" />
                <flux:error name="form.tagline" />
            </flux:field>
        </div>

        <div class="min-w-0 space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">Tautan sosial</flux:heading>
                <flux:text class="mt-2">Tautan yang kosong tidak ditampilkan di footer.</flux:text>
            </div>

            <flux:field>
                <flux:label>Instagram</flux:label>
                <flux:input wire:model="form.instagram_url" type="url" placeholder="https://www.instagram.com/..." />
                <flux:error name="form.instagram_url" />
            </flux:field>

            <flux:field>
                <flux:label>Website</flux:label>
                <flux:input wire:model="form.website_url" type="url" placeholder="https://..." />
                <flux:error name="form.website_url" />
            </flux:field>

            <flux:field>
                <flux:label>WhatsApp</flux:label>
                <flux:input wire:model="form.whatsapp_url" type="url" placeholder="https://wa.me/..." />
                <flux:error name="form.whatsapp_url" />
            </flux:field>
        </div>
    </div>

    <div class="flex justify-end">
        <flux:button type="submit" variant="primary">Simpan identitas</flux:button>
    </div>
</form>
