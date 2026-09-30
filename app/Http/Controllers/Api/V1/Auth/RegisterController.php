<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Api\V1\Auth\StoreRegisterAction;
use App\Data\Auth\RegistrationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\StoreRegisterRequest;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * Handle the incoming registration request.
     */
    public function store(StoreRegisterRequest $request, StoreRegisterAction $action): JsonResponse
    {
        $user = $action->handle(RegistrationData::fromArray($request->validated()));

        return $this->responseWithCreated([
            'token' => $user->createToken('API Token')->plainTextToken,
            'token_type' => 'bearer',
        ]);
    }
}
