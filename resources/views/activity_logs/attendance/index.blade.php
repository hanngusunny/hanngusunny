@extends('layouts.app', ['active' => 'activity'])

@php
    $courses = $courses ?? collect();
@endphp

@section('title', 'Check điểm danh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/activity-logs.css') }}?v=2">
@endpush

@section('subnav')
    @include('activity_logs.partials.subnav', ['activityTab' => 'attendance'])
@endsection

@section('content')
<div class="op-stack al-page">
    <x-page-title
        kicker="Giám sát hệ thống"
        icon="fa-user-check"
        title="Check điểm danh"
        lead="Chọn khóa học để điểm danh từng học viên cho toàn bộ buổi học."
    />

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-chalkboard"></i></span>
            <span class="op-panel-title">Khóa học · {{ $courses->count() }}</span>
        </div>
        <div class="op-panel-body is-flush">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th>Khóa học</th>
                            <th>Giáo viên</th>
                            <th>Học viên</th>
                            <th>Buổi học</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($courses as $course)
                            <tr>
                                <td>
                                    <strong>{{ $course->title }}</strong>
                                </td>
                                <td>{{ $course->teacher?->name ?: '—' }}</td>
                                <td>{{ (int) ($course->members_count ?? 0) }}</td>
                                <td>{{ (int) ($course->sessions_count ?? 0) }}</td>
                                <td class="text-right">
                                    <a class="op-btn" href="{{ route('activity-attendance.show', $course->id) }}">
                                        <i class="fa-solid fa-clipboard-user"></i> Check điểm danh
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-500">Chưa có khóa học nào để điểm danh.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
