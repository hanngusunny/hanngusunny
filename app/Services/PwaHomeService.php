<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\OnlineRoom;
use App\Models\User;

class PwaHomeService
{
    public function __construct(
        private StudentOverviewService $studentOverview,
        private TeacherDashboardService $teacherDashboard,
        private OnlineRoomService $rooms,
        private ClassroomService $classroom,
        private StudentAssessmentService $assessments,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        if ($user->isLearner()) {
            $data = $this->forStudent($user);
        } elseif ($user->isTeacher()) {
            $data = $this->forTeacher($user);
        } else {
            $data = $this->emptyHome($user, 'Chọn phòng học bên dưới để vào lớp.');
        }

        return array_merge($this->headerFields($user), $data, [
            'onlineRooms' => $this->onlineRoomsFor($user),
            'isStaff' => $user->isStaff(),
            'pwaTab' => 'home',
        ]);
    }

    public function zoomIsLive(User $user): bool
    {
        $courseIds = $this->courseIdsForZoom($user);

        if ($courseIds === []) {
            return false;
        }

        $sessions = CourseSession::query()
            ->whereIn('course_id', $courseIds)
            ->where('status', '!=', 'cancelled')
            ->whereDate('session_date', now()->toDateString())
            ->get();

        foreach ($sessions as $session) {
            if ($session->isJoinable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    private function courseIdsForZoom(User $user): array
    {
        if ($user->isLearner()) {
            $course = CourseMember::activeCourseFor((int) $user->id);

            return $course ? [(int) $course->id] : [];
        }

        if ($user->isTeacher()) {
            return Course::activeForTeacher((int) $user->id)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function headerFields(User $user): array
    {
        $courseTitle = '';
        $roleLabel = $user->role_label;

        if ($user->isLearner()) {
            $roleLabel = 'Học viên';
            $course = CourseMember::activeCourseFor((int) $user->id);
            $courseTitle = (string) ($course?->title ?? '');
        } elseif ($user->isTeacher()) {
            $roleLabel = 'Giáo viên';
        }

        return [
            'userName' => $user->name,
            'userInitials' => User::initialsFrom($user->name),
            'userAvatar' => $user->avatarUrl(),
            'roleLabel' => $roleLabel,
            'courseTitle' => $courseTitle,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function courses(User $user): array
    {
        $levels = [];
        $teacherCourses = [];
        $header = $this->headerFields($user);

        if ($user->isLearner()) {
            $overview = $this->studentOverview->build((int) $user->id);
            $header['courseTitle'] = (string) ($overview['course']?->title ?? '');
            $levels = $overview['levels'];
        } elseif ($user->isTeacher()) {
            $teacherCourses = Course::activeForTeacher((int) $user->id)
                ->map(static fn (Course $course): array => [
                    'id' => (int) $course->id,
                    'title' => (string) $course->title,
                    'statusLabel' => $course->statusLabel(),
                ])
                ->all();
        }

        return array_merge($header, [
            'pwaTab' => 'courses',
            'levels' => $levels,
            'teacherCourses' => $teacherCourses,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function zoom(User $user): array
    {
        return array_merge($this->headerFields($user), [
            'pwaTab' => 'zoom',
            'onlineRooms' => $this->onlineRoomsFor($user),
            'isStaff' => $user->isStaff(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function assessments(User $user): array
    {
        $items = [];
        $phaseLabels = CourseAssessment::studentPhaseLabels();

        if ($user->isLearner()) {
            $course = CourseMember::activeCourseFor((int) $user->id);
            $items = $course ? $this->assessments->listForStudentCourse((int) $course->id, (int) $user->id) : [];

            foreach ($items as $index => $item) {
                $items[$index]['take_url'] = route('pwa.assessments.show', (int) ($item['id'] ?? 0));
            }
        } elseif ($user->isTeacher()) {
            $courseIds = Course::activeForTeacher((int) $user->id)->pluck('id');

            if ($courseIds->isNotEmpty()) {
                $items = CourseAssessment::query()
                    ->with('slots')
                    ->whereIn('course_id', $courseIds)
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('sort_order')
                    ->orderBy('assessment_date')
                    ->orderBy('id')
                    ->get()
                    ->map(static function (CourseAssessment $row) use ($phaseLabels): array {
                        $phase = $row->timePhase();

                        return [
                            'id' => (int) $row->id,
                            'title' => (string) $row->title,
                            'phase' => $phase,
                            'display' => [
                                'dateLabel' => $row->dateForInput() !== ''
                                    ? $row->assessment_date->format('d/m/Y')
                                    : '',
                            ],
                            'phaseLabel' => $phaseLabels[$phase] ?? $phase,
                            'question_count' => 0,
                            'can_open' => false,
                            'is_takeable' => false,
                            'take_url' => '',
                        ];
                    })
                    ->all();
            }
        }

        foreach ($items as $index => $item) {
            if (! isset($items[$index]['phaseLabel'])) {
                $phase = (string) ($item['phase'] ?? '');
                $items[$index]['phaseLabel'] = $phaseLabels[$phase] ?? ($item['display']['phase'] ?? $phase);
            }
        }

        return array_merge($this->headerFields($user), [
            'pwaTab' => 'assessments',
            'assessments' => $items,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(User $user): array
    {
        return array_merge($this->headerFields($user), [
            'pwaTab' => 'profile',
            'user' => $user,
            'userAvatar' => $user->avatarUrl(),
            'userEmail' => (string) ($user->email ?? ''),
            'userPhone' => (string) ($user->phone ?? ''),
            'userUsername' => (string) ($user->username ?? ''),
            'userStudentCode' => (string) ($user->student_code ?? ''),
            'userStatusLabel' => (string) ($user->status_label ?? ''),
            'joinedLabel' => $user->created_at ? $user->created_at->format('d/m/Y') : '',
            'lastLoginLabel' => $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function forStudent(User $user): array
    {
        $overview = $this->studentOverview->build((int) $user->id);
        $next = $overview['nextSchedule'];
        $course = $overview['course'];
        $session = is_array($next['session'] ?? null) ? $next['session'] : [];
        $room = $this->roomForCourse($course);

        return $this->payload($user, [
            'roleLabel' => 'Học viên',
            'courseTitle' => (string) ($course?->title ?? ''),
            'next' => $next,
            'sessionTitle' => (string) ($next['sessionTitle'] ?? ''),
            'phase' => (string) ($next['phase'] ?? ''),
            'publicCode' => (string) ($next['publicCode'] ?? ''),
            'teacherName' => (string) ($session['teacher_name'] ?? ''),
            'waitingTeacher' => ! empty($session['waiting_teacher']),
            'isJoinable' => ! empty($next['isJoinable']),
            'room' => $room,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function forTeacher(User $user): array
    {
        $dash = $this->teacherDashboard->build((int) $user->id);
        $session = $dash['featureLiveSession'] ?? $dash['nearestUpcomingSession'] ?? null;

        if (! is_array($session)) {
            return $this->emptyHome($user, 'Chưa có buổi dạy sắp tới.');
        }

        $display = is_array($session['display'] ?? null) ? $session['display'] : [];
        $phase = (string) ($session['phase'] ?? '');
        $title = (string) ($session['title'] ?? 'Buổi học');
        $scheduleWhen = '';

        if (! empty($display['hasSchedule'])) {
            $scheduleWhen = trim(($display['weekdayFull'] ?? '').', '.($display['dateLabel'] ?? ''));
            $timeLabel = trim((string) ($display['timeLabel'] ?? ''));
            if ($timeLabel !== '') {
                $scheduleWhen = $scheduleWhen !== '' ? $scheduleWhen.' · '.$timeLabel : $timeLabel;
            }
        }

        $room = [
            'id' => (int) ($session['room_id'] ?? 0),
            'code' => (string) ($session['room_code'] ?? ''),
            'title' => (string) ($session['course_title'] ?? $title),
        ];

        return $this->payload($user, [
            'roleLabel' => 'Giáo viên',
            'courseTitle' => (string) ($session['course_title'] ?? ''),
            'next' => [
                'hasSession' => true,
                'label' => $phase === 'live' ? 'Đang tới giờ dạy' : 'Buổi dạy sắp tới',
                'detail' => $title.($scheduleWhen !== '' ? ' · '.$scheduleWhen : ''),
                'sessionTitle' => $title,
                'scheduleWhen' => $scheduleWhen,
                'timeLabel' => trim((string) ($display['timeLabel'] ?? '')),
                'countdownLabel' => trim((string) ($display['countdownLabel'] ?? '')),
                'durationLabel' => trim((string) ($display['durationLabel'] ?? '')),
                'opensAtLabel' => trim((string) ($display['opensAtLabel'] ?? '')),
                'hasSchedule' => ! empty($display['hasSchedule']),
                'phase' => $phase,
                'publicCode' => (string) ($session['public_code'] ?? ''),
                'isJoinable' => ! empty($session['is_joinable']),
                'levelTitle' => '',
            ],
            'sessionTitle' => $title,
            'phase' => $phase,
            'publicCode' => (string) ($session['public_code'] ?? ''),
            'teacherName' => $user->name,
            'waitingTeacher' => false,
            'isJoinable' => ! empty($session['is_joinable']),
            'room' => $room,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function onlineRoomsFor(User $user): array
    {
        $rooms = [];

        foreach ($this->classroom->availableForUser($user) as $room) {
            if (! $room instanceof OnlineRoom) {
                continue;
            }

            $status = (string) $room->status;
            $start = $room->scheduled_start;

            $rooms[] = [
                'id' => (int) $room->id,
                'title' => (string) ($room->title ?: 'Phòng học online'),
                'description' => (string) ($room->description ?? ''),
                'code' => (string) $room->room_code,
                'status' => $status,
                'statusLabel' => $room->statusLabel(),
                'hostName' => (string) ($room->host?->name ?? ''),
                'startLabel' => $start ? $start->format('d/m/Y H:i') : '',
                'onlineCount' => (int) ($room->online_count ?? 0),
                'maxParticipants' => (int) $room->max_participants,
                'isLive' => $status === 'live',
                'hasPassword' => filled($room->room_password),
            ];
        }

        return $rooms;
    }

    /**
     * @return array{id: int, code: string, title: string}
     */
    private function roomForCourse(?Course $course): array
    {
        if ($course === null) {
            return ['id' => 0, 'code' => '', 'title' => ''];
        }

        try {
            $room = $this->rooms->ensure($course);
        } catch (\Throwable $e) {
            report($e);
            $room = $course->room instanceof OnlineRoom ? $course->room : null;
        }

        if (! $room instanceof OnlineRoom) {
            return ['id' => 0, 'code' => '', 'title' => (string) $course->title];
        }

        return [
            'id' => (int) $room->id,
            'code' => (string) $room->room_code,
            'title' => (string) ($room->title ?: $course->title),
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(User $user, array $extra): array
    {
        $room = $extra['room'] ?? ['id' => 0, 'code' => '', 'title' => ''];
        $next = $extra['next'] ?? [];
        $hasSession = ! empty($next['hasSession']);
        $isJoinable = ! empty($extra['isJoinable']);
        $roomId = (int) ($room['id'] ?? 0);

        return array_merge([
            'user' => $user,
            'userName' => $user->name,
            'userInitials' => User::initialsFrom($user->name),
            'emptyMessage' => '',
            'hasSession' => $hasSession,
            'canJoin' => $isJoinable && (($extra['publicCode'] ?? '') !== '' || $roomId > 0),
            'roomUrl' => $roomId > 0 ? $this->classroom->lobbyUrl($roomId) : '',
        ], $extra);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyHome(User $user, string $message): array
    {
        return $this->payload($user, [
            'roleLabel' => $user->role_label,
            'courseTitle' => '',
            'next' => [
                'hasSession' => false,
                'label' => 'Chưa có buổi học',
                'detail' => $message,
                'sessionTitle' => '',
                'scheduleWhen' => '',
                'timeLabel' => '',
                'countdownLabel' => '',
                'durationLabel' => '',
                'opensAtLabel' => '',
                'hasSchedule' => false,
                'phase' => '',
                'publicCode' => '',
                'isJoinable' => false,
                'levelTitle' => '',
            ],
            'sessionTitle' => '',
            'phase' => '',
            'publicCode' => '',
            'teacherName' => '',
            'waitingTeacher' => false,
            'isJoinable' => false,
            'room' => ['id' => 0, 'code' => '', 'title' => ''],
            'emptyMessage' => $message,
        ]);
    }
}
