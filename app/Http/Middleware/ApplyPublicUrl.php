<?php

namespace App\Http\Middleware;

use App\Support\PublicUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyPublicUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        PublicUrl::applyFromRequest($request);

        return $next($request);
    }
}
