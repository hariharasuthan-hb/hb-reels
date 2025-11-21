<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(string $message, array $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => !empty($data) ? $data : (object) [],
        ], $code);
    }

    public static function error(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => !empty($errors) ? $errors : (object) [],
        ], $code);
    }

    public static function token(string $message, string $token, array $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'token' => $token,
            'data' => !empty($data) ? $data : (object) [],
        ], $code);
    }
}


