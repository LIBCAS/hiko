<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait for API response handling.
 * Provides methods to return standardized success and error responses.
 */
trait ApiResponseTrait
{
    protected function success(int $code = 200, string|null $message = null, $data = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? __('api.RESPONSE_SUCCESS'),
            'data' => $data,
        ], $code);
    }

    public function fail(int $code = 400, string|null $message = null, $context = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? __('api.RESPONSE_FAIL'),
            'context' => $context,
        ], $code);
    }
}
