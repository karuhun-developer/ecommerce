<?php

namespace App\Http\Controllers\Api\V1\Callback;

use App\Actions\Api\V1\Callback\HandleBiteshipCallbackAction;
use App\Http\Controllers\Controller;
use App\Traits\WithReturnResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BiteshipController extends Controller
{
    use WithReturnResponse;

    public function callback(Request $request, HandleBiteshipCallbackAction $action)
    {
        $headerKey = config('services.biteship.webhook.header_key');
        $headerSecret = config('services.biteship.webhook.header_secret');

        if ($headerKey && $headerSecret) {
            $providedSecret = $request->header($headerKey);

            if ($providedSecret !== $headerSecret) {
                Log::warning('Invalid Biteship Webhook Signature', [
                    'ip' => $request->ip(),
                ]);

                return $this->responseWithError('Unauthorized', 401);
            }
        }

        try {
            $action->handle($request->all());
        } catch (\Exception $e) {
            Log::error('Biteship Callback Error', [
                'error' => $e->getMessage(),
            ]);

            $status = $e->getCode() ?: 400;
            // Map common HTTP status codes, default to 400 for unknown exception codes
            if (! in_array($status, [400, 401, 403, 404, 500])) {
                $status = 400;
            }

            return $this->responseWithError($e->getMessage(), $status);
        }

        return $this->responseWithSuccess('Callback received');
    }
}
