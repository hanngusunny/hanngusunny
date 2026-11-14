<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsLearner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isLearner()) {
            return redirect()->to($user?->homePath() ?? route('login'))
                ->with('error', 'Khu vực này dành cho học viên.');
        }

        return $next($request);
    }
}
