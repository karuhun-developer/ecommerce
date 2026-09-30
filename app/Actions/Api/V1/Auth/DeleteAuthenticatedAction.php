<?php

namespace App\Actions\Api\V1\Auth;

use App\Models\User;

class DeleteAuthenticatedAction
{
    /**
     * Handle the action.
     */
    public function handle(User $user): void
    {
        activity()->performedOn($user)->causedBy($user)->event('Login')->log('Logout');

        $user->tokens()->delete();
    }
}
