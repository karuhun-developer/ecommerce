<?php

namespace App\Actions\Cms\Management\RolePermission;

use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RevokeAllRolePermissionsAction
{
    public function handle(Role $role, User $actor): void
    {
        Gate::forUser($actor)->authorize('update'.Role::class);
        $role->syncPermissions([]);
    }
}
