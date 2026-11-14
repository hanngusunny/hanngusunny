<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseAssessmentAnswerDraft extends Model
{
    protected $fillable = [
        'course_assessment_id',
        'user_id',
        'question_id',
        'selected_answer',
        'answer_text',
        'is_correct',
        'points_earned',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function mapForUser(int $assessmentId, int $userId): array
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return [];
        }

        $map = [];

        foreach (static::query()->where('course_assessment_id', $assessmentId)->where('user_id', $userId)->get() as $row) {
            $map[(int) $row->question_id] = $row->toArray();
        }

        return $map;
    }

    /**
     * @return array<int, string>
     */
    public static function rawMapForUser(int $assessmentId, int $userId): array
    {
        $map = [];

        foreach (self::mapForUser($assessmentId, $userId) as $questionId => $row) {
            $text = trim((string) ($row['answer_text'] ?? ''));
            $selected = trim((string) ($row['selected_answer'] ?? ''));
            $raw = $text !== '' ? $text : $selected;

            if ($questionId > 0 && $raw !== '') {
                $map[$questionId] = $raw;
            }
        }

        return $map;
    }

    /**
     * @return list<int>
     */
    public static function userIdsForAssessment(int $assessmentId): array
    {
        if ($assessmentId <= 0) {
            return [];
        }

        return static::query()
            ->where('course_assessment_id', $assessmentId)
            ->distinct()
            ->pluck('user_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{answered: int, updated_at: string}>
     */
    public static function progressByUser(int $assessmentId): array
    {
        if ($assessmentId <= 0) {
            return [];
        }

        $map = [];

        foreach (static::query()->where('course_assessment_id', $assessmentId)->get() as $row) {
            $userId = (int) $row->user_id;

            if ($userId <= 0) {
                continue;
            }

            $updated = (string) ($row->updated_at ?? $row->created_at ?? '');

            if (! isset($map[$userId])) {
                $map[$userId] = ['answered' => 0, 'updated_at' => $updated];
            }

            $map[$userId]['answered']++;

            if ($updated !== '' && $updated > (string) $map[$userId]['updated_at']) {
                $map[$userId]['updated_at'] = $updated;
            }
        }

        return $map;
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
}
