<?php

use App\Models\Order\Order;
use Illuminate\View\View;
use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;

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

    $title = 'Pembayaran ' . $order->reference;
    $description = 'Selesaikan pembayaran untuk pesanan ' . $order->reference . ' Anda di ' . config('app.name') . '.';
    $url = url()->current();
    $logo = asset('logo.png');

    SEOMeta::setTitle($title . ' | ' . config('app.name'));
    SEOMeta::setDescription($description);
    SEOMeta::setKeywords(['pembayaran pesanan', 'bayar pesanan', $order->reference, config('app.name')]);
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

    return $view->with(compact('order', 'title'));
}); ?>

<x-layouts.ecommerce :title="$title . ' | ' . config('app.name')">
    @section('seo')
        {!! SEO::generate() !!}
        <meta name="robots" content="noindex, nofollow">
    @endsection

    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4 md:px-6">
            <livewire:ecommerce.payment :$order />
        </div>
    </div>
</x-layouts.ecommerce>
