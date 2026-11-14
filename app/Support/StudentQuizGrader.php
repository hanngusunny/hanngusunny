<?php

namespace App\Support;

use App\Support\Questions\QuestionParser;

class StudentQuizGrader
{
    public function __construct(private QuestionParser $parser)
    {
    }

    /**
     * @param  array<int|string, mixed>  $answers
     * @return array<int, string>
     */
    public function parsePostedAnswers(mixed $answersRaw): array
    {
        $answers = is_array($answersRaw) ? $answersRaw : [];
        $parsed = [];

        foreach ($answers as $questionId => $value) {
            $id = (int) $questionId;

            if ($id <= 0) {
                continue;
            }

            if (is_array($value)) {
                $parsed[$id] = $this->parser->normalizeMultiSelectAnswer(implode(',', $value));

                continue;
            }

            $parsed[$id] = trim((string) $value);
        }

        return $parsed;
    }

    /**
     * @param  array<int, string>  $answers
     * @param  list<array<string, mixed>>  $questions
     * @return array{correct: int, total: int, score: int, answerMap: array<int, array<string, mixed>>}
     */
    public function evaluate(array $answers, array $questions): array
    {
        $correct = 0;
        $total = 0;
        $answerMap = [];

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);

            if ($questionId <= 0) {
                continue;
            }

            $total++;
            $graded = $this->gradeAnswerRow($question, (string) ($answers[$questionId] ?? ''));

            if ($graded['is_correct']) {
                $correct++;
            }

