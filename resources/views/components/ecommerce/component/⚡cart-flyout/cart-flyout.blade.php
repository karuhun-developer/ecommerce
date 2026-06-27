
<div>
    <flux:modal
        name="cartModal"
        flyout
    >
        <div class="flex h-full flex-col bg-white">
            <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                <div class="flex items-start justify-between">
                    <flux:heading size="xl" id="slide-over-title">Keranjang Belanja</flux:heading>
                </div>

                <div class="mt-8">
                    <div class="flow-root">
                        <template x-if="$store.cart.items.length === 0">
                            <div class="text-center py-12">
                                <flux:icon.shopping-bag class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                                <flux:text class="text-gray-500 font-medium">Keranjangmu kosong.</flux:text>
                                <flux:button @click="$flux.modal('cartModal').close()" variant="primary" color="green" class="mt-4">
                                    Mulai Belanja
                                </flux:button>
                            </div>
                        </template>

                        <!-- Grouped by shop -->
                        <template x-if="$store.cart.items.length > 0">
                            <div class="space-y-4">
                                <template x-for="group in $store.cart.groupedByShop" :key="group.shop_id">
                                    <div class="border rounded-xl overflow-hidden">
                                        <!-- Shop header -->
                                        <div class="flex items-center gap-2 bg-gray-50 px-4 py-2.5 border-b">
                                            <flux:icon.building-storefront class="w-4 h-4 text-gray-500 shrink-0" />
                                            <span class="font-semibold text-sm text-gray-800 truncate" x-text="group.shop_name"></span>
                                        </div>

                                        <!-- Items -->
                                        <ul role="list" class="divide-y divide-gray-100">
                                            <template x-for="item in group.items" :key="item.id">
                                                <li class="flex py-4 px-4">
                                                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200">
                                                        <img :src="item.image" alt="Product image" class="h-full w-full object-cover object-center" x-show="item.image != ''" x-cloak>
                                                        <div class="h-full w-full flex items-center justify-center bg-gray-100" x-show="item.image == ''" x-cloak>
                                                            <flux:icon.photo class="w-6 h-6 text-gray-300" />
                                                        </div>
                                                    </div>

                                                    <div class="ml-3 flex flex-1 flex-col">
                                                        <div>
                                                            <flux:heading size="sm" class="line-clamp-2">
                                                                <a href="#" x-text="item.name"></a>
                                                            </flux:heading>
                                                            <flux:text class="mt-1 font-bold text-orange-500" x-text="'Rp ' + item.price.toLocaleString('id-ID')"></flux:text>
                                                        </div>
                                                        <div class="flex flex-1 items-end justify-between text-sm mt-3">
                                                            <div class="flex items-center gap-2">
                                                                <flux:button size="sm" variant="subtle" icon="minus" @click="$store.cart.updateQty(item.id, item.qty - 1)" />
                                                                <flux:text class="font-medium w-4 text-center" x-text="item.qty"></flux:text>
                                                                <flux:button size="sm" variant="subtle" icon="plus" @click="$store.cart.updateQty(item.id, item.qty + 1)" />
                                                            </div>
                                                            <flux:button size="sm" variant="subtle" class="text-red-500 hover:text-red-600" @click="$store.cart.remove(item.id)">Hapus</flux:button>
                                                        </div>
                                                    </div>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 px-4 py-6 sm:px-6" x-show="$store.cart.items.length > 0">
                <div class="flex justify-between text-base mb-4">
                    <flux:text class="font-bold">Total Harga</flux:text>
                    <flux:text class="font-bold" x-text="'Rp ' + $store.cart.total.toLocaleString('id-ID')"></flux:text>
                </div>
                <div class="mt-6">
                    <flux:button href="{{ route('cart') }}" variant="primary" color="green" class="w-full justify-center text-base py-3" wire:navigate>
                        Lihat Keranjang
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>