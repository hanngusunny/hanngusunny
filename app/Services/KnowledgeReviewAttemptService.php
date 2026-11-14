<?php

namespace App\Services;

use App\Models\CourseKnowledgeReviewAttempt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class KnowledgeReviewAttemptService
{
    public const SUBMIT_GRACE_SECONDS = 30;

    public function tableExists(): bool
    {
        return Schema::hasTable('course_knowledge_review_attempts');
    }

    public function getDurationMinutes(array $review): int
    {
        return max(0, min(600, (int) ($review['duration_minutes'] ?? 0)));
    }

    public function hasTimeLimit(array $review): bool
    {
        return $this->getDurationMinutes($review) > 0;
    }

    public function isQuizStarted(int $courseKrId, int $userId, bool $isPractice, bool $hasTimeLimit): bool
    {
        if ($courseKrId <= 0 || $userId <= 0) {
            return false;
        }

        if ($hasTimeLimit) {
            return $this->tableExists() && CourseKnowledgeReviewAttempt::findActive($courseKrId, $userId, $isPractice) !== null;
        }

        return (bool) Session::get($this->startedKey($courseKrId, $userId, $isPractice));
    }

    public function startQuiz(int $courseKrId, int $userId, bool $isPractice, array $review): bool
    {
        if ($courseKrId <= 0 || $userId <= 0) {
            return false;
        }

        $durationMinutes = $this->getDurationMinutes($review);

        if ($durationMinutes > 0) {
            return $this->ensureActiveAttempt($courseKrId, $userId, $isPractice, $durationMinutes) !== null;
        }

        Session::put($this->startedKey($courseKrId, $userId, $isPractice), true);

        return true;
    }

    public function ensureActiveAttempt(int $courseKrId, int $userId, bool $isPractice, int $durationMinutes): ?CourseKnowledgeReviewAttempt
    {
        if ($courseKrId <= 0 || $userId <= 0 || $durationMinutes <= 0 || ! $this->tableExists()) {
            return null;
        }

        $existing = CourseKnowledgeReviewAttempt::findActive($courseKrId, $userId, $isPractice);

        if ($existing !== null) {
            return $existing;
        }

        $startedAt = now();

        return CourseKnowledgeReviewAttempt::query()->create([
            'course_knowledge_review_id' => $courseKrId,
            'user_id' => $userId,
            'is_practice' => $isPractice ? 1 : 0,
            'started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addMinutes($durationMinutes),
        ]);
    }

    public function getExpiresTimestamp(CourseKnowledgeReviewAttempt $attempt): int
    {
        return $attempt->expires_at?->getTimestamp() ?: time();
    }

    public function getRemainingSeconds(CourseKnowledgeReviewAttempt $attempt): int
    {
        return max(0, $this->getExpiresTimestamp($attempt) - time());
    }

    public function isExpired(CourseKnowledgeReviewAttempt $attempt, bool $allowGrace = false): bool
    {
        $grace = $allowGrace ? self::SUBMIT_GRACE_SECONDS : 0;

        return time() > ($this->getExpiresTimestamp($attempt) + $grace);
    }

    public function resetAttempt(int $courseKrId, int $userId, bool $isPractice): void
    {
        CourseKnowledgeReviewAttempt::deleteActive($courseKrId, $userId, $isPractice);
    }

    public function clearQuizStarted(int $courseKrId, int $userId, bool $isPractice, bool $hasTimeLimit): void
    {
        Session::forget($this->startedKey($courseKrId, $userId, $isPractice));

        if ($hasTimeLimit) {
            $this->resetAttempt($courseKrId, $userId, $isPractice);
        }
    }

    /**
     * @param  array<string, mixed>  $review
     * @return array<string, mixed>
     */
    public function enrichReviewTimer(array $review, int $userId, bool $hasSubmitted, bool $isPracticeMode): array
    {
        $durationMinutes = $this->getDurationMinutes($review);
        $courseKrId = (int) ($review['id'] ?? 0);
        $hasTimeLimit = $durationMinutes > 0;

        $review['duration_minutes'] = $hasTimeLimit ? $durationMinutes : null;
        $review['has_time_limit'] = $hasTimeLimit;
        $review['expires_ts'] = 0;
        $review['remaining_seconds'] = 0;
        $review['is_expired'] = false;
        $review['timer_label'] = $hasTimeLimit ? ($durationMinutes.' phút') : '';
        $review['quiz_started'] = $this->isQuizStarted($courseKrId, $userId, $isPracticeMode, $hasTimeLimit);

        if ($hasSubmitted && ! $isPracticeMode) {
            $review['is_takeable'] = false;

            return $review;
        }

        if (! $review['quiz_started'] || ! $hasTimeLimit) {
            $review['is_takeable'] = true;

            return $review;
        }

        $attempt = CourseKnowledgeReviewAttempt::findActive($courseKrId, $userId, $isPracticeMode);

        if ($attempt === null) {
            $review['quiz_started'] = false;
            $review['is_takeable'] = true;

            return $review;
        }

        $review['expires_ts'] = $this->getExpiresTimestamp($attempt);
        $review['remaining_seconds'] = $this->getRemainingSeconds($attempt);
        $review['is_expired'] = $this->isExpired($attempt);
        $review['is_takeable'] = ! $review['is_expired'];

        return $review;
    }

    /**
     * @param  array<string, mixed>  $review
     */
    public function validateSubmit(int $courseKrId, int $userId, bool $isPracticeMode, array $review, bool $timedOut = false): ?string
    {
        $hasTimeLimit = $this->hasTimeLimit($review);

        if (! $this->isQuizStarted($courseKrId, $userId, $isPracticeMode, $hasTimeLimit)) {
            return 'Bạn chưa bắt đầu làm bài. Vui lòng bấm «Bắt đầu làm bài» trước.';
        }

        if (! $hasTimeLimit || ! $this->tableExists()) {
            return null;
        }

        $attempt = CourseKnowledgeReviewAttempt::findActive($courseKrId, $userId, $isPracticeMode);

        if ($attempt === null) {
            return 'Không tìm thấy phiên làm bài. Vui lòng tải lại trang và bấm «Bắt đầu làm bài».';
        }

        if ($this->isExpired($attempt, $timedOut)) {
            return 'Đã hết thời gian làm bài. Hệ thống không nhận bài nộp muộn.';
        }

        return null;
    }

    public function clearAfterSubmit(int $courseKrId, int $userId, bool $isPracticeMode, array $review): void
    {
        $this->clearQuizStarted($courseKrId, $userId, $isPracticeMode, $this->hasTimeLimit($review));
    }

    private function startedKey(int $courseKrId, int $userId, bool $isPractice): string
    {
        return 'kr_quiz_started_'.$courseKrId.'_'.$userId.'_'.($isPractice ? '1' : '0');
    }
}
