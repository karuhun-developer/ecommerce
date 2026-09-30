<?php

namespace App\Actions\Cms\Management\Menu;

use App\Data\Cms\MenuData;
use App\Models\Menu\Menu;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StoreMenuAction
{
    public function handle(MenuData $data, User $actor): Menu
    {
        Gate::forUser($actor)->authorize('create'.Menu::class);

        return Menu::create($data->attributes());
    }
}
