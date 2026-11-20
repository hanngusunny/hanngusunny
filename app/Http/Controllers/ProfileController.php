<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Support\PwaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = auth()->user() ?? request()->user();
        $activity = app(\App\Services\UserActivityService::class);
        $activitySummary = $activity->getProfileActivitySummary((int) ($user?->id ?? 0));

        $activeSeconds = (int) ($activitySummary['active_seconds'] ?? 0);
        $hours = intdiv($activeSeconds, 3600);
        $minutes = intdiv($activeSeconds % 3600, 60);

        if ($hours > 0) {
            $activeHoursText = $minutes > 0 ? "{$hours} giờ {$minutes} phút" : "{$hours} giờ";
        } elseif ($minutes > 0) {
            $activeHoursText = "{$minutes} phút";
        } else {
            $activeHoursText = "0 phút";
        }

        $activeDays = (int) ($activitySummary['active_days'] ?? 0);
        if ($activeDays > 0) {
            $avgMinutes = (int) round(($activeSeconds / 60) / $activeDays);
            $activeAvgDailyText = $avgMinutes > 0 ? "Khoảng {$avgMinutes} phút / ngày" : "Chưa ghi nhận hoạt động";
        } else {
            $activeAvgDailyText = $activeSeconds > 0 ? "Khoảng " . (int) round($activeSeconds / 60) . " phút / ngày" : "Chưa ghi nhận hoạt động";
        }

        return view('profile.show', [
            'user' => $user,
            'activeHoursText' => $activeHoursText,
            'activeAvgDailyText' => $activeAvgDailyText,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['full_name', 'email', 'phone']);

        if ($request->boolean('remove_avatar')) {
            $this->deleteAvatarFile($user->avatar);
            $data['avatar'] = null;
        } elseif ($request->hasFile('avatar')) {
            $path = $this->storeAvatar($request->file('avatar'), (int) $user->id, $user->avatar);

            if ($path !== null) {
                $data['avatar'] = $path;
            }
        }

        $user->fill($data);
        $user->save();

        return redirect()->to($this->accountReturn($request, 'profile.show'))
            ->with('success', 'Đã lưu thay đổi hồ sơ thành công.');
    }

    public function password(): View
    {
        return view('profile.password', [
            'user' => auth()->user() ?? request()->user(),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->password = (string) $request->validated('password');
        $user->save();

        $url = $this->accountReturn($request, 'profile.password');

        if ($url === route('pwa.profile')) {
            $url .= '#mat-khau';
        }

        return redirect()->to($url)->with('success', 'Cập nhật mật khẩu thành công. Hãy sử dụng mật khẩu mới trong lần đăng nhập tiếp theo.');
    }

    private function accountReturn(\Illuminate\Http\Request $request, string $desktopRoute): string
    {
        if (PwaClient::active($request)) {
            return route('pwa.profile');
        }

        return route($desktopRoute);
    }

    private function storeAvatar(UploadedFile $file, int $userId, ?string $oldPath): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        $uploadDir = public_path('uploads/avatars');

        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            return null;
        }

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: 'jpg'));
        $fileName = 'user_'.$userId.'_'.bin2hex(random_bytes(8)).'.'.$extension;

        $file->move($uploadDir, $fileName);
        $this->deleteAvatarFile($oldPath);

        return 'uploads/avatars/'.$fileName;
    }

    private function deleteAvatarFile(?string $path): void
    {
        $path = trim(str_replace('\\', '/', (string) $path));

        if ($path === '' || preg_match('#^https?://#i', $path) === 1) {
            return;
        }

        $fullPath = public_path(ltrim($path, '/'));

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
