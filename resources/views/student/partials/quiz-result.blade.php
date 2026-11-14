@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $submissionService = app(\App\Services\AssessmentSubmissionService::class);
    $answerLabels = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
    $score = (int) ($result['score'] ?? 0);
    $correct = (int) ($result['correct'] ?? 0);
    $total = (int) ($result['total'] ?? count($questions ?? []));
    $pointsEarned = (float) ($result['points_earned'] ?? 0);
    $pointsPossible = (float) ($result['points_possible'] ?? 0);
    $essayCount = (int) ($result['essay_count'] ?? 0);
    $showPoints = ! empty($showPoints);
    $embeddedInSunny = ! empty($embeddedInSunny);
    $isPracticeResult = ! empty($isPracticeResult);
    $hasSavedSubmission = ! empty($hasSavedSubmission);
    $ringOffset = 226.2 - (226.2 * $score / 100);
    $quizQuestions = [];

    foreach ($questions ?? [] as $question) {
        if (is_array($question)) {
            $quizQuestions[] = $question;
            continue;
        }

        if (is_object($question) && method_exists($question, 'toArray')) {
            $quizQuestions[] = $question->toArray();
            continue;
        }

        $quizQuestions[] = (array) $question;
    }

    $quizAnswerMap = [];
    foreach (is_array($answerMap ?? null) ? $answerMap : [] as $id => $row) {
        $quizAnswerMap[(int) $id] = is_array($row) ? $row : (array) $row;
    }
@endphp

@if (empty($embeddedInSunny))
<div class="sunny-app page-session-show page-student-home sw-quiz-sunny">
@endif

@if (! empty($submitted))
    <div class="session-quiz-scoreboard">
        <div class="session-quiz-scoreboard-ring">
            <svg viewBox="0 0 120 120" aria-hidden="true">
                <circle class="session-quiz-scoreboard-bg" cx="60" cy="60" r="48"></circle>
                <circle class="session-quiz-scoreboard-fill" cx="60" cy="60" r="48" style="stroke-dashoffset: {{ $ringOffset }}"></circle>
            </svg>
            <div class="session-quiz-scoreboard-center">
                @if ($showPoints && $pointsPossible > 0)
                    <strong>{{ $submissionService->formatPoints($pointsEarned) }}</strong>
                    <span>/ {{ $submissionService->formatPoints($pointsPossible) }}</span>
                @else
                    <strong>{{ $correct }}</strong>
                    <span>/ {{ $total }}</span>
                @endif
            </div>
        </div>
        <div class="session-quiz-scoreboard-meta">
            @include('courses._quiz_score_praise', [
                'scorePct' => $score,
                'headingTag' => 'h4',
                'titlePrefix' => $isPracticeResult ? 'Kết quả luyện tập' : (string) ($title ?? ''),
            ])
            @if ($showPoints && $pointsPossible > 0)
                <p>{{ $score }}% · {{ $correct }}/{{ $total }} câu tự động đúng{{ $essayCount > 0 ? ' · '.$essayCount.' câu chờ/đã chấm tay' : '' }}</p>
            @else
                <p>Bạn trả lời đúng <strong>{{ $correct }}</strong> trên <strong>{{ $total }}</strong> câu hỏi.</p>
            @endif
            @if (! empty($readonlyNote))
                <p class="session-quiz-scoreboard-note{{ $isPracticeResult ? ' session-quiz-scoreboard-note--practice' : '' }}">
                    <i class="bi bi-info-circle"></i> {{ $readonlyNote }}
                </p>
            @elseif ($hasSavedSubmission && ! $isPracticeResult)
                <p class="session-quiz-scoreboard-note">
                    <i class="bi bi-shield-check"></i> Đây là kết quả đã lưu trên hệ thống (lần nộp đầu tiên).
                </p>
            @endif
        </div>
    </div>

    <div class="session-quiz-results">
        @include('courses._session_quiz_question_result_list', [
            'quizQuestions' => $quizQuestions,
            'quizAnswerMap' => $quizAnswerMap,
            'richTextModel' => app(\App\Support\RichContent::class),
            'answerLabels' => $answerLabels,
            'teacherReviewMode' => false,
            'showPoints' => $showPoints,
            'answerModel' => $showPoints ? $submissionService : null,
            'useAssessmentGradeForm' => false,
        ])
    </div>
@else
    <p class="text-sm text-slate-500" style="padding:0 16px 16px">Chưa có bài nộp.</p>
@endif

@if (empty($embeddedInSunny))
</div>
@endif
