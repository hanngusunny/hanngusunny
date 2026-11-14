<?php

namespace App\Services\Api;

use App\Models\CourseKnowledgeReview;
use App\Models\CourseKnowledgeReviewAnswer;
use App\Models\CourseKnowledgeReviewQuestion;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\CourseSessionQuestion;
use App\Models\CourseSessionQuizAnswer;
use App\Models\CourseSessionReflection;
use App\Models\StudyProgramKnowledgeReview;
use App\Services\KnowledgeReviewAttemptService;
use App\Services\KnowledgeReviewFlowService;
use App\Services\StudentCourseNavService;
use App\Services\StudentCourseVideoLibraryService;
use App\Services\StudentQuizPracticeService;
use App\Support\StudentQuizGrader;
use App\Support\VideoEmbed;

class ZaloSessionQuizApiService
{
    public function __construct(
        private ZaloApiSupport $api,
        private ZaloSessionApiService $sessions,
        private SessionQuestionApiFormatter $formatter,
        private StudentQuizGrader $grader,
        private StudentQuizPracticeService $practice,
        private KnowledgeReviewAttemptService $krAttempt,
        private KnowledgeReviewFlowService $krFlow,
        private StudentCourseNavService $nav,
        private StudentCourseVideoLibraryService $videos,
    ) {
    }

