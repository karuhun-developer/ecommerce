<?php

namespace App\Actions\Cms\Management\MenuSub;

use App\Models\Menu\MenuSub;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteMenuSubAction
{
    public function handle(MenuSub $menuSub, User $actor): ?bool
    {
        Gate::forUser($actor)->authorize('delete'.MenuSub::class);

        return $menuSub->delete();
    }
}
