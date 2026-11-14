@php
    $assessmentOptions = $assessmentOptions ?? [];
    $studentItems = $studentItems ?? [];
    $selectedAssessment = is_array($selectedAssessment ?? null) ? $selectedAssessment : null;
    $gradingService = $gradingService ?? app(\App\Services\TeacherAssessmentGradingService::class);
    $scoring = app(\App\Services\AssessmentSubmissionService::class);
    $assessmentTitle = (string) ($selectedAssessment['title'] ?? 'Bài kiểm tra');
@endphp

<article class="op-panel teacher-grading-queue-card">
    <div class="teacher-assessment-students-head">
        <h2>Học viên</h2>
        <span>{{ count($studentItems) }} học viên</span>
    </div>

    @if ($assessmentOptions === [])
        <div class="teacher-assessment-empty">
            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
            <p>Chưa có bài kiểm tra nào có câu hỏi trong khóa.</p>
        </div>
    @elseif ($studentItems === [])
        <div class="teacher-assessment-empty">
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            <p>Chưa có học viên nộp bài hoặc lưu nháp <strong>{{ $assessmentTitle }}</strong>.</p>
        </div>
    @else
        <div class="teacher-assessment-student-list">
            @foreach ($studentItems as $item)
                @php
                    $uid = (int) ($item['user_id'] ?? 0);
                    $isActive = $uid === (int) $studentId;
                    $itemUrl = $gradingService->pageUrl((int) $courseId, (int) $assessmentId, $uid);
                    $submitted = trim((string) ($item['submitted_at'] ?? ''));
                    $submittedTs = $submitted !== '' ? strtotime($submitted) : false;
                    $submittedLabel = $submittedTs ? date('H:i d/m/Y', $submittedTs) : '';
                    $needsGrade = ! empty($item['needs_grade']);
                    $isDraft = ! empty($item['is_draft']);
                    $isRetaking = ! empty($item['is_retaking']);
                    $pointsPossible = (float) ($item['points_possible'] ?? 0);
                    $pointsEarned = (float) ($item['points_earned'] ?? 0);
                    $stateClass = $isDraft ? 'is-draft' : (($needsGrade || $isRetaking) ? 'is-pending' : 'is-done');
                @endphp
                <a
                    class="teacher-assessment-student-item{{ $isActive ? ' is-active' : '' }} {{ $stateClass }}"
                    href="{{ $itemUrl }}"
                    aria-current="{{ $isActive ? 'page' : 'false' }}"
                >
                    <div class="teacher-assessment-student-item-head">
                        <x-user-avatar
                            class="teacher-assessment-student-avatar"
                            :url="$item['student_avatar'] ?? null"
                            :name="$item['student_name'] ?? ''"
                            :initials="$item['student_initial'] ?? null"
                        />
                        <div class="teacher-assessment-student-item-meta">
                            <strong>{{ $item['student_name'] ?? 'Học viên' }}</strong>
                            <span>{{ $assessmentTitle }}</span>
                        </div>
                        @if ($isDraft)
                            @php
                                $draftAnswered = (int) ($item['draft_answered'] ?? 0);
                                $draftTotal = (int) ($item['draft_total'] ?? 0);
                            @endphp
                            <span class="teacher-assessment-student-badge is-draft">{{ $draftTotal > 0 ? 'Nháp '.$draftAnswered.'/'.$draftTotal : 'Nháp' }}</span>
                        @elseif ($isRetaking)
                            @php
                                $draftAnswered = (int) ($item['draft_answered'] ?? 0);
                                $draftTotal = (int) ($item['draft_total'] ?? 0);
                            @endphp
                            <span class="teacher-assessment-student-badge">{{ $draftTotal > 0 ? 'Làm lại '.$draftAnswered.'/'.$draftTotal : 'Làm lại' }}</span>
                        @elseif ($needsGrade)
                            <span class="teacher-assessment-student-badge">Chờ chấm</span>
                        @elseif (! empty($item['is_graded']) && $pointsPossible > 0)
                            <span class="teacher-assessment-student-score">{{ $scoring->formatPoints($pointsEarned) }} đ</span>
                        @else
                            <span class="teacher-assessment-student-badge is-muted">Đã nộp</span>
                        @endif
                    </div>
                    @if ($submittedLabel !== '')
                        <span class="teacher-assessment-student-time">
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                            {{ $isDraft ? 'Lưu' : ($isRetaking ? 'Làm lại' : 'Nộp') }} {{ $submittedLabel }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</article>
