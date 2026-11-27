<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TeacherScheduleCalendarService
{
    /**
     * Bảng màu cố định cho từng khóa học theo Figma (#549:2765 & #549:2734).
     *
     * @var list<string>
     */
    private const COURSE_COLORS = ['orange', 'blue', 'purple', 'green', 'pink', 'teal'];

    public function __construct(
        private OnlineRoomService $rooms,
        private ClassroomService $classroom,
    ) {
    }

    /**
     * @return array{
     *   ready: bool,
     *   courses: list<array<string, mixed>>,
     *   sessions: list<array<string, mixed>>,
     *   stats: array<string, int>,
     *   todaySessions: list<array<string, mixed>>,
     *   reminders: list<array<string, mixed>>
     * }
     */
    public function build(int $teacherId): array
    {
        if ($teacherId <= 0 || ! $this->tablesReady()) {
            return $this->emptyPayload(false);
        }

        $phaseLabels = CourseSession::phaseLabels();

        $courses = Course::activeForTeacher($teacherId);
        $courses->load([
            'room',
            'program.levels' => static fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ]);
        $courses->loadCount([
            'activeMembers as member_count',
        ]);

        if ($courses->isEmpty()) {
            return $this->emptyPayload(true);
        }

        $courseIds = $courses->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $courseById = $courses->keyBy('id');

        // Tải toàn bộ buổi không bị hủy theo đúng thứ tự chương trình để tính số thứ tự buổi (Buổi N) chuẩn xác
        $allCourseSessions = CourseSession::query()
            ->whereIn('course_sessions.course_id', $courseIds)
            ->where('course_sessions.status', '!=', 'cancelled')
            ->with(['programSession.level'])
            ->inProgramOrder()
            ->get();

        foreach ($allCourseSessions as $session) {
            $course = $courseById->get((int) $session->course_id);
            if ($course !== null) {
                $session->setRelation('course', $course);
            }
        }

        /** @var Collection<int, Collection<int, CourseSession>> $sessionsByCourse */
        $sessionsByCourse = $allCourseSessions->groupBy('course_id');

        $courseMetaById = [];
        $coursesPayload = [];
        $sessionOrdinals = [];
        $timedSessions = [];

        foreach ($courses->values() as $courseIdx => $course) {
            $courseId = (int) $course->id;
            $color = self::COURSE_COLORS[$courseIdx % count(self::COURSE_COLORS)];
            $memberCount = (int) ($course->member_count ?? 0);

            /** @var Collection<int, CourseSession> $courseSessions */
            $courseSessions = $sessionsByCourse->get($courseId, collect());
            $courseLevelTitle = '';
            $hasLiveSession = false;

            foreach ($courseSessions->values() as $idx => $session) {
                $ordinal = $idx + 1;
                $sessionOrdinals[(int) $session->id] = $ordinal;

                if ($courseLevelTitle === '') {
                    $phase = $session->livePhase();
                    if ($phase === 'live' || $phase === 'upcoming') {
                        $courseLevelTitle = trim((string) ($session->programSession?->level?->title ?? ''));
                    }
                }

                if ($session->isTimed()) {
                    if ($session->livePhase() === 'live') {
                        $hasLiveSession = true;
                    }
                    $timedSessions[] = $session;
                }
            }

            if ($courseLevelTitle === '') {
                $courseLevelTitle = trim((string) ($course->program?->levels?->first()?->title ?? ''));
            }

            $roomLobbyUrl = null;
            $room = $course->room;
            if ($room === null && $hasLiveSession) {
                try {
                    $room = $this->rooms->ensure($course);
                    if ($room !== null) {
                        $course->setRelation('room', $room);
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            if ($room !== null) {
                $roomLobbyUrl = $this->classroom->lobbyUrl((int) $room->id);
            }

            $meta = [
                'id' => $courseId,
                'title' => (string) $course->title,
                'level_title' => $courseLevelTitle,
                'member_count' => $memberCount,
                'color' => $color,
                'room_lobby_url' => $roomLobbyUrl,
            ];

            $courseMetaById[$courseId] = $meta;
            $coursesPayload[] = [
                'id' => $courseId,
                'title' => $meta['title'],
                'level_title' => $meta['level_title'],
                'member_count' => $meta['member_count'],
                'color' => $color,
            ];
        }

        // Sắp xếp toàn bộ buổi đã lên lịch theo thời gian tăng dần
        usort($timedSessions, static function (CourseSession $a, CourseSession $b): int {
            $cmp = strcmp($a->dateForInput(), $b->dateForInput());
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcmp($a->timeForInput($a->start_time), $b->timeForInput($b->start_time));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = ((int) $a->sort_order) <=> ((int) $b->sort_order);

            return $cmp !== 0 ? $cmp : ((int) $a->id <=> (int) $b->id);
        });

        $sessions = [];
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');
        $stats = [
            'today' => 0,
            'live' => 0,
            'month' => 0,
        ];
        $todaySessions = [];
        $reminders = [];
        $now = time();
        $reminderBefore = $now + (48 * 3600);

        foreach ($timedSessions as $session) {
            $courseId = (int) $session->course_id;
            $courseMeta = $courseMetaById[$courseId] ?? [
                'id' => $courseId,
                'title' => (string) ($session->course?->title ?? ''),
                'level_title' => '',
                'member_count' => 0,
                'color' => 'orange',
                'room_lobby_url' => null,
            ];
            $ordinal = $sessionOrdinals[(int) $session->id] ?? 1;

            $formatted = $this->formatSession($session, $ordinal, $courseMeta, $phaseLabels, $now);
            $sessions[] = $formatted;

            $sessionDate = $session->dateForInput();
            $phase = (string) ($formatted['phase'] ?? 'upcoming');

            if ($sessionDate === $today) {
                $stats['today']++;
                $todaySessions[] = $formatted;
            }

            if ($phase === 'live') {
                $stats['live']++;
            }

            if (str_starts_with($sessionDate, $currentMonth)) {
                $stats['month']++;
            }

            if ($phase === 'upcoming') {
                $window = $session->window();

                if ($window['start'] >= $now && $window['start'] <= $reminderBefore) {
                    $item = $formatted;
                    $item['reminder_hint'] = $this->buildReminderHint((int) $window['start'], $now);
                    $reminders[] = $item;
                }
            }
        }

        usort($todaySessions, static fn (array $a, array $b): int => strcmp((string) $a['start_at'], (string) $b['start_at']));
        usort($reminders, static fn (array $a, array $b): int => strcmp((string) $a['start_at'], (string) $b['start_at']));
        $reminders = array_slice($reminders, 0, 5);

        /** @var list<array{icon: string, icon_fa: string, pill: string}> $reminderStyles */
        $reminderStyles = [
            ['icon' => 'red', 'icon_fa' => 'fa-circle-exclamation', 'pill' => 'green'],
            ['icon' => 'orange', 'icon_fa' => 'fa-bullhorn', 'pill' => 'orange'],
            ['icon' => 'blue', 'icon_fa' => 'fa-circle-info', 'pill' => 'blue'],
        ];

        foreach ($reminders as $i => &$reminder) {
            $style = $reminderStyles[$i % count($reminderStyles)];
            $reminder['reminder_icon'] = $style['icon'];
            $reminder['reminder_icon_fa'] = $style['icon_fa'];
            $reminder['reminder_pill'] = $style['pill'];
        }
        unset($reminder);

        return [
            'ready' => true,
            'courses' => $coursesPayload,
            'sessions' => $sessions,
            'stats' => $stats,
            'todaySessions' => $todaySessions,
            'reminders' => $reminders,
        ];
    }

    /**
     * @param  array<string, mixed>  $courseMeta
     * @param  array<string, string>  $phaseLabels
     * @return array<string, mixed>
     */
    private function formatSession(
        CourseSession $session,
        int $ordinal,
        array $courseMeta,
        array $phaseLabels,
        int $now
    ): array {
        $courseId = (int) ($session->course_id ?? 0);
        $sessionId = (int) ($session->id ?? 0);
        $startTime = $session->timeForInput($session->start_time);
        $endTime = $session->timeForInput($session->end_time);
        $date = $session->dateForInput();
        $phase = $session->livePhase();
        $display = $session->studentDisplay();
        $window = $session->window();

        $rawTitle = trim((string) ($session->title ?: 'Buổi học'));
        $sessionNumber = $ordinal;
        $shortTitle = 'Buổi '.$ordinal;
        $lessonTitle = $rawTitle;

        if (preg_match('/^(Buổi\s+(\d+))\s*(?:[:\-·–]\s*(.*))?$/iu', $rawTitle, $matches) === 1) {
            $sessionNumber = (int) $matches[2];
            $shortTitle = 'Buổi '.$sessionNumber;
            $lessonTitle = trim((string) ($matches[3] ?? ''));
        } elseif (preg_match('/^(Buổi\s+bù)\s*(?:[:\-·–]\s*(.*))?$/iu', $rawTitle, $matches) === 1) {
            $shortTitle = 'Buổi bù';
            $lessonTitle = trim((string) ($matches[2] ?? ''));
        }

        $levelTitle = trim((string) ($session->programSession?->level?->title ?? ''));
        if ($levelTitle === '') {
            $levelTitle = (string) ($courseMeta['level_title'] ?? '');
        }

        $isOngoing = $phase === 'live' && $now >= $window['start'] && $now <= $window['end'];
        $startsInMinutes = null;
        if ($phase === 'live' && $now < $window['start']) {
            $startsInMinutes = max(1, (int) ceil(($window['start'] - $now) / 60));
        }

        $durationLabel = (string) ($display['durationLabel'] ?? '');
        if ($durationLabel === '' && $window['start'] < PHP_INT_MAX && $window['end'] > $window['start']) {
            $minsTotal = (int) round(($window['end'] - $window['start']) / 60);
            $hours = intdiv($minsTotal, 60);
            $mins = $minsTotal % 60;
            $durationLabel = $minsTotal >= 60
                ? ($mins > 0 ? "{$hours} giờ {$mins} phút" : "{$hours} giờ")
                : "{$minsTotal} phút";
        }

        $effectiveEndTime = $endTime !== '' ? $endTime : date('H:i', (int) $window['end']);
        $color = (string) ($courseMeta['color'] ?? 'orange');
        $roomLobbyUrl = $courseMeta['room_lobby_url'] ?? null;
        $isJoinable = $session->isJoinable() && is_string($roomLobbyUrl) && $roomLobbyUrl !== '';

        return [
            'event_type' => 'session',
            'url' => $this->sessionUrl($courseId, $sessionId),
            'id' => $sessionId,
            'course_id' => $courseId,
            'public_code' => (string) ($session->public_code ?? ''),
            'title' => $rawTitle,
            'short_title' => $shortTitle,
            'lesson_title' => $lessonTitle,
            'session_number' => $sessionNumber,
            'description' => (string) ($session->description ?? ''),
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $effectiveEndTime,
            'time_label' => $effectiveEndTime !== '' ? $startTime.' – '.$effectiveEndTime : $startTime,
            'duration_label' => $durationLabel,
            'countdown_label' => (string) ($display['countdownLabel'] ?? ''),
            'start_at' => $date.'T'.($startTime !== '' ? $startTime : '00:00'),
            'phase' => $phase,
            'phase_label' => $phaseLabels[$phase] ?? $phase,
            'is_ongoing' => $isOngoing,
            'starts_in_minutes' => $startsInMinutes,
            'is_joinable' => $isJoinable,
            'is_viewable' => true,
            'room_lobby_url' => $roomLobbyUrl,
            'course_title' => (string) ($courseMeta['title'] ?? ''),
            'level_title' => $levelTitle,
            'member_count' => (int) ($courseMeta['member_count'] ?? 0),
            'course_color' => $color,
            'color' => $color,
        ];
    }

    private function sessionUrl(int $courseId, int $sessionId): string
    {
        if ($courseId <= 0 || $sessionId <= 0) {
            return route('dashboard');
        }

        return route('teacher.course.show', ['id' => $courseId]).'?buoi='.$sessionId;
    }

    private function buildReminderHint(int $startTs, int $now): string
    {
        $diff = $startTs - $now;

        if ($diff <= 0) {
            return 'Đang diễn ra';
        }

        if ($diff < 3600) {
            return max(1, (int) ceil($diff / 60)).' phút nữa';
        }

        if (date('Y-m-d', $startTs) === date('Y-m-d', $now)) {
            if ($diff < 6 * 3600) {
                return max(1, (int) ceil($diff / 3600)).' giờ nữa';
            }

            return 'Hôm nay';
        }

        return 'Sắp tới';
    }

    /**
     * @return array{
     *   ready: bool,
     *   courses: list<array<string, mixed>>,
     *   sessions: list<array<string, mixed>>,
     *   stats: array<string, int>,
     *   todaySessions: list<array<string, mixed>>,
     *   reminders: list<array<string, mixed>>
     * }
     */
    private function emptyPayload(bool $ready): array
    {
        return [
            'ready' => $ready,
            'courses' => [],
            'sessions' => [],
            'stats' => ['today' => 0, 'live' => 0, 'month' => 0],
            'todaySessions' => [],
            'reminders' => [],
        ];
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('course_sessions') && Schema::hasTable('courses');
    }
}
