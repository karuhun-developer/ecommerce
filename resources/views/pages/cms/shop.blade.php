<?php

use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.shop');

// Page title and breadcrumbs
render(function (View $view) {
    $title = 'Shop Management';
    $description = 'Manage your shop(s) and location details.';
    $breadcrumbs = [
        [
            'label' => 'Shop',
            'url' => '#'
        ],
        [
            'label' => 'Management',
            'url' => null
        ],
    ];

    $view->with(compact('title', 'description', 'breadcrumbs'));
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
        
        @if(isSingleShop() || auth()->user()->isShopOwner())
            @php
                $shop = auth()->user()->isShopOwner() ? \App\Models\Shop\Shop::where('user_id', auth()->id())->first() : \App\Models\Shop\Shop::first();
            @endphp
            <livewire:cms.shop.single :$shop />
        @else
            <livewire:cms.shop.table />
        @endif
    </div>
</x-layouts.app>
