@php
    $notificationData = is_array($notifications ?? null) ? $notifications : ['count' => 0, 'items' => []];
    $notificationCount = max(0, (int) ($notificationData['count'] ?? 0));
    $notificationItems = is_array($notificationData['items'] ?? null) ? $notificationData['items'] : [];
    $notificationBadge = $notificationCount > 99 ? '99+' : (string) $notificationCount;
    $notificationViewAllUrl = route('notifications.index');

    $toneForIcon = static function (string $icon): string {
        return match (true) {
            str_contains($icon, 'check') => 'green',
            str_contains($icon, 'xmark') || str_contains($icon, 'x-circle') => 'red',
            str_contains($icon, 'hourglass') => 'blue',
            str_contains($icon, 'clipboard') || str_contains($icon, 'list-check') => 'purple',
            str_contains($icon, 'video') || str_contains($icon, 'comments') => 'blue',
            default => 'orange',
        };
    };
@endphp

<details
    class="os-drop os-notify"
    data-notification-root
    data-notification-read-url="{{ route('notifications.read') }}"
    data-notification-read-all-url="{{ route('notifications.read-all') }}"
>
    <summary class="os-icon-btn os-notify-trigger" title="Thông báo" aria-label="Thông báo">
        <i class="fa-solid fa-bell"></i>
        <span
            class="os-notify-badge{{ $notificationCount > 0 ? '' : ' is-hidden' }}"
            data-notification-badge
            aria-hidden="{{ $notificationCount > 0 ? 'false' : 'true' }}"
        >{{ $notificationBadge }}</span>
    </summary>
    <div class="os-notify-card" role="menu">
        <div class="notification-head">
            <div class="notification-head-main">
                <span class="notification-head-icon"><i class="fa-solid fa-bell"></i></span>
                <div>
                    <strong>Thông báo</strong>
                    @if ($notificationCount > 0)
                        <small data-notification-subtitle>{{ $notificationBadge }} mục chưa đọc</small>
                    @else
                        <small data-notification-subtitle>Tất cả đã cập nhật</small>
                    @endif
                </div>
            </div>
            <a class="notification-view-all" href="{{ $notificationViewAllUrl }}">
                {{ $notificationCount > 0 ? 'Xem tất cả' : 'Xem trang thông báo' }}
            </a>
        </div>
        <div class="notification-body" data-notification-body>
            @if ($notificationItems === [])
                <div class="notification-empty" data-notification-empty>
                    <div class="notification-empty-visual" aria-hidden="true">
                        <span class="notification-empty-glow"></span>
                        <span class="notification-empty-ring"></span>
                        <span class="notification-empty-icon"><i class="fa-solid fa-bell-slash"></i></span>
                    </div>
                    <strong class="notification-empty-title">Chưa có thông báo mới</strong>
                    <p class="notification-empty-text">Khi có đơn xin nghỉ, phản hồi giáo viên hoặc cập nhật khóa học, chúng tôi sẽ hiển thị tại đây.</p>
                </div>
            @else
                <div class="notification-list" data-notification-list>
                    @foreach ($notificationItems as $item)
                        @php
                            $icon = (string) ($item['icon'] ?? 'fa-bell');
                            $tone = $toneForIcon($icon);
                        @endphp
                        <a
                            class="notification-item{{ ! empty($item['unread']) ? ' is-unread' : '' }}"
                            href="{{ $item['url'] ?? '#' }}"
                            data-notification-id="{{ $item['id'] ?? '' }}"
                            data-notification-link
                        >
                            <span class="notification-item-icon tone-{{ $tone }}"><i class="fa-solid {{ $icon }}"></i></span>
                            <div class="notification-item-content">
                                <strong>{{ $item['title'] ?? 'Thông báo' }}</strong>
                                <small>{{ $item['text'] ?? '' }}</small>
                            </div>
                            @if (! empty($item['time']))
                                <time class="notification-item-time">{{ $item['time'] }}</time>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        @if ($notificationCount > 0)
            <div class="notification-foot" data-notification-foot>
                @if ($notificationCount > count($notificationItems))
                    <a class="notification-foot-link" href="{{ $notificationViewAllUrl }}">
                        Xem thêm {{ $notificationCount - count($notificationItems) }} thông báo
                    </a>
                @endif
                <button type="button" class="notification-mark-all" data-notification-mark-all>
                    <i class="fa-solid fa-check-double"></i>
                    <span>Đã đọc tất cả</span>
                </button>
            </div>
        @endif
    </div>
</details>
