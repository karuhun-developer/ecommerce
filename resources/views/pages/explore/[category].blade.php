<?php

use App\Models\Product\ProductCategory;
use Illuminate\View\View;
use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;

name('explore.category');

render(function (View $view, string $category) {
    $catModel = ProductCategory::where('slug', $category)->first();
    $catName = $catModel ? $catModel->name : ucfirst(str_replace('-', ' ', $category));

    $title = 'Jual ' . $catName . ' Berkualitas';
    $description = 'Koleksi lengkap ' . $catName . ' dengan harga terbaik dan garansi resmi hanya di ' . config('app.name') . '.';
    $url = url()->current();
    $logo = asset('logo.png');

    SEOMeta::setTitle($title . ' | ' . config('app.name'));
    SEOMeta::setDescription($description);
    SEOMeta::setKeywords([$catName, 'jual ' . $catName, 'harga ' . $catName, config('app.name')]);
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

    $view->with(compact('title', 'category'));
});

?>

<x-layouts.ecommerce :title="$title . ' | ' . config('app.name')">
    @section('seo')
        {!! SEO::generate() !!}
    @endsection

    <livewire:ecommerce.explore :categorySlug="$category" />
</x-layouts.ecommerce>
