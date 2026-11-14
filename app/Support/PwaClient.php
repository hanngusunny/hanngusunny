<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class PwaClient
{
    public static function mark(?Request $request = null): void
    {
        $request ??= request();

        if ($request->hasSession()) {
            $request->session()->put('pwa_client', true);
        }
    }

    public static function active(?Request $request = null): bool
    {
        $request ??= request();

        if ((string) $request->input('_pwa') === '1') {
            return true;
        }

        if ($request->hasSession() && $request->session()->get('pwa_client') === true) {
            return true;
        }

        $path = (string) (parse_url((string) $request->headers->get('referer', ''), PHP_URL_PATH) ?: '');

        return str_contains($path, '/app/') || str_ends_with($path, '/app');
    }

    public static function roomUrl(int $roomId, ?User $user = null, ?Request $request = null): string
    {
        $user ??= request()->user();

        if ($user instanceof User && $user->isLearner() && self::active($request)) {
            return route('pwa.room', $roomId);
        }

        return route('classroom.room', $roomId);
    }

    public static function leaveUrl(?User $user = null, ?Request $request = null): string
    {
        $user ??= request()->user();

        if ($user instanceof User && $user->isLearner() && self::active($request)) {
            return route('pwa.home');
        }

        return route('dashboard');
    }

    public static function homeFallback(?Request $request = null): string
    {
        return self::active($request) ? route('pwa.home') : route('overview');
    }

    public static function intendedUrl(?Request $request = null): string
    {
        $request ??= request();
        $referer = (string) $request->headers->get('referer', '');
        $refPath = (string) (parse_url($referer, PHP_URL_PATH) ?: '');

        if ($referer !== '' && (str_contains($refPath, '/app/') || str_ends_with($refPath, '/app'))) {
            return $referer;
        }

        $path = $request->path();

        if (str_contains($path, 'kiem-tra')) {
            return route('pwa.assessments');
        }

        if (str_contains($path, 'buoi') || str_contains($path, 'khoa-hoc')) {
            return route('pwa.courses');
        }

        return route('pwa.home');
    }
}
