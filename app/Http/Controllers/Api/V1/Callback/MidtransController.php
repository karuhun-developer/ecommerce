<?php

namespace App\Http\Controllers\Api\V1\Callback;

use App\Actions\Api\V1\Callback\HandleMidtransCallbackAction;
use App\Http\Controllers\Controller;
use App\Traits\WithReturnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransController extends Controller
{
    use WithReturnResponse;

    public function callback(Request $request, HandleMidtransCallbackAction $action): JsonResponse
    {
        try {
            $action->handle($request->all());
        } catch (\Throwable $e) {
            Log::error('Midtrans Callback Error', [
                'exception' => $e::class,
                'code' => $e->getCode(),
            ]);

            $status = in_array($e->getCode(), [400, 403, 404], true) ? $e->getCode() : 400;

            return $this->responseWithError($e->getMessage(), $status);
        }

        return $this->responseWithSuccess('Callback received');
    }
}
