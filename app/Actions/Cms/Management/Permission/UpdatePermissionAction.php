<?php

namespace App\Actions\Cms\Management\Permission;

use App\Data\Cms\PermissionData;
use App\Models\Spatie\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdatePermissionAction
{
    public function handle(Permission $permission, PermissionData $data, User $actor): bool
    {
        Gate::forUser($actor)->authorize('update'.Permission::class);

        return $permission->update($data->attributes());
    }
}
