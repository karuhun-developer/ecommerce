<?php

namespace App\Actions\Cms\Management\MenuSub;

use App\Data\Cms\MenuSubData;
use App\Models\Menu\MenuSub;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StoreMenuSubAction
{
    public function handle(MenuSubData $data, User $actor): MenuSub
    {
        Gate::forUser($actor)->authorize('create'.MenuSub::class);

        return MenuSub::create($data->attributes());
    }
}
