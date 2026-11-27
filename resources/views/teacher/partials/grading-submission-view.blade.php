@php
    $detail = is_array($detail ?? null) ? $detail : [];
    $activeTab = (string) ($activeTab ?? \App\Services\TeacherGradingService::TAB_ALL);
    $student = is_array($detail['student'] ?? null) ? $detail['student'] : [];
    $allQuestions = is_array($detail['all_questions'] ?? null) ? $detail['all_questions'] : [];
    $autoQuestions = is_array($detail['auto_questions'] ?? null) ? $detail['auto_questions'] : [];
    $manualQuestions = is_array($detail['manual_questions'] ?? null) ? $detail['manual_questions'] : [];

    $totalCount = count($allQuestions);
    $autoCount = count($autoQuestions);
    $manualCount = count($manualQuestions);

    $submittedAt = trim((string) ($detail['submitted_at'] ?? ''));
    $gradedAt = trim((string) ($detail['graded_at'] ?? ''));
    $submittedLabel = $submittedAt !== '' ? date('d/m · H:i', strtotime($submittedAt)) : '';
    $gradedShortLabel = $gradedAt !== '' ? date('d/m · H:i', strtotime($gradedAt)) : '';
    $gradedFullLabel = $gradedAt !== '' ? date('d/m/Y · H:i', strtotime($gradedAt)) : ($submittedAt !== '' ? date('d/m/Y · H:i', strtotime($submittedAt)) : '');

    $metaParts = array_filter([
        (string) ($student['code'] ?? ''),
        (string) ($detail['course_title'] ?? ''),
        (string) ($detail['level_title'] ?? ''),
    ]);
    $isDraft = ! empty($detail['is_draft']);
    $isAssessment = ($detail['source'] ?? '') === 'assessment';
    $allowRetake = ! empty($detail['allow_retake']);

    // Tổng hợp nhận xét giáo viên đầu tiên nếu có
    $overallComment = '';
    foreach ($manualQuestions as $mq) {
        $c = trim((string) ($mq['teacher_comment'] ?? ''));
        if ($c !== '' && ! str_starts_with(ltrim($c), '{')) {
            $overallComment = \App\Support\HtmlContent::plain($c);
            if ($overallComment !== '') {
                break;
            }
        }
    }
@endphp

