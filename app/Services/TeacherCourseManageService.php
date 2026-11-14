<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\CourseSessionQuestion;
use App\Models\CourseSessionRoomEvent;
use App\Support\RichContent;
use App\Support\StudentQuizGrader;

class TeacherCourseManageService
{
    public function __construct(
        private ProgramCourseSyncService $sync,
        private OnlineRoomService $rooms,
        private ClassroomService $classroom,
        private StudentSessionCatalogService $catalog,
        private StudentQuizGrader $grader,
        private RichContent $richText,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(int $courseId, int $teacherId, int $selectedSessionId = 0): ?array
    {
        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->first();

        if ($course === null) {
            return null;
        }

        if ((int) $course->program_id > 0) {
            $this->sync->syncProgramToCourse((int) $course->program_id);
        }

        $members = CourseMember::activeMembersForCourse($courseId);
        $sessions = [];
        $rawSessions = CourseSession::query()
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->with(['course.teacher', 'course.room', 'programSession'])
            ->inProgramOrder()
            ->get();

        foreach ($rawSessions as $index => $session) {
            $row = $this->catalog->toStudentRow($session, 0);
            $sessionId = (int) $session->id;
            $sessions[] = array_merge($row, [
                'session_index' => $index + 1,
                'pre_question_count' => count($this->grader->readyQuestions(CourseSessionQuestion::forSessionGroup($sessionId, 'pre'))),
                'post_question_count' => count($this->grader->readyQuestions(CourseSessionQuestion::forSessionGroup($sessionId, 'post_practice'))),
                'has_pre_materials' => $this->richText->hasComment((string) ($session->pre_session_materials ?? '')),
                'has_post_materials' => $this->richText->hasComment((string) ($session->materials ?? '')),
                'has_summary' => $this->richText->hasComment((string) ($session->session_summary ?? '')),
                'attendance_count' => CourseSessionAttendance::countBySession($sessionId),
            ]);
        }

        if ($selectedSessionId <= 0) {
            $selectedSessionId = $this->defaultSessionId($sessions);
        }

        $selectedSession = null;

        foreach ($sessions as $session) {
            if ((int) ($session['id'] ?? 0) === $selectedSessionId) {
                $selectedSession = $session;
                break;
            }
        }

        if ($selectedSession === null && $sessions !== []) {
            $selectedSession = $sessions[0];
            $selectedSessionId = (int) ($selectedSession['id'] ?? 0);
        }

        $onlineRoom = null;
        $roomLobbyUrl = null;
        $canEnterRoom = false;

        if ($sessions !== []) {
            try {
                $onlineRoom = $this->rooms->ensure($course->fresh(['sessions']) ?? $course);
            } catch (\Throwable $e) {
                report($e);
            }

            $canEnterRoom = CourseSession::hasOpenWaitingRoomForCourse($courseId);

            if ($onlineRoom !== null) {
                $roomLobbyUrl = $this->classroom->lobbyUrl((int) $onlineRoom->id);
            }
        }

        return [
            'course' => $course,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'selectedSessionId' => $selectedSessionId,
            'phaseLabels' => CourseSession::phaseLabels(),
            'members' => $members,
            'memberCount' => $members->count(),
            'sessionAttendanceMap' => $selectedSessionId > 0
                ? CourseSessionAttendance::mapBySession($selectedSessionId)
                : [],
            'sessionRoomEvents' => $selectedSessionId > 0
                ? CourseSessionRoomEvent::timelineForSession($selectedSessionId)
                : [],
            'onlineRoom' => $onlineRoom,
            'canEnterRoom' => $canEnterRoom,
            'roomLobbyUrl' => $roomLobbyUrl,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     */
    private function defaultSessionId(array $sessions): int
    {
        if ($sessions === []) {
            return 0;
        }

        foreach (array_reverse($sessions) as $session) {
            if (($session['phase'] ?? '') === 'ended') {
                return (int) ($session['id'] ?? 0);
            }
        }

        foreach ($sessions as $session) {
            if (($session['phase'] ?? '') === 'live') {
                return (int) ($session['id'] ?? 0);
            }
        }

        foreach ($sessions as $session) {
            if (($session['phase'] ?? '') === 'upcoming') {
                return (int) ($session['id'] ?? 0);
            }
        }

        return (int) ($sessions[0]['id'] ?? 0);
    }
}
