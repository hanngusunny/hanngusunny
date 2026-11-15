<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentDraftService;
use App\Services\AssessmentSubmissionService;
use App\Services\AssessmentTakePageService;
use App\Services\StudentAssessmentService;
use App\Services\StudentCourseNavService;
use App\Support\PwaClient;
use App\Support\StudentQuizGrader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentTakeController extends Controller
{
    public function __construct(
        private StudentAssessmentService $assessments,
        private AssessmentAttemptService $attempts,
        private AssessmentDraftService $drafts,
        private AssessmentSubmissionService $submissions,
        private AssessmentTakePageService $pages,
        private StudentQuizGrader $grader,
        private StudentCourseNavService $nav,
    ) {
    }

    public function show(Request $request, int $id): View|RedirectResponse
    {
        $page = $this->pages->resolve($id, (int) $request->user()->id);

        if ($page['status'] === 'missing') {
            return redirect()->to($this->listUrl($request))->with('error', $page['message'] ?? '');
        }

        if ($page['status'] === 'closed') {
            return redirect()->to($this->listUrl($request))->with('error', $page['message'] ?? '');
        }

        if ($page['status'] === 'autosubmit') {
            return redirect()->to($this->showUrl($request, $id))
                ->with('info', $page['message'] ?? '');
        }

        return view('student.assessment-show', array_merge($page['data'] ?? [], [
            'startAction' => route('student.assessment.start', $id),
            'submitAction' => route('student.assessment.submit', $id),
            'autosaveUrl' => route('student.assessment.autosave', $id),
            'hubUrl' => $this->listUrl($request),
            'navService' => $this->nav,
        ]));
    }

    public function start(Request $request, int $id): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        $context = $this->assessments->resolveForStudent($id, $userId);
        $redirectUrl = $this->showUrl($request, $id, '#assessment-quiz');

        if ($context === null) {
            return redirect()->to($this->listUrl($request))
                ->with('error', 'Không tìm thấy bài kiểm tra.');
        }

        $questions = $this->assessments->readyQuestions($id);
        $assessment = $this->assessments->enrichForStudent($context['assessment'], $userId, $questions);
        $hasSubmitted = ! empty($assessment['has_submitted']);
        $allowRetake = ! empty($assessment['allow_retake']);

        if ($hasSubmitted && ! $allowRetake) {
            return redirect()->to($redirectUrl)
                ->with('error', 'Bạn đã nộp bài kiểm tra này và chỉ được làm một lần.');
        }

        if (! $this->assessments->canStartAssessment($context['assessment'], $hasSubmitted, $allowRetake, $questions)) {
            $display = $this->assessments->studentDisplay($assessment);
            $timeHint = ! empty($display['hasSchedule']) && ! empty($display['timeLabel'])
                ? ' Bài kiểm tra chỉ mở trong khung giờ '.$display['timeLabel'].'.'
                : '';

            return redirect()->to($redirectUrl)
                ->with('error', 'Chưa tới giờ hoặc đã hết thời gian làm bài.'.$timeHint);
        }

        if ($questions === []) {
            return redirect()->to($redirectUrl)->with('error', 'Bài kiểm tra chưa có câu hỏi.');
        }

        $hasTimeLimit = $this->attempts->hasTimeLimit($assessment);

        if ($this->attempts->isQuizStarted($id, $userId, $hasTimeLimit)) {
            return redirect()->to($redirectUrl);
        }

        $resetExisting = $allowRetake && $hasSubmitted;

        if ($allowRetake) {
            $this->drafts->seedForRetake($id, $userId, $questions);
        }

        if (! $this->attempts->startQuiz($id, $userId, $assessment, $resetExisting)) {
            return redirect()->to($redirectUrl)->with('error', 'Không thể bắt đầu làm bài. Thử lại sau.');
        }

        $message = $hasTimeLimit
            ? 'Đồng hồ đã bắt đầu — bạn có '.(int) ($assessment['duration_minutes'] ?? 0).' phút để hoàn thành bài.'
            : 'Bắt đầu làm bài — chúc bạn làm tốt!';

        return redirect()->to($redirectUrl)->with('success', $message);
    }

    public function submit(Request $request, int $id): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        $context = $this->assessments->resolveForStudent($id, $userId);
        $redirectUrl = $this->showUrl($request, $id);

        if ($context === null) {
            return redirect()->to($this->listUrl($request))
                ->with('error', 'Không tìm thấy bài kiểm tra.');
        }

        $questions = $this->assessments->readyQuestions($id);
        $assessment = $this->assessments->enrichForStudent($context['assessment'], $userId, $questions);

        if (! empty($assessment['has_submitted']) && empty($assessment['allow_retake'])) {
            return redirect()->to($redirectUrl)
                ->with('error', 'Bạn đã nộp bài kiểm tra này và chỉ được làm một lần.');
        }

        $timedOut = $this->attempts->shouldTreatAsTimedOut(
            $assessment,
            $request->input('timed_out') === '1'
        );

        if ($this->attempts->isSubmitWindowClosed($assessment, $timedOut)) {
            return redirect()->to($redirectUrl)
                ->with('error', 'Không thể nộp bài — đã hết thời gian làm bài hoặc bài kiểm tra chưa mở.');
        }

        if ($questions === []) {
            return redirect()->to($redirectUrl)->with('error', 'Bài kiểm tra chưa có câu hỏi.');
        }

        $submitError = $this->attempts->validateSubmit($id, $userId, $assessment, $timedOut);

        if ($submitError !== null && ! $timedOut) {
            return redirect()->to($redirectUrl)->with('error', $submitError);
        }

        $parsed = $this->submissions->sanitizeEssayAnswers(
            $this->drafts->mergeIncomingAnswers(
                $this->grader->parsePostedAnswers($request->input('answers')),
                $questions,
                $id,
                $userId
            ),
            $questions
        );

        $wasRetake = ! empty($assessment['allow_retake']);

        try {
            $this->submissions->saveSubmission($id, $userId, $parsed, $questions, [
                'allowPartial' => true,
                'fillUnanswered' => true,
            ]);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'already_submitted') {
                return redirect()->to($redirectUrl)
                    ->with('error', 'Bạn đã nộp bài kiểm tra này và không thể làm lại.');
            }

            if ($e->getMessage() === 'empty_submission') {
                if ($timedOut) {
                    return redirect()->to($redirectUrl)
                        ->with('error', 'Đã hết thời gian làm bài nhưng chưa lưu được câu trả lời. Hãy liên hệ giáo viên.');
                }

                return redirect()->to($redirectUrl)
                    ->with('error', 'Không có câu trả lời hợp lệ để nộp. Vui lòng trả lời ít nhất một câu.');
            }

            if ($e->getMessage() === 'submission_failed') {
                return redirect()->to($redirectUrl)
                    ->with('error', 'Không thể lưu bài nộp. Vui lòng thử lại.');
            }

            throw $e;
        }

        $this->attempts->clearAfterSubmit($id, $userId);

        $message = $timedOut
            ? 'Đã hết thời gian làm bài — hệ thống đã tự nộp bài. Giáo viên sẽ chấm và công bố kết quả.'
            : ($wasRetake
                ? 'Đã nộp bài làm lại. Giáo viên sẽ chấm và công bố kết quả.'
                : 'Đã nộp bài kiểm tra. Giáo viên đã được thông báo và sẽ chấm bài sớm.');

        return redirect()->to($redirectUrl)->with('success', $message);
    }

    public function autosave(Request $request, int $id): JsonResponse
    {
        $fail = function (string $message, int $status = 422): JsonResponse {
            return response()->json([
                'ok' => false,
                'error' => $message,
            ], $status);
        };

        $userId = (int) $request->user()->id;
        $context = $this->assessments->resolveForStudent($id, $userId);

        if ($context === null) {
            return $fail('Không tìm thấy bài kiểm tra.', 404);
        }

        $questions = $this->assessments->readyQuestions($id);
        $assessment = $this->assessments->enrichForStudent($context['assessment'], $userId, $questions);

        if (! empty($assessment['has_submitted']) && empty($assessment['allow_retake'])) {
            return $fail('Bạn đã nộp bài kiểm tra này.');
        }

        if (empty($assessment['is_takeable']) && empty($assessment['quiz_started'])) {
            return $fail('Không thể lưu — bài kiểm tra chưa mở hoặc đã hết giờ.');
        }

        if (! $this->attempts->isQuizStarted($id, $userId, $this->attempts->hasTimeLimit($assessment))) {
            return $fail('Bạn chưa bắt đầu làm bài.');
        }

        $parsed = $this->submissions->sanitizeEssayAnswers(
            $this->grader->parsePostedAnswers($request->input('answers')),
            $questions
        );
        $questionId = (int) ($request->input('question_id') ?: 0);

        if ($questionId <= 0 && count($parsed) === 1) {
            $questionId = (int) array_key_first($parsed);
        }

        $value = trim((string) ($parsed[$questionId] ?? ''));

        if ($questionId <= 0 || $value === '') {
            return $fail('Chưa có nội dung để lưu.');
        }

        $knownIds = [];

        foreach ($questions as $question) {
            $knownIds[(int) ($question['id'] ?? 0)] = true;
        }

        if (empty($knownIds[$questionId])) {
            return $fail('Không tìm thấy câu hỏi.');
        }

        if (! $this->drafts->saveQuestion($id, $userId, $questionId, $value, $questions)) {
            return $fail('Không lưu được câu trả lời.');
        }

        return response()->json([
            'ok' => true,
            'message' => 'Đã lưu',
            'question_id' => $questionId,
        ]);
    }

    private function hubUrl(Request $request): string
    {
        $levelId = max(0, (int) $request->query('trinh-do', 0));

        if ($levelId <= 0) {
            $levelId = $this->nav->resolveActiveLevelId((int) $request->user()->id);
        }

        $buoi = trim((string) $request->query('buoi', ''));

        return $this->nav->assessmentsPageUrl($levelId, $buoi !== '' ? $buoi : null);
    }

    private function listUrl(Request $request): string
    {
        return $this->shouldReturnToPwa($request)
            ? route('pwa.assessments')
            : $this->hubUrl($request);
    }

    private function showUrl(Request $request, int $id, string $hash = ''): string
    {
        $url = $this->shouldReturnToPwa($request)
            ? route('pwa.assessments.show', $id)
            : route('student.assessment.show', $id);

        return $hash !== '' ? $url.$hash : $url;
    }

    private function shouldReturnToPwa(Request $request): bool
    {
        return PwaClient::active($request);
    }
}
