<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::query()->count(),
            'activeUsers' => User::query()->where('status', 'active')->count(),
            'adminUsers' => User::query()->where('role', UserRole::ADMIN)->count(),
        ]);
    }
}
