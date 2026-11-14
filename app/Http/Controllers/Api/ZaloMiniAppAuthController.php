<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsAsZaloApi;
use App\Http\Controllers\Controller;
use App\Services\Api\ZaloAuthApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZaloMiniAppAuthController extends Controller
{
    use RespondsAsZaloApi;

    public function __construct(private ZaloAuthApiService $service)
    {
    }

    public function login(Request $request): JsonResponse
    {
        if ($error = $this->service->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        $json = $request->json()->all();

        if ($json !== []) {
            $login = trim((string) ($json['login'] ?? $json['phone'] ?? $json['email'] ?? $json['username'] ?? ''));
            $password = (string) ($json['password'] ?? '');
        } else {
            $login = trim((string) ($request->input('login') ?? $request->input('phone') ?? $request->input('email') ?? $request->input('username') ?? ''));
            $password = (string) ($request->input('password') ?? '');
        }

        return $this->fromResult($this->service->loginStudent($login, $password));
    }

    public function me(Request $request): JsonResponse
    {
        if ($error = $this->service->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        $userId = (int) $request->attributes->get('apiUserId', 0);

        if ($userId <= 0) {
            return $this->failApi('UNAUTHORIZED', 'Thiếu hoặc token không hợp lệ.', 401);
        }

        $result = $this->service->me($userId);

        if (isset($result['error'])) {
            return $this->fromResult($result);
        }

        $role = (string) ($result['payload']['user']['role'] ?? '');

        if ($role !== 'student') {
            return $this->failApi('FORBIDDEN', 'API này chỉ dành cho tài khoản học viên.', 403);
        }

        return $this->ok($result['payload']);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($error = $this->service->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        $token = $this->resolveBearerToken($request);

        if ($token === '') {
            return $this->failApi('UNAUTHORIZED', 'Thiếu header Authorization: Bearer <token>.', 401);
        }

        $this->service->logout($token);

        return $this->ok(['message' => 'Đã đăng xuất.']);
    }

    private function resolveBearerToken(Request $request): string
    {
        $header = trim((string) $request->header('Authorization', ''));

        if ($header !== '' && preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        return trim((string) $request->header('X-Auth-Token', ''));
    }
}
