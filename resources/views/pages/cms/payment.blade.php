<?php

use App\Models\Setting\Setting;
use Illuminate\View\View;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.payment');
middleware(['can:view'.Setting::class]);

render(function (View $view): void {
    $view->with('title', 'Pengaturan Integrasi');
}); ?>

<x-layouts.app :$title>
    <div class="w-full">
        <div class="mb-5 flex items-center justify-between gap-4">
            <h1 class="text-3xl font-bold">{{ $title }}</h1>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('cms.dashboard')" icon="home" />
                <flux:breadcrumbs.item>{{ $title }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
        <div class="space-y-8">
            <livewire:cms.payment.settings />
            <livewire:cms.shipping.biteship.settings />

            <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:heading size="lg">URL Callback</flux:heading>
                <flux:text class="mt-2">Daftarkan URL berikut di dashboard masing-masing penyedia. Callback membutuhkan domain publik HTTPS; alamat lokal hanya untuk preview.</flux:text>
                <div class="mt-5 space-y-4">
                    <flux:input :value="route('api.v1.midtrans.callback')" label="Midtrans — Payment Notification URL" readonly copyable />
                    <flux:input :value="route('api.v1.paywuz.callback')" label="Paywuz — Webhook URL" readonly copyable />
                    <flux:input :value="route('api.v1.biteship.callback')" label="Biteship — Webhook URL" readonly copyable />
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
