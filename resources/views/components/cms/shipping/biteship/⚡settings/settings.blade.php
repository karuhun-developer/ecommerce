
<section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
    <flux:heading size="lg">Pengiriman — Biteship</flux:heading>
    <flux:text class="mt-2">Atur kredensial pengiriman dan autentikasi webhook Biteship.</flux:text>
    <form wire:submit="save" class="mt-5 space-y-5">
        <flux:input wire:model="configuration.api_key" type="password" label="API Key Biteship" autocomplete="new-password"
            :placeholder="($hasCredentials['api_key'] ?? false) ? 'API key sudah dikonfigurasi' : 'Masukkan API key Biteship'"
            description="Kosongkan untuk mempertahankan API key yang sudah tersimpan." />
        <flux:input wire:model="configuration.header_key" label="Nama Header Webhook" placeholder="X-Biteship-Webhook-Secret"
            description="Gunakan nama header yang sama pada webhook di dashboard Biteship." />
        <flux:input wire:model="configuration.header_secret" type="password" label="Secret Header Webhook" autocomplete="new-password"
            :placeholder="($hasCredentials['header_secret'] ?? false) ? 'Secret sudah dikonfigurasi' : 'Masukkan secret webhook'"
            description="Gunakan nilai yang sama di Biteship. Kosongkan jika secret sudah tersimpan." />
        <details class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <summary class="cursor-pointer text-sm font-medium">Pengaturan lanjutan</summary>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="configuration.timeout" type="number" label="Timeout (detik)" min="1" max="120" />
                <flux:input wire:model="configuration.connect_timeout" type="number" label="Connection Timeout (detik)" min="1" max="120" />
                <flux:input wire:model="configuration.retries" type="number" label="Jumlah Retry" min="0" max="5" />
            </div>
        </details>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:button :href="route('cms.shipping.courier')" wire:navigate>Atur kurir</flux:button>
            @can('update'.\App\Models\Setting\Setting::class)
                <flux:button type="submit" variant="primary">Simpan Biteship</flux:button>
            @endcan
        </div>
    </form>
</section>
