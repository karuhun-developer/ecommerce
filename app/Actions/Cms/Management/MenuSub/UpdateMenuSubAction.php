<?php

namespace App\Actions\Cms\Management\MenuSub;

use App\Data\Cms\MenuSubData;
use App\Models\Menu\MenuSub;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateMenuSubAction
{
    public function handle(MenuSub $menuSub, MenuSubData $data, User $actor): bool
    {
        Gate::forUser($actor)->authorize('update'.MenuSub::class);

        return $menuSub->update($data->attributes());
    }
}
