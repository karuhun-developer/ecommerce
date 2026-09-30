<?php

namespace App\Actions\Api\V1\Auth;

use App\Data\Auth\EmailData;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class ResetPasswordAction
{
    public function handle(EmailData $data): bool
    {
        $status = Password::sendResetLink(['email' => $data->email]);
        $user = User::query()->where('email', $data->email)->first();

        if ($user !== null) {
            activity()->performedOn($user)->causedBy($user)->event('Reset Password')->log('Reset Password');
        }

        return $status === Password::RESET_LINK_SENT;
    }
}
