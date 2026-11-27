<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentRetake;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseMember;
use App\Services\AssessmentSubmissionService;
use App\Services\StudentAssessmentService;
use App\Services\TeacherAssessmentGradeService;
use App\Services\TeacherAssessmentGradingService;
use App\Services\TeacherGradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(
        Request $request,
        TeacherAssessmentGradingService $hub
    ): View|RedirectResponse {
        $user = $request->user();

        if (! $user?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $payload = $hub->build(
            (int) $user->id,
            max(0, (int) $request->query('khoa', 0)),
            max(0, (int) $request->query('bai', 0)),
            max(0, (int) $request->query('hoc-vien', 0))
        );

        if ($payload === null) {
            return redirect()->route('dashboard')
                ->with('error', 'Bạn chưa có khóa học đang dạy.');
        }

        return view('teacher.assessments', $payload);
    }

    public function grade(
        Request $request,
        TeacherAssessmentGradingService $hub,
        TeacherAssessmentGradeService $grader,
        TeacherGradingService $teacherGrading
    ): JsonResponse|RedirectResponse {
        $teacher = $request->user();

        if (! $teacher?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $courseId = (int) $request->input('course_id', 0);
        $assessmentId = (int) $request->input('assessment_id', 0);
        $userId = (int) $request->input('user_id', 0);
        $questionId = (int) $request->input('question_id', 0);
        $redirectUrl = $this->unifiedRedirectUrl($teacherGrading, $courseId, $assessmentId, $userId, $questionId);

        if (! $grader->canManageCourse((int) $teacher->id, $courseId)) {
            return redirect()->route('teacher.grading')->with('error', 'Không có quyền truy cập.');
        }

        $outcome = $grader->gradePostedAnswer(
            $courseId,
            $assessmentId,
            $userId,
            $questionId,
            (string) $request->input('grade_mode', ''),
            $request->input('item_grades'),
            (string) $request->input('points_earned', '0'),
            (string) $request->input('teacher_comment', '')
        );

        return $grader->respondToGradeRequest(
            $request->ajax() || $request->wantsJson(),
            $redirectUrl,
            $outcome
        );
    }

    public function publish(
        Request $request,
        TeacherAssessmentGradeService $grader,
        AssessmentSubmissionService $scoring,
        StudentAssessmentService $assessments,
        TeacherGradingService $teacherGrading
    ): RedirectResponse {
        $teacher = $request->user();

        if (! $teacher?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $courseId = (int) $request->input('course_id', 0);
        $assessmentId = (int) $request->input('assessment_id', 0);
        $userId = (int) $request->input('user_id', 0);
        $redirectUrl = $this->unifiedRedirectUrl($teacherGrading, $courseId, $assessmentId, $userId);

        if (! $grader->canManageCourse((int) $teacher->id, $courseId)) {
            return redirect()->route('teacher.grading')->with('error', 'Không có quyền truy cập.');
        }

        if (! CourseMember::isMemberOf($courseId, $userId)) {
            return redirect()->to($redirectUrl)->with('error', 'Học viên không thuộc khóa này.');
        }

        $assessment = CourseAssessment::query()
            ->where('course_id', $courseId)
            ->where('id', $assessmentId)
            ->first();

        if ($assessment === null) {
            return redirect()->to($teacherGrading->pageUrl($courseId, TeacherGradingService::TAB_ASSESSMENT))
                ->with('error', 'Bài thi không tồn tại.');
        }

        if (! CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId)) {
            return redirect()->to($redirectUrl)->with('error', 'Học viên chưa nộp bài.');
        }

        $questions = $assessments->readyQuestions($assessmentId);
        $answerMap = CourseAssessmentAnswer::mapForUser($assessmentId, $userId);
        $pending = $scoring->countPendingManualGrades($questions, $answerMap);

        if ($pending > 0) {
            return redirect()->to($redirectUrl)->with(
                'error',
                'Còn '.$pending.' câu chưa chấm điểm. Vui lòng chấm hết trước khi gửi kết quả.'
            );
        }

        if (! Schema::hasTable('course_assessment_submissions')) {
            return redirect()->to($redirectUrl)->with('error', 'Chưa cấu hình bảng kết quả bài thi.');
        }

        CourseAssessmentSubmission::markGraded($assessmentId, $userId, (int) $teacher->id);

        return redirect()->to($redirectUrl)->with('success', 'Đã gửi kết quả cho học viên.');
    }

    public function grantRetake(
        Request $request,
        TeacherAssessmentGradeService $grader,
        TeacherGradingService $teacherGrading
    ): RedirectResponse {
        return $this->handleRetake($request, $grader, $teacherGrading, true);
    }

    public function revokeRetake(
        Request $request,
        TeacherAssessmentGradeService $grader,
        TeacherGradingService $teacherGrading
    ): RedirectResponse {
        return $this->handleRetake($request, $grader, $teacherGrading, false);
    }

    private function handleRetake(
        Request $request,
        TeacherAssessmentGradeService $grader,
        TeacherGradingService $teacherGrading,
        bool $grant
    ): RedirectResponse {
        $teacher = $request->user();

        if (! $teacher?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $courseId = (int) $request->input('course_id', 0);
        $assessmentId = (int) $request->input('assessment_id', 0);
        $userId = (int) $request->input('user_id', 0);
        $redirectUrl = $this->unifiedRedirectUrl($teacherGrading, $courseId, $assessmentId, $userId);

        if (! $grader->canManageCourse((int) $teacher->id, $courseId)) {
            return redirect()->route('teacher.grading')->with('error', 'Không có quyền truy cập.');
        }

        if ($grant) {
            if (! CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId)) {
                return redirect()->to($redirectUrl)->with('error', 'Học viên chưa nộp bài — không cần cho làm lại.');
            }

            CourseAssessmentRetake::grant($assessmentId, $userId, (int) $teacher->id);
            $grader->prepareRetakeDrafts($assessmentId, $userId);
            CourseAssessmentSubmission::markSubmitted($assessmentId, $userId);
            $message = 'Đã cho phép học viên làm lại bài thi.';
        } else {
            CourseAssessmentRetake::revoke($assessmentId, $userId);
            $message = 'Đã hủy quyền làm lại.';
        }

        return redirect()->to($redirectUrl)->with('success', $message);
    }

    public function live(
        Request $request,
        TeacherAssessmentGradingService $hub,
        TeacherAssessmentGradeService $grader
    ): JsonResponse {
        $teacher = $request->user();

        if (! $teacher?->isTeacher()) {
            return response()->json(['ok' => false], 403);
        }

        $courseId = max(0, (int) $request->query('khoa', 0));
        $assessmentId = max(0, (int) $request->query('bai', 0));
        $studentId = max(0, (int) $request->query('hoc-vien', 0));

        if (! $grader->canManageCourse((int) $teacher->id, $courseId)) {
            return response()->json(['ok' => false], 403);
        }

        return response()->json([
            'ok' => true,
            'revision' => $hub->liveRevision($courseId, $assessmentId, $studentId),
        ]);
    }

    private function unifiedRedirectUrl(
        TeacherGradingService $teacherGrading,
        int $courseId,
        int $assessmentId,
        int $userId,
        int $questionId = 0
    ): string {
        $key = ($assessmentId > 0 && $userId > 0) ? 'ass-'.$assessmentId.'-'.$userId : '';
        $extra = $questionId > 0 ? ['cau' => $questionId] : [];

        return $teacherGrading->pageUrl($courseId, TeacherGradingService::TAB_ASSESSMENT, $key, $extra);
    }
}
