<?php

use function Laravel\Folio\name;
name('orders.detail');
?>
<x-layouts.ecommerce>
    <div class="bg-gray-50 min-h-screen py-8" x-data>
        <div class="max-w-4xl mx-auto px-4 md:px-6">
            
            <!-- Success Message (Mock) -->
            <template x-if="new URLSearchParams(location.search).get('success') === 'true'">
                <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl flex items-center gap-3 mb-6">
                    <flux:icon.check-circle class="w-6 h-6 text-green-500" />
                    <div>
                        <h3 class="font-bold">Pembayaran Berhasil!</h3>
                        <p class="text-sm">Pesanan Anda segera diproses oleh penjual.</p>
                    </div>
                </div>
            </template>

            <a href="/orders" class="text-green-600 hover:text-green-700 font-bold text-sm flex items-center gap-2 mb-6">
                <flux:icon.arrow-left class="w-4 h-4" /> Kembali ke Daftar Transaksi
            </a>

            <div class="bg-white border rounded-2xl shadow-sm p-6 mb-6">
                <div class="flex justify-between items-start border-b pb-4 mb-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 mb-1">Detail Transaksi</h1>
                        <p class="text-sm text-gray-500">{{ $id }}</p>
                    </div>
                    <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full">Selesai</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3 text-sm">Info Pengiriman</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><span class="w-24 inline-block text-gray-500">Kurir</span>: Reguler</p>
                            <p><span class="w-24 inline-block text-gray-500">No Resi</span>: <span class="font-bold text-green-600">JP2026001002</span></p>
                            <p><span class="w-24 inline-block text-gray-500">Alamat</span>:</p>
                            <p class="font-bold text-gray-900">Budi Santoso</p>
                            <p>081234567890</p>
                            <p>Jl. Jendral Sudirman No. 123, Komplek Mawar Blok B4, Kebayoran Baru, Jakarta Selatan, DKI Jakarta 12190</p>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3 text-sm">Info Pembayaran</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><span class="w-32 inline-block text-gray-500">Metode Bayar</span>: BCA Virtual Account</p>
                            <p><span class="w-32 inline-block text-gray-500">Tanggal Bayar</span>: 9 Jun 2026, 14:30 WIB</p>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h3 class="font-bold text-gray-900 mb-4 text-sm">Rincian Produk</h3>
                    
                    <div class="space-y-4">
                        <div class="flex gap-4 border p-4 rounded-xl">
                            <img src="https://images.unsplash.com/photo-1527443154391-42075928d114?auto=format&fit=crop&q=80&w=200&h=200" alt="Product" class="w-16 h-16 rounded-lg object-cover border">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-sm mb-1">Logitech G Pro X Superlight</h4>
                                <p class="text-xs text-gray-500 mb-2">1 x Rp1.500.000</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 mb-1">Total Harga</p>
                                <p class="font-bold text-gray-900">Rp1.500.000</p>
                            </div>
                        </div>

                        <div class="flex gap-4 border p-4 rounded-xl">
                            <img src="https://images.unsplash.com/photo-1595225476474-87563907a212?auto=format&fit=crop&q=80&w=200&h=200" alt="Product" class="w-16 h-16 rounded-lg object-cover border">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-sm mb-1">Keychron K2 Wireless Mechanical Keyboard</h4>
                                <p class="text-xs text-gray-500 mb-2">1 x Rp1.200.000</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 mb-1">Total Harga</p>
                                <p class="font-bold text-gray-900">Rp1.200.000</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4 mt-6">
                    <h3 class="font-bold text-gray-900 mb-3 text-sm">Rincian Pembayaran</h3>
                    <div class="space-y-2 text-sm text-gray-600 max-w-sm ml-auto">
                        <div class="flex justify-between">
                            <span>Total Harga (2 barang)</span>
                            <span>Rp2.700.000</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Ongkos Kirim</span>
                            <span>Rp20.000</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Asuransi Pengiriman</span>
                            <span>Rp2.500</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Biaya Jasa Aplikasi</span>
                            <span>Rp1.000</span>
                        </div>
                        <div class="flex justify-between border-t pt-2 mt-2 font-bold text-gray-900">
                            <span>Total Belanja</span>
                            <span>Rp2.723.500</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layouts.ecommerce>
