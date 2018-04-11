<?php

namespace App\Utils;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ErrorUtils {

    public static function apiErrorResponse($jsonData, $errCode): JsonResponse {
        return response()->json($jsonData, $errCode);
    }

    public static function invalidParameterResponse(string $hint): Response {
        return response($hint, 422);
    }
}