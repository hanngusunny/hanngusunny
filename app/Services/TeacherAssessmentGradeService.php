<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentRetake;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseMember;
use App\Support\Questions\QuestionParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TeacherAssessmentGradeService
{
    public function __construct(
        private AssessmentSubmissionService $scoring,
        private StudentAssessmentService $assessments,
        private AssessmentDraftService $drafts,
        private TeacherGradingService $grading,
        private QuestionParser $parser,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buildStudentDetail(int $courseId, int $assessmentId, int $userId): ?array
    {
        $course = Course::query()->find($courseId);

        if ($course === null || ! CourseMember::isMemberOf($courseId, $userId)) {
            return null;
        }

        $assessment = CourseAssessment::query()
            ->where('course_id', $courseId)
            ->where('id', $assessmentId)
            ->first();

        if ($assessment === null) {
            return null;
        }

        $member = null;

        foreach (CourseMember::activeMembersForCourse($courseId) as $row) {
            if ((int) $row->user_id === $userId) {
                $member = $row;
                break;
            }
        }

        if ($member === null) {
            return null;
        }

        $questions = $this->assessments->readyQuestions($assessmentId);
        $hasSubmitted = CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId);
        $allowRetake = CourseAssessmentRetake::isAllowed($assessmentId, $userId);
        $isDraftPreview = false;
        $isRetaking = $allowRetake && $hasSubmitted;
        $answerMap = $hasSubmitted ? CourseAssessmentAnswer::mapForUser($assessmentId, $userId) : [];
        $draftMap = $this->drafts->alignMapToQuestions(
            $this->drafts->getAnswerMap($assessmentId, $userId),
            $questions
        );

        if ($isRetaking && $draftMap !== []) {
            $answerMap = $draftMap;
            $isDraftPreview = true;
        } elseif ($hasSubmitted && $answerMap !== []) {
            $answerMap = $this->scoring->persistAutoScoresForTeacherReview($assessmentId, $userId, $questions, $answerMap);
        } elseif (! $hasSubmitted && $draftMap !== []) {
            $answerMap = $draftMap;
            $isDraftPreview = true;
        }

        $result = ($hasSubmitted || $isDraftPreview) ? $this->scoring->buildResult($questions, $answerMap) : null;
        $submission = $this->scoring->submissionSummariesForAssessment($assessmentId)[$userId] ?? null;

        $submissionRow = CourseAssessmentSubmission::getForStudent($assessmentId, $userId);

        if ($submission !== null && $submissionRow !== null) {
            $submission['grading_status'] = (string) ($submissionRow->status ?? 'submitted');
            $submission['is_graded'] = $submissionRow->status === 'graded';
            $submission['graded_at'] = $submissionRow->graded_at;
        } elseif ($submission !== null) {
            $submission['grading_status'] = 'submitted';
            $submission['is_graded'] = false;
        }

        $user = $member->user;

        return [
            'course' => $course,
            'assessment' => $assessment,
            'member' => [
                'user_id' => $userId,
                'full_name' => (string) ($user?->name ?? ''),
                'username' => (string) ($user?->username ?? ''),
                'avatar' => $user?->avatarUrl(),
                'initial' => $user?->initial ?? '?',
            ],
            'questions' => $questions,
            'answerMap' => $answerMap,
            'hasSubmitted' => $hasSubmitted,
            'isDraftPreview' => $isDraftPreview,
            'isRetaking' => $isRetaking,
            'allowRetake' => $allowRetake,
            'result' => $result,
            'submission' => $submission,
            'isGraded' => ! empty($submission['is_graded']) && ! $allowRetake,
        ];
    }

    /**
     * @return array{ok: bool, error?: string|null, message: string, question_id: int, complete: bool, question_earned: float|null, points_earned: float|null, pending_manual: int|null}
     */
    public function gradePostedAnswer(
        int $courseId,
        int $assessmentId,
        int $userId,
        int $questionId,
        string $gradeMode,
        mixed $itemGrades,
        string $pointsRaw,
        string $comment
    ): array {
        $questionId = max(0, $questionId);

        if ($gradeMode === 'word_translate') {
            $error = $this->gradeStudentWordTranslate($courseId, $assessmentId, $userId, $questionId, $itemGrades);

            if ($error !== null) {
                return $this->gradeOutcome(false, $error, $error, $questionId);
            }

            return $this->gradeOutcome(
                true,
                null,
                'Đã lưu chấm bài dịch từ.',
                $questionId,
                $this->gradeSnapshot($courseId, $assessmentId, $userId, $questionId)
            );
        }

        if ($gradeMode === 'fill_blanks') {
            $error = $this->gradeStudentFillBlanks(
                $courseId,
                $assessmentId,
                $userId,
                $questionId,
                $itemGrades,
                $pointsRaw
            );

            if ($error !== null) {
                return $this->gradeOutcome(false, $error, $error, $questionId);
            }

            return $this->gradeOutcome(
                true,
                null,
                'Đã lưu chấm chỗ trống.',
                $questionId,
                $this->gradeSnapshot($courseId, $assessmentId, $userId, $questionId)
            );
        }

        if ($gradeMode === 'composite_items') {
            $error = $this->gradeStudentCompositeItems(
                $courseId,
                $assessmentId,
                $userId,
                $questionId,
                $itemGrades
            );

            if ($error !== null) {
                return $this->gradeOutcome(false, $error, $error, $questionId);
            }

            return $this->gradeOutcome(
                true,
                null,
                'Đã lưu chấm câu hỏi nhỏ.',
                $questionId,
                $this->gradeSnapshot($courseId, $assessmentId, $userId, $questionId)
            );
        }

        $points = is_numeric(str_replace(',', '.', trim($pointsRaw)))
            ? (float) str_replace(',', '.', trim($pointsRaw))
            : 0.0;
        $error = $this->gradeStudentAnswer(
            $courseId,
            $assessmentId,
            $userId,
            $questionId,
            max(0, $points),
            trim($comment) !== '' ? trim($comment) : null
        );

        if ($error !== null) {
            return $this->gradeOutcome(false, $error, $error, $questionId);
        }

        return $this->gradeOutcome(
            true,
            null,
            'Đã lưu điểm câu hỏi.',
            $questionId,
            $this->gradeSnapshot($courseId, $assessmentId, $userId, $questionId)
        );
    }

    public function respondToGradeRequest(bool $isAjax, string $redirectUrl, array $outcome): JsonResponse|RedirectResponse
    {
        if ($isAjax) {
            return response()->json([
                'ok' => ! empty($outcome['ok']),
                'error' => $outcome['error'] ?? null,
                'message' => $outcome['message'] ?? '',
                'question_id' => (int) ($outcome['question_id'] ?? 0),
                'complete' => ! empty($outcome['complete']),
                'question_earned' => $outcome['question_earned'] ?? null,
                'points_earned' => $outcome['points_earned'] ?? null,
                'pending_manual' => $outcome['pending_manual'] ?? null,
            ], ! empty($outcome['ok']) ? 200 : 422);
        }

        if (empty($outcome['ok'])) {
            return redirect()->to($redirectUrl)->with('error', (string) ($outcome['error'] ?? 'Không lưu được.'));
        }

        return redirect()->to($redirectUrl)->with('success', (string) ($outcome['message'] ?? 'Đã lưu.'));
    }

    public function prepareRetakeDrafts(int $assessmentId, int $userId): void
    {
        $questions = $this->assessments->readyQuestions($assessmentId);

        if ($questions === []) {
            return;
        }

        $this->drafts->seedForRetake($assessmentId, $userId, $questions);
    }

    public function canManageCourse(int $teacherId, int $courseId): bool
    {
        if ($teacherId <= 0 || $courseId <= 0) {
            return false;
        }

        return Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->where('status', Course::STATUS_ACTIVE)
            ->exists();
    }

    private function gradeStudentAnswer(
        int $courseId,
        int $assessmentId,
        int $userId,
        int $questionId,
        float $pointsEarned,
        ?string $comment
    ): ?string {
        $gate = $this->gradeGate($courseId, $assessmentId, $userId, $questionId);

        if (is_string($gate)) {
            return $gate;
        }

        [$question, $answer] = $gate;
        $maxPoints = $this->scoring->questionPoints($question);

        if (! $answer->saveTeacherPoints($pointsEarned, $maxPoints, $comment)) {
            return 'Không thể lưu điểm. Học viên có thể chưa trả lời câu này.';
        }

        return null;
    }

    private function gradeStudentWordTranslate(
        int $courseId,
        int $assessmentId,
        int $userId,
        int $questionId,
        mixed $rawItemGrades
    ): ?string {
        $gate = $this->gradeGate($courseId, $assessmentId, $userId, $questionId);

        if (is_string($gate)) {
            return $gate;
        }

        [$question, $answer] = $gate;

        if ((string) ($question['question_type'] ?? '') !== 'word_translate') {
            return 'Câu hỏi này không phải dạng dịch từ.';
        }

        $itemCount = count($this->parser->decodeWordTranslateData($question)['items'] ?? []);
        $existing = $this->parser->decodeWordTranslateItemReview((string) ($answer->teacher_comment ?? ''));
        $itemGrades = $this->grading->mergeWordTranslateItemGrades($rawItemGrades, $itemCount, $existing);

        if ($itemGrades === []) {
            return 'Vui lòng chấm Đúng/Sai cho từng câu dịch.';
        }

        $maxPoints = $this->scoring->questionPoints($question);

        if (! $answer->saveTeacherWordTranslateReview($itemGrades, $itemCount, $maxPoints)) {
            return 'Không thể lưu chấm bài. Học viên có thể chưa trả lời câu này.';
        }

        return null;
    }

    private function gradeStudentFillBlanks(
        int $courseId,
        int $assessmentId,
        int $userId,
        int $questionId,
        mixed $rawItemGrades,
        string $pointsRaw
    ): ?string {
        $gate = $this->gradeGate($courseId, $assessmentId, $userId, $questionId);

        if (is_string($gate)) {
            return $gate;
        }

        [$question, $answer] = $gate;

        if ((string) ($question['question_type'] ?? '') !== 'fill_blanks') {
            return 'Câu hỏi này không phải dạng nhiều chỗ trống.';
        }

        $blankCount = count($this->parser->decodeFillBlanksData($question)['answers'] ?? []);

        if ($blankCount < 1) {
            return 'Câu hỏi chưa có chỗ trống để chấm.';
        }

        $itemGrades = $this->grading->parseFillBlanksItemGrades($rawItemGrades, $blankCount);
        $maxPoints = $this->scoring->questionPoints($question);
        $correctCount = $this->parser->countWordTranslateCorrectItems($itemGrades);
        $computed = $this->scoring->wordTranslateEarnedPoints($maxPoints, $blankCount, $correctCount);
        $points = is_numeric(str_replace(',', '.', trim($pointsRaw)))
            ? (float) str_replace(',', '.', trim($pointsRaw))
            : $computed;

        if (! $answer->saveTeacherFillBlanksReview($itemGrades, $blankCount, $maxPoints, max(0, $points))) {
            return 'Không thể lưu chấm bài. Học viên có thể chưa trả lời câu này.';
        }

        return null;
    }

    private function gradeStudentCompositeItems(
        int $courseId,
        int $assessmentId,
        int $userId,
        int $questionId,
        mixed $rawItemGrades
    ): ?string {
        $gate = $this->gradeGate($courseId, $assessmentId, $userId, $questionId);

        if (is_string($gate)) {
            return $gate;
        }

        [$question, $answer] = $gate;
        $questionType = (string) ($question['question_type'] ?? '');

        if (! in_array($questionType, ['listening', 'phat_am'], true)) {
            return 'Câu hỏi này không phải dạng nghe đáp hoặc phát âm.';
        }

        $maxPoints = $this->scoring->questionPoints($question);
        $items = $this->parser->compositeItemsForRow($question);
        $weights = $this->parser->distributeItemPoints($items, $maxPoints);

        if ($weights === []) {
            return 'Câu hỏi chưa có câu hỏi nhỏ để chấm.';
        }

        $comment = (string) ($answer->teacher_comment ?? '');
        $state = $this->parser->resolveCompositeItemGradeState(
            $question,
            (string) ($answer->selected_answer ?? $answer->answer_text ?? ''),
            $comment,
            $maxPoints
        );
        $parsed = $this->grading->parseCompositeItemGradePayload(
            $rawItemGrades,
            $weights,
            $state['earned'] ?? [],
            $state['parts'] ?? [],
            $state['part_weights'] ?? []
        );

        if (! $answer->saveTeacherCompositeItemReview($question, $parsed['earned'], $maxPoints, $parsed['parts'])) {
            return 'Không thể lưu chấm bài. Học viên có thể chưa trả lời câu này.';
        }

        return null;
    }

    /**
     * @return array{0: array<string, mixed>, 1: CourseAssessmentAnswer}|string
     */
    private function gradeGate(int $courseId, int $assessmentId, int $userId, int $questionId): array|string
    {
        $course = Course::query()->find($courseId);

        if ($course === null) {
            return 'Không tìm thấy khóa học.';
        }

        if (! CourseMember::isMemberOf($courseId, $userId)) {
            return 'Học viên không thuộc khóa này.';
        }

        $assessment = CourseAssessment::query()
            ->where('course_id', $courseId)
            ->where('id', $assessmentId)
            ->first();

        if ($assessment === null) {
            return 'Bài kiểm tra không tồn tại.';
        }

        if (! CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId)) {
            return 'Học viên chưa nộp bài.';
        }

        $question = $this->findQuestion($assessmentId, $questionId);

        if ($question === null) {
            return 'Không tìm thấy câu hỏi.';
        }

        $answer = $this->scoring->ensureUnansweredRow($assessmentId, $userId, $question);

        if ($answer === null) {
            return 'Không thể lưu điểm. Học viên có thể chưa trả lời câu này.';
        }

        return [$question, $answer];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findQuestion(int $assessmentId, int $questionId): ?array
    {
        if ($assessmentId <= 0 || $questionId <= 0) {
            return null;
        }

        foreach ($this->assessments->readyQuestions($assessmentId) as $row) {
            if ((int) ($row['id'] ?? 0) === $questionId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  array{complete?: bool, question_earned?: float|null, points_earned?: float|null, pending_manual?: int|null}  $snapshot
     * @return array{ok: bool, error?: string|null, message: string, question_id: int, complete: bool, question_earned: float|null, points_earned: float|null, pending_manual: int|null}
     */
    private function gradeOutcome(bool $ok, ?string $error, string $message, int $questionId, array $snapshot = []): array
    {
        return [
            'ok' => $ok,
            'error' => $error,
            'message' => $message,
            'question_id' => $questionId,
            'complete' => ! empty($snapshot['complete']),
            'question_earned' => $snapshot['question_earned'] ?? null,
            'points_earned' => $snapshot['points_earned'] ?? null,
            'pending_manual' => $snapshot['pending_manual'] ?? null,
        ];
    }

    /**
     * @return array{complete: bool, question_earned: float|null, points_earned: float|null, pending_manual: int|null}
     */
    private function gradeSnapshot(int $courseId, int $assessmentId, int $userId, int $questionId): array
    {
        $detail = $this->buildStudentDetail($courseId, $assessmentId, $userId);

        if ($detail === null) {
            return [
                'complete' => false,
                'question_earned' => null,
                'points_earned' => null,
                'pending_manual' => null,
            ];
        }

        $question = null;

        foreach ($detail['questions'] as $row) {
            if ((int) ($row['id'] ?? 0) === $questionId) {
                $question = $row;
                break;
            }
        }

        $answerRow = $detail['answerMap'][$questionId] ?? [];
        $earned = $question !== null ? $this->scoring->resolveEarnedPoints($question, $answerRow) : null;
        $result = is_array($detail['result'] ?? null) ? $detail['result'] : [];

        return [
            'complete' => trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '' || $earned !== null,
            'question_earned' => $earned,
            'points_earned' => isset($result['points_earned']) ? (float) $result['points_earned'] : null,
            'pending_manual' => isset($result['pending_manual']) ? (int) $result['pending_manual'] : null,
        ];
    }
}
