<?php

namespace App\Actions\Api\V1\Auth;

use App\Models\User;

class ResendAuthenticatedAction
{
    /**
     * Handle the action.
     */
    public function handle(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        activity()->performedOn($user)->causedBy($user)->log('Resend Verification Email');

        return true;
    }
}
