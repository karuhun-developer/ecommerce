<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Api\V1\Auth\DeleteAuthenticatedAction;
use App\Actions\Api\V1\Auth\ResendAuthenticatedAction;
use App\Actions\Api\V1\Auth\StoreAuthenticatedAction;
use App\Actions\Api\V1\Auth\UpdateAuthenticatedAction;
use App\Data\Auth\LoginData;
use App\Data\Auth\ProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\StoreAuthenticatedRequest;
use App\Http\Requests\Api\V1\Auth\UpdateAuthenticatedRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuthenticatedController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuthenticatedRequest $request, StoreAuthenticatedAction $action): JsonResponse
    {
        if (! $action->handle(LoginData::fromArray($request->validated()), Auth::guard())) {
            return $this->responseWithError('Your credentials are incorrect', 422);
        }

        return $this->responseWithSuccess([
            'token' => auth()->user()->createToken('API Token')->plainTextToken,
            'token_type' => 'bearer',
        ]);
    }

    /**
     * Display the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $ttl = now()->addMinutes(10);

        // If force refresh
        if ($request->has('forceRefresh') && $request?->forceRefresh) {
            Cache::forget('me:user'.$request->user()->id);
        }

        $user = Cache::remember('me:user'.$request->user()->id, $ttl, function () use ($request) {
            $user = $request->user()->load('roles', 'media');
            $user->image = $user->getFirstMediaUrl('image');
            $user->makeHidden('media');

            return $user;
        });

        return $this->responseWithSuccess($user);
    }

    /**
     * Update the authenticated user.
     */
    public function update(UpdateAuthenticatedRequest $request, UpdateAuthenticatedAction $action): JsonResponse
    {
        $user = $action->handle($request->user(), ProfileData::fromArray($request->validated()));

        return $this->responseWithSuccess($user);
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request, ResendAuthenticatedAction $action): JsonResponse
    {
        if (! $action->handle($request->user())) {
            return $this->responseWithError('Email is already verified', 400);
        }

        return $this->responseWithSuccess(message: 'Verification email resent');
    }

    /**
     * Logout the authenticated user.
     */
    public function delete(Request $request, DeleteAuthenticatedAction $action): JsonResponse
    {
        $action->handle($request->user());

        return $this->responseWithSuccess(message: 'Successfully logged out');
    }
}
