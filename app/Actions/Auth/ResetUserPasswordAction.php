<?php

namespace App\Actions\Auth;

use App\Data\Auth\PasswordData;
use App\Models\User;

class ResetUserPasswordAction
{
    public function handle(User $user, PasswordData $data): void
    {
        $user->forceFill(['password' => $data->password])->save();
    }
}
