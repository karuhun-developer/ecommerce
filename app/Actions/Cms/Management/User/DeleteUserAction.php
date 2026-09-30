<?php

namespace App\Actions\Cms\Management\User;

use App\Models\User;

class DeleteUserAction
{
    public function handle(User $user, User $actor): ?bool
    {
        abort_unless($actor->hasRole('superadmin'), 403);

        return $user->delete();
    }
}
