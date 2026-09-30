<?php

namespace App\Actions\Cms\Management\Permission;

use App\Models\Spatie\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeletePermissionAction
{
    public function handle(Permission $permission, User $actor): ?bool
    {
        Gate::forUser($actor)->authorize('delete'.Permission::class);

        return $permission->delete();
    }
}
