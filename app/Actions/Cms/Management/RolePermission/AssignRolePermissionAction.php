<?php

namespace App\Actions\Cms\Management\RolePermission;

use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AssignRolePermissionAction
{
    public function handle(Role $role, Permission $permission, User $actor): void
    {
        Gate::forUser($actor)->authorize('update'.Role::class);
        abort_unless($permission->guard_name === $role->guard_name, 422);

        $role->givePermissionTo($permission);

        activity()->causedBy($actor)->performedOn($role)
            ->withProperties(['permission' => $permission->name])
            ->event('check-permission')
            ->log('Add permission');
    }
}
