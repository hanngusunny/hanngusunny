@extends('layouts.app', ['active' => 'home'])

@php
    $courses = $courses ?? [];
    $joinableSessions = $joinableSessions ?? [];
    $nearestUpcomingSession = $nearestUpcomingSession ?? null;
    $phaseLabels = $phaseLabels ?? [];
    $featureSession = $joinableSessions[0] ?? $nearestUpcomingSession;
    $firstCourseUrl = $courses[0]['manage_url'] ?? null;
@endphp

@section('title', 'Tổng quan')

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-overview.css') }}?v=1">
@endpush

@section('content')
<div class="sn-td-page">
    <div class="sn-td-surface">
        {{-- Khối 1: Buổi dạy sắp tới (Figma #602:61023) --}}
        <section class="sn-td-section" aria-labelledby="snTdUpcomingHeading">
            <div class="sn-td-section-head">
                <h1 class="sn-td-section-title" id="snTdUpcomingHeading">Buổi dạy sắp tới</h1>
                <a class="sn-td-text-link" href="{{ route('teacher.schedule') }}">
                    <span>Xem lịch dạy</span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M13.5 4.5L21 12L13.5 19.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3 12L21 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>

            @if ($featureSession !== null)
                @include('teacher.partials.session-feature-card', [
                    'session' => $featureSession,
                    'phaseLabels' => $phaseLabels,
                ])
            @else
                <div class="sn-td-empty-box">
                    <span class="sn-td-empty-ico" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M6.66669 1.66663V4.16663" stroke="#F26500" stroke-width="1.25" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.3333 1.66663V4.16663" stroke="#F26500" stroke-width="1.25" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2.91669 7.57495H17.0834" stroke="#F26500" stroke-width="1.25" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17.5 7.08329V14.1666C17.5 16.6666 16.25 18.3333 13.3333 18.3333H6.66667C3.75 18.3333 2.5 16.6666 2.5 14.1666V7.08329C2.5 4.58329 3.75 2.91663 6.66667 2.91663H13.3333C16.25 2.91663 17.5 4.58329 17.5 7.08329Z" stroke="#F26500" stroke-width="1.25" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <p class="sn-td-empty-title">Chưa có buổi dạy sắp tới</p>
                    <p class="sn-td-empty-desc">Các buổi dạy đã lên lịch trong khóa học phụ trách sẽ hiển thị tại đây.</p>
                </div>
            @endif
        </section>

        {{-- Khối 2: Quản lý khóa học (Figma #602:61040) --}}
        <section class="sn-td-section" id="teacher-courses" aria-labelledby="snTdCoursesHeading">
            <div class="sn-td-section-head">
                <h2 class="sn-td-section-title" id="snTdCoursesHeading">Quản lý khóa học</h2>
                <a class="sn-td-text-link" href="{{ route('teacher.course.index') }}">
                    <span>Xem tất cả khóa học</span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M13.5 4.5L21 12L13.5 19.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3 12L21 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>

            @if ($courses === [])
                <div class="sn-td-empty-box">
                    <span class="sn-td-empty-ico" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="none">
                            <path d="M18.3334 13.95V3.89162C18.3334 2.89162 17.5167 2.14995 16.525 2.23329H16.475C14.725 2.38329 12.0667 3.27495 10.5834 4.20829L10.4417 4.29995C10.2 4.44995 9.80002 4.44995 9.55835 4.29995L9.35002 4.17495C7.86669 3.24995 5.21669 2.36662 3.46669 2.22495C2.47502 2.14162 1.66669 2.89162 1.66669 3.88329V13.95C1.66669 14.75 2.31669 15.5 3.11669 15.6L3.35835 15.6333C5.16669 15.875 7.95835 16.7916 9.55835 17.6666L9.59169 17.6833C9.81669 17.8083 10.175 17.8083 10.3917 17.6833C11.9917 16.8 14.7917 15.875 16.6084 15.6333L16.8834 15.6C17.6834 15.5 18.3334 14.75 18.3334 13.95Z" stroke="#F26500" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M10 4.57495V17.075" stroke="#F26500" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <p class="sn-td-empty-title">Chưa có khóa học nào được phân công</p>
                    <p class="sn-td-empty-desc">Khi quản trị viên phân công lớp giảng dạy, danh sách khóa học sẽ hiển thị tại đây.</p>
                </div>
            @else
                <div class="sn-td-courses-grid">
                    @foreach ($courses as $course)
                        @php
                            $isActive = ($course['status'] ?? '') === 'active';
                            $levelTitle = trim((string) ($course['level_title'] ?? ''));
                            $memberCount = (int) ($course['member_count'] ?? 0);
                            $sessionCount = (int) ($course['session_count'] ?? 0);
                            $currentSessionNo = (int) ($course['current_session_number'] ?? 0);
                            $metaParts = array_values(array_filter([
                                $levelTitle !== '' ? $levelTitle : null,
                                "{$memberCount} học viên",
                                $sessionCount > 0 ? "Buổi {$currentSessionNo}/{$sessionCount}" : 'Chưa có buổi học',
                            ]));
                        @endphp
                        <article class="sn-td-course-card">
                            <div class="sn-td-course-body">
                                <div class="sn-td-course-top">
                                    <div class="sn-td-course-id">
                                        <span class="sn-td-course-ico" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M18.3334 13.95V3.89162C18.3334 2.89162 17.5167 2.14995 16.525 2.23329H16.475C14.725 2.38329 12.0667 3.27495 10.5834 4.20829L10.4417 4.29995C10.2 4.44995 9.80002 4.44995 9.55835 4.29995L9.35002 4.17495C7.86669 3.24995 5.21669 2.36662 3.46669 2.22495C2.47502 2.14162 1.66669 2.89162 1.66669 3.88329V13.95C1.66669 14.75 2.31669 15.5 3.11669 15.6L3.35835 15.6333C5.16669 15.875 7.95835 16.7916 9.55835 17.6666L9.59169 17.6833C9.81669 17.8083 10.175 17.8083 10.3917 17.6833C11.9917 16.8 14.7917 15.875 16.6084 15.6333L16.8834 15.6C17.6834 15.5 18.3334 14.75 18.3334 13.95Z" stroke="#F26500" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M10 4.57495V17.075" stroke="#F26500" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M6.45831 7.07495H4.58331" stroke="#F26500" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M7.08331 9.57495H4.58331" stroke="#F26500" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        <h3 class="sn-td-course-title">{{ $course['title'] ?? '' }}</h3>
                                    </div>

                                    <span class="sn-td-course-status{{ $isActive ? '' : ' is-muted' }}">
                                        {{ $isActive ? 'Đang hoạt động' : ($course['status'] ?? 'Tạm dừng') }}
                                    </span>
                                </div>

                                <p class="sn-td-course-meta">{{ implode(' · ', $metaParts) }}</p>
                            </div>

                            <a class="sn-td-cta sn-td-cta--secondary sn-td-cta--full" href="{{ $course['manage_url'] }}">
                                <span>Xem chi tiết</span>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM16.03 12.53L13.03 15.53C12.88 15.68 12.69 15.75 12.5 15.75C12.31 15.75 12.12 15.68 11.97 15.53C11.68 15.24 11.68 14.76 11.97 14.47L13.69 12.75H8.5C8.09 12.75 7.75 12.41 7.75 12C7.75 11.59 8.09 11.25 8.5 11.25H13.69L11.97 9.53C11.68 9.24 11.68 8.76 11.97 8.47C12.26 8.18 12.74 8.18 13.03 8.47L16.03 11.47C16.32 11.76 16.32 12.24 16.03 12.53Z" fill="currentColor"/>
                                </svg>
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
