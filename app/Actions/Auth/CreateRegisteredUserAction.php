<?php

namespace App\Actions\Auth;

use App\Data\Auth\RegistrationData;
use App\Models\User;

class CreateRegisteredUserAction
{
    public function handle(RegistrationData $data): User
    {
        return User::query()->create($data->attributes());
    }
}
