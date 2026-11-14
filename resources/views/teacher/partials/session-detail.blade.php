@php
    $session = $session ?? null;
    $phaseLabels = $phaseLabels ?? [];
    $memberCount = (int) ($memberCount ?? 0);
    $canEnterRoom = (bool) ($canEnterRoom ?? false);
    $roomLobbyUrl = (string) ($roomLobbyUrl ?? '');
    $onlineRoom = $onlineRoom ?? null;
    $richText = app(\App\Support\RichContent::class);
@endphp

@if ($session === null)
    <article class="teacher-course-session-detail-empty">
        <div class="teacher-course-empty">
            <span class="teacher-course-empty-icon"><i class="fa-solid fa-hand-pointer"></i></span>
            <p>Chọn một buổi học bên phải để xem thông tin chi tiết.</p>
        </div>
    </article>
@else
    @php
        $display = is_array($session['display'] ?? null) ? $session['display'] : [];
        $phase = (string) ($session['phase'] ?? 'upcoming');
        $isLive = $phase === 'live';
        $isEnded = $phase === 'ended' || $phase === 'cancelled';
        $hasSummary = ! empty($session['has_summary']);
        $preCount = (int) ($session['pre_question_count'] ?? 0);
        $postCount = (int) ($session['post_question_count'] ?? 0);
        $attendance = (int) ($session['attendance_count'] ?? 0);
        $recordUrl = trim((string) ($session['record_video_url'] ?? ''));
        $showRoomCta = ($canEnterRoom || $isLive) && $roomLobbyUrl !== '';
        $roomId = (int) (is_object($onlineRoom) ? ($onlineRoom->id ?? 0) : ($onlineRoom['id'] ?? 0));
        $roomCode = (string) (is_object($onlineRoom) ? ($onlineRoom->room_code ?? '') : ($onlineRoom['room_code'] ?? ''));
    @endphp

    <article class="teacher-course-session-detail">
        <header class="teacher-course-session-detail-head">
            <div class="teacher-course-session-detail-head-main">
                <span class="teacher-course-session-phase teacher-course-session-phase--{{ $phase }}">
                    @if ($isLive)
                        <i class="fa-solid fa-tower-broadcast"></i> Đang diễn ra
                    @else
                        <i class="fa-solid fa-clock"></i> {{ $phaseLabels[$phase] ?? $phase }}
                    @endif
                </span>
                <h2>{{ $session['title'] ?? 'Buổi học' }}</h2>
                <p class="teacher-course-session-detail-sub">
                    @if (! empty($display['hasSchedule']))
                        <span><i class="fa-regular fa-calendar"></i> {{ $display['weekdayFull'] ?? '' }}, {{ $display['dateLabel'] ?? '' }}</span>
                        <span><i class="fa-regular fa-clock"></i> {{ $display['timeLabel'] ?? '' }}</span>
                        @if (! empty($display['durationLabel']))
                            <span><i class="fa-solid fa-hourglass-half"></i> {{ $display['durationLabel'] }}</span>
                        @endif
                    @else
                        <span><i class="fa-regular fa-calendar-xmark"></i> Chưa lên lịch</span>
                    @endif
                </p>
            </div>

            @if ($showRoomCta)
                <div class="teacher-course-session-detail-actions">
                    <a class="op-btn" href="{{ $roomLobbyUrl }}">
                        <i class="fa-solid fa-video"></i>
                        <span>{{ $isLive ? 'Vào lớp học' : 'Vào phòng chờ' }}</span>
                    </a>
                </div>
            @endif
        </header>

        @if ($roomId > 0)
            <div class="teacher-course-room-meta">
                <span><i class="fa-solid fa-hashtag"></i> ID phòng: <code>{{ $roomId }}</code></span>
                <span><i class="fa-solid fa-key"></i> Mã phòng: <code>{{ $roomCode }}</code></span>
            </div>
        @endif

        <div class="teacher-course-session-stats">
            <div class="teacher-course-session-stat">
                <i class="fa-solid fa-users"></i>
                <span>Học viên</span>
                <strong>{{ $memberCount }}</strong>
            </div>
            <div class="teacher-course-session-stat">
                <i class="fa-solid fa-square-check"></i>
                <span>Điểm danh</span>
                <strong>{{ $attendance }}/{{ $memberCount }}</strong>
            </div>
            @if ($preCount > 0)
                <div class="teacher-course-session-stat">
                    <i class="fa-solid fa-book-open"></i>
                    <span>Bài trước buổi</span>
                    <strong>{{ $preCount }} câu</strong>
                </div>
            @endif
            @if ($postCount > 0)
                <div class="teacher-course-session-stat">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Ôn tập sau buổi</span>
                    <strong>{{ $postCount }} câu</strong>
                </div>
            @endif
        </div>

        @if ($hasSummary)
            <section class="teacher-course-session-section">
                <h3><i class="fa-solid fa-align-left"></i> Tóm tắt buổi học</h3>
                <div class="course-rich-content teacher-course-session-rich">{!! $richText->renderComment((string) ($session['session_summary'] ?? '')) !!}</div>
            </section>
        @endif

        @if ($isEnded && $recordUrl !== '')
            <section class="teacher-course-session-section">
                <h3><i class="fa-solid fa-circle-play"></i> Video ghi hình</h3>
                <a class="op-btn-ghost" href="{{ $recordUrl }}" target="_blank" rel="noopener noreferrer">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Xem video buổi học</span>
                </a>
            </section>
        @endif

        @php $sessionRoomEvents = $sessionRoomEvents ?? []; @endphp
        @if ($sessionRoomEvents !== [])
            <section class="teacher-course-session-section" aria-label="Lịch sử hoạt động trong buổi học">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Lịch sử hoạt động trong buổi</h3>
                <p class="teacher-course-session-timeline-meta">{{ count($sessionRoomEvents) }} ghi nhận</p>
                <ol class="teacher-course-session-timeline">
                    @foreach ($sessionRoomEvents as $event)
                        <li>
                            <time>{{ $event['timeLabel'] }}</time>
                            <div>
                                <strong>{{ $event['name'] }}</strong>
                                <span>{{ $event['roleLabel'] }} · {{ $event['label'] }}</span>
                            </div>
                            <small>{{ $event['dateLabel'] }}</small>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    </article>
@endif
