<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseSession;
use DateTimeImmutable;
use Throwable;

class CourseScheduleService
{
    /** @var array<int, string> 1=T2 .. 7=CN */
    public const WEEKDAY_OPTIONS = [
        1 => 'T2',
        2 => 'T3',
        3 => 'T4',
        4 => 'T5',
        5 => 'T6',
        6 => 'T7',
        7 => 'CN',
    ];

    public function __construct(private OnlineRoomService $rooms)
    {
    }

    /**
     * @return array<int, string>
     */
    public function weekdayOptions(): array
    {
        return self::WEEKDAY_OPTIONS;
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<int> PHP date('w'): 0=CN, 1=T2, …, 6=T7
     */
    public function normalizeWeekdays(array $raw): array
    {
        $days = [];

        foreach ($raw as $value) {
            $key = (int) $value;
            if ($key >= 1 && $key <= 7) {
                $days[] = $key === 7 ? 0 : $key;
            }
        }

        $days = array_values(array_unique($days));
        sort($days);

        return $days;
    }

    /**
     * @param  list<int>  $phpWeekdays
     */
    public function encodeWeekdays(array $phpWeekdays): ?string
    {
        $phpWeekdays = $this->normalizeWeekdaysFromPhp($phpWeekdays);

        return $phpWeekdays === [] ? null : json_encode($phpWeekdays, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<int>
     */
    public function decodeWeekdays(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $this->normalizeWeekdaysFromPhp($decoded) : [];
    }

    /**
     * @return list<int> UI keys 1..7
     */
    public function decodeWeekdaysForForm(?string $raw): array
    {
        $keys = [];
        foreach ($this->decodeWeekdays($raw) as $phpDay) {
            $keys[] = $phpDay === 0 ? 7 : $phpDay;
        }
        sort($keys);

        return $keys;
    }

    /**
     * @param  list<mixed>|null  $posted
     * @return list<int>
     */
    public function parseWeekdaysFromPost(?array $posted): array
    {
        return is_array($posted) ? $this->normalizeWeekdays($posted) : [];
    }

    public function normalizeTime(?string $time): ?string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            return $time.':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time) === 1) {
            return $time;
        }

        return null;
    }

    public function formatTimeForInput(?string $time): string
    {
        $time = trim((string) $time);

        return $time === '' ? '' : (strlen($time) >= 5 ? substr($time, 0, 5) : $time);
    }

    /**
     * @param  list<int>  $weekdays
     * @return list<string>
     */
    public function buildSessionDates(string $startDate, array $weekdays, int $sessionCount): array
    {
        if ($sessionCount <= 0 || $weekdays === [] || $startDate === '') {
            return [];
        }

        $weekdays = $this->normalizeWeekdaysFromPhp($weekdays);

        try {
            $cursor = new DateTimeImmutable($startDate);
        } catch (Throwable) {
            return [];
        }

        $dates = [];
        for ($i = 0; $i < $sessionCount; $i++) {
            $guard = 0;
            while (! in_array((int) $cursor->format('w'), $weekdays, true)) {
                $cursor = $cursor->modify('+1 day');
                $guard++;
                if ($guard > 400) {
                    break 2;
                }
            }
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }

        return $dates;
    }

    /**
     * @param  list<int>  $weekdays
     */
    public function canAutoApply(?string $startDate, array $weekdays, ?string $startTime): bool
    {
        return trim((string) $startDate) !== ''
            && $weekdays !== []
            && $this->normalizeTime($startTime) !== null;
    }

    /**
     * @param  list<int>  $weekdays
     */
    public function applyToCourseSessions(
        Course $course,
        string $startDate,
        array $weekdays,
        string $startTime,
        ?string $endTime = null
    ): int {
        $startTime = $this->normalizeTime($startTime);
        $endTime = $this->normalizeTime($endTime);

        if ($startTime === null || ! $this->canAutoApply($startDate, $weekdays, $startTime)) {
            return 0;
        }

        $sessions = $course->sessions()->get()->values();
        if ($sessions->isEmpty()) {
            return 0;
        }

        $dates = $this->buildSessionDates($startDate, $weekdays, $sessions->count());
        $updated = 0;

        foreach ($sessions as $index => $session) {
            $date = $dates[$index] ?? null;
            if ($date === null) {
                continue;
            }
            $session->update([
                'session_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);
            $updated++;
        }

        $this->rooms->syncSchedule($course->fresh(['sessions']) ?? $course);

        return $updated;
    }

    /**
     * @return array{updated: int, from_session_no: int, error?: string}
     */
    public function shiftSessionsFrom(Course $course, int $fromSessionId, string $newAnchorDate): array
    {
        $newAnchorDate = trim($newAnchorDate);
        if ($newAnchorDate === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $newAnchorDate) !== 1) {
            return ['updated' => 0, 'from_session_no' => 0, 'error' => 'Ngày lùi không hợp lệ.'];
        }

        try {
            new DateTimeImmutable($newAnchorDate);
        } catch (Throwable) {
            return ['updated' => 0, 'from_session_no' => 0, 'error' => 'Ngày lùi không hợp lệ.'];
        }

        $sessions = $course->sessions()->get()->values();
        if ($sessions->isEmpty()) {
            return ['updated' => 0, 'from_session_no' => 0, 'error' => 'Khóa học chưa có buổi học.'];
        }

        $fromIndex = $sessions->search(fn (CourseSession $row) => (int) $row->id === $fromSessionId);
        if ($fromIndex === false) {
            return ['updated' => 0, 'from_session_no' => 0, 'error' => 'Không tìm thấy buổi học được chọn.'];
        }

        $weekdays = $this->resolveWeekdaysForCourse($course, $sessions->all());
        if ($weekdays === []) {
            return [
                'updated' => 0,
                'from_session_no' => $fromIndex + 1,
                'error' => 'Khóa chưa có lịch học trong tuần. Hãy cấu hình tại phần Lịch học mặc định khi sửa khóa học.',
            ];
        }

        $remaining = $sessions->count() - $fromIndex;
        $dates = $this->buildSessionDates($newAnchorDate, $weekdays, $remaining);
        if ($dates === [] || count($dates) < $remaining) {
            return [
                'updated' => 0,
                'from_session_no' => $fromIndex + 1,
                'error' => 'Không thể xếp lịch với ngày và các ngày học trong tuần đã chọn.',
            ];
        }

        $updated = 0;
        for ($offset = 0; $offset < $remaining; $offset++) {
            $session = $sessions[$fromIndex + $offset];
            $date = $dates[$offset] ?? null;
            if (! $session || $date === null) {
                continue;
            }
            $session->update(['session_date' => $date]);
            $updated++;
        }

        if ($fromIndex === 0) {
            $course->update(['schedule_start_date' => $dates[0]]);
        }

        $this->rooms->syncSchedule($course->fresh(['sessions']) ?? $course);

        return ['updated' => $updated, 'from_session_no' => $fromIndex + 1];
    }

    /**
     * @param  list<CourseSession>  $sessions
     * @return list<int>
     */
    public function resolveWeekdaysForCourse(Course $course, array $sessions): array
    {
        $weekdays = $this->decodeWeekdays($course->weekly_schedule_days);
        if ($weekdays !== []) {
            return $weekdays;
        }

        $inferred = [];
        foreach ($sessions as $session) {
            $date = $session->dateForInput();
            if ($date === '') {
                continue;
            }
            $ts = strtotime($date);
            if ($ts !== false) {
                $inferred[] = (int) date('w', $ts);
            }
        }

        return $this->normalizeWeekdaysFromPhp(array_values(array_unique($inferred)));
    }

    /**
     * @param  list<int>  $phpWeekdays
     */
    public function formatWeekdayLabels(array $phpWeekdays): string
    {
        $labels = [];
        foreach ($this->normalizeWeekdaysFromPhp($phpWeekdays) as $day) {
            $key = $day === 0 ? 7 : $day;
            $labels[] = self::WEEKDAY_OPTIONS[$key] ?? (string) $day;
        }

        return implode(', ', $labels);
    }

    /**
     * @return array<string, mixed>
     */
    public function collectFields(array $input): array
    {
        $startDate = trim((string) ($input['schedule_start_date'] ?? ''));
        $weekdays = $this->parseWeekdaysFromPost(isset($input['weekly_days']) && is_array($input['weekly_days']) ? $input['weekly_days'] : null);

        return [
            'schedule_start_date' => $startDate !== '' ? $startDate : null,
            'default_start_time' => $this->normalizeTime((string) ($input['default_start_time'] ?? '')),
            'default_end_time' => $this->normalizeTime((string) ($input['default_end_time'] ?? '')),
            'weekly_schedule_days' => $this->encodeWeekdays($weekdays),
        ];
    }

    public function applyIfConfigured(Course $course, array $scheduleFields): int
    {
        $startDate = (string) ($scheduleFields['schedule_start_date'] ?? '');
        $startTime = (string) ($scheduleFields['default_start_time'] ?? '');
        $endTime = isset($scheduleFields['default_end_time']) ? (string) $scheduleFields['default_end_time'] : null;
        $weekdays = $this->decodeWeekdays($scheduleFields['weekly_schedule_days'] ?? null);

        if (! $this->canAutoApply($startDate, $weekdays, $startTime)) {
            return 0;
        }

        return $this->applyToCourseSessions(
            $course,
            $startDate,
            $weekdays,
            $startTime,
            $endTime !== '' ? $endTime : null
        );
    }

    public function normalizeClock(?string $time): ?string
    {
        return $this->normalizeTime($time);
    }

    /**
     * @param  list<int>  $days
     * @return list<int>
     */
    private function normalizeWeekdaysFromPhp(array $days): array
    {
        $normalized = [];
        foreach ($days as $day) {
            $day = (int) $day;
            if ($day >= 0 && $day <= 6) {
                $normalized[] = $day;
            }
        }
        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }
}
