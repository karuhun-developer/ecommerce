
<div>
    <div class="rounded-2xl overflow-hidden mb-8 relative bg-gray-900 h-[200px] md:h-[350px]">
        <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&q=80&w=2000&h=600" class="w-full h-full object-cover opacity-80" alt="Banner">
        <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent flex items-center">
            <div class="px-8 md:px-16">
                <h2 class="text-3xl md:text-5xl font-black text-white mb-2">Mega Sale 12.12</h2>
                <p class="text-white/90 text-lg mb-4">Diskon hingga 90% untuk semua produk elektronik!</p>
                <flux:button href="{{ route('explore.index') }}" wire:navigate>
                    Belanja Sekarang
                </flux:button>
            </div>
        </div>
    </div>
</div>