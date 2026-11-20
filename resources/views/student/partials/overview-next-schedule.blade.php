@php
    $nextSchedule = is_array($nextSchedule ?? null) ? $nextSchedule : [];
    $scheduleLabel = trim((string) ($nextSchedule['label'] ?? ''));
    $session = is_array($nextSchedule['session'] ?? null) ? $nextSchedule['session'] : [];
    $schedulePhase = (string) ($nextSchedule['phase'] ?? '');
    $scheduleUrl = $nextSchedule['sessionUrl'] ?? null;
    $sessionTitle = trim((string) ($nextSchedule['sessionTitle'] ?? ''));
    $scheduleWhen = trim((string) ($nextSchedule['scheduleWhen'] ?? ''));
    $timeLabel = trim((string) ($nextSchedule['timeLabel'] ?? ''));
    $hasPreStudy = ! empty($nextSchedule['hasPreStudy']);
    $levelTitle = trim((string) ($nextSchedule['levelTitle'] ?? ''));
    $countdownLabel = trim((string) ($nextSchedule['countdownLabel'] ?? ''));
    $durationLabel = trim((string) ($nextSchedule['durationLabel'] ?? ''));
    $opensAtLabel = trim((string) ($nextSchedule['opensAtLabel'] ?? ''));
    $hasSchedule = ! empty($nextSchedule['hasSchedule']);
    $hasSession = $session !== [];
    $isLive = $schedulePhase === 'live';
    $isUpcoming = $hasSession && ! $isLive;
    $preStudyDetailUrl = $scheduleUrl !== null ? $scheduleUrl.'#session-pre-materials' : null;
    $showJoinActions = $isLive || ! empty($session['is_joinable']) || ! empty($session['waiting_teacher']);
    $publicCode = (string) ($session['public_code'] ?? $nextSchedule['publicCode'] ?? '');
    $scheduleDate = '';
    $scheduleTime = '';
    if ($scheduleWhen !== '' && str_contains($scheduleWhen, ' · ')) {
        [$scheduleDate, $scheduleTime] = array_map('trim', explode(' · ', $scheduleWhen, 2));
    } else {
        $scheduleDate = $scheduleWhen;
    }
@endphp

