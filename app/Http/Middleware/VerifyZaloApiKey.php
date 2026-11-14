<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyZaloApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (strtoupper($request->method()) === 'OPTIONS') {
            return $next($request);
        }

        $key = trim((string) config('api.key', ''));

        if ($key === '') {
            $key = trim((string) config('api.fixed_key', ''));
        }

        if ($key === '') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'API_NOT_CONFIGURED',
                    'message' => 'API chưa được cấu hình. Thiết lập API_KEY trong file .env.',
                ],
            ], 503, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $provided = $this->resolveProvidedKey($request);

        if ($provided === '') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Thiếu header X-API-Key hoặc Authorization: Bearer <API_KEY>.',
                ],
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (! hash_equals($key, $provided)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'API key không đúng. Giá trị phải khớp API_KEY trong .env trên server đang xử lý request.',
                ],
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $next($request);
    }

    private function resolveProvidedKey(Request $request): string
    {
        $provided = trim((string) $request->header('X-API-Key', ''));

        if ($provided === '') {
            $auth = (string) $request->header('Authorization', '');

            if (str_starts_with($auth, 'Bearer ')) {
                $provided = trim(substr($auth, 7));
            }
        }

        if ($provided !== '') {
            return $provided;
        }

        foreach (['HTTP_X_API_KEY', 'REDIRECT_HTTP_X_API_KEY'] as $serverKey) {
            $fromServer = trim((string) $request->server($serverKey, ''));

            if ($fromServer !== '') {
                return $fromServer;
            }
        }

        $authServer = trim((string) (
            $request->server('HTTP_AUTHORIZATION')
            ?: $request->server('REDIRECT_HTTP_AUTHORIZATION')
            ?: ''
        ));

        if (str_starts_with($authServer, 'Bearer ')) {
            return trim(substr($authServer, 7));
        }

        return '';
    }
}
