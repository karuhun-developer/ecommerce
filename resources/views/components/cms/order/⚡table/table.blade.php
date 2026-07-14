<div>
    <div class="flex items-center justify-between mt-5 mb-4 gap-4">
        <div class="flex gap-2 overflow-x-auto hide-scrollbar flex-1">
            <flux:button wire:click="setStatus('semua')" variant="{{ $status === 'semua' ? 'primary' : 'outline' }}" size="sm">Semua</flux:button>
            <flux:button wire:click="setStatus('menunggu-pembayaran')" variant="{{ $status === 'menunggu-pembayaran' ? 'primary' : 'outline' }}" size="sm">Menunggu Pembayaran</flux:button>
            <flux:button wire:click="setStatus('proses')" variant="{{ $status === 'proses' ? 'primary' : 'outline' }}" size="sm">Proses</flux:button>
            <flux:button wire:click="setStatus('dikirim')" variant="{{ $status === 'dikirim' ? 'primary' : 'outline' }}" size="sm">Dikirim</flux:button>
            <flux:button wire:click="setStatus('sampai')" variant="{{ $status === 'sampai' ? 'primary' : 'outline' }}" size="sm">Sampai</flux:button>
            <flux:button wire:click="setStatus('gagal')" variant="{{ $status === 'gagal' ? 'primary' : 'outline' }}" size="sm">Gagal</flux:button>
        </div>
    </div>

    <flux:table class="min-w-full">
        <flux:table.columns>
            <flux:table.column>Aksi</flux:table.column>
            <flux:table.column>Order Ref</flux:table.column>
            <flux:table.column>Tanggal</flux:table.column>
            <flux:table.column>Pelanggan</flux:table.column>
            @if(!isSingleShop())
                <flux:table.column>Toko</flux:table.column>
            @endif
            <flux:table.column>Total</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->orders as $orderShop)
                @php
                    $order = $orderShop->order;
                @endphp
                <flux:table.row>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button icon:trailing="chevron-down" size="sm">Opsi</flux:button>
                            <flux:menu>
                                <flux:menu.item
                                    icon="eye"
                                    href="{{ route('cms.order.show', ['id' => $orderShop->id]) }}"
                                    wire:navigate>
                                    Detail
                                </flux:menu.item>
                                
                                @if ($order->status && !$orderShop->shipping_status)
                                    <flux:menu.item
                                        icon="truck"
                                        @click="$wire.dispatch('confirm', {
                                            function: 'kirimPesanan',
                                            id: '{{ $orderShop->id }}'
                                        })">
                                        Kirim Pesanan
                                    </flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <span class="font-medium text-gray-900 dark:text-zinc-100">{{ $order->reference }}</span>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        {{ $order->created_at->format('d M Y, H:i') }}
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        {{ $order->user->name ?? ($order->guest_data['name'] ?? 'Guest') }}
                    </flux:table.cell>
                    
                    @if(!isSingleShop())
                        <flux:table.cell>
                            {{ $orderShop->shop->name ?? 'Toko' }}
                        </flux:table.cell>
                    @endif
                    
                    <flux:table.cell>
                        {{ numberToCurrency($orderShop->total) }}
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        @if ($order->status && $orderShop->shipping_status)
                            <flux:badge color="blue" size="sm">Dikirim</flux:badge>
                        @elseif ($order->status && !$orderShop->shipping_status)
                            <flux:badge color="indigo" size="sm">Proses</flux:badge>
                        @elseif (!$order->status && $order->latestPayment && $order->latestPayment->expired_at && $order->latestPayment->expired_at->isPast())
                            <flux:badge color="red" size="sm">Gagal</flux:badge>
                        @else
                            <flux:badge color="orange" size="sm">Menunggu Pembayaran</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="999" align="center" variant="strong">
                        Belum ada pesanan dengan status ini.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
