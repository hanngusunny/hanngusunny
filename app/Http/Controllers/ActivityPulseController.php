<?php

namespace App\Http\Controllers;

use App\Services\UserActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityPulseController extends Controller
{
    public function __invoke(Request $request, UserActivityService $activity): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['ok' => false], 401);
        }

        $seconds = (int) $request->query('seconds', config('activity.pulse_interval_seconds', 60));
        $seconds = max(1, min($seconds, 120));
        $activity->recordPulse((int) $user->id, $seconds, $request);

        return response()->json(['ok' => true, 'seconds' => $seconds]);
    }
}
