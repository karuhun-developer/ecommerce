<?php

namespace App\Http\Controllers\Api\V1\Callback;

use App\Actions\Api\V1\Callback\HandleMidtransCallbackAction;
use App\Data\Callbacks\MidtransCallbackData;
use App\Http\Controllers\Controller;
use App\Traits\WithReturnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransController extends Controller
{
    use WithReturnResponse;

    public function callback(Request $request, HandleMidtransCallbackAction $action): JsonResponse
    {
        try {
            $action->handle(MidtransCallbackData::fromArray($request->all()));
        } catch (\Throwable $exception) {
            if (in_array($exception->getCode(), [400, 403, 404], true)) {
                return $this->responseWithError($exception->getMessage(), $exception->getCode());
            }

            report($exception);

            return $this->responseWithError('Callback could not be processed.', 500);
        }

        return $this->responseWithSuccess('Callback received');
    }
}
