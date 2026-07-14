<?php

use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;
use Illuminate\View\View;

name('orders.index');

render(function (View $view) {
    $title = 'Daftar Transaksi';
    $description = 'Pantau status pesanan dan riwayat transaksi belanja Anda di ' . config('app.name') . '.';
    $url = url()->current();
    $logo = asset('logo.png');

    SEOMeta::setTitle($title . ' | ' . config('app.name'));
    SEOMeta::setDescription($description);
    SEOMeta::setKeywords(['transaksi', 'pesanan', 'riwayat pesanan']);
    SEOMeta::setCanonical($url);

    OpenGraph::setDescription($description);
    OpenGraph::setTitle($title . ' | ' . config('app.name'));
    OpenGraph::setUrl($url);
    OpenGraph::addProperty('type', 'website');
    OpenGraph::setSiteName(config('app.name'));
    OpenGraph::addImage($logo);

    TwitterCard::setTitle($title . ' | ' . config('app.name'));
    TwitterCard::setSite('@' . strtolower(config('app.name')));
    TwitterCard::setDescription($description);
    TwitterCard::setImage($logo);

    JsonLd::setTitle($title . ' | ' . config('app.name'));
    JsonLd::setDescription($description);
    JsonLd::addImage($logo);

    $view->with(compact('title'));
});

?>

<x-layouts.ecommerce :title="$title . ' | ' . config('app.name')">
    @section('seo')
        {!! SEO::generate() !!}
    @endsection

    <div class="bg-gray-50 min-h-screen py-8">
        <div class="max-w-6xl mx-auto px-4 md:px-6">
            
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Sidebar -->
                <div class="w-full md:w-1/4">
                    <div class="bg-white border rounded-2xl shadow-sm p-4 sticky top-24">
                        <div class="flex items-center gap-4 border-b pb-4 mb-4">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-xl">
                                B
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Budi Santoso</h3>
                                <p class="text-sm text-gray-500">Silver Member</p>
                            </div>
                        </div>
                        <nav class="space-y-1">
                            <a href="#" class="block px-3 py-2 text-sm font-bold text-green-600 bg-green-50 rounded-lg">Daftar Transaksi</a>
                            <a href="#" class="block px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-green-600 rounded-lg transition">Ulasan</a>
                            <a href="#" class="block px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-green-600 rounded-lg transition">Wishlist</a>
                            <a href="#" class="block px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-green-600 rounded-lg transition">Pengaturan Akun</a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="w-full md:w-3/4">
                    <div class="bg-white border rounded-2xl shadow-sm p-6 mb-6">
                        <h1 class="text-xl font-bold text-gray-900 mb-6">Daftar Transaksi</h1>
                        
                        <!-- Tabs -->
                        <div class="flex gap-6 border-b mb-6 overflow-x-auto hide-scrollbar">
                            <button class="pb-3 text-sm font-bold text-green-600 border-b-2 border-green-600 whitespace-nowrap">Semua</button>
                            <button class="pb-3 text-sm font-medium text-gray-500 hover:text-green-600 whitespace-nowrap">Berlangsung</button>
                            <button class="pb-3 text-sm font-medium text-gray-500 hover:text-green-600 whitespace-nowrap">Berhasil</button>
                            <button class="pb-3 text-sm font-medium text-gray-500 hover:text-green-600 whitespace-nowrap">Tidak Berhasil</button>
                        </div>

                        <!-- Search -->
                        <div class="flex gap-2 mb-6">
                            <flux:input placeholder="Cari transaksi" icon="magnifying-glass" class="w-full max-w-sm" />
                            <button class="border border-gray-300 text-gray-600 hover:bg-gray-50 font-medium px-4 py-2 rounded-lg text-sm transition">Filter</button>
                        </div>

                        <!-- Order Card -->
                        <div class="border rounded-xl shadow-sm mb-4">
                            <div class="bg-gray-50 border-b p-3 flex justify-between items-center rounded-t-xl text-sm">
                                <div class="flex items-center gap-3">
                                    <flux:icon.shopping-bag class="w-4 h-4 text-gray-400" />
                                    <span class="font-bold text-gray-900">Belanja</span>
                                    <span class="text-gray-400">9 Jun 2026</span>
                                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded">Selesai</span>
                                    <span class="text-gray-400">INV-2026-001</span>
                                </div>
                            </div>
                            <div class="p-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                                <div class="w-16 h-16 shrink-0 border rounded-lg overflow-hidden">
                                    <img src="https://images.unsplash.com/photo-1527443154391-42075928d114?auto=format&fit=crop&q=80&w=200&h=200" alt="Product" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-900 line-clamp-1 mb-1">Logitech G Pro X Superlight Wireless Gaming Mouse</h3>
                                    <p class="text-sm text-gray-500 mb-2">1 barang x Rp1.500.000</p>
                                    <p class="text-sm text-gray-500">+1 produk lainnya</p>
                                </div>
                                <div class="sm:text-right border-t sm:border-t-0 sm:border-l pt-3 sm:pt-0 sm:pl-4 w-full sm:w-auto mt-3 sm:mt-0">
                                    <p class="text-sm text-gray-500 mb-1">Total Belanja</p>
                                    <p class="font-bold text-gray-900">Rp2.723.500</p>
                                </div>
                            </div>
                            <div class="border-t p-3 flex justify-end gap-2 bg-white rounded-b-xl">
                                <a href="/orders/INV-2026-001" class="text-sm font-bold text-green-600 hover:text-green-700 border border-green-600 px-4 py-1.5 rounded-lg transition">Detail Transaksi</a>
                                <button class="text-sm font-bold text-white bg-green-600 hover:bg-green-700 border border-green-600 px-4 py-1.5 rounded-lg transition">Beli Lagi</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</x-layouts.ecommerce>