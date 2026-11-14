<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAssessmentSlot extends Model
{
    protected $fillable = [
        'course_assessment_id',
        'slot_date',
        'start_time',
        'end_time',
        'sort_order',
    ];

    protected $casts = [
        'slot_date' => 'date',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CourseAssessment::class, 'course_assessment_id');
    }

    public function dateForInput(): string
    {
        if ($this->slot_date instanceof \DateTimeInterface) {
            return $this->slot_date->format('Y-m-d');
        }

        $raw = trim((string) ($this->slot_date ?? ''));

        return $raw !== '' ? substr($raw, 0, 10) : '';
    }

    public function timeForInput(?string $value = null): string
    {
        $time = trim((string) ($value ?? $this->start_time ?? ''));

        return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
    }

    public function isComplete(): bool
    {
        return $this->dateForInput() !== '' && $this->timeForInput($this->start_time) !== '';
    }

    /**
     * @return array{start: int, end: int}
     */
    public function window(?int $durationMinutes = null): array
    {
        $date = $this->dateForInput();
        $startTime = $this->timeForInput($this->start_time);
        $endTime = $this->timeForInput($this->end_time);
        $start = strtotime($date.' '.$startTime.':00') ?: 0;
        $fallback = $start + (($durationMinutes && $durationMinutes > 0)
            ? $durationMinutes * 60
            : CourseAssessment::DEFAULT_DURATION_HOURS * 3600);
        $end = $endTime !== ''
            ? (strtotime($date.' '.$endTime.':00') ?: $fallback)
            : $fallback;

        if ($end <= $start) {
            $end = $fallback > $start ? $fallback : $start + (CourseAssessment::DEFAULT_DURATION_HOURS * 3600);
        }

        return ['start' => $start, 'end' => $end];
    }
}
