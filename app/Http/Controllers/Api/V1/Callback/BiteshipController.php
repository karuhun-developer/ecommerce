<?php

namespace App\Http\Controllers\Api\V1\Callback;

use App\Actions\Api\V1\Callback\HandleBiteshipCallbackAction;
use App\Http\Controllers\Controller;
use App\Traits\WithReturnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BiteshipController extends Controller
{
    use WithReturnResponse;

    public function callback(Request $request, HandleBiteshipCallbackAction $action): JsonResponse
    {
        $headerKey = config('services.biteship.webhook.header_key');
        $headerSecret = config('services.biteship.webhook.header_secret');

        if (blank($headerKey) || blank($headerSecret)) {
            Log::warning('Biteship webhook authentication rejected', [
                'reason' => 'configuration_missing',
            ]);

            return $this->responseWithError('Unauthorized', 401);
        }

        $providedSecret = $request->header((string) $headerKey);

        if (! is_string($providedSecret) || blank($providedSecret) || ! hash_equals((string) $headerSecret, $providedSecret)) {
            Log::warning('Biteship webhook authentication rejected', [
                'reason' => blank($providedSecret) ? 'header_missing' : 'signature_invalid',
            ]);

            return $this->responseWithError('Unauthorized', 401);
        }

        try {
            $action->handle($request->all());
        } catch (\Throwable $e) {
            Log::error('Biteship Callback Error', [
                'exception' => $e::class,
                'code' => $e->getCode(),
            ]);

            $status = $e->getCode() ?: 400;
            if (! in_array($status, [400, 401, 403, 404, 500])) {
                $status = 400;
            }

            return $this->responseWithError($e->getMessage(), $status);
        }

        return $this->responseWithSuccess('Callback received');
    }
}
