<?php

namespace App\Actions\Cms\Management\User;

use App\Data\Cms\UserData;
use App\Models\User;

class StoreUserAction
{
    public function handle(UserData $data, User $actor): User
    {
        abort_unless($actor->hasRole('superadmin'), 403);

        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);

        if ($data->role !== null) {
            $user->syncRoles([$data->role]);
        }

        return $user;
    }
}
