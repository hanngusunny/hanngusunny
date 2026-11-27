<?php

namespace App\Services;

use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Support\PwaClient;
use Illuminate\Http\RedirectResponse;

class StudentCoursePageService
{
    public function __construct(
        private StudentCourseNavService $nav,
        private StudentSessionCatalogService $catalog,
        private StudentWorkspaceService $workspace,
        private StudentAssessmentService $assessments,
        private KnowledgeReviewFlowService $krFlow,
        private StudentQuizPracticeService $practice,
    ) {
    }

    /**
     * @return RedirectResponse|array<string, mixed>
     */
    public function buildDashboard(int $userId, int $requestedLevelId, ?string $requestedBuoi, mixed $tabParam): RedirectResponse|array
    {
        $page = $this->resolveCourseLevel($userId, $requestedLevelId);

        if ($page instanceof RedirectResponse) {
            return $page;
        }

        if ($this->nav->isAssessmentsTab($tabParam)) {
            return redirect()->to($this->nav->assessmentsPageUrl(
                $page['activeLevelId'],
                $requestedBuoi !== null && $requestedBuoi !== '' ? $requestedBuoi : null
            ));
        }

        if ($this->nav->isVideoLibraryTab($tabParam)) {
            return redirect()->to($this->nav->videoLibraryUrl());
        }

        if ($this->nav->isEvaluationsTab($tabParam)) {
            return redirect()->to($this->nav->evaluationsUrl());
        }

        if ($requestedLevelId <= 0) {
            return redirect()->route('dashboard', ['trinh-do' => $page['activeLevelId']]);
        }

        $workspace = $this->buildWorkspace($page, $requestedBuoi, $tabParam, $userId);

        if (
            ($requestedBuoi === null || $requestedBuoi === '')
            && ($workspace['selectedPublicCode'] ?? null) !== null
            && $workspace['selectedPublicCode'] !== ''
        ) {
            $redirectTab = is_string($tabParam) && trim($tabParam) !== ''
                ? trim($tabParam)
                : StudentCourseNavService::TAB_PRE;

            if (! is_string($tabParam) || trim($tabParam) === '') {
                foreach ($workspace['sessions'] as $row) {
                    if ((string) ($row['public_code'] ?? '') !== (string) $workspace['selectedPublicCode']) {
                        continue;
                    }

                    $redirectTab = $this->nav->defaultTabForSession($row);
                    break;
                }
            }

            return redirect()->to($this->nav->dashboardSessionUrl(
                $page['activeLevelId'],
                (string) $workspace['selectedPublicCode'],
                $redirectTab
            ));
        }

        $krRedirect = $this->maybeAutoSubmitKnowledgeReview($workspace, $page['activeLevelId'], $userId);

        if ($krRedirect !== null) {
            return $krRedirect;
        }

        return $workspace;
    }

    /**
     * @return RedirectResponse|array<string, mixed>
     */
    public function buildAssessmentHub(int $userId, int $requestedLevelId, ?string $requestedBuoi): RedirectResponse|array
    {
        $page = $this->resolveCourseLevel($userId, $requestedLevelId);

        if ($page instanceof RedirectResponse) {
            return $page;
        }

        if ($requestedLevelId <= 0) {
            return redirect()->to($this->nav->assessmentsPageUrl(
                $page['activeLevelId'],
                $requestedBuoi !== null && $requestedBuoi !== '' ? $requestedBuoi : null
            ));
        }

        $this->nav->rememberActiveContext((int) $page['course']->id, $page['activeLevelId']);

        return [
            'course' => $page['course'],
            'activeLevelId' => $page['activeLevelId'],
            'activeLevel' => $page['activeLevel'],
            'assessments' => $this->assessments->listForStudentCourse((int) $page['course']->id, $userId),
        ];
    }

