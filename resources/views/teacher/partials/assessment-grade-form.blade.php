@php
    $scoring = app(\App\Services\AssessmentSubmissionService::class);
    $questionId = (int) ($questionId ?? 0);
    $question = is_array($question ?? null) ? $question : [];
    $answerRow = is_array($answerRow ?? null) ? $answerRow : [];
    $gradeUrl = (string) ($gradeUrl ?? '');
    $embedFieldsOnly = ! empty($embedFieldsOnly);
    $gradeMode = (string) ($gradeMode ?? '');
    $blankCorrectMap = is_array($blankCorrectMap ?? null) ? $blankCorrectMap : [];

    if ($questionId <= 0 || ($gradeUrl === '' && ! $embedFieldsOnly)) {
        return;
    }

    $maxPoints = $scoring->questionPoints($question);
    $questionType = (string) ($question['question_type'] ?? 'choice');
    $isManualType = $scoring->needsManualGrading($questionType);
    $rawSelected = (string) ($answerRow['selected_answer'] ?? $answerRow['answer_text'] ?? '');
    $needsTeacherReview = $scoring->questionNeedsTeacherReview($question, $rawSelected);
    $isAutoScored = ! $needsTeacherReview && $scoring->isAutoScoredQuestion($question, $rawSelected);
    $teacherEdited = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';
    $isFillBlanksGrade = $gradeMode === 'fill_blanks' || $questionType === 'fill_blanks';
    $isCompositeItemGrade = $gradeMode === 'composite_items'
        || ($embedFieldsOnly && in_array($questionType, ['listening', 'phat_am'], true));
    $isGraded = $teacherEdited
        || (
            ! $needsTeacherReview
            && (
                (($answerRow['points_earned'] ?? null) !== null && $answerRow['points_earned'] !== '')
                || $isAutoScored
            )
        );
    $currentPoints = $scoring->resolveEarnedPoints($question, $answerRow);
    $currentPoints = $currentPoints !== null
        ? round(max(0, min($maxPoints, $currentPoints)), 2)
        : (! empty($answerRow['is_correct']) ? $maxPoints : 0.0);

    if ($isFillBlanksGrade && $blankCorrectMap !== []) {
        $blankCount = max(1, count($blankCorrectMap));
        $correctCount = 0;
        foreach ($blankCorrectMap as $isBlankCorrect) {
            if (! empty($isBlankCorrect)) {
                $correctCount++;
            }
        }
        $computedPoints = $scoring->wordTranslateEarnedPoints($maxPoints, $blankCount, $correctCount);
        if (! $teacherEdited) {
            $currentPoints = $computedPoints;
        }
    }

    if ($isCompositeItemGrade && isset($compositeEarnedSum)) {
        $currentPoints = round(max(0, min($maxPoints, (float) $compositeEarnedSum)), 2);
    }

    $comment = (string) ($answerRow['teacher_comment'] ?? '');
    $commentHtml = '';
    if (! ($isFillBlanksGrade && str_starts_with(ltrim($comment), '{'))) {
        $commentHtml = \App\Support\HtmlContent::has($comment)
            ? \App\Support\HtmlContent::sanitize($comment)
            : '';
    }
    $gradedAt = (string) ($answerRow['teacher_graded_at'] ?? '');
    $gradedTs = $gradedAt !== '' ? strtotime($gradedAt) : false;
    $isChoiceType = $scoring->isChoiceQuestionType($questionType);
    $blankCountLabel = count($blankCorrectMap);
    $blankPoints = $blankCountLabel > 0 ? $scoring->wordTranslateItemPoints($maxPoints, $blankCountLabel) : 0.0;
@endphp

