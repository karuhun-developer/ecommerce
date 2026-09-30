<?php

namespace App\Actions\Cms\Management\RolePermission;

use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AssignAllRolePermissionsAction
{
    public function handle(Role $role, User $actor): void
    {
        Gate::forUser($actor)->authorize('update'.Role::class);
        $role->syncPermissions(Permission::query()->where('guard_name', $role->guard_name)->get());
    }
}
