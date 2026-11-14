<?php

namespace App\Http\Middleware;

use App\Support\PwaClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkPwaClient
{
    public function handle(Request $request, Closure $next): Response
    {
        PwaClient::mark($request);

        return $next($request);
    }
}
