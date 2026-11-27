@extends('layouts.app', ['active' => 'grading'])

@php
    $gradingService = app(\App\Services\TeacherGradingService::class);
    $course = $course ?? null;
    $courseId = (int) ($courseId ?? 0);
    $courseLevelTitle = (string) ($courseLevelTitle ?? '');
    $activeTab = (string) ($activeTab ?? \App\Services\TeacherGradingService::TAB_ALL);
    $submissionDetail = is_array($submissionDetail ?? null) ? $submissionDetail : null;
    $pageItems = is_array($pageItems ?? null) ? $pageItems : [];
    $pagination = is_array($pagination ?? null) ? $pagination : [
        'total' => 0, 'per_page' => 8, 'current_page' => 1, 'last_page' => 1, 'from' => 0, 'to' => 0, 'pages' => [1],
    ];
    $search = (string) ($search ?? '');
    $statusFilter = (string) ($statusFilter ?? 'all');
    $sort = (string) ($sort ?? 'priority');
    $filterQuery = is_array($filterQuery ?? null) ? $filterQuery : [];

    $contentTabs = [
        \App\Services\TeacherGradingService::TAB_ALL => 'Tất cả',
        \App\Services\TeacherGradingService::TAB_PRE => 'Trước buổi',
        \App\Services\TeacherGradingService::TAB_POST => 'Sau buổi',
        \App\Services\TeacherGradingService::TAB_KNOWLEDGE_REVIEW => 'Bài kiểm tra',
        \App\Services\TeacherGradingService::TAB_ASSESSMENT => 'Bài thi',
    ];

    $statusOptions = [
        'all' => 'Tất cả trạng thái',
        'pending' => 'Chờ chấm',
        'draft' => 'Bản nháp',
        'graded' => 'Đã gửi kết quả',
    ];

    $sortOptions = [
        'priority' => 'Ưu tiên chờ chấm',
        'newest' => 'Mới nộp nhất',
        'oldest' => 'Cũ nhất',
        'name' => 'Tên học viên A–Z',
    ];

    $hasActiveFilter = $statusFilter !== 'all' || $sort !== 'priority';
@endphp

@section('title', 'Chấm điểm')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/session-quiz.css') }}?v=4">
@endpush

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-grading.css') }}?v=14">
@endpush

