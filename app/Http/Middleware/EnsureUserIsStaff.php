<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStaff()) {
            return redirect()->to($user?->homePath() ?? route('login'))
                ->with('error', 'Khu vực này dành cho giáo viên và quản trị viên.');
        }

        return $next($request);
    }
}
