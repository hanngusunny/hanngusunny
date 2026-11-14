@php
    $report = $attendanceReport ?? ['items' => [], 'attended' => 0, 'total' => 0];
    $items = $report['items'] ?? [];
    $total = (int) ($report['total'] ?? 0);
    $attended = (int) ($report['attended'] ?? 0);
    $weekLabel = $weekLabel ?? '';
@endphp

@if ($total === 0)
    <article class="op-panel course-attendance-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-clipboard-user"></i></span>
            <span class="op-panel-title">Điểm danh tuần</span>
            <span class="te-side-meta">{{ $weekLabel }}</span>
        </div>
        <div class="op-panel-body">
            <p class="muted">Chưa có buổi học nào được đặt lịch trong tuần này.</p>
        </div>
    </article>
@else
    <article class="op-panel course-attendance-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-clipboard-user"></i></span>
            <span class="op-panel-title">Điểm danh tuần</span>
            <span class="te-side-meta">{{ $attended }}/{{ $total }} buổi có mặt</span>
        </div>
        <div class="op-panel-body">
            <ul class="teacher-eval-week-attendance-list">
                @foreach ($items as $item)
                    @php
                        $session = $item['session'] ?? [];
                        $title = trim((string) ($session['title'] ?? 'Buổi học'));
                        $date = trim((string) ($session['session_date'] ?? ''));
                        $start = substr((string) ($session['start_time'] ?? ''), 0, 5);
                        $status = (string) ($item['status_label'] ?? '');
                        $joined = $item['joined_at'] ?? null;
                        $statusClass = match ($status) {
                            'Có mặt' => 'present',
                            'Chưa diễn ra' => 'upcoming',
                            default => 'absent',
                        };
                    @endphp
                    <li class="teacher-eval-week-attendance-item teacher-eval-week-attendance-item--{{ $statusClass }}">
                        <div class="teacher-eval-week-attendance-main">
                            <strong>{{ $title }}</strong>
                            <span>
                                {{ $date !== '' ? date('d/m/Y', strtotime($date)) : '' }}
                                {{ $start !== '' ? ' · '.$start : '' }}
                            </span>
                        </div>
                        <div class="teacher-eval-week-attendance-status">
                            <i class="fa-solid {{ $statusClass === 'present' ? 'fa-circle-check' : ($statusClass === 'upcoming' ? 'fa-clock' : 'fa-circle-xmark') }}" aria-hidden="true"></i>
                            {{ $status }}
                            @if ($joined)
                                <small>(vào {{ date('H:i', strtotime((string) $joined)) }})</small>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </article>
@endif
