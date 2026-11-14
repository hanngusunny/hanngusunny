<?php

namespace App\Services\Api;

use App\Models\ApiToken;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ZaloAuthApiService
{
    public function __construct(private ZaloApiSupport $api)
    {
    }

    /**
     * @return array{code: string, message: string, status: int}|null
     */
    public function ensureReady(): ?array
    {
        return $this->api->ensureTables(
            ['users', 'api_tokens'],
            'Bảng api_tokens chưa tồn tại. Chạy migration create_api_tokens_table trên server.'
        );
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function loginStudent(string $login, string $password): array
    {
        $login = trim($login);
        $password = (string) $password;

        if ($login === '' || strlen($login) < 3) {
            return ['error' => $this->api->error('VALIDATION_ERROR', 'Trường login (SĐT/email/tên đăng nhập) không hợp lệ.', 422)];
        }

        if ($password === '' || strlen($password) < 6) {
            return ['error' => $this->api->error('VALIDATION_ERROR', 'Mật khẩu phải có ít nhất 6 ký tự.', 422)];
        }

        try {
            $user = User::findByLogin($login);
        } catch (Throwable $e) {
            report($e);

            return ['error' => $this->api->error('DATABASE_ERROR', 'Không thể kết nối database.', 503)];
        }

        if ($user === null || ! Auth::getProvider()->validateCredentials($user, ['password' => $password])) {
            return ['error' => $this->api->error('INVALID_CREDENTIALS', 'Số điện thoại/email hoặc mật khẩu không đúng.', 401)];
        }

        if ((string) $user->role !== UserRole::STUDENT) {
            return ['error' => $this->api->error('FORBIDDEN', 'API này chỉ dành cho tài khoản học viên.', 403)];
        }

        if ((string) $user->status !== 'active') {
            return ['error' => $this->api->error('USER_INACTIVE', 'Tài khoản đã bị khóa hoặc không hoạt động.', 403)];
        }

        try {
            $user->forceFill(['last_login_at' => now()])->save();
        } catch (Throwable $e) {
            report($e);
        }

        $issued = ApiToken::issueForUser((int) $user->id, $this->api->tokenTtlDays());

        return [
            'payload' => [
                'token' => $issued['token'],
                'token_type' => 'Bearer',
                'expires_at' => $this->api->formatIso8601($issued['expires_at']),
                'user' => $this->api->formatUser($user),
            ],
        ];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function me(int $userId): array
    {
        $user = User::query()->find($userId);

        if ($user === null || (string) $user->status !== 'active') {
            return ['error' => $this->api->error('USER_NOT_FOUND', 'Tài khoản không tồn tại hoặc đã bị khóa.', 404)];
        }

        return [
            'payload' => [
                'user' => $this->api->formatUser($user),
            ],
        ];
    }

    public function logout(string $plainToken): bool
    {
        return ApiToken::revokePlainToken($plainToken);
    }
}
