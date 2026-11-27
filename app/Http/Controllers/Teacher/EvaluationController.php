<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherEvaluationRequest;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseMonthlyEvaluation;
use App\Services\TeacherEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(Request $request, TeacherEvaluationService $evaluations): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $payload = $evaluations->build(
            (int) $user->id,
            max(0, (int) $request->query('khoa', 0)),
            max(0, (int) $request->query('hoc-vien', 0)),
            (string) ($request->query('tuan') ?: CourseMonthlyEvaluation::currentWeekKey()),
            (string) ($request->query('tab') ?: TeacherEvaluationService::TAB_ALL),
            (string) ($request->query('q') ?: '')
        );

        if ($payload === null) {
            return redirect()->route('dashboard')
                ->with('error', 'Bạn chưa có khóa học đang dạy.');
        }

        return view('teacher.evaluation', $payload);
    }

    public function save(StoreTeacherEvaluationRequest $request, TeacherEvaluationService $evaluations): RedirectResponse
    {
        $teacher = $request->user();
        $courseId = (int) $request->validated('course_id');
        $userId = (int) $request->validated('user_id');
        $evalWeek = CourseMonthlyEvaluation::normalizePeriod((string) $request->validated('eval_week'));

        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', (int) $teacher->id)
            ->where('status', Course::STATUS_ACTIVE)
            ->first();

        if ($course === null) {
            return redirect()->route('teacher.evaluations')
                ->with('error', 'Không tìm thấy khóa học đang dạy.');
        }

        if (! CourseMember::isMemberOf($courseId, $userId)) {
            return redirect()->to($evaluations->pageUrl($courseId, 0, $evalWeek))
                ->with('error', 'Học viên không thuộc khóa này.');
        }

        if (! CourseMonthlyEvaluation::canEditPeriod($evalWeek)) {
            return redirect()->back()->with('error', 'Không thể đánh giá cho tuần tương lai.');
        }

        CourseMonthlyEvaluation::upsertEvaluation($courseId, $userId, (int) $teacher->id, $evalWeek, $request->validated());

        $member = CourseMember::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->with('user')
            ->first();
        $memberName = trim((string) ($member?->user?->name ?? ''));

        return redirect()->to($evaluations->pageUrl($courseId, $userId, $evalWeek))
            ->with('success', 'Đã lưu đánh giá tuần cho '.($memberName !== '' ? $memberName : 'học viên').'.');
    }
}
