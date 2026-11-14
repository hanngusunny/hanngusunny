<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\CourseSessionAttendance;
use App\Services\TeacherCourseManageService;
use App\Services\TeacherCourseStudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseManageController extends Controller
{
    public function show(Request $request, int $id, TeacherCourseManageService $manage): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $payload = $manage->build($id, (int) $user->id, max(0, (int) $request->query('buoi', 0)));

        if ($payload === null) {
            return redirect()->route('dashboard')
                ->with('error', 'Không tìm thấy khóa học hoặc bạn không có quyền truy cập.');
        }

        return view('teacher.course-manage', $payload);
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

        if ($students->build($id, $sessionId, $userId, (int) $teacher->id) === null) {
            return redirect()->route('teacher.course.show', ['id' => $id, 'buoi' => $sessionId])
                ->with('error', 'Không tìm thấy học viên hoặc buổi học.');
        }

        $url = $students->studentUrl($id, $sessionId, $userId, TeacherCourseStudentService::TAB_OVERVIEW);
        $attended = (string) $request->input('attended', '') === '1';

        if (! $attended) {
            CourseSessionAttendance::clearAttendance($sessionId, $userId);

            return redirect()->to($url)->with('success', 'Đã gỡ điểm danh học viên.');
        }

        $joinedAt = $this->parseJoinedAt(trim((string) $request->input('joined_at', '')));

        if ($joinedAt === null) {
            return redirect()->to($url)->with('error', 'Thời gian điểm danh không hợp lệ.');
        }

        CourseSessionAttendance::setManual($sessionId, $userId, $joinedAt);

        return redirect()->to($url)->with('success', 'Đã cập nhật điểm danh học viên.');
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