<section class="teacher-essay-grade-edit assessment-question-grade-edit{{ $isGraded ? ' is-graded' : '' }}" aria-label="Chấm điểm câu {{ $questionId }}">
    <div class="teacher-essay-grade-edit-head">
        <div>
            <h4>
                <i class="fa-solid fa-award"></i>
                {{ $teacherEdited ? 'Chỉnh sửa điểm' : ($isAutoScored || ($embedFieldsOnly && ! $needsTeacherReview) ? 'Điểm chấm sẵn' : 'Chấm điểm') }}
            </h4>
            @if ($isCompositeItemGrade && $needsTeacherReview)
                <p class="teacher-essay-grade-edit-hint">Điểm câu = tổng điểm các câu hỏi nhỏ. Câu tự luận cần bạn chấm — hệ thống lưu khi bạn nhập điểm.</p>
            @elseif ($isCompositeItemGrade)
                <p class="teacher-essay-grade-edit-hint">Điểm câu = tổng điểm các câu hỏi nhỏ. Hệ thống lưu tự động khi bạn nhập điểm từng câu.</p>
            @elseif ($blankCountLabel > 0)
                <p class="teacher-essay-grade-edit-hint">
                    Mỗi ô đúng = <strong>{{ $scoring->formatPoints($maxPoints) }}</strong>
                    / {{ $blankCountLabel }}
                    = <strong>{{ $scoring->formatPoints($blankPoints) }}</strong> điểm.
                    Mặc định so với đáp án mẫu — tick ô Đúng để cộng vào điểm bên dưới.
                </p>
            @elseif ($isChoiceType || $isAutoScored)
                <p class="teacher-essay-grade-edit-hint">
                    Hệ thống đã chấm sẵn theo thang <strong>{{ $scoring->formatPoints($maxPoints) }}</strong> điểm (đúng = đủ điểm, sai = 0).
                    Bạn có thể sửa điểm bên dưới — hệ thống lưu tự động.
                </p>
            @else
                <p class="teacher-essay-grade-edit-hint">Thang điểm tối đa: <strong>{{ $scoring->formatPoints($maxPoints) }}</strong> điểm. Hệ thống lưu tự động khi bạn nhập điểm.</p>
            @endif
        </div>
        @if ($teacherEdited)
            <span class="teacher-essay-grade-status is-done"><i class="fa-solid fa-circle-check"></i> Đã chỉnh</span>
        @elseif ($isAutoScored || ($embedFieldsOnly && ! $needsTeacherReview))
            <span class="teacher-essay-grade-status is-done"><i class="fa-solid fa-wand-magic-sparkles"></i> Chấm sẵn</span>
        @elseif ($isGraded)
            <span class="teacher-essay-grade-status is-done"><i class="fa-solid fa-circle-check"></i> Đã chấm</span>
        @elseif ($isManualType || $needsTeacherReview)
            <span class="teacher-essay-grade-status is-pending">Chưa chấm</span>
        @else
            <span class="teacher-essay-grade-status is-done">Tự động</span>
        @endif
    </div>

    @if (! $embedFieldsOnly)
        <form method="post" action="{{ $gradeUrl }}" class="teacher-essay-grade-form assessment-question-grade-form" data-assessment-autosave>
            @csrf
            <input type="hidden" name="question_id" value="{{ $questionId }}">
            @if ((int) ($hubCourseId ?? 0) > 0)
                <input type="hidden" name="course_id" value="{{ (int) $hubCourseId }}">
                <input type="hidden" name="assessment_id" value="{{ (int) ($hubAssessmentId ?? 0) }}">
                <input type="hidden" name="user_id" value="{{ (int) ($hubUserId ?? 0) }}">
            @endif
    @endif

    <div class="assessment-question-grade-points">
        <label class="form-label" for="assessment_grade_points_{{ $questionId }}">Điểm đạt được</label>
        <div class="program-question-points-input-wrap">
            <input
                type="text"
                class="form-control program-question-points-input"
                id="assessment_grade_points_{{ $questionId }}"
                name="points_earned"
                value="{{ $scoring->formatPoints($currentPoints) }}"
                min="0"
                max="{{ $scoring->formatPoints($maxPoints) }}"
                step="0.01"
                inputmode="decimal"
                lang="en"
                autocomplete="off"
                required
                data-fill-blanks-points-input
                @if ($isCompositeItemGrade) readonly @endif
            >
            <span class="program-question-points-suffix">/ {{ $scoring->formatPoints($maxPoints) }} điểm</span>
        </div>
    </div>

    @if ($isManualType && ! $isFillBlanksGrade && ! $isCompositeItemGrade)
        <div class="teacher-essay-grade-comment">
            <label class="form-label" for="assessment_grade_comment_{{ $questionId }}">Nhận xét</label>
            <textarea
                class="form-control js-assessment-comment-editor"
                id="assessment_grade_comment_{{ $questionId }}"
                name="teacher_comment"
                rows="3"
                placeholder="Nhận xét cho học viên về câu trả lời này..."
            >{{ $commentHtml }}</textarea>
        </div>
    @endif

    <div class="teacher-essay-grade-actions">
        @if ($isGraded && $gradedTs)
            <span class="teacher-essay-grade-updated" data-autosave-status>
                <i class="fa-regular fa-clock"></i>
                Cập nhật lần cuối: {{ date('H:i d/m/Y', $gradedTs) }}
            </span>
        @else
            <span class="teacher-essay-grade-updated" data-autosave-status hidden></span>
        @endif
        <button type="submit" class="op-btn op-btn-sm" data-autosave-submit>
            <i class="fa-solid fa-floppy-disk"></i>
            <span>{{ ($isGraded || $isAutoScored || $isFillBlanksGrade || $isCompositeItemGrade) ? 'Cập nhật điểm' : 'Lưu điểm' }}</span>
        </button>
    </div>

    @if (! $embedFieldsOnly)
        </form>
    @endif
</section>
