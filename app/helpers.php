<?php

use App\Enums\CommonStatusEnum;
use App\Models\Menu\Menu;
use Illuminate\Support\Facades\Cache;

function numberToCurrency($value)
{
    return number_format($value, 0, ',', '.');
}

function currencyToNumber($value)
{
    return (int) str_replace('.', '', $value);
}

function getMenus()
{
    $roles = auth()->user()->roles->pluck('id')->toArray();

    return Cache::remember('menu:'.implode(',', $roles), now()->addDay(), fn () => Menu::query()
        ->with('subMenu')
        ->whereIn('role_id', $roles)
        ->where('status', CommonStatusEnum::ACTIVE)
        ->orderBy('order', 'asc')
        ->get()
    );
}
