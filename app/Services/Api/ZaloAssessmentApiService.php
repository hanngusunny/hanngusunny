<?php

namespace App\Services\Api;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseMember;
use App\Models\User;
use App\Services\AssessmentDraftService;
use App\Services\AssessmentFlowService;
use App\Services\AssessmentSubmissionService;
use App\Services\StudentAssessmentService;
use App\Services\StudentCourseNavService;
use App\Models\StudyProgramLevel;
use App\Support\HtmlContent;

class ZaloAssessmentApiService
{
    public function __construct(
        private ZaloApiSupport $api,
        private StudentAssessmentService $assessments,
        private AssessmentSubmissionService $scoring,
        private AssessmentDraftService $drafts,
        private AssessmentFlowService $flow,
        private StudentCourseNavService $nav,
    ) {
    }

    /**
     * @return array{code: string, message: string, status: int}|null
     */
    public function ensureReady(): ?array
    {
        return $this->api->ensureTables(
            ['users', 'courses', 'course_assessments'],
            'Module bài kiểm tra chưa được cài database.'
        );
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getStudentListByPhone(string $phone, ?int $courseId = null, ?int $levelId = null, ?string $month = null): array
    {
        $student = User::findStudentByPhone($phone);

        if ($student === null) {
            return ['error' => $this->api->error('STUDENT_NOT_FOUND', 'Không tìm thấy học viên với số điện thoại này.', 404)];
        }

        $result = $this->getByUserId((int) $student->id, $courseId, $levelId, $month);

        if (isset($result['error'])) {
            return $result;
        }

        $payload = $result['payload'];
        $payload['student'] = $this->api->formatStudentBrief($student);
        $this->applyZaloMiniAppAssessmentUrls($payload);

        return ['payload' => $payload];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getStudentDetailByPhone(string $phone, int $assessmentId): array
    {
        $student = User::findStudentByPhone($phone);

        if ($student === null) {
            return ['error' => $this->api->error('STUDENT_NOT_FOUND', 'Không tìm thấy học viên với số điện thoại này.', 404)];
        }

        $result = $this->getDetailByUserId((int) $student->id, $assessmentId);

        if (isset($result['error'])) {
            return $result;
        }

        $payload = $result['payload'];
        $payload['student'] = $this->api->formatStudentBrief($student);
        $this->applyZaloMiniAppAssessmentUrls($payload);

        return ['payload' => $payload];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getByUserId(int $userId, ?int $courseId = null, ?int $levelId = null, ?string $month = null): array
    {
        if ($error = $this->api->requireStudent($userId)) {
            return ['error' => $error];
        }

        $courseResult = $this->resolveCourseForStudentUser($userId, $courseId);

        if (isset($courseResult['error'])) {
            return ['error' => $courseResult['error']];
        }

        $course = $courseResult['course'];

        if ($course === null) {
            return [
                'payload' => [
                    'course' => null,
                    'level' => null,
                    'stats' => $this->emptyStats(),
                    'total' => 0,
                    'assessments' => [],
                    'message' => 'Học viên chưa tham gia khóa học nào.',
                ],
            ];
        }

        $resolvedCourseId = (int) $course->id;
        CourseAssessmentSubmission::reconcileForCourse($resolvedCourseId);

        $activeLevelId = $this->nav->resolveActiveLevelId($userId, (int) ($levelId ?? 0));
        $activeLevel = null;

        if ($activeLevelId > 0 && (int) ($course->program_id ?? 0) > 0) {
            $activeLevel = StudyProgramLevel::query()
                ->where('program_id', (int) $course->program_id)
                ->where('id', $activeLevelId)
                ->first();
        }

        $month = $this->normalizeMonth($month);
        $assessments = $this->buildAssessmentsForStudent($resolvedCourseId, $userId, $month, false);
        $stats = $this->emptyStats();

        foreach ($assessments as $assessment) {
            ++$stats['total'];
            $phase = (string) ($assessment['phase'] ?? '');

            if ($phase === 'open') {
                ++$stats['open'];
            } elseif ($phase === 'upcoming') {
                ++$stats['upcoming'];
            } elseif ($phase === 'completed' || $phase === 'closed') {
                ++$stats['completed'];
            }

            if (! empty($assessment['is_takeable'])) {
                ++$stats['takeable'];
            }

            if (! empty($assessment['has_submitted']) && empty($assessment['is_graded'])) {
                ++$stats['pending_grade'];
            }

            if (! empty($assessment['results_visible'])) {
                ++$stats['graded'];
            }
        }

        return [
            'payload' => [
                'course' => $this->api->formatCourse($course),
                'level' => $activeLevel !== null ? [
                    'id' => (int) $activeLevel->id,
                    'title' => (string) $activeLevel->title,
                ] : null,
                'filter_month' => $month,
                'stats' => $stats,
                'total' => count($assessments),
                'assessments' => $assessments,
                'message' => $assessments === [] ? 'Chưa có bài kiểm tra nào trong khóa học.' : null,
            ],
        ];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getDetailByUserId(int $userId, int $assessmentId): array
    {
        if ($error = $this->api->requireStudent($userId)) {
            return ['error' => $error];
        }

        if ($assessmentId <= 0) {
            return ['error' => $this->api->error('VALIDATION_ERROR', 'ID bài kiểm tra không hợp lệ.', 422)];
        }

        $assessment = CourseAssessment::query()->with(['course.teacher'])->find($assessmentId);

        if ($assessment === null) {
            return ['error' => $this->api->error('ASSESSMENT_NOT_FOUND', 'Không tìm thấy bài kiểm tra.', 404)];
        }

        $course = $assessment->course;
        $courseId = (int) $assessment->course_id;

        if ($course === null || ! $this->isStudentInCourse($courseId, $userId)) {
            return ['error' => $this->api->error('NOT_IN_COURSE', 'Học viên không thuộc khóa học của bài kiểm tra này.', 404)];
        }

        CourseAssessmentSubmission::reconcileForCourse($courseId);

        $questions = $this->assessments->readyQuestions($assessmentId);
        $enriched = $this->assessments->enrichForStudent($assessment, $userId, $questions);

        if ($this->flow->autoSubmitExpiredIfNeeded(
            $enriched,
            $assessmentId,
            $userId,
            $questions,
            ! empty($enriched['has_submitted'])
        )) {
            $assessment = CourseAssessment::query()->with(['course.teacher'])->find($assessmentId) ?? $assessment;
        }

        return [
            'payload' => [
                'course' => $this->api->formatCourse($course),
                'assessment' => $this->formatAssessmentForStudent($assessment, $userId, true),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildAssessmentsForStudent(int $courseId, int $userId, ?string $month, bool $withQuestions): array
    {
        $out = [];

        $models = CourseAssessment::query()
            ->with('slots')
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('sort_order')
            ->orderBy('assessment_date')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        foreach ($models as $assessment) {
            if ($month !== null && ! $assessment->hasDateInMonth($month)) {
                continue;
            }

            if ($this->assessments->readyQuestions((int) $assessment->id) === []) {
                continue;
            }

            $out[] = $this->formatAssessmentForStudent($assessment, $userId, $withQuestions);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAssessmentForStudent(CourseAssessment $assessment, int $userId, bool $withQuestions): array
    {
        $assessmentId = (int) $assessment->id;
        $questions = $this->assessments->readyQuestions($assessmentId);
        $enriched = $this->assessments->enrichForStudent($assessment, $userId, $questions);
        $display = $this->assessments->studentDisplay($enriched);
        $phaseLabels = CourseAssessment::studentPhaseLabels();
        $phase = (string) ($enriched['phase'] ?? '');
        $submitted = ! empty($enriched['has_submitted']);
        $allowRetake = ! empty($enriched['allow_retake']);
        $isGraded = ! empty($enriched['is_graded']);
        $showResults = ! empty($enriched['results_visible']);
        $answerMap = ($submitted && $showResults) ? CourseAssessmentAnswer::mapForUser($assessmentId, $userId) : [];
        $result = ($submitted && $showResults) ? $this->scoring->buildResult($questions, $answerMap) : null;
        $submission = CourseAssessmentSubmission::getForStudent($assessmentId, $userId);
        $hasTimeLimit = ! empty($enriched['has_time_limit']);
        $quizStarted = ! empty($enriched['quiz_started']);
        $needsStart = (! $submitted || $allowRetake) && ! $quizStarted && ! empty($enriched['is_takeable']);

        $choiceCount = 0;
        $essayCount = 0;

        foreach ($questions as $question) {
            if (($question['question_type'] ?? 'choice') === 'essay' || ($question['question_type'] ?? '') === 'fill') {
                ++$essayCount;
            } else {
                ++$choiceCount;
            }
        }

        $row = [
            'id' => $assessmentId,
            'event_type' => 'assessment',
            'title' => (string) ($enriched['title'] ?? ''),
            'description' => (string) ($enriched['description'] ?? ''),
            'assessment_date' => (string) ($enriched['assessment_date'] ?? ''),
            'start_time' => substr((string) ($enriched['start_time'] ?? ''), 0, 5),
            'end_time' => substr((string) ($enriched['end_time'] ?? ''), 0, 5),
            'status' => (string) ($enriched['status'] ?? ''),
            'phase' => $phase,
            'phase_label' => $phaseLabels[$phase] ?? $phase,
            'has_schedule' => ! empty($display['hasSchedule']),
            'schedule' => [
                'date_label' => (string) ($display['dateLabel'] ?? ''),
                'weekday_full' => (string) ($display['weekdayFull'] ?? ''),
                'weekday_short' => (string) ($display['weekdayShort'] ?? ''),
                'time_label' => (string) ($display['timeLabel'] ?? ''),
                'countdown_label' => (string) ($display['countdownLabel'] ?? ''),
            ],
            'question_count' => count($questions),
            'choice_count' => $choiceCount,
            'essay_count' => $essayCount,
            'is_takeable' => ! empty($enriched['is_takeable']),
            'can_open' => $this->assessments->canStudentOpenPage($enriched),
            'has_submitted' => $submitted,
            'allow_retake' => $allowRetake,
            'is_graded' => $isGraded,
            'results_visible' => $showResults,
            'pending_review' => $submitted && ! $isGraded,
            'grading_status' => $this->gradingStatus($submitted, $isGraded, $showResults),
            'grading_status_label' => $this->gradingStatusLabel($submitted, $isGraded, $showResults),
            'submitted_at' => optional($submission?->submitted_at)?->format('Y-m-d H:i:s'),
            'duration_minutes' => isset($enriched['duration_minutes']) ? (int) $enriched['duration_minutes'] : null,
            'has_time_limit' => $hasTimeLimit,
            'quiz_started' => $quizStarted,
            'needs_start' => $needsStart,
            'remaining_seconds' => (int) ($enriched['remaining_seconds'] ?? 0),
            'expires_at' => isset($enriched['expires_at'])
                ? (string) $enriched['expires_at']
                : (! empty($enriched['expires_ts']) ? date('c', (int) $enriched['expires_ts']) : null),
            'is_expired' => ! empty($enriched['is_expired']),
            'can_submit' => ! empty($enriched['is_takeable']) && $quizStarted && (! $submitted || $allowRetake),
            'result' => null,
            'urls' => [
                'web_detail' => $this->api->absoluteUrl('khoa-hoc-cua-toi/kiem-tra/'.$assessmentId),
                'api_detail' => $this->api->apiPath('hoc-vien/bai-kiem-tra-cua-toi/'.$assessmentId),
                'start' => $this->api->apiPath('hoc-vien/bai-kiem-tra-cua-toi/'.$assessmentId.'/bat-dau'),
                'submit' => $this->api->apiPath('hoc-vien/bai-kiem-tra-cua-toi/'.$assessmentId.'/nop'),
            ],
            'detail_url_hint' => '/api/v1/hoc-vien/bai-kiem-tra/'.$assessmentId.'?phone=',
        ];

        if ($showResults && $result !== null) {
            $row['result'] = [
                'score' => (int) ($result['score'] ?? 0),
                'correct' => (int) ($result['correct'] ?? 0),
                'total' => (int) ($result['total'] ?? 0),
                'essay_count' => (int) ($result['essay_count'] ?? 0),
                'points_earned' => (float) ($result['points_earned'] ?? 0),
                'points_possible' => (float) ($result['points_possible'] ?? 0),
                'score_label' => $this->scoring->formatPoints((float) ($result['points_earned'] ?? 0))
                    .'/'.$this->scoring->formatPoints((float) ($result['points_possible'] ?? 0)).' điểm',
                'submitted_at' => optional($submission?->submitted_at)?->format('Y-m-d H:i:s'),
            ];
        }

        if ($withQuestions) {
            $draftAnswers = [];

            if ((! $submitted || $allowRetake) && $quizStarted) {
                $draftAnswers = $this->drafts->answersForTakeForm(
                    $assessmentId,
                    $userId,
                    $questions,
                    $submitted,
                    $allowRetake
                );

                foreach ($draftAnswers as $questionId => $value) {
                    if (isset($answerMap[$questionId])) {
                        continue;
                    }

                    $answerMap[$questionId] = [
                        'selected_answer' => $value,
                        'answer_text' => $value,
                    ];
                }
            }

            $row['questions'] = $this->formatQuestionsForApi($questions, $answerMap, $showResults);
            $row['draft_answers'] = $draftAnswers;
        }

        return $row;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  array<int, array<string, mixed>>  $answerMap
     * @return list<array<string, mixed>>
     */
    private function formatQuestionsForApi(array $questions, array $answerMap, bool $submitted): array
    {
        $answerLabels = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
        $out = [];

        foreach ($questions as $index => $question) {
            $questionId = (int) ($question['id'] ?? 0);
            $questionType = (string) ($question['question_type'] ?? 'choice');
            $answerRow = $answerMap[$questionId] ?? [];
            $rawOptions = $this->decodeOptions($question['options_json'] ?? $question['options'] ?? null);
            $bankModel = app(\App\Support\Questions\QuestionBankItemModel::class);
            $options = array_map(function ($opt) use ($bankModel) {
                if (! is_string($opt) || trim($opt) === '') {
                    return $opt;
                }
                $trimmed = trim($opt);
                if (str_contains($trimmed, '<')) {
                    return HtmlContent::render($trimmed);
                }
                if ($bankModel->isOptionImage($trimmed)) {
                    return \App\Support\MediaUrl::to($trimmed);
                }
                return $trimmed;
            }, $rawOptions);

            $item = [
                'id' => $questionId,
                'sort_order' => (int) ($question['sort_order'] ?? $index),
                'question_type' => $questionType,
                'question_text' => HtmlContent::render((string) ($question['question_text'] ?? '')),
                'points' => $this->scoring->questionPoints($question),
                'options' => $options,
                'has_answer' => $answerRow !== [],
            ];

            if ($questionType === 'essay' || $questionType === 'fill') {
                $rawAnswer = (string) ($answerRow['answer_text'] ?? $answerRow['selected_answer'] ?? '');
                $item['student_answer_text'] = HtmlContent::render($rawAnswer);
            } else {
                $selected = strtolower(trim((string) ($answerRow['selected_answer'] ?? '')));
                $item['student_answer'] = $selected !== '' ? $selected : null;
                $item['student_answer_label'] = $answerLabels[$selected] ?? null;
                $item['is_correct'] = ! empty($answerRow['is_correct']);
            }

            if ($submitted && $answerRow !== []) {
                $pointsEarned = $answerRow['points_earned'] ?? null;
                $item['points_earned'] = $pointsEarned !== null && $pointsEarned !== ''
                    ? (float) $pointsEarned
                    : null;
                $item['points_label'] = $item['points_earned'] !== null
                    ? $this->scoring->formatPoints((float) $item['points_earned']).'/'
                        .$this->scoring->formatPoints($item['points']).' điểm'
                    : null;

                $teacherGradedAt = trim((string) ($answerRow['teacher_graded_at'] ?? ''));

                if ($teacherGradedAt !== '') {
                    $item['teacher_graded'] = true;
                    $item['teacher_graded_at'] = $teacherGradedAt;
                }

                $comment = trim((string) ($answerRow['teacher_comment'] ?? ''));

                if ($comment !== '') {
                    $item['teacher_comment'] = $comment;
                }
            }

            if ($submitted) {
                if ($questionType !== 'essay' && $questionType !== 'fill') {
                    $correct = strtolower(trim((string) ($question['correct_answer'] ?? '')));
                    $item['correct_answer'] = $correct !== '' ? $correct : null;
                    $item['correct_answer_label'] = $answerLabels[$correct] ?? null;
                }

                $rawExplanation = (string) ($question['answer_explanation'] ?? '');
                $renderedExplanation = HtmlContent::render($rawExplanation);
                $item['explanation'] = $renderedExplanation;
                $item['explanation_html'] = $renderedExplanation;
            }

            $out[] = $item;
        }

        return $out;
    }

    /**
     * @return list<mixed>
     */
    private function decodeOptions(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyZaloMiniAppAssessmentUrls(array &$payload): void
    {
        if (isset($payload['assessment']) && is_array($payload['assessment'])) {
            $this->rewriteAssessmentZaloUrls($payload['assessment']);

            return;
        }

        if (! isset($payload['assessments']) || ! is_array($payload['assessments'])) {
            return;
        }

        foreach ($payload['assessments'] as $index => $assessment) {
            if (! is_array($assessment)) {
                continue;
            }

            $this->rewriteAssessmentZaloUrls($payload['assessments'][$index]);
        }
    }

    /**
     * @param  array<string, mixed>  $assessment
     */
    private function rewriteAssessmentZaloUrls(array &$assessment): void
    {
        $assessmentId = (int) ($assessment['id'] ?? 0);

        if ($assessmentId <= 0) {
            return;
        }

        if (! isset($assessment['urls']) || ! is_array($assessment['urls'])) {
            $assessment['urls'] = [];
        }

        $assessment['urls']['api_detail'] = $this->api->apiPath('zalo-mini-app/hoc-vien/bai-kiem-tra/'.$assessmentId);
        $assessment['detail_url_hint'] = '/api/v1/zalo-mini-app/hoc-vien/bai-kiem-tra/'.$assessmentId.'?phone=';
    }

    /**
     * @return array{course: Course}|array{error: array<string, mixed>}|array{course: null}
     */
    private function resolveCourseForStudentUser(int $userId, ?int $courseId): array
    {
        if ($courseId !== null && $courseId > 0) {
            if (! $this->isStudentInCourse($courseId, $userId)) {
                return ['error' => $this->api->error('NOT_IN_COURSE', 'Học viên không thuộc khóa học này.', 404)];
            }

            $course = Course::query()->with('teacher')->find($courseId);

            if ($course === null) {
                return ['error' => $this->api->error('COURSE_NOT_FOUND', 'Không tìm thấy khóa học.', 404)];
            }

            return ['course' => $course];
        }

        $active = CourseMember::activeCourseFor($userId);

        if ($active !== null) {
            return ['course' => $active];
        }

        $lookup = $this->api->courseForLookup($userId);

        return ['course' => $lookup['course'] ?? null];
    }

    private function isStudentInCourse(int $courseId, int $userId): bool
    {
        if ($courseId <= 0 || $userId <= 0) {
            return false;
        }

        if (CourseMember::isMemberOf($courseId, $userId)) {
            return true;
        }

        return CourseMember::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * @return array{total: int, open: int, upcoming: int, takeable: int, completed: int, pending_grade: int, graded: int}
     */
    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'open' => 0,
            'upcoming' => 0,
            'takeable' => 0,
            'completed' => 0,
            'pending_grade' => 0,
            'graded' => 0,
        ];
    }

    private function gradingStatus(bool $submitted, bool $isGraded, bool $showResults): ?string
    {
        if (! $submitted) {
            return null;
        }

        if ($showResults) {
            return 'graded';
        }

        return $isGraded ? 'graded' : 'pending';
    }

    private function gradingStatusLabel(bool $submitted, bool $isGraded, bool $showResults): ?string
    {
        $status = $this->gradingStatus($submitted, $isGraded, $showResults);

        return match ($status) {
            'graded' => 'Đã chấm',
            'pending' => 'Chờ chấm',
            default => null,
        };
    }

    private function normalizeMonth(?string $month): ?string
    {
        if ($month === null || trim($month) === '') {
            return null;
        }

        $month = trim($month);

        return preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : null;
    }
}
