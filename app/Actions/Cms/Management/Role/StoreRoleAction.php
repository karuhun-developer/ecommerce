<?php

namespace App\Actions\Cms\Management\Role;

use App\Data\Cms\RoleData;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StoreRoleAction
{
    public function handle(RoleData $data, User $actor): Role
    {
        Gate::forUser($actor)->authorize('create'.Role::class);

        return Role::create($data->attributes());
    }
}
