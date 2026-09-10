<?php

namespace App\Actions\Cms\Management\User;

use App\Models\User;

class StoreUserAction
{
    /**
     * Handle the action.
     */
    public function handle(array $data): User
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user;
    }
}
