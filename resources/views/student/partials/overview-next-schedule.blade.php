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
    <article class="op-panel so-session{{ $isLive ? ' is-live' : '' }}" aria-label="{{ $isLive ? 'Buổi học đang diễn ra' : 'Buổi học sắp tới' }}">
        <header class="so-session-head">
            @if ($isLive)
                <span class="so-session-badge is-live">
                    <span class="so-session-dot" aria-hidden="true"></span>
                    Đang live
                </span>
            @else
                <span class="so-session-badge">
                    <i class="fa-solid fa-calendar-day" aria-hidden="true"></i>
                    Buổi học sắp tới
                </span>
            @endif
            <span class="so-session-mark" aria-hidden="true">
                <i class="fa-solid {{ $isLive ? 'fa-tower-broadcast' : 'fa-calendar-day' }}"></i>
            </span>
        </header>

        <div class="so-session-body">
            <span class="so-session-kicker">{{ $isLive ? 'Đang tới giờ học' : 'Lịch học tiếp theo' }}</span>
            <h2>{{ $sessionTitle !== '' ? $sessionTitle : $scheduleLabel }}</h2>

            @if ($isLive || $countdownLabel !== '')
                <div class="so-session-countdown" role="status">
                    <i class="fa-solid {{ $isLive ? 'fa-tower-broadcast' : 'fa-bell' }}" aria-hidden="true"></i>
                    <span>{{ $isLive ? ($countdownLabel !== '' ? $countdownLabel : 'Đang diễn ra') : $countdownLabel }}</span>
                </div>
            @endif

            <div class="so-session-stats">
                @if ($hasSchedule && $scheduleWhen !== '')
                    <div class="so-session-stat is-wide">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                        <span>
                            <em>Giờ học</em>
                            <strong>
                                @if ($scheduleDate !== '')
                                    {{ $scheduleDate }}
                                @endif
                                @if ($scheduleTime !== '')
                                    <small>{{ $scheduleTime }}</small>
                                @endif
                            </strong>
                        </span>
                    </div>
                @elseif (! $hasSchedule)
                    <div class="so-session-stat">
                        <i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i>
                        <span>
                            <em>Lịch học</em>
                            <strong>Chưa lên lịch cụ thể</strong>
                        </span>
                    </div>
                @elseif ($timeLabel !== '')
                    <div class="so-session-stat">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                        <span>
                            <em>Giờ học</em>
                            <strong>{{ $timeLabel }}</strong>
                        </span>
                    </div>
                @endif

                @if ($levelTitle !== '')
                    <div class="so-session-stat">
                        <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                        <span>
                            <em>Trình độ</em>
                            <strong>{{ $levelTitle }}</strong>
                        </span>
                    </div>
                @endif

                @if ($durationLabel !== '')
                    <div class="so-session-stat">
                        <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                        <span>
                            <em>Thời lượng</em>
                            <strong>{{ $durationLabel }}</strong>
                        </span>
                    </div>
                @endif

                @if ($isLive)
                    <div class="so-session-stat is-accent">
                        <i class="fa-solid fa-door-open" aria-hidden="true"></i>
                        <span>
                            <em>Cửa lớp</em>
                            <strong>Đang mở · vào học ngay</strong>
                        </span>
                    </div>
                @elseif ($hasSchedule && $opensAtLabel !== '')
                    <div class="so-session-stat">
                        <i class="fa-solid fa-door-open" aria-hidden="true"></i>
                        <span>
                            <em>Cửa lớp mở</em>
                            <strong>{{ $opensAtLabel }}</strong>
                        </span>
                    </div>
                @endif
            </div>

            @if ($showJoinActions && $publicCode !== '')
                <div class="so-session-actions">
                    @if (! empty($session['is_joinable']))
                        <form method="post" action="{{ route('student.session.join', $publicCode) }}">
                            @csrf
                            <button class="op-btn" type="submit">
                                <i class="fa-solid fa-video"></i>
                                <span>{{ ! empty($session['has_attended']) ? 'Vào lại lớp học' : 'Vào học ngay' }}</span>
                            </button>
                        </form>
                    @endif
                    @if (! empty($session['waiting_teacher']))
                        <p class="so-session-note">
                            <i class="fa-solid fa-hourglass-half"></i>
                            <span>Giáo viên chưa vào lớp — bạn vẫn vào phòng được, vui lòng đợi giáo viên.</span>
                        </p>
                    @endif
                </div>
            @endif

            @if ($hasPreStudy)
                @if ($isLive && $preStudyDetailUrl !== null)
                    <div class="so-session-actions">
                        <a class="op-btn-ghost" href="{{ $preStudyDetailUrl }}">
                            <i class="fa-solid fa-book-open-reader"></i>
                            <span>Ôn tập trước buổi</span>
                        </a>
                    </div>
                @elseif (! $isLive)
                    <section class="so-prestudy" aria-label="Tài liệu chuẩn bị trước buổi học">
                        <div class="so-prestudy-head">
                            <span class="op-panel-ico" aria-hidden="true"><i class="fa-solid fa-book-open"></i></span>
                            <div>
                                <strong>Chuẩn bị trước buổi học</strong>
                                <span>Giáo viên gợi ý nội dung nên xem trước</span>
                            </div>
                            @if ($preStudyDetailUrl !== null)
                                <a href="{{ $preStudyDetailUrl }}">
                                    Xem nội dung trước buổi học <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            @endif
                        </div>
                        @if (($nextSchedule['preSessionHtml'] ?? '') !== '')
                            <div class="so-prestudy-body">{!! $nextSchedule['preSessionHtml'] !!}</div>
                        @endif
                    </section>
                @endif
            @else
                <p class="so-session-note">
                    <i class="fa-solid fa-book-open"></i>
                    <span>Giáo viên chưa đăng tài liệu chuẩn bị — xem chi tiết buổi học.</span>
                </p>
                @if ($scheduleUrl !== null)
                    <div class="so-session-actions">
                        <a class="so-session-more" href="{{ $scheduleUrl }}">
                            Xem nội dung trước buổi học
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                @endif
            @endif
        </div>
    </article>
@else
    <article class="op-panel so-session is-empty">
        <span class="so-session-mark" aria-hidden="true"><i class="fa-solid fa-calendar-day"></i></span>
        <div>
            <strong>{{ $scheduleLabel }}</strong>
            @if (trim((string) ($nextSchedule['detail'] ?? '')) !== '')
                <p>{{ $nextSchedule['detail'] }}</p>
            @endif
        </div>
    </article>
@endif
