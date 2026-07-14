<div>
    @placeholder
        <header class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 sticky top-0 z-50 w-full">
            {{-- Main Header Row --}}
            <div class="max-w-7xl mx-auto px-4 md:px-6 py-4 flex items-center justify-between gap-4 md:gap-8">
                <div class="relative h-8 w-24 bg-zinc-100 dark:bg-zinc-800 rounded-lg overflow-hidden shrink-0">
                    <x-placeholder-pattern class="absolute inset-0 w-full h-full text-zinc-300 dark:text-zinc-700 stroke-current" />
                </div>

                <div class="hidden md:flex flex-1 max-w-3xl relative">
                    <div class="relative h-10 w-full bg-zinc-50 dark:bg-zinc-800/50 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
                        <x-placeholder-pattern class="absolute inset-0 w-full h-full text-zinc-200 dark:text-zinc-700/50 stroke-current" />
                    </div>
                </div>

                <div class="flex items-center gap-1 md:gap-3 shrink-0">
                    {{-- Cart Button Placeholder --}}
                    <div class="relative h-10 w-10 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
                        <x-placeholder-pattern class="absolute inset-0 w-full h-full text-zinc-200 dark:text-zinc-700/50 stroke-current" />
                    </div>

                    {{-- Separator Line --}}
                    <div class="hidden md:block mx-2 h-6 w-[1px] bg-zinc-200 dark:bg-zinc-700"></div>

                    {{-- Login Button Placeholder --}}
                    <div class="relative hidden md:block h-10 w-20 bg-zinc-100 dark:bg-zinc-800 rounded-lg overflow-hidden">
                        <x-placeholder-pattern class="absolute inset-0 w-full h-full text-zinc-300 dark:text-zinc-700 stroke-current" />
                    </div>

                    {{-- Register Button Placeholder --}}
                    <div class="relative hidden md:block h-10 w-20 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700">
                        <x-placeholder-pattern class="absolute inset-0 w-full h-full text-zinc-200 dark:text-zinc-700/50 stroke-current" />
                    </div>
                </div>
            </div>

            <div class="md:hidden px-4 pb-3">
                <div class="relative h-10 w-full bg-zinc-50 dark:bg-zinc-800/50 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
                    <x-placeholder-pattern class="absolute inset-0 w-full h-full text-zinc-200 dark:text-zinc-700/50 stroke-current" />
                </div>
            </div>

            <div class="hidden md:flex max-w-7xl mx-auto px-6 py-3.5 gap-6 items-center">
                {{-- Menyesuaikan variasi panjang teks menu kategori asli --}}
                @foreach(['w-16', 'w-32', 'w-36', 'w-24', 'w-28', 'w-20', 'w-24'] as $width)
                    <div class="relative h-4 {{ $width }} bg-zinc-100 dark:bg-zinc-800 rounded overflow-hidden">
                        <x-placeholder-pattern class="absolute inset-0 w-full h-full text-zinc-200 dark:text-zinc-700/50 stroke-current" />
                    </div>
                @endforeach
            </div>
        </header>
    @endplaceholder

    <header class="bg-white border-b sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 md:px-6 py-4 flex items-center justify-between gap-4 md:gap-8">
            <!-- Logo -->
            <a href="/" class="text-2xl md:text-3xl font-black text-green-600 tracking-tight shrink-0" wire:navigate>
                {{ config('app.name', 'Nexa') }}
                <span class="text-gray-800">.</span>
            </a>

            <!-- Search -->
            <div class="hidden md:flex flex-1 items-center max-w-3xl relative">
                <flux:input
                    icon="magnifying-glass"
                    placeholder="Cari barang, merek, atau toko..."
                    class="w-full rounded-xl"
                />
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 md:gap-3 shrink-0">
                <div class="relative">
                    <flux:button @click="$flux.modal('cartModal').show()" variant="subtle" icon="shopping-cart" />
                    <flux:badge x-show="$store.cart.count > 0" x-text="$store.cart.count" color="red" class="absolute -top-1 -right-1 text-[10px] font-bold px-1.5" />
                </div>
                <flux:separator vertical class="hidden md:block mx-2" />
                <flux:button href="{{ route('login') }}" variant="primary" color="green" class="hidden md:flex font-semibold px-6 rounded-lg" wire:navigate>
                    Masuk
                </flux:button>
                <flux:button href="{{ route('register') }}" variant="outline" class="hidden md:flex font-semibold px-6 rounded-lg" wire:navigate>
                    Daftar
                </flux:button>
            </div>
        </div>

        <!-- Mobile Search (Visible on small screens) -->
        <div class="md:hidden px-4 pb-3">
            <flux:input
                placeholder="Cari di Nexa..."
                icon="magnifying-glass"
                class="w-full rounded-xl"
            />
        </div>

        <!-- Categories Menu -->
        <flux:navbar class="hidden md:flex max-w-7xl mx-auto px-6 py-2 gap-6 w-full">
            @foreach ($this->featuredCategories as $category)
                <flux:navbar.item href="{{ route('explore.category', ['category' => $category->slug]) }}" wire:navigate>
                    {{ $category->name }}
                </flux:navbar.item>
            @endforeach

            <div class="ml-auto flex items-center">
                <flux:navbar.item href="{{ route('orders.check') }}" icon="receipt-percent" wire:navigate class="!text-green-600 font-semibold">
                    Cek Transaksi
                </flux:navbar.item>
            </div>
        </flux:navbar>
    </header>
</div>