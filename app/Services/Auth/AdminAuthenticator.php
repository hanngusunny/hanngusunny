<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;

class AdminAuthenticator
{
    /**
     * @return array{ok: bool, message_key: string|null}
     */
    public function attempt(string $username, string $password, bool $remember = false): array
    {
        if ($username === '' || $password === '') {
            return ['ok' => false, 'message_key' => 'admin_login.login_empty'];
        }

        $ok = Auth::guard('admin')->attempt(
            [
                'username' => $username,
                'password' => $password,
                'is_active' => 1,
            ],
            $remember
        );

        if (! $ok) {
            return ['ok' => false, 'message_key' => 'admin_login.login_invalid'];
        }

        return ['ok' => true, 'message_key' => null];
    }

    public function logout(): void
    {
        Auth::guard('admin')->logout();
    }
}
