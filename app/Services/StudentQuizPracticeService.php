<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class StudentQuizPracticeService
{
    public const TYPE_PRE = 'pre';

    public const TYPE_POST = 'post';

    public const TYPE_KNOWLEDGE = 'kr';

    public function enterPracticeMode(string $type, int $scopeId, int $userId): void
    {
        if ($scopeId <= 0 || $userId <= 0) {
            return;
        }

        Session::put($this->modeKey($type, $scopeId, $userId), true);
        Session::forget($this->resultKey($type, $scopeId, $userId));
    }

    public function isPracticeMode(string $type, int $scopeId, int $userId): bool
    {
        if ($scopeId <= 0 || $userId <= 0) {
            return false;
        }

        return (bool) Session::get($this->modeKey($type, $scopeId, $userId));
    }

    /**
     * @param  array{correct:int,total:int,score:int}  $result
     * @param  array<int, array<string, mixed>>  $answerMap
     */
    public function storePracticeResult(string $type, int $scopeId, int $userId, array $result, array $answerMap): void
    {
        if ($scopeId <= 0 || $userId <= 0) {
            return;
        }

        Session::forget($this->modeKey($type, $scopeId, $userId));
        Session::put($this->resultKey($type, $scopeId, $userId), [
            'result' => $result,
            'answerMap' => $answerMap,
        ]);
    }

    /**
     * @return array{result: array{correct:int,total:int,score:int}, answerMap: array<int, array<string, mixed>>}|null
     */
    public function getPracticeResult(string $type, int $scopeId, int $userId): ?array
    {
        if ($scopeId <= 0 || $userId <= 0) {
            return null;
        }

        $payload = Session::get($this->resultKey($type, $scopeId, $userId));

        return is_array($payload) ? $payload : null;
    }

    public function clearPracticeResult(string $type, int $scopeId, int $userId): void
    {
        if ($scopeId <= 0 || $userId <= 0) {
            return;
        }

        Session::forget($this->resultKey($type, $scopeId, $userId));
        Session::forget($this->modeKey($type, $scopeId, $userId));
    }

    /**
     * @param  array<int, array<string, mixed>>  $savedAnswerMap
     * @param  array{correct:int,total:int,score:int}|null  $savedResult
     * @return array{
     *   hasSavedSubmission: bool,
     *   quizSubmitted: bool,
     *   quizAnswerMap: array<int, array<string, mixed>>,
     *   quizResult: array{correct:int,total:int,score:int}|null,
     *   quizPracticeMode: bool,
     *   quizIsPracticeResult: bool
     * }
     */
    public function resolveStudentDisplay(
        string $type,
        int $scopeId,
        int $userId,
        bool $savedSubmitted,
        array $savedAnswerMap,
        ?array $savedResult
    ): array {
        $practicePayload = $this->getPracticeResult($type, $scopeId, $userId);

        if ($practicePayload !== null) {
            return [
                'hasSavedSubmission' => $savedSubmitted,
                'quizSubmitted' => true,
                'quizAnswerMap' => is_array($practicePayload['answerMap'] ?? null) ? $practicePayload['answerMap'] : [],
                'quizResult' => is_array($practicePayload['result'] ?? null) ? $practicePayload['result'] : null,
                'quizPracticeMode' => false,
                'quizIsPracticeResult' => true,
            ];
        }

        if ($this->isPracticeMode($type, $scopeId, $userId)) {
            return [
                'hasSavedSubmission' => $savedSubmitted,
                'quizSubmitted' => false,
                'quizAnswerMap' => [],
                'quizResult' => null,
                'quizPracticeMode' => true,
                'quizIsPracticeResult' => false,
            ];
        }

        return [
            'hasSavedSubmission' => $savedSubmitted,
            'quizSubmitted' => $savedSubmitted,
            'quizAnswerMap' => $savedAnswerMap,
            'quizResult' => $savedResult,
            'quizPracticeMode' => false,
            'quizIsPracticeResult' => false,
        ];
    }

    private function modeKey(string $type, int $scopeId, int $userId): string
    {
        return 'student_quiz_practice_'.$type.'_'.$scopeId.'_'.$userId;
    }

    private function resultKey(string $type, int $scopeId, int $userId): string
    {
        return 'student_quiz_practice_result_'.$type.'_'.$scopeId.'_'.$userId;
    }
}
