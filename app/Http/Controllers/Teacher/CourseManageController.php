<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStudentCommitmentRequest;
use App\Http\Requests\SaveDrawerEvaluationRequest;
use App\Http\Requests\UpdateTeacherAttendanceStatusRequest;
use App\Services\TeacherCourseManageService;
use App\Services\TeacherCourseStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseManageController extends Controller
{
    public function index(Request $request, TeacherCourseManageService $manage): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $payload = $manage->buildCourseList(
            (int) $user->id,
            (string) ($request->query('trinh_do') ?: 'all'),
            (string) ($request->query('q') ?: '')
        );

        return view('teacher.courses-index', $payload);
    }

    public function show(Request $request, int $id, TeacherCourseManageService $manage): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $payload = $manage->build(
            $id,
            (int) $user->id,
            max(0, (int) $request->query('buoi', 0)),
            [
                'tab' => (string) ($request->query('tab') ?: TeacherCourseManageService::TAB_INFO),
                'filter_mode' => (string) ($request->query('che_do') ?: ($request->query('buoi') && $request->query('tab') === TeacherCourseManageService::TAB_STUDENTS ? TeacherCourseManageService::FILTER_SESSION : TeacherCourseManageService::FILTER_WEEK)),
                'week' => (string) ($request->query('tuan') ?: ''),
                'package_filter' => (string) ($request->query('loai') ?: 'all'),
                'q' => (string) ($request->query('q') ?: ''),
                'page' => max(1, (int) $request->query('page', 1)),
                'open_student' => max(0, (int) $request->query('hoc_vien', 0)),
            ]
        );

        if ($payload === null) {
            return redirect()->route('teacher.course.index')
                ->with('error', 'Không tìm thấy khóa học hoặc bạn không có quyền truy cập.');
        }

        return view('teacher.course-manage', $payload);
    }

    public function studentDrawer(
        Request $request,
        int $id,
        int $userId,
        TeacherCourseManageService $manage
    ): JsonResponse {
        $teacher = $request->user();

        if (! $teacher?->isTeacher()) {
            return response()->json(['ok' => false, 'message' => 'Không có quyền truy cập.'], 403);
        }

        $drawer = $manage->buildStudentDrawer(
            $id,
            (int) $teacher->id,
            $userId,
            (string) ($request->query('tuan') ?: ''),
            max(0, (int) $request->query('buoi', 0))
        );

        if ($drawer === null) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy hồ sơ học viên.'], 404);
        }

        return response()->json([
            'ok' => true,
            'drawer' => $drawer,
        ]);
    }

    public function adjustCommitment(
        AdjustStudentCommitmentRequest $request,
        int $id,
        int $userId,
        TeacherCourseManageService $manage
    ): JsonResponse {
        $teacher = $request->user();
        $metric = (string) $request->validated('metric');
        $delta = (int) $request->validated('delta');
        $evalWeek = (string) ($request->validated('eval_week') ?? '');

        $drawer = $manage->adjustCommitment(
            $id,
            (int) $teacher->id,
            $userId,
            $metric,
            $delta,
            $evalWeek
        );

        if ($drawer === null) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy học viên trong khóa học.'], 404);
        }

        $metricLabel = $metric === 'homework' ? 'bài tập' : 'chuyên cần';
        $actionLabel = $delta < 0 ? 'Đã đặc cách giảm 1 buổi' : 'Đã cộng lại 1 buổi';

        return response()->json([
            'ok' => true,
            'message' => "{$actionLabel} {$metricLabel} cho ".($drawer['student']['name'] ?? 'học viên').'.',
            'drawer' => $drawer,
        ]);
    }

    public function saveWeeklyEvaluation(
        SaveDrawerEvaluationRequest $request,
        int $id,
        int $userId,
        TeacherCourseManageService $manage
    ): JsonResponse {
        $teacher = $request->user();
        $evalWeek = (string) $request->validated('eval_week');
        $isDraft = (string) $request->validated('mode') === 'draft';

        $result = $manage->saveDrawerEvaluation(
            $id,
            (int) $teacher->id,
            $userId,
            $evalWeek,
            $request->validated(),
            $isDraft
        );

        return response()->json($result, ! empty($result['ok']) ? 200 : 422);
    }

    public function student(
        Request $request,
        int $id,
        int $sessionId,
        int $userId,
        TeacherCourseStudentService $students
    ): View|RedirectResponse {
        $teacher = $request->user();

        if (! $teacher?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $tab = (string) ($request->query('muc') ?: TeacherCourseStudentService::TAB_OVERVIEW);
        $payload = $students->build($id, $sessionId, $userId, (int) $teacher->id, $tab);

        if ($payload === null) {
            return redirect()->route('teacher.course.show', ['id' => $id, 'buoi' => $sessionId])
                ->with('error', 'Không tìm thấy học viên hoặc buổi học.');
        }

        $payload['backUrl'] = route('teacher.course.show', ['id' => $id, 'buoi' => $sessionId]);
        $payload['attendanceUpdateUrl'] = route('teacher.course.attendance', [
            'id' => $id,
            'sessionId' => $sessionId,
            'userId' => $userId,
        ]);
        $payload['essayGradeUrl'] = route('teacher.course.essay', [
            'id' => $id,
            'sessionId' => $sessionId,
            'userId' => $userId,
        ]);

        return view('teacher.course-student', $payload);
    }

    public function saveEssay(
        Request $request,
        int $id,
        int $sessionId,
        int $userId,
        TeacherCourseStudentService $students
    ): RedirectResponse {
        $teacher = $request->user();

        if (! $teacher?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $tab = $students->normalizeTab((string) $request->input('muc', TeacherCourseStudentService::TAB_REVIEW));
        $result = $students->saveEssayReview($id, $sessionId, $userId, (int) $teacher->id, $request->all());
        $url = $students->studentUrl($id, $sessionId, $userId, $tab);

        return redirect()->to($url)->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function updateAttendance(
        UpdateTeacherAttendanceStatusRequest $request,
        int $id,
        int $sessionId,
        int $userId,
        TeacherCourseManageService $manage,
        TeacherCourseStudentService $students
    ): JsonResponse|RedirectResponse {
        $teacher = $request->user();
        $wantsJson = $request->ajax() || $request->wantsJson();
        $status = (string) $request->validated('status');
        $evalWeek = (string) ($request->validated('eval_week') ?? '');
        $joinedAtRaw = (string) ($request->validated('joined_at') ?? '');
        $joinedAt = $this->parseJoinedAt($joinedAtRaw);

        if ($status !== 'absent' && $joinedAt === null) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => 'Thời gian điểm danh không hợp lệ.'], 422);
            }

            return redirect()->to($students->studentUrl($id, $sessionId, $userId, TeacherCourseStudentService::TAB_OVERVIEW))
                ->with('error', 'Thời gian điểm danh không hợp lệ.');
        }

        $drawer = $manage->updateAttendanceStatus(
            $id,
            (int) $teacher->id,
            $sessionId,
            $userId,
            $status,
            $joinedAt,
            $evalWeek
        );

        if ($drawer === null) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => 'Không tìm thấy học viên hoặc buổi học.'], 404);
            }

            return redirect()->route('teacher.course.show', ['id' => $id, 'buoi' => $sessionId])
                ->with('error', 'Không tìm thấy học viên hoặc buổi học.');
        }

        $statusLabel = match ($status) {
            'late' => 'Đi muộn',
            'absent' => 'Vắng',
            default => 'Có mặt',
        };
        $msg = "Đã cập nhật điểm danh thành {$statusLabel}.";

        if ($wantsJson) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'status' => $status,
                'drawer' => $drawer,
            ]);
        }

        return redirect()
            ->to($students->studentUrl($id, $sessionId, $userId, TeacherCourseStudentService::TAB_OVERVIEW))
            ->with('success', $msg);
    }

    private function parseJoinedAt(string $raw): ?string
    {
        if ($raw === '') {
            return now()->format('Y-m-d H:i:s');
        }

        $raw = str_replace('T', ' ', $raw);

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw) === 1) {
            $raw .= ':00';
        }

        $ts = strtotime($raw);

        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
