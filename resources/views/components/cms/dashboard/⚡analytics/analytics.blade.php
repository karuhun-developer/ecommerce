<div class="space-y-6">
    @php
        $dashboard = $this->dashboard;
    @endphp
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Ringkasan bisnis</flux:heading>
            <flux:text class="mt-1">Pantau penjualan, pengiriman, dan persediaan toko.</flux:text>
        </div>
        <div class="text-sm text-zinc-500">{{ \Carbon\Carbon::parse($appliedStart)->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($appliedEnd)->translatedFormat('d M Y') }}</div>
    </div>
    <form wire:submit="apply" class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input type="date" wire:model="form.startDate" label="Dari tanggal" />
        <flux:input type="date" wire:model="form.endDate" label="Sampai tanggal" />
        <flux:select wire:model="form.shopId" label="Toko">
            <option value="">Semua toko</option>
            @foreach($this->shops as $shop)
                <option value="{{ $shop->id }}" wire:key="dashboard-shop-{{ $shop->id }}">{{ $shop->name }}</option>
            @endforeach
        </flux:select>
        <div class="flex items-end"><flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">Terapkan filter</flux:button></div>
    </form>
    <div wire:loading wire:target="apply" class="text-sm text-zinc-500" role="status">Memperbarui ringkasan…</div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach(['revenue'=>'Pendapatan produk','orders'=>'Pesanan toko','shops'=>'Toko','products'=>'Varian produk'] as $key=>$label)
            <div wire:key="stat-{{ $key }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight">{{ $key === 'revenue' ? numberToCurrency($dashboard->stats[$key]) : number_format($dashboard->stats[$key]) }}</div>
                @if($key === 'revenue')<p class="mt-2 text-xs text-zinc-500">Dari pesanan yang sudah dibayar.</p>@endif
            </div>
        @endforeach
    </div>
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Tren pendapatan</flux:heading>
            <flux:text>Penjualan produk per hari dalam periode yang dipilih.</flux:text>
            @php
                $maximum = max(1, ...array_column($dashboard->trend, 'revenue'));
                $count = count($dashboard->trend);
                $points = collect($dashboard->trend)->map(fn($point,$index)=> (20 + $index * 660 / max(1,$count-1)).','. (210 - $point['revenue'] * 185 / $maximum))->implode(' ');
            @endphp
            <div class="mt-6" x-data="{ selected: null }" wire:key="trend-{{ $appliedStart }}-{{ $appliedEnd }}-{{ $appliedShopId }}">
                <svg viewBox="0 0 700 240" class="w-full text-zinc-800 dark:text-zinc-200" role="img" aria-label="Grafik pendapatan harian">
                    <path d="M20 25H680 M20 117H680 M20 210H680" fill="none" class="stroke-zinc-200 dark:stroke-zinc-700" />
                    <polygon points="20,210 {{ $points }} 680,210" fill="currentColor" opacity="0.06" />
                    <polyline points="{{ $points }}" fill="none" stroke="currentColor" stroke-width="2.5" />
                    @foreach($dashboard->trend as $index=>$point)
                        <circle wire:key="point-{{ $point['date'] }}" cx="{{ 20 + $index * 660 / max(1,$count-1) }}" cy="{{ 210 - $point['revenue'] * 185 / $maximum }}" r="{{ $count > 60 ? 2 : 4 }}" fill="currentColor" tabindex="0" @mouseenter="selected = @js($point)" @focus="selected = @js($point)">
                            <title>{{ $point['date'] }}: {{ numberToCurrency($point['revenue']) }}, {{ $point['paid'] }} dibayar, {{ $point['unpaid'] }} belum dibayar</title>
                        </circle>
                    @endforeach
                </svg>
                <div class="flex justify-between text-xs text-zinc-500"><span>{{ $appliedStart }}</span><span>{{ numberToCurrency($maximum) }} tertinggi</span><span>{{ $appliedEnd }}</span></div>
                <div class="mt-3 min-h-6 text-sm text-zinc-500" aria-live="polite" x-text="selected ? selected.date + ' · Rp ' + new Intl.NumberFormat('id-ID').format(selected.revenue) + ' · ' + selected.paid + ' dibayar · ' + selected.unpaid + ' belum dibayar' : 'Arahkan ke titik untuk melihat rincian harian.'"></div>
            </div>
        </div>
        <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Status pesanan</flux:heading>
            <div class="space-y-3">
                @foreach(['paid'=>'Dibayar','unpaid'=>'Menunggu pembayaran','expired'=>'Pembayaran kedaluwarsa','pending'=>'Menunggu pengiriman','in_transit'=>'Dalam pengiriman','delivered'=>'Terkirim'] as $key=>$label)
                    <div wire:key="bucket-{{ $key }}" class="flex items-center justify-between gap-3 text-sm"><span class="text-zinc-500 dark:text-zinc-400">{{ $label }}</span><span class="font-semibold tabular-nums">{{ number_format($dashboard->stats[$key]) }}</span></div>
                @endforeach
            </div>
            @if($dashboard->stats['users'] !== null)
                <flux:separator />
                <div class="flex justify-between gap-3 text-sm"><span class="text-zinc-500">Total pelanggan</span><span class="font-semibold">{{ number_format($dashboard->stats['users']) }}</span></div>
            @endif
        </div>
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Produk terlaris</flux:heading>
            <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($dashboard->topProducts as $product)
                    <div wire:key="top-{{ $product['id'] }}" class="flex items-center justify-between gap-4 py-3"><div class="min-w-0"><p class="truncate font-medium">{{ $product['name'] }}</p><p class="text-xs text-zinc-500">{{ $product['quantity'] }} unit terjual</p></div><span class="shrink-0 text-sm">{{ numberToCurrency($product['revenue']) }}</span></div>
                @empty
                    <p class="py-6 text-sm text-zinc-500">Belum ada produk terjual pada periode ini.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Stok perlu perhatian</flux:heading>
            <flux:text>Varian dengan sisa stok 5 atau kurang.</flux:text>
            <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($dashboard->lowStock as $product)
                    <div wire:key="stock-{{ $product->id }}" class="flex items-center justify-between gap-4 py-3"><div class="min-w-0"><p class="truncate font-medium">{{ $product->name }}</p><p class="text-xs text-zinc-500">{{ $product->shop?->name }}</p></div><flux:badge color="{{ $product->stock > 0 ? 'amber' : 'red' }}">{{ $product->stock }} tersisa</flux:badge></div>
                @empty
                    <p class="py-6 text-sm text-zinc-500">Tidak ada stok rendah.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Transaksi terbaru</flux:heading>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-zinc-500"><tr><th class="py-3 pr-4">Pesanan</th><th class="pr-4">Pelanggan / toko</th><th class="pr-4">Total produk</th><th class="pr-4">Pembayaran</th><th>Tanggal</th></tr></thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($dashboard->recentTransactions as $transaction)
                        <tr wire:key="recent-{{ $transaction->id }}">
                            <td class="whitespace-nowrap py-3 pr-4 font-medium">{{ $transaction->order->reference }}</td>
                            <td class="py-3 pr-4"><p>{{ $transaction->order->user?->name ?? $transaction->order->guest_data['contact_name'] ?? 'Tamu' }}</p><p class="text-xs text-zinc-500">{{ $transaction->shop?->name }}</p></td>
                            <td class="whitespace-nowrap py-3 pr-4">{{ numberToCurrency($transaction->total_checkout) }}</td>
                            <td class="py-3 pr-4"><flux:badge color="{{ $transaction->order->status ? 'zinc' : 'amber' }}">{{ $transaction->order->status ? 'Dibayar' : ($transaction->order->latestPayment?->expired_at?->isPast() ? 'Kedaluwarsa' : 'Menunggu') }}</flux:badge></td>
                            <td class="whitespace-nowrap py-3 text-zinc-500">{{ $transaction->order->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-zinc-500">Belum ada transaksi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
