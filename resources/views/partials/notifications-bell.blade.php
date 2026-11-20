@php
    $notificationData = is_array($notifications ?? null) ? $notifications : ['count' => 0, 'items' => []];
    $notificationCount = max(0, (int) ($notificationData['count'] ?? 0));
    $notificationItems = is_array($notificationData['items'] ?? null) ? $notificationData['items'] : [];
    $notificationBadge = $notificationCount > 99 ? '99+' : (string) $notificationCount;
    $notificationViewAllUrl = route('notifications.index');
@endphp

<details
    class="os-drop os-notify sn-notify-dropdown"
    data-notification-root
    data-notification-read-url="{{ route('notifications.read') }}"
    data-notification-read-all-url="{{ route('notifications.read-all') }}"
>
    <summary class="sn-topbar-circle-btn sn-topbar-notify-btn os-notify-trigger" title="Thông báo" aria-label="Thông báo">
        <img src="{{ asset('assets/images/figma-notification.svg') }}" alt="Thông báo" width="24" height="24">
        <span
            class="sn-topbar-notify-badge os-notify-badge{{ $notificationCount > 0 ? '' : ' is-hidden' }}"
            data-notification-badge
            aria-hidden="{{ $notificationCount > 0 ? 'false' : 'true' }}"
        >{{ $notificationBadge }}</span>
    </summary>

    <!-- Bảng thông báo nổi (Figma Component #1020:6149 / ComponentSet #1020:6983) -->
    <div class="os-notify-card sn-notify-popover" role="menu">
        <!-- Danh sách cuộn thông báo -->
        <div class="sn-notify-body" data-notification-body>
            @if ($notificationItems === [])
                <div class="sn-notify-empty" data-notification-empty>
                    <div class="sn-notify-empty-icon" aria-hidden="true">
                        <img src="{{ asset('assets/images/figma-notification.svg') }}" width="28" height="28" alt="" style="opacity: 0.35;">
                    </div>
                    <strong class="sn-notify-empty-title">Chưa có thông báo mới</strong>
                    <p class="sn-notify-empty-text">Bạn sẽ nhận thông báo khi có lịch học, phản hồi giáo viên hoặc cập nhật khóa học.</p>
                </div>
            @else
                <div class="sn-notify-list" data-notification-list>
                    @foreach ($notificationItems as $item)
                        @php
                            $isUnread = ! empty($item['unread']);
                        @endphp
                        <a
                            class="sn-notify-item{{ $isUnread ? ' is-unread' : ' is-read' }}"
                            href="{{ $item['url'] ?? '#' }}"
                            data-notification-id="{{ $item['id'] ?? '' }}"
                            data-notification-link
                        >
                            <strong class="sn-notify-item-title">{{ $item['title'] ?? 'Thông báo' }}</strong>
                            @if (! empty($item['text']))
                                <p class="sn-notify-item-desc">{{ $item['text'] }}</p>
                            @endif
                            @if (! empty($item['time']))
                                <time class="sn-notify-item-time">{{ $item['time'] }}</time>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Thao tác: Xem tất cả & Đánh dấu đã đọc (Figma Node #1020:6259) -->
        <div class="sn-notify-actions">
            <a class="sn-btn sn-btn--white sn-btn--m notification-view-all" href="{{ $notificationViewAllUrl }}">
                Xem tất cả
            </a>
            <button
                type="button"
                class="sn-btn sn-btn--primary sn-btn--m notification-mark-all{{ $notificationCount > 0 ? '' : ' is-disabled' }}"
                data-notification-mark-all
                {{ $notificationCount > 0 ? '' : 'disabled' }}
            >
                Đánh dấu đã đọc
            </button>
        </div>
    </div>
</details>
