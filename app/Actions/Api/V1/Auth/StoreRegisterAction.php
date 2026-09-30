<?php

namespace App\Actions\Api\V1\Auth;

use App\Actions\Auth\CreateRegisteredUserAction;
use App\Data\Auth\RegistrationData;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

class StoreRegisterAction
{
    public function __construct(private readonly CreateRegisteredUserAction $createUser) {}

    public function handle(RegistrationData $data): User
    {
        $user = $this->createUser->handle($data);

        $user->syncRoles(['user']);

        event(new Registered($user));

        activity()->performedOn($user)->causedBy($user)->event('Register')->log('Register');

        return $user;
    }
}
