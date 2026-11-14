@php
    $detail = is_array($selectedDetail ?? null) ? $selectedDetail : null;
    $scoring = app(\App\Services\AssessmentSubmissionService::class);
@endphp

@if ($detail === null)
    <article class="op-panel teacher-grading-detail-card teacher-assessment-empty-card">
        <div class="teacher-assessment-empty">
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            <h2>Chọn học viên để chấm</h2>
            <p>Danh sách bên trái hiển thị các bài kiểm tra đã nộp. Dùng dropdown <strong>Khóa học</strong> phía trên để đổi lớp.</p>
        </div>
    </article>
@else
    @php
        $course = $detail['course'] ?? null;
        $assessment = $detail['assessment'] ?? null;
        $member = is_array($detail['member'] ?? null) ? $detail['member'] : [];
        $questions = $detail['questions'] ?? [];
        $answerMap = $detail['answerMap'] ?? [];
        $hasSubmitted = ! empty($detail['hasSubmitted']);
        $isDraftPreview = ! empty($detail['isDraftPreview']);
        $isRetaking = ! empty($detail['isRetaking']);
        $allowRetake = ! empty($detail['allowRetake']);
        $result = $detail['result'] ?? null;
        $submission = is_array($detail['submission'] ?? null) ? $detail['submission'] : [];
        $isGraded = ! empty($detail['isGraded']);
        $displayName = trim((string) ($member['full_name'] ?: ($member['username'] ?? 'Học viên')));
        $scorePct = ($hasSubmitted && $result !== null) ? (int) $result['score'] : 0;
        $scoreCorrect = ($hasSubmitted && $result !== null) ? (int) $result['correct'] : 0;
        $scoreTotal = ($hasSubmitted && $result !== null) ? (int) $result['total'] : 0;
        $pointsEarned = ($hasSubmitted && $result !== null) ? (float) ($result['points_earned'] ?? 0) : 0;
        $pointsPossible = ($hasSubmitted && $result !== null) ? (float) ($result['points_possible'] ?? 0) : 0;
        $pendingManual = ($hasSubmitted && $result !== null) ? (int) ($result['pending_manual'] ?? 0) : 0;
        $ringOffset = 226.2 - (226.2 * $scorePct / 100);
        $gradeUrl = (string) ($detail['gradeUrl'] ?? route('teacher.assessments.grade'));
        $publishUrl = (string) ($detail['publishUrl'] ?? route('teacher.assessments.publish'));
        $retakeGrantUrl = (string) ($detail['retakeGrantUrl'] ?? route('teacher.assessments.retake.grant'));
        $retakeRevokeUrl = (string) ($detail['retakeRevokeUrl'] ?? route('teacher.assessments.retake.revoke'));
        $hubCourseId = (int) ($course?->id ?? $courseId);
        $hubAssessmentId = (int) ($assessment?->id ?? $assessmentId);
        $hubUserId = (int) ($member['user_id'] ?? $studentId);
        $submittedAt = (string) ($submission['submitted_at'] ?? '');
        $submittedTs = $submittedAt !== '' ? strtotime($submittedAt) : false;
    @endphp

    <article class="op-panel teacher-assessment-grade-card">
        <header class="teacher-grading-detail-head teacher-assessment-grade-head">
            <div class="teacher-grading-detail-student teacher-assessment-grade-student">
                <x-user-avatar
                    class="teacher-assessment-grade-avatar"
                    :url="$member['avatar'] ?? null"
                    :name="$displayName"
                    :initials="$member['initial'] ?? null"
                />
                <div>
                    <h2>{{ $displayName }}</h2>
                    <p>
                        <span class="teacher-grading-detail-tag teacher-assessment-grade-tag">{{ $assessment?->title ?? 'Bài kiểm tra' }}</span>
                        {{ $course?->title ?? '' }}
                    </p>
                </div>
            </div>

            @if ($hasSubmitted)
                <div class="teacher-assessment-grade-actions">
                    @if (! $isGraded)
                        <form method="post" action="{{ $publishUrl }}">
                            @csrf
                            <input type="hidden" name="course_id" value="{{ $hubCourseId }}">
                            <input type="hidden" name="assessment_id" value="{{ $hubAssessmentId }}">
                            <input type="hidden" name="user_id" value="{{ $hubUserId }}">
                            <button class="op-btn" type="submit" @disabled($pendingManual > 0) @if ($pendingManual > 0) title="Còn câu chưa chấm" @endif>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Công bố kết quả</span>
                            </button>
                        </form>
                    @endif

                    @if ($allowRetake)
                        <form method="post" action="{{ $retakeRevokeUrl }}">
                            @csrf
                            <input type="hidden" name="course_id" value="{{ $hubCourseId }}">
                            <input type="hidden" name="assessment_id" value="{{ $hubAssessmentId }}">
                            <input type="hidden" name="user_id" value="{{ $hubUserId }}">
                            <button class="op-btn-ghost" type="submit">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Hủy làm lại</span>
                            </button>
                        </form>
                    @else
                        <form method="post" action="{{ $retakeGrantUrl }}">
                            @csrf
                            <input type="hidden" name="course_id" value="{{ $hubCourseId }}">
                            <input type="hidden" name="assessment_id" value="{{ $hubAssessmentId }}">
                            <input type="hidden" name="user_id" value="{{ $hubUserId }}">
                            <button class="op-btn-ghost" type="submit">
                                <i class="fa-solid fa-rotate"></i>
                                <span>Cho làm lại</span>
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </header>

        @if (! $hasSubmitted && ! $isDraftPreview)
            <div class="teacher-assessment-empty">
                <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                <p>Học viên chưa nộp bài kiểm tra này.</p>
            </div>
        @else
            <div class="teacher-assessment-grade-summary">
                @if ($isDraftPreview)
                    <span class="teacher-assessment-pending-note">
                        @if ($isRetaking)
                            Học viên đang làm lại — đây là bài mới chưa nộp. Điểm cũ chưa cập nhật cho đến khi học viên nộp.
                        @else
                            Bài lưu nháp — học viên chưa nộp. Bạn có thể xem câu đã làm, chưa chấm điểm.
                        @endif
                    </span>
                @elseif ($isRetaking)
                    <span class="teacher-assessment-pending-note">
                        Đã cho làm lại. Đang hiện bài cũ; trang sẽ tự tải lại khi học viên nộp bài mới.
                    </span>
                @elseif (! $isGraded)
                    <span class="teacher-assessment-pending-note session-post-quiz-pending">
                        {{ $pendingManual > 0 ? 'Còn '.$pendingManual.' câu chưa chấm' : 'Chờ công bố kết quả' }}
                    </span>
                @elseif ($result !== null)
                    <div class="teacher-assessment-scoreboard session-quiz-scoreboard--compact">
                        <div class="teacher-assessment-scoreboard-ring">
                            <svg viewBox="0 0 120 120" aria-hidden="true">
                                <circle class="teacher-assessment-scoreboard-bg" cx="60" cy="60" r="48"></circle>
                                <circle class="teacher-assessment-scoreboard-fill" cx="60" cy="60" r="48" style="stroke-dashoffset: {{ $ringOffset }}"></circle>
                            </svg>
                            <div class="teacher-assessment-scoreboard-center">
                                <strong>{{ $scoring->formatPoints($pointsEarned) }}</strong>
                                <span>/ {{ $scoring->formatPoints($pointsPossible) }} đ</span>
                            </div>
                        </div>
                        <div class="teacher-assessment-scoreboard-meta">
                            <h3>Tổng điểm: {{ $scoring->formatPoints($pointsEarned) }}/{{ $scoring->formatPoints($pointsPossible) }}</h3>
                            @if ($scoreTotal > 0)
                                <p>Trắc nghiệm: {{ $scoreCorrect }}/{{ $scoreTotal }} câu đúng</p>
                            @endif
                            @if ($submittedTs)
                                <p>Nộp lúc <strong>{{ date('H:i d/m/Y', $submittedTs) }}</strong></p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="teacher-assessment-grade-results">
                @include('teacher.partials.assessment-questions', [
                    'questions' => $questions,
                    'answerMap' => $answerMap,
                    'useAssessmentGradeForm' => $hasSubmitted && ! $isDraftPreview,
                    'assessmentGradeUrl' => $gradeUrl,
                    'assessmentHubCourseId' => $hubCourseId,
                    'assessmentHubAssessmentId' => $hubAssessmentId,
                    'assessmentHubUserId' => $hubUserId,
                    'teacherReviewMode' => true,
                ])
            </div>

            @if (! $isDraftPreview)
                <div
                    class="teacher-assessment-grade-live-total"
                    data-assessment-live-total
                    data-page-earned="{{ $scoring->formatPoints($pointsEarned) }}"
                    data-page-possible="{{ $scoring->formatPoints($pointsPossible) }}"
                >
                    <span><i class="fa-solid fa-calculator" aria-hidden="true"></i> Tổng điểm</span>
                    <strong>
                        <span data-live-earned>{{ $scoring->formatPoints($pointsEarned) }}</span>
                        / {{ $scoring->formatPoints($pointsPossible) }} đ
                    </strong>
                </div>
            @endif
        @endif
    </article>
@endif
