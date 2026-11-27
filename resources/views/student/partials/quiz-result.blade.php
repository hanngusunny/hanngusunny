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
    $figmaCourseResult = ! empty($figmaCourseResult);
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

    $skippedCount = 0;
    $wrongCount = 0;
    if ($figmaCourseResult && ! empty($submitted)) {
        foreach ($quizQuestions as $q) {
            $qid = (int) ($q['id'] ?? 0);
            $ansRow = $quizAnswerMap[$qid] ?? null;
            $rawSel = trim((string) ($ansRow['selected_answer'] ?? ''));
            $rawTxt = trim((string) ($ansRow['answer_text'] ?? ''));
            if ($ansRow === null || ($rawSel === '' && $rawTxt === '')) {
                $skippedCount++;
            } elseif (empty($ansRow['is_correct'])) {
                $wrongCount++;
            }
        }
    }
@endphp

@if (empty($embeddedInSunny))
<div class="sunny-app page-session-show page-student-home sw-quiz-sunny{{ $figmaCourseResult ? ' sn-quiz-sunny-clean' : '' }}">
@endif

@if (! empty($submitted))
    @if ($figmaCourseResult)
        {{-- Bảng tổng kết KẾT QUẢ chuẩn Figma (#317:13252) --}}
        <div class="sn-result-summary-box">
            <h3 class="sn-result-summary-heading">KẾT QUẢ</h3>
            <div class="sn-result-summary-card">
                <div class="sn-result-stat-grid">
                    <div class="sn-result-stat-item">
                        <span class="sn-result-stat-ico sn-result-stat-ico--correct" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="currentColor"/>
                                <path d="M8.5 12.2L10.8 14.5L15.8 9.5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div class="sn-result-stat-copy">
                            <span>Đúng</span>
                            <strong>{{ $correct }} Câu</strong>
                        </div>
                    </div>

                    <div class="sn-result-stat-item">
                        <span class="sn-result-stat-ico sn-result-stat-ico--wrong" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="currentColor"/>
                                <path d="M9.5 9.5L14.5 14.5M14.5 9.5L9.5 14.5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="sn-result-stat-copy">
                            <span>Sai</span>
                            <strong>{{ $wrongCount }} Câu</strong>
                        </div>
                    </div>

                    <div class="sn-result-stat-item">
                        <span class="sn-result-stat-ico sn-result-stat-ico--skipped" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="currentColor"/>
                                <path d="M8.5 12H15.5" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="sn-result-stat-copy">
                            <span>Bỏ qua</span>
                            <strong>{{ $skippedCount }} Câu</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
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
    @endif

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
