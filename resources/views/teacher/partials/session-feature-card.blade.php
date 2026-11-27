@php
    $session = is_array($session ?? null) ? $session : [];
    $display = is_array($session['display'] ?? null) ? $session['display'] : [];
    $phase = (string) ($display['phase'] ?? $session['phase'] ?? 'upcoming');
    $hasSchedule = ! empty($display['hasSchedule']);
    $isJoinable = ! empty($session['is_joinable']) || $phase === 'live';
    $isOngoing = ! empty($session['is_ongoing']);
    $startsInMinutes = $session['starts_in_minutes'] ?? null;

    $levelTitle = trim((string) ($session['level_title'] ?? ''));
    $courseTitle = trim((string) ($session['course_title'] ?? 'Khóa học'));
    $sessionNumber = (int) ($session['session_number'] ?? 0);
    $lessonTitle = trim((string) ($session['lesson_title'] ?? ($session['title'] ?? 'Buổi học')));
    $headingTitle = $sessionNumber > 0 ? "{$courseTitle} · Buổi {$sessionNumber}" : $courseTitle;

    $memberCount = (int) ($session['member_count'] ?? 0);
    $pendingGradingCount = (int) ($session['pending_grading_count'] ?? 0);

    $manageUrl = (string) ($session['manage_url'] ?? route('dashboard'));
    $gradingUrl = (string) ($session['grading_url'] ?? route('teacher.grading'));
    $roomUrl = trim((string) ($session['room_lobby_url'] ?? ''));
    $showEnterRoom = $isJoinable && $roomUrl !== '';

    $timeLabel = $hasSchedule ? trim((string) ($display['timeLabel'] ?? '')) : '';
    $weekdayFull = $hasSchedule ? trim((string) ($display['weekdayFull'] ?? '')) : '';
    $dateLabel = $hasSchedule ? trim((string) ($display['dateLabel'] ?? '')) : '';
    $fullDateLine = $hasSchedule && $dateLabel !== ''
        ? ($weekdayFull !== '' ? "{$weekdayFull}, ngày {$dateLabel}" : "Ngày {$dateLabel}")
        : 'Chưa lên lịch';
    $opensAtLabel = $hasSchedule ? trim((string) ($display['opensAtLabel'] ?? '')) : '';

    if ($isOngoing) {
        $badgeTone = 'live';
        $badgeText = 'Đang live';
    } elseif ($isJoinable) {
        $badgeTone = 'ready';
        $badgeText = $startsInMinutes !== null ? "Còn {$startsInMinutes} phút" : 'Sẵn sàng vào lớp';
    } else {
        $badgeTone = 'upcoming';
        $badgeText = trim((string) ($display['countdownLabel'] ?? 'Sắp diễn ra'));
    }

    if ($showEnterRoom) {
        $helperText = 'Lớp học đã mở, bạn có thể vào lớp';
    } elseif ($hasSchedule && $opensAtLabel !== '') {
        $helperText = "Lớp học sẽ mở lúc {$opensAtLabel} (trước giờ dạy 15 phút)";
    } else {
        $helperText = 'Lịch buổi dạy sẽ được cập nhật sớm';
    }
@endphp

