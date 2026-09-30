<?php

namespace App\Http\Controllers\Api\V1\Callback;

use App\Actions\Api\V1\Callback\HandlePaywuzCallbackAction;
use App\Data\Callbacks\PaywuzCallbackData;
use App\Http\Controllers\Controller;
use App\Traits\WithReturnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaywuzController extends Controller
{
    use WithReturnResponse;

    public function callback(Request $request, HandlePaywuzCallbackAction $action): JsonResponse
    {
        try {
            $action->handle(new PaywuzCallbackData(
                rawBody: $request->getContent(),
                signature: $request->header('X-Paywuz-Signature'),
                event: $request->header('X-Paywuz-Event'),
                deliveryId: $request->header('X-Paywuz-Delivery'),
            ));
        } catch (\Throwable $exception) {
            if (in_array($exception->getCode(), [400, 401, 403, 404], true)) {
                return $this->responseWithError($exception->getMessage(), $exception->getCode());
            }

            report($exception);

            return $this->responseWithError('Callback could not be processed.', 500);
        }

        return $this->responseWithSuccess('Callback received');
    }
}
