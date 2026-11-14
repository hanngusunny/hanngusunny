@extends('layouts.app', ['active' => 'home'])

@php
    $courses = $courses ?? [];
    $joinableSessions = $joinableSessions ?? [];
    $nearestUpcomingSession = $nearestUpcomingSession ?? null;
    $phaseLabels = $phaseLabels ?? [];
    $featureSession = $joinableSessions[0] ?? $nearestUpcomingSession;
@endphp

@section('title', 'Tổng quan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/legacy-overview.css') }}?v=1">
@endpush

@section('content')
<div class="td-legacy">
    <div class="app-page-title-bar">
        <div class="app-page-title-main">
            <header class="student-overview-page-title" aria-label="Tổng quan">
                <h1>Tổng quan</h1>
            </header>
            <p class="student-overview-page-sub">Buổi học đang diễn ra hoặc buổi sắp tới gần nhất, cùng danh sách khóa bạn phụ trách.</p>
        </div>
    </div>

    <div class="teacher-dashboard-split">
        <section class="teacher-dashboard-main" aria-label="Buổi học">
            <div class="teacher-dashboard-sessions-main">
                @if ($featureSession !== null)
                    @include('teacher.partials.session-feature-card', [
                        'session' => $featureSession,
                        'phaseLabels' => $phaseLabels,
                        'isUpcoming' => ($joinableSessions[0] ?? null) === null,
                    ])
                @else
                    <div class="teacher-dashboard-live-empty table-card">
                        <div class="empty-state">
                            <span class="soft-icon blue"><i class="fa-solid fa-calendar-xmark"></i></span>
                            <p>Chưa có buổi học sắp diễn ra.</p>
                            <small>Các buổi đã lên lịch sẽ hiển thị tại đây.</small>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <aside class="teacher-dashboard-side" aria-label="Khóa học phụ trách">
            <article class="table-card teacher-dashboard-courses-panel">
                <div class="panel-title-row">
                    <div class="table-title" style="margin:0">
                        <h2><i class="fa-solid fa-graduation-cap"></i> Khóa học của bạn</h2>
                        <span>{{ count($courses) }} khóa đang mở</span>
                    </div>
                </div>

                @if ($courses === [])
                    <div class="empty-state">
                        <span class="soft-icon orange"><i class="fa-solid fa-graduation-cap"></i></span>
                        <p>Chưa có khóa học nào được phân công.</p>
                    </div>
                @else
                    <div class="teacher-dashboard-course-list">
                        @foreach ($courses as $course)
                            @php
                                $isActive = ($course['status'] ?? '') === 'active';
                            @endphp
                            <article class="teacher-dashboard-course-item">
                                <div class="teacher-dashboard-course-item-head">
                                    <span class="soft-icon orange"><i class="fa-solid fa-graduation-cap"></i></span>
                                    <div class="teacher-dashboard-course-item-meta">
                                        <strong>{{ $course['title'] ?? '' }}</strong>
                                        <span>{{ (int) ($course['member_count'] ?? 0) }} học viên · {{ (int) ($course['session_count'] ?? 0) }} buổi</span>
                                    </div>
                                </div>
                                <div class="teacher-dashboard-course-item-foot">
                                    @if ($isActive)
                                        <span class="pill green">Active</span>
                                    @else
                                        <span class="pill gray">{{ $course['status'] ?? '' }}</span>
                                    @endif
                                    <a class="btn btn-blue btn-sm btn-with-icon" href="{{ $course['manage_url'] }}">
                                        <span>Quản lý</span>
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </article>
        </aside>
    </div>
</div>
@endsection
