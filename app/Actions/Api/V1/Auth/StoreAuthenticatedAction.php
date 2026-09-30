<?php

namespace App\Actions\Api\V1\Auth;

use App\Data\Auth\LoginData;
use Illuminate\Contracts\Auth\StatefulGuard;

class StoreAuthenticatedAction
{
    public function handle(LoginData $data, StatefulGuard $guard): bool
    {
        if (! $guard->attempt($data->credentials())) {
            return false;
        }

        $user = $guard->user();
        activity()->performedOn($user)->causedBy($user)->event('Login')->log('Login');

        return true;
    }
}
