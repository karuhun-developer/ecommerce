<?php

use App\Models\Order\Order;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.order.show');

render(function (View $view, $reference) {
    // For now we just load the basic order
    $order = Order::with(['orderShops.shop', 'orderShops.items', 'user', 'latestPayment', 'location'])
        ->where('reference', $reference)
        ->firstOrFail();

    $title = 'Detail Pesanan ' . $order->reference;
    $description = 'Rincian pesanan dari pelanggan.';
    $breadcrumbs = [
        [
            'label' => 'Order',
            'url' => route('cms.order.index')
        ],
        [
            'label' => $order->reference,
            'url' => null
        ],
    ];

    $view->with(compact('title', 'description', 'breadcrumbs', 'order'));
}); ?>

<x-layouts.app :$title>
    <div class="w-full">
        <div class="flex justify-between items-center mb-5">
            <h1 class="text-3xl font-bold">{{ $title }}</h1>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('cms.dashboard') }}" icon="home" />
                @foreach($breadcrumbs as $breadcrumb)
                    @if($breadcrumb['url'])
                        <flux:breadcrumbs.item href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</flux:breadcrumbs.item>
                    @else
                        <flux:breadcrumbs.item>{{ $breadcrumb['label'] }}</flux:breadcrumbs.item>
                    @endif
                @endforeach
            </flux:breadcrumbs>
        </div>
        
        <div class="border-gray-200 mb-6">
            <flux:text>
                {{ $description }}
            </flux:text>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm p-6">
            <flux:heading size="lg" class="mb-4">Informasi Pelanggan</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <span class="text-sm text-gray-500 block">Nama Pelanggan</span>
                    <span class="font-bold">{{ $order->user->name ?? ($order->guest_data['name'] ?? 'Guest') }}</span>
                </div>
                <div>
                    <span class="text-sm text-gray-500 block">Email Pelanggan</span>
                    <span class="font-bold">{{ $order->user->email ?? ($order->guest_data['email'] ?? '-') }}</span>
                </div>
            </div>
            
            <flux:heading size="lg" class="mb-4">Daftar Barang</flux:heading>
            <div class="space-y-4">
                @foreach ($order->orderShops as $orderShop)
                    <div class="border dark:border-zinc-800 rounded-lg p-4">
                        <div class="font-bold mb-2 pb-2 border-b dark:border-zinc-800">
                            {{ $orderShop->shop->name ?? 'Toko' }}
                        </div>
                        @foreach ($orderShop->items as $item)
                            <div class="flex justify-between items-center mt-2">
                                <div>
                                    <h4 class="font-semibold">{{ $item->name }}</h4>
                                    <span class="text-sm text-gray-500">{{ $item->qty }} x {{ numberToCurrency($item->price) }}</span>
                                </div>
                                <div class="font-bold">
                                    {{ numberToCurrency($item->price * $item->qty) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
