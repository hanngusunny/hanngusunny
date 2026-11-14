@php
    $summary = $activitySummary ?? [];
    $helper = $activityHelper ?? app(\App\Services\UserActivityService::class);
    $days = (int) ($summary['period_days'] ?? 30);
    $lastSeen = $summary['last_seen_at'] ?? null;
    if ($lastSeen === null || $lastSeen === '') {
        $lastSeen = $user->last_login_at ?? null;
    }
@endphp
<section class="op-panel pf-activity">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-chart-line"></i></span>
        <span class="op-panel-title">Hoạt động {{ $days }} ngày gần đây</span>
        @if (! empty($activityDetailUrl))
            <div class="op-panel-tools">
                <a class="op-btn-ghost" href="{{ $activityDetailUrl }}">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Chi tiết</span>
                </a>
            </div>
        @endif
    </div>
    <div class="op-panel-body">
        <div class="pf-activity-stats">
            <div class="pf-activity-stat">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Ngày hoạt động</span>
                <strong>{{ (int) ($summary['active_days'] ?? 0) }}</strong>
            </div>
            <div class="pf-activity-stat">
                <i class="fa-solid fa-eye"></i>
                <span>Lượt xem trang</span>
                <strong>{{ (int) ($summary['page_views'] ?? 0) }}</strong>
            </div>
            <div class="pf-activity-stat">
                <i class="fa-solid fa-bolt"></i>
                <span>Thao tác</span>
                <strong>{{ (int) ($summary['actions'] ?? 0) }}</strong>
            </div>
            <div class="pf-activity-stat">
                <i class="fa-solid fa-hourglass-half"></i>
                <span>Thời gian online</span>
                <strong>{{ $helper->formatDuration((int) ($summary['active_seconds'] ?? 0)) }}</strong>
            </div>
        </div>
        <p class="pf-activity-footnote">
            <i class="fa-regular fa-clock"></i>
            Truy cập gần nhất trong kỳ:
            <strong>{{ $helper->formatDateTime($lastSeen) }}</strong>
        </p>
    </div>
</section>
