<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseKnowledgeReview;
use App\Models\CourseKnowledgeReviewAnswer;
use App\Models\CourseSessionQuestion;
use App\Models\CourseSessionQuizAnswer;
use Illuminate\Support\Facades\Schema;

class StudentSessionReminderService
{
    public const TYPE_HOMEWORK = 'homework';

    public const TYPE_REVIEW = 'review';

    public function __construct(
        private StudentCourseNavService $nav,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return list<array{sessionTitle: string, label: string, type: string, url: string}>
     */
    public function buildPendingReminders(Course $course, int $userId, array $sessions, int $limit = 6): array
    {
        if ((int) $course->id <= 0 || $userId <= 0) {
            return [];
        }

        $ended = array_values(array_filter(
            $sessions,
            static fn (array $session): bool => ($session['phase'] ?? '') === 'ended'
        ));

        usort($ended, static function (array $a, array $b): int {
            return strcmp((string) ($b['session_date'] ?? ''), (string) ($a['session_date'] ?? ''));
        });

        $reminders = [];

        foreach ($ended as $session) {
            if (count($reminders) >= $limit) {
                break;
            }

            $sessionId = (int) ($session['id'] ?? 0);
            $publicCode = trim((string) ($session['public_code'] ?? ''));

            if ($sessionId <= 0 || $publicCode === '') {
                continue;
            }

            $levelId = (int) ($session['level_id'] ?? 0);
            $sessionTitle = trim((string) ($session['title'] ?? ''));
            $sessionTitle = $sessionTitle !== '' ? $sessionTitle : 'Buổi học';

            $postQuestions = CourseSessionQuestion::forSessionGroup($sessionId, 'post_practice');
            $postIds = $postQuestions->pluck('id')->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all();

            if ($postIds !== [] && ! CourseSessionQuizAnswer::hasSubmittedForQuestionIds($sessionId, $userId, $postIds)) {
                $reminders[] = [
                    'sessionTitle' => $sessionTitle,
                    'label' => 'Bài tập về nhà',
                    'type' => self::TYPE_HOMEWORK,
                    'url' => $this->nav->dashboardSessionUrl($levelId, $publicCode, StudentCourseNavService::TAB_POST).'#session-quiz',
                ];

                if (count($reminders) >= $limit) {
                    break;
                }
            }

            $programKnowledgeReviewId = (int) ($session['program_knowledge_review_id'] ?? 0);

            if ($programKnowledgeReviewId <= 0 || ! Schema::hasTable('course_knowledge_reviews')) {
                continue;
            }

            $knowledgeReview = CourseKnowledgeReview::findForCourseByProgramReview((int) $course->id, $programKnowledgeReviewId);

            if ($knowledgeReview === null) {
                continue;
            }

            $questions = $knowledgeReview->questions()->get()->map(fn ($row) => $row->toArray())->all();

            if ($questions === [] || CourseKnowledgeReviewAnswer::hasSubmitted((int) $knowledgeReview->id, $userId, $questions)) {
                continue;
            }

            $reminders[] = [
                'sessionTitle' => $sessionTitle,
                'label' => 'Bài tập ôn tập',
                'type' => self::TYPE_REVIEW,
                'url' => $this->nav->dashboardSessionUrl($levelId, $publicCode, StudentCourseNavService::TAB_KNOWLEDGE_REVIEW).'#session-knowledge-review',
            ];
        }

        return $reminders;
    }
}
