<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = array_keys(config('admin.languages'));
        $default = config('admin.language_default');
        $lang = $request->session()->get('admin_language');
        if (! is_string($lang) || ! in_array($lang, $allowed, true)) {
            $lang = $request->cookie('admin_language');
        }
        if (! is_string($lang) || ! in_array($lang, $allowed, true)) {
            $lang = $default;
        }

        $request->session()->put('admin_language', $lang);
        $locale = config('admin.languages.'.$lang.'.locale', 'vi');
        app()->setLocale($locale);

        return $next($request);
    }
}
