<?php

namespace App\Services;

use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\Auth;

class GuestUserService
{
    /**
     * @return array{id: int, username: string, full_name: string, role: string, user: User}
     */
    public function createGuestUser(string $displayName): array
    {
        $displayName = trim($displayName);

        if ($displayName === '') {
            throw new \InvalidArgumentException('Tên hiển thị không được để trống.');
        }

        if (mb_strlen($displayName) > 80) {
            throw new \InvalidArgumentException('Tên hiển thị tối đa 80 ký tự.');
        }

        $suffix = bin2hex(random_bytes(4));
        $user = new User;
        $user->fill([
            'username' => 'guest_'.$suffix,
            'email' => 'guest_'.$suffix.'@guest.local',
            'password' => bin2hex(random_bytes(16)),
            'full_name' => $displayName,
            'role' => UserRole::GUEST,
            'status' => 'active',
        ]);
        $user->save();

        return [
            'id' => (int) $user->id,
            'username' => (string) $user->username,
            'full_name' => $displayName,
            'role' => UserRole::GUEST,
            'user' => $user,
        ];
    }

    public function loginGuestSession(User $guestUser, int $roomId, int $requestId): void
    {
        Auth::login($guestUser, false);
        session()->regenerate();
        session([
            'is_guest_session' => true,
            'guest_room_id' => $roomId,
            'guest_join_request_id' => $requestId,
        ]);
    }
}
