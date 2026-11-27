@extends('layouts.app', ['active' => 'courses'])

@php
    $courses = is_array($courses ?? null) ? $courses : [];
    $allCourses = is_array($allCourses ?? null) ? $allCourses : [];
    $levelTabs = is_array($levelTabs ?? null) ? $levelTabs : ['all' => 'Tất cả'];
    $activeLevel = (string) ($activeLevel ?? 'all');
    $search = (string) ($search ?? '');

    $searchPayload = array_map(static fn (array $c): array => [
        'id' => (int) ($c['id'] ?? 0),
        'title' => (string) ($c['title'] ?? ''),
        'status_label' => (string) ($c['status_label'] ?? 'Đang hoạt động'),
        'level_label' => (string) ($c['level_label'] ?? ''),
        'url' => (string) ($c['manage_url'] ?? '#'),
    ], $allCourses);
@endphp

@section('title', 'Quản lý khóa học')

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-course-manage.css') }}?v=2">
@endpush

@section('content')
<div class="sn-tc-page" id="snTcListPage" data-courses='@json($searchPayload)'>
    <section class="sn-tc-surface" aria-label="Danh sách khóa học">
        <header class="sn-tc-head">
            <h1 class="sn-tc-title">Quản lý khóa học</h1>
        </header>

        {{-- Thanh tìm kiếm khóa học kèm autocomplete dropdown (#602:58122) --}}
        <div class="sn-tc-search-wrap" id="snTcCourseSearchWrap">
            <form class="sn-tc-search-bar" method="get" action="{{ route('teacher.course.index') }}" role="search">
                @if ($activeLevel !== 'all')
                    <input type="hidden" name="trinh_do" value="{{ $activeLevel }}">
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
                    id="snTcCourseSearchInput"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Tìm kiếm khoá học"
                    aria-label="Tìm kiếm khoá học"
                    autocomplete="off"
                >
                <button
                    type="button"
                    class="sn-tc-search-clear"
                    id="snTcCourseSearchClear"
                    aria-label="Xóa từ khóa"
                    @if ($search === '') hidden @endif
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M9.17 14.83L14.83 9.17M14.83 14.83L9.17 9.17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>

            <div class="sn-tc-suggest-panel" id="snTcCourseSuggest" role="listbox" hidden></div>
        </div>

        {{-- Bộ lọc trình độ dạng pill ở giữa (#602:57584) --}}
        @if (count($levelTabs) > 1)
            <div class="sn-tc-tabs-center">
                <nav class="sn-tc-pill-tabs" role="tablist" aria-label="Lọc theo trình độ khóa học">
                    @foreach ($levelTabs as $lvKey => $lvLabel)
                        @php
                            $isOn = $activeLevel === $lvKey;
                            $query = array_filter([
                                'trinh_do' => $lvKey !== 'all' ? $lvKey : null,
                                'q' => $search !== '' ? $search : null,
                            ]);
                        @endphp
                        <a
                            href="{{ route('teacher.course.index', $query) }}"
                            class="sn-tc-pill-tab{{ $isOn ? ' is-active' : '' }}"
                            role="tab"
                            aria-selected="{{ $isOn ? 'true' : 'false' }}"
                        >{{ $lvLabel }}</a>
                    @endforeach
                </nav>
            </div>
        @endif

        {{-- Khung xám danh sách thẻ khóa học 2 cột (#572:48179) --}}
        <div class="sn-tc-well">
            @if ($courses === [])
                <div class="sn-tc-empty">
                    <span class="sn-tc-empty-ico" aria-hidden="true">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                            <path d="M4 19.5V6.5C4 4.5 5.5 3 7.5 3H16.5C18.5 3 20 4.5 20 6.5V19.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M4 17.5C4 16.1 5.1 15 6.5 15H20V19C20 20.4 18.9 21.5 17.5 21.5H6.5C5.1 21.5 4 20.4 4 19V17.5Z" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </span>
                    <strong class="sn-tc-empty-title">
                        {{ ($search !== '' || $activeLevel !== 'all') ? 'Không tìm thấy khóa học phù hợp' : 'Chưa có khóa học đang giảng dạy' }}
                    </strong>
                    <p class="sn-tc-empty-desc">
                        {{ ($search !== '' || $activeLevel !== 'all') ? 'Thử thay đổi từ khóa tìm kiếm hoặc chọn mục Tất cả.' : 'Khi quản trị viên phân công khóa học, danh sách lớp sẽ hiển thị tại đây.' }}
                    </p>
                    @if ($search !== '' || $activeLevel !== 'all')
                        <a class="sn-tc-btn-secondary" href="{{ route('teacher.course.index') }}">Xem tất cả khóa học</a>
                    @endif
                </div>
            @else
                <div class="sn-tc-course-grid">
                    @foreach ($courses as $course)
                        <article class="sn-tc-course-card">
                            <div class="sn-tc-course-card-head">
                                <h2 class="sn-tc-course-name">{{ $course['title'] }}</h2>
                                <span class="sn-tc-badge-status">{{ $course['status_label'] }}</span>
                            </div>

                            <div class="sn-tc-course-metrics">
                                <div class="sn-tc-metric-row">
                                    <div class="sn-tc-metric-pill">
                                        <span class="sn-tc-metric-ico is-orange" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M16.5 3H7.5C4.5 3 3.5 4 3.5 7V17C3.5 20 4.5 21 7.5 21H16.5C19.5 21 20.5 20 20.5 17V7C20.5 4 19.5 3 16.5 3Z" fill="currentColor" fill-opacity="0.18"/>
                                                <path d="M8 8H16M8 12H16M8 16H13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </span>
                                        <strong>{{ (int) $course['session_count'] }} Buổi</strong>
                                    </div>

                                    <div class="sn-tc-metric-pill">
                                        <span class="sn-tc-metric-ico is-blue" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 4.5C9.5 4.5 7.5 6.5 7.5 9C7.5 11.5 9.5 13.5 12 13.5C14.5 13.5 16.5 11.5 16.5 9C16.5 6.5 14.5 4.5 12 4.5Z" fill="currentColor"/>
                                                <path d="M5 19.5C5 16.7 8.1 15 12 15C15.9 15 19 16.7 19 19.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </span>
                                        <strong>{{ (int) $course['member_count'] }} Học viên</strong>
                                    </div>
                                </div>

                                <div class="sn-tc-metric-pill sn-tc-metric-pill--full">
                                    <span class="sn-tc-metric-ico is-purple" aria-hidden="true">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                            <path d="M7 3.5H15L19 7.5V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V5.5C5 4.4 5.9 3.5 7 3.5Z" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="1.8"/>
                                            <path d="M9 12H15M9 16H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                    </span>
                                    <strong>{{ $course['progress_text'] }}</strong>
                                </div>
                            </div>

                            <div class="sn-tc-course-card-foot">
                                <a class="sn-tc-cta-white" href="{{ $course['manage_url'] }}">
                                    <span>Xem khoá học</span>
                                    <span class="sn-tc-cta-arrow" aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                            <path d="M3.5 8H12.5M12.5 8L8.8 4.3M12.5 8L8.8 11.7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/teacher-course-manage.js') }}?v=1"></script>
@endpush
