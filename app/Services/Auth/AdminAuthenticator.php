<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthenticator
{
    /**
     * @return array{ok: bool, message_key: string|null}
     */
    public function attempt(string $username, string $password, bool $remember = false): array
    {
        $login = trim($username);
        $pass = (string) $password;

        if ($login === '' || $pass === '') {
            return ['ok' => false, 'message_key' => 'admin_login.login_empty'];
        }

        $user = User::query()
            ->where(function ($q) use ($login) {
                $q->where('username', $login)
                    ->orWhere('email', $login);
            })
            ->where('role', UserRole::ADMIN)
            ->where('status', 'active')
            ->first();

        if (! $user || ! Hash::check($pass, (string) $user->password)) {
            return ['ok' => false, 'message_key' => 'admin_login.login_invalid'];
        }

        Auth::guard('web')->login($user, $remember);
        Auth::guard('admin')->login($user, $remember);

        $user->update(['last_login_at' => now()]);

        return ['ok' => true, 'message_key' => null];
    }

    public function logout(): void
    {
        Auth::guard('admin')->logout();
        Auth::guard('web')->logout();
    }
}
