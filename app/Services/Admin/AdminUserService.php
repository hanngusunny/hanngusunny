<?php

namespace App\Services\Admin;

use App\Models\AdminUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserService
{
    public function create(array $data, bool $canAssignSuper): AdminUser
    {
        return AdminUser::query()->create([
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'name' => $data['name'] !== '' ? $data['name'] : null,
            'is_active' => ! empty($data['is_active']),
            'is_super_admin' => $canAssignSuper && ! empty($data['is_super_admin']),
        ]);
    }

    public function update(AdminUser $user, array $data, bool $canAssignSuper, bool $isSelf): AdminUser
    {
        $user->name = ($data['name'] ?? '') !== '' ? $data['name'] : null;
        $user->is_active = $isSelf ? true : ! empty($data['is_active']);
        if ($canAssignSuper) {
            $user->is_super_admin = ! empty($data['is_super_admin']);
        }
        $pw = trim((string) ($data['password'] ?? ''));
        if ($pw !== '') {
            $user->password_hash = Hash::make($pw);
        }
        $user->save();

        return $user;
    }

    public function updateProfile(AdminUser $user, array $data, ?UploadedFile $avatar, bool $removeAvatar): AdminUser
    {
        $user->name = ($data['name'] ?? '') !== '' ? $data['name'] : null;
        $pw = trim((string) ($data['password_new'] ?? ''));
        if ($pw !== '') {
            $user->password_hash = Hash::make($pw);
        }
        if ($removeAvatar) {
            $this->deleteAvatarFile((string) $user->avatar);
            $user->avatar = null;
        } elseif ($avatar !== null && $avatar->isValid()) {
            $rel = $this->storeAvatar($user->id, $avatar);
            $this->deleteAvatarFile((string) $user->avatar);
            $user->avatar = $rel;
        }
        $user->save();

        return $user;
    }

    private function storeAvatar(int $userId, UploadedFile $file): string
    {
        $dir = public_path('uploads/admin_avatars');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        $name = 'av_'.$userId.'_'.Str::random(16).'.'.$ext;
        $file->move($dir, $name);

        return 'uploads/admin_avatars/'.$name;
    }

    private function deleteAvatarFile(string $rel): void
    {
        $rel = str_replace('\\', '/', trim($rel));
        if ($rel === '' || ! str_starts_with($rel, 'uploads/admin_avatars/')) {
            return;
        }
        $full = public_path($rel);
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
