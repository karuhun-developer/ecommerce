
<div
    x-data="{
        lsKey: 'checkout_guest_address',
        totalShippingCost: $wire.entangle('totalShippingCost'),
        asuransiPengiriman: $wire.entangle('asuransiPengiriman'),
        jasaAplikasi: $wire.entangle('jasaAplikasi'),
        ready: false,

        get checkoutItems() {
            const ids = @js($this->getSelectedIdsArrayProperty());
            if (ids.length === 0) return $store.cart.items;
            return $store.cart.items.filter(i => ids.includes(i.id));
        },

        get subtotal() {
            return this.checkoutItems.reduce((s, i) => s + i.price * i.qty, 0);
        },

        get checkoutCount() {
            return this.checkoutItems.reduce((s, i) => s + i.qty, 0);
        },

        get grandTotal() {
            return this.subtotal + this.totalShippingCost + this.asuransiPengiriman + this.jasaAplikasi;
        },

        async init() {
            await $wire.resolveShopGroups($store.cart.items);
            this.ready = true;
        },

        async submitOrder() {
            let guestData = {};
            if (!{{ auth()->check() ? 'true' : 'false' }}) {
                const localStorageData = JSON.parse(localStorage.getItem(this.lsKey) || '{}');
                guestData = {
                    contact_name: localStorageData.contact_name,
                    contact_phone: localStorageData.contact_phone,
                    email: localStorageData.email,
                    address: localStorageData.address,
                    note: localStorageData.note,
                    postal_code: localStorageData.postal_code,
                    area_string: localStorageData.area_string,
                    biteship_area_id: localStorageData.biteship_area_id,
                    latitude: localStorageData.latitude,
                    longitude: localStorageData.longitude,
                }
            }

            await $wire.submit(guestData);
        }
    }"
>
    <div class="bg-gray-50 min-h-screen py-8">
        <div class="max-w-6xl mx-auto px-4 md:px-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Pengiriman</h1>

            <div class="flex flex-col lg:flex-row gap-8">
                <div class="w-full lg:w-2/3 space-y-6">
                    <!-- Shipping Address -->
                    <livewire:ecommerce.shipping.list />

                    <!-- Empty cart -->
                    <template x-if="ready && checkoutItems.length === 0">
                        <div class="bg-white border rounded-2xl shadow-sm p-12 text-center">
                            <flux:icon.shopping-bag class="w-16 h-16 text-gray-200 mx-auto mb-4" />
                            <p class="font-bold text-gray-800 mb-1">Tidak ada item untuk di-checkout</p>
                            <p class="text-sm text-gray-500 mb-4">Kembali ke keranjang dan pilih barang terlebih dahulu.</p>
                            <flux:button href="{{ route('cart') }}" variant="primary" color="green" wire:navigate>
                                Ke Keranjang
                            </flux:button>
                        </div>
                    </template>

                    <!-- Shop groups -->
                    @foreach($shopGroups as $group)
                        <div
                            wire:key="shop-group-{{ $group['shop_id'] }}"
                            class="bg-white border rounded-2xl shadow-sm overflow-hidden"
                            x-data="{
                                get shopItems() {
                                    return $store.cart.items.filter(i => i.shop_id == {{ $group['shop_id'] }});
                                }
                            }"
                        >
                            <!-- Shop header -->
                            <div class="flex items-center gap-2 bg-gray-50 px-6 py-3 border-b">
                                <flux:icon.building-storefront class="w-4 h-4 text-gray-500 shrink-0" />
                                <span class="font-semibold text-sm text-gray-800">
                                    {{ $group['shop_name'] }}
                                </span>
                            </div>

                            <div class="p-6">
                                <!-- Items -->
                                <div class="space-y-4 mb-4">
                                    <template x-for="item in shopItems" :key="item.id">
                                        <div class="flex gap-4">
                                            <div class="shrink-0">
                                                <img :src="item.image" alt="Product" class="w-16 h-16 rounded-xl object-cover border" x-show="item.image != ''" />
                                                <div class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center border" x-show="item.image == ''">
                                                    <flux:icon.photo class="w-6 h-6 text-gray-400" />
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-gray-900 font-medium line-clamp-2 mb-1 text-sm" x-text="item.name"></h3>
                                                <div class="text-gray-500 text-sm" x-text="item.qty + ' x Rp' + item.price.toLocaleString('id-ID')"></div>
                                            </div>
                                            <div class="font-bold text-gray-900 text-sm shrink-0" x-text="'Rp' + (item.price * item.qty).toLocaleString('id-ID')"></div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Shipping rates for this shop -->
                                <livewire:ecommerce.shipping.rates
                                    :shopId="$group['shop_id']"
                                    :items="$group['items']"
                                    :key="'rates-'.$group['shop_id']"
                                />
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Summary -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-white border rounded-2xl shadow-sm p-6 sticky top-24">
                        <h2 class="font-bold text-lg text-gray-900 mb-4">Ringkasan Belanja</h2>

                        <div class="space-y-3 text-sm mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span x-text="'Total Harga (' + checkoutCount + ' barang)'"></span>
                                <span x-text="'Rp' + subtotal.toLocaleString('id-ID')"></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Total Ongkos Kirim</span>
                                @if($totalShippingCost > 0)
                                    <span class="font-medium text-gray-900">Rp{{ number_format($totalShippingCost, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-gray-400 italic">Pilih kurir</span>
                                @endif
                            </div>

                            {{-- Per-shop breakdown if multiple shops --}}
                            @if(count($shopRates) > 1)
                                @foreach($shopRates as $sId => $rate)
                                    <div class="flex justify-between text-gray-500 text-xs pl-3">
                                        <span>{{ $shopGroups[array_search($sId, array_column($shopGroups, 'shop_id'))]['shop_name'] ?? 'Toko' }} — {{ $rate['name'] }}</span>
                                        <span>Rp{{ number_format($rate['price'], 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            @endif

                            <div class="flex justify-between text-gray-600">
                                <span>Asuransi Pengiriman</span>
                                <span x-text="'Rp' + asuransiPengiriman.toLocaleString('id-ID')"></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Biaya Jasa Aplikasi</span>
                                <span x-text="'Rp' + jasaAplikasi.toLocaleString('id-ID')"></span>
                            </div>
                        </div>

                        <div class="border-t pt-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-900">Total Tagihan</span>
                                <span class="font-black text-xl text-gray-900" x-text="'Rp' + grandTotal.toLocaleString('id-ID')"></span>
                            </div>
                        </div>

                        <flux:button
                            variant="primary"
                            color="green"
                            class="w-full"
                            x-bind:disabled="checkoutItems.length === 0 || {{ count($shopGroups) > 0 && count($shopRates) < count($shopGroups) ? 'true' : 'false' }}"
                            x-on:click="submitOrder"
                        >
                            Pilih Pembayaran
                        </flux:button>

                        @if(count($shopGroups) > 0 && count($shopRates) < count($shopGroups))
                            <p class="text-xs text-amber-600 text-center mt-2">Pilih kurir untuk semua toko</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>