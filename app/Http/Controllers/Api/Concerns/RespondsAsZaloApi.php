<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsAsZaloApi
{
    protected function ok(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<string, mixed>  $error
     */
    protected function failApi(string $code, string $message, int $status = 400, array $error = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => array_merge([
                'code' => $code,
                'message' => $message,
            ], $error),
        ], $status, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array{payload?: array<string, mixed>, error?: array<string, mixed>}  $result
     */
    protected function fromResult(array $result): JsonResponse
    {
        if (isset($result['error']) && is_array($result['error'])) {
            $err = $result['error'];

            return $this->failApi(
                (string) ($err['code'] ?? 'LOAD_FAILED'),
                (string) ($err['message'] ?? 'Không thể xử lý yêu cầu.'),
                (int) ($err['status'] ?? 400)
            );
        }

        return $this->ok($result['payload'] ?? []);
    }
}