    /**
     * @return RedirectResponse|array{course: \App\Models\Course, activeLevelId: int, activeLevel: \App\Models\StudyProgramLevel}
     */
    private function resolveCourseLevel(int $userId, int $requestedLevelId): RedirectResponse|array
    {
        $activeLevelId = $this->nav->resolveActiveLevelId($userId, $requestedLevelId);

        if ($activeLevelId <= 0) {
            return redirect()->to(PwaClient::homeFallback())->with('error', 'Bạn chưa được thêm vào khóa học nào.');
        }

        $course = CourseMember::activeCourseFor($userId);

        if ($course === null) {
            return redirect()->to(PwaClient::homeFallback())->with('error', 'Bạn chưa được thêm vào khóa học nào.');
        }

        $activeLevel = $this->catalog->findLevel((int) $course->program_id, $activeLevelId);

        if ($activeLevel === null) {
            return redirect()->to(PwaClient::homeFallback())->with('error', 'Không tìm thấy trình độ học.');
        }

        return [
            'course' => $course,
            'activeLevelId' => $activeLevelId,
            'activeLevel' => $activeLevel,
        ];
    }

    /**
     * @param  array{course: \App\Models\Course, activeLevelId: int, activeLevel: \App\Models\StudyProgramLevel}  $page
     * @return array<string, mixed>
     */
    private function buildWorkspace(array $page, ?string $requestedBuoi, mixed $tabParam, int $userId): array
    {
        $course = $page['course'];
        $activeLevelId = $page['activeLevelId'];
        $allSessions = $this->catalog->forStudentCourse((int) $course->id, $userId);
        $sessions = $this->catalog->forProgramLevel($allSessions, $activeLevelId);
        $nearestSessionId = $this->catalog->nearestSessionId($sessions);
        $this->nav->rememberActiveContext((int) $course->id, $activeLevelId);

        $panel = $this->workspace->resolveSessionPanel(
            $sessions,
            $nearestSessionId,
            $requestedBuoi !== null && $requestedBuoi !== '' ? $requestedBuoi : null,
            $userId,
            $tabParam
        );

        return [
            'course' => $course,
            'sessions' => $sessions,
            'nearestSessionId' => $nearestSessionId,
            'phaseLabels' => CourseSession::phaseLabels(),
            'activeLevelId' => $activeLevelId,
            'activeLevel' => $page['activeLevel'],
            'navService' => $this->nav,
            'selectedPublicCode' => $panel['selectedPublicCode'],
            'sessionDetail' => $panel['sessionDetail'],
            'sessionLockedMessage' => $panel['sessionLockedMessage'],
            'workspaceTab' => $panel['workspaceTab'],
            'contentTab' => $panel['contentTab'],
            'hasSessionKnowledgeReview' => $panel['hasSessionKnowledgeReview'],
        ];
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function maybeAutoSubmitKnowledgeReview(array $workspace, int $activeLevelId, int $userId): ?RedirectResponse
    {
        $sessionDetail = $workspace['sessionDetail'] ?? null;

        if (
            $sessionDetail === null
            || ($workspace['contentTab'] ?? '') !== StudentCourseNavService::TAB_KNOWLEDGE_REVIEW
        ) {
            return null;
        }

        $knowledgeReview = is_array($sessionDetail['knowledgeReview'] ?? null)
            ? $sessionDetail['knowledgeReview']
            : null;
        $krQuestions = is_array($sessionDetail['knowledgeReviewQuestions'] ?? null)
            ? $sessionDetail['knowledgeReviewQuestions']
            : [];

        if ($knowledgeReview === null || $krQuestions === []) {
            return null;
        }

        $courseKrId = (int) ($knowledgeReview['id'] ?? 0);
        $krPracticeMode = $this->practice->isPracticeMode(
            StudentQuizPracticeService::TYPE_KNOWLEDGE,
            $courseKrId,
            $userId
        );

        if (! $this->krFlow->autoSubmitExpiredIfNeeded(
            $knowledgeReview,
            $courseKrId,
            $userId,
            $krQuestions,
            (bool) ($sessionDetail['knowledgeReviewSubmitted'] ?? false),
            $krPracticeMode
        )) {
            return null;
        }

        $publicCode = trim((string) ($sessionDetail['session']['public_code'] ?? $workspace['selectedPublicCode'] ?? ''));

        return redirect()->to(
            $this->nav->dashboardSessionUrl($activeLevelId, $publicCode, StudentCourseNavService::TAB_KNOWLEDGE_REVIEW).'#session-knowledge-review'
        )->with('info', 'Đã hết thời gian làm bài — hệ thống đã tự nộp bài.');
    }
}
