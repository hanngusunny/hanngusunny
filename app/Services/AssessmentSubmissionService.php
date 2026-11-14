<?php

namespace App\Services;

use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentRetake;
use App\Models\CourseAssessmentSubmission;
use App\Support\HtmlContent;
use App\Support\Questions\QuestionParser;
use App\Support\StudentQuizGrader;
use Illuminate\Support\Facades\DB;

class AssessmentSubmissionService
{
    public function __construct(
        private StudentQuizGrader $grader,
        private QuestionParser $parser,
    ) {
    }

    public function canSubmit(int $assessmentId, int $userId): bool
    {
        if (! CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId)) {
            return true;
        }

        return CourseAssessmentRetake::isAllowed($assessmentId, $userId);
    }

    /**
     * @param  array<string, mixed>  $question
     */
    public function questionPoints(array $question): float
    {
        $type = $this->normalizeType((string) ($question['question_type'] ?? 'choice'));
        $points = (float) ($question['points'] ?? 1);

        if ($points <= 0) {
            $points = 1.0;
        } else {
            $points = round(min(1000, $points), 2);
        }

        if (in_array($type, ['listening', 'phat_am'], true)) {
            return $this->parser->resolveCompositeParentPoints($question, $points);
        }

        return $points;
    }

    public function needsManualGrading(string $questionType): bool
    {
        return in_array($this->normalizeType($questionType), ['essay', 'fill'], true);
    }

    public function isPendingTeacherGrade(string $questionType, string $rawSelected = ''): bool
    {
        $questionType = $this->normalizeType($questionType);

        if ($questionType === 'word_translate') {
            return true;
        }

        if ($questionType !== 'phat_am' || trim($rawSelected) === '') {
            return false;
        }

        return $this->parser->hasPhatAmRecordingInSubmission($rawSelected);
    }

    /**
     * @param  array<string, mixed>  $question
     */
    public function questionNeedsTeacherReview(array $question, string $rawSelected = ''): bool
    {
        $questionType = $this->normalizeType((string) ($question['question_type'] ?? ''));

        if ($this->needsManualGrading($questionType) || $questionType === 'word_translate') {
            return true;
        }

        if ($this->isPendingTeacherGrade($questionType, $rawSelected)) {
            return true;
        }

        return $this->parser->hasCompositeFillItems($question);
    }

    public function isAutoScoredType(string $questionType, string $rawSelected = ''): bool
    {
        $questionType = $this->normalizeType($questionType);

        if ($this->needsManualGrading($questionType) || $questionType === 'word_translate') {
            return false;
        }

        if ($this->isPendingTeacherGrade($questionType, $rawSelected)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $question
     */
    public function isAutoScoredQuestion(array $question, string $rawSelected = ''): bool
    {
        return ! $this->questionNeedsTeacherReview($question, $rawSelected);
    }

    public function formatPoints(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    public function isChoiceQuestionType(string $questionType): bool
    {
        return in_array($this->normalizeType($questionType), ['choice', 'multi_select'], true);
    }

    public function wordTranslateItemPoints(float $maxPoints, int $itemCount): float
    {
        if ($itemCount <= 0) {
            return 0.0;
        }

        return round($maxPoints / $itemCount, 2);
    }

    public function wordTranslateEarnedPoints(float $maxPoints, int $itemCount, int $correctCount): float
    {
        if ($itemCount <= 0) {
            return 0.0;
        }

        return round(max(0, min($maxPoints, $maxPoints * ($correctCount / $itemCount))), 2);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  array<int, array<string, mixed>>  $answerMap
     * @return array<int, array<string, mixed>>
     */
    public function persistAutoScoresForTeacherReview(int $assessmentId, int $userId, array $questions, array $answerMap): array
    {
        if ($assessmentId <= 0 || $userId <= 0 || $questions === [] || $answerMap === []) {
            return $answerMap;
        }

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);
            $answerRow = $answerMap[$questionId] ?? [];

            if ($questionId <= 0 || $answerRow === []) {
                continue;
            }

            $rowId = (int) ($answerRow['id'] ?? 0);
            $maxPoints = $this->questionPoints($question);
            $currentRaw = $answerRow['points_earned'] ?? null;
            $current = $currentRaw !== null && $currentRaw !== '' ? round((float) $currentRaw, 2) : null;
            $teacherEdited = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';
            $rawSelected = (string) ($answerRow['selected_answer'] ?? $answerRow['answer_text'] ?? '');
            $questionType = $this->normalizeType((string) ($question['question_type'] ?? 'choice'));

            if ($teacherEdited || $this->questionNeedsTeacherReview($question, $rawSelected)) {
                continue;
            }

            if ($current !== null) {
                continue;
            }

            if (! $this->isAutoScoredQuestion($question, $rawSelected)) {
                continue;
            }

            $expected = round(max(0, min($maxPoints, $this->autoEarnedPoints($question, $answerRow))), 2);
            $answerMap[$questionId]['points_earned'] = $expected;

            if ($rowId > 0) {
                CourseAssessmentAnswer::query()->where('id', $rowId)->update([
                    'points_earned' => $expected,
                ]);
            }
        }

        return $answerMap;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function submissionSummariesForAssessment(int $assessmentId): array
    {
        if ($assessmentId <= 0) {
            return [];
        }

        $questions = app(StudentAssessmentService::class)->readyQuestions($assessmentId);

        if ($questions === []) {
            return [];
        }

        $grouped = [];

        foreach (CourseAssessmentAnswer::query()->where('course_assessment_id', $assessmentId)->get() as $row) {
            $userId = (int) $row->user_id;

            if ($userId <= 0) {
                continue;
            }

            $grouped[$userId][] = $row->toArray();
        }

        $map = [];

        foreach ($grouped as $userId => $answers) {
            $answerMap = [];
            $latest = '';

            foreach ($answers as $row) {
                $answerMap[(int) ($row['question_id'] ?? 0)] = $row;
                $updatedAt = (string) ($row['updated_at'] ?? '');

                if ($updatedAt !== '' && ($latest === '' || $updatedAt > $latest)) {
                    $latest = $updatedAt;
                }
            }

            $map[$userId] = array_merge($this->buildResult($questions, $answerMap), [
                'submitted' => true,
                'submitted_at' => $latest !== '' ? $latest : null,
            ]);
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  array<int, array<string, mixed>>  $answerMap
     */
    public function countPendingManualGrades(array $questions, array $answerMap): int
    {
        $pending = 0;

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);
            $answerRow = $answerMap[$questionId] ?? [];

            if ($answerRow === []) {
                if ($this->questionNeedsTeacherReview($question)) {
                    ++$pending;
                }

                continue;
            }

            if ($this->resolveEarnedPoints($question, $answerRow) !== null) {
                continue;
            }

            ++$pending;
        }

        return $pending;
    }

    /**
     * @param  array<string, mixed>  $question
     * @param  array<string, mixed>  $answerRow
     */
    public function resolveEarnedPoints(array $question, array $answerRow): ?float
    {
        $questionType = $this->normalizeType((string) ($question['question_type'] ?? 'choice'));
        $maxPoints = $this->questionPoints($question);
        $rawSelected = (string) ($answerRow['selected_answer'] ?? $answerRow['answer_text'] ?? '');
        $teacherEdited = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';

        $storedRaw = $answerRow['points_earned'] ?? null;
        $stored = $storedRaw !== null && $storedRaw !== '' ? round((float) $storedRaw, 2) : null;

        if (! $teacherEdited && $this->questionNeedsTeacherReview($question, $rawSelected)) {
            return null;
        }

        if ($teacherEdited && $stored !== null) {
            return round(max(0, $stored), 2);
        }

        if ($stored !== null) {
            return round(max(0, min($maxPoints, $stored)), 2);
        }

        if (! $teacherEdited && $this->isAutoScoredQuestion($question, $rawSelected)) {
            return round(max(0, min($maxPoints, $this->autoEarnedPoints($question, $answerRow))), 2);
        }

        if ($this->questionNeedsTeacherReview($question, $rawSelected)) {
            return null;
        }

        if (in_array($questionType, ['listening', 'phat_am'], true)) {
            return round(max(0, min($maxPoints, $this->autoEarnedPoints($question, $answerRow))), 2);
        }

        return ! empty($answerRow['is_correct']) ? $maxPoints : 0.0;
    }

    /**
     * @param  array<string, mixed>  $question
     * @param  array<string, mixed>  $answerRow
     */
    public function autoEarnedPoints(array $question, array $answerRow): float
    {
        $questionType = $this->normalizeType((string) ($question['question_type'] ?? 'choice'));
        $maxPoints = $this->questionPoints($question);
        $raw = trim((string) ($answerRow['selected_answer'] ?? $answerRow['answer_text'] ?? ''));

        if (in_array($questionType, ['listening', 'phat_am'], true)) {
            return (float) ($this->parser->scoreCompositeSubmission($question, $raw)['earned'] ?? 0);
        }

        if ($questionType === 'fill_blanks') {
            return (float) ($this->parser->scoreFillBlanksSubmission($question, $raw, $maxPoints)['earned'] ?? 0);
        }

        return ! empty($answerRow['is_correct']) ? $maxPoints : 0.0;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  array<int, array<string, mixed>>  $answerMap
     * @return array{correct: int, total: int, essay_count: int, score: int, points_earned: float, points_possible: float, pending_manual: int}
     */
    public function buildResult(array $questions, array $answerMap): array
    {
        $correct = 0;
        $choiceTotal = 0;
        $essayCount = 0;
        $pointsEarned = 0.0;
        $pointsPossible = 0.0;
        $pendingManual = 0;

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);
            $questionType = $this->normalizeType((string) ($question['question_type'] ?? 'choice'));
            $answerRow = $answerMap[$questionId] ?? [];
            $maxPoints = $this->questionPoints($question);
            $pointsPossible += $maxPoints;

            $rawSelected = (string) ($answerRow['selected_answer'] ?? $answerRow['answer_text'] ?? '');

            if ($this->questionNeedsTeacherReview($question, $rawSelected)) {
                if (trim((string) ($answerRow['answer_text'] ?? '')) !== '' || trim((string) ($answerRow['selected_answer'] ?? '')) !== '') {
                    ++$essayCount;
                }

                $earned = $this->resolveEarnedPoints($question, $answerRow);

                if ($earned === null) {
                    ++$pendingManual;

                    continue;
                }

                $pointsEarned += $earned;

                continue;
            }

            ++$choiceTotal;
            $earned = $this->resolveEarnedPoints($question, $answerRow);
            $pointsEarned += $earned ?? 0.0;

            if (! empty($answerRow['is_correct'])) {
                ++$correct;
            }
        }

        $pointsEarned = round($pointsEarned, 2);
        $pointsPossible = round($pointsPossible, 2);

        return [
            'correct' => $correct,
            'total' => $choiceTotal,
            'essay_count' => $essayCount,
            'score' => $pointsPossible > 0 ? (int) round(($pointsEarned / $pointsPossible) * 100) : 100,
            'points_earned' => $pointsEarned,
            'points_possible' => $pointsPossible,
            'pending_manual' => $pendingManual,
        ];
    }

    /**
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>
     */
    public function storedAnswerFields(array $question, string $rawSelected): array
    {
        $questionId = (int) ($question['id'] ?? 0);
        $maxPoints = $this->questionPoints($question);
        $questionType = $this->normalizeType((string) ($question['question_type'] ?? 'choice'));
        $graded = $this->grader->gradeAnswerRow($question, $rawSelected);

        if ($this->needsManualGrading($questionType)) {
            return [
                'question_id' => $questionId,
                'selected_answer' => null,
                'answer_text' => $rawSelected,
                'is_correct' => ! empty($graded['is_correct']) ? 1 : 0,
                'points_earned' => ! empty($graded['is_correct']) ? $maxPoints : null,
            ];
        }

        if ($this->questionNeedsTeacherReview($question, $rawSelected)) {
            return [
                'question_id' => $questionId,
                'selected_answer' => $graded['selected_answer'],
                'answer_text' => null,
                'is_correct' => 0,
                'points_earned' => null,
            ];
        }

        if (in_array($questionType, ['listening', 'phat_am'], true)) {
            $score = $this->parser->scoreCompositeSubmission($question, $rawSelected);

            return [
                'question_id' => $questionId,
                'selected_answer' => $graded['selected_answer'],
                'answer_text' => null,
                'is_correct' => ! empty($score['all_correct']) ? 1 : 0,
                'points_earned' => $score['earned'],
            ];
        }

        if ($questionType === 'fill_blanks') {
            $score = $this->parser->scoreFillBlanksSubmission($question, $rawSelected, $maxPoints);

            return [
                'question_id' => $questionId,
                'selected_answer' => $graded['selected_answer'],
                'answer_text' => null,
                'is_correct' => ! empty($score['all_correct']) ? 1 : 0,
                'points_earned' => $score['earned'],
            ];
        }

        $isCorrect = ! empty($graded['is_correct']);
        $selected = $graded['selected_answer'];

        if (($selected === null || $selected === '') && $rawSelected !== '') {
            $selected = $rawSelected;
        }

        return [
            'question_id' => $questionId,
            'selected_answer' => $selected,
            'answer_text' => null,
            'is_correct' => $isCorrect ? 1 : 0,
            'points_earned' => $isCorrect ? $maxPoints : 0.0,
        ];
    }

    /**
     * @param  array<int, mixed>  $answers
     * @param  list<array<string, mixed>>  $questions
     * @param  array{allowPartial?: bool, fillUnanswered?: bool}  $options fillUnanswered is ignored; skipped questions always get a row.
     * @return array{correct: int, total: int, essay_count: int, score: int, points_earned: float, points_possible: float, pending_manual: int}
     */
    public function saveSubmission(
        int $assessmentId,
        int $userId,
        array $answers,
        array $questions,
        array $options = []
    ): array {
        if (! $this->canSubmit($assessmentId, $userId)) {
            throw new \RuntimeException('already_submitted');
        }

        $allowPartial = ! empty($options['allowPartial']);

        return DB::transaction(function () use ($assessmentId, $userId, $answers, $questions, $allowPartial): array {
            CourseAssessmentAnswer::query()
                ->where('course_assessment_id', $assessmentId)
                ->where('user_id', $userId)
                ->delete();

            $savedCount = 0;
            $expectedAnswered = 0;
            $answeredSaved = 0;
            $savedQuestionIds = [];

            foreach ($questions as $question) {
                $questionId = (int) ($question['id'] ?? 0);
                $rawSelected = trim((string) ($answers[$questionId] ?? ''));

                if ($questionId <= 0 || $rawSelected === '') {
                    continue;
                }

                ++$expectedAnswered;

                if ($this->insertGradedAnswerRow($assessmentId, $userId, $question, $rawSelected)) {
                    ++$savedCount;
                    ++$answeredSaved;
                    $savedQuestionIds[$questionId] = true;
                }
            }

            if ($expectedAnswered === 0 && ! $allowPartial) {
                CourseAssessmentSubmission::clearForStudent($assessmentId, $userId);

                throw new \RuntimeException('empty_submission');
            }

            if (! $allowPartial && $answeredSaved !== $expectedAnswered) {
                CourseAssessmentSubmission::clearForStudent($assessmentId, $userId);
                CourseAssessmentAnswer::query()
                    ->where('course_assessment_id', $assessmentId)
                    ->where('user_id', $userId)
                    ->delete();

                throw new \RuntimeException('submission_failed');
            }

            foreach ($questions as $question) {
                $questionId = (int) ($question['id'] ?? 0);

                if ($questionId <= 0 || isset($savedQuestionIds[$questionId])) {
                    continue;
                }

                if ($this->insertUnansweredRow($assessmentId, $userId, $question)) {
                    ++$savedCount;
                    $savedQuestionIds[$questionId] = true;
                }
            }

            if ($savedCount === 0) {
                CourseAssessmentSubmission::clearForStudent($assessmentId, $userId);

                throw new \RuntimeException('empty_submission');
            }

            CourseAssessmentRetake::revoke($assessmentId, $userId);
            CourseAssessmentSubmission::markSubmitted($assessmentId, $userId);
            app(AssessmentDraftService::class)->clear($assessmentId, $userId);

            return $this->buildResult($questions, CourseAssessmentAnswer::mapForUser($assessmentId, $userId));
        });
    }

    /**
     * @param  array<int, mixed>  $answers
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, string>
     */
    public function sanitizeEssayAnswers(array $answers, array $questions): array
    {
        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);

            if ($questionId <= 0 || ! isset($answers[$questionId])) {
                continue;
            }

            $type = $this->normalizeType((string) ($question['question_type'] ?? 'choice'));

            if (! in_array($type, ['fill', 'essay', 'listening', 'phat_am'], true)) {
                continue;
            }

            $raw = (string) $answers[$questionId];
            $trimmed = ltrim($raw);

            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($raw, true);

                if (is_array($decoded)) {
                    $encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    if (is_string($encoded) && $encoded !== '') {
                        $answers[$questionId] = $encoded;

                        continue;
                    }
                }
            }

            $answers[$questionId] = HtmlContent::sanitize($raw);
        }

        return $answers;
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function insertGradedAnswerRow(int $assessmentId, int $userId, array $question, string $rawSelected): bool
    {
        $fields = $this->storedAnswerFields($question, $rawSelected);
        $fields['course_assessment_id'] = $assessmentId;
        $fields['user_id'] = $userId;

        CourseAssessmentAnswer::query()->create($fields);

        return true;
    }

    /**
     * @param  array<string, mixed>  $question
     */
    public function ensureUnansweredRow(int $assessmentId, int $userId, array $question): ?CourseAssessmentAnswer
    {
        $questionId = (int) ($question['id'] ?? 0);

        if ($assessmentId <= 0 || $userId <= 0 || $questionId <= 0) {
            return null;
        }

        $existing = CourseAssessmentAnswer::findForTeacherReview($assessmentId, $userId, $questionId);

        if ($existing !== null) {
            return $existing;
        }

        if (! $this->insertUnansweredRow($assessmentId, $userId, $question)) {
            return null;
        }

        return CourseAssessmentAnswer::findForTeacherReview($assessmentId, $userId, $questionId);
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function insertUnansweredRow(int $assessmentId, int $userId, array $question): bool
    {
        $questionId = (int) ($question['id'] ?? 0);
        $pointsEarned = $this->questionNeedsTeacherReview($question) ? null : 0.0;

        CourseAssessmentAnswer::query()->create([
            'course_assessment_id' => $assessmentId,
            'user_id' => $userId,
            'question_id' => $questionId,
            'selected_answer' => null,
            'answer_text' => null,
            'is_correct' => 0,
            'points_earned' => $pointsEarned,
        ]);

        return true;
    }

    private function normalizeType(string $questionType): string
    {
        return $questionType === 'text' ? 'fill' : $questionType;
    }
}
