<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\OnlineRoom;
use App\Models\OnlineRoomParticipant;
use App\Support\ClassroomConfig;
use App\Models\StudyProgramLevel;
use App\Models\StudyProgramSession;

class StudentSessionCatalogService
{
    /** @var array<int, bool> */
    private static array $teacherPresentCache = [];

    /** @var array<string, list<array<string, mixed>>> */
    private static array $studentCourseCatalogCache = [];

    /**
     * Clear catalog cache if needed.
     */
    public static function clearStaticCache(): void
    {
        self::$studentCourseCatalogCache = [];
        self::$teacherPresentCache = [];
    }

    public function clearCache(): void
    {
        self::clearStaticCache();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forStudentCourse(int $courseId, int $userId): array
    {
        if ($courseId <= 0 || $userId <= 0 || ! CourseMember::isMemberOf($courseId, $userId)) {
            return [];
        }

        $cacheKey = "{$courseId}:{$userId}";
        if (isset(self::$studentCourseCatalogCache[$cacheKey])) {
            return self::$studentCourseCatalogCache[$cacheKey];
        }

        $sessions = CourseSession::query()
            ->with(['programSession.level', 'course.teacher', 'course.room'])
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->inProgramOrder()
            ->get();

        $sessionIds = $sessions->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $attendedMap = CourseSessionAttendance::attendedMapForUser($sessionIds, $userId);

        $rows = [];

        foreach ($sessions as $session) {
            $rows[] = $this->toStudentRow($session, $userId, $attendedMap[(int) $session->id] ?? false);
        }

        $nearestId = $this->nearestSessionId($rows);

        foreach ($rows as &$row) {
            $row['is_viewable'] = $this->isViewableByNearest($row, $nearestId);
        }
        unset($row);

        return self::$studentCourseCatalogCache[$cacheKey] = $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return list<array<string, mixed>>
     */
    public function forProgramLevel(array $sessions, int $levelId): array
    {
        if ($levelId <= 0) {
            return $sessions;
        }

        return array_values(array_filter(
            $sessions,
            static fn (array $session): bool => (int) ($session['level_id'] ?? 0) === $levelId
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     */
    public function nearestSessionId(array $sessions): int
    {
        foreach ($sessions as $session) {
            if (($session['phase'] ?? '') === 'live') {
                return (int) ($session['id'] ?? 0);
            }
        }

        $nearest = null;
        $nearestStart = PHP_INT_MAX;
        $now = time();

        foreach ($sessions as $session) {
            $phase = (string) ($session['phase'] ?? '');

            if ($phase === 'ended' || $phase === 'cancelled') {
                continue;
            }

            $date = trim((string) ($session['session_date'] ?? ''));

            if ($date === '') {
                continue;
            }

            $time = trim((string) ($session['start_time'] ?? '00:00:00'));
            $ts = strtotime($date.' '.$time);

            if ($ts === false) {
                continue;
            }

            if ($ts >= $now && $ts < $nearestStart) {
                $nearestStart = $ts;
                $nearest = $session;
            }
        }

        if ($nearest !== null) {
            return (int) ($nearest['id'] ?? 0);
        }

        foreach ($sessions as $session) {
            if (($session['phase'] ?? '') === 'ended') {
                return (int) ($session['id'] ?? 0);
            }
        }

        return (int) ($sessions[0]['id'] ?? 0);
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     */
    public function resolveSelectedPublicCode(array $sessions, int $nearestSessionId, ?string $requested): ?string
    {
        if ($requested !== null && $requested !== '') {
            foreach ($sessions as $session) {
                if ((string) ($session['public_code'] ?? '') === $requested) {
                    return $requested;
                }
            }
        }

        foreach ($sessions as $session) {
            if ((int) ($session['id'] ?? 0) === $nearestSessionId && ! empty($session['is_viewable'])) {
                return (string) ($session['public_code'] ?? '');
            }
        }

        foreach ($sessions as $session) {
            if (! empty($session['is_viewable'])) {
                return (string) ($session['public_code'] ?? '');
            }
        }

        return $sessions !== [] ? (string) ($sessions[0]['public_code'] ?? '') : null;
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return array{total: int, live: int, attended: int, ended: int}
     */
    public function summarize(array $sessions): array
    {
        return [
            'total' => count($sessions),
            'live' => count(array_filter($sessions, static fn (array $s): bool => ($s['phase'] ?? '') === 'live')),
            'attended' => count(array_filter($sessions, static fn (array $s): bool => ! empty($s['has_attended']))),
            'ended' => count(array_filter($sessions, static fn (array $s): bool => ($s['phase'] ?? '') === 'ended')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toStudentRow(CourseSession $session, int $userId = 0, ?bool $hasAttended = null): array
    {
        $phase = $session->livePhase();
        $display = $session->studentDisplay();
        $course = $session->course;
        $room = $course?->room;
        $teacherInRoom = $this->teacherIsPresent($room, (int) ($course?->teacher_id ?? 0));
        $inTimeWindow = $session->isJoinable();

        return [
            'id' => (int) $session->id,
            'public_code' => (string) $session->public_code,
            'course_id' => (int) $session->course_id,
            'program_session_id' => (int) $session->program_session_id,
            'level_id' => (int) ($session->programSession?->level_id ?? 0),
            'level_title' => (string) ($session->programSession?->level?->title ?? ''),
            'title' => (string) $session->title,
            'description' => (string) ($session->description ?? ''),
            'pre_session_materials' => (string) ($session->pre_session_materials ?? ''),
            'pre_session_videos' => (string) ($session->pre_session_videos ?? ''),
            'pre_session_video_content' => (string) ($session->pre_session_video_content ?? ''),
            'pre_session_video_url' => (string) ($session->pre_session_video_url ?? ''),
            'materials' => (string) ($session->materials ?? ''),
            'session_summary' => (string) ($session->session_summary ?? ''),
            'program_knowledge_review_id' => (int) ($session->program_knowledge_review_id ?? 0),
            'session_date' => $session->dateForInput(),
            'start_time' => (string) ($session->start_time ?? ''),
            'end_time' => (string) ($session->end_time ?? ''),
            'record_video_url' => (string) ($session->record_video_url ?? ''),
            'status' => (string) $session->status,
            'online_room_id' => (int) ($session->online_room_id ?: $course?->online_room_id ?: 0),
            'teacher_id' => (int) ($course?->teacher_id ?? 0),
            'teacher_name' => (string) ($course?->teacher?->name ?? ''),
            'course_title' => (string) ($course?->title ?? ''),
            'course_status' => (string) ($course?->status ?? ''),
            'room_title' => (string) ($room?->title ?? ''),
            'room_code' => (string) ($room?->room_code ?? ''),
            'room_status' => (string) ($room?->status ?? ''),
            'phase' => $phase,
            'has_attended' => $hasAttended !== null
                ? $hasAttended
                : ($userId > 0 && CourseSessionAttendance::hasAttended((int) $session->id, $userId)),
            'teacher_in_room' => $teacherInRoom,
            'waiting_teacher' => $inTimeWindow && ! $teacherInRoom,
            'is_joinable' => $inTimeWindow,
            'display' => $display,
        ];
    }

    private function teacherIsPresent(?OnlineRoom $room, int $teacherId): bool
    {
        if ($room === null) {
            return false;
        }

        $roomId = (int) $room->id;

        if (array_key_exists($roomId, self::$teacherPresentCache)) {
            return self::$teacherPresentCache[$roomId];
        }

        $timeout = app(ClassroomConfig::class)->presenceTimeout();
        self::$teacherPresentCache[$roomId] = OnlineRoomParticipant::isTeacherPresent(
            $roomId,
            [$teacherId, (int) $room->host_id],
            $timeout
        );

        return self::$teacherPresentCache[$roomId];
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function isViewableByNearest(array $session, int $nearestId): bool
    {
        $phase = (string) ($session['phase'] ?? '');

        if ($phase === 'cancelled') {
            return false;
        }

        if ($phase === 'ended') {
            return true;
        }

        return (int) ($session['id'] ?? 0) === $nearestId && $nearestId > 0;
    }

    public function findLevel(int $programId, int $levelId): ?StudyProgramLevel
    {
        if ($programId <= 0 || $levelId <= 0) {
            return null;
        }

        return StudyProgramLevel::query()
            ->where('program_id', $programId)
            ->where('id', $levelId)
            ->first();
    }

    /**
     * @return list<StudyProgramSession>
     */
    public function programSessionsForLevel(int $levelId): array
    {
        return StudyProgramSession::query()
            ->where('level_id', $levelId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->all();
    }
}
