<?php

use App\Models\Order\Order;
use Illuminate\View\View;
use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;

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

    $title = 'Detail Pesanan ' . $order->reference;
    $description = 'Rincian dan status pesanan Anda dengan kode transaksi ' . $order->reference . ' di ' . config('app.name') . '.';
    $url = url()->current();
    $logo = asset('logo.png');

    SEOMeta::setTitle($title . ' | ' . config('app.name'));
    SEOMeta::setDescription($description);
    SEOMeta::setKeywords(['detail pesanan', 'status pesanan', $order->reference, config('app.name')]);
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
    <div class="bg-gray-50 min-h-screen py-8" x-data>
        <div class="max-w-4xl mx-auto px-4 md:px-6">
            <livewire:ecommerce.order-detail :$order />
        </div>
    </div>
</x-layouts.ecommerce>
