@php
    $detail = is_array($detail ?? null) ? $detail : [];
    $activeTab = (string) ($activeTab ?? \App\Services\TeacherGradingService::TAB_ALL);
    $student = is_array($detail['student'] ?? null) ? $detail['student'] : [];
    $contextPills = is_array($detail['context_pills'] ?? null) ? $detail['context_pills'] : [];
    $manualQuestions = is_array($detail['manual_questions'] ?? null) ? $detail['manual_questions'] : [];
    $autoQuestions = is_array($detail['auto_questions'] ?? null) ? $detail['auto_questions'] : [];
    $defaultMode = (string) ($detail['default_mode'] ?? (count($manualQuestions) > 0 ? 'manual' : 'auto'));
    $focusQid = (int) ($detail['focus_question_id'] ?? 0);

    $manualCount = count($manualQuestions);
    $autoCount = count($autoQuestions);
    $pendingManual = (int) ($detail['pending_manual_count'] ?? 0);
    $pendingLabels = is_array($detail['pending_manual_labels'] ?? null) ? $detail['pending_manual_labels'] : [];
    $source = (string) ($detail['source'] ?? 'session');
    $isAssessment = $source === 'assessment';
    $isDraft = ! empty($detail['is_draft']);
    $allowRetake = ! empty($detail['allow_retake']);

    // Xác định câu đang mở mặc định trong từng tab
    $defaultManualIdx = 0;
    $defaultAutoIdx = 0;
    foreach ($manualQuestions as $i => $mq) {
        if ($focusQid > 0 && (int) ($mq['question_id'] ?? 0) === $focusQid) {
            $defaultManualIdx = $i;
            break;
        }
        if ($focusQid <= 0 && empty($mq['is_graded'])) {
            $defaultManualIdx = $i;
            break;
        }
    }
    foreach ($autoQuestions as $i => $aq) {
        if ($focusQid > 0 && (int) ($aq['question_id'] ?? 0) === $focusQid) {
            $defaultAutoIdx = $i;
            break;
        }
    }
@endphp

<div
    class="sn-tg-edit-shell"
    id="snTgGradingWorkspace"
    data-default-mode="{{ $defaultMode }}"
    data-source="{{ $source }}"
    data-view-url="{{ (string) ($detail['view_url'] ?? '') }}"
    data-pending-manual="{{ $pendingManual }}"
