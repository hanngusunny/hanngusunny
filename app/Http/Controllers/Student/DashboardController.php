<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentCoursePageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StudentCoursePageService $pages): View|RedirectResponse
    {
        $result = $pages->buildDashboard(
            (int) $request->user()->id,
            max(0, (int) $request->query('trinh-do', 0)),
            trim((string) $request->query('buoi', '')) ?: null,
            $request->query('tab')
        );

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        return view('student.dashboard', $result);
    }
}
