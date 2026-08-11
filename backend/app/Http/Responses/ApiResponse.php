<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a successful JSON payload.
     *
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(?array $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = ['success' => true];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return an error JSON payload.
     *
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(string $message, int $status = 422, ?array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
