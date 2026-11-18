<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('admin') ?? $request->user();
        $isSuper = $user !== null && (method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : (method_exists($user, 'isAdmin') && $user->isAdmin()));
        if (! $isSuper) {
            abort(403);
        }

        return $next($request);
    }
}