>
    {{-- Thanh điều hướng ngữ cảnh (#909:122557) --}}
    <div class="sn-tg-ctx-bar">
        <a class="sn-tg-back-sq" href="{{ (string) ($detail['back_url'] ?? route('teacher.grading')) }}" aria-label="Quay lại danh sách bài nộp" title="Quay lại danh sách bài nộp">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
        <span class="sn-tg-ctx-label">Giữ nguyên:</span>
        <div class="sn-tg-ctx-pills">
            @foreach ($contextPills as $pillText)
                <span class="sn-tg-ctx-pill">{{ $pillText }}</span>
            @endforeach
        </div>
    </div>

    {{-- Thẻ thông tin học sinh (#909:122567) --}}
    <div class="sn-tg-student-card">
        <div class="sn-tg-student-left">
            <span class="sn-tg-avatar sn-tg-avatar--lg sn-tg-avatar--{{ (string) ($student['avatar_tone'] ?? 'blue') }}">
                @if (! empty($student['avatar']))
                    <img src="{{ $student['avatar'] }}" alt="{{ $student['name'] ?? 'Học viên' }}">
                @else
                    <span>{{ $student['initials'] ?? 'HV' }}</span>
                @endif
            </span>
            <div class="sn-tg-student-copy">
                <h2 class="sn-tg-student-name">{{ $student['name'] ?? 'Học viên' }}</h2>
                @if (! empty($student['code']))
                    <span class="sn-tg-student-code">{{ $student['code'] }}</span>
                @endif
            </div>
        </div>

        <div class="sn-tg-student-right">
            @if ($isAssessment && ! $isDraft)
                @if ($allowRetake && ! empty($detail['retake_revoke_url']))
                    <form method="post" action="{{ $detail['retake_revoke_url'] }}">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ (int) ($detail['course_id'] ?? 0) }}">
                        <input type="hidden" name="assessment_id" value="{{ (int) ($detail['assessment_id'] ?? 0) }}">
                        <input type="hidden" name="user_id" value="{{ (int) ($student['id'] ?? 0) }}">
                        <button type="submit" class="sn-tg-retake-btn">Hủy làm lại</button>
                    </form>
                @elseif (! empty($detail['retake_grant_url']))
                    <form method="post" action="{{ $detail['retake_grant_url'] }}">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ (int) ($detail['course_id'] ?? 0) }}">
                        <input type="hidden" name="assessment_id" value="{{ (int) ($detail['assessment_id'] ?? 0) }}">
                        <input type="hidden" name="user_id" value="{{ (int) ($student['id'] ?? 0) }}">
                        <button type="submit" class="sn-tg-retake-btn">Cho làm lại</button>
                    </form>
                @endif
            @endif
            <span class="sn-tg-type-pill">{{ $detail['content_type_label'] ?? '' }}</span>
        </div>
    </div>

    {{-- Cụm 2 tab: Chấm tay & Tự động (#909:122578) --}}
    <div class="sn-tg-mode-row">
        <div class="sn-tg-tabs" role="tablist" aria-label="Chế độ chấm câu hỏi">
            <button
                type="button"
                class="sn-tg-tab{{ $defaultMode === 'manual' ? ' is-active' : '' }}"
                data-grade-tab="manual"
                role="tab"
                aria-selected="{{ $defaultMode === 'manual' ? 'true' : 'false' }}"
            >Chấm tay ({{ $manualCount }})</button>
            <button
                type="button"
                class="sn-tg-tab{{ $defaultMode === 'auto' ? ' is-active' : '' }}"
                data-grade-tab="auto"
                role="tab"
                aria-selected="{{ $defaultMode === 'auto' ? 'true' : 'false' }}"
            >Tự động ({{ $autoCount }})</button>
        </div>
    </div>

    {{-- Khối Tab: Chấm tay (#909:123422) --}}
    <div class="sn-tg-mode-pane" data-grade-pane="manual" @if ($defaultMode !== 'manual') hidden @endif>
        @if ($manualCount === 0)
            <div class="sn-tg-empty">
                <strong class="sn-tg-empty-title">Bài này không có câu chấm tay</strong>
                <p class="sn-tg-empty-desc">Tất cả câu hỏi đều thuộc nhóm chấm tự động. Chuyển sang tab Tự động để xem chi tiết.</p>
            </div>
        @else
            <div class="sn-tg-qnav-card">
                <span class="sn-tg-qnav-label">Câu hỏi</span>
                <div class="sn-tg-qnav-list" role="tablist">
                    @foreach ($manualQuestions as $mIdx => $mq)
                        <button
                            type="button"
                            class="sn-tg-qnum{{ $mIdx === $defaultManualIdx ? ' is-active' : (! empty($mq['is_graded']) ? ' is-graded' : '') }}"
                            data-qnav-btn="manual"
                            data-qnav-index="{{ $mIdx }}"
                            data-qnav-qid="{{ (int) ($mq['question_id'] ?? 0) }}"
                            role="tab"
                            aria-selected="{{ $mIdx === $defaultManualIdx ? 'true' : 'false' }}"
                        >{{ $mq['label'] ?? ($mIdx + 1) }}</button>
                    @endforeach
                </div>
            </div>

            @foreach ($manualQuestions as $mIdx => $mq)
                <article
                    class="sn-tg-qcard"
                    data-qcard="manual"
                    data-qcard-index="{{ $mIdx }}"
                    data-qcard-qid="{{ (int) ($mq['question_id'] ?? 0) }}"
                    @if ($mIdx !== $defaultManualIdx) hidden @endif
                >
                    <header class="sn-tg-qcard-head">
                        <span class="sn-tg-qbadge">Câu {{ $mq['label'] ?? ($mIdx + 1) }}</span>
                        <strong class="sn-tg-qtype">{{ $mq['question_type_label'] ?? 'Tự luận' }}</strong>
                        <span class="sn-tg-qhead-spacer"></span>
                        <div class="sn-tg-qscore-wrap">
                            <span
                                class="sn-tg-qscore-box is-{{ (string) ($mq['score_state'] ?? 'pending') }}"
                                data-qscore-box="{{ (int) ($mq['question_id'] ?? 0) }}"
                            >{{ $mq['earned_points_display'] ?? '—' }}</span>
                            <span class="sn-tg-qscore-max">/ {{ $mq['max_points_display'] ?? '0' }} điểm</span>
                        </div>
                    </header>

                    @include('teacher.partials.grading-question-body', [
                        'entry' => $mq,
                        'detail' => $detail,
                        'interactive' => true,
                        'activeTab' => $activeTab,
                    ])
                </article>
            @endforeach
        @endif
    </div>

    {{-- Khối Tab: Tự động (#909:122552) --}}
    <div class="sn-tg-mode-pane" data-grade-pane="auto" @if ($defaultMode !== 'auto') hidden @endif>
        @if ($autoCount === 0)
            <div class="sn-tg-empty">
                <strong class="sn-tg-empty-title">Bài này không có câu chấm tự động</strong>
                <p class="sn-tg-empty-desc">Tất cả câu hỏi đều cần giáo viên chấm tay.</p>
            </div>
        @else
            <div class="sn-tg-qnav-card">
                <span class="sn-tg-qnav-label">Câu hỏi</span>
                <div class="sn-tg-qnav-list" role="tablist">
                    @foreach ($autoQuestions as $aIdx => $aq)
                        <button
                            type="button"
                            class="sn-tg-qnum{{ $aIdx === $defaultAutoIdx ? ' is-active' : ' is-graded' }}"
                            data-qnav-btn="auto"
                            data-qnav-index="{{ $aIdx }}"
                            data-qnav-qid="{{ (int) ($aq['question_id'] ?? 0) }}"
                            role="tab"
                            aria-selected="{{ $aIdx === $defaultAutoIdx ? 'true' : 'false' }}"
                        >{{ $aq['label'] ?? ($aIdx + 1) }}</button>
                    @endforeach
                </div>
            </div>

            @foreach ($autoQuestions as $aIdx => $aq)
                <article
                    class="sn-tg-qcard"
                    data-qcard="auto"
                    data-qcard-index="{{ $aIdx }}"
                    data-qcard-qid="{{ (int) ($aq['question_id'] ?? 0) }}"
                    @if ($aIdx !== $defaultAutoIdx) hidden @endif
                >
                    <header class="sn-tg-qcard-head">
                        <span class="sn-tg-qbadge">Câu {{ $aq['label'] ?? ($aIdx + 1) }}</span>
                        <strong class="sn-tg-qtype">{{ $aq['question_type_label'] ?? 'Trắc nghiệm' }}</strong>
                        <span class="sn-tg-qhead-spacer"></span>
                        <div class="sn-tg-qscore-wrap">
                            <span
                                class="sn-tg-qscore-box is-{{ (string) ($aq['score_state'] ?? 'full') }}"
                                data-qscore-box="{{ (int) ($aq['question_id'] ?? 0) }}"
                            >{{ $aq['earned_points_display'] ?? '0' }}</span>
                            <span class="sn-tg-qscore-max">/ {{ $aq['max_points_display'] ?? '0' }} điểm</span>
                        </div>
                    </header>

                    @include('teacher.partials.grading-question-body', [
                        'entry' => $aq,
                        'detail' => $detail,
                        'interactive' => false,
                        'activeTab' => $activeTab,
                    ])
                </article>
            @endforeach
        @endif
    </div>

    {{-- Thẻ Tổng điểm & nhận xét chung (#909:122613 / #909:123480) --}}
    <section class="sn-tg-summary-card" aria-label="Tổng điểm và thao tác chấm">
        <div class="sn-tg-total-block">
            <span class="sn-tg-total-kicker">Tổng điểm</span>
            <strong class="sn-tg-total-value">
                <span data-total-earned>{{ $detail['total_earned_display'] ?? '0' }}</span>
                / <span data-total-possible>{{ $detail['total_possible_display'] ?? '100' }}</span>
            </strong>
            <p class="sn-tg-total-hint{{ $pendingManual === 0 ? ' is-ready' : '' }}" data-pending-hint>
                @if ($pendingManual > 0)
                    Còn {{ $pendingManual }} câu chấm tay ({{ implode(', ', array_slice($pendingLabels, 0, 6)) }}{{ count($pendingLabels) > 6 ? '…' : '' }}) chưa có điểm
                @else
                    Tất cả câu hỏi đã có điểm · Sẵn sàng gửi kết quả
                @endif
            </p>
        </div>

        <div class="sn-tg-general-comment">
            <input
                type="text"
                class="sn-tg-comment-input"
                id="snTgGeneralComment"
                placeholder="Nhận xét chung gửi đến học sinh…"
                autocomplete="off"
            >
        </div>

        <div class="sn-tg-action-foot">
            <span class="sn-tg-save-state" id="snTgGlobalSaveState">
                {{ $pendingManual > 0 ? 'Chưa lưu đủ câu chấm tay' : 'Đã lưu điểm' }}
            </span>

            <div class="sn-tg-action-btns">
                <button type="button" class="sn-tg-btn-draft" id="snTgSaveDraftBtn">Lưu nháp</button>

                @if ($isAssessment && ! empty($detail['publish_url']) && ! $isDraft)
                    <form method="post" action="{{ $detail['publish_url'] }}" id="snTgPublishForm">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ (int) ($detail['course_id'] ?? 0) }}">
                        <input type="hidden" name="assessment_id" value="{{ (int) ($detail['assessment_id'] ?? 0) }}">
                        <input type="hidden" name="user_id" value="{{ (int) ($student['id'] ?? 0) }}">
                        <button
                            type="submit"
                            class="sn-tg-btn-submit{{ $pendingManual === 0 ? ' is-ready' : '' }}"
                            id="snTgSubmitResultBtn"
                            @disabled($pendingManual > 0)
                        >Gửi kết quả</button>
                    </form>
                @else
                    <button
                        type="button"
                        class="sn-tg-btn-submit{{ $pendingManual === 0 ? ' is-ready' : '' }}"
                        id="snTgSubmitResultBtn"
                        data-finish-url="{{ (string) ($detail['view_url'] ?? '') }}"
                        @disabled($pendingManual > 0)
                    >Gửi kết quả</button>
                @endif
            </div>
        </div>
    </section>
</div>
