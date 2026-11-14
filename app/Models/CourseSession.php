<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSession extends Model
{
    protected $fillable = [
        'public_code',
        'course_id',
        'program_session_id',
        'title',
        'description',
        'pre_session_materials',
        'pre_session_videos',
        'pre_session_video_content',
        'pre_session_video_url',
        'materials',
        'session_summary',
        'program_knowledge_review_id',
        'session_date',
        'start_time',
        'end_time',
        'record_video_url',
        'sort_order',
        'status',
        'online_room_id',
    ];

    public const EARLY_JOIN_MINUTES = 15;

    public const DEFAULT_DURATION_HOURS = 2;

    protected $casts = [
        'session_date' => 'date',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function programSession(): BelongsTo
    {
        return $this->belongsTo(StudyProgramSession::class, 'program_session_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(OnlineRoom::class, 'online_room_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseSessionAttendance::class, 'session_id');
    }

    public function reflections(): HasMany
    {
        return $this->hasMany(CourseSessionReflection::class, 'course_session_id');
    }

    public function quizAnswers(): HasMany
    {
        return $this->hasMany(CourseSessionQuizAnswer::class, 'course_session_id');
    }

    public function livePhase(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->status === 'completed') {
            return 'ended';
        }

        if (! $this->isTimed()) {
            return 'upcoming';
        }

        $now = time();
        $window = $this->window();

        if ($now < $window['early']) {
            return 'upcoming';
        }

        if ($now > $window['end']) {
            return 'ended';
        }

        return 'live';
    }

    public function isJoinable(): bool
    {
        return $this->status !== 'cancelled' && $this->isTimed() && $this->livePhase() === 'live';
    }

    /**
     * @return array<string, mixed>
     */
    public function studentDisplay(): array
    {
        $phase = $this->livePhase();
        $startTime = $this->timeForInput($this->start_time);
        $endTime = $this->timeForInput($this->end_time);
        $hasSchedule = $this->isTimed();
        $timeLabel = $startTime;

        if ($startTime !== '' && $endTime !== '') {
            $timeLabel = $startTime.' – '.$endTime;
        }

        $display = [
            'phase' => $phase,
            'hasSchedule' => $hasSchedule,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'timeLabel' => $timeLabel,
            'durationLabel' => '',
            'countdownLabel' => $this->countdownLabel(),
            'opensAtLabel' => '',
            'dateDay' => '',
            'dateMonth' => '',
            'weekdayFull' => '',
            'weekdayShort' => '',
            'dateLabel' => 'Chưa lên lịch',
        ];

        if (! $hasSchedule) {
            return $display;
        }

        $dateTs = strtotime($this->dateForInput()) ?: time();
        $weekdaysFull = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
        $weekdaysShort = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
        $weekdayIndex = (int) date('w', $dateTs);

        if ($startTime !== '' && $endTime !== '') {
            $startTs = strtotime($this->dateForInput().' '.$startTime.':00');
            $endTs = strtotime($this->dateForInput().' '.$endTime.':00');

            if ($endTs > $startTs) {
                $minutes = (int) round(($endTs - $startTs) / 60);
                $hours = intdiv($minutes, 60);
                $mins = $minutes % 60;
                $display['durationLabel'] = $minutes >= 60
                    ? ($mins > 0 ? "{$hours} giờ {$mins} phút" : "{$hours} giờ")
                    : "{$minutes} phút";
            }
        }

        $display['dateDay'] = date('d', $dateTs);
        $display['dateMonth'] = date('m/Y', $dateTs);
        $display['weekdayFull'] = $weekdaysFull[$weekdayIndex] ?? '';
        $display['weekdayShort'] = $weekdaysShort[$weekdayIndex] ?? '';
        $display['dateLabel'] = date('d/m/Y', $dateTs);
        $display['opensAtLabel'] = date('H:i', $this->window()['early']);

        return $display;
    }

    public function countdownLabel(): string
    {
        if ($this->status === 'cancelled') {
            return 'Đã hủy';
        }

        $phase = $this->livePhase();

        if ($phase === 'live') {
            return 'Đang diễn ra';
        }

        if ($phase === 'ended') {
            return 'Đã kết thúc';
        }

        if (! $this->isTimed()) {
            return 'Chưa lên lịch';
        }

        $diff = $this->window()['early'] - time();

        if ($diff <= 0) {
            return 'Sắp mở';
        }

        $hours = intdiv($diff, 3600);
        $mins = intdiv($diff % 3600, 60);

        if ($hours >= 24) {
            return 'Còn '.intdiv($hours, 24).' ngày';
        }

        if ($hours > 0) {
            return $mins > 0 ? "Còn {$hours} giờ {$mins} phút" : "Còn {$hours} giờ";
        }

        return "Còn {$mins} phút";
    }

    /**
     * @return array<string, string>
     */
    public static function phaseLabels(): array
    {
        return [
            'upcoming' => 'Sắp diễn ra',
            'live' => 'Đang học',
            'ended' => 'Đã kết thúc',
            'cancelled' => 'Đã hủy',
        ];
    }

    public function isTimed(): bool
    {
        return filled($this->session_date) && filled($this->start_time);
    }

    /**
     * @return array{start: int, end: int, early: int}
     */
    public function window(): array
    {
        if (! $this->isTimed()) {
            return ['start' => PHP_INT_MAX, 'end' => PHP_INT_MAX, 'early' => PHP_INT_MAX];
        }

        $date = $this->session_date instanceof \DateTimeInterface
            ? $this->session_date->format('Y-m-d')
            : substr((string) $this->session_date, 0, 10);
        $startTime = $this->normalizeClock((string) $this->start_time, '09:00:00');
        $start = strtotime($date.' '.$startTime) ?: PHP_INT_MAX;

        $endTime = trim((string) ($this->end_time ?? ''));
        $end = $endTime !== ''
            ? (strtotime($date.' '.$this->normalizeClock($endTime, $endTime)) ?: $start + (self::DEFAULT_DURATION_HOURS * 3600))
            : $start + (self::DEFAULT_DURATION_HOURS * 3600);

        if ($end <= $start) {
            $end = $start + (self::DEFAULT_DURATION_HOURS * 3600);
        }

        return [
            'start' => $start,
            'end' => $end,
            'early' => $start - (self::EARLY_JOIN_MINUTES * 60),
        ];
    }

    public function timeForInput(?string $value = null): string
    {
        $time = trim((string) ($value ?? $this->start_time ?? ''));

        return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
    }

    public function dateForInput(): string
    {
        if ($this->session_date instanceof \DateTimeInterface) {
            return $this->session_date->format('Y-m-d');
        }

        $raw = trim((string) ($this->session_date ?? ''));

        return $raw !== '' ? substr($raw, 0, 10) : '';
    }

    private function normalizeClock(string $time, string $fallback): string
    {
        $time = trim($time);

        if ($time === '') {
            return $fallback;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            return $time.':00';
        }

        return substr($time, 0, 8);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CourseSessionQuestion::class, 'course_session_id');
    }

    /**
     * @return list<string>
     */
    public static function scheduleListColumns(): array
    {
        return [
            'course_sessions.id',
            'course_sessions.course_id',
            'course_sessions.program_session_id',
            'course_sessions.title',
            'course_sessions.session_date',
            'course_sessions.start_time',
            'course_sessions.end_time',
            'course_sessions.record_video_url',
            'course_sessions.sort_order',
            'course_sessions.status',
        ];
    }

    public function scopeInProgramOrder(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();
        $joins = $query->getQuery()->joins ?? [];
        $hasOrderJoin = false;

        foreach ($joins as $join) {
            if (str_contains((string) $join->table, 'sps_ord')) {
                $hasOrderJoin = true;
                break;
            }
        }

        if (! $hasOrderJoin) {
            $query->leftJoin('study_program_sessions as sps_ord', 'sps_ord.id', '=', $table.'.program_session_id')
                ->leftJoin('study_program_levels as spl_ord', 'spl_ord.id', '=', 'sps_ord.level_id');
        }

        if ($query->getQuery()->columns === null) {
            $query->select($table.'.*');
        }

        return $query
            ->orderByRaw('COALESCE(spl_ord.sort_order, 999999)')
            ->orderByRaw('COALESCE(sps_ord.sort_order, '.$table.'.sort_order, 999999)')
            ->orderBy($table.'.session_date')
            ->orderBy($table.'.start_time')
            ->orderBy($table.'.id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function findJoinableForCourse(int $courseId): ?self
    {
        if ($courseId <= 0) {
            return null;
        }

        $sessions = static::query()
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($sessions as $session) {
            if ($session->isJoinable()) {
                return $session;
            }
        }

        return null;
    }

    public static function hasOpenWaitingRoomForCourse(int $courseId): bool
    {
        if ($courseId <= 0) {
            return false;
        }

        $sessions = static::query()
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($sessions as $session) {
            if (! $session->isTimed()) {
                continue;
            }

            if (in_array($session->livePhase(), ['live', 'upcoming'], true)) {
                return true;
            }
        }

        return false;
    }

    public static function countScheduledThisWeekForTeacher(int $teacherId): int
    {
        if ($teacherId <= 0) {
            return 0;
        }

        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));

        return static::query()
            ->whereHas('course', static function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId)->where('status', Course::STATUS_ACTIVE);
            })
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('session_date')
            ->whereDate('session_date', '>=', $monday)
            ->whereDate('session_date', '<=', $sunday)
            ->count();
    }

    public static function upcomingForTeacher(int $teacherId, int $limit = 12)
    {
        if ($teacherId <= 0) {
            return collect();
        }

        return static::query()
            ->whereHas('course', static function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId)->where('status', Course::STATUS_ACTIVE);
            })
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('session_date')
            ->whereNotNull('start_time')
            ->with(['course.teacher', 'course.room'])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(static fn (self $session): bool => $session->livePhase() === 'upcoming')
            ->take($limit)
            ->values();
    }

    public static function scheduledForWeek(int $courseId, string $evalWeek)
    {
        $range = CourseMonthlyEvaluation::weekDateRange($evalWeek);

        if ($courseId <= 0 || $range === null) {
            return collect();
        }

        return static::query()
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('session_date')
            ->whereNotNull('start_time')
            ->whereDate('session_date', '>=', $range['start'])
            ->whereDate('session_date', '<', $range['end'])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
