<?php

use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;
use Illuminate\View\View;

name('explore.index');

render(function (View $view) {
    $title = 'Jelajahi Produk Terbaik';
    $description = 'Temukan ribuan produk pilihan dari berbagai kategori dengan harga menarik di ' . config('app.name') . '.';
    $url = url()->current();
    $logo = asset('logo.png');

    SEOMeta::setTitle($title . ' | ' . config('app.name'));
    SEOMeta::setDescription($description);
    SEOMeta::setKeywords(['jelajahi produk', 'katalog produk', 'belanja online', config('app.name')]);
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

    <livewire:ecommerce.explore />
</x-layouts.ecommerce>
