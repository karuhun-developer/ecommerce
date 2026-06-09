<?php

use function Laravel\Folio\name;
name('payment.index');
?>
<x-layouts.ecommerce>
    <div class="bg-gray-50 min-h-screen py-12" x-data="{
        paymentMethod: null,
        totalAmount: $store.cart.total + 20000 + 3500, // Subtotal + Ongkir + Fee
        processing: false,
        pay() {
            if(!this.paymentMethod) {
                alert('Pilih metode pembayaran terlebih dahulu!');
                return;
            }
            this.processing = true;
            setTimeout(() => {
                // Mock clear cart and redirect
                $store.cart.clear();
                window.location.href = '/orders/INV-2026-001?success=true';
            }, 1500);
        }
    }">
        <div class="max-w-3xl mx-auto px-4 md:px-6">
            
            <div class="bg-white border rounded-2xl shadow-sm overflow-hidden mb-6">
                <div class="p-6 border-b text-center bg-gray-50/50">
                    <h1 class="text-lg text-gray-500 mb-2">Total Tagihan</h1>
                    <div class="text-3xl font-black text-gray-900" x-text="'Rp' + totalAmount.toLocaleString('id-ID')"></div>
                    <div class="text-sm text-gray-500 mt-2">Bayar dalam <span class="font-bold text-orange-500">23:59:59</span></div>
                </div>

                <div class="p-6">
                    <h2 class="font-bold text-gray-900 mb-4">Pilih Metode Pembayaran</h2>

                    <div class="space-y-4">
                        <!-- Virtual Account -->
                        <div>
                            <h3 class="text-sm font-bold text-gray-700 mb-2">Virtual Account</h3>
                            <div class="space-y-2">
                                <label class="border rounded-xl p-4 flex items-center gap-4 cursor-pointer hover:bg-gray-50 transition" :class="paymentMethod === 'BCA VA' ? 'border-green-500 bg-green-50/30' : ''">
                                    <input type="radio" x-model="paymentMethod" value="BCA VA" class="text-green-600 focus:ring-green-500">
                                    <div class="font-bold text-gray-900">BCA Virtual Account</div>
                                </label>
                                <label class="border rounded-xl p-4 flex items-center gap-4 cursor-pointer hover:bg-gray-50 transition" :class="paymentMethod === 'Mandiri VA' ? 'border-green-500 bg-green-50/30' : ''">
                                    <input type="radio" x-model="paymentMethod" value="Mandiri VA" class="text-green-600 focus:ring-green-500">
                                    <div class="font-bold text-gray-900">Mandiri Virtual Account</div>
                                </label>
                            </div>
                        </div>

                        <!-- E-Wallet -->
                        <div class="pt-2">
                            <h3 class="text-sm font-bold text-gray-700 mb-2">E-Wallet</h3>
                            <div class="space-y-2">
                                <label class="border rounded-xl p-4 flex items-center gap-4 cursor-pointer hover:bg-gray-50 transition" :class="paymentMethod === 'GoPay' ? 'border-green-500 bg-green-50/30' : ''">
                                    <input type="radio" x-model="paymentMethod" value="GoPay" class="text-green-600 focus:ring-green-500">
                                    <div class="font-bold text-gray-900">GoPay</div>
                                </label>
                                <label class="border rounded-xl p-4 flex items-center gap-4 cursor-pointer hover:bg-gray-50 transition" :class="paymentMethod === 'OVO' ? 'border-green-500 bg-green-50/30' : ''">
                                    <input type="radio" x-model="paymentMethod" value="OVO" class="text-green-600 focus:ring-green-500">
                                    <div class="font-bold text-gray-900">OVO</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <button @click="pay()" :disabled="processing" class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-bold py-4 rounded-xl transition text-center flex items-center justify-center gap-2">
                <span x-show="!processing">Bayar Sekarang</span>
                <span x-show="processing" class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Memproses...
                </span>
            </button>
            <p class="text-center text-sm text-gray-500 mt-4">Pilih metode pembayaran untuk melanjutkan.</p>

        </div>
    </div>
</x-layouts.ecommerce>
