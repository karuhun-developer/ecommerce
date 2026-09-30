<?php

namespace App\Actions\Cms\Management\Menu;

use App\Data\Cms\MenuData;
use App\Models\Menu\Menu;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateMenuAction
{
    public function handle(Menu $menu, MenuData $data, User $actor): bool
    {
        Gate::forUser($actor)->authorize('update'.Menu::class);

        return $menu->update($data->attributes());
    }
}
