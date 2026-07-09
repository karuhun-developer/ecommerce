<?php

use App\Models\Order\Order;
use Illuminate\View\View;
use function Laravel\Folio\{render, name};

name('payment.show');

render(function (View $view, string $reference) {
    $order = Order::where('reference', $reference);

    // Check if the user is authenticated and filter the order accordingly
    if (auth()->check()) {
        $order->where('user_id', auth()->id());
    } else {
        $order->whereNull('user_id');
    }

    $order = $order->firstOrFail();

    return $view->with(compact('order'));
}); ?>

<x-layouts.ecommerce>
    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4 md:px-6">
            <livewire:ecommerce.payment :$order />
        </div>
    </div>
</x-layouts.ecommerce>
