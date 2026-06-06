<?php

namespace App\Actions\Api\V1\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;

class StoreRegisterAction
{
    /**
     * Handle the action.
     */
    public function handle(array $data): User
    {
        $user = User::create($data);

        // Assign as user by default
        $user->syncRoles(['user']);

        // Confirm email if needed
        event(new Registered($user));

        // Save activity
        activity()->performedOn($user)->causedBy($user)->event('Register')->log('Register');

        return $user;
    }
}