@section('content')
<div class="tg-page sn-tg-page">
    <section class="sn-tg-surface{{ $submissionDetail !== null ? ' sn-tg-surface--detail' : '' }}" aria-label="Chấm điểm bài nộp">
        @if ($submissionDetail === null)
            {{-- Màn 1: Danh sách bài nộp (#636:53389) --}}
            <div class="sn-tg-top-row">
                <div class="sn-tg-top-spacer" aria-hidden="true"></div>

                <nav class="sn-tg-tabs" role="tablist" aria-label="Loại nội dung chấm điểm">
                    @foreach ($contentTabs as $tabKey => $tabLabel)
                        @php
                            $isTabActive = $activeTab === $tabKey;
                            $tabHref = $gradingService->pageUrl($courseId, $tabKey, '', $filterQuery);
                        @endphp
                        <a
                            class="sn-tg-tab{{ $isTabActive ? ' is-active' : '' }}"
                            href="{{ $tabHref }}"
                            role="tab"
                            aria-selected="{{ $isTabActive ? 'true' : 'false' }}"
                        >{{ $tabLabel }}</a>
                    @endforeach
                </nav>

                <div class="sn-tg-course-slot">
                    @include('teacher.partials.grading-courses', [
                        'courseId' => $courseId,
                        'courseLevelTitle' => $courseLevelTitle,
                        'activeTab' => $activeTab,
                        'selectedKey' => '',
                        'filterQuery' => $filterQuery,
                        'courseSummaries' => $courseSummaries ?? [],
                        'gradingService' => $gradingService,
                    ])
                </div>
            </div>

            <div class="sn-tg-search-row">
                <form class="sn-tg-search-form" method="get" action="{{ route('teacher.grading') }}" role="search">
                    @if ($courseId > 0)
                        <input type="hidden" name="khoa" value="{{ $courseId }}">
                    @endif
                    @if ($activeTab !== \App\Services\TeacherGradingService::TAB_ALL)
                        <input type="hidden" name="muc" value="{{ $activeTab }}">
                    @endif
                    @if ($statusFilter !== 'all')
                        <input type="hidden" name="trang_thai" value="{{ $statusFilter }}">
                    @endif
                    @if ($sort !== 'priority')
                        <input type="hidden" name="sap_xep" value="{{ $sort }}">
                    @endif

                    <span class="sn-tg-search-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <circle cx="11.5" cy="11.5" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M21 21L18.2 18.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <input
                        type="search"
                        class="sn-tg-search-input sn-search-input-bare"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Tìm kiếm học viên"
                        aria-label="Tìm kiếm học viên"
                        autocomplete="off"
                    >
                    @if ($search !== '')
                        <a
                            class="sn-tg-search-clear"
                            href="{{ $gradingService->pageUrl($courseId, $activeTab, '', array_diff_key($filterQuery, ['q' => true])) }}"
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

                <div class="sn-tg-filter-wrap">
                    <button
                        type="button"
                        class="sn-tg-filter-btn{{ $hasActiveFilter ? ' has-active' : '' }}"
                        id="snTgFilterToggle"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="snTgFilterMenu"
                        aria-label="Lọc và sắp xếp bài nộp"
                        title="Lọc & sắp xếp"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8.16 6.65H15.83C16.47 6.65 16.99 7.17 16.99 7.81V9.09C16.99 9.56 16.7 10.14 16.41 10.43L13.91 12.64C13.56 12.93 13.33 13.51 13.33 13.98V16.48C13.33 16.83 13.1 17.29 12.81 17.47L12 17.98C11.24 18.45 10.2 17.92 10.2 16.99V13.91C10.2 13.5 9.97 12.98 9.73 12.69L7.52 10.36C7.23 10.07 7 9.55 7 9.2V7.87C7 7.17 7.52 6.65 8.16 6.65Z" stroke="currentColor" stroke-width="1.6" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 22H15C20 22 22 20 22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        @if ($hasActiveFilter)
                            <span class="sn-tg-filter-dot" aria-hidden="true"></span>
                        @endif
                    </button>

                    <div class="sn-tg-filter-menu" id="snTgFilterMenu" role="menu" hidden>
                        <div class="sn-tg-filter-group">
                            <span class="sn-tg-filter-label">Trạng thái chấm</span>
                            <div class="sn-tg-filter-options">
                                @foreach ($statusOptions as $stKey => $stLabel)
                                    @php
                                        $nextQuery = $filterQuery;
                                        $nextQuery['trang_thai'] = $stKey;
                                        $stUrl = $gradingService->pageUrl($courseId, $activeTab, '', $nextQuery);
                                    @endphp
                                    <a
                                        class="sn-tg-filter-option{{ $statusFilter === $stKey ? ' is-selected' : '' }}"
                                        href="{{ $stUrl }}"
                                        role="menuitemradio"
                                        aria-checked="{{ $statusFilter === $stKey ? 'true' : 'false' }}"
                                    >
                                        <span>{{ $stLabel }}</span>
                                        @if ($statusFilter === $stKey)
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                                <path d="M3.5 8.5L6.5 11.5L12.5 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="sn-tg-filter-divider"></div>

                        <div class="sn-tg-filter-group">
                            <span class="sn-tg-filter-label">Sắp xếp theo</span>
                            <div class="sn-tg-filter-options">
                                @foreach ($sortOptions as $soKey => $soLabel)
                                    @php
                                        $nextQuery = $filterQuery;
                                        $nextQuery['sap_xep'] = $soKey;
                                        $soUrl = $gradingService->pageUrl($courseId, $activeTab, '', $nextQuery);
                                    @endphp
                                    <a
                                        class="sn-tg-filter-option{{ $sort === $soKey ? ' is-selected' : '' }}"
                                        href="{{ $soUrl }}"
                                        role="menuitemradio"
                                        aria-checked="{{ $sort === $soKey ? 'true' : 'false' }}"
                                    >
                                        <span>{{ $soLabel }}</span>
                                        @if ($sort === $soKey)
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                                <path d="M3.5 8.5L6.5 11.5L12.5 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        @if ($hasActiveFilter)
                            <div class="sn-tg-filter-divider"></div>
                            <a
                                class="sn-tg-filter-reset"
                                href="{{ $gradingService->pageUrl($courseId, $activeTab, '', $search !== '' ? ['q' => $search] : []) }}"
                            >Đặt lại bộ lọc</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="sn-tg-list-shell">
                @if ($pageItems === [])
                    <div class="sn-tg-empty">
                        <span class="sn-tg-empty-icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                                <path d="M9 11L11 13L15.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <rect x="3" y="4" width="18" height="16" rx="4" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </span>
                        <strong class="sn-tg-empty-title">
                            @if ($search !== '' || $hasActiveFilter)
                                Không tìm thấy bài nộp phù hợp
                            @else
                                Chưa có bài nộp nào trong mục này
                            @endif
                        </strong>
                        <p class="sn-tg-empty-desc">
                            @if ($search !== '' || $hasActiveFilter)
                                Thử thay đổi từ khóa tìm kiếm hoặc đặt lại bộ lọc trạng thái.
                            @else
                                Khi học viên hoàn thành bài tập hoặc bài thi của khóa này, bài nộp sẽ hiển thị tại đây.
                            @endif
                        </p>
                        @if ($search !== '' || $hasActiveFilter)
                            <a class="sn-tg-empty-reset" href="{{ $gradingService->pageUrl($courseId, $activeTab) }}">Xem tất cả bài nộp</a>
                        @endif
                    </div>
                @else
                    <div class="sn-tg-list" role="list">
                        @foreach ($pageItems as $row)
                            @php
                                $rowAvatar = $row['student_avatar'] ?? null;
                                $rowInitials = (string) ($row['student_initials'] ?? 'HV');
                                $rowTone = (string) ($row['avatar_tone'] ?? 'orange');
                                $rowName = (string) ($row['student_name'] ?? 'Học viên');
                                $rowSubtitle = (string) ($row['subtitle'] ?? ($row['course_title'] ?? ''));
                                $rowType = (string) ($row['content_type_label'] ?? '');
                                $rowStatus = (string) ($row['status'] ?? 'pending');
                                $rowStatusLabel = (string) ($row['status_label'] ?? 'Chờ chấm');
                                $rowScore = (string) ($row['score_display'] ?? '—');
                                $rowUrl = (string) ($row['detail_url'] ?? '#');
                                $isMutedScore = $rowScore === '—' || $rowScore === '';
                            @endphp
                            <a class="sn-tg-row" href="{{ $rowUrl }}" role="listitem">
                                <span class="sn-tg-avatar sn-tg-avatar--{{ $rowTone }}">
                                    @if (is_string($rowAvatar) && $rowAvatar !== '')
                                        <img src="{{ $rowAvatar }}" alt="{{ $rowName }}" loading="lazy">
                                    @else
                                        <span>{{ $rowInitials }}</span>
                                    @endif
                                </span>

                                <div class="sn-tg-row-main">
                                    <div class="sn-tg-row-ident">
                                        <strong class="sn-tg-row-name">{{ $rowName }}</strong>
                                        @if ($rowSubtitle !== '')
                                            <span class="sn-tg-row-sub">{{ $rowSubtitle }}</span>
                                        @endif
                                    </div>
                                    @if ($rowType !== '')
                                        <span class="sn-tg-type-pill">{{ $rowType }}</span>
                                    @endif
                                </div>

                                <div class="sn-tg-row-right">
                                    <span class="sn-tg-status-pill sn-tg-status-pill--{{ $rowStatus }}">{{ $rowStatusLabel }}</span>
                                    <span class="sn-tg-score{{ $isMutedScore ? ' is-muted' : '' }}">{{ $rowScore }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                @php
                    $curPage = (int) ($pagination['current_page'] ?? 1);
                    $lastPage = (int) ($pagination['last_page'] ?? 1);
                    $totalRows = (int) ($pagination['total'] ?? 0);
                    $fromRow = (int) ($pagination['from'] ?? 0);
                    $toRow = (int) ($pagination['to'] ?? 0);
                    $pageWindow = is_array($pagination['pages'] ?? null) ? $pagination['pages'] : [1];
                @endphp
                <div class="sn-tg-pagination">
                    <span class="sn-tg-page-summary">
                        @if ($totalRows > 0)
                            Hiển thị {{ $fromRow }}–{{ $toRow }} / {{ $totalRows }} bài nộp
                        @else
                            Hiển thị 0 bài nộp
                        @endif
                    </span>

                    @if ($lastPage > 1)
                        <nav class="sn-tg-page-controls" aria-label="Phân trang danh sách bài nộp">
                            @if ($curPage > 1)
                                <a
                                    class="sn-tg-page-btn"
                                    href="{{ $gradingService->pageUrl($courseId, $activeTab, '', array_merge($filterQuery, ['page' => $curPage - 1])) }}"
                                    aria-label="Trang trước"
                                >
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                        <path d="M11.25 13.5L6.75 9L11.25 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                            @else
                                <span class="sn-tg-page-btn is-disabled" aria-disabled="true">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                        <path d="M11.25 13.5L6.75 9L11.25 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            @endif

                            @foreach ($pageWindow as $pItem)
                                @if ($pItem === '...')
                                    <span class="sn-tg-page-btn is-ellipsis" aria-hidden="true">…</span>
                                @elseif ((int) $pItem === $curPage)
                                    <span class="sn-tg-page-btn is-active" aria-current="page">{{ (int) $pItem }}</span>
                                @else
                                    <a
                                        class="sn-tg-page-btn"
                                        href="{{ $gradingService->pageUrl($courseId, $activeTab, '', array_merge($filterQuery, ['page' => (int) $pItem])) }}"
                                    >{{ (int) $pItem }}</a>
                                @endif
                            @endforeach

                            @if ($curPage < $lastPage)
                                <a
                                    class="sn-tg-page-btn"
                                    href="{{ $gradingService->pageUrl($courseId, $activeTab, '', array_merge($filterQuery, ['page' => $curPage + 1])) }}"
                                    aria-label="Trang sau"
                                >
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                        <path d="M6.75 13.5L11.25 9L6.75 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                            @else
                                <span class="sn-tg-page-btn is-disabled" aria-disabled="true">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                        <path d="M6.75 13.5L11.25 9L6.75 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            @endif
                        </nav>
                    @endif
                </div>
            </div>
        @elseif (! empty($submissionDetail['is_read_only']))
            {{-- Màn 3: Xem chi tiết kết quả đã chấm — Chỉ xem (#652:29958) --}}
            @include('teacher.partials.grading-submission-view', [
                'detail' => $submissionDetail,
                'activeTab' => $activeTab,
                'gradingService' => $gradingService,
            ])
        @else
            {{-- Màn 2: Chấm bài — 2 tab Chấm tay & Tự động (#909:123422 & #909:122552) --}}
            @include('teacher.partials.grading-submission-edit', [
                'detail' => $submissionDetail,
                'activeTab' => $activeTab,
                'gradingService' => $gradingService,
            ])
        @endif
    </section>
</div>
@endsection

@push('scripts')
    <script>
        document.body.dataset.tinymceBase = @json(asset('assets/tinymce'));
    </script>
    <script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('assets/js/assessment-comment-editor.js') }}?v=2"></script>
    <script src="{{ asset('assets/js/session-phat-am.js') }}?v=5"></script>
    <script src="{{ asset('assets/js/teacher-grading.js') }}?v=5"></script>
@endpush
