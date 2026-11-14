<?php

namespace App\Services;

use App\Models\CourseKnowledgeReview;
use App\Models\CourseKnowledgeReviewAnswer;
use App\Models\CourseKnowledgeReviewQuestion;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\CourseSessionQuestion;
use App\Models\CourseSessionQuizAnswer;
use App\Models\CourseSessionReflection;
use App\Models\StudyProgramKnowledgeReview;
use App\Support\StudentQuizGrader;

class StudentWorkspaceService
{
    public function __construct(
        private StudentSessionCatalogService $catalog,
        private StudentCourseNavService $nav,
        private StudentQuizGrader $grader,
        private StudentQuizPracticeService $practice,
        private KnowledgeReviewAttemptService $krAttempt,
        private KnowledgeReviewFlowService $krFlow,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return array{
     *   selectedPublicCode: string|null,
     *   sessionDetail: array<string, mixed>|null,
     *   sessionLockedMessage: string|null,
     *   workspaceTab: string,
     *   contentTab: string,
     *   hasSessionKnowledgeReview: bool
     * }
     */
    public function resolveSessionPanel(
        array $sessions,
        int $nearestSessionId,
        ?string $requestedBuoi,
        int $userId,
        mixed $tabParam
    ): array {
        $selectedCode = $this->catalog->resolveSelectedPublicCode(
            $sessions,
            $nearestSessionId,
            $requestedBuoi !== null && $requestedBuoi !== '' ? $requestedBuoi : null
        );

        $sessionDetail = null;
        $sessionLockedMessage = null;
        $hasLinkedKr = false;

        if ($selectedCode !== null && $selectedCode !== '') {
            $sessionDetail = $this->buildDetail($selectedCode, $userId);

            if ($sessionDetail === null) {
                foreach ($sessions as $row) {
                    if ((string) ($row['public_code'] ?? '') !== $selectedCode) {
                        continue;
                    }

                    if (empty($row['is_viewable'])) {
                        $sessionLockedMessage = 'Buổi học này chưa mở. Bạn chỉ xem được buổi học gần nhất.';
                    }

                    break;
                }
            } else {
                $hasLinkedKr = $this->sessionHasLinkedKnowledgeReview(
                    $sessionDetail['session'],
                    $sessionDetail['course']
                );
            }
        }

        $workspaceTab = $this->resolveWorkspaceTab($tabParam, $sessionDetail, $hasLinkedKr);
        $contentTab = $this->resolveContentTabForDetail($workspaceTab, $sessionDetail, $hasLinkedKr);

        return [
            'selectedPublicCode' => $selectedCode !== '' ? $selectedCode : null,
            'sessionDetail' => $sessionDetail,
            'sessionLockedMessage' => $sessionLockedMessage,
            'workspaceTab' => $workspaceTab,
            'contentTab' => $contentTab,
            'hasSessionKnowledgeReview' => $hasLinkedKr,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buildDetail(string $publicCode, int $userId): ?array
    {
        $session = CourseSession::query()
            ->with(['course.teacher', 'course.room', 'course.program', 'programSession.level', 'questions'])
            ->where('public_code', $publicCode)
            ->first();

        if ($session === null) {
            return null;
        }

        $course = $session->course;

        if ($course === null || ! CourseMember::isMemberOf((int) $course->id, $userId)) {
            return null;
        }

        $activeCourse = CourseMember::activeCourseFor($userId);

        if ($activeCourse === null || (int) $activeCourse->id !== (int) $course->id) {
            return null;
        }

        $row = $this->catalog->toStudentRow($session, $userId);
        $allForCourse = $this->catalog->forStudentCourse((int) $course->id, $userId);
        $nearestId = $this->catalog->nearestSessionId($allForCourse);
        $row['is_viewable'] = ($row['phase'] === 'ended')
            || ((int) $row['id'] === $nearestId && $nearestId > 0);

        if ($row['phase'] === 'cancelled' || empty($row['is_viewable'])) {
            return null;
        }

        $sessionId = (int) $session->id;
        $attendance = CourseSessionAttendance::query()
            ->where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        $preQuestions = $this->questionsForGroup($session, 'pre');
        $reviewQuestions = $this->questionsForGroup($session, 'review', ['choice', 'multi_select']);
        $quizQuestions = $row['phase'] === 'ended' ? $this->questionsForGroup($session, 'post_practice') : [];

        $preAnswerMap = CourseSessionQuizAnswer::mapForUser($sessionId, $userId);
        $preIds = array_map(static fn (array $q): int => (int) $q['id'], $preQuestions);
        $preSubmitted = CourseSessionQuizAnswer::hasSubmittedForQuestionIds($sessionId, $userId, $preIds);
        $preResult = $this->scoreFromMap($preQuestions, $preAnswerMap, $preSubmitted);

        $reviewIds = array_map(static fn (array $q): int => (int) $q['id'], $reviewQuestions);
        $reviewSubmitted = CourseSessionQuizAnswer::hasSubmittedForQuestionIds($sessionId, $userId, $reviewIds);
        $reviewResult = $this->scoreFromMap($reviewQuestions, $preAnswerMap, $reviewSubmitted);

        $quizSubmitted = $quizQuestions !== [] && CourseSessionQuizAnswer::hasSubmittedForQuestionIds(
            $sessionId,
            $userId,
            array_map(static fn (array $q): int => (int) $q['id'], $quizQuestions)
        );
        $quizResult = $this->scoreFromMap($quizQuestions, $preAnswerMap, $quizSubmitted);

        $reflection = null;
        $knowledgeReview = null;
        $knowledgeReviewQuestions = [];
        $knowledgeReviewAnswerMap = [];
        $knowledgeReviewSubmitted = false;
        $knowledgeReviewResult = null;

        if ($row['phase'] === 'ended') {
            $reflectionModel = CourseSessionReflection::getForUser($sessionId, $userId);
            $reflection = $reflectionModel?->toArray();

            $programKrId = (int) ($session->program_knowledge_review_id ?? 0);

            if ($programKrId > 0) {
                $knowledgeReviewModel = CourseKnowledgeReview::findForCourseByProgramReview((int) $course->id, $programKrId);

                if ($knowledgeReviewModel !== null) {
                    $courseKrId = (int) $knowledgeReviewModel->id;
                    $knowledgeReviewQuestions = $this->grader->readyQuestions(
                        CourseKnowledgeReviewQuestion::query()
                            ->where('course_knowledge_review_id', $courseKrId)
                            ->orderBy('sort_order')
                            ->orderBy('id')
                            ->get()
                    );
                    $knowledgeReviewAnswerMap = CourseKnowledgeReviewAnswer::mapForUser($courseKrId, $userId);
                    $knowledgeReviewSubmitted = CourseKnowledgeReviewAnswer::hasSubmitted($courseKrId, $userId, $knowledgeReviewQuestions);
                    $knowledgeReviewResult = $this->scoreFromMap($knowledgeReviewQuestions, $knowledgeReviewAnswerMap, $knowledgeReviewSubmitted);
                    $krPracticeMode = $this->practice->isPracticeMode(StudentQuizPracticeService::TYPE_KNOWLEDGE, $courseKrId, $userId);
                    $knowledgeReview = $this->krAttempt->enrichReviewTimer(
                        $knowledgeReviewModel->toArray(),
                        $userId,
                        $knowledgeReviewSubmitted,
                        $krPracticeMode
                    );
                }
            }
        }

        return [
            'session' => $row,
            'course' => [
                'id' => (int) $course->id,
                'title' => (string) $course->title,
                'program_id' => (int) $course->program_id,
                'teacher_name' => (string) ($course->teacher?->name ?? ''),
                'status' => (string) $course->status,
            ],
            'sessionId' => $sessionId,
            'phaseLabels' => CourseSession::phaseLabels(),
            'teacherName' => (string) ($course->teacher?->name ?? ''),
            'attendance' => $attendance?->toArray(),
            'attendeeCount' => CourseSessionAttendance::countBySession($sessionId),
            'quizQuestions' => $quizQuestions,
            'quizAnswerMap' => $preAnswerMap,
            'quizSubmitted' => $quizSubmitted,
            'quizResult' => $quizResult,
            'reviewQuestions' => $reviewQuestions,
            'reviewAnswerMap' => $preAnswerMap,
            'reviewSubmitted' => $reviewSubmitted,
            'reviewResult' => $reviewResult,
            'preQuestions' => $preQuestions,
            'preAnswerMap' => $preAnswerMap,
            'preSubmitted' => $preSubmitted,
            'preResult' => $preResult,
            'reflection' => $reflection,
            'knowledgeReview' => $knowledgeReview,
            'knowledgeReviewQuestions' => $knowledgeReviewQuestions,
            'knowledgeReviewAnswerMap' => $knowledgeReviewAnswerMap,
            'knowledgeReviewSubmitted' => $knowledgeReviewSubmitted,
            'knowledgeReviewResult' => $knowledgeReviewResult,
            'display' => $row['display'],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $sessionDetail
     */
    public function resolveWorkspaceTab(mixed $tabParam, ?array $sessionDetail, bool $hasLinkedKr): string
    {
        $tab = is_string($tabParam) ? trim($tabParam) : '';

        if ($tab === StudentCourseNavService::TAB_ASSESSMENTS) {
            return StudentCourseNavService::TAB_ASSESSMENTS;
        }

        if ($tab === StudentCourseNavService::TAB_OVERVIEW) {
            return StudentCourseNavService::TAB_OVERVIEW;
        }

        if ($tab === StudentCourseNavService::TAB_KNOWLEDGE_REVIEW) {
            return $hasLinkedKr
                ? StudentCourseNavService::TAB_KNOWLEDGE_REVIEW
                : $this->defaultTabFromSessionDetail($sessionDetail);
        }

        if ($this->nav->isSessionContentTab($tab)
            && $tab !== StudentCourseNavService::TAB_KNOWLEDGE_REVIEW
            && $tab !== StudentCourseNavService::TAB_OVERVIEW) {
            return $tab;
        }

        return $this->defaultTabFromSessionDetail($sessionDetail);
    }

    /**
     * @param  array<string, mixed>|null  $sessionDetail
     */
    private function defaultTabFromSessionDetail(?array $sessionDetail): string
    {
        if ($sessionDetail === null) {
            return StudentCourseNavService::TAB_PRE;
        }

        $phase = (string) ($sessionDetail['session']['phase'] ?? 'upcoming');

        return $phase === 'ended'
            ? StudentCourseNavService::TAB_POST
            : StudentCourseNavService::TAB_PRE;
    }

    /**
     * @param  array<string, mixed>|null  $sessionDetail
     */
    private function resolveContentTabForDetail(string $workspaceTab, ?array $sessionDetail, bool $hasLinkedKr): string
    {
        if ($workspaceTab === StudentCourseNavService::TAB_ASSESSMENTS) {
            return StudentCourseNavService::TAB_ASSESSMENTS;
        }

        if ($workspaceTab === StudentCourseNavService::TAB_OVERVIEW) {
            return $this->defaultTabFromSessionDetail($sessionDetail);
        }

        if ($sessionDetail === null) {
            return StudentCourseNavService::TAB_PRE;
        }

        if ($workspaceTab === StudentCourseNavService::TAB_KNOWLEDGE_REVIEW && ! $hasLinkedKr) {
            return StudentCourseNavService::TAB_PRE;
        }

        return $workspaceTab;
    }

    /**
     * @param  array<string, mixed>  $session
     * @param  array<string, mixed>  $course
     */
    public function sessionHasLinkedKnowledgeReview(array $session, array $course): bool
    {
        $programKnowledgeReviewId = (int) ($session['program_knowledge_review_id'] ?? 0);

        if ($programKnowledgeReviewId <= 0) {
            return false;
        }

        $programReview = StudyProgramKnowledgeReview::query()->find($programKnowledgeReviewId);

        if ($programReview === null) {
            return false;
        }

        $programId = (int) ($course['program_id'] ?? 0);
        $reviewProgramId = (int) $programReview->program_id;

        return $programId <= 0 || $reviewProgramId === $programId;
    }

    /**
     * @param  list<string>|null  $types
     * @return list<array<string, mixed>>
     */
    private function questionsForGroup(CourseSession $session, string $group, ?array $types = null): array
    {
        $rows = $session->questions
            ->where('question_group', $group)
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->values();

        if ($types !== null) {
            $rows = $rows->filter(static function (CourseSessionQuestion $question) use ($types): bool {
                $type = (string) $question->question_type;
                $type = $type === 'text' ? 'fill' : $type;

                return in_array($type, $types, true);
            });
        }

        return $this->grader->readyQuestions($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  array<int, array<string, mixed>>  $answerMap
     * @return array{correct:int,total:int,score:int}|null
     */
    private function scoreFromMap(array $questions, array $answerMap, bool $submitted): ?array
    {
        if (! $submitted || $questions === []) {
            return null;
        }

        $correct = 0;

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);
            $row = $answerMap[$questionId] ?? null;

            if ($row !== null && ! empty($row['is_correct'])) {
                $correct++;
            }
        }

        $total = count($questions);

        return [
            'correct' => $correct,
            'total' => $total,
            'score' => $total > 0 ? (int) round(($correct / $total) * 100) : 0,
        ];
    }
}
