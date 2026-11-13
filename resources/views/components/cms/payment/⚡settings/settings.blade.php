
<section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
    <flux:heading size="lg">Pembayaran</flux:heading>
    <flux:text class="mt-2">Pilih gateway, isi kredensialnya, lalu simpan untuk mengaktifkannya.</flux:text>
    <form wire:submit="save" class="mt-5 space-y-6">
        <flux:select wire:model.live="driver" label="Payment Gateway">
            <option value="midtrans">Midtrans</option>
            <option value="paywuz">Paywuz</option>
            <option value="karuhunqris">Karuhun QRIS</option>
        </flux:select>

        <div wire:key="gateway-{{ $driver }}" class="space-y-5">
            @if ($driver === 'midtrans')
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="configuration.merchant_id" label="Merchant ID" />
                    <flux:select wire:model="configuration.is_production" label="Mode Midtrans">
                        <option value="0">Sandbox / Testing</option>
                        <option value="1">Production / Live</option>
                    </flux:select>
                </div>
                <flux:input wire:model="configuration.server_key" type="password" label="Server Key" autocomplete="new-password"
                    :placeholder="($hasCredentials['server_key'] ?? false) ? 'Server key sudah dikonfigurasi' : 'Masukkan server key Midtrans'"
                    description="Gunakan key sesuai mode yang dipilih. Kosongkan jika key sudah tersimpan." />
                <flux:input wire:model="configuration.client_key" type="password" label="Client Key" autocomplete="new-password"
                    :placeholder="($hasCredentials['client_key'] ?? false) ? 'Client key sudah dikonfigurasi' : 'Masukkan client key Midtrans'"
                    description="Kosongkan untuk mempertahankan key yang sudah tersimpan." />
            @elseif ($driver === 'paywuz' || $driver === 'karuhunqris')
                <flux:input wire:model="configuration.api_key" type="password" label="API Key" autocomplete="new-password"
                    :placeholder="($hasCredentials['api_key'] ?? false) ? 'API key sudah dikonfigurasi' : 'Masukkan API key merchant'"
                    description="Kosongkan untuk mempertahankan API key yang sudah tersimpan." />
                <flux:input wire:model="configuration.base_url" type="url" label="URL API" />
            @endif
            @if ($driver === 'karuhunqris')
                <flux:callout>
                    <flux:callout.text>Scheduler wajib aktif untuk cek pembayaran otomatis: <code>php artisan schedule:work</code> saat development, atau <code>schedule:run</code> setiap menit di production.</flux:callout.text>
                </flux:callout>
            @endif
            <details class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <summary class="cursor-pointer text-sm font-medium">Pengaturan lanjutan</summary>
                <div class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="configuration.timeout" type="number" label="Timeout (detik)" min="1" max="120" />
                        <flux:input wire:model="configuration.connect_timeout" type="number" label="Connection Timeout (detik)" min="1" max="120" />
                    </div>
                    @if ($driver === 'midtrans')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input wire:model="configuration.retries" type="number" label="Jumlah Retry" min="0" max="5" />
                            <flux:input wire:model="configuration.timezone" label="Timezone Midtrans" />
                        </div>
                    @elseif ($driver === 'paywuz')
                        <flux:input wire:model="configuration.expiry_minutes" type="number" label="Masa berlaku (menit)" min="1" max="1440" description="Kosongkan untuk mengikuti pengaturan Paywuz." />
                        <flux:input wire:model="configuration.redirect_url" type="url" label="Redirect URL" description="Opsional: halaman tujuan pelanggan setelah pembayaran." />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:select wire:model="configuration.fee_by_merchant" label="Biaya pembayaran">
                                <option value="">Ikuti default Paywuz</option>
                                <option value="0">Ditanggung pembeli</option>
                                <option value="1">Ditanggung merchant</option>
                            </flux:select>
                            <flux:select wire:model="configuration.payment_reminder_enabled" label="Pengingat pembayaran">
                                <option value="">Ikuti default Paywuz</option>
                                <option value="0">Nonaktif</option>
                                <option value="1">Aktif</option>
                            </flux:select>
                        </div>
                    @endif
                </div>
            </details>
        </div>

        @can('update'.\App\Models\Setting\Setting::class)
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Simpan pembayaran</flux:button>
            </div>
        @endcan
    </form>
</section>
