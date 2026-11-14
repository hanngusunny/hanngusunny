<?php

namespace App\Models;

use App\Support\HtmlContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseMonthlyEvaluation extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'teacher_id',
        'eval_month',
        'score_attitude',
        'score_progress',
        'score_participation',
        'comment',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return array<int, string>
     */
    public static function scoreLabels(): array
    {
        return [
            1 => 'Yếu',
            2 => 'Trung bình',
            3 => 'Khá',
            4 => 'Tốt',
            5 => 'Xuất sắc',
        ];
    }

    public static function formatMonthLabel(string $evalMonth): string
    {
        $parts = explode('-', $evalMonth);

        if (count($parts) !== 2) {
            return $evalMonth;
        }

        return 'Tháng '.(int) $parts[1].'/'.$parts[0];
    }

    public static function isWeeklyPeriod(string $period): bool
    {
        return preg_match('/^\d{4}-W\d{2}$/i', $period) === 1;
    }

    public static function isMonthlyPeriod(string $period): bool
    {
        return preg_match('/^\d{4}-\d{2}$/', $period) === 1;
    }

    public static function currentWeekKey(): string
    {
        return date('o').'-W'.date('W');
    }

    public static function normalizePeriod(string $period): string
    {
        $period = trim($period);

        if (preg_match('/^(\d{4})-W(\d{1,2})$/i', $period, $matches) === 1) {
            return sprintf('%04d-W%02d', (int) $matches[1], (int) $matches[2]);
        }

        if (self::isMonthlyPeriod($period)) {
            return $period;
        }

        return self::currentWeekKey();
    }

    /**
     * @return list<string>
     */
    public static function weekOptions(int $weeksBack = 16): array
    {
        $options = [];
        $date = new \DateTime('monday this week');

        for ($i = 0; $i <= $weeksBack; $i++) {
            $weekDate = clone $date;

            if ($i > 0) {
                $weekDate->modify('-'.$i.' weeks');
            }

            $options[] = $weekDate->format('o').'-W'.$weekDate->format('W');
        }

        return array_values(array_unique($options));
    }

    public static function formatWeekLabel(string $evalWeek): string
    {
        $weekStart = self::weekToDate($evalWeek);

        if ($weekStart === null) {
            return $evalWeek;
        }

        $weekEnd = (clone $weekStart)->modify('+6 days');

        return sprintf(
            'Tuần %02d (%s – %s)',
            (int) $weekStart->format('W'),
            $weekStart->format('d/m'),
            $weekEnd->format('d/m/Y')
        );
    }

    public static function formatPeriodLabel(string $period): string
    {
        return self::isWeeklyPeriod($period)
            ? self::formatWeekLabel($period)
            : self::formatMonthLabel($period);
    }

    /**
     * @return array{year: int, week: int}
     */
    public static function parseEvalWeek(string $evalWeek): array
    {
        if (preg_match('/^(\d{4})-W(\d{2})$/i', $evalWeek, $matches) !== 1) {
            return [
                'year' => (int) date('o'),
                'week' => (int) date('W'),
            ];
        }

        return [
            'year' => (int) $matches[1],
            'week' => (int) $matches[2],
        ];
    }

    public static function canEditPeriod(string $period): bool
    {
        $period = self::normalizePeriod($period);

        if (self::isWeeklyPeriod($period)) {
            $weekStart = self::weekToDate($period);
            $current = self::weekToDate(self::currentWeekKey());

            return $weekStart !== null && $current !== null && $weekStart <= $current;
        }

        if (self::isMonthlyPeriod($period)) {
            return $period <= date('Y-m');
        }

        return false;
    }

    public static function weekToDate(string $evalWeek): ?\DateTime
    {
        if (preg_match('/^(\d{4})-W(\d{2})$/i', $evalWeek, $matches) !== 1) {
            return null;
        }

        return (new \DateTime())->setISODate((int) $matches[1], (int) $matches[2], 1);
    }

    /**
     * @return array{start: string, end: string}|null
     */
    public static function weekDateRange(string $evalWeek): ?array
    {
        $weekStart = self::weekToDate(self::normalizePeriod($evalWeek));

        if ($weekStart === null) {
            return null;
        }

        $weekEnd = (clone $weekStart)->modify('+7 days');

        return [
            'start' => $weekStart->format('Y-m-d'),
            'end' => $weekEnd->format('Y-m-d'),
        ];
    }

    /**
     * @param  list<string>  $filledWeeks
     */
    public static function resolveStudentEvalWeek(array $filledWeeks, ?string $requestedWeek = null): string
    {
        $requestedWeek = trim((string) $requestedWeek);

        if ($requestedWeek !== '' && self::isWeeklyPeriod($requestedWeek)) {
            $normalized = self::normalizePeriod($requestedWeek);

            if (self::canEditPeriod($normalized)) {
                return $normalized;
            }
        }

        if ($filledWeeks !== []) {
            return self::normalizePeriod($filledWeeks[0]);
        }

        return self::currentWeekKey();
    }

    /**
     * @param  list<string>  $filledWeeks
     * @return list<string>
     */
    public static function weekPickerOptionsForStudent(array $filledWeeks, int $weeksBack = 20): array
    {
        $merged = array_values(array_unique(array_merge($filledWeeks, self::weekOptions($weeksBack))));

        usort($merged, static fn (string $a, string $b): int => self::compareWeeklyPeriods($b, $a));

        return $merged;
    }

    public static function compareWeeklyPeriods(string $a, string $b): int
    {
        $dateA = self::weekToDate(self::normalizePeriod($a));
        $dateB = self::weekToDate(self::normalizePeriod($b));

        if ($dateA === null || $dateB === null) {
            return strcmp($b, $a);
        }

        return $dateA <=> $dateB;
    }

    /**
     * @return array{year: int, month: int}
     */
    public static function parseEvalMonth(string $evalMonth): array
    {
        $parts = explode('-', $evalMonth);

        if (count($parts) !== 2) {
            return [
                'year' => (int) date('Y'),
                'month' => (int) date('n'),
            ];
        }

        return [
            'year' => (int) $parts[0],
            'month' => (int) $parts[1],
        ];
    }

    public function hasComment(): bool
    {
        return HtmlContent::has($this->comment);
    }

    public function renderComment(): string
    {
        return HtmlContent::render($this->comment);
    }

    public function isFilled(): bool
    {
        if ($this->hasComment()) {
            return true;
        }

        foreach (['score_attitude', 'score_progress', 'score_participation'] as $field) {
            if ((int) ($this->{$field} ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<self>
     */
    public static function forStudentInCourse(int $courseId, int $userId): array
    {
        if ($courseId <= 0 || $userId <= 0) {
            return [];
        }

        return static::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->orderByDesc('eval_month')
            ->get()
            ->all();
    }

    public static function forStudent(int $courseId, int $userId, string $evalMonth): ?self
    {
        if ($courseId <= 0 || $userId <= 0 || $evalMonth === '') {
            return null;
        }

        return static::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->where('eval_month', $evalMonth)
            ->first();
    }

    /**
     * @return array<int, self>
     */
    public static function mapForCoursePeriod(int $courseId, string $period): array
    {
        if ($courseId <= 0 || $period === '') {
            return [];
        }

        $map = [];

        foreach (static::query()->where('course_id', $courseId)->where('eval_month', $period)->get() as $row) {
            $map[(int) $row->user_id] = $row;
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public static function filledWeeklyPeriodsForStudent(int $courseId, int $userId): array
    {
        $periods = [];

        foreach (static::forStudentInCourse($courseId, $userId) as $row) {
            $period = (string) $row->eval_month;

            if (! self::isWeeklyPeriod($period) || ! $row->isFilled()) {
                continue;
            }

            $periods[] = self::normalizePeriod($period);
        }

        $periods = array_values(array_unique($periods));
        usort($periods, static fn (string $a, string $b): int => self::compareWeeklyPeriods($b, $a));

        return $periods;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function upsertEvaluation(int $courseId, int $userId, int $teacherId, string $period, array $data): self
    {
        $period = self::normalizePeriod($period);

        $row = static::query()->firstOrNew([
            'course_id' => $courseId,
            'user_id' => $userId,
            'eval_month' => $period,
        ]);

        $row->teacher_id = $teacherId;
        $row->score_attitude = self::normalizeScore($data['score_attitude'] ?? null);
        $row->score_progress = self::normalizeScore($data['score_progress'] ?? null);
        $row->score_participation = self::normalizeScore($data['score_participation'] ?? null);
        $row->comment = HtmlContent::sanitize((string) ($data['comment'] ?? ''));
        $row->save();

        return $row;
    }

    public function summary(): string
    {
        if (! $this->isFilled()) {
            return '';
        }

        $labels = self::scoreLabels();
        $parts = [];

        foreach ([
            'score_attitude' => 'Thái độ',
            'score_progress' => 'Tiến độ',
            'score_participation' => 'Tham gia',
        ] as $field => $label) {
            $score = (int) ($this->{$field} ?? 0);

            if ($score >= 1 && $score <= 5) {
                $parts[] = $label.': '.($labels[$score] ?? $score);
            }
        }

        return implode(' · ', $parts);
    }

    private static function normalizeScore(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $score = (int) $value;

        return $score >= 1 && $score <= 5 ? $score : null;
    }
}
