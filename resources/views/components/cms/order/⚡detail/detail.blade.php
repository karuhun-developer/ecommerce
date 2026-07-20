<div>
    @php
        $order = $this->orderShop->order;
        $orderShop = $this->orderShop;
        $payment = $order->latestPayment;
        $isPaid = $order->status;
    @endphp
    
    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6 mb-6">
        <div class="flex justify-between items-start border-b dark:border-zinc-800 pb-4 mb-4">
            <div>
                <flux:heading size="lg" class="mb-1">Status Transaksi</flux:heading>
                <p class="text-sm text-gray-500">{{ $order->reference }}</p>
            </div>
            @if ($isPaid && $orderShop->shipping_status && $orderShop->waybill_number)
                <flux:badge color="green" size="sm">Sampai</flux:badge>
            @elseif ($isPaid && !$orderShop->shipping_status && $orderShop->waybill_number)
                <flux:badge color="blue" size="sm">Dikirim</flux:badge>
            @elseif ($isPaid && !$orderShop->shipping_status && !$orderShop->waybill_number)
                <flux:badge color="indigo" size="sm">Proses</flux:badge>
            @elseif (!$isPaid && $payment && $payment->expired_at && $payment->expired_at->isPast())
                <flux:badge color="red" size="sm">Gagal / Kedaluwarsa</flux:badge>
            @else
                <flux:badge color="orange" size="sm">Menunggu Pembayaran</flux:badge>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
            <div>
                <flux:heading size="md" class="mb-3">Info Pelanggan & Pengiriman</flux:heading>
                <div class="text-sm text-gray-600 dark:text-zinc-400 space-y-1">
                    @if($order->guest_data)
                        <p class="font-bold text-gray-900 dark:text-zinc-100">
                            {{ $order->guest_data['contact_name'] ?? '' }}
                        </p>
                        <p>{{ $order->guest_data['contact_phone'] ?? '' }}</p>
                        <p>{{ $order->guest_data['contact_email'] ?? '' }}</p>
                        <p class="mt-2">
                            {{ $order->guest_data['area_string'] ?? '' }}<br>
                            {{ $order->guest_data['address'] ?? '' }} 
                            @if(!empty($order->guest_data['note']))
                                [{{ $order->guest_data['note'] }}]
                            @endif
                        </p>
                    @else
                        <p class="font-bold text-gray-900 dark:text-zinc-100">{{ $order->location->contact_name ?? ($order->user->name ?? 'Guest') }}</p>
                        <p>{{ $order->location->contact_phone ?? '' }}</p>
                        <p>{{ $order->location->contact_email ?? ($order->user->email ?? '') }}</p>
                        @if($order->location)
                        <p class="mt-2">
                            {{ $order->location->area_string ?? '' }}<br>
                            {{ $order->location->address ?? '' }} 
                            @if(!empty($order->location->note))
                                [{{ $order->location->note }}]
                            @endif
                        </p>
                        @endif
                    @endif
                </div>
            </div>
            <div>
                <flux:heading size="md" class="mb-3">Info Pembayaran (Keseluruhan Transaksi)</flux:heading>
                <div class="text-sm text-gray-600 dark:text-zinc-400 space-y-1">
                    <p><span class="w-32 inline-block text-gray-500">Metode Bayar</span>: {{ $payment ? strtoupper($payment->payment_type) . ' (' . strtoupper($payment->channel) . ')' : '-' }}</p>
                </div>
            </div>
        </div>

        <div class="border-t dark:border-zinc-800 pt-4">
            <flux:heading size="md" class="mb-4">Rincian Produk Toko Anda</flux:heading>
            <div class="space-y-6">
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.building-storefront class="w-5 h-5 text-gray-500" />
                        <span class="font-bold text-gray-900 dark:text-zinc-100 text-sm">{{ $orderShop->shop->name ?? 'Toko' }}</span>
                    </div>

                    @if($orderShop->shipping_note)
                        <div class="bg-red-50 text-red-700 p-4 rounded-xl text-sm border border-red-200">
                            <strong>Catatan Pengiriman:</strong> {{ $orderShop->shipping_note }}
                        </div>
                    @endif

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
                        <div class="flex gap-4 border dark:border-zinc-800 p-4 rounded-xl">
                            <div class="flex-1">
                                <div class="flex gap-4">
                                    @if ($item->productFlat && $item->productFlat->getFirstMediaUrl('image_slot_0'))
                                        <img src="{{ $item->productFlat->getFirstMediaUrl('image_slot_0') }}" alt="Product" class="w-20 h-20 rounded-xl object-cover border dark:border-zinc-700 shrink-0" />
                                    @else
                                        <div class="w-20 h-20 rounded-xl bg-gray-100 dark:bg-zinc-800 flex items-center justify-center border dark:border-zinc-700 shrink-0">
                                            <flux:icon.photo class="w-8 h-8 text-gray-400" />
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-gray-900 dark:text-zinc-100 font-medium line-clamp-2 mb-1">
                                            {{ $item->product_data['name'] ?? $item->name }}
                                        </h3>
                                        <div class="font-bold text-gray-900 dark:text-zinc-100">
                                            Rp{{ numberToCurrency($item->price) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 mb-1">Kuantitas</p>
                                <p class="font-bold text-gray-900 dark:text-zinc-100 mb-3">{{ $item->qty }}</p>
                                
                                <p class="text-sm text-gray-500 mb-1">Total Harga</p>
                                <p class="font-bold text-gray-900 dark:text-zinc-100">Rp{{ numberToCurrency($item->total) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="border-t dark:border-zinc-800 pt-4 mt-6">
            <flux:heading size="md" class="mb-3">Rincian Total Toko</flux:heading>
            <div class="space-y-2 text-sm text-gray-600 dark:text-zinc-400 max-w-sm ml-auto">
                <div class="flex justify-between border-t dark:border-zinc-800 pt-2 mt-2 font-bold text-gray-900 dark:text-zinc-100">
                    <span>Total Pembayaran (Toko Ini)</span>
                    <span>Rp{{ numberToCurrency($orderShop->total) }}</span>
                </div>
            </div>
        </div>
        
        @if ($isPaid && !$orderShop->shipping_status && !$orderShop->waybill_number)
        <div class="border-t dark:border-zinc-800 pt-6 mt-6 flex justify-end">
            <flux:button 
                @click="$wire.dispatch('confirm', {
                    function: 'kirimPesanan',
                    id: '{{ $orderShop->id }}',
                })" 
                variant="primary" 
                icon="truck">
                Kirim Pesanan Sekarang
            </flux:button>
        </div>
        @endif
    </div>
</div>
