<?php

use App\Models\Product\Product;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.product.edit');

// Page title and breadcrumbs
render(function (View $view) {
    $title = 'Edit Product';
    $description = 'Edit product details, flat items, and media collections.';
    
    $product = Product::findOrFail(request('product_id'));

    $breadcrumbs = [
        [
            'label' => 'Product',
            'url' => route('cms.product')
        ],
        [
            'label' => 'Edit',
            'url' => null,
        ],
    ];

    $view->with(compact('title', 'description', 'breadcrumbs', 'product'));
}); ?>

<x-layouts.app :$title>
    <div class="w-full">
        <div class="flex justify-between items-center mb-5">
            <div class="flex items-center gap-4">
                <flux:button
                    href="{{ route('cms.product') }}"
                    size="sm"
                    variant="primary"
                    icon="arrow-left"
                    wire:navigate
                />
                <h1 class="text-3xl font-bold">{{ $title }}</h1>
            </div>
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
        
        <livewire:cms.product.edit :$product />
    </div>
</x-layouts.app>
