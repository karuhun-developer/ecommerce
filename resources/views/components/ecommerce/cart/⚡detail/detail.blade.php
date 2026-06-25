
<div>
    <div
        class="max-w-7xl mx-auto px-4 md:px-6 py-8"
        x-data="{
            checked: [
                ...$store.cart.items.map(i => i.id)
            ],

            get allIds() {
                return $store.cart.items.map(i => i.id);
            },

            get isAllChecked() {
                return this.allIds.length > 0 && this.allIds.every(id => this.checked.includes(id));
            },

            get isIndeterminate() {
                return this.checked.length > 0 && !this.isAllChecked;
            },

            get isSomeChecked() {
                return this.checked.length > 0;
            },

            // Group items by shop
            get groupedByShop() {
                const groups = {};
                $store.cart.items.forEach(item => {
                    const shopId = item.shop_id ?? 'unknown';
                    if (!groups[shopId]) {
                        groups[shopId] = {
                            shop_id: shopId,
                            shop_name: item.shop_name ?? 'Toko',
                            items: [],
                        };
                    }
                    groups[shopId].items.push(item);
                });
                return Object.values(groups);
            },

            // Per-shop check helpers
            shopItemIds(shopId) {
                return $store.cart.items
                    .filter(i => (i.shop_id ?? 'unknown') == shopId)
                    .map(i => i.id);
            },

            isShopAllChecked(shopId) {
                const ids = this.shopItemIds(shopId);
                return ids.length > 0 && ids.every(id => this.checked.includes(id));
            },

            isShopIndeterminate(shopId) {
                const ids = this.shopItemIds(shopId);
                const someChecked = ids.some(id => this.checked.includes(id));
                return someChecked && !this.isShopAllChecked(shopId);
            },

            toggleShop(shopId) {
                const ids = this.shopItemIds(shopId);
                if (this.isShopAllChecked(shopId)) {
                    this.checked = this.checked.filter(id => !ids.includes(id));
                } else {
                    ids.forEach(id => {
                        if (!this.checked.includes(id)) this.checked.push(id);
                    });
                }
            },

            toggleAll() {
                if (this.isAllChecked) {
                    this.checked = [];
                } else {
                    this.checked = [...this.allIds];
                }
            },

            toggleItem(id) {
                if (this.checked.includes(id)) {
                    this.checked = this.checked.filter(c => c !== id);
                } else {
                    this.checked.push(id);
                }
            },

            isChecked(id) {
                return this.checked.includes(id);
            },

            deleteChecked() {
                this.checked.forEach(id => $store.cart.remove(id));
                this.checked = [];
            },

            get checkedTotal() {
                return $store.cart.items
                    .filter(i => this.checked.includes(i.id))
                    .reduce((sum, i) => sum + i.price * i.qty, 0);
            },

            get checkedCount() {
                return $store.cart.items
                    .filter(i => this.checked.includes(i.id))
                    .reduce((sum, i) => sum + i.qty, 0);
            },

            init() {
                // Sync checked state when cart changes
                this.$watch('$store.cart.items', (items) => {
                    const ids = items.map(i => i.id);
                    this.checked = this.checked.filter(id => ids.includes(id));
                });
            },
        }"
    >
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Keranjang Belanja</h1>
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items -->
            <div class="w-full lg:w-2/3">
                <div class="bg-white border rounded-2xl shadow-sm p-6">
                    <!-- Header: Pilih Semua -->
                    <div class="flex items-center gap-4 border-b pb-4 mb-4">
                        <input
                            type="checkbox"
                            class="w-5 h-5 text-green-600 rounded border-gray-300 focus:ring-green-500 cursor-pointer"
                            :checked="isAllChecked"
                            :indeterminate="isIndeterminate"
                            @change="toggleAll()"
                        >
                        <span class="font-bold text-gray-800">Pilih Semua Item</span>
                        <flux:button
                            x-show="isSomeChecked"
                            variant="danger"
                            size="sm"
                            class="ml-auto cursor-pointer"
                            @click="deleteChecked()"
                        >
                            Hapus
                        </flux:button>
                    </div>

                    <!-- Empty state -->
                    <template x-if="$store.cart.items.length === 0">
                        <div class="text-center py-12">
                            <flux:icon.shopping-bag class="w-20 h-20 text-gray-200 mx-auto mb-4" />
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Keranjangmu masih kosong</h2>
                            <p class="text-gray-500 mb-6">Yuk, mulai penuhi dengan barang-barang impianmu!</p>
                            <flux:button href="{{ route('home') }}" variant="primary" color="green" wire:navigate>
                                Mulai Belanja
                            </flux:button>
                        </div>
                    </template>

                    <!-- Grouped by shop -->
                    <div class="space-y-6">
                        <template x-for="group in groupedByShop" :key="group.shop_id">
                            <div class="border rounded-xl overflow-hidden">
                                <!-- Shop header -->
                                <div class="flex items-center gap-3 bg-gray-50 px-4 py-3 border-b">
                                    <input
                                        type="checkbox"
                                        class="w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-green-500 cursor-pointer"
                                        :checked="isShopAllChecked(group.shop_id)"
                                        :indeterminate="isShopIndeterminate(group.shop_id)"
                                        @change="toggleShop(group.shop_id)"
                                    >
                                    <flux:icon.building-storefront class="w-4 h-4 text-gray-500" />
                                    <span class="font-semibold text-sm text-gray-800" x-text="group.shop_name"></span>
                                </div>

                                <!-- Items in this shop -->
                                <div class="divide-y">
                                    <template x-for="item in group.items" :key="item.id">
                                        <div class="flex gap-4 p-4">
                                            <input
                                                type="checkbox"
                                                class="w-5 h-5 mt-2 text-green-600 rounded border-gray-300 focus:ring-green-500 cursor-pointer shrink-0"
                                                :checked="isChecked(item.id)"
                                                @change="toggleItem(item.id)"
                                            >
                                            <img :src="item.image" alt="Product" class="w-20 h-20 rounded-xl object-cover border shrink-0" x-show="item.image != ''" />
                                            <div class="w-20 h-20 rounded-xl bg-gray-100 flex items-center justify-center border shrink-0" x-show="item.image == ''">
                                                <flux:icon.photo class="w-8 h-8 text-gray-400" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-gray-900 font-medium line-clamp-2 mb-1" x-text="item.name"></h3>
                                                <div class="font-bold text-gray-900" x-text="'Rp' + item.price.toLocaleString('id-ID')"></div>
                                            </div>
                                            <div class="flex flex-col justify-end items-end gap-3 shrink-0">
                                                <flux:button
                                                    icon="trash"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="text-gray-400 hover:text-red-500 cursor-pointer"
                                                    @click="$store.cart.remove(item.id)"
                                                />
                                                <div class="flex items-center border rounded-lg">
                                                    <button @click="$store.cart.updateQty(item.id, item.qty - 1)" class="px-2 py-1 text-gray-500 hover:bg-gray-100 rounded-l-lg">-</button>
                                                    <span class="px-4 text-gray-900 font-medium text-sm" x-text="item.qty"></span>
                                                    <button @click="$store.cart.updateQty(item.id, item.qty + 1)" class="px-2 py-1 text-gray-500 hover:bg-gray-100 rounded-r-lg">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="w-full lg:w-1/3">
                <div class="bg-white border rounded-2xl shadow-sm p-6 sticky top-24">
                    <h2 class="font-bold text-lg text-gray-900 mb-4">Ringkasan Belanja</h2>
                    <div class="space-y-3 text-sm mb-6">
                        <div class="flex justify-between text-gray-600">
                            <span x-text="'Total Harga (' + checkedCount + ' barang)'"></span>
                            <span x-text="'Rp' + checkedTotal.toLocaleString('id-ID')"></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Total Diskon Barang</span>
                            <span class="text-green-600">-Rp0</span>
                        </div>
                    </div>

                    <div class="border-t pt-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-900">Total Harga</span>
                            <span class="font-black text-xl text-gray-900" x-text="'Rp' + checkedTotal.toLocaleString('id-ID')"></span>
                        </div>
                    </div>

                    <flux:button
                        x-bind:href="'{{ route('checkout') }}?items=' + checked.join(',')"
                        variant="primary"
                        color="green"
                        class="w-full cursor-pointer"
                        x-bind:disabled="checkedCount === 0"
                        wire:navigate
                    >
                        Beli (<span x-text="checkedCount"></span>)
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>