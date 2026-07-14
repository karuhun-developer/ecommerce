<div>
    <!-- Tabs -->
    <div class="flex gap-6 border-b mb-6 overflow-x-auto hide-scrollbar">
        <button wire:click="setStatus('semua')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'semua' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Semua</button>
        <button wire:click="setStatus('menunggu-pembayaran')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'menunggu-pembayaran' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Menunggu Pembayaran</button>
        <button wire:click="setStatus('proses')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'proses' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Proses</button>
        <button wire:click="setStatus('dikirim')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'dikirim' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Dikirim</button>
        <button wire:click="setStatus('sampai')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'sampai' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Sampai</button>
        <button wire:click="setStatus('gagal')" class="pb-3 text-sm font-bold whitespace-nowrap {{ $status === 'gagal' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600' }}">Gagal</button>
    </div>

    <div class="space-y-4">
        @forelse ($this->orders as $orderShop)
            @php
                $order = $orderShop->order;
            @endphp
            <div class="border rounded-xl shadow-sm bg-white overflow-hidden dark:bg-zinc-900 dark:border-zinc-800">
                <div class="bg-gray-50 dark:bg-zinc-800/50 border-b dark:border-zinc-800 p-3 flex justify-between items-center text-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:icon.shopping-bag class="w-4 h-4 text-gray-400" />
                        <span class="font-bold text-gray-900 dark:text-zinc-100">Pesanan</span>
                        <span class="text-gray-400">{{ $order->created_at->format('d M Y, H:i') }}</span>
                        
                        @if ($order->status && $orderShop->shipping_status)
                            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded">Dikirim</span>
                        @elseif ($order->status && !$orderShop->shipping_status)
                            <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded">Proses</span>
                        @elseif (!$order->status && $order->latestPayment && $order->latestPayment->expired_at && $order->latestPayment->expired_at->isPast())
                            <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-0.5 rounded">Gagal</span>
                        @else
                            <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2 py-0.5 rounded">Menunggu Pembayaran</span>
                        @endif

                        <span class="text-gray-400">{{ $order->reference }}</span>
                    </div>
                    
                    @if(!isSingleShop())
                    <div class="text-sm font-semibold text-gray-700 dark:text-zinc-300">
                        {{ $orderShop->shop->name ?? 'Toko' }}
                    </div>
                    @endif
                </div>
                
                <div class="p-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <flux:icon.user class="w-4 h-4 text-gray-500" />
                            <span class="font-bold text-gray-900 dark:text-zinc-100">
                                {{ $order->user->name ?? ($order->guest_data['name'] ?? 'Guest') }}
                            </span>
                        </div>
                        @foreach ($orderShop->items as $item)
                            <div class="ml-6 flex justify-between items-center mb-2">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-zinc-100 line-clamp-1 mb-1">{{ $item->name }}</h3>
                                    <p class="text-sm text-gray-500">{{ $item->qty }} barang x {{ numberToCurrency($item->price) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="sm:text-right border-t sm:border-t-0 sm:border-l dark:border-zinc-800 pt-3 sm:pt-0 sm:pl-4 w-full sm:w-auto mt-3 sm:mt-0 self-stretch flex flex-col justify-center">
                        <p class="text-sm text-gray-500 mb-1">Total Pesanan</p>
                        <p class="font-bold text-gray-900 dark:text-zinc-100">{{ numberToCurrency($orderShop->total) }}</p>
                    </div>
                </div>
                
                <div class="border-t dark:border-zinc-800 p-3 flex justify-end gap-2 bg-gray-50 dark:bg-zinc-800/50">
                    <a href="{{ route('cms.order.show', ['reference' => $order->reference]) }}" wire:navigate class="text-sm font-bold text-green-600 hover:text-green-700 border border-green-600 px-4 py-1.5 rounded-lg transition">Detail Transaksi</a>
                    
                    @if ($order->status && !$orderShop->shipping_status)
                        <button wire:click="kirimPesanan({{ $orderShop->id }})" class="text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-1.5 rounded-lg transition">
                            Kirim Pesanan
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12 border dark:border-zinc-800 rounded-xl bg-gray-50 dark:bg-zinc-800/50">
                <flux:icon.inbox class="w-12 h-12 text-gray-300 dark:text-zinc-600 mx-auto mb-4" />
                <h3 class="text-lg font-bold text-gray-900 dark:text-zinc-100">Belum ada pesanan</h3>
                <p class="text-gray-500 mt-1">Anda belum memiliki pesanan dengan status ini.</p>
            </div>
        @endforelse
    </div>
</div>