    /**
     * @return array{code: string, message: string, status: int}|null
     */
    public function ensureReady(): ?array
    {
        return $this->sessions->ensureReady();
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getSessionDetail(int $userId, string $publicCode): array
    {
        if ($error = $this->api->requireStudent($userId)) {
            return ['error' => $error];
        }

        $resolved = $this->sessions->resolveStudentSession($userId, $publicCode);

        if (isset($resolved['error'])) {
            return $resolved;
        }

        $session = $resolved['session'];
        $row = $resolved['row'];
        $course = $session->course;
        $phase = (string) ($row['phase'] ?? '');

        if ($phase === 'cancelled' || empty($row['is_viewable'])) {
            return ['error' => $this->api->error(
                'SESSION_NOT_VIEWABLE',
                'Buổi học này chưa mở. Bạn chỉ xem được buổi học gần nhất hoặc buổi đã học.',
                403
            )];
        }

        $sessionId = (int) $session->id;
        $courseId = (int) $course->id;
        $preBlock = $this->buildPreQuizBlock($session, $userId, $phase);
        $postBlock = $this->buildPostQuizBlock($session, $userId, $phase);
        $krBlock = $this->buildKnowledgeReviewBlock($session, $row, $courseId, $userId, $phase);
        $reviewBlock = $this->buildReviewQuizBlock($session, $row, $userId);
        $reflectionBlock = $this->buildReflectionBlock($sessionId, $userId, $phase);
        $publicCode = (string) $session->public_code;
        $basePath = 'hoc-vien/buoi-hoc/'.$publicCode;

        $preBlock['urls'] = $this->quizTabUrls($basePath, 'trac-nghiem-truoc');
        $postBlock['urls'] = $this->quizTabUrls($basePath, 'trac-nghiem');
        $krBlock['urls'] = array_merge(
            $this->quizTabUrls($basePath, 'on-tap-kien-thuc'),
            ['start' => $this->api->apiPath($basePath.'/on-tap-kien-thuc/bat-dau')]
        );

        $levelContext = $this->nav->resolveLevelContext($course, $session);
        $attendance = CourseSessionAttendance::query()
            ->where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();
        $activeTab = $phase === 'ended'
            ? (! empty($krBlock['available']) ? StudentCourseNavService::TAB_KNOWLEDGE_REVIEW : StudentCourseNavService::TAB_POST)
            : StudentCourseNavService::TAB_PRE;
        $meetingUrls = $this->sessions->meetingUrlsForSession((int) ($row['online_room_id'] ?? 0));
        $roomId = (int) ($row['online_room_id'] ?? 0);

        return [
            'payload' => [
                'session' => array_merge(
                    $this->sessions->formatSessionHeader($row, $session, $userId),
                    $meetingUrls,
                    ['attendee_count' => CourseSessionAttendance::countBySession($sessionId)]
                ),
                'course' => [
                    'id' => $courseId,
                    'title' => (string) $course->title,
                    'teacher_name' => (string) ($course->teacher?->name ?? ''),
                    'program_id' => (int) ($course->program_id ?? 0) > 0 ? (int) $course->program_id : null,
                ],
                'level' => $levelContext['level'] !== null ? [
                    'id' => (int) ($levelContext['level_id'] ?? 0),
                    'title' => (string) ($levelContext['level']->title ?? ''),
                ] : null,
                'attendance' => [
                    'has_attended' => ! empty($row['has_attended']),
                    'joined_at' => $attendance?->joined_at?->format('Y-m-d H:i:s'),
                ],
                'active_tab' => $activeTab,
                'available_tabs' => $this->buildAvailableTabs($phase, $preBlock, $postBlock, $krBlock, $reviewBlock, $reflectionBlock, $row),
                'content' => [
                    'pre_study' => $this->buildPreStudyContent($row),
                    'post_study' => $phase === 'ended' ? $this->buildPostStudyContent($row) : null,
                ],
                'tabs' => [
                    'pre' => $preBlock,
                    'post' => $postBlock,
                    'knowledge_review' => $krBlock,
                    'review_quiz' => $reviewBlock,
                    'reflection' => $reflectionBlock,
                ],
                'urls' => [
                    'detail' => $this->api->apiPath($basePath),
                    'quiz_status' => $this->api->apiPath($basePath.'/trang-thai-bai-tap'),
                    'join_status' => $this->api->apiPath($basePath.'/trang-thai'),
                    'join' => $this->api->apiPath($basePath.'/vao'),
                    'reflection' => $this->api->apiPath($basePath.'/nhan-xet'),
                    'online_room' => $roomId > 0 ? $this->api->apiPath('lop-hoc-online/'.$roomId) : null,
                    'upload_answer_image' => $this->api->apiPath('hoc-vien/upload-anh-cau-tra-loi'),
                    'upload_recording' => $this->api->apiPath('hoc-vien/upload-ghi-am'),
                    'assessments' => $this->api->apiPath('hoc-vien/bai-kiem-tra-cua-toi'),
                    'course_video_library' => $this->api->apiPath('hoc-vien/thu-vien-video-khoa-hoc'),
                ],
            ],
        ];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getSessionPreTab(int $userId, string $publicCode): array
    {
        $result = $this->getSessionDetail($userId, $publicCode);

        if (isset($result['error'])) {
            return $result;
        }

        $payload = $result['payload'];
        $preTab = $payload['tabs']['pre'] ?? [];

        return [
            'payload' => [
                'tab' => StudentCourseNavService::TAB_PRE,
                'session' => $payload['session'],
                'course' => $payload['course'],
                'level' => $payload['level'],
                'attendance' => $payload['attendance'],
                'pre_study' => $payload['content']['pre_study'] ?? [],
                'quiz' => $preTab,
                'urls' => array_merge(
                    [
                        'detail' => $payload['urls']['detail'] ?? null,
                        'quiz_status' => $payload['urls']['quiz_status'] ?? null,
                        'join_status' => $payload['urls']['join_status'] ?? null,
                        'join' => $payload['urls']['join'] ?? null,
                        'upload_answer_image' => $payload['urls']['upload_answer_image'] ?? null,
                        'upload_recording' => $payload['urls']['upload_recording'] ?? null,
                    ],
                    is_array($preTab['urls'] ?? null) ? $preTab['urls'] : []
                ),
            ],
        ];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getSessionPostTab(int $userId, string $publicCode): array
    {
        $result = $this->getSessionDetail($userId, $publicCode);

        if (isset($result['error'])) {
            return $result;
        }

        $payload = $result['payload'];
        $phase = (string) ($payload['session']['phase'] ?? '');

        if ($phase !== 'ended') {
            return ['error' => $this->api->error(
                'SESSION_NOT_ENDED',
                'Nội dung sau buổi học sẽ mở khi buổi đã kết thúc.',
                403
            )];
        }

        $postTabIds = [
            StudentCourseNavService::TAB_POST,
            StudentCourseNavService::TAB_KNOWLEDGE_REVIEW,
            'review_quiz',
            'reflection',
        ];

        $availableTabs = array_values(array_filter(
            $payload['available_tabs'] ?? [],
            static fn (array $tab): bool => in_array((string) ($tab['id'] ?? ''), $postTabIds, true)
        ));

        return [
            'payload' => [
                'tab' => StudentCourseNavService::TAB_POST,
                'session' => $payload['session'],
                'course' => $payload['course'],
                'level' => $payload['level'],
                'attendance' => $payload['attendance'],
                'available_tabs' => $availableTabs,
                'post_study' => $payload['content']['post_study'] ?? [],
                'tabs' => [
                    'post' => $payload['tabs']['post'] ?? [],
                    'knowledge_review' => $payload['tabs']['knowledge_review'] ?? [],
                    'review_quiz' => $payload['tabs']['review_quiz'] ?? [],
                    'reflection' => $payload['tabs']['reflection'] ?? [],
                ],
                'urls' => $payload['urls'] ?? [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPreQuizBlock(CourseSession $session, int $userId, string $phase): array
    {
        if ($phase === 'cancelled') {
            return $this->emptyQuizBlock(false);
        }

        $questions = $this->questionsForGroup($session, 'pre');

        if ($questions === []) {
            return $this->emptyQuizBlock(false);
        }

        return $this->buildSessionQuizBlock(
            StudentQuizPracticeService::TYPE_PRE,
            (int) $session->id,
            $userId,
            $questions,
            true
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPostQuizBlock(CourseSession $session, int $userId, string $phase): array
    {
        if ($phase !== 'ended') {
            return $this->emptyQuizBlock(false);
        }

        $questions = $this->questionsForGroup($session, 'post_practice');

        if ($questions === []) {
            return $this->emptyQuizBlock(false);
        }

        return $this->buildSessionQuizBlock(
            StudentQuizPracticeService::TYPE_POST,
            (int) $session->id,
            $userId,
            $questions,
            false
        );
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<string, mixed>
     */
    private function buildSessionQuizBlock(
        string $practiceType,
        int $sessionId,
        int $userId,
        array $questions,
        bool $checkQuestionIds
    ): array {
        $questionIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $questions);
        $savedSubmitted = $checkQuestionIds
            ? CourseSessionQuizAnswer::hasSubmittedForQuestionIds($sessionId, $userId, $questionIds)
            : CourseSessionQuizAnswer::hasSubmittedForQuestionIds($sessionId, $userId, $questionIds);
        $savedAnswerMap = CourseSessionQuizAnswer::mapForUser($sessionId, $userId);
        $savedResult = $savedSubmitted ? $this->computeQuizResult($questions, $savedAnswerMap) : null;
        $display = $this->mapPracticeDisplay($this->practice->resolveStudentDisplay(
            $practiceType,
            $sessionId,
            $userId,
            $savedSubmitted,
            $savedAnswerMap,
            $savedResult
        ));
        $showResults = ! empty($display['submitted']);

        return array_merge([
            'available' => true,
            'question_count' => count($questions),
            'submitted' => $display['submitted'],
            'has_saved_submission' => $display['has_saved_submission'],
            'practice_mode' => $display['practice_mode'],
            'is_practice_result' => $display['is_practice_result'],
            'result' => $display['result'],
            'questions' => $this->formatter->formatQuestions($questions, $display['answer_map'], $showResults),
        ], $this->quizSubmitMeta($display, $questions !== []));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function buildKnowledgeReviewBlock(CourseSession $session, array $row, int $courseId, int $userId, string $phase): array
    {
        if ($phase !== 'ended' || ! $this->sessionHasLinkedKnowledgeReview($row, $courseId)) {
            return $this->emptyKnowledgeReviewBlock(false);
        }

        $programKrId = (int) ($row['program_knowledge_review_id'] ?? 0);
        $knowledgeReviewModel = CourseKnowledgeReview::findForCourseByProgramReview($courseId, $programKrId);

        if ($knowledgeReviewModel === null) {
            return $this->emptyKnowledgeReviewBlock(false);
        }

        $courseKrId = (int) $knowledgeReviewModel->id;
        $questions = $this->grader->readyQuestions(
            CourseKnowledgeReviewQuestion::query()
                ->where('course_knowledge_review_id', $courseKrId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );

        if ($questions === []) {
            return $this->emptyKnowledgeReviewBlock(true, $knowledgeReviewModel->toArray());
        }

        $savedSubmitted = CourseKnowledgeReviewAnswer::hasSubmitted($courseKrId, $userId, $questions);
        $savedAnswerMap = CourseKnowledgeReviewAnswer::mapForUser($courseKrId, $userId);
        $savedResult = $savedSubmitted ? $this->computeQuizResult($questions, $savedAnswerMap) : null;
        $display = $this->mapPracticeDisplay($this->practice->resolveStudentDisplay(
            StudentQuizPracticeService::TYPE_KNOWLEDGE,
            $courseKrId,
            $userId,
            $savedSubmitted,
            $savedAnswerMap,
            $savedResult
        ));
        $isPracticeMode = ! empty($display['practice_mode']);
        $knowledgeReview = $this->krAttempt->enrichReviewTimer(
            $knowledgeReviewModel->toArray(),
            $userId,
            $savedSubmitted,
            $isPracticeMode
        );

        if (
            ! $savedSubmitted
            && ! $isPracticeMode
            && ! empty($knowledgeReview['quiz_started'])
            && ! empty($knowledgeReview['is_expired'])
        ) {
            $this->krFlow->autoSubmitExpiredIfNeeded(
                $knowledgeReview,
                $courseKrId,
                $userId,
                $questions,
                $savedSubmitted,
                $isPracticeMode
            );
            $savedSubmitted = CourseKnowledgeReviewAnswer::hasSubmitted($courseKrId, $userId, $questions);
            $savedAnswerMap = CourseKnowledgeReviewAnswer::mapForUser($courseKrId, $userId);
            $savedResult = $savedSubmitted ? $this->computeQuizResult($questions, $savedAnswerMap) : null;
            $display = $this->mapPracticeDisplay($this->practice->resolveStudentDisplay(
                StudentQuizPracticeService::TYPE_KNOWLEDGE,
                $courseKrId,
                $userId,
                $savedSubmitted,
                $savedAnswerMap,
                $savedResult
            ));
            $knowledgeReview = $this->krAttempt->enrichReviewTimer(
                $knowledgeReviewModel->toArray(),
                $userId,
                $savedSubmitted,
                $isPracticeMode
            );
        }

        $showResults = ! empty($display['submitted']);
        $needsStart = ! empty($knowledgeReview['has_time_limit']) && empty($knowledgeReview['quiz_started']);
        $canSubmit = $questions !== [] && ! $needsStart && (
            (empty($display['has_saved_submission']) && ! empty($knowledgeReview['is_takeable']))
            || ! empty($display['practice_mode'])
        );

        return [
            'available' => true,
            'question_count' => count($questions),
            'submitted' => $display['submitted'],
            'has_saved_submission' => $display['has_saved_submission'],
            'practice_mode' => $display['practice_mode'],
            'is_practice_result' => $display['is_practice_result'],
            'can_submit' => $canSubmit,
            'needs_start' => $needsStart,
            'result' => $display['result'],
            'knowledge_review' => [
                'id' => (int) ($knowledgeReview['id'] ?? 0),
                'title' => (string) ($knowledgeReview['title'] ?? ''),
                'duration_minutes' => isset($knowledgeReview['duration_minutes']) ? (int) $knowledgeReview['duration_minutes'] : null,
                'has_time_limit' => ! empty($knowledgeReview['has_time_limit']),
                'quiz_started' => ! empty($knowledgeReview['quiz_started']),
                'is_takeable' => ! empty($knowledgeReview['is_takeable']),
                'is_expired' => ! empty($knowledgeReview['is_expired']),
                'remaining_seconds' => (int) ($knowledgeReview['remaining_seconds'] ?? 0),
                'expires_at' => isset($knowledgeReview['attempt']['expires_at'])
                    ? (string) $knowledgeReview['attempt']['expires_at']
                    : null,
                'timer_label' => (string) ($knowledgeReview['timer_label'] ?? ''),
            ],
            'questions' => $this->formatter->formatQuestions($questions, $display['answer_map'], $showResults),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function buildReviewQuizBlock(CourseSession $session, array $row, int $userId): array
    {
        $questions = $this->questionsForGroup($session, 'review', ['choice', 'multi_select']);
        $roomId = (int) ($row['online_room_id'] ?? 0);

        if ($questions === []) {
            return [
                'available' => false,
                'question_count' => 0,
                'submitted' => false,
                'can_submit' => false,
                'submit_in_online_room' => true,
                'result' => null,
                'questions' => [],
                'note' => 'Buổi học chưa có bài kiểm tra bài cũ.',
                'urls' => [],
            ];
        }

        $sessionId = (int) $session->id;
        $reviewQuestionIds = array_map(static fn (array $q): int => (int) ($q['id'] ?? 0), $questions);
        $submitted = CourseSessionQuizAnswer::hasSubmittedForQuestionIds($sessionId, $userId, $reviewQuestionIds);
        $answerMap = CourseSessionQuizAnswer::mapForUser($sessionId, $userId);
        $result = $submitted ? $this->computeQuizResult($questions, $answerMap) : null;

        return [
            'available' => true,
            'question_count' => count($questions),
            'submitted' => $submitted,
            'can_submit' => false,
            'submit_in_online_room' => true,
            'result' => $result,
            'questions' => $submitted ? $this->formatter->formatQuestions($questions, $answerMap, true) : [],
            'note' => $submitted
                ? 'Kết quả kiểm tra bài cũ trong buổi học online.'
                : 'Giáo viên sẽ bật bài kiểm tra trong lớp học online. Khi làm xong, kết quả sẽ hiển thị tại đây.',
            'urls' => $roomId > 0
                ? ['submit' => $this->api->apiPath('lop-hoc-online/'.$roomId.'/kiem-tra-bai-cu/nop')]
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReflectionBlock(int $sessionId, int $userId, string $phase): array
    {
        $ratingLabels = [
            1 => 'Chưa hài lòng',
            2 => 'Tạm được',
            3 => 'Bình thường',
            4 => 'Hài lòng',
            5 => 'Rất hài lòng',
        ];

        if ($phase !== 'ended') {
            return [
                'available' => false,
                'can_submit' => false,
                'has_reflection' => false,
                'student_comment' => '',
                'session_rating' => null,
                'rating_label' => null,
                'rating_labels' => $ratingLabels,
                'urls' => [],
            ];
        }

        $reflection = CourseSessionReflection::getForUser($sessionId, $userId);
        $studentComment = trim((string) ($reflection?->student_comment ?? ''));
        $sessionRating = (int) ($reflection?->session_rating ?? 0);
        $sessionRating = $sessionRating >= 1 && $sessionRating <= 5 ? $sessionRating : null;

        return [
            'available' => true,
            'can_submit' => true,
            'has_reflection' => $sessionRating !== null || $studentComment !== '',
            'student_comment' => $studentComment,
            'session_rating' => $sessionRating,
            'rating_label' => $sessionRating !== null ? ($ratingLabels[$sessionRating] ?? null) : null,
            'rating_labels' => $ratingLabels,
            'urls' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    private function buildPreStudyContent(array $session): array
    {
        $materials = (string) ($session['pre_session_materials'] ?? '');
        $descriptionRaw = (string) ($session['description'] ?? '');
        $videos = $this->videos->videosForSession($session);

        return [
            'has_materials' => $this->api->hasHtml($materials),
            'materials_html' => $this->api->renderHtml($materials),
            'has_description' => $this->api->hasHtml($descriptionRaw) || trim($descriptionRaw) !== '',
            'description' => $this->api->plainHtml($descriptionRaw),
            'description_html' => $this->api->renderHtml($descriptionRaw),
            'videos' => $videos,
            'video_count' => count($videos),
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    private function buildPostStudyContent(array $session): array
    {
        $materials = (string) ($session['materials'] ?? '');
        $sessionSummary = (string) ($session['session_summary'] ?? '');
        $recordVideoUrl = trim((string) ($session['record_video_url'] ?? ''));
        $recordVideo = VideoEmbed::parse($recordVideoUrl);

        return [
            'has_materials' => $this->api->hasHtml($materials),
            'materials_html' => $this->api->renderHtml($materials),
            'has_teacher_summary' => $this->api->hasHtml($sessionSummary),
            'teacher_summary_html' => $this->api->renderHtml($sessionSummary),
            'record_video' => ($recordVideo['type'] ?? '') === 'youtube' && ! empty($recordVideo['src'])
                ? [
                    'type' => (string) $recordVideo['type'],
                    'src' => (string) $recordVideo['src'],
                    'url' => $recordVideoUrl,
                ]
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $preBlock
     * @param  array<string, mixed>  $postBlock
     * @param  array<string, mixed>  $krBlock
     * @param  array<string, mixed>  $reviewBlock
     * @param  array<string, mixed>  $reflectionBlock
     * @param  array<string, mixed>  $session
     * @return list<array<string, mixed>>
     */
    private function buildAvailableTabs(
        string $phase,
        array $preBlock,
        array $postBlock,
        array $krBlock,
        array $reviewBlock,
        array $reflectionBlock,
        array $session
    ): array {
        $tabs = [];

        if ($phase !== 'cancelled' && ! empty($preBlock['available'])) {
            $tabs[] = ['id' => StudentCourseNavService::TAB_PRE, 'label' => 'Trước buổi học', 'icon' => 'bi-book-half'];
        }

        if ($phase === 'ended') {
            if (! empty($postBlock['available']) || $this->hasPostStudyContent($session)) {
                $tabs[] = ['id' => StudentCourseNavService::TAB_POST, 'label' => 'Sau buổi học', 'icon' => 'bi-mortarboard'];
            }

            if (! empty($krBlock['available'])) {
                $tabs[] = ['id' => StudentCourseNavService::TAB_KNOWLEDGE_REVIEW, 'label' => 'Ôn tập kiến thức', 'icon' => 'bi-journal-check'];
            }

            if (! empty($reviewBlock['available'])) {
                $tabs[] = ['id' => 'review_quiz', 'label' => 'Kiểm tra bài cũ', 'icon' => 'bi-patch-check'];
            }

            if (! empty($reflectionBlock['available'])) {
                $tabs[] = ['id' => 'reflection', 'label' => 'Đánh giá buổi học', 'icon' => 'bi-star'];
            }
        }

        return $tabs;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function hasPostStudyContent(array $session): bool
    {
        return $this->api->hasHtml((string) ($session['materials'] ?? ''))
            || $this->api->hasHtml((string) ($session['session_summary'] ?? ''))
            || trim((string) ($session['record_video_url'] ?? '')) !== '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function sessionHasLinkedKnowledgeReview(array $row, int $courseId): bool
    {
        $programKnowledgeReviewId = (int) ($row['program_knowledge_review_id'] ?? 0);

        if ($programKnowledgeReviewId <= 0) {
            return false;
        }

        $programReview = StudyProgramKnowledgeReview::query()->find($programKnowledgeReviewId);

        if ($programReview === null) {
            return false;
        }

        $course = \App\Models\Course::query()->find($courseId);
        $programId = (int) ($course?->program_id ?? 0);
        $reviewProgramId = (int) $programReview->program_id;

        return $programId <= 0 || $reviewProgramId === $programId;
    }

    /**
     * @param  list<string>|null  $types
     * @return list<array<string, mixed>>
     */
    private function questionsForGroup(CourseSession $session, string $group, ?array $types = null): array
    {
        $session->loadMissing('questions');
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
     * @return array{correct:int,total:int,score:int}
     */
    private function computeQuizResult(array $questions, array $answerMap): array
    {
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

    /**
     * @param  array<string, mixed>  $display
     * @return array{submitted: bool, has_saved_submission: bool, practice_mode: bool, is_practice_result: bool, result: mixed, answer_map: array<int, array<string, mixed>>}
     */
    private function mapPracticeDisplay(array $display): array
    {
        return [
            'submitted' => ! empty($display['quizSubmitted']),
            'has_saved_submission' => ! empty($display['hasSavedSubmission']),
            'practice_mode' => ! empty($display['quizPracticeMode']),
            'is_practice_result' => ! empty($display['quizIsPracticeResult']),
            'result' => $display['quizResult'] ?? null,
            'answer_map' => is_array($display['quizAnswerMap'] ?? null) ? $display['quizAnswerMap'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $display
     * @return array{can_submit: bool, save_policy: string, allows_repeat: bool, will_save_on_submit: bool}
     */
    private function quizSubmitMeta(array $display, bool $hasQuestions): array
    {
        $hasSaved = ! empty($display['has_saved_submission']);

        return [
            'can_submit' => $hasQuestions,
            'save_policy' => 'first_only',
            'allows_repeat' => $hasSaved,
            'will_save_on_submit' => $hasQuestions && ! $hasSaved,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function quizTabUrls(string $basePath, string $segment): array
    {
        return [
            'submit' => $this->api->apiPath($basePath.'/'.$segment),
            'retry' => $this->api->apiPath($basePath.'/'.$segment.'/lam-lai'),
            'exit_practice' => $this->api->apiPath($basePath.'/'.$segment.'/ket-qua-da-nop'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyQuizBlock(bool $available): array
    {
        return [
            'available' => $available,
            'question_count' => 0,
            'submitted' => false,
            'has_saved_submission' => false,
            'practice_mode' => false,
            'is_practice_result' => false,
            'can_submit' => false,
            'result' => null,
            'questions' => [],
            'urls' => [],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $knowledgeReview
     * @return array<string, mixed>
     */
    private function emptyKnowledgeReviewBlock(bool $available, ?array $knowledgeReview = null): array
    {
        return [
            'available' => $available,
            'question_count' => 0,
            'submitted' => false,
            'has_saved_submission' => false,
            'practice_mode' => false,
            'is_practice_result' => false,
            'can_submit' => false,
            'needs_start' => false,
            'result' => null,
            'knowledge_review' => $knowledgeReview !== null ? [
                'id' => (int) ($knowledgeReview['id'] ?? 0),
                'title' => (string) ($knowledgeReview['title'] ?? ''),
                'duration_minutes' => isset($knowledgeReview['duration_minutes']) ? (int) $knowledgeReview['duration_minutes'] : null,
                'has_time_limit' => ! empty($knowledgeReview['has_time_limit']),
                'quiz_started' => ! empty($knowledgeReview['quiz_started']),
                'is_takeable' => ! empty($knowledgeReview['is_takeable']),
                'is_expired' => ! empty($knowledgeReview['is_expired']),
                'remaining_seconds' => (int) ($knowledgeReview['remaining_seconds'] ?? 0),
                'expires_at' => null,
                'timer_label' => (string) ($knowledgeReview['timer_label'] ?? ''),
            ] : null,
            'questions' => [],
            'urls' => [],
        ];
    }
}
