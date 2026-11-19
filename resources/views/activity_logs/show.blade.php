@extends('layouts.app', ['active' => 'activity'])

@php
    $subject = $subject ?? null;
    $filters = $filters ?? ['from' => '', 'to' => '', 'tab' => 'daily'];
    $activity = $activity ?? app(\App\Services\UserActivityService::class);
    $tab = $filters['tab'] ?? 'daily';
@endphp

@section('title', 'Lịch sử: '.($subject?->name ?? 'Người dùng'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/activity-logs.css') }}?v=2">
@endpush

@section('subnav')
    @include('activity_logs.partials.subnav', ['activityTab' => 'logs'])
@endsection

@section('content')
<div class="op-stack al-page">
    <x-page-title kicker="Giám sát hệ thống" icon="fa-clock-rotate-left" :title="$subject?->name" :lead="($subject?->role_label ?? '').($subject?->phone ? ' · SĐT: '.$subject->phone : '')">
        <x-slot:aside>
            <x-button variant="white" href="{{ route('activity-logs.index', ['from' => $filters['from'], 'to' => $filters['to']]) }}" icon="fa-solid fa-arrow-left">Quay lại danh sách</x-button>
        </x-slot:aside>
    </x-page-title>

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-filter"></i></span>
            <span class="op-panel-title">Khoảng ngày</span>
            <div class="op-panel-tools">
                <form class="al-filter" method="get" action="{{ route('activity-logs.show', $subject->id) }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="px-3 py-2 text-sm">
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="px-3 py-2 text-sm">
                    <x-button type="submit" icon="fa-solid fa-magnifying-glass">Lọc dữ liệu</x-button>
                </form>
            </div>
        </div>
        <div class="op-panel-body">
            <div class="al-tabs" data-al-tabs>
                <button type="button" class="al-tab{{ $tab === 'daily' ? ' is-on' : '' }}" data-al-tab="daily">Theo ngày</button>
                <button type="button" class="al-tab{{ $tab === 'sessions' ? ' is-on' : '' }}" data-al-tab="sessions">Phiên đăng nhập</button>
                <button type="button" class="al-tab{{ $tab === 'logs' ? ' is-on' : '' }}" data-al-tab="logs">Nhật ký thao tác</button>
            </div>

            <div class="al-pane{{ $tab === 'daily' ? ' is-on' : '' }}" data-al-pane="daily">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Vào lần đầu</th>
                                <th>Hoạt động cuối</th>
                                <th>Thời gian online</th>
                                <th>Lượt xem trang</th>
                                <th>Số thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dailyStats as $stat)
                                <tr>
                                    <td><strong>{{ $activity->formatDate($stat->stat_date) }}</strong></td>
                                    <td>{{ $activity->formatDateTime($stat->first_seen_at) }}</td>
                                    <td>{{ $activity->formatDateTime($stat->last_seen_at) }}</td>
                                    <td>{{ $activity->formatDuration((int) $stat->active_seconds) }}</td>
                                    <td>{{ (int) $stat->page_views }}</td>
                                    <td>{{ (int) $stat->action_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Chưa có dữ liệu theo ngày.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="al-pane{{ $tab === 'sessions' ? ' is-on' : '' }}" data-al-pane="sessions">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th>Đăng nhập</th>
                                <th>Đăng xuất</th>
                                <th>Thời gian hoạt động</th>
                                <th>IP</th>
                                <th>Thiết bị</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($loginSessions as $session)
                                @php $ua = (string) ($session->user_agent ?? ''); @endphp
                                <tr>
                                    <td>{{ $activity->formatDateTime($session->login_at) }}</td>
                                    <td>{{ $activity->formatDateTime($session->logout_at) }}</td>
                                    <td>{{ $activity->formatDuration((int) $session->active_seconds) }}</td>
                                    <td>{{ $session->ip_address ?: '—' }}</td>
                                    <td title="{{ $ua }}">{{ $ua !== '' ? \Illuminate\Support\Str::limit($ua, 80) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Chưa có phiên đăng nhập.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="al-pane{{ $tab === 'logs' ? ' is-on' : '' }}" data-al-pane="logs">
                <div class="al-log-list">
                    @forelse ($activityLogs as $log)
                        @php
                            $context = $log->context();
                            $typeClass = match ($log->action_type) {
                                'login' => 'is-login',
                                'logout' => 'is-logout',
                                'page_view' => 'is-page',
                                default => 'is-action',
                            };
                        @endphp
                        <article class="al-log {{ $typeClass }}">
                            <div class="al-log-head">
                                <time>{{ $activity->formatDateTime($log->created_at) }}</time>
                                <span>{{ strtoupper((string) $log->http_method) }}</span>
                            </div>
                            <h3>{{ $log->action_label }}</h3>
                            @if ($log->route_path)
                                <p class="al-log-path"><code>{{ $log->route_path }}</code></p>
                            @endif
                            @if ($context !== [])
                                <details>
                                    <summary>Chi tiết thao tác</summary>
                                    <pre>{{ json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @endif
                        </article>
                    @empty
                        <p class="px-2 py-8 text-center text-slate-500">Chưa có nhật ký thao tác.</p>
                    @endforelse
                </div>
                @if ($pager->hasPages())
                    <div class="os-pager">
                        {{ $pager->onEachSide(1)->links('pagination.office') }}
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-al-tabs] [data-al-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-al-tab');
            document.querySelectorAll('[data-al-tab]').forEach(function (el) { el.classList.toggle('is-on', el === btn); });
            document.querySelectorAll('[data-al-pane]').forEach(function (el) {
                el.classList.toggle('is-on', el.getAttribute('data-al-pane') === id);
            });
            var url = new URL(window.location.href);
            url.searchParams.set('tab', id);
            window.history.replaceState({}, '', url.toString());
        });
    });
</script>
@endpush
