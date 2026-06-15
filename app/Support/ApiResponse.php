<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ApiResponse
{
    public static function success(mixed $data = [], string $message = 'ok', int $status = 200): JsonResponse
    {
        return response()->json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
            'request_id' => (string) Str::uuid(),
        ], $status);
    }

    public static function error(int $code, string $message, int $status = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'request_id' => (string) Str::uuid(),
        ], $status);
    }
}
