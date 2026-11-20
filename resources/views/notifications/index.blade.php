@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
@php
    $unreadCount = (int) ($unreadCount ?? 0);
    $totalCount = (int) ($totalCount ?? 0);
    $filteredCount = (int) ($filteredCount ?? 0);
    $filters = is_array($filters ?? null) ? $filters : ['loai' => '', 'doc' => 'all'];
    $items = is_array($items ?? null) ? $items : [];
    $groupedItems = is_array($groupedItems ?? null) ? $groupedItems : [];
    $readFilter = (string) ($filters['doc'] ?? 'all');
    $typeFilter = (string) ($filters['loai'] ?? '');

    $buildFilterUrl = static function (string $doc) use ($typeFilter): string {
        $params = [];

        if ($doc !== 'all') {
            $params['doc'] = $doc;
        }

        if ($typeFilter !== '') {
            $params['loai'] = $typeFilter;
        }

        return $params === [] ? route('notifications.index') : route('notifications.index', $params);
    };
@endphp

<div
    class="sn-notif-page"
    data-notifications-page
    data-notification-read-url="{{ route('notifications.read') }}"
    data-notification-read-all-url="{{ route('notifications.read-all') }}"
>
    <!-- 1. Tiêu đề trang (Figma Node #425:39464) -->
    <div class="sn-notif-header">
        <h1 class="sn-notif-title">Thông báo</h1>
    </div>

    <!-- 2. Thanh công cụ lọc & thao tác (Figma Node #425:39468) -->
    <div class="sn-notif-toolbar">
        <div class="sn-notif-tabs" role="tablist" aria-label="Bộ lọc thông báo">
            <a
                class="sn-notif-tab{{ $readFilter === 'all' ? ' is-active' : '' }}"
                href="{{ $buildFilterUrl('all') }}"
                role="tab"
                aria-selected="{{ $readFilter === 'all' ? 'true' : 'false' }}"
                data-filter-tab="all"
            >
                Tất cả
            </a>
            <a
                class="sn-notif-tab{{ $readFilter === 'unread' ? ' is-active' : '' }}"
                href="{{ $buildFilterUrl('unread') }}"
                role="tab"
                aria-selected="{{ $readFilter === 'unread' ? 'true' : 'false' }}"
                data-filter-tab="unread"
            >
                Chưa đọc (<span data-unread-count>{{ $unreadCount }}</span>)
            </a>
            <a
                class="sn-notif-tab{{ $readFilter === 'read' ? ' is-active' : '' }}"
                href="{{ $buildFilterUrl('read') }}"
                role="tab"
                aria-selected="{{ $readFilter === 'read' ? 'true' : 'false' }}"
                data-filter-tab="read"
            >
                Đã đọc
            </a>
        </div>

        <button
            type="button"
            class="sn-notif-mark-all-btn{{ $unreadCount > 0 ? '' : ' is-disabled' }}"
            data-notifications-mark-all
            {{ $unreadCount > 0 ? '' : 'disabled' }}
        >
            Đánh dấu tất cả đã đọc
        </button>
    </div>

    <!-- 3. Khu vực danh sách thông báo theo nhóm ngày (Figma Node #425:39477) -->
    <div class="sn-notif-container" data-notifications-container>
        @php
            $initialVisibleTotal = 0;
            foreach ($items as $chkItem) {
                $isU = ! empty($chkItem['unread']);
                if ($readFilter === 'all' || ($readFilter === 'unread' && $isU) || ($readFilter === 'read' && ! $isU)) {
                    $initialVisibleTotal++;
                }
            }
        @endphp

        <div class="sn-notif-empty" data-notifications-empty @if ($initialVisibleTotal > 0) style="display: none;" @endif>
            <div class="sn-notif-empty-icon" aria-hidden="true">
                <img src="{{ asset('assets/images/figma-notification.svg') }}" width="28" height="28" alt="" style="opacity: 0.35;">
            </div>
            <strong class="sn-notif-empty-title" data-empty-title>
                @if ($readFilter === 'unread')
                    Không có thông báo chưa đọc
                @elseif ($readFilter === 'read')
                    Chưa có thông báo đã đọc
                @else
                    Chưa có thông báo mới
                @endif
            </strong>
            <p class="sn-notif-empty-text">Bạn sẽ nhận thông báo khi có lịch học, phản hồi giáo viên hoặc cập nhật khóa học.</p>
        </div>

        @if ($items !== [])
            @foreach ($groupedItems as $groupTitle => $groupList)
                @if ($groupList === [])
                    @continue
                @endif
                @php
                    $visibleCountInGroup = 0;
                    foreach ($groupList as $gItem) {
                        $isU = ! empty($gItem['unread']);
                        if ($readFilter === 'all' || ($readFilter === 'unread' && $isU) || ($readFilter === 'read' && ! $isU)) {
                            $visibleCountInGroup++;
                        }
                    }
                @endphp
                <div class="sn-notif-group" data-notif-group @if ($visibleCountInGroup === 0) style="display: none;" @endif>
                    <h2 class="sn-notif-group-title">{{ $groupTitle }}</h2>
                    <div class="sn-notif-group-items">
                        @foreach ($groupList as $item)
                            @php
                                $isUnread = ! empty($item['unread']);
                                $hideInitially = ($readFilter === 'unread' && ! $isUnread) || ($readFilter === 'read' && $isUnread);
                            @endphp
                            <a
                                class="sn-notif-card{{ $isUnread ? ' is-unread' : ' is-read' }}"
                                href="{{ $item['url'] ?? '#' }}"
                                data-notification-id="{{ $item['id'] ?? '' }}"
                                data-read-state="{{ $isUnread ? 'unread' : 'read' }}"
                                data-notification-link
                                @if ($hideInitially) style="display: none;" @endif
                            >
                                <div class="sn-notif-card-main">
                                    <h3 class="sn-notif-card-title">{{ $item['title'] ?? 'Thông báo' }}</h3>
                                    @if (! empty($item['text']))
                                        <p class="sn-notif-card-desc">{{ $item['text'] }}</p>
                                    @endif
                                    @if (! empty($item['time']))
                                        <time class="sn-notif-card-time">{{ $item['time'] }}</time>
                                    @endif
                                </div>
                                <div class="sn-notif-card-status">
                                    @if ($isUnread)
                                        <span class="sn-notif-unread-dot" aria-label="Chưa đọc" data-unread-dot></span>
                                    @else
                                        <span class="sn-notif-read-badge">Đã đọc</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/notifications-page.js') }}"></script>
@endpush
