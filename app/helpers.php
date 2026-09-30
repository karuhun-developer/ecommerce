<?php

use App\Enums\CommonStatusEnum;
use App\Models\Menu\Menu;
use App\Models\Shop\Shop;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

function numberToCurrency($value)
{
    return number_format($value, 0, ',', '.');
}

function currencyToNumber($value)
{
    return (int) str_replace('.', '', $value);
}

function getMenus(): Collection
{
    $roles = auth()->user()->roles->pluck('id')->toArray();

    return Cache::remember('menu:'.implode(',', $roles), now()->addDay(), fn () => Menu::query()
        ->with('subMenu')
        ->whereIn('role_id', $roles)
        ->where('status', CommonStatusEnum::ACTIVE)
        ->orderBy('order', 'asc')
        ->get()
    )->unique(fn (Menu $menu): string => filled($menu->url) && $menu->url !== '#' && $menu->subMenu->isEmpty()
        ? 'link:'.$menu->url
        : 'menu:'.$menu->id
    )->values();
}

function getDefaultShop()
{
    return Cache::remember('default:shop', now()->addDay(), function () {
        return Shop::with('media')->first();
    });
}

function isSingleShop()
{
    return config('shop.single_shop', true);
}
