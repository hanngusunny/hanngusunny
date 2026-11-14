@php
    $session = is_array($session ?? null) ? $session : [];
    $phaseLabels = is_array($phaseLabels ?? null) ? $phaseLabels : [];
    $display = is_array($session['display'] ?? null) ? $session['display'] : [];
    $phase = (string) ($display['phase'] ?? $session['phase'] ?? 'live');
    $phaseLabel = $phaseLabels[$phase] ?? $phase;
    $hasSchedule = ! empty($display['hasSchedule']);
    $isLive = $phase === 'live';
    $isUpcoming = (bool) ($isUpcoming ?? ($phase === 'upcoming'));
    $courseTitle = (string) ($session['course_title'] ?? 'Khóa học');
    $manageUrl = (string) ($session['manage_url'] ?? route('dashboard'));
    $roomUrl = trim((string) ($session['room_lobby_url'] ?? ''));
    $roomId = (int) ($session['room_id'] ?? 0);
    $roomCode = trim((string) ($session['room_code'] ?? ''));
    $description = trim((string) ($session['description'] ?? ''));
    $durationLabel = (string) ($display['durationLabel'] ?? '');
    $opensAtLabel = $hasSchedule ? (string) ($display['opensAtLabel'] ?? '') : '';
    $showEnterRoom = $isLive && $roomUrl !== '';
@endphp

<article class="student-session-card student-session-card--feature student-session-card--{{ $phase }}{{ $hasSchedule ? '' : ' student-session-card--unscheduled' }}" aria-label="{{ $isLive ? 'Buổi học đang diễn ra' : 'Buổi học sắp diễn ra' }}">
    <header class="student-session-hero">
        <div class="student-session-hero-copy">
            <p class="student-session-hero-eyebrow">{{ $isLive ? 'Buổi học đang diễn ra' : 'Buổi học sắp diễn ra' }}</p>
            <span class="student-session-phase student-session-phase--{{ $phase }}">
                <i class="fa-solid {{ $isLive ? 'fa-tower-broadcast' : 'fa-clock-rotate-left' }}"></i>
                {{ $phaseLabel }}
            </span>
        </div>
        <div class="student-session-countdown student-session-countdown--hero{{ $hasSchedule ? '' : ' student-session-countdown--pending' }}" style="display: contents;">
            <i class="fa-solid {{ $hasSchedule ? 'fa-bell' : 'fa-calendar-xmark' }}"></i>
            <span class="student-session-countdown-text">{{ $display['countdownLabel'] ?? ($isLive ? 'Đang diễn ra' : 'Sắp diễn ra') }}</span>
        </div>
    </header>

    <div class="student-session-body">
        <div class="student-session-hero-layout">
            <div class="student-session-date student-session-date--hero{{ $hasSchedule ? '' : ' student-session-date--pending' }}" aria-hidden="true">
                @if ($hasSchedule)
                    <strong>{{ $display['dateDay'] ?? '' }}</strong>
                    <span class="student-session-date-weekday">{{ $display['weekdayFull'] ?? '' }}</span>
                    <em class="student-session-date-month">{{ $display['dateMonth'] ?? '' }}</em>
                @else
                    <span class="student-session-date-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                    <strong class="student-session-date-placeholder">--</strong>
                    <span class="student-session-date-pending-label">Chưa lên lịch</span>
                @endif
            </div>

            <div class="student-session-main">
                <h2 class="student-session-title">{{ $session['title'] ?? 'Buổi học' }}</h2>
                <div class="student-session-meta-strip">
                    <div class="student-session-meta-chip">
                        <span class="student-session-meta-chip-icon" aria-hidden="true"><i class="fa-solid fa-book"></i></span>
                        <span class="student-session-meta-chip-copy">
                            <em>Khóa học</em>
                            <strong>{{ $courseTitle }}</strong>
                        </span>
                    </div>
                </div>
                <div class="student-session-info-grid">
                    <div class="student-session-info-item">
                        <span class="student-session-info-icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></span>
                        <span class="student-session-info-copy">
                            <span class="student-session-info-label">Giờ học</span>
                            <strong>{{ $hasSchedule ? ($display['timeLabel'] ?? '') : 'Chưa đặt' }}</strong>
                        </span>
                    </div>
                    <div class="student-session-info-item">
                        <span class="student-session-info-icon" aria-hidden="true"><i class="fa-solid fa-hourglass-half"></i></span>
                        <span class="student-session-info-copy">
                            <span class="student-session-info-label">Thời lượng</span>
                            <strong class="{{ $durationLabel !== '' ? '' : 'is-muted' }}">{{ $durationLabel !== '' ? $durationLabel : '—' }}</strong>
                        </span>
                    </div>
                    <div class="student-session-info-item">
                        <span class="student-session-info-icon" aria-hidden="true"><i class="fa-solid fa-door-open"></i></span>
                        <span class="student-session-info-copy">
                            <span class="student-session-info-label">Mở cửa lớp</span>
                            <strong class="{{ ($hasSchedule && $isUpcoming) || $isLive ? '' : 'is-muted' }}">
                                @if ($isLive)
                                    Đang mở
                                @elseif ($hasSchedule && $opensAtLabel !== '')
                                    {{ $opensAtLabel }}
                                @else
                                    —
                                @endif
                            </strong>
                        </span>
                    </div>
                    <div class="student-session-info-item">
                        <span class="student-session-info-icon student-session-info-icon--zoom" aria-hidden="true"><i class="fa-solid fa-video"></i></span>
                        <span class="student-session-info-copy">
                            <span class="student-session-info-label">Hình thức</span>
                            <strong>Zoom trực tuyến</strong>
                        </span>
                    </div>
                    @if ($roomId > 0)
                        <div class="student-session-info-item">
                            <span class="student-session-info-icon" aria-hidden="true"><i class="fa-solid fa-hashtag"></i></span>
                            <span class="student-session-info-copy">
                                <span class="student-session-info-label">ID phòng</span>
                                <strong><code>{{ $roomId }}</code>@if ($roomCode !== '') · {{ $roomCode }}@endif</strong>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($description !== '')
            <div class="student-session-desc-box">
                <div class="student-session-desc-head">
                    <span class="student-session-desc-icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></span>
                    <div class="student-session-desc-copy">
                        <strong>Nội dung buổi học</strong>
                        <span class="student-session-desc-subtitle">Giới thiệu nội dung sẽ dạy trong buổi này</span>
                    </div>
                </div>
                <div class="student-session-desc-body">
                    <p>{{ $description }}</p>
                </div>
            </div>
        @endif
    </div>

    <footer class="student-session-feature-foot">
        @if ($isUpcoming && $hasSchedule && $opensAtLabel !== '')
            <p class="student-session-note">
                <i class="fa-solid fa-circle-info"></i>
                Cửa lớp mở lúc <strong>{{ $opensAtLabel }}</strong> (trước giờ học 15 phút)
            </p>
        @endif
        <div class="student-session-feature-links">
            @if ($showEnterRoom)
                <a class="btn btn-primary btn-with-icon" href="{{ $roomUrl }}">
                    <i class="fa-solid fa-video"></i>
                    <span>Vào lớp học</span>
                </a>
            @endif
            <a class="btn btn-outline btn-with-icon" href="{{ $manageUrl }}">
                <i class="fa-solid fa-users"></i>
                <span>Quản lý buổi</span>
            </a>
        </div>
    </footer>
</article>