            $answerMap[$questionId] = [
                'question_id' => $questionId,
                'selected_answer' => $graded['selected_answer'],
                'is_correct' => $graded['is_correct'] ? 1 : 0,
            ];
        }

        return [
            'correct' => $correct,
            'total' => $total,
            'score' => $total > 0 ? (int) round(($correct / $total) * 100) : 0,
            'answerMap' => $answerMap,
        ];
    }

    /**
     * @param  array<int, string>  $answers
     * @param  list<array<string, mixed>>  $questions
     * @return array{correct: int, total: int, score: int, answerMap: array<int, array<string, mixed>>}
     */
    public function persistSessionAnswers(int $sessionId, int $userId, array $answers, array $questions): array
    {
        $eval = $this->evaluate($answers, $questions);
        $questionIds = array_keys($eval['answerMap']);

        if ($questionIds !== []) {
            \App\Models\CourseSessionQuizAnswer::query()
                ->where('course_session_id', $sessionId)
                ->where('user_id', $userId)
                ->whereIn('question_id', $questionIds)
                ->delete();
        }

        foreach ($eval['answerMap'] as $row) {
            \App\Models\CourseSessionQuizAnswer::query()->create([
                'course_session_id' => $sessionId,
                'user_id' => $userId,
                'question_id' => $row['question_id'],
                'selected_answer' => $row['selected_answer'],
                'is_correct' => $row['is_correct'],
            ]);
        }

        return $eval;
    }

    /**
     * @param  array<int, string>  $answers
     * @param  list<array<string, mixed>>  $questions
     * @return array{correct: int, total: int, score: int, answerMap: array<int, array<string, mixed>>}
     */
    public function persistKnowledgeReviewAnswers(int $reviewId, int $userId, array $answers, array $questions): array
    {
        $eval = $this->evaluate($answers, $questions);
        $questionIds = array_keys($eval['answerMap']);

        if ($questionIds !== []) {
            \App\Models\CourseKnowledgeReviewAnswer::query()
                ->where('course_knowledge_review_id', $reviewId)
                ->where('user_id', $userId)
                ->whereIn('question_id', $questionIds)
                ->delete();
        }

        foreach ($eval['answerMap'] as $row) {
            \App\Models\CourseKnowledgeReviewAnswer::query()->create([
                'course_knowledge_review_id' => $reviewId,
                'user_id' => $userId,
                'question_id' => $row['question_id'],
                'selected_answer' => $row['selected_answer'],
                'is_correct' => $row['is_correct'],
            ]);
        }

        return $eval;
    }

    /**
     * @param  array<string, mixed>  $question
     * @return array{selected_answer: mixed, is_correct: bool}
     */
    public function gradeAnswerRow(array $question, string $rawSelected): array
    {
        $question = $this->parser->normalizeQuestionRowForEdit($question);
        $type = (string) ($question['question_type'] ?? 'choice');
        $type = $type === 'text' || $type === 'essay' ? 'fill' : $type;
        $rawSelected = trim($rawSelected);
        $expectedRaw = trim((string) ($question['correct_answer'] ?? ''));
        $storedAnswer = null;
        $isCorrect = false;

        if ($type === 'memory_match') {
            $payload = json_decode($rawSelected, true);
            $storedAnswer = $rawSelected;
            $isCorrect = is_array($payload) && ($payload['status'] ?? '') === 'completed';
        } elseif ($type === 'word_link') {
            $storedAnswer = $rawSelected;
            $isCorrect = $this->parser->gradeWordLinkSubmission($question, $rawSelected);
        } elseif ($type === 'word_order') {
            $storedAnswer = $rawSelected;
            $isCorrect = $this->parser->gradeWordOrderSubmission($question, $rawSelected);
        } elseif ($type === 'listening') {
            $storedAnswer = $rawSelected;
            $isCorrect = $this->parser->gradeListeningSubmission($question, $rawSelected);
        } elseif ($type === 'category_sort') {
            $storedAnswer = $rawSelected;
            $isCorrect = $this->parser->gradeCategorySortSubmission($question, $rawSelected);
        } elseif ($type === 'fill_blanks') {
            $storedAnswer = $rawSelected;
            $isCorrect = $this->parser->gradeFillBlanksSubmission($question, $rawSelected);
        } elseif ($type === 'word_select') {
            $storedAnswer = $rawSelected;
            $isCorrect = $this->parser->gradeWordSelectSubmission($question, $rawSelected);
        } elseif ($type === 'phat_am') {
            $storedAnswer = $rawSelected;
            $isCorrect = $this->parser->gradePhatAmSubmission($question, $rawSelected);
        } elseif ($type === 'word_translate') {
            $storedAnswer = $rawSelected;
            $isCorrect = false;
        } elseif ($type === 'image_hotspot') {
            $storedAnswer = $rawSelected;
            $isCorrect = $this->parser->gradeImageHotspotSubmission($question, $rawSelected);
        } elseif ($type === 'image_label_drop') {
            $storedAnswer = $rawSelected;
            $isCorrect = $this->parser->gradeImageLabelDropSubmission($question, $rawSelected);
        } elseif ($type === 'multi_select') {
            $normalized = $this->parser->normalizeMultiSelectAnswer($rawSelected);
            $storedAnswer = $normalized !== '' ? $normalized : null;
            $isCorrect = $this->parser->gradeMultiSelectAnswer($normalized, $expectedRaw);
        } elseif ($type === 'fill') {
            $storedAnswer = $rawSelected;
            $isCorrect = $rawSelected !== ''
                && $expectedRaw !== ''
                && $this->normalizeEssayAnswer($rawSelected) === $this->normalizeEssayAnswer($expectedRaw);
        } else {
            $selected = strtolower($rawSelected);
            $expected = strtolower($expectedRaw);
            $storedAnswer = in_array($selected, ['a', 'b', 'c', 'd'], true) ? $selected : null;
            $isCorrect = $selected !== '' && $expected !== '' && $selected === $expected;
        }

        return [
            'selected_answer' => $storedAnswer,
            'is_correct' => $isCorrect,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return list<array<string, mixed>>
     */
    public function readyQuestions(iterable $questions): array
    {
        $rows = [];

        foreach ($questions as $question) {
            $row = is_array($question) ? $question : $question->toArray();
            $row = $this->parser->normalizeQuestionRowForEdit($row);

            if (! $this->parser->isReadyForUse($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function normalizeEssayAnswer(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return mb_strtolower($value);
    }
}
