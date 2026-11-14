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
use App\Support\PwaClient;
use App\Support\StudentQuizGrader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentSessionActionService
{
    public function __construct(
        private StudentSessionCatalogService $catalog,
        private StudentCourseNavService $nav,
        private StudentQuizGrader $grader,
        private StudentQuizPracticeService $practice,
        private KnowledgeReviewAttemptService $krAttempt,
        private OnlineRoomService $rooms,
        private ClassroomService $classroom,
        private SessionQuizDraftService $drafts,
    ) {
    }

    /**
     * @return array{session: CourseSession, row: array<string, mixed>, courseId: int, userId: int}|RedirectResponse
     */
    public function resolveContext(string $publicCode, int $userId): array|RedirectResponse
    {
        $session = CourseSession::query()
            ->with(['course.teacher', 'course.room', 'programSession'])
            ->where('public_code', $publicCode)
            ->first();

        if ($session === null) {
            return redirect()->to(PwaClient::homeFallback())->with('error', 'Buổi học không tồn tại.');
        }

        $courseId = (int) $session->course_id;

        if (! CourseMember::isMemberOf($courseId, $userId)) {
            return redirect()->to(PwaClient::homeFallback())->with('error', 'Bạn không thuộc khóa học này.');
        }

        $course = CourseMember::activeCourseFor($userId);

        if ($course === null || (int) $course->id !== $courseId) {
            return redirect()->to(PwaClient::homeFallback())->with('error', 'Không tìm thấy khóa học.');
        }

        if ($course->status !== 'active') {
            return redirect()->to(PwaClient::homeFallback())->with('error', 'Khóa học đã bị hủy.');
        }

        $row = $this->catalog->toStudentRow($session, $userId);
        $all = $this->catalog->forStudentCourse($courseId, $userId);
        $nearestId = $this->catalog->nearestSessionId($all);
        $row['is_viewable'] = $row['phase'] === 'ended' || ((int) $row['id'] === $nearestId && $nearestId > 0);

        return [
            'session' => $session,
            'row' => $row,
            'courseId' => $courseId,
            'userId' => $userId,
        ];
    }

    public function dashboardReturn(CourseSession $session, ?string $tab = null, string $hash = ''): string
    {
        if ($this->shouldReturnToPwa()) {
            $params = ['code' => (string) $session->public_code];

            if ($tab === StudentCourseNavService::TAB_OVERVIEW) {
                $params['muc'] = PwaCourseService::MUC_INFO;
            } elseif (is_string($tab) && $tab !== '') {
                $params['muc'] = $tab;
            }

            $url = route('pwa.courses.session', $params);

            return $hash !== '' ? $url.$hash : $url;
        }

        $level = $this->nav->resolveLevelContext($session->course, $session);
        $levelId = (int) ($level['level_id'] ?? 0);

        if ($levelId <= 0) {
            $levelId = $this->nav->resolveActiveLevelId((int) auth()->id());
        }

        $url = $this->nav->dashboardSessionUrl($levelId, (string) $session->public_code, $tab);

        return $hash !== '' ? $url.$hash : $url;
    }

    private function shouldReturnToPwa(): bool
    {
        return PwaClient::active();
    }

    public function join(string $publicCode, int $userId): RedirectResponse
    {
        $context = $this->resolveContext($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        ['session' => $session, 'row' => $row] = $context;

        if (empty($row['is_viewable'])) {
            return redirect()->to($this->dashboardReturn($session))
                ->with('error', 'Buổi học này chưa mở. Bạn chỉ tham gia buổi học gần nhất.');
        }

        if (empty($row['is_joinable'])) {
            return redirect()->to($this->dashboardReturn($session, StudentCourseNavService::TAB_OVERVIEW))
                ->with('error', 'Buổi học chưa đến giờ hoặc đã kết thúc.');
        }

        $attendance = CourseSessionAttendance::markPresent((int) $session->id, $userId);
        $message = ! empty($row['waiting_teacher'])
            ? 'Đã vào phòng học. Giáo viên chưa vào lớp — vui lòng đợi giáo viên.'
            : 'Đã vào lớp học.';

        if ($attendance['first']) {
            $message = 'Đã điểm danh thành công. Chào mừng bạn vào lớp!';
        } elseif (! empty($attendance['joined_at'])) {
            $joined = strtotime((string) $attendance['joined_at']);
            $message = 'Bạn đã điểm danh lúc '.date('H:i d/m/Y', $joined ?: time()).'.';
        }

        $course = $session->course;

        if ($course === null) {
            return redirect()->to($this->dashboardReturn($session, StudentCourseNavService::TAB_OVERVIEW))
                ->with('error', 'Không thể mở phòng học. Vui lòng thử lại.');
        }

        try {
            $room = $this->rooms->ensure($course);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->to($this->dashboardReturn($session, StudentCourseNavService::TAB_OVERVIEW))
                ->with('error', 'Không thể mở phòng học. Vui lòng thử lại.');
        }

        if ($room === null) {
            return redirect()->to($this->dashboardReturn($session, StudentCourseNavService::TAB_OVERVIEW))
                ->with('error', 'Không thể mở phòng học. Vui lòng thử lại.');
        }

        return redirect()->to($this->classroom->lobbyUrl((int) $room->id))->with('success', $message);
    }

    public function submitPreQuiz(string $publicCode, int $userId, mixed $answersRaw): RedirectResponse
    {
        return $this->submitSessionQuiz($publicCode, $userId, $answersRaw, 'pre');
    }

    public function submitPostQuiz(string $publicCode, int $userId, mixed $answersRaw): RedirectResponse
    {
        return $this->submitSessionQuiz($publicCode, $userId, $answersRaw, 'post');
    }

    public function enterPrePractice(string $publicCode, int $userId): RedirectResponse
    {
        return $this->enterSessionPractice($publicCode, $userId, 'pre');
    }

    public function enterPostPractice(string $publicCode, int $userId): RedirectResponse
    {
        return $this->enterSessionPractice($publicCode, $userId, 'post');
    }

    public function exitPrePractice(string $publicCode, int $userId): RedirectResponse
    {
        return $this->exitSessionPractice($publicCode, $userId, 'pre');
    }

    public function exitPostPractice(string $publicCode, int $userId): RedirectResponse
    {
        return $this->exitSessionPractice($publicCode, $userId, 'post');
    }

    public function saveReflection(string $publicCode, int $userId, string $comment, mixed $ratingRaw): RedirectResponse
    {
        $context = $this->resolveContext($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        ['session' => $session, 'row' => $row] = $context;

        if (($row['phase'] ?? '') !== 'ended') {
            return redirect()->to($this->dashboardReturn($session, StudentCourseNavService::TAB_POST))
                ->with('error', 'Chỉ ghi nhận xét sau khi buổi học đã kết thúc.');
        }

        $rating = is_numeric($ratingRaw) ? (int) $ratingRaw : null;

        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            $rating = null;
        }

        CourseSessionReflection::upsertComment((int) $session->id, $userId, $comment, $rating);

        return redirect()->to($this->dashboardReturn($session, StudentCourseNavService::TAB_POST, '#session-reflection'))
            ->with('success', ($comment !== '' || $rating !== null) ? 'Đã lưu đánh giá buổi học.' : 'Đã cập nhật đánh giá buổi học.');
    }

    public function startKnowledgeReview(string $publicCode, int $userId): RedirectResponse
    {
        $context = $this->resolveKnowledgeReview($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $practiceMode = $this->practice->isPracticeMode(
            StudentQuizPracticeService::TYPE_KNOWLEDGE,
            $context['courseKrId'],
            $userId
        );

        $this->krAttempt->startQuiz($context['courseKrId'], $userId, $practiceMode, $context['review']);

        return redirect()->to($this->dashboardReturn($context['session'], StudentCourseNavService::TAB_KNOWLEDGE_REVIEW, '#session-knowledge-review'))
            ->with('success', 'Đã bắt đầu làm bài ôn tập kiến thức.');
    }

    public function submitKnowledgeReview(string $publicCode, int $userId, mixed $answersRaw, bool $timedOut = false): RedirectResponse
    {
        $context = $this->resolveKnowledgeReview($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $redirectUrl = $this->dashboardReturn($context['session'], StudentCourseNavService::TAB_KNOWLEDGE_REVIEW, '#session-knowledge-review');
        $hasSubmitted = CourseKnowledgeReviewAnswer::hasSubmitted($context['courseKrId'], $userId, $context['questions']);
        $isPracticeMode = $this->practice->isPracticeMode(StudentQuizPracticeService::TYPE_KNOWLEDGE, $context['courseKrId'], $userId);
        $review = $this->krAttempt->enrichReviewTimer($context['review'], $userId, $hasSubmitted, $isPracticeMode);

        if (empty($review['is_takeable']) && ! $timedOut) {
            if ($hasSubmitted && ! $isPracticeMode) {
                return redirect()->to($redirectUrl)
                    ->with('error', 'Bạn đã nộp bài rồi. Bấm «Làm lại» để luyện tập thêm — điểm luyện tập không thay thế bài đã nộp.');
            }

            return redirect()->to($redirectUrl)->with('error', 'Đã hết thời gian làm bài ôn tập kiến thức.');
        }

        $timerError = $this->krAttempt->validateSubmit($context['courseKrId'], $userId, $isPracticeMode, $review, $timedOut);

        if (
            $timerError !== null
            && ! $this->krAttempt->isQuizStarted($context['courseKrId'], $userId, $isPracticeMode, $this->krAttempt->hasTimeLimit($review))
        ) {
            $this->krAttempt->startQuiz($context['courseKrId'], $userId, $isPracticeMode, $review);
            $timerError = $this->krAttempt->validateSubmit($context['courseKrId'], $userId, $isPracticeMode, $review, $timedOut);
        }

        if ($timerError !== null) {
            return redirect()->to($redirectUrl)->with('error', $timerError);
        }

        $parsed = $this->drafts->mergeIncoming(
            $this->grader->parsePostedAnswers($answersRaw),
            $this->drafts->mapForKnowledgeReview($context['courseKrId'], $userId, $context['questions']),
            $context['questions']
        );

        if ($hasSubmitted) {
            if (! $isPracticeMode) {
                return redirect()->to($redirectUrl)
                    ->with('error', 'Bạn đã nộp bài rồi. Bấm «Làm lại» để luyện tập thêm — điểm luyện tập không thay thế bài đã nộp.');
            }

            $eval = $this->grader->evaluate($parsed, $context['questions']);
            $this->practice->storePracticeResult(
                StudentQuizPracticeService::TYPE_KNOWLEDGE,
                $context['courseKrId'],
                $userId,
                ['correct' => $eval['correct'], 'total' => $eval['total'], 'score' => $eval['score']],
                $eval['answerMap']
            );
            $this->krAttempt->clearAfterSubmit($context['courseKrId'], $userId, true, $review);

            return redirect()->to($redirectUrl)
                ->with('info', 'Kết quả luyện tập: '.$eval['correct'].'/'.$eval['total'].' câu đúng ('.$eval['score'].'%). Không thay thế bài đã nộp.');
        }

        $result = $this->grader->persistKnowledgeReviewAnswers($context['courseKrId'], $userId, $parsed, $context['questions']);
        $this->drafts->clearKnowledgeReview($context['courseKrId'], $userId);
        $this->krAttempt->clearAfterSubmit($context['courseKrId'], $userId, false, $review);
        $this->practice->clearPracticeResult(StudentQuizPracticeService::TYPE_KNOWLEDGE, $context['courseKrId'], $userId);

        return redirect()->to($redirectUrl)
            ->with('success', 'Đã nộp bài ôn: '.$result['correct'].'/'.$result['total'].' câu đúng ('.$result['score'].'%).');
    }

    public function enterKnowledgeReviewPractice(string $publicCode, int $userId): RedirectResponse
    {
        $context = $this->resolveKnowledgeReview($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        if (! CourseKnowledgeReviewAnswer::hasSubmitted($context['courseKrId'], $userId, $context['questions'])) {
            return redirect()->to($this->dashboardReturn($context['session'], StudentCourseNavService::TAB_KNOWLEDGE_REVIEW))
                ->with('error', 'Bạn cần nộp bài chính thức trước khi luyện tập lại.');
        }

        $this->practice->enterPracticeMode(StudentQuizPracticeService::TYPE_KNOWLEDGE, $context['courseKrId'], $userId);
        $this->krAttempt->startQuiz($context['courseKrId'], $userId, true, $context['review']);

        return redirect()->to($this->dashboardReturn($context['session'], StudentCourseNavService::TAB_KNOWLEDGE_REVIEW, '#session-knowledge-review'))
            ->with('success', 'Chế độ luyện tập — câu trả lời được lưu tự động, không thay thế bài đã nộp.');
    }

    public function exitKnowledgeReviewPractice(string $publicCode, int $userId): RedirectResponse
    {
        $context = $this->resolveKnowledgeReview($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $this->practice->clearPracticeResult(StudentQuizPracticeService::TYPE_KNOWLEDGE, $context['courseKrId'], $userId);
        $this->krAttempt->clearAfterSubmit($context['courseKrId'], $userId, true, $context['review']);

        return redirect()->to($this->dashboardReturn($context['session'], StudentCourseNavService::TAB_KNOWLEDGE_REVIEW, '#session-knowledge-review'));
    }

    public function autosavePreQuiz(string $publicCode, int $userId, Request $request): JsonResponse
    {
        return $this->autosaveSessionQuiz($publicCode, $userId, $request, 'pre');
    }

    public function autosavePostQuiz(string $publicCode, int $userId, Request $request): JsonResponse
    {
        return $this->autosaveSessionQuiz($publicCode, $userId, $request, 'post');
    }

    public function autosaveKnowledgeReview(string $publicCode, int $userId, Request $request): JsonResponse
    {
        $fail = $this->autosaveFail(...);
        $context = $this->resolveKnowledgeReview($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $fail('Không thể lưu câu trả lời.', 422);
        }

        $isPracticeMode = $this->practice->isPracticeMode(
            StudentQuizPracticeService::TYPE_KNOWLEDGE,
            $context['courseKrId'],
            $userId
        );
        $hasSubmitted = CourseKnowledgeReviewAnswer::hasSubmitted($context['courseKrId'], $userId, $context['questions']);

        if ($hasSubmitted && ! $isPracticeMode) {
            return $fail('Bạn đã nộp bài ôn tập này.');
        }

        $review = $this->krAttempt->enrichReviewTimer($context['review'], $userId, $hasSubmitted, $isPracticeMode);

        if (empty($review['is_takeable'])) {
            return $fail('Không thể lưu — bài ôn chưa mở hoặc đã hết giờ.');
        }

        if (! $this->krAttempt->isQuizStarted(
            $context['courseKrId'],
            $userId,
            $isPracticeMode,
            $this->krAttempt->hasTimeLimit($review)
        )) {
            return $fail('Bạn chưa bắt đầu làm bài.');
        }

        [$questionId, $value] = $this->autosavePayload($request, $context['questions']);

        if ($questionId <= 0 || $value === '') {
            return $fail('Chưa có nội dung để lưu.');
        }

        if (! $this->drafts->saveKnowledgeQuestion($context['courseKrId'], $userId, $questionId, $value, $context['questions'])) {
            return $fail('Không lưu được câu trả lời.');
        }

        return response()->json(['ok' => true, 'message' => 'Đã lưu', 'question_id' => $questionId]);
    }

    private function autosaveSessionQuiz(string $publicCode, int $userId, Request $request, string $kind): JsonResponse
    {
        $fail = $this->autosaveFail(...);
        $context = $this->resolveContext($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $fail('Không thể lưu câu trả lời.', 422);
        }

        ['session' => $session, 'row' => $row] = $context;
        $type = $kind === 'post' ? StudentQuizPracticeService::TYPE_POST : StudentQuizPracticeService::TYPE_PRE;
        $group = $kind === 'post' ? 'post_practice' : 'pre';

        if ($kind === 'post' && ($row['phase'] ?? '') !== 'ended') {
            return $fail('Chỉ làm bài ôn tập sau khi buổi học đã kết thúc.');
        }

        if ($kind === 'pre' && ($row['phase'] ?? '') === 'cancelled') {
            return $fail('Buổi học đã hủy — không thể lưu bài.');
        }

        $isPracticeMode = $this->practice->isPracticeMode($type, (int) $session->id, $userId);

        $questions = $this->grader->readyQuestions(
            CourseSessionQuestion::query()
                ->where('course_session_id', $session->id)
                ->where('question_group', $group)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );

        if ($questions === []) {
            return $fail('Chưa có câu hỏi để lưu.');
        }

        $questionIds = array_map(static fn (array $q): int => (int) $q['id'], $questions);

        if (
            ! $isPracticeMode
            && CourseSessionQuizAnswer::hasSubmittedForQuestionIds((int) $session->id, $userId, $questionIds)
        ) {
            return $fail('Bạn đã nộp bài này.');
        }

        [$questionId, $value] = $this->autosavePayload($request, $questions);

        if ($questionId <= 0 || $value === '') {
            return $fail('Chưa có nội dung để lưu.');
        }

        if (! $this->drafts->saveSessionQuestion((int) $session->id, $userId, $questionId, $value, $questions)) {
            return $fail('Không lưu được câu trả lời.');
        }

        return response()->json(['ok' => true, 'message' => 'Đã lưu', 'question_id' => $questionId]);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array{0: int, 1: string}
     */
    private function autosavePayload(Request $request, array $questions): array
    {
        $parsed = $this->grader->parsePostedAnswers($request->input('answers'));
        $questionId = (int) ($request->input('question_id') ?: 0);

        if ($questionId <= 0 && count($parsed) === 1) {
            $questionId = (int) array_key_first($parsed);
        }

        $value = trim((string) ($parsed[$questionId] ?? ''));

        if ($questionId > 0 && $value === '') {
            $value = trim((string) $request->input('answers.'.$questionId, ''));
        }

        if (! $this->drafts->hasQuestion($questions, $questionId)) {
            return [0, ''];
        }

        return [$questionId, $value];
    }

    private function autosaveFail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $message], $status);
    }

    private function submitSessionQuiz(string $publicCode, int $userId, mixed $answersRaw, string $kind): RedirectResponse
    {
        $context = $this->resolveContext($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        ['session' => $session, 'row' => $row] = $context;
        $tab = $kind === 'post' ? StudentCourseNavService::TAB_POST : StudentCourseNavService::TAB_PRE;
        $hash = $kind === 'post' ? '#session-quiz' : '#session-pre-quiz';
        $type = $kind === 'post' ? StudentQuizPracticeService::TYPE_POST : StudentQuizPracticeService::TYPE_PRE;
        $group = $kind === 'post' ? 'post_practice' : 'pre';

        if ($kind === 'post' && ($row['phase'] ?? '') !== 'ended') {
            return redirect()->to($this->dashboardReturn($session, $tab))
                ->with('error', 'Chỉ làm bài ôn tập sau khi buổi học đã kết thúc.');
        }

        if ($kind === 'pre' && ($row['phase'] ?? '') === 'cancelled') {
            return redirect()->to($this->dashboardReturn($session, $tab))
                ->with('error', 'Buổi học đã hủy — không thể nộp bài.');
        }

        $questions = $this->grader->readyQuestions(
            CourseSessionQuestion::query()
                ->where('course_session_id', $session->id)
                ->where('question_group', $group)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );

        if ($questions === []) {
            return redirect()->to($this->dashboardReturn($session, $tab))
                ->with('error', $kind === 'post' ? 'Buổi học chưa có câu hỏi ôn tập sau buổi.' : 'Buổi học chưa có bài tập trước buổi.');
        }

        $questionIds = array_map(static fn (array $q): int => (int) $q['id'], $questions);
        $hasSubmitted = CourseSessionQuizAnswer::hasSubmittedForQuestionIds((int) $session->id, $userId, $questionIds);
        $parsed = $this->drafts->mergeIncoming(
            $this->grader->parsePostedAnswers($answersRaw),
            $this->drafts->mapForSession((int) $session->id, $userId, $questions),
            $questions
        );
        $redirectUrl = $this->dashboardReturn($session, $tab, $hash);

        if ($hasSubmitted) {
            if (! $this->practice->isPracticeMode($type, (int) $session->id, $userId)) {
                return redirect()->to($redirectUrl)
                    ->with('error', 'Bạn đã nộp bài rồi. Bấm «Làm lại» để luyện tập thêm — điểm luyện tập không thay thế bài đã nộp.');
            }

            $eval = $this->grader->evaluate($parsed, $questions);
            $this->practice->storePracticeResult(
                $type,
                (int) $session->id,
                $userId,
                ['correct' => $eval['correct'], 'total' => $eval['total'], 'score' => $eval['score']],
                $eval['answerMap']
            );

            return redirect()->to($redirectUrl)
                ->with('info', 'Kết quả luyện tập: '.$eval['correct'].'/'.$eval['total'].' câu đúng ('.$eval['score'].'%). Không thay thế bài đã nộp.');
        }

        $result = $this->grader->persistSessionAnswers((int) $session->id, $userId, $parsed, $questions);
        $this->drafts->clearSession((int) $session->id, $userId, $questions);
        $this->practice->clearPracticeResult($type, (int) $session->id, $userId);

        return redirect()->to($redirectUrl)
            ->with('success', ($kind === 'post' ? 'Đã nộp bài ôn tập: ' : 'Đã nộp bài trước buổi: ').$result['correct'].'/'.$result['total'].' câu đúng ('.$result['score'].'%).');
    }

    private function enterSessionPractice(string $publicCode, int $userId, string $kind): RedirectResponse
    {
        $context = $this->resolveContext($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        ['session' => $session] = $context;
        $tab = $kind === 'post' ? StudentCourseNavService::TAB_POST : StudentCourseNavService::TAB_PRE;
        $type = $kind === 'post' ? StudentQuizPracticeService::TYPE_POST : StudentQuizPracticeService::TYPE_PRE;
        $group = $kind === 'post' ? 'post_practice' : 'pre';
        $questions = $this->grader->readyQuestions(
            CourseSessionQuestion::query()
                ->where('course_session_id', $session->id)
                ->where('question_group', $group)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );
        $questionIds = array_map(static fn (array $q): int => (int) $q['id'], $questions);

        if (! CourseSessionQuizAnswer::hasSubmittedForQuestionIds((int) $session->id, $userId, $questionIds)) {
            return redirect()->to($this->dashboardReturn($session, $tab))
                ->with('error', 'Bạn cần nộp bài chính thức trước khi luyện tập lại.');
        }

        $this->practice->enterPracticeMode($type, (int) $session->id, $userId);

        return redirect()->to($this->dashboardReturn($session, $tab, $kind === 'post' ? '#session-quiz' : '#session-pre-quiz'))
            ->with('success', 'Chế độ luyện tập — câu trả lời được lưu tự động, không thay thế bài đã nộp.');
    }

    private function exitSessionPractice(string $publicCode, int $userId, string $kind): RedirectResponse
    {
        $context = $this->resolveContext($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        ['session' => $session] = $context;
        $tab = $kind === 'post' ? StudentCourseNavService::TAB_POST : StudentCourseNavService::TAB_PRE;
        $type = $kind === 'post' ? StudentQuizPracticeService::TYPE_POST : StudentQuizPracticeService::TYPE_PRE;
        $this->practice->clearPracticeResult($type, (int) $session->id, $userId);

        return redirect()->to($this->dashboardReturn($session, $tab, $kind === 'post' ? '#session-quiz' : '#session-pre-quiz'));
    }

    /**
     * @return array{session: CourseSession, review: array<string, mixed>, questions: list<array<string, mixed>>, courseKrId: int}|RedirectResponse
     */
    private function resolveKnowledgeReview(string $publicCode, int $userId): array|RedirectResponse
    {
        $context = $this->resolveContext($publicCode, $userId);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        ['session' => $session, 'row' => $row, 'courseId' => $courseId] = $context;

        if (($row['phase'] ?? '') !== 'ended') {
            return redirect()->to($this->dashboardReturn($session))
                ->with('error', 'Chỉ làm bài ôn sau khi buổi học đã kết thúc.');
        }

        $programKrId = (int) $session->program_knowledge_review_id;
        $review = CourseKnowledgeReview::findForCourseByProgramReview($courseId, $programKrId);

        if ($review === null) {
            return redirect()->to($this->dashboardReturn($session, StudentCourseNavService::TAB_POST))
                ->with('error', 'Buổi học chưa gắn bài ôn tập kiến thức.');
        }

        $questions = $this->grader->readyQuestions(
            CourseKnowledgeReviewQuestion::query()
                ->where('course_knowledge_review_id', $review->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );

        if ($questions === []) {
            return redirect()->to($this->dashboardReturn($session, StudentCourseNavService::TAB_KNOWLEDGE_REVIEW))
                ->with('error', 'Bài ôn chưa có câu hỏi.');
        }

        return [
            'session' => $session,
            'review' => $review->toArray(),
            'questions' => $questions,
            'courseKrId' => (int) $review->id,
        ];
    }
}
