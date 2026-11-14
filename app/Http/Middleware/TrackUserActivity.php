<?php

namespace App\Http\Middleware;

use App\Services\UserActivityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackUserActivity
{
    public function __construct(
        private UserActivityService $activity,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && ! $request->user()->isGuest()) {
            try {
                $this->activity->logRequest($request, $response);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $response;
    }
}