<article class="sn-td-session-card" aria-label="{{ $headingTitle }}">
    <header class="sn-td-session-head">
        <div class="sn-td-session-titles">
            @if ($levelTitle !== '')
                <span class="sn-td-level-pill">{{ $levelTitle }}</span>
            @endif
            <h2 class="sn-td-session-heading">{{ $headingTitle }}</h2>
            @if ($lessonTitle !== '')
                <p class="sn-td-session-subtitle">{{ $lessonTitle }}</p>
            @endif
        </div>

        <span class="sn-td-status-badge sn-td-status-badge--{{ $badgeTone }}">
            @if ($badgeTone === 'live')
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M10 11.9004C10 13.005 10.8954 13.9004 12 13.9004C13.1046 13.9004 14 13.005 14 11.9004C14 10.7958 13.1046 9.90039 12 9.90039C10.8954 9.90039 10 10.7958 10 11.9004Z" fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"/>
                    <path d="M16 11.9004C16 10.7564 15.617 9.70477 14.9718 8.86265C14.6359 8.42426 14.719 7.79657 15.1574 7.46067C15.5958 7.12476 16.2234 7.20785 16.5594 7.64624C17.4627 8.82522 18 10.3015 18 11.9004C18 13.5668 17.4164 15.0997 16.4434 16.3018C16.0959 16.7311 15.4662 16.7974 15.0369 16.4499C14.6077 16.1025 14.5413 15.4728 14.8888 15.0435C15.5842 14.1844 16 13.0923 16 11.9004Z" fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"/>
                    <path d="M8 11.9005C8 10.755 8.38397 9.70208 9.03075 8.85941C9.36702 8.42129 9.28446 7.79354 8.84635 7.45727C8.40824 7.121 7.78048 7.20356 7.44421 7.64167C6.53871 8.82142 6 10.2995 6 11.9005C6 13.5656 6.58267 15.0974 7.55437 16.2991C7.90163 16.7286 8.53128 16.7952 8.96074 16.4479C9.39019 16.1007 9.45682 15.471 9.10956 15.0416C8.41518 14.1828 8 13.0915 8 11.9005Z" fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"/>
                    <path d="M20 11.9003C20 9.66825 19.1888 7.62823 17.8438 6.05514C17.4849 5.63536 17.5342 5.00413 17.954 4.64523C18.3738 4.28633 19.005 4.33567 19.3639 4.75545C21.0069 6.67707 22 9.17398 22 11.9003C22 14.6893 20.9607 17.2381 19.2497 19.1766C18.8843 19.5907 18.2523 19.6301 17.8383 19.2646C17.4242 18.8991 17.3848 18.2672 17.7503 17.8531C19.1511 16.266 20 14.1835 20 11.9003Z" fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"/>
                    <path d="M4 11.9004C4 9.66855 4.81114 7.62864 6.15595 6.05559C6.51483 5.6358 6.46545 5.00457 6.04566 4.64568C5.62588 4.2868 4.99464 4.33618 4.63576 4.75597C2.99299 6.67754 2 9.17431 2 11.9004C2 14.6857 3.03649 17.2314 4.74335 19.1689C5.10842 19.5833 5.74032 19.6233 6.15474 19.2582C6.56915 18.8932 6.60915 18.2613 6.24408 17.8469C4.8466 16.2605 4 14.1806 4 11.9004Z" fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"/>
                </svg>
            @else
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 19C15.3137 19 18 16.3137 18 13C18 9.68629 15.3137 7 12 7C8.68629 7 6 9.68629 6 13C6 16.3137 8.68629 19 12 19ZM12 21C16.4183 21 20 17.4183 20 13C20 8.58172 16.4183 5 12 5C7.58172 5 4 8.58172 4 13C4 17.4183 7.58172 21 12 21Z" fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"/>
                    <path d="M7.46603 4.08491C7.21611 3.59349 6.61217 3.39396 6.14542 3.68738C4.75273 4.56291 3.57391 5.73995 2.69627 7.13131C2.40214 7.59761 2.60076 8.20186 3.0918 8.45252C3.58284 8.70319 4.17964 8.5045 4.4831 8.04421C5.15697 7.02206 6.03245 6.14791 7.05561 5.47558C7.51636 5.17282 7.71596 4.57633 7.46603 4.08491Z" fill="currentColor"/>
                    <path d="M16.534 4.08491C16.7839 3.59349 17.3878 3.39396 17.8546 3.68738C19.2473 4.56291 20.4261 5.73995 21.3037 7.13131C21.5978 7.59761 21.3992 8.20186 20.9082 8.45252C20.4171 8.70319 19.8203 8.5045 19.5169 8.04421C18.843 7.02206 17.9675 6.14791 16.9444 5.47558C16.4836 5.17282 16.284 4.57633 16.534 4.08491Z" fill="currentColor"/>
                    <path d="M12 8C12.5523 8 13 8.44772 13 9V12.5858L14.7071 14.2929C15.0976 14.6834 15.0976 15.3166 14.7071 15.7071C14.3166 16.0976 13.6834 16.0976 13.2929 15.7071L11.5858 14C11.2107 13.6249 11 13.1162 11 12.5858V9C11 8.44772 11.4477 8 12 8Z" fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"/>
                </svg>
            @endif
            <span>{{ $badgeText }}</span>
        </span>
    </header>

    <div class="sn-td-stat-grid">
        <div class="sn-td-stat-card">
            <div class="sn-td-stat-label">
                <span class="sn-td-stat-ico sn-td-stat-ico--orange" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M6.66669 1.66663V4.16663" stroke="#F26500" stroke-width="1.25" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13.3333 1.66663V4.16663" stroke="#F26500" stroke-width="1.25" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2.91669 7.57495H17.0834" stroke="#F26500" stroke-width="1.25" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17.5 7.08329V14.1666C17.5 16.6666 16.25 18.3333 13.3333 18.3333H6.66667C3.75 18.3333 2.5 16.6666 2.5 14.1666V7.08329C2.5 4.58329 3.75 2.91663 6.66667 2.91663H13.3333C16.25 2.91663 17.5 4.58329 17.5 7.08329Z" stroke="#F26500" stroke-width="1.25" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9.99626 11.4167H10.0037" stroke="#F26500" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6.91191 11.4167H6.91939" stroke="#F26500" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6.91191 13.9167H6.91939" stroke="#F26500" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span>Giờ dạy</span>
            </div>
            <strong class="sn-td-stat-value sn-td-stat-value--blue">{{ $timeLabel !== '' ? $timeLabel : 'Chưa đặt' }}</strong>
            <p class="sn-td-stat-sub">{{ $fullDateLine }}</p>
        </div>

        <div class="sn-td-stat-card">
            <div class="sn-td-stat-label">
                <span class="sn-td-stat-ico sn-td-stat-ico--green" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M1.66744 9.99996C1.66744 6.66084 1.66744 4.99129 2.64375 3.95396C3.62006 2.91663 5.19141 2.91663 8.33411 2.91663L11.6674 2.91663C14.8101 2.91663 16.3815 2.91663 17.3578 3.95396C18.3341 4.99129 18.3341 6.66084 18.3341 9.99996C18.3341 13.3391 18.3341 15.0086 17.3578 16.046C16.3815 17.0833 14.8101 17.0833 11.6674 17.0833L8.33411 17.0833C5.19141 17.0833 3.62006 17.0833 2.64375 16.046C1.66744 15.0086 1.66744 13.3391 1.66744 9.99996Z" stroke="#10B266" stroke-width="1.25" stroke-linejoin="round"/>
                        <path d="M4.16736 12.9166C5.5079 11.1355 8.62895 11.0382 10.0007 12.9166M8.54154 8.54171C8.54154 9.34712 7.88863 10 7.08321 10C6.2778 10 5.62488 9.34712 5.62488 8.54171C5.62488 7.73629 6.2778 7.08337 7.08321 7.08337C7.88863 7.08337 8.54154 7.73629 8.54154 8.54171Z" stroke="#10B266" stroke-width="1.25" stroke-linecap="round"/>
                        <path d="M12.5007 7.91663L15.8341 7.91663" stroke="#10B266" stroke-width="1.25" stroke-linecap="round"/>
                        <path d="M12.5007 11.25H14.1674" stroke="#10B266" stroke-width="1.25" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>Sĩ số lớp</span>
            </div>
            <strong class="sn-td-stat-value">{{ $memberCount }} học viên</strong>
            @if ($pendingGradingCount > 0)
                <p class="sn-td-stat-sub">
                    <a class="sn-td-stat-link" href="{{ $gradingUrl }}">{{ $pendingGradingCount }} bài tập chưa chấm trong lớp</a>
                </p>
            @else
                <p class="sn-td-stat-sub">Không có bài tập chưa chấm trong lớp</p>
            @endif
        </div>
    </div>

    <footer class="sn-td-session-foot">
        <p class="sn-td-session-note">{{ $helperText }}</p>

        @if ($showEnterRoom)
            <a class="sn-td-cta sn-td-cta--primary" href="{{ $roomUrl }}">
                <span>Vào lớp</span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM16.03 12.53L13.03 15.53C12.88 15.68 12.69 15.75 12.5 15.75C12.31 15.75 12.12 15.68 11.97 15.53C11.68 15.24 11.68 14.76 11.97 14.47L13.69 12.75H8.5C8.09 12.75 7.75 12.41 7.75 12C7.75 11.59 8.09 11.25 8.5 11.25H13.69L11.97 9.53C11.68 9.24 11.68 8.76 11.97 8.47C12.26 8.18 12.74 8.18 13.03 8.47L16.03 11.47C16.32 11.76 16.32 12.24 16.03 12.53Z" fill="currentColor"/>
                </svg>
            </a>
        @else
            <a class="sn-td-cta sn-td-cta--secondary" href="{{ $manageUrl }}">
                <span>Xem chi tiết</span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM16.03 12.53L13.03 15.53C12.88 15.68 12.69 15.75 12.5 15.75C12.31 15.75 12.12 15.68 11.97 15.53C11.68 15.24 11.68 14.76 11.97 14.47L13.69 12.75H8.5C8.09 12.75 7.75 12.41 7.75 12C7.75 11.59 8.09 11.25 8.5 11.25H13.69L11.97 9.53C11.68 9.24 11.68 8.76 11.97 8.47C12.26 8.18 12.74 8.18 13.03 8.47L16.03 11.47C16.32 11.76 16.32 12.24 16.03 12.53Z" fill="currentColor"/>
                </svg>
            </a>
        @endif
    </footer>
</article>
