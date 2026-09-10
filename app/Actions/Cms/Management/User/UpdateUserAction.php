<?php

namespace App\Actions\Cms\Management\User;

use App\Models\User;

class UpdateUserAction
{
    /**
     * Handle the action.
     */
    public function handle(User $user, array $data): bool
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);

        $updated = $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $updated;
    }
}
