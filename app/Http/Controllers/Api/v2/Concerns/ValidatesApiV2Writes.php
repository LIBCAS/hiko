<?php

namespace App\Http\Controllers\Api\v2\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ValidatesApiV2Writes
{
    protected function rejectUnknownFields(Request $request, array $allowedFields): ?JsonResponse
    {
        $unknownFields = array_values(array_diff(array_keys($request->all()), $allowedFields));

        if ($unknownFields === []) {
            return null;
        }

        $errors = [];
        foreach ($unknownFields as $field) {
            $errors[$field] = [
                "The {$field} field is not supported by this endpoint. Use client_meta for custom app data.",
            ];
        }

        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $errors,
        ], 422);
    }
}
