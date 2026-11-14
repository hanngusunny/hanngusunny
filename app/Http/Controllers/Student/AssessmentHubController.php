<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\AssessmentController as TeacherAssessmentController;
use App\Services\StudentCoursePageService;
use App\Services\TeacherAssessmentGradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentHubController extends Controller
{
    public function __invoke(
        Request $request,
        StudentCoursePageService $pages,
        TeacherAssessmentController $teacherAssessments,
        TeacherAssessmentGradingService $teacherHub
    ): View|RedirectResponse {
        $user = $request->user();

        if ($user?->isTeacher()) {
            return $teacherAssessments->index($request, $teacherHub);
        }

        if (! $user?->isLearner()) {
            return redirect()->route('dashboard');
        }

        $result = $pages->buildAssessmentHub(
            (int) $user->id,
            max(0, (int) $request->query('trinh-do', 0)),
            trim((string) $request->query('buoi', '')) ?: null
        );

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        return view('student.dashboard', $result);
    }
}
