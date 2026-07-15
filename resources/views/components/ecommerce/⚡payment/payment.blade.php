<div x-data="{
    paymentMethod: $wire.entangle('paymentMethod'),
    baseAmount: {{ (float) $order->total }},
    get fee() {
        if (!this.paymentMethod) return 0;
        return this.paymentMethod === 'qris' ? Math.round(this.baseAmount * 0.007) : 4500;
    },
    get total() {
        return this.baseAmount + this.fee;
    },
}" class="w-full">
    <div class="mb-6">
        <a href="{{ route('orders.detail', ['reference' => $order->reference]) }}" class="text-gray-600 hover:text-gray-700 font-bold text-sm flex items-center gap-2" wire:navigate>
            <flux:icon.arrow-left class="w-4 h-4" /> Kembali ke Detail Pesanan
        </a>
    </div>

    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Transaksi #{{ $order->reference }}</flux:heading>
        @if($isPaid)
            <flux:badge color="green" size="sm">Dibayar</flux:badge>
        @else
            <flux:badge color="yellow" size="sm">Menunggu Pembayaran</flux:badge>
        @endif
    </div>

    <!-- If there is a payment, and it's active -->
    @if($payment && $payment->expired_at->isFuture())
        <flux:card class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5 pb-5 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <flux:heading size="lg">Selesaikan Pembayaran</flux:heading>
                    <div class="text-sm text-gray-500 mt-1">
                        Silakan selesaikan pembayaran anda sebelum pesanan anda kadaluarsa.
                    </div>
                </div>
                @if(!$isPaid)
                <div x-data="{
                    expiredAt: new Date('{{ $payment->expired_at->toIso8601String() }}').getTime(),
                    now: new Date().getTime(),
                    timeLeft: '',
                    init() {
                        this.updateTimer();
                        setInterval(() => this.updateTimer(), 1000);
                    },
                    updateTimer() {
                        this.now = new Date().getTime();
                        const distance = this.expiredAt - this.now;

                        if (distance < 0) {
                            this.timeLeft = 'EXPIRED';
                            return;
                        }

                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        this.timeLeft = hours + 'h ' + minutes + 'm ' + seconds + 's ';
                    }
                }" class="px-4 py-2 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-xl text-center border border-rose-100 dark:border-rose-800 shrink-0">
                    <div class="text-xs font-semibold mb-0.5 uppercase tracking-wider">Waktu Tersisa</div>
                    <div class="text-lg font-bold tabular-nums" x-text="timeLeft"></div>
                </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wider">Transaction ID</span>
                    <div class="font-medium mt-1">{{ $payment->order_id }}</div>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wider">Payment Type</span>
                    <div class="font-medium uppercase mt-1">{{ str_replace('_', ' ', $payment->payment_type) }} ({{ $payment->channel }})</div>
                </div>
                
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 pt-5 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wider">Account / VA Number</span>
                        <div class="font-medium mt-1">
                            @if($payment->payment_type === 'qris')
                                <div class="flex flex-col items-start gap-2">
                                    <div class="p-2 bg-white inline-block rounded-xl shadow-sm border border-gray-200">
                                        <img src="{{ $payment->account_number }}" alt="QRIS Barcode" class="w-32 h-32 object-contain">
                                    </div>
                                    <flux:button size="sm" icon="arrow-down-tray" href="{{ $payment->account_number }}" download="QRIS-Payment-{{ $payment->order_id }}.png" target="_blank">
                                        Download QR
                                    </flux:button>
                                </div>
                            @else
                                <div class="flex items-center gap-2" x-data="{ copied: false }">
                                    <span class="text-lg font-bold">{{ $payment->account_number }}</span>
                                    <flux:button size="sm" variant="subtle" icon="document-duplicate" x-on:click="
                                        navigator.clipboard.writeText('{{ $payment->account_number }}');
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    ">
                                        <span x-show="!copied">Copy VA</span>
                                        <span x-show="copied" class="text-gray-600">Copied!</span>
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wider">Amount to Pay</span>
                        <div class="font-medium mt-1">
                            <div class="flex items-center gap-2" x-data="{ copied: false }">
                                <span class="text-2xl font-bold text-gray-600 dark:text-indigo-400">Rp {{ number_format($payment->total, 0, ',', '.') }}</span>
                                <flux:button size="sm" variant="subtle" icon="document-duplicate" x-on:click="
                                    navigator.clipboard.writeText('{{ $payment->total }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                ">
                                    <span x-show="!copied">Copy Amount</span>
                                    <span x-show="copied" class="text-gray-600">Copied!</span>
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg flex items-start gap-3 text-amber-800 dark:text-amber-300">
                <flux:icon.exclamation-triangle class="w-5 h-5 shrink-0" />
                <div class="text-xs mt-0.5">
                    <strong>Penting:</strong> Pastikan Anda membayar dengan nominal yang sesuai, yaitu <strong>Rp {{ number_format($payment->total, 0, ',', '.') }}</strong> hingga digit terakhir. Perbedaan jumlah transfer dapat menyebabkan kegagalan verifikasi pembayaran.
                </div>
            </div>
        </flux:card>
    @endif

    <form wire:submit.prevent="submit" class="space-y-8">
        <flux:heading size="xl">Metode Pembayaran</flux:heading>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <!-- QRIS -->
            <label class="cursor-pointer relative rounded-xl border p-4 flex flex-col items-center justify-center gap-4 transition-all hover:border-indigo-500 hover:shadow-md"
                :class="paymentMethod === 'qris' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 ring-1 ring-indigo-600' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900'">
                <input type="radio" wire:model="paymentMethod" value="qris" class="sr-only">
                <img src="{{ asset('payment-icon/QRIS.svg') }}" alt="QRIS" class="h-8">
                <span class="font-medium text-center">QRIS</span>
            </label>

            <!-- BCA -->
            <label class="cursor-pointer relative rounded-xl border p-4 flex flex-col items-center justify-center gap-4 transition-all hover:border-indigo-500 hover:shadow-md"
                :class="paymentMethod === 'bca' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 ring-1 ring-indigo-600' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900'">
                <input type="radio" wire:model="paymentMethod" value="bca" class="sr-only">
                <img src="{{ asset('payment-icon/BCA.svg') }}" alt="BCA" class="h-8">
                <span class="font-medium text-center text-sm">Virtual Account BCA</span>
            </label>

            <!-- BNI -->
            <label class="cursor-pointer relative rounded-xl border p-4 flex flex-col items-center justify-center gap-4 transition-all hover:border-indigo-500 hover:shadow-md"
                :class="paymentMethod === 'bni' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 ring-1 ring-indigo-600' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900'">
                <input type="radio" wire:model="paymentMethod" value="bni" class="sr-only">
                <img src="{{ asset('payment-icon/BNI.svg') }}" alt="BNI" class="h-8">
                <span class="font-medium text-center text-sm">Virtual Account BNI</span>
            </label>

            <!-- BRI -->
            <label class="cursor-pointer relative rounded-xl border p-4 flex flex-col items-center justify-center gap-4 transition-all hover:border-indigo-500 hover:shadow-md"
                :class="paymentMethod === 'bri' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 ring-1 ring-indigo-600' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900'">
                <input type="radio" wire:model="paymentMethod" value="bri" class="sr-only">
                <img src="{{ asset('payment-icon/BRI.svg') }}" alt="BRI" class="h-8">
                <span class="font-medium text-center text-sm">Virtual Account BRI</span>
            </label>
        </div>

        <flux:error name="paymentMethod" />

        <flux:card>
            <div class="mb-4">
                <flux:heading size="lg">Ringkasan Pembayaran</flux:heading>
                <flux:text>
                    Reference: <strong class="text-gray-900 dark:text-white">{{ $order->reference }}</strong>
                </flux:text>
            </div>
            <div class="space-y-6">
                @foreach($order->orderShops as $orderShop)
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <flux:icon.building-storefront class="w-4 h-4 text-gray-500" />
                            <span class="font-bold text-gray-900 dark:text-white text-sm">{{ $orderShop->shop->name ?? 'Toko' }}</span>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700 border-t border-gray-200 dark:border-gray-700">
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
                    </div>
                @endforeach
            </div>

            <div class="space-y-3 pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                @if($order->total_shipping > 0)
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm">
                    <span>Total Ongkos Kirim</span>
                    <span>Rp {{ number_format($order->total_shipping, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($order->application_fee > 0)
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm">
                    <span>Biaya Jasa Aplikasi</span>
                    <span>Rp {{ number_format($order->application_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($order->insurance_fee > 0)
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm">
                    <span>Asuransi Pengiriman</span>
                    <span>Rp {{ number_format($order->insurance_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Subtotal</span>
                    <span x-text="'Rp ' + window.numberToCurrency(baseAmount)"></span>
                </div>
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm">
                    <span>Biaya Layanan Pembayaran</span>
                    <span x-text="'Rp ' + window.numberToCurrency(fee)"></span>
                </div>
                <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center font-bold text-lg">
                    <span>Total Tagihan</span>
                    <span x-text="'Rp ' + window.numberToCurrency(total)"></span>
                </div>
            </div>
        </flux:card>

        @if (! $isPaid)
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" x-bind:disabled="!paymentMethod">
                    <span wire:loading.remove>
                        {{ $payment ? 'Change Payment Method' : 'Proceed to Payment' }}
                    </span>
                    <span wire:loading>Processing...</span>
                </flux:button>
            </div>
        @endif
    </form>
</div>