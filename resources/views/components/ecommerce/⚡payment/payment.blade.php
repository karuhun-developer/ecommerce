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
    formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
    }
}" class="w-full">

    <div class="mb-6">
        <a href="{{ route('orders.detail', ['reference' => $order->reference]) }}" class="text-green-600 hover:text-green-700 font-bold text-sm flex items-center gap-2" wire:navigate>
            <flux:icon.arrow-left class="w-4 h-4" /> Kembali ke Detail Pesanan
        </a>
    </div>

    <flux:heading size="xl" class="mb-6">Payment Method</flux:heading>
    
    <!-- If there is a payment, and it's active -->
    @if($payment && $payment->expired_at->isFuture())
        <flux:card class="mb-8 space-y-4">
            <div>
                <flux:heading size="lg">Complete Your Payment</flux:heading>
                <flux:text>Please complete your payment before the timer expires.</flux:text>
            </div>
            
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
            }">
                <div class="text-2xl font-bold" x-text="timeLeft"></div>
            </div>
            
            <div class="space-y-2">
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-sm">Transaction ID</span>
                    <div class="font-medium">{{ $payment->order_id }}</div>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-sm">Payment Type</span>
                    <div class="font-medium uppercase">{{ str_replace('_', ' ', $payment->payment_type) }} ({{ $payment->channel }})</div>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-sm">Account / VA Number</span>
                    <div class="font-medium">
                        @if($payment->payment_type === 'qris')
                            <div class="mt-2 p-2 bg-white inline-block rounded-lg shadow-sm border border-gray-200">
                                <img src="{{ $payment->account_number }}" alt="QRIS Barcode" class="w-48 h-48 object-contain">
                            </div>
                        @else
                            {{ $payment->account_number }}
                        @endif
                    </div>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-sm">Amount to Pay</span>
                    <div class="font-medium text-lg">Rp {{ number_format($payment->total, 0, ',', '.') }}</div>
                </div>
            </div>
        </flux:card>
    @endif

    <form wire:submit.prevent="submit" class="space-y-8">
        
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
                <flux:heading size="lg">Order & Payment Summary</flux:heading>
                <flux:text>Reference: <strong class="text-gray-900 dark:text-white">{{ $order->reference }}</strong></flux:text>
            </div>
            
            <div class="space-y-6">
                @foreach($order->orderShops as $orderShop)
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <flux:icon.building-storefront class="w-4 h-4 text-gray-500" />
                            <span class="font-bold text-gray-900 dark:text-white text-sm">{{ $orderShop->shop->name ?? 'Toko' }}</span>
                        </div>
                        
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700 border-t border-gray-200 dark:border-gray-700">
                            @foreach($orderShop->items as $item)
                                <li class="py-3 flex justify-between items-start">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $item->product_data['name'] ?? 'Product Item' }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        Rp {{ number_format($item->total, 0, ',', '.') }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            
            <div class="space-y-3 pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                @if($order->total_shipping > 0)
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm">
                    <span>Shipping</span>
                    <span>Rp {{ number_format($order->total_shipping, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($order->application_fee > 0)
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm">
                    <span>Application Fee</span>
                    <span>Rp {{ number_format($order->application_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($order->insurance_fee > 0)
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm">
                    <span>Insurance Fee</span>
                    <span>Rp {{ number_format($order->insurance_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Subtotal</span>
                    <span x-text="formatRupiah(baseAmount)"></span>
                </div>
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400 text-sm">
                    <span>Payment Fee</span>
                    <span x-text="formatRupiah(fee)"></span>
                </div>
                <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center font-bold text-lg">
                    <span>Total Tagihan</span>
                    <span x-text="formatRupiah(total)"></span>
                </div>
            </div>
        </flux:card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" x-bind:disabled="!paymentMethod">
                <span wire:loading.remove>
                    {{ $payment ? 'Change Payment Method' : 'Proceed to Payment' }}
                </span>
                <span wire:loading>Processing...</span>
            </flux:button>
        </div>
    </form>
</div>