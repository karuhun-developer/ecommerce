<?php

namespace App\Actions\Cms\Management\Menu;

use App\Models\Menu\Menu;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteMenuAction
{
    public function handle(Menu $menu, User $actor): ?bool
    {
        Gate::forUser($actor)->authorize('delete'.Menu::class);

        return $menu->delete();
    }
}
