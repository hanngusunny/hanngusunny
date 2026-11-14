@extends('layouts.app', ['active' => 'schedule'])

@php
    $course = $course ?? null;
    $sessions = $sessions ?? [];
    $selectedSession = $selectedSession ?? null;
    $selectedSessionId = (int) ($selectedSessionId ?? 0);
    $members = $members ?? collect();
    $sessionAttendanceMap = $sessionAttendanceMap ?? [];
    $phaseLabels = $phaseLabels ?? [];
    $memberCount = (int) ($memberCount ?? 0);
@endphp

@section('title', ($course?->title ?? 'Khóa học').' · Quản lý')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-course.css') }}?v=3">
@endpush

@section('content')
<div class="op-stack page-teacher-course-manage">
    <x-page-title
        kicker="Giáo viên"
        icon="fa-chalkboard"
        :title="$course?->title ?? 'Khóa học của tôi'"
        :lead="$memberCount.' học viên · '.count($sessions).' buổi học'"
    >
        <x-slot:aside>
            <a class="op-btn-ghost" href="{{ route('dashboard') }}"><i class="fa-solid fa-house"></i> Tổng quan</a>
        </x-slot:aside>
    </x-page-title>

    @include('teacher.partials.course-mode-nav', [
        'course' => $course,
        'courseMode' => 'sessions',
    ])

    @if ($sessions === [])
        <div class="teacher-course-manage-split teacher-course-manage-split--no-sessions">
            <section class="teacher-course-manage-main" aria-label="Học viên khóa học">
                @include('teacher.partials.course-members', [
                    'course' => $course,
                    'members' => $members,
                    'sessionAttendanceMap' => $sessionAttendanceMap,
                    'session' => null,
                ])
            </section>
            <aside class="teacher-course-manage-side" aria-label="Danh sách buổi học">
                <article class="teacher-course-session-list-panel">
                    <div class="teacher-course-empty">
                        <span class="teacher-course-empty-icon"><i class="fa-regular fa-calendar-xmark"></i></span>
                        <p>Chưa có buổi học nào trong khóa.</p>
                    </div>
                </article>
            </aside>
        </div>
    @else
        <div class="teacher-course-manage-split">
            <section class="teacher-course-manage-main" aria-label="Thông tin buổi học và học viên">
                <div class="teacher-course-manage-main-stack">
                    @include('teacher.partials.session-detail', [
                        'course' => $course,
                        'session' => $selectedSession,
                        'phaseLabels' => $phaseLabels,
                        'memberCount' => $memberCount,
                        'canEnterRoom' => $canEnterRoom ?? false,
                        'roomLobbyUrl' => $roomLobbyUrl ?? null,
                        'onlineRoom' => $onlineRoom ?? null,
                        'sessionRoomEvents' => $sessionRoomEvents ?? [],
                    ])
                    @include('teacher.partials.course-members', [
                        'course' => $course,
                        'members' => $members,
                        'sessionAttendanceMap' => $sessionAttendanceMap,
                        'session' => $selectedSession,
                    ])
                </div>
            </section>

            <aside class="teacher-course-manage-side" aria-label="Danh sách buổi học">
                @include('teacher.partials.session-list', [
                    'course' => $course,
                    'sessions' => $sessions,
                    'selectedSessionId' => $selectedSessionId,
                    'phaseLabels' => $phaseLabels,
                ])
            </aside>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var active = document.getElementById('teacher-course-session-active');
        var list = active && active.closest('.teacher-course-session-list');
        if (active && list) {
            var top = active.offsetTop - list.offsetTop - (list.clientHeight / 2) + (active.clientHeight / 2);
            list.scrollTop = Math.max(0, top);
        }
    })();
</script>
@endpush
