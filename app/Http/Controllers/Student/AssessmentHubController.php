<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentCoursePageService;
use App\Services\TeacherGradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentHubController extends Controller
{
    public function __invoke(
        Request $request,
        StudentCoursePageService $pages,
        TeacherGradingService $teacherGrading
    ): View|RedirectResponse {
        $user = $request->user();

        if ($user?->isTeacher()) {
            // Màn Bài kiểm tra của giáo viên đã gộp vào màn Chấm điểm thống nhất (/giao-vien/cham-diem)
            $courseId = max(0, (int) $request->query('khoa', 0));
            $assessmentId = max(0, (int) $request->query('bai', 0));
            $studentId = max(0, (int) $request->query('hoc-vien', 0));
            $submissionKey = ($assessmentId > 0 && $studentId > 0)
                ? 'ass-'.$assessmentId.'-'.$studentId
                : '';

            return redirect()->to(
                $teacherGrading->pageUrl($courseId, TeacherGradingService::TAB_ASSESSMENT, $submissionKey)
            );
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

        return view('student.assessments-index', $result);
    }
}
