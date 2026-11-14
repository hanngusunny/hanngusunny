<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isTeacher()) {
            return redirect()->to($user?->homePath() ?? route('login'))
                ->with('error', 'Khu vực này dành cho giáo viên.');
        }

        return $next($request);
    }
}
