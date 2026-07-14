<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.item href="/orders" current wire:navigate>Daftar Transaksi</flux:navlist.item>
            <flux:navlist.item :href="route('account.profile')" wire:navigate>Pengaturan Akun</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading size="xl" class="mb-6">Daftar Transaksi</flux:heading>

        <!-- Tabs -->
        <div class="flex gap-6 border-b mb-6 overflow-x-auto hide-scrollbar">
            <button wire:click="setStatus('semua')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'semua' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Semua</button>
            <button wire:click="setStatus('berlangsung')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'berlangsung' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Berlangsung</button>
            <button wire:click="setStatus('berhasil')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'berhasil' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Berhasil</button>
            <button wire:click="setStatus('tidak-berhasil')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'tidak-berhasil' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Tidak Berhasil</button>
        </div>

        <div class="space-y-4">
            @forelse ($this->orders as $order)
                <div class="border rounded-xl shadow-sm bg-white overflow-hidden">
                    <div class="bg-gray-50 border-b p-3 flex justify-between items-center text-sm">
                        <div class="flex flex-wrap items-center gap-3">
                            <flux:icon.shopping-bag class="w-4 h-4 text-gray-400" />
                            <span class="font-bold text-gray-900">Belanja</span>
                            <span class="text-gray-400">{{ $order->created_at->format('d M Y') }}</span>
                            
                            @if ($order->status)
                                <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded">Berhasil</span>
                            @elseif ($order->latestPayment && $order->latestPayment->expired_at && $order->latestPayment->expired_at->isPast())
                                <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-0.5 rounded">Kedaluwarsa</span>
                            @else
                                <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2 py-0.5 rounded">Menunggu Pembayaran</span>
                            @endif

                            <span class="text-gray-400">{{ $order->reference }}</span>
                        </div>
                    </div>
                    
                    <div class="p-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                        <div class="flex-1">
                            @foreach ($order->orderShops as $orderShop)
                                <div class="mb-4 last:mb-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <flux:icon.building-storefront class="w-4 h-4 text-gray-500" />
                                        <span class="font-bold text-gray-900">{{ $orderShop->shop->name ?? 'Toko' }}</span>
                                    </div>
                                    @foreach ($orderShop->items as $item)
                                        <div class="ml-6 flex justify-between items-center mb-2">
                                            <div>
                                                <h3 class="font-bold text-gray-900 line-clamp-1 mb-1">{{ $item->name }}</h3>
                                                <p class="text-sm text-gray-500">{{ $item->qty }} barang x {{ numberToCurrency($item->price) }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        <div class="sm:text-right border-t sm:border-t-0 sm:border-l pt-3 sm:pt-0 sm:pl-4 w-full sm:w-auto mt-3 sm:mt-0 self-stretch flex flex-col justify-center">
                            <p class="text-sm text-gray-500 mb-1">Total Belanja</p>
                            <p class="font-bold text-gray-900">{{ numberToCurrency($order->total) }}</p>
                        </div>
                    </div>
                    
                    <div class="border-t p-3 flex justify-end gap-2 bg-gray-50">
                        <a href="{{ route('orders.detail', ['reference' => $order->reference]) }}" wire:navigate class="text-sm font-bold text-green-600 hover:text-green-700 border border-green-600 px-4 py-1.5 rounded-lg transition">Detail Transaksi</a>
                        @if (!$order->status && $order->latestPayment && (!$order->latestPayment->expired_at || $order->latestPayment->expired_at->isFuture()))
                            <a href="{{ route('payment.show', ['reference' => $order->reference]) }}" wire:navigate class="text-sm font-bold text-white bg-green-600 hover:bg-green-700 px-4 py-1.5 rounded-lg transition">Bayar Sekarang</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-12 border rounded-xl bg-gray-50">
                    <flux:icon.inbox class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-lg font-bold text-gray-900">Belum ada transaksi</h3>
                    <p class="text-gray-500 mt-1">Anda belum memiliki transaksi di kategori ini.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
