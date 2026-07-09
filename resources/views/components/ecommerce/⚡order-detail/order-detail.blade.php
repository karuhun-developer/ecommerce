<div>
    <!-- Success Message (Mock) -->
    <template x-if="new URLSearchParams(location.search).get('success') === 'true'">
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl flex items-center gap-3 mb-6">
            <flux:icon.check-circle class="w-6 h-6 text-green-500" />
            <div>
                <h3 class="font-bold">Pembayaran Berhasil!</h3>
                <p class="text-sm">Pesanan Anda segera diproses oleh penjual.</p>
            </div>
        </div>
    </template>

    <div class="flex justify-between items-center mb-6">
        <a href="/orders" class="text-green-600 hover:text-green-700 font-bold text-sm flex items-center gap-2" wire:navigate>
            <flux:icon.arrow-left class="w-4 h-4" /> Kembali ke Daftar Transaksi
        </a>
        
        @if(!$isPaid)
            <flux:button href="{{ route('payment.show', ['reference' => $order->reference]) }}" variant="primary" size="sm" wire:navigate>
                Pay This Order
            </flux:button>
        @endif
    </div>

    <div class="bg-white border rounded-2xl shadow-sm p-6 mb-6">
        <div class="flex justify-between items-start border-b pb-4 mb-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 mb-1">Detail Transaksi</h1>
                <p class="text-sm text-gray-500">{{ $order->reference }}</p>
            </div>
            @if($isPaid)
                <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full">Dibayar</span>
            @else
                <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-3 py-1 rounded-full">Menunggu Pembayaran</span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
            <div>
                <h3 class="font-bold text-gray-900 mb-3 text-sm">Info Pengiriman</h3>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><span class="w-24 inline-block text-gray-500">Alamat</span>:</p>
                    @if($order->guest_data)
                        <p class="font-bold text-gray-900">{{ $order->guest_data['name'] ?? '' }}</p>
                        <p>{{ $order->guest_data['phone'] ?? '' }}</p>
                    @else
                        <p class="font-bold text-gray-900">{{ $order->user->name ?? '' }}</p>
                        <p>{{ $order->user->phone ?? '' }}</p>
                    @endif
                    <p>{{ $order->location->address ?? '' }}, {{ $order->location->district ?? '' }}, {{ $order->location->city ?? '' }}, {{ $order->location->province ?? '' }} {{ $order->location->postal_code ?? '' }}</p>
                </div>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 mb-3 text-sm">Info Pembayaran</h3>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><span class="w-32 inline-block text-gray-500">Metode Bayar</span>: {{ $payment ? strtoupper($payment->payment_type) . ' (' . strtoupper($payment->channel) . ')' : '-' }}</p>
                    <p><span class="w-32 inline-block text-gray-500">Status</span>: {{ $isPaid ? 'Sudah Dibayar' : 'Menunggu Pembayaran' }}</p>
                </div>
            </div>
        </div>

        <div class="border-t pt-4">
            <h3 class="font-bold text-gray-900 mb-4 text-sm">Rincian Produk</h3>
            
            <div class="space-y-6">
                @foreach($order->orderShops as $orderShop)
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <flux:icon.building-storefront class="w-5 h-5 text-gray-500" />
                            <span class="font-bold text-gray-900 text-sm">{{ $orderShop->shop->name ?? 'Toko' }}</span>
                        </div>
                        
                        @if($orderShop->latestShipment)
                        <div class="bg-gray-50 border rounded-lg p-3 text-sm flex gap-4">
                            <div class="flex-1">
                                <p class="text-gray-500 mb-1">Kurir: <strong class="text-gray-900">{{ $orderShop->latestShipment->courier_company }} ({{ $orderShop->latestShipment->courier_type }})</strong></p>
                                <p class="text-gray-500">Resi: <strong class="text-gray-900">{{ $orderShop->latestShipment->courier_waybill_id ?? '-' }}</strong></p>
                            </div>
                            <div>
                                <p class="text-gray-500 mb-1">Status: <strong class="text-gray-900">{{ $orderShop->latestShipment->status ?? 'Menunggu Resi' }}</strong></p>
                            </div>
                        </div>
                        @endif

                        @foreach($orderShop->items as $item)
                        <div class="flex gap-4 border p-4 rounded-xl">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-sm mb-1">{{ $item->product_data['name'] ?? 'Product Item' }}</h4>
                                <p class="text-xs text-gray-500 mb-2">{{ $item->quantity }} x Rp{{ number_format($item->price, 0, ',', '.') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 mb-1">Total Harga</p>
                                <p class="font-bold text-gray-900">Rp{{ number_format($item->total, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div class="border-t pt-4 mt-6">
            <h3 class="font-bold text-gray-900 mb-3 text-sm">Rincian Pembayaran</h3>
            <div class="space-y-2 text-sm text-gray-600 max-w-sm ml-auto">
                <div class="flex justify-between">
                    <span>Total Harga Barang</span>
                    <span>Rp{{ number_format($order->total_checkout, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Total Ongkos Kirim</span>
                    <span>Rp{{ number_format($order->total_shipping, 0, ',', '.') }}</span>
                </div>
                @if($order->insurance_fee > 0)
                <div class="flex justify-between">
                    <span>Asuransi Pengiriman</span>
                    <span>Rp{{ number_format($order->insurance_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($order->application_fee > 0)
                <div class="flex justify-between">
                    <span>Biaya Jasa Aplikasi</span>
                    <span>Rp{{ number_format($order->application_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($order->payment_fee > 0)
                <div class="flex justify-between">
                    <span>Biaya Layanan Pembayaran</span>
                    <span>Rp{{ number_format($order->payment_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between border-t pt-2 mt-2 font-bold text-gray-900">
                    <span>Total Belanja</span>
                    <span>Rp{{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

    </div>
</div>
