<?php

namespace App\Actions\Api\V1\Auth;

use App\Data\Auth\ProfileData;
use App\Models\User;
use App\Traits\WithMediaCollection;
use Illuminate\Support\Facades\Cache;

class UpdateAuthenticatedAction
{
    use WithMediaCollection;

    /**
     * Handle the action.
     */
    public function handle(User $user, ProfileData $data): User
    {
        $user->name = $data->name;
        $user->email = $data->email;
        $user->phone = $data->phone ?? $user->phone;

        if ($data->password ?? false) {
            $user->password = bcrypt($data->password);
        }

        if ($data->image !== null) {
            $this->saveMedia(
                model: $user,
                file: $data->image,
                collection: 'image',
            );
        }

        $user->save();

        Cache::forget('me:user'.$user->id);

        activity()->performedOn($user)->causedBy($user)->log('Update Profile');

        return $user->refresh();
    }
}
