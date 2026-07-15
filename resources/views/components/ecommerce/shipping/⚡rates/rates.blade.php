
<div
    wire:key="shipping-rates-{{ $shopId }}"
    x-data="{
        // For guest: read destination info from localStorage
        lsKey: 'checkout_guest_address',

        loadGuestDestination() {
            try {
                const raw = localStorage.getItem(this.lsKey);
                if (!raw) return null;
                return JSON.parse(raw);
            } catch { return null; }
        },

        init() {
            // Only run for guests — auth handled server-side on mount
            const isAuth = {{ auth()->check() ? 'true' : 'false' }};
            if (isAuth) return;

            setTimeout(() => {
                const data = this.loadGuestDestination();
                if (data && data.biteship_area_id) {
                    $wire.setGuestDestination(data.biteship_area_id, data.postal_code ?? '');
                }
            }, 500); // wait to be ready
        }
    }"
>
    {{-- Trigger fetch rates --}}
    <div class="border-t pt-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-gray-900 text-sm">Pilih Pengiriman</h3>
            @if(blank($destinationAreaId))
                <span class="text-xs text-amber-600 font-medium">Lengkapi alamat dulu</span>
            @else
                <flux:button
                    wire:click="fetchRates"
                    size="xs"
                    variant="ghost"
                    icon="arrow-path"
                    wire:loading.attr="disabled"
                    wire:target="fetchRates"
                >
                    <span wire:loading.remove wire:target="fetchRates">Cek Tarif</span>
                    <span wire:loading wire:target="fetchRates">Memuat...</span>
                </flux:button>
            @endif
        </div>

        {{-- Error --}}
        @if($error)
            <div class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 mb-3">
                <flux:icon.exclamation-circle class="w-4 h-4 shrink-0 mt-0.5" />
                <span>{{ $error }}</span>
            </div>
        @endif

        {{-- Loading skeleton --}}
        <div wire:loading wire:target="fetchRates" class="space-y-2 w-full">
            @foreach(range(1, 3) as $_)
                <div class="border rounded-xl p-3 animate-pulse flex justify-between items-center">
                    <div class="space-y-1.5">
                        <div class="h-3 w-32 bg-gray-200 rounded"></div>
                        <div class="h-2.5 w-20 bg-gray-100 rounded"></div>
                    </div>
                    <div class="h-3 w-16 bg-gray-200 rounded"></div>
                </div>
            @endforeach
        </div>

        {{-- Empty / before fetch --}}
        @if(empty($rates) && !$loading && blank($error) && !blank($destinationAreaId))
            <button
                wire:click="fetchRates"
                class="w-full border border-dashed border-gray-300 rounded-xl p-4 text-sm text-gray-500 hover:border-gray-400 hover:text-gray-600 transition flex items-center justify-center gap-2"
            >
                <flux:icon.truck class="w-4 h-4" />
                Klik untuk cek ongkir
            </button>
        @endif

        {{-- Rates list --}}
        @if(!empty($rates))
            <div class="space-y-2" wire:loading.remove wire:target="fetchRates">
                @foreach($rates as $rate)
                    @php
                        $code    = $rate['courier_code'] ?? '';
                        $service = $rate['courier_service_code'] ?? '';
                        $price   = (int) ($rate['price'] ?? 0);
                        $name    = ($rate['courier_name'] ?? '') . ' ' . ($rate['courier_service_name'] ?? '');
                        $etd     = $rate['duration'] ?? ($rate['etd'] ?? '-');
                        $isSelected = $selectedCourierCode === $code && $selectedServiceCode === $service;
                    @endphp
                    <button
                        wire:key="rate-{{ $shopId }}-{{ $code }}-{{ $service }}"
                        wire:click="selectRate('{{ $code }}', '{{ $service }}', {{ $price }}, '{{ addslashes($name) }}', '{{ addslashes($etd) }}')"
                        class="w-full border rounded-xl p-3 cursor-pointer flex items-center justify-between transition text-left {{ $isSelected ? 'border-gray-500 bg-gray-50' : 'hover:border-gray-300 bg-white' }}"
                    >
                        <div class="flex items-center gap-3">
                            {{-- Radio indicator --}}
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 {{ $isSelected ? 'border-gray-500' : 'border-gray-300' }}">
                                @if($isSelected)
                                    <div class="w-2 h-2 rounded-full bg-gray-500"></div>
                                @endif
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 text-sm">{{ $name }}</div>
                                <div class="text-xs text-gray-500">
                                    @if(is_array($etd))
                                        Estimasi {{ $etd['min'] ?? '-' }}–{{ $etd['max'] ?? '-' }} hari
                                    @else
                                        Estimasi {{ $etd }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="font-bold text-gray-900 text-sm shrink-0">
                            Rp{{ number_format($price, 0, ',', '.') }}
                        </div>
                    </button>
                @endforeach
            </div>

            @if($selectedCourierCode)
                <div class="mt-3 flex items-center gap-2 p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800">
                    <flux:icon.check-circle class="w-4 h-4 text-gray-600 shrink-0" />
                    <span><span class="font-bold">{{ $selectedName }}</span> — Rp{{ number_format($selectedPrice, 0, ',', '.') }}</span>
                </div>
            @endif
        @endif
    </div>
</div>