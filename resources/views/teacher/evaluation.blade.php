@extends('layouts.app', ['active' => 'evaluations'])

@php
    $evaluationService = app(\App\Services\TeacherEvaluationService::class);
    $isCourseSelected = (bool) ($isCourseSelected ?? false);
    $activeTab = (string) ($activeTab ?? \App\Services\TeacherEvaluationService::TAB_ALL);
    $search = (string) ($search ?? '');
    $course = $course ?? null;
    $courseId = (int) ($courseId ?? 0);
    $userId = (int) ($userId ?? 0);
    $evalWeek = (string) ($evalWeek ?? '');
    $weekLabel = (string) ($weekLabel ?? '');
    $members = $members ?? collect();
    $member = $member ?? null;
    $evaluatedCount = (int) ($evaluatedCount ?? 0);
    $filteredCourseSummaries = is_array($filteredCourseSummaries ?? null) ? $filteredCourseSummaries : ($courseSummaries ?? []);

    $overviewTabs = [
        \App\Services\TeacherEvaluationService::TAB_ALL => 'Tất cả',
        \App\Services\TeacherEvaluationService::TAB_IN_PROGRESS => 'Đang đánh giá',
        \App\Services\TeacherEvaluationService::TAB_DONE => 'Hoàn tất',
    ];
    $searchExtra = $search !== '' ? ['q' => $search] : [];
    $backToOverviewUrl = $evaluationService->pageUrl(0, 0, $evalWeek);
@endphp

@section('title', 'Đánh giá học viên')

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-evaluation.css') }}?v=3">
@endpush

