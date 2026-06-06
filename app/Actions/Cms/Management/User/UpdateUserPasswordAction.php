<?php

namespace App\Actions\Cms\Management\User;

use App\Models\User;

class UpdateUserPasswordAction
{
    /**
     * Handle the action.
     */
    public function handle(User $user, string $password): bool
    {
        return $user->update([
            'password' => bcrypt($password),
        ]);
    }
}
