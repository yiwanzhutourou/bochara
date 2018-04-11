<?php

namespace App\Utils;

use App\Http\Controllers\Api\Exceptions\Exception;
use Illuminate\Http\JsonResponse;

class ErrorUtils {

    public static function apiErrorResponse($jsonData, $errCode): JsonResponse {
        return response()->json($jsonData, $errCode);
    }

    public static function errorResponse(string $hint,
                                         $errCode = Exception::PARAMETERS_MISSING): JsonResponse {
        return response()->json([
            'error' => $errCode,
            'message' => $hint,
            'ext' => '',
        ], 422);
    }
}