<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Api\V1\Auth\ResetPasswordAction;
use App\Data\Auth\EmailData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    /**
     * Handle the incoming password reset request.
     */
    public function store(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        if (! $action->handle(EmailData::fromArray($request->validated()))) {
            return $this->responseWithError('Unable to send password reset email.', 422);
        }

        return $this->responseWithSuccess('Password reset link sent successfully.');
    }
}
