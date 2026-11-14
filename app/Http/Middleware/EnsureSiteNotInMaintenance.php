<?php

namespace App\Http\Middleware;

use App\Services\Settings\SiteSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiteNotInMaintenance
{
    public function __construct(private readonly SiteSettingsService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }
        if (! $this->settings->isMaintenance()) {
            return $next($request);
        }

        abort(503);
    }
}
