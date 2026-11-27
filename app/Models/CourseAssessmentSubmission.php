<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAssessmentSubmission extends Model
{
    protected $fillable = [
        'course_assessment_id',
        'user_id',
        'status',
        'submitted_at',
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CourseAssessment::class, 'course_assessment_id');
    }

    public static function getForStudent(int $assessmentId, int $userId): ?self
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return null;
        }

        return static::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->first();
    }

    public static function isGraded(int $assessmentId, int $userId): bool
    {
        $row = self::getForStudent($assessmentId, $userId);

        return $row !== null && $row->status === 'graded';
    }

    public static function markSubmitted(int $assessmentId, int $userId): void
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return;
        }

        if (! CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId)) {
            self::clearForStudent($assessmentId, $userId);

            return;
        }

        $row = self::getForStudent($assessmentId, $userId);
        $now = now();

        if ($row !== null) {
            $row->fill([
                'status' => 'submitted',
                'submitted_at' => $now,
                'graded_at' => null,
                'graded_by' => null,
            ])->save();

            return;
        }

        static::query()->create([
            'course_assessment_id' => $assessmentId,
            'user_id' => $userId,
            'status' => 'submitted',
            'submitted_at' => $now,
        ]);
    }

    public static function clearForStudent(int $assessmentId, int $userId): void
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return;
        }

        static::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * @return array<int, self>
     */
    public static function mapForAssessment(int $assessmentId): array
    {
        if ($assessmentId <= 0) {
            return [];
        }

        $map = [];

        foreach (static::query()->where('course_assessment_id', $assessmentId)->get() as $row) {
            $map[(int) $row->user_id] = $row;
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $summaries
     * @return array<int, array<string, mixed>>
     */
    public static function mergeIntoSummaries(int $assessmentId, array $summaries): array
    {
        $statusMap = self::mapForAssessment($assessmentId);

        foreach ($summaries as $userId => &$summary) {
            $submission = $statusMap[$userId] ?? null;
            $summary['grading_status'] = (string) ($submission?->status ?? 'submitted');
            $summary['is_graded'] = $submission !== null && $submission->status === 'graded';
            $summary['graded_at'] = $submission?->graded_at;
        }
        unset($summary);

        return $summaries;
    }

    public static function markGraded(int $assessmentId, int $userId, int $graderId): void
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return;
        }

        if (! CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId)) {
            self::clearForStudent($assessmentId, $userId);

            return;
        }

        $now = now();
        $row = self::getForStudent($assessmentId, $userId);

        if ($row === null) {
            static::query()->create([
                'course_assessment_id' => $assessmentId,
                'user_id' => $userId,
                'status' => 'graded',
                'submitted_at' => $now,
                'graded_at' => $now,
                'graded_by' => $graderId > 0 ? $graderId : null,
            ]);
        } else {
            $row->fill([
                'status' => 'graded',
                'graded_at' => $now,
                'graded_by' => $graderId > 0 ? $graderId : null,
            ])->save();
        }

        app(\App\Services\AssessmentDraftService::class)->clear($assessmentId, $userId);
    }

    public static function reconcileForCourse(int $courseId): void
    {
        if ($courseId <= 0) {
            return;
        }

        $ids = CourseAssessment::query()->where('course_id', $courseId)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $answered = CourseAssessmentAnswer::query()
            ->whereIn('course_assessment_id', $ids)
            ->select('course_assessment_id', 'user_id')
            ->distinct()
            ->get();

        if ($answered->isEmpty()) {
            return;
        }

        $existing = static::query()
            ->whereIn('course_assessment_id', $ids)
            ->get(['course_assessment_id', 'user_id'])
            ->mapWithKeys(fn ($row) => [$row->course_assessment_id.':'.$row->user_id => true]);

        foreach ($answered as $row) {
            if (! isset($existing[$row->course_assessment_id.':'.$row->user_id])) {
                self::markSubmitted((int) $row->course_assessment_id, (int) $row->user_id);
            }
        }
    }
}
