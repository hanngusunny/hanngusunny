<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateZaloApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->resolveBearerToken($request);

        if ($token === '') {
            return $this->unauthorized('Thiếu header Authorization: Bearer <token>.');
        }

        $apiKey = trim((string) config('api.key', config('api.fixed_key', '')));

        if ($apiKey !== '' && hash_equals($apiKey, $token)) {
            return $this->unauthorized('Đây là API key tích hợp, không phải token đăng nhập người dùng.');
        }

        try {
            $row = ApiToken::findValidByPlainToken($token);
        } catch (Throwable $e) {
            report($e);

            return $this->unauthorized('Không thể xác thực token.');
        }

        if ($row === null) {
            return $this->unauthorized('Token không hợp lệ hoặc đã hết hạn.');
        }

        $request->attributes->set('apiUserId', (int) $row->user_id);

        return $next($request);
    }

    private function resolveBearerToken(Request $request): string
    {
        $header = trim((string) $request->header('Authorization', ''));

        if ($header !== '' && preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        return trim((string) $request->header('X-Auth-Token', ''));
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => $message,
            ],
        ], 401, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