@section('content')
<div class="te-page sn-te-page">
    <section class="sn-te-surface" aria-label="Đánh giá học viên">
        @if (! $isCourseSelected)
            {{-- Hàng 1: Cụm tab pill căn giữa (#681:61599 / #681:61600) --}}
            <div class="sn-te-tabs-row">
                <nav class="sn-te-tabs" role="tablist" aria-label="Trạng thái đánh giá khóa học">
                    @foreach ($overviewTabs as $tabKey => $tabLabel)
                        @php
                            $isTabActive = $activeTab === $tabKey;
                            $tabHref = $evaluationService->pageUrl(0, 0, $evalWeek, array_merge($searchExtra, ['tab' => $tabKey]));
                        @endphp
                        <a
                            class="sn-te-tab{{ $isTabActive ? ' is-active' : '' }}"
                            href="{{ $tabHref }}"
                            role="tab"
                            aria-selected="{{ $isTabActive ? 'true' : 'false' }}"
                        >{{ $tabLabel }}</a>
                    @endforeach
                    <a
                        class="sn-te-tab"
                        href="{{ route('teacher.grading', ['muc' => 'bai-thi']) }}"
                        role="tab"
                        aria-selected="false"
                    >Bài thi</a>
                </nav>
            </div>

            {{-- Hàng 2: Thanh tìm kiếm khóa học (#681:61601 / #681:61602) --}}
            <form class="sn-te-search-form" method="get" action="{{ route('teacher.evaluations') }}" role="search">
                @if ($activeTab !== \App\Services\TeacherEvaluationService::TAB_ALL)
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                @endif
                @if ($evalWeek !== '')
                    <input type="hidden" name="tuan" value="{{ $evalWeek }}">
                @endif

                <span class="sn-te-search-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <circle cx="11.5" cy="11.5" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M21 21L18.2 18.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <input
                    type="search"
                    id="snTeCourseSearch"
                    class="sn-te-search-input sn-search-input-bare"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Tìm kiếm khoá học"
                    aria-label="Tìm kiếm khoá học"
                    autocomplete="off"
                >
                @if ($search !== '')
                    <a
                        class="sn-te-search-clear"
                        href="{{ $evaluationService->pageUrl(0, 0, $evalWeek, ['tab' => $activeTab]) }}"
                        aria-label="Xóa từ khóa tìm kiếm"
                        title="Xóa tìm kiếm"
                    >
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M9.17 14.83L14.83 9.17M14.83 14.83L9.17 9.17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @endif
            </form>

            {{-- Hàng 3: Lưới 2 cột thẻ khóa học (#681:61603) --}}
            <div class="sn-te-grid-shell">
                @if ($filteredCourseSummaries === [])
                    <div class="sn-te-empty">
                        <span class="sn-te-empty-icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                                <path d="M12 3L14.8 8.7L21 9.6L16.5 14L17.6 20.2L12 17.2L6.4 20.2L7.5 14L3 9.6L9.2 8.7L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <strong class="sn-te-empty-title">
                            @if ($search !== '' || $activeTab !== \App\Services\TeacherEvaluationService::TAB_ALL)
                                Không tìm thấy khoá học phù hợp
                            @else
                                Chưa có khoá học nào cần đánh giá
                            @endif
                        </strong>
                        <p class="sn-te-empty-desc">
                            @if ($search !== '' || $activeTab !== \App\Services\TeacherEvaluationService::TAB_ALL)
                                Thử thay đổi từ khóa tìm kiếm hoặc chuyển sang tab Tất cả.
                            @else
                                Các khoá học đang phụ trách sẽ xuất hiện tại đây để bạn đánh giá học viên hàng tuần.
                            @endif
                        </p>
                        @if ($search !== '' || $activeTab !== \App\Services\TeacherEvaluationService::TAB_ALL)
                            <a class="sn-te-empty-reset" href="{{ $evaluationService->pageUrl(0, 0, $evalWeek) }}">Xem tất cả khoá học</a>
                        @endif
                    </div>
                @else
                    <div class="sn-te-course-grid" id="snTeCourseGrid">
                        @foreach ($filteredCourseSummaries as $summary)
                            @php
                                $row = $summary['course'] ?? null;
                                $cId = (int) ($row?->id ?? 0);
                                $cTitle = trim((string) ($row?->title ?? 'Khoá học'));
                                $cMembers = (int) ($summary['member_count'] ?? 0);
                                $cEvaluated = (int) ($summary['evaluated'] ?? 0);
                                $cPct = (int) ($summary['progress_pct'] ?? 0);
                                $cDone = (bool) ($summary['is_completed'] ?? false);
                                $cUrl = $evaluationService->pageUrl($cId, 0, $evalWeek);
                            @endphp
                            <article class="sn-te-card" data-course-name="{{ mb_strtolower($cTitle, 'UTF-8') }}">
                                <div class="sn-te-card-head">
                                    <h2 class="sn-te-card-title">{{ $cTitle }}</h2>
                                    @if ($cDone)
                                        <span class="sn-te-badge sn-te-badge--done">Hoàn tất</span>
                                    @else
                                        <span class="sn-te-badge sn-te-badge--progress">Đang đánh giá</span>
                                    @endif
                                </div>

                                <div class="sn-te-progress-box">
                                    <span class="sn-te-member-count">{{ $cMembers }} học viên</span>
                                    <div class="sn-te-progress-row">
                                        <span class="sn-te-progress-ratio">{{ $cEvaluated }}/{{ $cMembers }}</span>
                                        <div class="sn-te-progress-track" role="progressbar" aria-valuenow="{{ $cPct }}" aria-valuemin="0" aria-valuemax="100">
                                            <span class="sn-te-progress-fill{{ $cDone ? ' is-done' : '' }}" style="width: {{ $cPct }}%"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="sn-te-card-foot">
                                    <a class="sn-te-cta" href="{{ $cUrl }}">
                                        <span>{{ $cDone ? 'Xem chi tiết' : 'Tiếp tục' }}</span>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle cx="12" cy="12" r="10" fill="#0D99FF"/>
                                            <path d="M8.5 12H15.5M15.5 12L12.5 9M15.5 12L12.5 15" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="sn-te-empty sn-te-empty--live" id="snTeLiveEmpty" hidden>
                        <strong class="sn-te-empty-title">Không tìm thấy khoá học khớp từ khóa</strong>
                        <p class="sn-te-empty-desc">Thử nhập lại tên khoá học khác.</p>
                    </div>
                @endif
            </div>
        @else
            {{-- Chế độ 2: Không gian đánh giá tuần chi tiết cho khóa đã chọn --}}
            <div class="sn-te-detail-bar">
                <a class="sn-te-back-btn" href="{{ $backToOverviewUrl }}">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Quay lại danh sách khoá học</span>
                </a>

                <div class="sn-te-detail-meta">
                    <strong class="sn-te-detail-course">{{ trim((string) ($course?->title ?? 'Khoá học')) }}</strong>
                    @if ($weekLabel !== '')
                        <span class="sn-te-detail-week">{{ $weekLabel }}</span>
                    @endif
                    <span class="sn-te-detail-ratio">{{ $evaluatedCount }}/{{ $members->count() }} học viên</span>
                </div>
            </div>

            <div class="sn-te-detail-workspace">
                <aside class="teacher-eval-workspace-students" aria-label="Danh sách học viên">
                    @include('teacher.partials.eval-students', [
                        'course' => $course,
                        'courseId' => $courseId,
                        'userId' => $userId,
                        'evalWeek' => $evalWeek,
                        'members' => $members,
                        'evalMap' => $evalMap ?? [],
                        'attendanceMap' => $attendanceMap ?? [],
                        'weekSessionCount' => (int) ($weekSessionCount ?? 0),
                        'evaluatedCount' => $evaluatedCount,
                        'evaluationService' => $evaluationService,
                    ])
                </aside>

                <section class="teacher-eval-workspace-main" aria-label="Nội dung đánh giá">
                    @include('teacher.partials.eval-panel', [
                        'courseId' => $courseId,
                        'userId' => $userId,
                        'evalWeek' => $evalWeek,
                        'weekOptions' => $weekOptions ?? [],
                        'member' => $member,
                        'evaluation' => $evaluation ?? null,
                        'canEdit' => (bool) ($canEdit ?? false),
                        'scoreLabels' => $scoreLabels ?? [],
                        'evaluationService' => $evaluationService,
                    ])
                </section>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/teacher-evaluation.js') }}?v=2"></script>
    @if ($isCourseSelected && ! empty($canEdit) && $member !== null)
        <script>
            document.body.dataset.tinymceBase = @json(asset('assets/tinymce'));
        </script>
        <script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
        <script src="{{ asset('assets/js/course-eval-form.js') }}"></script>
    @endif
@endpush
