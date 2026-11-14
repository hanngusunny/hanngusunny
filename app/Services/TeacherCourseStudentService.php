<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseKnowledgeReview;
use App\Models\CourseKnowledgeReviewAnswer;
use App\Models\CourseKnowledgeReviewQuestion;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\CourseSessionQuestion;
use App\Models\CourseSessionQuizAnswer;
use App\Models\CourseSessionReflection;
use App\Support\Questions\QuestionParser;
use App\Support\StudentQuizGrader;

class TeacherCourseStudentService
{
    public const TAB_OVERVIEW = 'tong-quan';

    public const TAB_PRE = 'truoc-buoi';

    public const TAB_POST = 'sau-buoi';

    public const TAB_REVIEW = 'kiem-tra';

    public function __construct(
        private StudentSessionCatalogService $catalog,
        private StudentQuizGrader $grader,
        private QuestionParser $parser,
        private TeacherGradingService $grading,
    ) {
    }

    public function normalizeTab(string $tab): string
    {
        return in_array($tab, [self::TAB_OVERVIEW, self::TAB_PRE, self::TAB_POST, self::TAB_REVIEW], true)
            ? $tab
            : self::TAB_OVERVIEW;
    }

    public function studentUrl(int $courseId, int $sessionId, int $userId, string $tab = self::TAB_OVERVIEW): string
    {
        return route('teacher.course.student', [
            'id' => $courseId,
            'sessionId' => $sessionId,
            'userId' => $userId,
            'muc' => $this->normalizeTab($tab),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveCourse(int $courseId, int $teacherId): ?Course
    {
        return Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(int $courseId, int $sessionId, int $userId, int $teacherId, string $tab = self::TAB_OVERVIEW): ?array
    {
        $course = $this->resolveCourse($courseId, $teacherId);

        if ($course === null) {
            return null;
        }

        $session = CourseSession::query()
            ->where('course_id', $courseId)
            ->where('id', $sessionId)
            ->with(['course.teacher', 'course.room', 'programSession'])
            ->first();

        if ($session === null) {
            return null;
        }

        $members = CourseMember::activeMembersForCourse($courseId);
        $member = $members->firstWhere('user_id', $userId);

        if ($member === null) {
            return null;
        }

        $row = $this->catalog->toStudentRow($session, $userId);
        $preQuestions = $this->grader->readyQuestions(CourseSessionQuestion::forSessionGroup($sessionId, 'pre'));
        $postQuestions = $this->grader->readyQuestions(CourseSessionQuestion::forSessionGroup($sessionId, 'post_practice'));
        $answerMap = CourseSessionQuizAnswer::mapForUser($sessionId, $userId);

        $preIds = array_map(static fn (array $q): int => (int) $q['id'], $preQuestions);
        $postIds = array_map(static fn (array $q): int => (int) $q['id'], $postQuestions);
        $preFullySubmitted = CourseSessionQuizAnswer::hasSubmittedForQuestionIds($sessionId, $userId, $preIds);
        $postFullySubmitted = CourseSessionQuizAnswer::hasSubmittedForQuestionIds($sessionId, $userId, $postIds);
        $preSubmitted = $this->hasAnyAnswer($answerMap, $preIds);
        $postSubmitted = $this->hasAnyAnswer($answerMap, $postIds);

        $reviewQuestions = [];
        $reviewAnswerMap = [];
        $reviewSubmitted = false;
        $reviewFullySubmitted = false;
        $reviewResult = null;
        $courseKnowledgeReviewId = 0;
        $programKrId = (int) ($session->program_knowledge_review_id ?? 0);

        if ($programKrId > 0) {
            $kr = CourseKnowledgeReview::findForCourseByProgramReview($courseId, $programKrId);

            if ($kr !== null) {
                $courseKnowledgeReviewId = (int) $kr->id;
                $reviewQuestions = $this->grader->readyQuestions(
                    CourseKnowledgeReviewQuestion::query()
                        ->where('course_knowledge_review_id', $courseKnowledgeReviewId)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get()
                );
                $reviewAnswerMap = CourseKnowledgeReviewAnswer::mapForUser($courseKnowledgeReviewId, $userId);
                $reviewFullySubmitted = CourseKnowledgeReviewAnswer::hasSubmitted($courseKnowledgeReviewId, $userId, $reviewQuestions);
                $reviewSubmitted = $this->hasAnyAnswer($reviewAnswerMap, array_map(static fn (array $q): int => (int) $q['id'], $reviewQuestions));

                if ($reviewSubmitted) {
                    $reviewResult = $this->scoreFromMap($reviewQuestions, $reviewAnswerMap);
                }
            }
        }

        $reflection = CourseSessionReflection::getForUser($sessionId, $userId);

        $sessions = [];
        $rawSessions = CourseSession::query()
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->with(['course.teacher', 'course.room', 'programSession'])
            ->inProgramOrder()
            ->get();

        foreach ($rawSessions as $index => $item) {
            $sessions[] = array_merge($this->catalog->toStudentRow($item, $userId), [
                'session_index' => $index + 1,
            ]);
        }

        return [
            'course' => $course,
            'session' => $row,
            'sessionId' => $sessionId,
            'sessions' => $sessions,
            'member' => $member,
            'userId' => $userId,
            'members' => $members,
            'sessionAttendanceMap' => CourseSessionAttendance::mapBySession($sessionId),
            'attendanceRow' => CourseSessionAttendance::query()
                ->where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->first(),
            'phaseLabels' => CourseSession::phaseLabels(),
            'display' => $row['display'] ?? $session->studentDisplay(),
            'activeTab' => $this->normalizeTab($tab),
            'preQuestions' => $preQuestions,
            'preAnswerMap' => $answerMap,
            'preSubmitted' => $preSubmitted,
            'preFullySubmitted' => $preFullySubmitted,
            'preSubmittedAt' => $this->latestAnswerAt($answerMap, $preIds),
            'preResult' => $preSubmitted ? $this->scoreFromMap($preQuestions, $answerMap) : null,
            'postQuestions' => $postQuestions,
            'postAnswerMap' => $answerMap,
            'postSubmitted' => $postSubmitted,
            'postFullySubmitted' => $postFullySubmitted,
            'postSubmittedAt' => $this->latestAnswerAt($answerMap, $postIds),
            'postResult' => $postSubmitted ? $this->scoreFromMap($postQuestions, $answerMap) : null,
            'reviewQuestions' => $reviewQuestions,
            'reviewAnswerMap' => $reviewAnswerMap,
            'reviewSubmitted' => $reviewSubmitted,
            'reviewFullySubmitted' => $reviewFullySubmitted,
            'reviewSubmittedAt' => $this->latestAnswerAt(
                $reviewAnswerMap,
                array_map(static fn (array $q): int => (int) $q['id'], $reviewQuestions)
            ),
            'reviewResult' => $reviewResult,
            'courseKnowledgeReviewId' => $courseKnowledgeReviewId,
            'reflection' => $reflection,
            'hasReflection' => $reflection !== null && (
                (int) ($reflection->session_rating ?? 0) > 0
                || trim((string) ($reflection->student_comment ?? '')) !== ''
            ),
            'hasSessionKnowledgeReview' => $programKrId > 0,
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function saveEssayReview(int $courseId, int $sessionId, int $userId, int $teacherId, array $input): array
    {
        $tab = $this->normalizeTab((string) ($input['muc'] ?? self::TAB_REVIEW));
        $payload = $this->build($courseId, $sessionId, $userId, $teacherId, $tab);

        if ($payload === null) {
            return ['ok' => false, 'message' => 'Không tìm thấy học viên hoặc buổi học.'];
        }

        $questionId = (int) ($input['question_id'] ?? 0);
        $isCorrectRaw = (string) ($input['is_correct'] ?? '');
        $comment = trim((string) ($input['teacher_comment'] ?? ''));
        $gradeMode = (string) ($input['grade_mode'] ?? '');
        $recordingPath = trim((string) ($input['teacher_recording'] ?? ''));
        $pool = match ($tab) {
            self::TAB_PRE => $payload['preQuestions'],
            self::TAB_POST => $payload['postQuestions'],
            self::TAB_REVIEW => $payload['reviewQuestions'],
            default => [],
        };

        $target = null;

        foreach ($pool as $question) {
            if ((int) ($question['id'] ?? 0) === $questionId) {
                $target = $question;
                break;
            }
        }

        if ($target === null) {
            return ['ok' => false, 'message' => 'Không tìm thấy câu cần chấm.'];
        }

        $questionType = (string) ($target['question_type'] ?? '');
        $isWordTranslate = $questionType === 'word_translate' || $gradeMode === 'word_translate';
        $isCompositeFill = $gradeMode === 'composite_fill' || $this->parser->hasCompositeFillItems($target);
        $isPhatAm = $questionType === 'phat_am';
        $allowPhatAm = $isPhatAm && $gradeMode === 'phat_am' && ! $isCompositeFill;

        if (! $isWordTranslate && ! $isCompositeFill && ! $allowPhatAm && ! $this->parser->requiresTeacherGrading($target)) {
            return ['ok' => false, 'message' => 'Không tìm thấy câu cần chấm.'];
        }

        $isReview = $tab === self::TAB_REVIEW;
        $courseKrId = (int) ($payload['courseKnowledgeReviewId'] ?? 0);

        if ($isWordTranslate) {
            $itemCount = count($this->parser->decodeWordTranslateData($target)['items'] ?? []);
            $itemGrades = $this->grading->parseWordTranslateItemGrades($input['item_grades'] ?? null, $itemCount);

            if ($itemGrades === null) {
                return ['ok' => false, 'message' => 'Vui lòng chấm Đúng/Sai cho tất cả các từ dịch.'];
            }

            $row = $isReview
                ? CourseKnowledgeReviewAnswer::findForTeacherReview($courseKrId, $userId, $questionId)
                : CourseSessionQuizAnswer::findForTeacherReview($sessionId, $userId, $questionId);

            if ($row === null || ! $row->saveTeacherWordTranslateReview($itemGrades, $itemCount)) {
                return ['ok' => false, 'message' => 'Không thể lưu chấm bài. Học viên có thể chưa nộp câu này.'];
            }

            return ['ok' => true, 'message' => 'Đã lưu chấm bài dịch từ.'];
        }

        if ($isCompositeFill) {
            $fillIndexes = $this->parser->compositeFillItemIndexes($target);
            $itemGrades = $this->grading->parseCompositeFillItemGrades($input['item_grades'] ?? null, $fillIndexes);

            if ($itemGrades === null) {
                return ['ok' => false, 'message' => 'Vui lòng chấm Đúng/Sai cho từng câu tự luận.'];
            }

            $row = $isReview
                ? CourseKnowledgeReviewAnswer::findForTeacherReview($courseKrId, $userId, $questionId)
                : CourseSessionQuizAnswer::findForTeacherReview($sessionId, $userId, $questionId);

            if ($row === null || ! $row->saveTeacherCompositeFillReview(
                $target,
                (string) ($row->selected_answer ?? ''),
                $itemGrades,
                $comment,
                $recordingPath
            )) {
                return ['ok' => false, 'message' => 'Không thể lưu chấm bài. Học viên có thể chưa nộp câu này.'];
            }

            return ['ok' => true, 'message' => 'Đã lưu chấm từng câu tự luận.'];
        }

        if ($allowPhatAm) {
            if (! in_array($isCorrectRaw, ['0', '1'], true)) {
                return ['ok' => false, 'message' => 'Vui lòng chọn Đúng hoặc Sai trước khi lưu.'];
            }

            $row = $isReview
                ? CourseKnowledgeReviewAnswer::findForTeacherReview($courseKrId, $userId, $questionId)
                : CourseSessionQuizAnswer::findForTeacherReview($sessionId, $userId, $questionId);

            if ($row === null || ! $row->saveTeacherPhatAmReview($isCorrectRaw === '1', $comment, $recordingPath)) {
                return ['ok' => false, 'message' => 'Không thể lưu chấm bài. Học viên có thể chưa nộp câu này.'];
            }

            return ['ok' => true, 'message' => 'Đã lưu chấm bài phát âm.'];
        }

        if (! in_array($isCorrectRaw, ['0', '1'], true)) {
            return ['ok' => false, 'message' => 'Vui lòng chọn Đúng hoặc Sai trước khi lưu.'];
        }

        $row = $isReview
            ? CourseKnowledgeReviewAnswer::findForTeacherReview($courseKrId, $userId, $questionId)
            : CourseSessionQuizAnswer::findForTeacherReview($sessionId, $userId, $questionId);

        if ($row === null || ! $row->saveTeacherEssayReview($isCorrectRaw === '1', $comment)) {
            return ['ok' => false, 'message' => 'Không thể lưu chấm bài. Học viên có thể chưa nộp câu này.'];
        }

        return ['ok' => true, 'message' => 'Đã lưu chấm bài tự luận.'];
    }

    /**
     * @param  list<int>  $questionIds
     * @param  array<int, array<string, mixed>>  $answerMap
     */
    private function hasAnyAnswer(array $answerMap, array $questionIds): bool
    {
        foreach ($questionIds as $questionId) {
            if (isset($answerMap[$questionId])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $answerMap
     * @param  list<int>  $questionIds
     */
    private function latestAnswerAt(array $answerMap, array $questionIds): ?string
    {
        $latest = '';

        foreach ($questionIds as $questionId) {
            $row = $answerMap[$questionId] ?? [];
            $at = (string) ($row['updated_at'] ?? $row['created_at'] ?? '');

            if ($at !== '' && ($latest === '' || $at > $latest)) {
                $latest = $at;
            }
        }

        return $latest !== '' ? $latest : null;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  array<int, array<string, mixed>>  $answerMap
     * @return array{correct: int, total: int, score: int}
     */
    private function scoreFromMap(array $questions, array $answerMap): array
    {
        $correct = 0;

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);

            if ($questionId > 0 && ! empty($answerMap[$questionId]['is_correct'])) {
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
