<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Support\Facades\Schema;

class TeacherScheduleCalendarService
{
    /** @var list<string> */
    private const EVENT_COLORS = ['green', 'blue', 'orange', 'purple', 'pink', 'teal'];

    /**
     * @return array{
     *   ready: bool,
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
        $rawSessions = CourseSession::query()
            ->whereHas('course', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId)
                    ->where('status', Course::STATUS_ACTIVE);
            })
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('session_date')
            ->whereNotNull('start_time')
            ->with(['course:id,title,teacher_id,status'])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

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
        $eventIndex = 0;

        foreach ($rawSessions as $session) {
            if (! $session->isTimed()) {
                continue;
            }

            $formatted = $this->formatSession($session, $eventIndex, $phaseLabels);
            $eventIndex++;
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
            'sessions' => $sessions,
            'stats' => $stats,
            'todaySessions' => $todaySessions,
            'reminders' => $reminders,
        ];
    }

    /**
     * @param  array<string, string>  $phaseLabels
     * @return array<string, mixed>
     */
    private function formatSession(CourseSession $session, int $index, array $phaseLabels): array
    {
        $courseId = (int) ($session->course_id ?? 0);
        $sessionId = (int) ($session->id ?? 0);
        $startTime = $session->timeForInput($session->start_time);
        $endTime = $session->timeForInput($session->end_time);
        $date = $session->dateForInput();
        $phase = $session->livePhase();

        return [
            'event_type' => 'session',
            'url' => $this->sessionUrl($courseId, $sessionId),
            'id' => $sessionId,
            'public_code' => (string) ($session->public_code ?? ''),
            'title' => (string) ($session->title ?: 'Buổi học'),
            'description' => (string) ($session->description ?? ''),
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_label' => $endTime !== '' ? $startTime.' – '.$endTime : $startTime,
            'start_at' => $date.'T'.($startTime !== '' ? $startTime : '00:00'),
            'phase' => $phase,
            'phase_label' => $phaseLabels[$phase] ?? $phase,
            'is_joinable' => false,
            'is_viewable' => true,
            'course_title' => (string) ($session->course?->title ?? ''),
            'color' => self::EVENT_COLORS[$index % count(self::EVENT_COLORS)],
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
