<?php

namespace App\Actions\Cms\Management\Permission;

use App\Data\Cms\PermissionData;
use App\Models\Spatie\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StorePermissionAction
{
    public function handle(PermissionData $data, User $actor): Permission
    {
        Gate::forUser($actor)->authorize('create'.Permission::class);

        return Permission::create($data->attributes());
    }
}
