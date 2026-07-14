<?php

use App\Models\Order\OrderShop;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.order.show');

render(function (View $view, $id) {
    // For now we just load the order shop
    $orderShop = OrderShop::with(['order', 'shop', 'items.productFlat'])
        ->where('id', $id)
        ->firstOrFail();

    if (!isSingleShop() && auth()->user()->hasRole('shopowner')) {
        if ($orderShop->shop->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order.');
        }
    }

    $title = 'Detail Pesanan ' . $orderShop->order->reference;
    $description = 'Rincian pesanan dari pelanggan.';
    $breadcrumbs = [
        [
            'label' => 'Order',
            'url' => route('cms.order.index')
        ],
        [
            'label' => $orderShop->order->reference,
            'url' => null
        ],
    ];

    $view->with(compact('title', 'description', 'breadcrumbs', 'orderShop', 'id'));
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

        <livewire:cms.order.detail :orderShopId="$id" />
    </div>
</x-layouts.app>