<div class="sn-tg-view-shell" id="snTgReadOnlyView">
    {{-- Thanh điều hướng trên cùng (#652:30679) --}}
    <div class="sn-tg-view-top">
        <div class="sn-tg-view-top-left">
            <a class="sn-tg-back-sq" href="{{ (string) ($detail['back_url'] ?? route('teacher.grading')) }}" aria-label="Quay lại danh sách bài nộp">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <span class="sn-tg-view-top-title">Danh sách bài nộp</span>
        </div>

        <div class="sn-tg-view-top-right">
            @if (! $isDraft && ! empty($detail['edit_url']))
                <a class="sn-tg-edit-link" href="{{ $detail['edit_url'] }}">Chỉnh sửa điểm</a>
            @endif
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
        </div>
    </div>

    {{-- Khung xám chi tiết kết quả (#652:34034) --}}
    <div class="sn-tg-view-shell-box">
        {{-- Thẻ thông tin bài đã chấm (#652:30692) --}}
        <div class="sn-tg-view-summary">
            <div class="sn-tg-view-summary-top">
                <span class="sn-tg-avatar sn-tg-avatar--xl sn-tg-avatar--{{ (string) ($student['avatar_tone'] ?? 'green') }}">
                    @if (! empty($student['avatar']))
                        <img src="{{ $student['avatar'] }}" alt="{{ $student['name'] ?? 'Học viên' }}">
                    @else
                        <span>{{ $student['initials'] ?? 'HV' }}</span>
                    @endif
                </span>

                <div class="sn-tg-view-ident">
                    <h2 class="sn-tg-view-name">{{ $student['name'] ?? 'Học viên' }}</h2>
                    <span class="sn-tg-view-meta">{{ implode(' · ', $metaParts) }}</span>
                </div>

                <div class="sn-tg-view-score-col">
                    <span class="sn-tg-view-score-kicker">Tổng điểm</span>
                    <strong class="sn-tg-view-score-big">{{ $detail['total_earned_display'] ?? '0' }} / {{ $detail['total_possible_display'] ?? '100' }}</strong>
                </div>
            </div>

            <div class="sn-tg-view-summary-foot">
                @if ($isDraft)
                    <span class="sn-tg-status-pill sn-tg-status-pill--draft">Bản nháp · Học sinh chưa nộp bài</span>
                @else
                    <span class="sn-tg-status-pill sn-tg-status-pill--graded">Đã gửi kết quả · Chỉ xem, không thể chỉnh sửa</span>
                @endif
                @if ($submittedLabel !== '')
                    <span class="sn-tg-view-time">Nộp: {{ $submittedLabel }}</span>
                @endif
                @if ($gradedShortLabel !== '')
                    <span class="sn-tg-view-time">Gửi: {{ $gradedShortLabel }}</span>
                @endif
            </div>
        </div>

        {{-- Bộ lọc 3 tab (#923:42177) --}}
        <div class="sn-tg-view-tabs-row">
            <div class="sn-tg-tabs" role="tablist" aria-label="Lọc câu hỏi đã chấm">
                <button type="button" class="sn-tg-tab is-active" data-view-filter="all" role="tab" aria-selected="true">Tất cả ({{ $totalCount }})</button>
                <button type="button" class="sn-tg-tab" data-view-filter="auto" role="tab" aria-selected="false">Tự động ({{ $autoCount }})</button>
                <button type="button" class="sn-tg-tab" data-view-filter="manual" role="tab" aria-selected="false">Giáo viên chấm ({{ $manualCount }})</button>
            </div>
        </div>

        {{-- Thanh nhảy câu hỏi tô màu kết quả (#923:42184) --}}
        <div class="sn-tg-qnav-card">
            <span class="sn-tg-qnav-label">Câu hỏi</span>
            <div class="sn-tg-qnav-list">
                @foreach ($allQuestions as $qItem)
                    @php
                        $qState = (string) ($qItem['score_state'] ?? 'full');
                        $isMan = ! empty($qItem['is_manual']);
                        $pillTone = $isMan ? 'manual' : $qState;
                    @endphp
                    <a
                        class="sn-tg-qjump is-{{ $pillTone }}"
                        href="#sn-tg-qv-{{ (int) ($qItem['index'] ?? 0) }}"
                        data-view-group="{{ $isMan ? 'manual' : 'auto' }}"
                    >{{ $qItem['label'] ?? $qItem['index'] }}</a>
                @endforeach
            </div>
        </div>

        {{-- Nhóm 1: Tự động chấm (#923:42204) --}}
        @if ($autoCount > 0)
            <div class="sn-tg-view-section" data-view-section="auto">
                <h3 class="sn-tg-view-section-title">Tự động chấm · {{ $autoCount }} câu</h3>

                <div class="sn-tg-view-cards">
                    @foreach ($autoQuestions as $aq)
                        <article class="sn-tg-qcard" id="sn-tg-qv-{{ (int) ($aq['index'] ?? 0) }}">
                            <header class="sn-tg-qcard-head">
                                <span class="sn-tg-qbadge">Câu {{ $aq['label'] ?? $aq['index'] }}</span>
                                <strong class="sn-tg-qtype">{{ $aq['question_type_label'] ?? 'Trắc nghiệm' }}</strong>
                                <span class="sn-tg-qhead-spacer"></span>
                                <div class="sn-tg-qscore-wrap">
                                    <span class="sn-tg-qscore-box is-{{ (string) ($aq['score_state'] ?? 'full') }}">{{ $aq['earned_points_display'] ?? '0' }}</span>
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
                </div>
            </div>
        @endif

        {{-- Nhóm 2: Giáo viên chấm --}}
        @if ($manualCount > 0)
            <div class="sn-tg-view-section" data-view-section="manual">
                <h3 class="sn-tg-view-section-title">Giáo viên chấm · {{ $manualCount }} câu</h3>

                <div class="sn-tg-view-cards">
                    @foreach ($manualQuestions as $mq)
                        <article class="sn-tg-qcard" id="sn-tg-qv-{{ (int) ($mq['index'] ?? 0) }}">
                            <header class="sn-tg-qcard-head">
                                <span class="sn-tg-qbadge">Câu {{ $mq['label'] ?? $mq['index'] }}</span>
                                <strong class="sn-tg-qtype">{{ $mq['question_type_label'] ?? 'Tự luận' }}</strong>
                                <span class="sn-tg-qhead-spacer"></span>
                                <div class="sn-tg-qscore-wrap">
                                    <span class="sn-tg-qscore-box is-{{ (string) ($mq['score_state'] ?? 'partial') }}">{{ $mq['earned_points_display'] ?? '—' }}</span>
                                    <span class="sn-tg-qscore-max">/ {{ $mq['max_points_display'] ?? '0' }} điểm</span>
                                </div>
                            </header>

                            @include('teacher.partials.grading-question-body', [
                                'entry' => $mq,
                                'detail' => $detail,
                                'interactive' => false,
                                'activeTab' => $activeTab,
                            ])
                        </article>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Chân trang kết quả (#652:30823) --}}
        <div class="sn-tg-view-footer-card">
            @if ($overallComment !== '')
                <div class="sn-tg-comment-readonly">{{ $overallComment }}</div>
            @endif
            <div class="sn-tg-view-footer-bar">
                <span class="sn-tg-view-footer-meta">
                    @if ($isDraft)
                        Bản nháp · Chỉ xem
                    @elseif ($gradedFullLabel !== '')
                        Gửi kết quả lúc {{ $gradedFullLabel }} · Chỉ xem
                    @else
                        Đã gửi kết quả · Chỉ xem
                    @endif
                </span>
                <a class="sn-tg-btn-draft" href="{{ (string) ($detail['back_url'] ?? route('teacher.grading')) }}">Quay lại danh sách</a>
            </div>
        </div>
    </div>
</div>
