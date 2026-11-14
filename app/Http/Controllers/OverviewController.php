<?php

namespace App\Http\Controllers;

use App\Services\StudentOverviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request, StudentOverviewService $overview): View
    {
        $user = $request->user();

        if ($user?->isGuest()) {
            return redirect()->to($user->homePath());
        }

        if ($user?->isLearner()) {
            $data = $overview->build((int) $user->id);

            return view('overview', $data);
        }

        return view('admin.dashboard', [
            'totalUsers' => \App\Models\User::query()->count(),
            'activeUsers' => \App\Models\User::query()->where('status', 'active')->count(),
            'adminUsers' => \App\Models\User::query()->where('role', \App\Support\UserRole::ADMIN)->count(),
        ]);
    }
}
