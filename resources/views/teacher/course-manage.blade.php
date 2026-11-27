@extends('layouts.app', ['active' => 'courses'])

@php
    $course = $course ?? null;
    $courseId = (int) ($course?->id ?? 0);
    $activeTab = (string) ($activeTab ?? \App\Services\TeacherCourseManageService::TAB_INFO);
    $filterMode = (string) ($filterMode ?? \App\Services\TeacherCourseManageService::FILTER_WEEK);
    $sessions = is_array($sessions ?? null) ? $sessions : [];
    $selectedSession = is_array($selectedSession ?? null) ? $selectedSession : null;
    $selectedSessionId = (int) ($selectedSessionId ?? 0);
    $activeWeek = (string) ($activeWeek ?? '');
    $activeWeekFull = (string) ($activeWeekFull ?? '');
    $weekOptions = is_array($weekOptions ?? null) ? $weekOptions : [];
    $kpis = is_array($kpis ?? null) ? $kpis : [];
    $sessionMetrics = is_array($sessionMetrics ?? null) ? $sessionMetrics : [];
    $allStudentRows = is_array($allStudentRows ?? null) ? $allStudentRows : [];
    $pageStudents = is_array($pageStudents ?? null) ? $pageStudents : [];
    $pagination = is_array($pagination ?? null) ? $pagination : [
        'total' => 0, 'per_page' => 10, 'current_page' => 1, 'last_page' => 1, 'from' => 0, 'to' => 0, 'pages' => [1],
    ];
    $packageOptions = is_array($packageOptions ?? null) ? $packageOptions : ['all' => 'Tất cả'];
    $packageFilter = (string) ($packageFilter ?? 'all');
    $search = (string) ($search ?? '');
    $openStudentId = (int) ($openStudentId ?? 0);
    $canEnterRoom = ! empty($canEnterRoom);
    $roomLobbyUrl = $roomLobbyUrl ?? null;

    $baseStudentsQuery = array_filter([
        'tab' => \App\Services\TeacherCourseManageService::TAB_STUDENTS,
        'che_do' => $filterMode !== 'week' ? $filterMode : null,
        'tuan' => $activeWeek !== '' ? $activeWeek : null,
        'buoi' => $selectedSessionId > 0 ? $selectedSessionId : null,
        'loai' => $packageFilter !== 'all' ? $packageFilter : null,
        'q' => $search !== '' ? $search : null,
    ], static fn ($v) => $v !== null && $v !== '');

    $studentSearchPayload = array_map(static fn (array $r): array => [
        'user_id' => (int) ($r['user_id'] ?? 0),
        'name' => (string) ($r['name'] ?? ''),
        'student_code' => (string) ($r['student_code'] ?? ''),
        'avatar' => $r['avatar'] ?? null,
        'initials' => (string) ($r['initials'] ?? 'HV'),
        'avatar_tone' => (string) ($r['avatar_tone'] ?? 'blue'),
        'tag_label' => (string) ($r['tag_label'] ?? ''),
        'tag_tone' => (string) ($r['tag_tone'] ?? 'purple'),
        'drawer_url' => (string) ($r['drawer_url'] ?? ''),
    ], $allStudentRows);

    $openStudentUrl = '';
    if ($openStudentId > 0) {
        foreach ($allStudentRows as $sr) {
            if ((int) ($sr['user_id'] ?? 0) === $openStudentId) {
                $openStudentUrl = (string) ($sr['drawer_url'] ?? '');
                break;
            }
        }
    }

    $sessionJumpUrl = route('teacher.course.show', array_filter([
        'id' => $courseId,
        'tab' => \App\Services\TeacherCourseManageService::TAB_STUDENTS,
        'che_do' => \App\Services\TeacherCourseManageService::FILTER_SESSION,
        'buoi' => $selectedSessionId > 0 ? $selectedSessionId : null,
        'tuan' => $activeWeek !== '' ? $activeWeek : null,
    ]));
