<?php

namespace App\Http\Middleware;

use App\Support\PwaClient;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if ($request->is('app') || $request->is('app/*') || PwaClient::active($request)) {
            if ($request->hasSession()) {
                $request->session()->put('url.intended', PwaClient::intendedUrl($request));
            }

            return route('pwa.login');
        }

        return route('login');
    }
}
