<div class="max-w-2xl mx-auto px-4 py-12 md:py-24">
    <flux:card class="p-8 shadow-sm">
        <div class="flex flex-col items-center text-center mb-8">
            <div class="w-16 h-16 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-2xl flex items-center justify-center mb-4">
                <flux:icon.receipt-percent class="w-8 h-8" />
            </div>
            <flux:heading size="xl" class="mb-2">Cek Status Pesanan</flux:heading>
            <flux:subheading>Masukkan kode referensi transaksi Anda untuk melacak status pesanan terkini.</flux:subheading>
        </div>
        
        <form wire:submit="check" class="flex flex-col gap-6">
            <flux:input 
                wire:model="reference" 
                label="Kode Referensi" 
                placeholder="Contoh: TRX-123456789" 
                required 
                class="w-full"
            />
            
            <flux:button type="submit" variant="primary" color="green" class="w-full">
                Lacak Pesanan
            </flux:button>
        </form>
    </flux:card>
</div>