@endphp

@section('title', ($course?->title ?? 'Khóa học').' · Quản lý khóa học')

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-course-manage.css') }}?v=2">
@endpush

@section('content')
<div
    class="sn-tc-page"
    id="snTcDetailPage"
    data-course-id="{{ $courseId }}"
    data-active-week="{{ $activeWeek }}"
    data-selected-session="{{ $selectedSessionId }}"
    data-filter-mode="{{ $filterMode }}"
    data-students='@json($studentSearchPayload)'
    data-auto-open-drawer="{{ $openStudentUrl }}"
>
    <section class="sn-tc-surface sn-tc-surface--detail" aria-label="Chi tiết khóa học">
        {{-- Đầu trang: nút tròn quay lại + Tên khóa học (#582:26813) --}}
        <header class="sn-tc-detail-head">
            <a
                class="sn-tc-back-circle"
                href="{{ route('teacher.course.index') }}"
                aria-label="Quay lại danh sách khóa học"
                title="Danh sách khóa học"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M15.833 10H4.167M4.167 10L9.167 5M4.167 10L9.167 15" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <h1 class="sn-tc-title">{{ $course?->title ?? 'Khóa học' }}</h1>
        </header>

        {{-- Cụm 2 tab ở giữa: Thông tin lớp / Danh sách học sinh (#572:48654) --}}
        <div class="sn-tc-tabs-center">
            <nav class="sn-tc-pill-tabs" role="tablist" aria-label="Mục quản lý lớp học">
                <a
                    href="{{ route('teacher.course.show', array_filter(['id' => $courseId, 'tab' => 'thong-tin', 'buoi' => $selectedSessionId > 0 ? $selectedSessionId : null, 'tuan' => $activeWeek !== '' ? $activeWeek : null])) }}"
                    class="sn-tc-pill-tab{{ $activeTab === 'thong-tin' ? ' is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $activeTab === 'thong-tin' ? 'true' : 'false' }}"
                >Thông tin lớp</a>
                <a
                    href="{{ route('teacher.course.show', array_filter(['id' => $courseId, 'tab' => 'hoc-sinh', 'tuan' => $activeWeek !== '' ? $activeWeek : null, 'buoi' => $selectedSessionId > 0 ? $selectedSessionId : null])) }}"
                    class="sn-tc-pill-tab{{ $activeTab === 'hoc-sinh' ? ' is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $activeTab === 'hoc-sinh' ? 'true' : 'false' }}"
                >Danh sách học sinh</a>
            </nav>
        </div>

        @if ($activeTab === \App\Services\TeacherCourseManageService::TAB_INFO)
            {{-- =====================================================================
                 TAB 1: THÔNG TIN LỚP (#570:47489)
                 ===================================================================== --}}
            <div class="sn-tc-info-body">
                {{-- 4 thẻ KPI tổng quan (#575:48741) --}}
                <div class="sn-tc-kpi-grid">
                    <div class="sn-tc-kpi-card">
                        <div class="sn-tc-kpi-label">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <rect x="2.5" y="3.5" width="15" height="14" rx="3" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M6.5 2V5M13.5 2V5M2.5 8H17.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                            <span>Buổi học</span>
                        </div>
                        <strong class="sn-tc-kpi-val">{{ (int) ($kpis['completed_sessions'] ?? 0) }}/{{ (int) ($kpis['total_sessions'] ?? 0) }}</strong>
                        <span class="sn-tc-kpi-sub">Đã hoàn thành</span>
                    </div>

                    <a
                        class="sn-tc-kpi-card sn-tc-kpi-card--link"
                        href="{{ route('teacher.course.show', ['id' => $courseId, 'tab' => 'hoc-sinh', 'tuan' => $activeWeek]) }}"
                    >
                        <div class="sn-tc-kpi-label">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <circle cx="8" cy="7" r="3" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M3 16.5C3 14 5.2 12.5 8 12.5C10.8 12.5 13 14 13 16.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M13.5 5.5C15 5.5 16.2 6.7 16.2 8.2C16.2 9.4 15.4 10.4 14.3 10.7M17.5 16.5C17.5 14.6 16.3 13.3 14.5 12.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                            <span>Học sinh</span>
                        </div>
                        <strong class="sn-tc-kpi-val">{{ (int) ($kpis['member_count'] ?? 0) }}</strong>
                        <span class="sn-tc-kpi-sub">Đang theo học</span>
                    </a>

                    <a class="sn-tc-kpi-card sn-tc-kpi-card--link" href="{{ $gradingUrl ?? route('teacher.grading', ['khoa' => $courseId]) }}">
                        <div class="sn-tc-kpi-label">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M7 10.2L9.1 12.3L13.4 7.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Bài đang chờ chấm</span>
                        </div>
                        <strong class="sn-tc-kpi-val is-orange">{{ (int) ($kpis['pending_grading_count'] ?? 0) }}</strong>
                        <span class="sn-tc-kpi-sub">Mở Chấm điểm để xử lý</span>
                    </a>

                    <a class="sn-tc-kpi-card sn-tc-kpi-card--link" href="{{ $evaluationUrl ?? route('teacher.evaluations', ['khoa' => $courseId]) }}">
                        <div class="sn-tc-kpi-label">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M10 6V10.2L12.8 11.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Cần đánh giá tuần</span>
                        </div>
                        <strong class="sn-tc-kpi-val is-orange">{{ (int) ($kpis['pending_eval_count'] ?? 0) }}</strong>
                        <span class="sn-tc-kpi-sub">{{ $kpis['eval_week_label'] ?? '' }}</span>
                    </a>
                </div>

                {{-- Khối Theo buổi học (#582:26786) --}}
                <section class="sn-tc-session-panel" aria-label="Tiến độ theo buổi học">
                    <div class="sn-tc-session-panel-head">
                        <div class="sn-tc-session-panel-copy">
                            <h2 class="sn-tc-panel-title">Theo buổi học</h2>
                            <p class="sn-tc-panel-sub">Buổi đang chọn sẽ được giữ khi mở tiến độ từng buổi.</p>
                        </div>

                        @if ($sessions !== [])
                            <div class="sn-tc-dd" data-tc-dd>
                                <button type="button" class="sn-tc-dd-trigger sn-tc-dd-trigger--session" data-tc-dd-btn aria-expanded="false">
                                    <span class="sn-tc-dd-trigger-copy">
                                        <small>Buổi học</small>
                                        <strong>{{ $selectedSession['short_label'] ?? 'Chọn buổi học' }}</strong>
                                    </span>
                                    <svg class="sn-tc-dd-chev" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                        <path d="M4.5 6.75L9 11.25L13.5 6.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>

                                <div class="sn-tc-dd-menu sn-tc-dd-menu--right" data-tc-dd-menu hidden>
                                    <div class="sn-tc-dd-head">Danh sách buổi học</div>
                                    <div class="sn-tc-dd-list">
                                        @foreach ($sessions as $sItem)
                                            @php $isSel = (int) ($sItem['id'] ?? 0) === $selectedSessionId; @endphp
                                            <a
                                                class="sn-tc-dd-opt{{ $isSel ? ' is-selected' : '' }}"
                                                href="{{ route('teacher.course.show', ['id' => $courseId, 'tab' => 'thong-tin', 'buoi' => (int) $sItem['id']]) }}"
                                            >
                                                <small>Buổi học</small>
                                                <strong>{{ $sItem['short_label'] }}</strong>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="sn-tc-session-cols">
                        {{-- Cột trái: Thông tin buổi học --}}
                        <div class="sn-tc-session-col">
                            <h3 class="sn-tc-col-title">Thông tin</h3>

                            <a class="sn-tc-info-tile" href="{{ $sessionJumpUrl }}">
                                <span class="sn-tc-tile-ico is-blue" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <circle cx="9" cy="6.5" r="3" stroke="currentColor" stroke-width="1.6"/>
                                        <path d="M3.5 16C3.5 13.5 5.8 12 9 12C10.4 12 11.6 12.3 12.5 12.9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        <path d="M13 15.2L14.5 16.7L17.5 13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span class="sn-tc-tile-copy">
                                    <small>Điểm danh</small>
                                    <strong>{{ (int) ($sessionMetrics['attendance_recorded'] ?? 0) }}/{{ (int) ($sessionMetrics['member_count'] ?? 0) }} đã ghi</strong>
                                </span>
                            </a>

                            <div class="sn-tc-tile-pair">
                                <a class="sn-tc-info-tile" href="{{ $sessionJumpUrl }}">
                                    <span class="sn-tc-tile-ico is-blue" aria-hidden="true">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                            <rect x="3.5" y="2.5" width="13" height="15" rx="2.5" stroke="currentColor" stroke-width="1.6"/>
                                            <path d="M7 7H13M7 10.5H13M7 14H10.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        </svg>
                                    </span>
                                    <span class="sn-tc-tile-copy">
                                        <small>Bài tập trước buổi</small>
                                        <strong>
                                            @if (! empty($sessionMetrics['pre_has_questions']))
                                                Đã nộp {{ (int) ($sessionMetrics['pre_submitted'] ?? 0) }}/{{ (int) ($sessionMetrics['member_count'] ?? 0) }}
                                            @else
                                                Không có bài
                                            @endif
                                        </strong>
                                    </span>
                                </a>

                                <a class="sn-tc-info-tile" href="{{ $sessionJumpUrl }}">
                                    <span class="sn-tc-tile-ico is-blue" aria-hidden="true">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                            <path d="M10 4.5C8.2 3.3 5.5 3 3.5 3.5V15.5C5.5 15 8.2 15.3 10 16.5M10 4.5C11.8 3.3 14.5 3 16.5 3.5V15.5C14.5 15 11.8 15.3 10 16.5M10 4.5V16.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <span class="sn-tc-tile-copy">
                                        <small>Bài tập sau buổi</small>
                                        <strong>
                                            @if (! empty($sessionMetrics['post_has_questions']))
                                                Đã nộp {{ (int) ($sessionMetrics['post_submitted'] ?? 0) }}/{{ (int) ($sessionMetrics['member_count'] ?? 0) }}
                                            @else
                                                Không có bài
                                            @endif
                                        </strong>
                                    </span>
                                </a>
                            </div>

                            @if ($canEnterRoom && $roomLobbyUrl)
                                <a class="sn-tc-zoom-banner" href="{{ $roomLobbyUrl }}">
                                    <span class="sn-tc-zoom-dot" aria-hidden="true"></span>
                                    <span>Phòng học Zoom đang mở · <strong>Vào phòng chờ ngay</strong></span>
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <path d="M6 3.5L10.5 8L6 12.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                            @endif
                        </div>

                        {{-- Cột phải: Việc cần xử lý --}}
                        <div class="sn-tc-session-col">
                            <h3 class="sn-tc-col-title">Việc cần xử lý</h3>

                            <a class="sn-tc-action-tile" href="{{ $gradingUrl ?? route('teacher.grading', ['khoa' => $courseId, 'trang_thai' => 'pending']) }}">
                                <span class="sn-tc-tile-ico is-blue" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M10 4.5C8.2 3.3 5.5 3 3.5 3.5V15.5C5.5 15 8.2 15.3 10 16.5M10 4.5C11.8 3.3 14.5 3 16.5 3.5V15.5C14.5 15 11.8 15.3 10 16.5M10 4.5V16.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span class="sn-tc-tile-copy">
                                    <strong>Bài kiểm tra đang chờ chấm</strong>
                                    <small>{{ $kpis['pending_grading_subtitle'] ?? 'Mở Chấm điểm để xử lý' }}</small>
                                </span>
                                <span class="sn-tc-action-badge">{{ (int) ($kpis['pending_grading_count'] ?? 0) }} bài</span>
                                <svg class="sn-tc-action-chev" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                    <path d="M6.75 4.5L11.25 9L6.75 13.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>

                            <a class="sn-tc-action-tile" href="{{ $evaluationUrl ?? route('teacher.evaluations', ['khoa' => $courseId, 'tuan' => $activeWeek]) }}">
                                <span class="sn-tc-tile-ico is-green" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <circle cx="7.5" cy="7" r="2.7" stroke="currentColor" stroke-width="1.6"/>
                                        <path d="M3 16C3 13.6 5 12.2 7.5 12.2C10 12.2 12 13.6 12 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        <path d="M13.5 7H17.5M13.5 10.5H17.5M13.5 14H16.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <span class="sn-tc-tile-copy">
                                    <strong>Học viên cần đánh giá tuần</strong>
                                    <small>{{ $kpis['eval_week_full_label'] ?? '' }}</small>
                                </span>
                                <span class="sn-tc-action-badge">{{ (int) ($kpis['pending_eval_count'] ?? 0) }} học viên</span>
                                <svg class="sn-tc-action-chev" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                    <path d="M6.75 4.5L11.25 9L6.75 13.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        @else
            {{-- =====================================================================
                 TAB 2: DANH SÁCH HỌC SINH (#1443:35213 / #591:51284 / #602:55351)
                 ===================================================================== --}}
            <div class="sn-tc-students-body">
                {{-- Thanh công cụ 3 thành phần: Tìm kiếm · Loại khóa · Tuần/Buổi học --}}
                <div class="sn-tc-toolbar">
                    <div class="sn-tc-search-wrap sn-tc-search-wrap--student" id="snTcStudentSearchWrap">
                        <form class="sn-tc-search-bar" method="get" action="{{ route('teacher.course.show', ['id' => $courseId]) }}" role="search">
                            <input type="hidden" name="tab" value="hoc-sinh">
                            @if ($filterMode !== 'week')
                                <input type="hidden" name="che_do" value="{{ $filterMode }}">
                            @endif
                            @if ($activeWeek !== '')
                                <input type="hidden" name="tuan" value="{{ $activeWeek }}">
                            @endif
                            @if ($selectedSessionId > 0)
                                <input type="hidden" name="buoi" value="{{ $selectedSessionId }}">
                            @endif
                            @if ($packageFilter !== 'all')
                                <input type="hidden" name="loai" value="{{ $packageFilter }}">
                            @endif

                            <span class="sn-tc-search-ico" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <circle cx="11.5" cy="11.5" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M21 21L18.2 18.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input
                                type="search"
                                class="sn-tc-search-input sn-search-input-bare"
                                id="snTcStudentSearchInput"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Tìm kiếm học viên"
                                aria-label="Tìm kiếm học viên"
                                autocomplete="off"
                            >
                            <button
                                type="button"
                                class="sn-tc-search-clear"
                                id="snTcStudentSearchClear"
                                aria-label="Xóa từ khóa"
                                @if ($search === '') hidden @endif
                            >
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M9.17 14.83L14.83 9.17M14.83 14.83L9.17 9.17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </form>

                        <div class="sn-tc-suggest-panel sn-tc-suggest-panel--students" id="snTcStudentSuggest" role="listbox" hidden></div>
                    </div>

                    {{-- Dropdown Loại khóa (#953:132173) --}}
                    <div class="sn-tc-dd" data-tc-dd>
                        <button type="button" class="sn-tc-dd-trigger" data-tc-dd-btn aria-expanded="false">
                            <span class="sn-tc-dd-trigger-copy">
                                <small>Loại khóa</small>
                                <strong>{{ $packageOptions[$packageFilter] ?? 'Tất cả' }}</strong>
                            </span>
                            <svg class="sn-tc-dd-chev" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                <path d="M4.5 6.75L9 11.25L13.5 6.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>

                        <div class="sn-tc-dd-menu" data-tc-dd-menu hidden>
                            <div class="sn-tc-dd-list">
                                @foreach ($packageOptions as $pkKey => $pkLabel)
                                    @php
                                        $pkQuery = array_merge($baseStudentsQuery, ['loai' => $pkKey !== 'all' ? $pkKey : null, 'page' => null]);
                                        $pkQuery = array_filter($pkQuery, static fn ($v) => $v !== null && $v !== '');
                                    @endphp
                                    <a
                                        class="sn-tc-dd-opt sn-tc-dd-opt--simple{{ $packageFilter === $pkKey ? ' is-selected' : '' }}"
                                        href="{{ route('teacher.course.show', array_merge(['id' => $courseId], $pkQuery)) }}"
                                    >{{ $pkLabel }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Dropdown Tuần / Buổi học (#602:55350 & #591:51284) --}}
                    <div class="sn-tc-dd" data-tc-dd>
                        <button type="button" class="sn-tc-dd-trigger{{ $filterMode === 'session' ? ' is-accent' : '' }}" data-tc-dd-btn aria-expanded="false">
                            <span class="sn-tc-dd-trigger-copy">
                                <small>{{ $filterMode === 'session' ? 'Buổi học' : 'Tuần' }}</small>
                                <strong>{{ $filterMode === 'session' ? ($selectedSession['short_label'] ?? 'Chọn buổi') : $activeWeekFull }}</strong>
                            </span>
                            <svg class="sn-tc-dd-chev" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                <path d="M4.5 6.75L9 11.25L13.5 6.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>

                        <div class="sn-tc-dd-menu sn-tc-dd-menu--right sn-tc-dd-menu--wide" data-tc-dd-menu hidden>
                            <div class="sn-tc-dd-mode-tabs" role="tablist">
                                <button type="button" class="sn-tc-dd-mode-btn{{ $filterMode === 'week' ? ' is-active' : '' }}" data-dd-mode-tab="week">Theo tuần</button>
                                <button type="button" class="sn-tc-dd-mode-btn{{ $filterMode === 'session' ? ' is-active' : '' }}" data-dd-mode-tab="session">Theo buổi học</button>
                            </div>

                            <div class="sn-tc-dd-list" data-dd-mode-pane="week" @if ($filterMode !== 'week') hidden @endif>
                                @foreach ($weekOptions as $wk)
                                    @php
                                        $wkQuery = array_merge($baseStudentsQuery, ['che_do' => 'week', 'tuan' => $wk['key'], 'page' => null]);
                                        $wkQuery = array_filter($wkQuery, static fn ($v) => $v !== null && $v !== '');
                                    @endphp
                                    <a
                                        class="sn-tc-dd-opt{{ $filterMode === 'week' && $activeWeek === $wk['key'] ? ' is-selected' : '' }}"
                                        href="{{ route('teacher.course.show', array_merge(['id' => $courseId], $wkQuery)) }}"
                                    >
                                        <small>Tuần{{ (int) ($wk['session_count'] ?? 0) > 0 ? ' · '.(int) $wk['session_count'].' buổi' : '' }}</small>
                                        <strong>{{ $wk['label'] }}</strong>
                                    </a>
                                @endforeach
                            </div>

                            <div class="sn-tc-dd-list" data-dd-mode-pane="session" @if ($filterMode !== 'session') hidden @endif>
                                @forelse ($sessions as $sItem)
                                    @php
                                        $sQuery = array_merge($baseStudentsQuery, [
                                            'che_do' => 'session',
                                            'buoi' => (int) $sItem['id'],
                                            'tuan' => ($sItem['week_key'] ?? '') !== '' ? $sItem['week_key'] : $activeWeek,
                                            'page' => null,
                                        ]);
                                        $sQuery = array_filter($sQuery, static fn ($v) => $v !== null && $v !== '');
                                    @endphp
                                    <a
                                        class="sn-tc-dd-opt{{ $filterMode === 'session' && $selectedSessionId === (int) $sItem['id'] ? ' is-selected' : '' }}"
                                        href="{{ route('teacher.course.show', array_merge(['id' => $courseId], $sQuery)) }}"
                                    >
                                        <small>Buổi học</small>
                                        <strong>{{ $sItem['short_label'] }}</strong>
                                    </a>
                                @empty
                                    <p class="sn-tc-dd-empty">Khóa học chưa có buổi học nào.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bảng Danh sách học viên (#1443:35229) --}}
                <div class="sn-tc-table-card">
                    @if ($pageStudents === [])
                        <div class="sn-tc-empty sn-tc-empty--compact">
                            <strong class="sn-tc-empty-title">Không có học viên phù hợp</strong>
                            <p class="sn-tc-empty-desc">
                                {{ ($search !== '' || $packageFilter !== 'all') ? 'Thử thay đổi từ khóa tìm kiếm hoặc đặt lại bộ lọc.' : 'Khóa học hiện chưa có học viên đang theo học.' }}
                            </p>
                            @if ($search !== '' || $packageFilter !== 'all')
                                <a class="sn-tc-btn-secondary" href="{{ route('teacher.course.show', ['id' => $courseId, 'tab' => 'hoc-sinh', 'tuan' => $activeWeek]) }}">Xem tất cả học viên</a>
                            @endif
                        </div>
                    @else
                        <div class="sn-tc-table-scroll">
                            <table class="sn-tc-table">
                                <thead>
                                    <tr>
                                        <th>Học viên</th>
                                        <th>BTVN trước</th>
                                        <th>BTVN sau</th>
                                        <th>Bài kiểm tra</th>
                                        <th>Chuyên cần</th>
                                        <th>Đánh giá</th>
                                        <th class="is-right"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pageStudents as $row)
                                        <tr data-student-row="{{ (int) $row['user_id'] }}">
                                            <td>
                                                <button
                                                    type="button"
                                                    class="sn-tc-student-cell"
                                                    data-open-drawer="{{ $row['drawer_url'] }}"
                                                    data-student-id="{{ (int) $row['user_id'] }}"
                                                >
                                                    <span class="sn-tc-avatar sn-tc-avatar--{{ $row['avatar_tone'] }}">
                                                        @if (! empty($row['avatar']))
                                                            <img src="{{ $row['avatar'] }}" alt="{{ $row['name'] }}" loading="lazy">
                                                        @else
                                                            <span>{{ $row['initials'] }}</span>
                                                        @endif
                                                    </span>
                                                    <span class="sn-tc-student-meta">
                                                        <strong class="sn-tc-student-link">{{ $row['name'] }}</strong>
                                                        <span class="sn-tc-prog-tag is-{{ $row['tag_tone'] }}">{{ $row['tag_label'] }}</span>
                                                    </span>
                                                </button>
                                            </td>

                                            @foreach (['pre', 'post', 'test'] as $colKey)
                                                @php $cell = $row[$colKey] ?? ['mode' => 'week', 'text' => '—', 'tone' => 'muted']; @endphp
                                                <td>
                                                    @if ($cell['tone'] === 'done-text')
                                                        <span class="sn-tc-status-text is-done">{{ $cell['text'] }}</span>
                                                    @elseif ($cell['tone'] === 'missing-text')
                                                        <span class="sn-tc-status-text is-missing">{{ $cell['text'] }}</span>
                                                    @elseif ($cell['tone'] === 'muted')
                                                        <span class="sn-tc-status-text is-muted">—</span>
                                                    @else
                                                        <span class="sn-tc-count-pill is-{{ $cell['tone'] }}">{{ $cell['text'] }}</span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            @php $attCell = $row['attendance'] ?? ['text' => '—', 'tone' => 'gray']; @endphp
                                            <td>
                                                <span class="sn-tc-count-pill is-{{ $attCell['tone'] }}" data-row-att-pill>{{ $attCell['text'] }}</span>
                                            </td>

                                            <td>
                                                <span
                                                    class="sn-tc-eval-text {{ ! empty($row['evaluated']) ? 'is-done' : 'is-pending' }}"
                                                    data-row-eval-text
                                                >{{ $row['eval_label'] }}</span>
                                            </td>

                                            <td class="is-right">
                                                <button
                                                    type="button"
                                                    class="sn-tc-view-btn"
                                                    data-open-drawer="{{ $row['drawer_url'] }}"
                                                    data-student-id="{{ (int) $row['user_id'] }}"
                                                >Xem</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Phân trang (#1443:35241) --}}
                @php
                    $curPage = (int) ($pagination['current_page'] ?? 1);
                    $lastPage = (int) ($pagination['last_page'] ?? 1);
                    $totalRows = (int) ($pagination['total'] ?? 0);
                    $fromRow = (int) ($pagination['from'] ?? 0);
                    $toRow = (int) ($pagination['to'] ?? 0);
                    $pageWindow = is_array($pagination['pages'] ?? null) ? $pagination['pages'] : [1];
                @endphp
                <div class="sn-tc-pagination">
                    <span class="sn-tc-page-summary">
                        @if ($totalRows > 0)
                            Hiển thị {{ $fromRow }}–{{ $toRow }} / {{ $totalRows }} học viên
                        @else
                            Hiển thị 0 học viên
                        @endif
                    </span>

                    @if ($lastPage > 1)
                        <nav class="sn-tc-page-controls" aria-label="Phân trang danh sách học viên">
                            @if ($curPage > 1)
                                <a class="sn-tc-page-btn" href="{{ route('teacher.course.show', array_merge(['id' => $courseId], $baseStudentsQuery, ['page' => $curPage - 1])) }}" aria-label="Trang trước">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            @else
                                <span class="sn-tc-page-btn is-disabled" aria-disabled="true">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            @endif

                            @foreach ($pageWindow as $pItem)
                                @if ($pItem === '...')
                                    <span class="sn-tc-page-btn is-ellipsis" aria-hidden="true">…</span>
                                @elseif ((int) $pItem === $curPage)
                                    <span class="sn-tc-page-btn is-active" aria-current="page">{{ (int) $pItem }}</span>
                                @else
                                    <a class="sn-tc-page-btn" href="{{ route('teacher.course.show', array_merge(['id' => $courseId], $baseStudentsQuery, ['page' => (int) $pItem])) }}">{{ (int) $pItem }}</a>
                                @endif
                            @endforeach

                            @if ($curPage < $lastPage)
                                <a class="sn-tc-page-btn" href="{{ route('teacher.course.show', array_merge(['id' => $courseId], $baseStudentsQuery, ['page' => $curPage + 1])) }}" aria-label="Trang sau">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            @else
                                <span class="sn-tc-page-btn is-disabled" aria-disabled="true">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            @endif
                        </nav>
                    @endif
                </div>
            </div>
        @endif
    </section>

    {{-- Backdrop riêng khi mở autocomplete tìm kiếm học viên (#602:55261) --}}
    <div class="sn-tc-search-dim" id="snTcSearchDim" hidden></div>

    {{-- Màn 3: Slide-over Student Drawer + Modal Xác nhận đặc cách + Toast --}}
    @include('teacher.partials.course-student-drawer', [
        'scoreLabels' => $scoreLabels ?? \App\Models\CourseMonthlyEvaluation::scoreLabels(),
    ])
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/teacher-course-manage.js') }}?v=1"></script>
@endpush
