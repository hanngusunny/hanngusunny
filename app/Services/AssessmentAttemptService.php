<?php

namespace App\Services;

use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAttempt;
use Illuminate\Support\Facades\Session;

class AssessmentAttemptService
{
    public const SUBMIT_GRACE_SECONDS = 30;

    public function getDurationMinutes(array $assessment): int
    {
        return max(0, min(600, (int) ($assessment['duration_minutes'] ?? 0)));
    }

    public function hasTimeLimit(array $assessment): bool
    {
        return $this->getDurationMinutes($assessment) > 0;
    }

    public function isQuizStarted(int $assessmentId, int $userId, bool $hasTimeLimit): bool
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return false;
        }

        if (CourseAssessmentAttempt::findActive($assessmentId, $userId) !== null) {
            return true;
        }

        if ($hasTimeLimit) {
            return false;
        }

        return (bool) Session::get($this->startedKey($assessmentId, $userId));
    }

    public function startQuiz(int $assessmentId, int $userId, array $assessment, bool $resetExisting = false): bool
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return false;
        }

        if ($resetExisting) {
            $this->resetAttempt($assessmentId, $userId);
            Session::forget($this->startedKey($assessmentId, $userId));
        }

        $durationMinutes = $this->getDurationMinutes($assessment);

        if ($durationMinutes > 0) {
            return $this->ensureActiveAttempt($assessmentId, $userId, $durationMinutes) !== null;
        }

        Session::put($this->startedKey($assessmentId, $userId), (string) time());
        $this->ensureOpenAttempt($assessmentId, $userId);

        return true;
    }

    public function ensureActiveAttempt(int $assessmentId, int $userId, int $durationMinutes): ?CourseAssessmentAttempt
    {
        if ($assessmentId <= 0 || $userId <= 0 || $durationMinutes <= 0) {
            return null;
        }

        $existing = CourseAssessmentAttempt::findActive($assessmentId, $userId);

        if ($existing !== null) {
            return $existing;
        }

        $startedAt = now();

        return CourseAssessmentAttempt::query()->create([
            'course_assessment_id' => $assessmentId,
            'user_id' => $userId,
            'started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addMinutes($durationMinutes),
        ]);
    }

    public function ensureOpenAttempt(int $assessmentId, int $userId): void
    {
        if ($assessmentId <= 0 || $userId <= 0 || CourseAssessmentAttempt::findActive($assessmentId, $userId) !== null) {
            return;
        }

        CourseAssessmentAttempt::query()->create([
            'course_assessment_id' => $assessmentId,
            'user_id' => $userId,
            'started_at' => now(),
            'expires_at' => '2099-12-31 23:59:59',
        ]);
    }

    public function getExpiresTimestamp(CourseAssessmentAttempt $attempt): int
    {
        return $attempt->expires_at?->getTimestamp() ?: time();
    }

    public function getRemainingSeconds(CourseAssessmentAttempt $attempt): int
    {
        return max(0, $this->getExpiresTimestamp($attempt) - time());
    }

    public function isExpired(CourseAssessmentAttempt $attempt, bool $allowGrace = false): bool
    {
        $grace = $allowGrace ? self::SUBMIT_GRACE_SECONDS : 0;

        return time() > ($this->getExpiresTimestamp($attempt) + $grace);
    }

    public function resetAttempt(int $assessmentId, int $userId): void
    {
        CourseAssessmentAttempt::deleteActive($assessmentId, $userId);
    }

    public function clearQuizStarted(int $assessmentId, int $userId): void
    {
        Session::forget($this->startedKey($assessmentId, $userId));
        $this->resetAttempt($assessmentId, $userId);
    }

    public function shouldTreatAsTimedOut(array $assessment, bool $timedOut): bool
    {
        if ($timedOut) {
            return true;
        }

        return ! empty($assessment['quiz_started']) && ! empty($assessment['is_expired']);
    }

    public function isSubmitWindowClosed(array $assessment, bool $timedOut): bool
    {
        if (! empty($assessment['is_takeable'])) {
            return false;
        }

        return ! $this->shouldTreatAsTimedOut($assessment, $timedOut);
    }

    /**
     * @param  array<string, mixed>  $assessment
     * @return array<string, mixed>
     */
    public function enrichAssessmentTimer(array $assessment, int $userId, bool $hasSubmitted, bool $allowRetake): array
    {
        $durationMinutes = $this->getDurationMinutes($assessment);
        $assessmentId = (int) ($assessment['id'] ?? 0);
        $hasTimeLimit = $durationMinutes > 0;

        $assessment['duration_minutes'] = $hasTimeLimit ? $durationMinutes : null;
        $assessment['has_time_limit'] = $hasTimeLimit;
        $assessment['expires_ts'] = 0;
        $assessment['remaining_seconds'] = 0;
        $assessment['is_expired'] = false;
        $assessment['timer_label'] = $hasTimeLimit ? ($durationMinutes.' phút') : '';
        $assessment['quiz_started'] = $this->isQuizStarted($assessmentId, $userId, $hasTimeLimit);

        if ($hasSubmitted && ! $allowRetake) {
            $assessment['is_takeable'] = false;

            return $assessment;
        }

        if (! $assessment['quiz_started']) {
            return $assessment;
        }

        if (! $hasTimeLimit) {
            $assessment['offline_attempt_key'] = (string) (Session::get($this->startedKey($assessmentId, $userId)) ?: '');

            return $assessment;
        }

        $attempt = CourseAssessmentAttempt::findActive($assessmentId, $userId);

        if ($attempt === null) {
            $assessment['quiz_started'] = false;

            return $assessment;
        }

        $model = CourseAssessment::query()->find($assessmentId);
        $assessment['expires_ts'] = $this->getExpiresTimestamp($attempt);
        $assessment['remaining_seconds'] = $this->getRemainingSeconds($attempt);
        $assessment['is_expired'] = $this->isExpired($attempt);
        $assessment['is_takeable'] = ! $assessment['is_expired'] && ($model?->isScheduleOpen() ?? false);
        $assessment['offline_attempt_key'] = (string) (int) $assessment['expires_ts'];

        return $assessment;
    }

    /**
     * @param  array<string, mixed>  $assessment
     */
    public function validateSubmit(int $assessmentId, int $userId, array $assessment, bool $timedOut = false): ?string
    {
        $hasTimeLimit = $this->hasTimeLimit($assessment);

        if (! $this->isQuizStarted($assessmentId, $userId, $hasTimeLimit)) {
            return 'Bạn chưa bắt đầu làm bài. Vui lòng bấm «Bắt đầu làm bài» trước.';
        }

        return $this->validateSubmitTiming($assessmentId, $userId, $assessment, $timedOut);
    }

    public function validateSubmitTiming(int $assessmentId, int $userId, array $assessment, bool $timedOut = false): ?string
    {
        $model = CourseAssessment::query()->find($assessmentId);
        $scheduleOpen = $model?->isScheduleOpen() ?? false;

        if (! $timedOut && ! $scheduleOpen) {
            return 'Đã hết thời gian làm bài theo lịch khóa học. Không thể nộp bài.';
        }

        if (! $this->hasTimeLimit($assessment)) {
            return null;
        }

        $attempt = CourseAssessmentAttempt::findActive($assessmentId, $userId);

        if ($attempt === null) {
            return 'Không tìm thấy phiên làm bài. Vui lòng tải lại trang và bấm «Bắt đầu làm bài».';
        }

        if ($this->isExpired($attempt, $timedOut)) {
            return 'Đã hết thời gian làm bài. Hệ thống không nhận bài nộp muộn.';
        }

        return null;
    }

    public function clearAfterSubmit(int $assessmentId, int $userId): void
    {
        $this->clearQuizStarted($assessmentId, $userId);
    }

    private function startedKey(int $assessmentId, int $userId): string
    {
        return 'assessment_quiz_started_'.$assessmentId.'_'.$userId;
    }
}
