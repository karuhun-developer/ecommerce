<?php

namespace App\Actions\Cms\Management\Role;

use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteRoleAction
{
    public function handle(Role $role, User $actor): ?bool
    {
        Gate::forUser($actor)->authorize('delete'.Role::class);

        return $role->delete();
    }
}