@if ($scheduleLabel === '')
@elseif ($isLive || $isUpcoming)
    <article class="sn-next-session-box" aria-label="{{ $isLive ? 'Buổi học đang diễn ra' : 'Buổi học sắp tới' }}">
        <!-- Top bar: Trình độ & Trạng thái live/sắp tới -->
        <header class="sn-session-top-bar">
            <div class="sn-session-badges-left">
                @if ($levelTitle !== '')
                    <span class="sn-session-level-badge">{{ $levelTitle }}</span>
                @else
                    <span class="sn-session-level-badge">Lịch học tiếp theo</span>
                @endif
            </div>

            <div>
                @if ($isLive)
                    <span class="sn-session-status-badge is-live">
                        <span class="sn-live-pulse-dot" aria-hidden="true"></span>
                        <span>Đang live</span>
                    </span>
                @else
                    <span class="sn-session-status-badge is-upcoming">
                        <i class="fa-solid fa-calendar-day" aria-hidden="true"></i>
                        <span>Buổi học sắp tới</span>
                    </span>
                @endif
            </div>
        </header>

        <!-- Tiêu đề buổi học -->
        <div class="sn-session-headline">
            <h3>{{ $sessionTitle !== '' ? $sessionTitle : $scheduleLabel }}</h3>
            @if ($isLive || $countdownLabel !== '')
                <p>
                    <i class="fa-solid {{ $isLive ? 'fa-tower-broadcast' : 'fa-clock' }} text-orange-500"></i>
                    <strong>{{ $isLive ? ($countdownLabel !== '' ? $countdownLabel : 'Lớp học đang diễn ra') : $countdownLabel }}</strong>
                </p>
            @endif
        </div>

        <!-- White Cards Grid: Giờ học & Sẵn sàng buổi học (Figma Node #317:12132) -->
        <div class="sn-session-cards-grid">
            <!-- Card 1: Giờ học (Figma Node #317:12133) -->
            <div class="sn-session-white-card sn-session-time-card">
                <div class="sn-session-card-header">
                    <span class="sn-session-cal-icon-box">
                        <img src="{{ asset('assets/images/figma-calendar.svg') }}" alt="Giờ học" width="20" height="20">
                    </span>
                    <span class="sn-session-cal-label">Giờ học</span>
                </div>
                <div class="sn-session-time-body">
                    @if ($scheduleTime !== '')
                        <div class="sn-session-time-highlight">{{ $scheduleTime }}</div>
                    @elseif ($timeLabel !== '')
                        <div class="sn-session-time-highlight">{{ $timeLabel }}</div>
                    @else
                        <div class="sn-session-time-highlight">19:30 – 22:00</div>
                    @endif

                    @if ($scheduleDate !== '')
                        <div class="sn-session-date-sub">{{ $scheduleDate }}</div>
                    @elseif ($durationLabel !== '')
                        <div class="sn-session-date-sub">Thời lượng: {{ $durationLabel }}</div>
                    @else
                        <div class="sn-session-date-sub">Lịch học trực tuyến SUNNY</div>
                    @endif
                </div>
            </div>

            <!-- Card 2: Sẵn sàng buổi học (Figma Node #317:12141) -->
            <div class="sn-session-white-card sn-session-ready-card">
                <div class="sn-session-ready-content">
                    <h4 class="sn-session-ready-title">Bạn đã <span class="text-[#0D99FF]">sẵn sàng</span> cho buổi học sắp tới chưa?</h4>
                    <p class="sn-session-ready-desc">Ôn lại bài để tự tin hơn nhé!</p>
                </div>
                <div class="sn-session-ready-visual">
                    <img src="{{ asset('assets/images/figma-ready-mascot.png') }}" alt="Sẵn sàng" class="sn-session-ready-img" loading="lazy">
                </div>
            </div>
        </div>

        <!-- Tài liệu ôn tập trước buổi học (nếu có) -->
        @if ($hasPreStudy)
            <div class="sn-prestudy-box">
                <div class="sn-prestudy-title">
                    <i class="fa-solid fa-book-open text-orange-500"></i>
                    <span>Tài liệu chuẩn bị trước buổi học đã sẵn sàng</span>
                </div>
                @if ($preStudyDetailUrl !== null)
                    <a href="{{ $preStudyDetailUrl }}" class="sn-login-link flex items-center gap-1">
                        <span>Xem chi tiết</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                @endif
            </div>
        @endif

        <!-- Action Bar: Vào lớp học / Ôn tập -->
        <footer class="sn-session-action-bar">
            @if ($hasPreStudy && $preStudyDetailUrl !== null)
                <x-sunny.button variant="white" href="{{ $preStudyDetailUrl }}" icon="fa-solid fa-book-open">
                    Ôn tập trước buổi
                </x-sunny.button>
            @endif

            @if ($showJoinActions && $publicCode !== '')
                @if (! empty($session['is_joinable']))
                    <form method="post" action="{{ route('student.session.join', $publicCode) }}" class="inline-block m-0">
                        @csrf
                        <x-sunny.button variant="secondary" type="submit" icon="fa-solid fa-video" iconRight="fa-solid fa-arrow-right">
                            {{ ! empty($session['has_attended']) ? 'Vào lại lớp học' : 'Vào lớp học' }}
                        </x-sunny.button>
                    </form>
                @endif
            @elseif ($scheduleUrl)
                <x-sunny.button variant="secondary" href="{{ $scheduleUrl }}" icon="fa-solid fa-video" iconRight="fa-solid fa-arrow-right">
                    Vào lớp học
                </x-sunny.button>
            @else
                <x-sunny.button variant="secondary" href="#" icon="fa-solid fa-video" iconRight="fa-solid fa-arrow-right">
                    Vào lớp học
                </x-sunny.button>
            @endif
        </footer>

        @if (! empty($session['waiting_teacher']))
            <p class="text-xs text-amber-700 bg-amber-50 p-3 rounded-xl border border-amber-200 mt-2 flex items-center gap-2">
                <i class="fa-solid fa-hourglass-half"></i>
                <span>Giáo viên chưa vào lớp — bạn vẫn có thể vào phòng trước và chuẩn bị micro, tai nghe.</span>
            </p>
        @endif
    </article>
@else
    <article class="sn-next-session-box text-center py-10" aria-label="Chưa có lịch học">
        <div class="w-16 h-16 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center text-2xl mx-auto mb-3">
            <i class="fa-solid fa-calendar-day"></i>
        </div>
        <h4 class="font-bold text-slate-800 text-lg mb-1">{{ $scheduleLabel }}</h4>
        @if (trim((string) ($nextSchedule['detail'] ?? '')) !== '')
            <p class="text-sm text-slate-500 max-w-md mx-auto">{{ $nextSchedule['detail'] }}</p>
        @else
            <p class="text-sm text-slate-500 max-w-md mx-auto">Giáo viên sẽ cập nhật lịch học sớm nhất có thể.</p>
        @endif
    </article>
@endif
