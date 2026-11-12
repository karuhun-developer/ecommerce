<div
    class="w-full {{ $this->canSubmitPayment() ? 'pb-28 lg:pb-8' : 'pb-8' }}"
    @if ($this->isPaymentPending()) wire:poll.15s="refreshPaymentStatus" @endif
>
    <div class="mb-6">
        @if ($order->user_id === null)
            <button type="button" class="flex items-center gap-2 text-sm font-bold text-gray-600 transition hover:text-gray-900 dark:text-gray-300 dark:hover:text-white" x-on:click="history.back()">
                <flux:icon.arrow-left class="size-4" />
                Kembali ke Detail Pesanan
            </button>
        @else
            <a href="{{ route('orders.detail', ['reference' => $order->reference]) }}" class="flex items-center gap-2 text-sm font-bold text-gray-600 transition hover:text-gray-900 dark:text-gray-300 dark:hover:text-white" wire:navigate>
                <flux:icon.arrow-left class="size-4" />
                Kembali ke Detail Pesanan
            </a>
        @endif
    </div>

    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            @if ($this->canSubmitPayment())
                <flux:heading size="xl">Pembayaran #{{ $order->reference }}</flux:heading>
                <flux:text class="mt-1">Pilih metode yang tersedia dan selesaikan pembayaran dengan aman.</flux:text>
            @elseif ($this->paymentStatus() === 'paid')
                <flux:heading size="xl">Pembayaran selesai #{{ $order->reference }}</flux:heading>
                <flux:text class="mt-1">Pembayaran telah dikonfirmasi dan pesanan sedang diproses.</flux:text>
            @elseif ($this->paymentStatus() === 'expired')
                <flux:heading size="xl">Pembayaran berakhir #{{ $order->reference }}</flux:heading>
                <flux:text class="mt-1">Transaksi tidak dapat dilanjutkan. Lihat detail pesanan untuk langkah berikutnya.</flux:text>
            @else
                <flux:heading size="xl">Instruksi pembayaran #{{ $order->reference }}</flux:heading>
                <flux:text class="mt-1">Ikuti instruksi pembayaran dan selesaikan transaksi sebelum batas waktu.</flux:text>
            @endif
        </div>

        @if ($payment && filled($payment->transaction_id))
            @if ($this->paymentStatus() === 'paid')
                <flux:badge color="green" size="sm" icon="check-circle">Dibayar</flux:badge>
            @elseif ($this->paymentStatus() === 'expired')
                <flux:badge color="red" size="sm" icon="x-circle">Gagal atau kedaluwarsa</flux:badge>
            @else
                <flux:badge color="yellow" size="sm" icon="clock">Menunggu pembayaran</flux:badge>
            @endif
        @endif
    </header>

    @if ($this->canSubmitPayment())
        @php
            $selectedMethod = $this->selectedPaymentMethod();
        @endphp

        <form wire:submit="submit" data-payment-layout class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <flux:card data-payment-methods class="space-y-6">
                <div>
                    <flux:heading size="lg">Pilih metode pembayaran</flux:heading>
                    <flux:text class="mt-1">Biaya di bawah ini berasal dari penyedia pembayaran aktif.</flux:text>
                </div>

                @if ($paymentMethods === [])
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                        Metode pembayaran sedang tidak tersedia. Muat ulang halaman atau coba beberapa saat lagi.
                    </div>
                @else
                    <fieldset class="grid gap-3 sm:grid-cols-2">
                        <legend class="sr-only">Metode pembayaran yang tersedia</legend>

                        @foreach ($paymentMethods as $method)
                            <label
                                wire:key="payment-method-{{ $method['providerCode'] }}"
                                @class([
                                    'relative flex min-h-32 cursor-pointer flex-col justify-between gap-4 rounded-xl border p-4 transition focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 dark:focus-within:ring-offset-gray-900',
                                    'border-indigo-600 bg-indigo-50 ring-1 ring-indigo-600 dark:bg-indigo-950/40' => $paymentMethod === $method['providerCode'],
                                    'border-gray-200 bg-white hover:border-indigo-400 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900' => $paymentMethod !== $method['providerCode'] && $method['isAvailable'],
                                    'cursor-not-allowed border-gray-200 bg-gray-100 opacity-60 dark:border-gray-800 dark:bg-gray-950' => ! $method['isAvailable'],
                                ])
                            >
                                <input
                                    type="radio"
                                    name="payment_method"
                                    class="sr-only"
                                    wire:model.live="paymentMethod"
                                    value="{{ $method['providerCode'] }}"
                                    @disabled(! $method['isAvailable'])
                                >

                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex h-10 min-w-20 items-center">
                                        @if ($method['icon'])
                                            <img src="{{ asset($method['icon']) }}" alt="{{ $method['name'] }}" class="max-h-9 max-w-24 object-contain">
                                        @else
                                            <span data-payment-icon-fallback class="inline-flex size-10 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                                <flux:icon.credit-card class="size-5" />
                                            </span>
                                        @endif
                                    </div>

                                    @if ($paymentMethod === $method['providerCode'])
                                        <flux:icon.check-circle class="size-5 text-indigo-600 dark:text-indigo-400" />
                                    @endif
                                </div>

                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $method['name'] }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        @if ($method['isAvailable'])
                                            Estimasi biaya Rp {{ number_format($method['estimatedFee'], 0, ',', '.') }}
                                        @else
                                            Tidak tersedia untuk nominal pesanan ini
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </fieldset>
                @endif

                <flux:error name="paymentMethod" />

                <div data-payment-primary-cta class="hidden border-t border-gray-200 pt-5 dark:border-gray-700 lg:block">
                    <flux:button type="submit" variant="primary" class="w-full" :disabled="$selectedMethod === null || ! $selectedMethod['isAvailable']">
                        <span wire:loading.remove wire:target="submit">Lanjutkan pembayaran</span>
                        <span wire:loading wire:target="submit">Memproses...</span>
                    </flux:button>
                </div>
            </flux:card>

            <aside data-payment-summary class="lg:sticky lg:top-24">
                <flux:card>
                    <flux:heading size="lg">Ringkasan pembayaran</flux:heading>
                    <flux:text class="mt-1">Referensi {{ $order->reference }}</flux:text>

                    <dl class="mt-6 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4 text-gray-600 dark:text-gray-300">
                            <dt>Subtotal pesanan</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 text-gray-600 dark:text-gray-300">
                            <dt>Estimasi biaya pembayaran</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($selectedMethod['estimatedFee'] ?? 0, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-gray-200 pt-4 text-base font-bold text-gray-900 dark:border-gray-700 dark:text-white">
                            <dt>Estimasi total</dt>
                            <dd>Rp {{ number_format($selectedMethod['estimatedTotal'] ?? (float) $order->total, 0, ',', '.') }}</dd>
                        </div>
                    </dl>

                    <p class="mt-4 text-xs leading-5 text-gray-500 dark:text-gray-400">Total final mengikuti nilai yang disimpan setelah transaksi berhasil dibuat.</p>
                </flux:card>
            </aside>

            <div data-payment-mobile-cta class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 p-4 shadow-[0_-8px_24px_rgba(0,0,0,0.08)] backdrop-blur dark:border-gray-700 dark:bg-gray-900/95 lg:hidden">
                <div class="mx-auto max-w-7xl">
                    <flux:button type="submit" variant="primary" class="w-full" :disabled="$selectedMethod === null || ! $selectedMethod['isAvailable']">
                        <span wire:loading.remove wire:target="submit">Lanjutkan · Rp {{ number_format($selectedMethod['estimatedTotal'] ?? (float) $order->total, 0, ',', '.') }}</span>
                        <span wire:loading wire:target="submit">Memproses...</span>
                    </flux:button>
                </div>
            </div>
        </form>
    @else
        @php
            $isPaymentPending = $this->isPaymentPending();
        @endphp

        @if ($isPaymentPending)
            @php
                $destinationUrl = $this->paymentDestinationUrl();
                $isQris = $payment->payment_type === 'qris';
                $qrisDataUri = $this->qrisDataUri();
                $isHostedPayment = ! $isQris && $destinationUrl !== null;
            @endphp
        @endif

        @php
            $paymentStatus = $this->paymentStatus();
        @endphp

        <div data-payment-layout class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <main
                class="space-y-6"
                @if ($isPaymentPending)
                x-data="{
                    copyState: 'idle',
                    copyTarget: '',
                    copyMessage: '',
                    copyTimer: null,
                    async copy(value, target) {
                        clearTimeout(this.copyTimer);
                        this.copyState = 'copying';
                        this.copyTarget = target;
                        this.copyMessage = `Menyalin ${target}...`;

                        try {
                            await navigator.clipboard.writeText(value);
                            this.copyState = 'success';
                            this.copyMessage = `${target.charAt(0).toUpperCase()}${target.slice(1)} berhasil disalin.`;
                        } catch (error) {
                            this.copyState = 'error';
                            this.copyMessage = `Gagal menyalin ${target}. Silakan salin secara manual.`;
                        }

                        this.copyTimer = setTimeout(() => {
                            this.copyState = 'idle';
                            this.copyTarget = '';
                            this.copyMessage = '';
                        }, 3000);
                    },
                }"
                @endif
            >
                @if ($isPaymentPending)
                    <flux:card data-payment-instructions class="space-y-6">
                    <div>
                        <flux:heading size="lg">Selesaikan pembayaran sekarang</flux:heading>
                        <flux:text class="mt-1">Ikuti petunjuk utama di bawah ini. Status akan diperbarui otomatis selama pembayaran masih menunggu.</flux:text>
                    </div>

                    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total yang perlu dibayar</div>
                            <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format((float) $payment->total, 0, ',', '.') }}</div>
                        </div>
                        <flux:button data-payment-total-copy type="button" size="sm" variant="outline" icon="document-duplicate" aria-label="Salin total pembayaran" x-bind:aria-label="copyState === 'success' && copyTarget === 'total pembayaran' ? 'Total pembayaran tersalin' : 'Salin total pembayaran'" data-copy-value="{{ (int) round((float) $payment->total) }}" x-on:click="copy($el.dataset.copyValue, 'total pembayaran')">
                            <span x-show="copyState !== 'success' || copyTarget !== 'total pembayaran'">Salin total</span>
                            <span x-cloak x-show="copyState === 'success' && copyTarget === 'total pembayaran'">Tersalin</span>
                        </flux:button>
                    </div>

                    @if ($isQris && $qrisDataUri)
                        <div data-payment-qr class="grid items-center gap-6 sm:grid-cols-[12rem_1fr]">
                            <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700">
                                <img src="{{ $qrisDataUri }}" alt="Kode QR pembayaran {{ $order->reference }}" class="aspect-square w-full object-contain">
                            </div>
                            <div class="space-y-4">
                                <ol class="list-decimal space-y-2 pl-5 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    <li>Buka aplikasi bank atau dompet digital yang mendukung QRIS.</li>
                                    <li>Pindai kode QR dan pastikan total pembayaran sesuai.</li>
                                    <li>Selesaikan pembayaran sebelum batas waktu.</li>
                                </ol>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <flux:button data-payment-qr-download size="sm" icon="arrow-down-tray" href="{{ $qrisDataUri }}" download="QRIS-{{ $payment->order_id }}.svg">Unduh QR</flux:button>
                                    <flux:button data-payment-qr-open size="sm" variant="outline" icon="arrow-top-right-on-square" href="{{ $qrisDataUri }}" target="_blank" rel="noopener noreferrer">Buka QR</flux:button>
                                </div>
                            </div>
                        </div>
                    @elseif ($isQris && $destinationUrl)
                        <div class="space-y-4 rounded-xl border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-800 dark:bg-indigo-950/40">
                            <div>
                                <div class="font-semibold text-indigo-950 dark:text-indigo-100">Lanjutkan di halaman QRIS penyedia</div>
                                <p class="mt-1 text-sm text-indigo-800 dark:text-indigo-200">Buka tautan aman penyedia untuk melihat dan menyelesaikan pembayaran QRIS.</p>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <flux:button data-payment-qr-hosted-open variant="primary" icon="arrow-top-right-on-square" href="{{ $destinationUrl }}" target="_blank" rel="noopener noreferrer">Buka QRIS</flux:button>
                                <flux:button type="button" size="sm" variant="outline" icon="document-duplicate" aria-label="Salin tautan QRIS" x-bind:aria-label="copyState === 'success' && copyTarget === 'tautan QRIS' ? 'Tautan QRIS tersalin' : 'Salin tautan QRIS'" data-copy-value="{{ $destinationUrl }}" x-on:click="copy($el.dataset.copyValue, 'tautan QRIS')">
                                    <span x-show="copyState !== 'success' || copyTarget !== 'tautan QRIS'">Salin tautan</span>
                                    <span x-cloak x-show="copyState === 'success' && copyTarget === 'tautan QRIS'">Tersalin</span>
                                </flux:button>
                            </div>
                        </div>
                    @elseif ($isQris && filled($payment->account_number))
                        <div class="space-y-4 rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/40">
                            <p class="text-sm text-amber-800 dark:text-amber-200">Kode QR tidak dapat ditampilkan. Salin payload QRIS untuk digunakan pada aplikasi pembayaran.</p>
                            <flux:button data-payment-qr-copy type="button" size="sm" variant="outline" icon="document-duplicate" aria-label="Salin payload QRIS" x-bind:aria-label="copyState === 'success' && copyTarget === 'payload QRIS' ? 'Payload QRIS tersalin' : 'Salin payload QRIS'" data-copy-value="{{ $payment->account_number }}" x-on:click="copy($el.dataset.copyValue, 'payload QRIS')">
                                <span x-show="copyState !== 'success' || copyTarget !== 'payload QRIS'">Salin payload QRIS</span>
                                <span x-cloak x-show="copyState === 'success' && copyTarget === 'payload QRIS'">Tersalin</span>
                            </flux:button>
                        </div>
                    @elseif ($isHostedPayment)
                        <div class="space-y-4 rounded-xl border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-800 dark:bg-indigo-950/40">
                            <div class="flex gap-3">
                                <flux:icon.arrow-top-right-on-square class="mt-0.5 size-5 shrink-0 text-indigo-600 dark:text-indigo-300" />
                                <div>
                                    <div class="font-semibold text-indigo-950 dark:text-indigo-100">Lanjutkan di halaman penyedia</div>
                                    <p class="mt-1 text-sm text-indigo-800 dark:text-indigo-200">Pilih bank atau kanal pembayaran, lalu selesaikan transaksi di halaman aman penyedia.</p>
                                </div>
                            </div>
                            <flux:button data-payment-hosted-open variant="primary" icon="arrow-top-right-on-square" href="{{ $destinationUrl }}" target="_blank" rel="noopener noreferrer">Buka halaman pembayaran</flux:button>
                        </div>
                    @elseif (filled($payment->account_number))
                        <div class="space-y-5">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nomor virtual account</div>
                                <div class="mt-2 flex flex-col gap-3 rounded-xl bg-gray-50 p-4 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
                                    <span class="break-all text-xl font-bold tracking-wide text-gray-900 dark:text-white">{{ $payment->account_number }}</span>
                                    <flux:button data-payment-va-copy type="button" size="sm" variant="outline" icon="document-duplicate" aria-label="Salin nomor virtual account" x-bind:aria-label="copyState === 'success' && copyTarget === 'nomor virtual account' ? 'Nomor virtual account tersalin' : 'Salin nomor virtual account'" data-copy-value="{{ $payment->account_number }}" x-on:click="copy($el.dataset.copyValue, 'nomor virtual account')">
                                        <span x-show="copyState !== 'success' || copyTarget !== 'nomor virtual account'">Salin VA</span>
                                        <span x-cloak x-show="copyState === 'success' && copyTarget === 'nomor virtual account'">Tersalin</span>
                                    </flux:button>
                                </div>
                            </div>

                            @if (filled($payment->account_code))
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode pembayaran</div>
                                    <div class="mt-2 flex flex-col gap-3 rounded-xl bg-gray-50 p-4 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
                                        <span class="break-all text-lg font-bold text-gray-900 dark:text-white">{{ $payment->account_code }}</span>
                                        <flux:button data-payment-code-copy type="button" size="sm" variant="outline" icon="document-duplicate" aria-label="Salin kode pembayaran" x-bind:aria-label="copyState === 'success' && copyTarget === 'kode pembayaran' ? 'Kode pembayaran tersalin' : 'Salin kode pembayaran'" data-copy-value="{{ $payment->account_code }}" x-on:click="copy($el.dataset.copyValue, 'kode pembayaran')">
                                            <span x-show="copyState !== 'success' || copyTarget !== 'kode pembayaran'">Salin kode</span>
                                            <span x-cloak x-show="copyState === 'success' && copyTarget === 'kode pembayaran'">Tersalin</span>
                                        </flux:button>
                                    </div>
                                </div>
                            @endif

                            <ol class="list-decimal space-y-2 pl-5 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                <li>Salin nomor virtual account atau kode pembayaran.</li>
                                <li>Buka aplikasi bank, ATM, atau kanal pembayaran yang sesuai.</li>
                                <li>Bayar tepat sesuai total tagihan sebelum batas waktu.</li>
                            </ol>
                        </div>
                    @else
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                            Instruksi pembayaran belum tersedia. Muat ulang halaman atau coba beberapa saat lagi.
                        </div>
                    @endif

                    <p
                        data-payment-copy-status
                        role="status"
                        aria-live="polite"
                        aria-atomic="true"
                        class="text-sm"
                        x-cloak
                        x-show="copyState !== 'idle'"
                        x-bind:class="copyState === 'error' ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300'"
                        x-text="copyMessage"
                    ></p>
                    </flux:card>
                @else
                    <flux:card data-payment-terminal-state class="space-y-4">
                        @if ($paymentStatus === 'paid')
                            <div class="flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                                <flux:icon.check-circle class="mt-0.5 size-5 shrink-0" />
                                <div>
                                    <flux:heading size="lg">Pembayaran selesai</flux:heading>
                                    <p class="mt-1 text-sm">Pembayaran telah dikonfirmasi. Pesanan Anda sedang diproses.</p>
                                </div>
                            </div>
                        @else
                            <div class="flex gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
                                <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0" />
                                <div>
                                    <flux:heading size="lg">Pembayaran tidak dapat dilanjutkan</flux:heading>
                                    <p class="mt-1 text-sm">Transaksi ini gagal atau telah kedaluwarsa. Kembali ke detail pesanan untuk melihat opsi berikutnya.</p>
                                </div>
                            </div>
                        @endif
                    </flux:card>
                @endif

                <details data-payment-order-details class="group rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-semibold text-gray-900 marker:hidden dark:text-white">
                        Detail pesanan dan transaksi
                        <flux:icon.chevron-down class="size-5 text-gray-500 transition group-open:rotate-180" />
                    </summary>
                    <div class="space-y-5 border-t border-gray-200 p-5 dark:border-gray-700">
                        <dl class="grid gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">ID transaksi</dt>
                                <dd class="mt-1 break-all font-medium text-gray-900 dark:text-white">{{ $payment->order_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Metode</dt>
                                <dd class="mt-1 font-medium uppercase text-gray-900 dark:text-white">{{ str_replace('_', ' ', $payment->channel) }}</dd>
                            </div>
                        </dl>

                        @foreach ($order->orderShops as $orderShop)
                            <div>
                                <div class="mb-3 flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                                    <flux:icon.building-storefront class="size-4 text-gray-500" />
                                    {{ $orderShop->shop->name ?? 'Toko' }}
                                </div>
                                <div class="space-y-3">
                                    @foreach ($orderShop->items as $item)
                                        <div class="flex items-start justify-between gap-4 rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">
                                            <span class="min-w-0 text-gray-700 dark:text-gray-200">{{ $item->product_data['name'] ?? 'Produk' }}</span>
                                            <span class="shrink-0 font-semibold text-gray-900 dark:text-white">Rp {{ number_format((float) $item->total, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </details>
            </main>

            <aside data-payment-summary class="lg:sticky lg:top-24">
                <flux:card class="space-y-5">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status pembayaran</div>
                        <div class="mt-2">
                            @if ($paymentStatus === 'paid')
                                <flux:badge color="green" icon="check-circle">Dibayar</flux:badge>
                            @elseif ($paymentStatus === 'expired')
                                <flux:badge color="red" icon="x-circle">Gagal atau kedaluwarsa</flux:badge>
                            @else
                                <flux:badge color="yellow" icon="clock">Menunggu pembayaran</flux:badge>
                            @endif
                        </div>
                    </div>

                    @if ($isPaymentPending)
                        @if ($payment->expired_at)
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Batas pembayaran</div>
                                <div class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $payment->expired_at->translatedFormat('d M Y, H:i') }}</div>

                                <div
                                    data-payment-countdown
                                    class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm font-bold tabular-nums text-rose-700 dark:bg-rose-950/40 dark:text-rose-200"
                                    x-data="{
                                        expiresAt: Date.parse(@js($payment->expired_at->toIso8601String())),
                                        remaining: '',
                                        timer: null,
                                        init() {
                                            this.tick();
                                            this.timer = setInterval(() => this.tick(), 1000);
                                        },
                                        tick() {
                                            const seconds = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000));
                                            const hours = Math.floor(seconds / 3600);
                                            const minutes = Math.floor((seconds % 3600) / 60);
                                            const remainder = seconds % 60;
                                            this.remaining = `${hours}j ${minutes}m ${remainder}d`;

                                            if (seconds === 0 && this.timer) {
                                                clearInterval(this.timer);
                                                this.timer = null;
                                            }
                                        },
                                        destroy() {
                                            if (this.timer) clearInterval(this.timer);
                                        },
                                    }"
                                    x-text="remaining"
                                ></div>
                            </div>
                        @else
                            <div class="text-sm text-gray-500 dark:text-gray-400">Penyedia tidak menetapkan batas waktu pembayaran.</div>
                        @endif
                    @endif

                    <dl class="space-y-3 border-t border-gray-200 pt-5 text-sm dark:border-gray-700">
                        <div class="flex items-center justify-between gap-4 text-gray-600 dark:text-gray-300">
                            <dt>Nominal pesanan</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 text-gray-600 dark:text-gray-300">
                            <dt>Biaya pembayaran</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">Rp {{ number_format((float) $payment->fee, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-gray-200 pt-4 text-lg font-bold text-gray-900 dark:border-gray-700 dark:text-white">
                            <dt>Total tagihan</dt>
                            <dd>Rp {{ number_format((float) $payment->total, 0, ',', '.') }}</dd>
                        </div>
                    </dl>

                    @if ($isPaymentPending)
                        <div class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                            <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0" />
                            Bayar tepat hingga digit terakhir agar verifikasi berjalan otomatis.
                        </div>
                    @endif
                </flux:card>
            </aside>
        </div>
    @endif
</div>
