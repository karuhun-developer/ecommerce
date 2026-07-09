<?php

use App\Models\Order\Order;
use Illuminate\View\View;
use function Laravel\Folio\{render, name};

name('orders.detail');

render(function (View $view, string $reference) {
    $order = Order::where('reference', $reference)
        ->with(['orderShops.shop', 'orderShops.items', 'orderShops.latestShipment', 'payments', 'location']);

    if (auth()->check()) {
        $order->where('user_id', auth()->id());
    } else {
        $order->whereNull('user_id');
    }

    $order = $order->firstOrFail();

    return $view->with(compact('order'));
}); ?>
<x-layouts.ecommerce>
    <div class="bg-gray-50 min-h-screen py-8" x-data>
        <div class="max-w-4xl mx-auto px-4 md:px-6">
            <livewire:ecommerce.order-detail :$order />
        </div>
    </div>
</x-layouts.ecommerce>
