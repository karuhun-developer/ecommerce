<?php

namespace App\Actions\Cms\Management\User;

use App\Data\Auth\PasswordData;
use App\Models\User;

class UpdateUserPasswordAction
{
    public function handle(User $user, PasswordData $data, User $actor): bool
    {
        abort_unless($actor->hasRole('superadmin'), 403);

        return $user->update([
            'password' => $data->password,
        ]);
    }
}
