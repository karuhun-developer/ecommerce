<?php

namespace App\Actions\Cms\Management\User;

use App\Data\Cms\UserData;
use App\Models\User;

class UpdateUserAction
{
    public function handle(User $user, UserData $data, User $actor): bool
    {
        abort_unless($actor->hasRole('superadmin'), 403);

        $updated = $user->update([
            'name' => $data->name,
            'email' => $data->email,
        ]);

        if ($data->role !== null) {
            $user->syncRoles([$data->role]);
        }

        return $updated;
    }
}
