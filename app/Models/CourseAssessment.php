<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseAssessment extends Model
{
    protected $fillable = [
        'course_id',
        'program_assessment_id',
        'title',
        'description',
        'duration_minutes',
        'assessment_date',
        'start_time',
        'end_time',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'assessment_date' => 'date',
    ];

    public function dateForInput(): string
    {
        if ($this->assessment_date instanceof \DateTimeInterface) {
            return $this->assessment_date->format('Y-m-d');
        }

        $raw = trim((string) ($this->assessment_date ?? ''));

        return $raw !== '' ? substr($raw, 0, 10) : '';
    }

    public function timeForInput(?string $value = null): string
    {
        $time = trim((string) ($value ?? $this->start_time ?? ''));

        return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public const DEFAULT_DURATION_HOURS = 2;

    public function questions(): HasMany
    {
        return $this->hasMany(CourseAssessmentQuestion::class, 'course_assessment_id')->orderBy('sort_order')->orderBy('id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(CourseAssessmentSlot::class, 'course_assessment_id')
            ->orderBy('sort_order')
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->orderBy('id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, CourseAssessmentSlot>
     */
    public function slotModels()
    {
        return $this->relationLoaded('slots') ? $this->slots : $this->slots()->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, CourseAssessmentSlot>
     */
    public function completeSlots()
    {
        return $this->slotModels()->filter(static fn (CourseAssessmentSlot $slot) => $slot->isComplete())->values();
    }

    public function isScheduled(): bool
    {
        if ($this->completeSlots()->isNotEmpty()) {
            return true;
        }

        return $this->dateForInput() !== '' && $this->timeForInput($this->start_time) !== '';
    }

    /**
     * @return array{start: int, end: int}
     */
    public function window(): array
    {
        $picked = $this->pickSlot();
        if ($picked instanceof CourseAssessmentSlot) {
            return $picked->window((int) ($this->duration_minutes ?? 0));
        }

        $date = $this->dateForInput();
        $startTime = $this->timeForInput($this->start_time);
        $endTime = $this->timeForInput($this->end_time);
        $start = strtotime($date.' '.$startTime.':00') ?: 0;
        $end = $endTime !== ''
            ? (strtotime($date.' '.$endTime.':00') ?: $start)
            : $start + (self::DEFAULT_DURATION_HOURS * 3600);

        if ($end <= $start) {
            $end = $start + (self::DEFAULT_DURATION_HOURS * 3600);
        }

        return ['start' => $start, 'end' => $end];
    }

    public function pickSlot(): ?CourseAssessmentSlot
    {
        $complete = $this->completeSlots();
        if ($complete->isEmpty()) {
            return null;
        }

        $now = time();
        $duration = (int) ($this->duration_minutes ?? 0);
        $open = $complete->first(function (CourseAssessmentSlot $slot) use ($now, $duration) {
            $window = $slot->window($duration);

            return $now >= $window['start'] && $now <= $window['end'];
        });
        if ($open) {
            return $open;
        }

        $upcoming = $complete->first(function (CourseAssessmentSlot $slot) use ($now, $duration) {
            return $slot->window($duration)['start'] > $now;
        });

        return $upcoming ?: $complete->last();
    }

    public function timePhase(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if (! $this->isScheduled()) {
            return 'upcoming';
        }

        $now = time();
        $duration = (int) ($this->duration_minutes ?? 0);
        $complete = $this->completeSlots();
        if ($complete->isNotEmpty()) {
            foreach ($complete as $slot) {
                $window = $slot->window($duration);
                if ($now >= $window['start'] && $now <= $window['end']) {
                    return 'open';
                }
            }
            foreach ($complete as $slot) {
                if ($slot->window($duration)['start'] > $now) {
                    return 'upcoming';
                }
            }

            return 'closed';
        }

        $window = $this->window();
        if ($now < $window['start']) {
            return 'upcoming';
        }
        if ($now <= $window['end']) {
            return 'open';
        }

        return 'closed';
    }

    public function hasDateInMonth(string $month): bool
    {
        foreach ($this->completeSlots() as $slot) {
            if (str_starts_with($slot->dateForInput(), $month)) {
                return true;
            }
        }

        $date = $this->dateForInput();

        return $date !== '' && str_starts_with($date, $month);
    }

    public function syncScheduleFromSlots(): void
    {
        $slot = $this->pickSlot();
        if (! $slot || ! $slot->isComplete()) {
            $this->update([
                'assessment_date' => null,
                'start_time' => null,
                'end_time' => null,
            ]);

            return;
        }

        $this->update([
            'assessment_date' => $slot->dateForInput(),
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
        ]);
    }

    public function isScheduleOpen(): bool
    {
        return $this->timePhase() === 'open';
    }

    public function studentPhase(bool $hasSubmitted, bool $allowRetake): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($hasSubmitted && ! $allowRetake) {
            return 'completed';
        }

        $timePhase = $this->timePhase();

        if ($hasSubmitted && $allowRetake) {
            return $timePhase === 'open' ? 'open' : 'retake';
        }

        return $timePhase;
    }

    /**
     * @return array<string, mixed>
     */
    public function toStudentArray(): array
    {
        return [
            'id' => (int) $this->id,
            'course_id' => (int) $this->course_id,
            'title' => (string) $this->title,
            'description' => (string) ($this->description ?? ''),
            'duration_minutes' => (int) ($this->duration_minutes ?? 0),
            'assessment_date' => $this->dateForInput(),
            'start_time' => (string) ($this->start_time ?? ''),
            'end_time' => (string) ($this->end_time ?? ''),
            'slots' => $this->completeSlots()->map(function (CourseAssessmentSlot $slot) {
                return [
                    'id' => (int) $slot->id,
                    'date' => $slot->dateForInput(),
                    'start_time' => $slot->timeForInput($slot->start_time),
                    'end_time' => $slot->timeForInput($slot->end_time),
                ];
            })->values()->all(),
            'status' => (string) $this->status,
            'sort_order' => (int) $this->sort_order,
        ];
    }

    public function livePhase(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        $phase = $this->timePhase();
        if ($phase === 'open') {
            return 'live';
        }
        if ($phase === 'closed') {
            return 'ended';
        }

        return 'upcoming';
    }

    /**
     * @return array<string, string>
     */
    public static function phaseLabels(): array
    {
        return [
            'upcoming' => 'Sắp diễn ra',
            'live' => 'Đang mở',
            'ended' => 'Đã kết thúc',
            'cancelled' => 'Đã hủy',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function studentPhaseLabels(): array
    {
        return [
            'upcoming' => 'Sắp mở',
            'open' => 'Đang mở',
            'closed' => 'Đã đóng',
            'completed' => 'Đã nộp',
            'retake' => 'Được phép làm lại',
            'cancelled' => 'Đã hủy',
        ];
    }
}
