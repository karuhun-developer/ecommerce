<?php

namespace App\Actions\Cms\Management\User;

use App\Models\User;

class ValidateUserEmailAction
{
    /**
     * Handle the action.
     */
    public function handle(User $user): bool
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);

        return $user->markEmailAsVerified();
    }
}
