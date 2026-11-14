<?php

namespace App\Services;

use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentSubmission;

class AssessmentTakePageService
{
    public function __construct(
        private StudentAssessmentService $assessments,
        private AssessmentDraftService $drafts,
        private AssessmentSubmissionService $submissions,
        private AssessmentFlowService $flow,
    ) {
    }

    /**
     * @return array{
     *   status: 'ok'|'missing'|'closed'|'autosubmit',
     *   message?: string,
     *   data?: array<string, mixed>
     * }
     */
    public function resolve(int $id, int $userId): array
    {
        $context = $this->assessments->resolveForStudent($id, $userId);

        if ($context === null) {
            return [
                'status' => 'missing',
                'message' => 'Không tìm thấy bài kiểm tra hoặc bạn không thuộc khóa học này.',
            ];
        }

        $questions = $this->assessments->readyQuestions($id);
        $assessment = $this->assessments->enrichForStudent($context['assessment'], $userId, $questions);
        $display = $this->assessments->studentDisplay($assessment);

        if (! $this->assessments->canStudentOpenPage($assessment)) {
            $timeHint = ! empty($display['hasSchedule']) && ! empty($display['timeLabel'])
                ? ' Bài kiểm tra mở lúc '.$display['timeLabel'].'.'
                : '';

            return [
                'status' => 'closed',
                'message' => 'Bài kiểm tra chưa mở.'.$timeHint,
            ];
        }

        $submitted = ! empty($assessment['has_submitted']);

        if (
            ! $submitted
            && $this->flow->autoSubmitExpiredIfNeeded($assessment, $id, $userId, $questions, $submitted)
        ) {
            return [
                'status' => 'autosubmit',
                'message' => 'Đã hết thời gian làm bài — hệ thống đã tự nộp bài. Giáo viên sẽ chấm và công bố kết quả.',
            ];
        }

        $showResults = ! empty($assessment['results_visible']);
        $allowRetake = ! empty($assessment['allow_retake']);
        $pendingReview = $submitted && ! $showResults && empty($assessment['is_takeable']);
        $answerMap = ($submitted && $showResults) ? CourseAssessmentAnswer::mapForUser($id, $userId) : [];
        $result = $showResults ? $this->submissions->buildResult($questions, $answerMap) : null;

        return [
            'status' => 'ok',
            'data' => [
                'course' => $context['course'],
                'assessment' => $assessment,
                'display' => $display,
                'phaseLabels' => CourseAssessment::studentPhaseLabels(),
                'questions' => $questions,
                'answerMap' => $answerMap,
                'submitted' => $showResults,
                'hasSubmitted' => $submitted,
                'pendingReview' => $pendingReview,
                'isGraded' => ! empty($assessment['is_graded']),
                'allowRetake' => $allowRetake,
                'result' => $result,
                'submissionMeta' => CourseAssessmentSubmission::getForStudent($id, $userId),
                'teacherName' => trim((string) ($context['course']->teacher?->name ?? '')),
                'draftAnswers' => $this->drafts->answersForTakeForm($id, $userId, $questions, $submitted, $allowRetake),
            ],
        ];
    }
}
