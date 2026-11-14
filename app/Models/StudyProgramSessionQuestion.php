<?php

namespace App\Models;

use App\Support\Questions\QuestionParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyProgramSessionQuestion extends Model
{
    protected $fillable = [
        'session_id',
        'question_group',
        'question_text',
        'question_type',
        'options_json',
        'correct_answer',
        'answer_explanation',
        'sort_order',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(StudyProgramSession::class, 'session_id');
    }

    /**
     * @return array{review: list<self>, quiz: list<self>, post_practice: list<self>, pre: list<self>}
     */
    public static function groupedForSession(int $sessionId): array
    {
        $grouped = ['review' => [], 'quiz' => [], 'post_practice' => [], 'pre' => []];

        $rows = static::query()
            ->where('session_id', $sessionId)
            ->orderBy('question_group')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $group = (string) $row->question_group;
            $grouped[$group][] = $row;
        }

        return $grouped;
    }

    public static function createEmpty(int $sessionId, string $group, ?string $questionType = null): self
    {
        if (! in_array($group, ['review', 'quiz', 'post_practice', 'pre'], true)) {
            $group = 'quiz';
        }

        $allowed = QuestionParser::TYPES;

        if ($questionType === null || ! in_array($questionType, $allowed, true)) {
            $questionType = $group === 'quiz' ? 'choice' : 'fill';
        }

        if ($group === 'quiz') {
            $questionType = 'choice';
        }

        $sort = (int) static::query()
            ->where('session_id', $sessionId)
            ->where('question_group', $group)
            ->max('sort_order');

        return static::query()->create([
            'session_id' => $sessionId,
            'question_group' => $group,
            'question_text' => '',
            'question_type' => $questionType,
            'sort_order' => $sort + 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function updateFromPost(array $item): bool
    {
        $parser = app(QuestionParser::class);
        $parsed = $parser->resolveQuestionFromPost($item, (string) $this->question_group, $this->toArray());

        return $this->update([
            'question_text' => $parsed['question_text'],
            'question_type' => $parsed['question_type'],
            'options_json' => $parsed['options_json'],
            'correct_answer' => $parsed['correct_answer'],
            'answer_explanation' => $parsed['answer_explanation'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toEditArray(): array
    {
        return app(QuestionParser::class)->normalizeQuestionRowForEdit($this->toArray());
    }
}
