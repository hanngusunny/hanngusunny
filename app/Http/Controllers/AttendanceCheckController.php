<?php

namespace App\Http\Controllers;

use App\Services\AttendanceCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceCheckController extends Controller
{
    public function __construct(
        private AttendanceCheckService $attendance,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $request->user();

        if ($viewer === null || ! $viewer->isAdmin()) {
            return redirect()->route('dashboard');
        }

        return view('activity_logs.attendance.index', [
            'courses' => $this->attendance->coursesForViewer($viewer),
            'activityTab' => 'attendance',
        ]);
    }

    public function show(Request $request, int $courseId): View|RedirectResponse
    {
        $viewer = $request->user();

        if ($viewer === null || ! $viewer->isAdmin()) {
            return redirect()->route('dashboard');
        }

        $course = $this->attendance->resolveCourse($viewer, $courseId);

        if ($course === null) {
            return redirect()->route('activity-attendance.index')->with('error', 'Không tìm thấy khóa học.');
        }

        $selectedUserId = (int) $request->query('hoc_vien', 0);
        $board = $this->attendance->buildCourseBoard($course, $selectedUserId);

        return view('activity_logs.attendance.show', [
            'course' => $board['course'],
            'students' => $board['students'],
            'selectedStudent' => $board['selectedStudent'],
            'sessionRows' => $board['sessionRows'],
            'sessionCount' => $board['sessions']->count(),
            'activityTab' => 'attendance',
        ]);
    }

    public function toggle(Request $request, int $courseId, int $sessionId, int $userId): JsonResponse|RedirectResponse
    {
        $viewer = $request->user();

        if ($viewer === null || ! $viewer->isAdmin()) {
            return $this->toggleResponse($request, false, 'Không có quyền.', 403);
        }

        $attended = (string) $request->input('attended', '') === '1';
        $joinedAt = trim((string) $request->input('joined_at', ''));
        $result = $this->attendance->toggle(
            $viewer,
            $courseId,
            $sessionId,
            $userId,
            $attended,
            $joinedAt !== '' ? $joinedAt : null
        );

        if ($result === null) {
            return $this->toggleResponse($request, false, 'Không thể cập nhật điểm danh.', 422);
        }

        return $this->toggleResponse($request, true, $result['message'], 200, $result);
    }

    public function markAll(Request $request, int $courseId, int $userId): RedirectResponse
    {
        $viewer = $request->user();

        if ($viewer === null || ! $viewer->isAdmin()) {
            return redirect()->route('dashboard');
        }

        $result = $this->attendance->markAllPresent($viewer, $courseId, $userId);

        if ($result === null) {
            return redirect()
                ->route('activity-attendance.show', ['courseId' => $courseId, 'hoc_vien' => $userId])
                ->with('error', 'Không thể điểm danh hàng loạt.');
        }

        return redirect()
            ->route('activity-attendance.show', ['courseId' => $courseId, 'hoc_vien' => $userId])
            ->with('success', $result['message']);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function toggleResponse(
        Request $request,
        bool $ok,
        string $message,
        int $status = 200,
        array $extra = []
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(array_merge(['ok' => $ok, 'message' => $message], $extra), $status);
        }

        $courseId = (int) $request->route('courseId');
        $userId = (int) $request->route('userId');

        return redirect()
            ->route('activity-attendance.show', ['courseId' => $courseId, 'hoc_vien' => $userId])
            ->with($ok ? 'success' : 'error', $message);
    }
}
