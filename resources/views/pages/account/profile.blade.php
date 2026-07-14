<?php

use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Illuminate\View\View;

name('account.profile');

render(function (View $view) {
    $title = 'Pengaturan Akun';
    $description = 'Atur profil akun Anda.';
    
    SEOMeta::setTitle($title . ' | ' . config('app.name'));
    SEOMeta::setDescription($description);
    SEOMeta::addMeta('robots', 'noindex, nofollow');

    OpenGraph::setTitle($title . ' | ' . config('app.name'));
    OpenGraph::setDescription($description);
    
    $view->with(compact('title'));
});
?>

<x-layouts.ecommerce :title="$title . ' | ' . config('app.name')">
    @section('seo')
        {!! SEO::generate() !!}
    @endsection

    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
        <div class="max-w-6xl mx-auto px-4 md:px-6">
            <div class="flex items-start max-md:flex-col">
                <div class="me-10 w-full pb-4 md:w-[220px]">
                    <flux:navlist>
                        <flux:navlist.item :href="route('orders.index')" wire:navigate>Daftar Transaksi</flux:navlist.item>
                        <flux:navlist.item :href="route('account.profile')" current wire:navigate>Pengaturan Akun</flux:navlist.item>
                    </flux:navlist>
                </div>

                <flux:separator class="md:hidden" />

                <div class="flex-1 self-stretch max-md:pt-6">
                    <div class="bg-white border rounded-2xl shadow-sm p-6 mb-6">
                        <flux:heading size="xl" class="mb-6">Pengaturan Akun</flux:heading>
                        
                        <div class="space-y-12">
                            <!-- Update Profile Form -->
                            <div class="border-b pb-8">
                                <flux:heading size="lg" class="mb-2">Profil Pengguna</flux:heading>
                                <flux:subheading class="mb-6">Perbarui nama dan alamat email akun Anda.</flux:subheading>
                                <div class="max-w-xl">
                                    <livewire:setting.profile />
                                </div>
                            </div>
                            
                            <!-- Update Password Form -->
                            <div class="border-b pb-8">
                                <flux:heading size="lg" class="mb-2">Ubah Password</flux:heading>
                                <flux:subheading class="mb-6">Pastikan akun Anda menggunakan password yang panjang dan aman.</flux:subheading>
                                <div class="max-w-xl">
                                    <livewire:setting.password />
                                </div>
                            </div>
                            
                            <!-- 2FA Setup -->
                            <div>
                                <flux:heading size="lg" class="mb-2">Two Factor Authentication</flux:heading>
                                <flux:subheading class="mb-6">Kelola keamanan tambahan untuk akun Anda.</flux:subheading>
                                <div class="max-w-xl">
                                    <livewire:setting.two-factor />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.ecommerce>
