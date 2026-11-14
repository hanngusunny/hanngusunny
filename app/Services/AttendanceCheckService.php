<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\User;
use Illuminate\Support\Collection;

class AttendanceCheckService
{
    /**
     * @return Collection<int, Course>
     */
    public function coursesForViewer(User $viewer): Collection
    {
        $query = Course::query()
            ->with(['teacher:id,full_name,username'])
            ->withCount([
                'activeMembers as members_count',
                'sessions as sessions_count',
            ])
            ->orderBy('title')
            ->orderBy('id');

        if ($viewer->isAdmin()) {
            return $query->where('status', Course::STATUS_ACTIVE)->get();
        }

        if ($viewer->isTeacher()) {
            return $query
                ->where('teacher_id', (int) $viewer->id)
                ->where('status', Course::STATUS_ACTIVE)
                ->get();
        }

        return collect();
    }

    public function resolveCourse(User $viewer, int $courseId): ?Course
    {
        if ($courseId <= 0) {
            return null;
        }

        $query = Course::query()
            ->with(['teacher:id,full_name,username'])
            ->whereKey($courseId);

        if ($viewer->isAdmin()) {
            return $query->first();
        }

        if ($viewer->isTeacher()) {
            return $query->where('teacher_id', (int) $viewer->id)->first();
        }

        return null;
    }

    /**
     * @return array{
     *   course: Course,
     *   sessions: Collection<int, CourseSession>,
     *   students: list<array<string, mixed>>,
     *   selectedStudent: array<string, mixed>|null,
     *   sessionRows: list<array<string, mixed>>
     * }
     */
    public function buildCourseBoard(Course $course, int $selectedUserId = 0): array
    {
        $sessions = CourseSession::query()
            ->where('course_id', $course->id)
            ->where('status', '!=', 'cancelled')
            ->inProgramOrder()
            ->get(['id', 'course_id', 'title', 'session_date', 'start_time', 'end_time', 'status', 'sort_order']);

        $sessionIds = $sessions->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $totalSessions = count($sessionIds);

        $members = CourseMember::query()
            ->where('course_id', $course->id)
            ->where('status', CourseMember::STATUS_ACTIVE)
            ->with(['user:id,full_name,username,phone,role'])
            ->orderBy('id')
            ->get();

        $attendanceByUser = [];
        if ($sessionIds !== []) {
            $rows = CourseSessionAttendance::query()
                ->whereIn('session_id', $sessionIds)
                ->get(['session_id', 'user_id', 'joined_at']);

            foreach ($rows as $row) {
                $uid = (int) $row->user_id;
                $sid = (int) $row->session_id;
                $attendanceByUser[$uid][$sid] = optional($row->joined_at)->format('Y-m-d H:i:s') ?: '';
            }
        }

        $students = [];
        $selectedStudent = null;

        foreach ($members as $member) {
            $user = $member->user;
            if ($user === null) {
                continue;
            }

            $userId = (int) $user->id;
            $map = $attendanceByUser[$userId] ?? [];
            $attended = count($map);

            $student = [
                'id' => $userId,
                'name' => (string) ($user->name ?: 'Học viên #'.$userId),
                'phone' => (string) ($user->phone ?? ''),
                'attended' => $attended,
                'total' => $totalSessions,
                'pct' => $totalSessions > 0 ? (int) round(($attended / $totalSessions) * 100) : 0,
            ];

            $students[] = $student;

            if ($selectedUserId > 0 && $userId === $selectedUserId) {
                $selectedStudent = $student;
            }
        }

        if ($selectedStudent === null && $students !== []) {
            $selectedStudent = $students[0];
            $selectedUserId = (int) $selectedStudent['id'];
        }

        $sessionRows = [];
        if ($selectedUserId > 0) {
            $map = $attendanceByUser[$selectedUserId] ?? [];

            foreach ($sessions as $index => $session) {
                $sessionId = (int) $session->id;
                $attended = array_key_exists($sessionId, $map);
                $phase = $session->livePhase();

                if ($attended) {
                    $statusLabel = 'Có mặt';
                } elseif ($phase === 'upcoming') {
                    $statusLabel = 'Chưa diễn ra';
                } else {
                    $statusLabel = 'Vắng';
                }

                $sessionRows[] = [
                    'id' => $sessionId,
                    'index' => $index + 1,
                    'title' => (string) ($session->title ?: 'Buổi học'),
                    'date' => $session->dateForInput(),
                    'date_label' => $session->dateForInput() !== ''
                        ? date('d/m/Y', strtotime($session->dateForInput()) ?: time())
                        : '—',
                    'time_label' => trim(
                        ($session->timeForInput($session->start_time) ?: '')
                        .(($session->timeForInput($session->end_time) ?: '') !== ''
                            ? ' – '.$session->timeForInput($session->end_time)
                            : '')
                    ),
                    'attended' => $attended,
                    'joined_at' => $attended ? ($map[$sessionId] ?: null) : null,
                    'status_label' => $statusLabel,
                    'phase' => $phase,
                ];
            }
        }

        return [
            'course' => $course,
            'sessions' => $sessions,
            'students' => $students,
            'selectedStudent' => $selectedStudent,
            'sessionRows' => $sessionRows,
        ];
    }

    /**
     * @return array{ok: bool, attended: bool, joined_at: string|null, message: string}|null
     */
    public function toggle(User $viewer, int $courseId, int $sessionId, int $userId, bool $attended, ?string $joinedAt = null): ?array
    {
        $course = $this->resolveCourse($viewer, $courseId);

        if ($course === null) {
            return null;
        }

        $session = CourseSession::query()
            ->where('course_id', $courseId)
            ->whereKey($sessionId)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($session === null) {
            return null;
        }

        if (! CourseMember::isMemberOf($courseId, $userId)) {
            return null;
        }

        if (! $attended) {
            CourseSessionAttendance::clearAttendance($sessionId, $userId);

            return [
                'ok' => true,
                'attended' => false,
                'joined_at' => null,
                'message' => 'Đã đánh dấu vắng.',
            ];
        }

        $parsed = $this->parseJoinedAt($joinedAt);
        CourseSessionAttendance::setManual($sessionId, $userId, $parsed);

        return [
            'ok' => true,
            'attended' => true,
            'joined_at' => $parsed,
            'message' => 'Đã điểm danh có mặt.',
        ];
    }

    /**
     * @return array{ok: bool, marked: int, message: string}|null
     */
    public function markAllPresent(User $viewer, int $courseId, int $userId): ?array
    {
        $course = $this->resolveCourse($viewer, $courseId);

        if ($course === null || ! CourseMember::isMemberOf($courseId, $userId)) {
            return null;
        }

        $sessionIds = CourseSession::query()
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $now = now()->format('Y-m-d H:i:s');
        $marked = 0;

        foreach ($sessionIds as $sessionId) {
            if (! CourseSessionAttendance::hasAttended($sessionId, $userId)) {
                CourseSessionAttendance::setManual($sessionId, $userId, $now);
                $marked++;
            }
        }

        return [
            'ok' => true,
            'marked' => $marked,
            'message' => $marked > 0
                ? "Đã điểm danh thêm {$marked} buổi còn thiếu."
                : 'Học viên đã có mặt đủ các buổi.',
        ];
    }

    private function parseJoinedAt(?string $raw): string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return now()->format('Y-m-d H:i:s');
        }

        $raw = str_replace('T', ' ', $raw);

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw) === 1) {
            $raw .= ':00';
        }

        $ts = strtotime($raw);

        return $ts ? date('Y-m-d H:i:s', $ts) : now()->format('Y-m-d H:i:s');
    }
}
