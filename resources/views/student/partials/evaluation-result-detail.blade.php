@php
    $detail = $detail ?? [];
    $activeFilter = (string) ($activeFilter ?? 'all');
    $backUrl = route('student.evaluations', array_filter([
        'tab' => 'ket-qua',
        'loc' => $activeFilter !== 'all' ? $activeFilter : null,
    ]));
    $isPending = ! empty($detail['is_pending']);
    $questions = $detail['questions'] ?? [];
    $teacherName = (string) ($detail['teacher_name'] ?? 'Giáo viên');
    $teacherInitials = (string) ($detail['teacher_initials'] ?? 'GV');
@endphp

<div class="sn-res-detail">
    {{-- Đầu trang: Nút quay lại + Tiêu đề & Thời gian (#719:26030 / #719:26850) --}}
    <header class="sn-res-detail-head">
        <a class="sn-res-back-circle" href="{{ $backUrl }}" aria-label="Quay lại danh sách kết quả" title="Quay lại danh sách kết quả">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M9.99996 15.8327L4.16663 9.99935L9.99996 4.16602" stroke="#313B47" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15.8333 10H4.16663" stroke="#313B47" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
        <div class="sn-res-detail-titles">
            <h1 class="sn-res-detail-title">{{ $detail['title'] ?? 'Chi tiết kết quả' }}</h1>
            <p class="sn-res-detail-sub">{{ $detail['time_subtitle'] ?? '' }}</p>
        </div>
    </header>

    {{-- Khối tổng hợp: Tổng điểm + Nhận xét giáo viên --}}
    <div class="sn-res-summary-grid">
        <div class="sn-res-score-card">
            <span class="sn-res-score-label">Tổng điểm</span>
            <strong class="sn-res-score-val{{ $isPending ? ' is-pending' : '' }}">
                {{ $detail['score_display'] ?? '— / 100' }}
            </strong>
            @if (! empty($detail['score_sub']))
                <span class="sn-res-score-sub">{{ $detail['score_sub'] }}</span>
            @endif
        </div>

        <div class="sn-res-teacher-card">
            <div class="sn-res-teacher-head">
                <span class="sn-res-teacher-badge" aria-hidden="true">{{ $teacherInitials }}</span>
                <strong>{{ $teacherName }}</strong>
            </div>
            <p class="sn-res-teacher-note{{ $isPending ? ' is-pending' : '' }}">{{ $detail['overall_note'] ?? '' }}</p>
        </div>
    </div>

    @if ($isPending)
        {{-- Chờ giáo viên chấm (#719:26850) — không hiển thị đáp án hay điểm --}}
        <section class="sn-res-pending-box" aria-label="Danh sách câu hỏi đã nộp">
            <h2 class="sn-res-pending-heading">Bài làm đã nộp · {{ count($questions) }} câu</h2>

            <div class="sn-res-pending-list">
                @foreach ($questions as $q)
                    <div class="sn-res-pending-row">
                        <div class="sn-res-pending-left">
                            <span class="sn-res-q-num-inline">Câu {{ $q['number'] }}</span>
                            <span class="sn-res-q-type">{{ $q['type_label'] }}</span>
                        </div>
                        <div class="sn-res-pending-right">
                            <span class="sn-res-chip sn-res-chip--muted">{{ $q['status_label'] }}</span>
                            <span class="sn-res-pending-pts">{{ $q['points_display'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @else
        {{-- Dãy ô số Câu hỏi nhảy nhanh (#719:26030 / #719:26243 / #719:26596) --}}
        @if ($questions !== [])
            <nav class="sn-res-jump-bar" aria-label="Chuyển nhanh tới câu hỏi">
                <span class="sn-res-jump-label">Câu hỏi</span>
                <div class="sn-res-jump-list">
                    @foreach ($questions as $q)
                        <button
                            type="button"
                            class="sn-res-jump-btn sn-res-jump-btn--{{ $q['status'] }}"
                            data-sn-res-jump="snResQ{{ $q['number'] }}"
                            aria-label="Đi tới câu {{ $q['number'] }} ({{ $q['status_label'] }})"
                        >{{ $q['number'] }}</button>
                    @endforeach
                </div>
            </nav>
        @endif

        {{-- Danh sách thẻ chi tiết từng câu hỏi --}}
        <div class="sn-res-q-list">
            @foreach ($questions as $q)
                <article class="sn-res-q-card" id="snResQ{{ $q['number'] }}">
                    <div class="sn-res-q-head">
                        <div class="sn-res-q-head-left">
                            <span class="sn-res-q-pill">Câu {{ $q['number'] }}</span>
                            <strong class="sn-res-q-type-bold">{{ $q['type_label'] }}</strong>
                            @if ($q['status'] === 'correct')
                                <span class="sn-res-chip sn-res-chip--green">Đúng</span>
                            @elseif ($q['status'] === 'wrong')
                                <span class="sn-res-chip sn-res-chip--red">Sai</span>
                            @elseif ($q['status'] === 'partial')
                                <span class="sn-res-chip sn-res-chip--amber">{{ $q['status_label'] }}</span>
                            @else
                                <span class="sn-res-chip sn-res-chip--muted">Chưa trả lời</span>
                            @endif
                        </div>

                        <span class="sn-res-q-pts{{ ! empty($q['points_is_zero']) ? ' is-zero' : '' }}">
                            {{ $q['points_display'] }}
                        </span>
                    </div>

                    @if (($q['prompt_html'] ?? '') !== '')
                        <div class="sn-res-q-prompt course-rich-content">{!! $q['prompt_html'] !!}</div>
                    @endif

                    @if (! empty($q['is_essay_box']))
                        <div class="sn-res-essay-box">
                            <span class="sn-res-essay-label">Bài làm của em</span>

                            @if (($q['audio_url'] ?? '') !== '')
                                <div class="sn-res-audio-player" data-sn-audio-player>
                                    <button type="button" class="sn-res-audio-play" data-audio-toggle aria-label="Phát bản ghi âm">
                                        <svg class="ico-play" width="18" height="18" viewBox="0 0 18 18" fill="none">
                                            <path d="M4.5 2.25L15 9L4.5 15.75V2.25Z" fill="#FFFFFF" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <svg class="ico-pause" width="18" height="18" viewBox="0 0 18 18" fill="none" hidden>
                                            <rect x="4.5" y="3.5" width="3.2" height="11" rx="1" fill="#FFFFFF"/>
                                            <rect x="10.3" y="3.5" width="3.2" height="11" rx="1" fill="#FFFFFF"/>
                                        </svg>
                                    </button>
                                    <div class="sn-res-audio-body">
                                        <span class="sn-res-audio-title">Bản ghi âm · {{ $q['audio_name'] }}</span>
                                        <div class="sn-res-audio-track" data-audio-track>
                                            <span class="sn-res-audio-fill" data-audio-fill style="width: 0%"></span>
                                        </div>
                                    </div>
                                    <span class="sn-res-audio-time" data-audio-time>00:00</span>
                                    <audio preload="metadata" src="{{ $q['audio_url'] }}" data-audio-el></audio>
                                </div>
                            @endif

                            @if (($q['essay_html'] ?? '') !== '')
                                <div class="sn-res-essay-content course-rich-content">{!! $q['essay_html'] !!}</div>
                            @elseif (($q['audio_url'] ?? '') === '')
                                <p class="sn-res-essay-empty">Chưa trả lời câu hỏi này.</p>
                            @endif
                        </div>
                    @else
                        <div class="sn-res-answer-line">
                            @if ($q['status'] === 'unanswered')
                                <span class="sn-res-ans-muted">Chưa trả lời</span>
                            @else
                                <span class="sn-res-ans-prefix">Chọn:</span>
                                <strong class="sn-res-ans-val sn-res-ans-val--{{ $q['status'] }}">
                                    {{ $q['student_text'] !== '' ? $q['student_text'] : 'Đã nộp' }}
                                </strong>
                            @endif

                            @if (($q['correct_text'] ?? '') !== '')
                                <span class="sn-res-ans-sep" aria-hidden="true">|</span>
                                <span class="sn-res-ans-prefix">Đúng:</span>
                                <strong class="sn-res-ans-val sn-res-ans-val--correct">{{ $q['correct_text'] }}</strong>
                            @endif
                        </div>
                    @endif

                    @if (($q['teacher_comment'] ?? '') !== '' || ($q['teacher_audio_url'] ?? '') !== '')
                        <div class="sn-res-q-comment">
                            <span class="sn-res-teacher-badge sn-res-teacher-badge--sm" aria-hidden="true">{{ $teacherInitials }}</span>
                            <div class="sn-res-q-comment-body">
                                @if (($q['teacher_comment'] ?? '') !== '')
                                    <p>{{ $q['teacher_comment'] }}</p>
                                @endif
                                @if (($q['teacher_audio_url'] ?? '') !== '')
                                    <audio controls preload="metadata" src="{{ $q['teacher_audio_url'] }}" class="sn-res-teacher-audio"></audio>
                                @endif
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
