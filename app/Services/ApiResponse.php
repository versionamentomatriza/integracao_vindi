<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($message, $data = null): JsonResponse
    {
        return response()->json([
            'status_code' => 200,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    public static function error($message): JsonResponse
    {
        return response()->json([
            'status_code' => 500,
            'message' => $message
        ], 500);
    }

    public static function unauthorized(): JsonResponse
    {
        return response()->json([
            'status_code' => 401,
            'message' => 'Unauthorized access.'
        ], 401);
    }

    public static function notFound($message): JsonResponse
    {
        return response()->json([
            'status_code' => 404,
            'message' => $message
        ], 404);
    }
}
