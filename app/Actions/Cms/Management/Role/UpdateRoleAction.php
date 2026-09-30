<?php

namespace App\Actions\Cms\Management\Role;

use App\Data\Cms\RoleData;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateRoleAction
{
    public function handle(Role $role, RoleData $data, User $actor): bool
    {
        Gate::forUser($actor)->authorize('update'.Role::class);

        return $role->update($data->attributes());
    }
}
