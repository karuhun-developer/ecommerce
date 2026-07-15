<div>
    <div class="flex justify-between items-center mb-6">
        @auth
            <a href="/orders" class="text-gray-600 hover:text-gray-700 font-bold text-sm flex items-center gap-2" wire:navigate>
                <flux:icon.arrow-left class="w-4 h-4" /> Kembali ke Daftar Transaksi
            </a>
        @endauth
        @guest
            <flux:spacer />
        @endguest
        @if(!$isPaid)
            <flux:button href="{{ route('payment.show', ['reference' => $order->reference]) }}" icon="credit-card" variant="primary" size="sm" wire:navigate>
                Bayar Sekarang
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
                <flux:badge color="green" size="sm">Dibayar</flux:badge>
            @else
                <flux:badge color="yellow" size="sm">Menunggu Pembayaran</flux:badge>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
            <div>
                <h3 class="font-bold text-gray-900 mb-3 text-sm">Info Pengiriman</h3>
                <div class="text-sm text-gray-600 space-y-1">
                    @if($order->guest_data)
                        <p class="font-bold text-gray-900">
                            {{ $order->guest_data['contact_name'] ?? '' }}
                        </p>
                        <p>
                            {{ $order->guest_data['contact_phone'] ?? '' }}
                        </p>
                        <p>
                            {{ $order->guest_data['contact_email'] ?? '' }}
                        </p>
                        <p>
                            {{ $order->guest_data['area_string'] ?? '' }}, <br>
                            {{ $order->guest_data['address'] ?? '' }} [{{ $order->guest_data['note'] ?? '' }}]
                        </p>
                    @else
                        <p class="font-bold text-gray-900">{{ $order->location->contact_name ?? '' }}</p>
                        <p>{{ $order->location->contact_phone ?? '' }}</p>
                        <p>{{ $order->location->contact_email ?? '' }}</p>
                        <p>
                            {{ $order->location->area_string ?? '' }}, <br>
                            {{ $order->location->address ?? '' }} [{{ $order->location->note ?? '' }}]
                        </p>
                    @endif
                </div>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 mb-3 text-sm">Info Pembayaran</h3>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><span class="w-32 inline-block text-gray-500">Metode Bayar</span>: {{ $payment ? strtoupper($payment->payment_type) . ' (' . strtoupper($payment->channel) . ')' : '-' }}</p>
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
                            <div class="bg-gray-50 border rounded-lg p-3 text-sm flex gap-4 items-center">
                                <div class="flex-1">
                                    <p class="text-gray-500 mb-1">Kurir: <strong class="text-gray-900">{{ $orderShop->latestShipment->courier_company }} ({{ $orderShop->latestShipment->courier_type }})</strong></p>
                                    <p class="text-gray-500">Resi: <strong class="text-gray-900">{{ $orderShop->latestShipment->courier_waybill_id ?? '-' }}</strong></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-gray-500 mb-2">Status: <strong class="text-gray-900">{{ $orderShop->latestShipment->status ?? 'Menunggu Resi' }}</strong></p>
                                    <flux:modal.trigger name="shipment-history-{{ $orderShop->id }}">
                                        <flux:button size="sm" variant="outline" class="text-xs">Lihat History</flux:button>
                                    </flux:modal.trigger>
                                </div>
                            </div>
                            
                            <flux:modal name="shipment-history-{{ $orderShop->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">History Pengiriman</flux:heading>
                                        <flux:subheading>Detail status pengiriman paket.</flux:subheading>
                                    </div>
                                    
                                    <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                                        @foreach($orderShop->shipments->sortByDesc('created_at') as $shipment)
                                            <div class="border-l-2 border-primary-500 pl-4 relative">
                                                <div class="absolute w-3 h-3 bg-primary-500 rounded-full -left-[7px] top-1"></div>
                                                <div class="text-sm font-bold text-gray-900">{{ $shipment->status }}</div>
                                                <div class="text-xs text-gray-500">{{ $shipment->created_at->format('d M Y, H:i') }}</div>
                                                @if($shipment->courier_driver_name)
                                                    <div class="text-sm text-gray-600 mt-2 bg-gray-50 rounded-lg p-3 border">
                                                        <div class="flex items-center gap-3">
                                                            @if($shipment->courier_driver_photo_url)
                                                                <img src="{{ $shipment->courier_driver_photo_url }}" alt="Driver Photo" class="w-10 h-10 rounded-full object-cover border bg-white">
                                                            @else
                                                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center border">
                                                                    <flux:icon.user class="w-5 h-5 text-gray-500" />
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <p class="font-bold text-gray-900">{{ $shipment->courier_driver_name }}</p>
                                                                <p class="text-xs">{{ $shipment->courier_driver_phone }} &bull; {{ $shipment->courier_driver_plate_number }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    <div class="flex">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Tutup</flux:button>
                                        </flux:modal.close>
                                    </div>
                                </div>
                            </flux:modal>
                        @endif

                        @foreach($orderShop->items as $item)
                            <div class="flex gap-4 border p-4 rounded-xl">
                                <div class="flex-1">
                                    <div class="flex gap-4">
                                        @if (blank($item->productFlat->getFirstMediaUrl('image_slot_0')))
                                            <div class="w-20 h-20 rounded-xl bg-gray-100 flex items-center justify-center border shrink-0">
                                                <flux:icon.photo class="w-8 h-8 text-gray-400" />
                                            </div>
                                        @else
                                            <img src="{{ $item->productFlat->getFirstMediaUrl('image_slot_0') }}" alt="Product" class="w-20 h-20 rounded-xl object-cover border shrink-0" />
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-gray-900 font-medium line-clamp-2 mb-1">
                                                {{ $item->product_data['name'] ?? 'Product Item' }}
                                            </h3>
                                            <div class="font-bold text-gray-900">
                                                Rp{{ numberToCurrency($item->price) }}
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-2"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 mb-1">Total Harga</p>
                                    <p class="font-bold text-gray-900">Rp{{ numberToCurrency($item->total) }}</p>
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
                    <span>Rp{{ numberToCurrency($order->total_checkout) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Total Ongkos Kirim</span>
                    <span>Rp{{ numberToCurrency($order->total_shipping) }}</span>
                </div>
                @if($order->insurance_fee > 0)
                    <div class="flex justify-between">
                        <span>Asuransi Pengiriman</span>
                        <span>Rp{{ numberToCurrency($order->insurance_fee) }}</span>
                    </div>
                @endif
                @if($order->application_fee > 0)
                    <div class="flex justify-between">
                        <span>Biaya Jasa Aplikasi</span>
                        <span>Rp{{ numberToCurrency($order->application_fee) }}</span>
                    </div>
                @endif
                @if($order->payment_fee > 0)
                    <div class="flex justify-between">
                        <span>Biaya Layanan Pembayaran</span>
                        <span>Rp{{ numberToCurrency($order->payment_fee) }}</span>
                    </div>
                @endif
                <div class="flex justify-between border-t pt-2 mt-2 font-bold text-gray-900">
                    <span>Total Belanja</span>
                    <span>Rp{{ numberToCurrency($order->total) }}</span>
                </div>
            </div>
        </div>

    </div>
</div>
