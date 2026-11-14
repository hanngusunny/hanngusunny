<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Services\StudentCoursePageService;
use App\Services\TeacherDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardGatewayController extends Controller
{
    public function __invoke(
        Request $request,
        StudentDashboardController $studentDashboard,
        TeacherDashboardController $teacherDashboard,
        AdminDashboardController $adminDashboard,
        StudentCoursePageService $pages,
        TeacherDashboardService $teacherDash
    ): View|RedirectResponse {
        $user = $request->user();

        if ($user?->isGuest()) {
            return redirect()->to($user->homePath());
        }

        if ($user?->isLearner()) {
            return $studentDashboard($request, $pages);
        }

        if ($user?->isTeacher()) {
            return $teacherDashboard($request, $teacherDash);
        }

        return $adminDashboard();
    }
}
