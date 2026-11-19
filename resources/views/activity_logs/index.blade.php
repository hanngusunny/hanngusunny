@extends('layouts.app', ['active' => 'activity'])

@php
    $summaries = $summaries ?? [];
    $filters = $filters ?? ['role' => 'student', 'from' => '', 'to' => ''];
    $roles = $roles ?? [];
    $isAdmin = (bool) ($isAdmin ?? false);
    $activity = $activity ?? app(\App\Services\UserActivityService::class);
    $totalDays = array_sum(array_map(static fn ($row) => (int) ($row['active_days'] ?? 0), $summaries));
    $totalViews = array_sum(array_map(static fn ($row) => (int) ($row['total_page_views'] ?? 0), $summaries));
    $totalActions = array_sum(array_map(static fn ($row) => (int) ($row['total_actions'] ?? 0), $summaries));
    $fromLabel = ! empty($filters['from']) ? date('d/m/Y', strtotime($filters['from'])) : '';
    $toLabel = ! empty($filters['to']) ? date('d/m/Y', strtotime($filters['to'])) : '';
@endphp

@section('title', 'Lịch sử hoạt động')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/activity-logs.css') }}?v=2">
@endpush

@section('subnav')
    @include('activity_logs.partials.subnav', ['activityTab' => 'logs'])
@endsection

@section('content')
<div class="op-stack al-page">
    <x-page-title kicker="Giám sát hệ thống" icon="fa-clock-rotate-left" title="Lịch sử hoạt động" :lead="'Theo dõi thời gian đăng nhập, thời lượng hoạt động và các thao tác trên hệ thống'.($fromLabel && $toLabel ? ' · '.$fromLabel.' – '.$toLabel : '').'.'" />

    <div class="op-stat-grid is-4">
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-sky"><i class="fa-solid fa-users"></i></span><p>Tài khoản<small>Trong bộ lọc</small></p></span><strong>{{ count($summaries) }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-emerald"><i class="fa-solid fa-calendar-check"></i></span><p>Ngày hoạt động<small>Tổng các tài khoản</small></p></span><strong>{{ $totalDays }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-blue"><i class="fa-solid fa-eye"></i></span><p>Lượt xem trang<small>Trong khoảng ngày</small></p></span><strong>{{ $totalViews }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-amber"><i class="fa-solid fa-bolt"></i></span><p>Thao tác<small>Gửi dữ liệu</small></p></span><strong>{{ $totalActions }}</strong></article>
    </div>

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-filter"></i></span>
            <span class="op-panel-title">Bộ lọc</span>
            <div class="op-panel-tools">
                <form class="al-filter" method="get" action="{{ route('activity-logs.index') }}">
                    @if ($isAdmin)
                        <select name="role" class="px-3 py-2 text-sm">
                            @foreach ($roles as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['role'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="px-3 py-2 text-sm">
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="px-3 py-2 text-sm">
                    <x-button type="submit" icon="fa-solid fa-magnifying-glass">Lọc dữ liệu</x-button>
                </form>
            </div>
        </div>
        <div class="op-panel-body is-flush">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th>Họ tên</th>
                            <th>SĐT</th>
                            <th>Vai trò</th>
                            <th>Ngày hoạt động</th>
                            <th>Thời gian online</th>
                            <th>Lượt xem</th>
                            <th>Thao tác</th>
                            <th>Truy cập cuối</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summaries as $row)
                            @php
                                $role = (string) ($row['role'] ?? '');
                                $roleClass = match ($role) {
                                    'admin' => 'is-admin',
                                    'teacher' => 'is-teacher',
                                    default => 'is-student',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="al-person">
                                        <x-user-avatar
                                            class="al-avatar"
                                            :url="\App\Support\MediaUrl::to((string) ($row['avatar'] ?? '')) ?: null"
                                            :name="($row['full_name'] ?: $row['username'])"
                                        />
                                        <strong>{{ $row['full_name'] ?: $row['username'] }}</strong>
                                    </div>
                                </td>
                                <td>{{ $row['phone'] ?: '—' }}</td>
                                <td><span class="al-pill {{ $roleClass }}">{{ $roles[$role] ?? $role }}</span></td>
                                <td>{{ (int) ($row['active_days'] ?? 0) }} ngày</td>
                                <td>{{ $activity->formatDuration((int) ($row['total_active_seconds'] ?? 0)) }}</td>
                                <td>{{ (int) ($row['total_page_views'] ?? 0) }}</td>
                                <td>{{ (int) ($row['total_actions'] ?? 0) }}</td>
                                <td>{{ $activity->formatDateTime($row['last_seen_at'] ?? null) }}</td>
                                <td class="text-right">
                                    <a class="al-link" href="{{ route('activity-logs.show', ['id' => $row['id'], 'from' => $filters['from'], 'to' => $filters['to']]) }}" title="Xem chi tiết">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-10 text-center text-slate-500">Chưa có dữ liệu hoạt động trong khoảng thời gian này.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
