@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
@php
    $unreadCount = (int) ($unreadCount ?? 0);
    $totalCount = (int) ($totalCount ?? 0);
    $filteredCount = (int) ($filteredCount ?? 0);
    $filters = is_array($filters ?? null) ? $filters : ['loai' => '', 'doc' => 'all'];
    $typeOptions = is_array($typeOptions ?? null) ? $typeOptions : [];
    $typeCounts = is_array($typeCounts ?? null) ? $typeCounts : [];
    $items = is_array($items ?? null) ? $items : [];
    $readFilter = (string) ($filters['doc'] ?? 'all');
    $typeFilter = (string) ($filters['loai'] ?? '');

    $buildFilterUrl = static function (string $doc, ?string $loai = null) use ($typeFilter): string {
        $params = [];

        if ($doc !== 'all') {
            $params['doc'] = $doc;
        }

        $loaiValue = $loai ?? $typeFilter;

        if ($loaiValue !== '') {
            $params['loai'] = $loaiValue;
        }

        return $params === [] ? route('notifications.index') : route('notifications.index', $params);
    };

    $toneForItem = static function (array $item): string {
        $icon = (string) ($item['icon'] ?? 'fa-bell');
        $type = (string) ($item['type'] ?? '');

        return match ($type) {
            'chat' => 'blue',
            'session' => 'purple',
            'assessment' => 'green',
            'evaluation' => 'purple',
            'leave' => str_contains($icon, 'check') ? 'green' : (str_contains($icon, 'xmark') ? 'red' : 'orange'),
            default => 'orange',
        };
    };

    $typeLabel = static function (string $type): string {
        return \App\Services\TopbarNotificationService::TYPE_LABELS[$type] ?? $type;
    };
@endphp

<div
    class="notifications-page"
    data-notifications-page
    data-notification-read-url="{{ route('notifications.read') }}"
    data-notification-read-all-url="{{ route('notifications.read-all') }}"
>
    <x-page-title
        kicker="Cập nhật"
        icon="fa-bell"
        title="Thông báo"
        lead="Theo dõi buổi học, đơn xin nghỉ, bài kiểm tra và đánh giá học viên."
    >
        <x-slot:meta>
            <span><i class="fa-solid fa-inbox"></i>{{ $totalCount }} thông báo</span>
            @if ($unreadCount > 0)
                <span><i class="fa-solid fa-envelope"></i>{{ $unreadCount }} chưa đọc</span>
            @endif
            @if ($filteredCount !== $totalCount)
                <span><i class="fa-solid fa-filter"></i>{{ $filteredCount }} đang hiển thị</span>
            @endif
        </x-slot:meta>
        <x-slot:aside>
            <span class="op-hero-pill">
                <strong>{{ $unreadCount }}</strong>
                chưa đọc
            </span>
        </x-slot:aside>
    </x-page-title>

    <div class="notifications-toolbar">
        <form class="notifications-filters" method="get" action="{{ route('notifications.index') }}">
            <div class="notifications-filter-tabs" role="tablist" aria-label="Trạng thái đọc">
                <a
                    class="notifications-tab{{ $readFilter === 'all' ? ' is-active' : '' }}"
                    href="{{ $buildFilterUrl('all') }}"
                    role="tab"
                    aria-selected="{{ $readFilter === 'all' ? 'true' : 'false' }}"
                >Tất cả</a>
                <a
                    class="notifications-tab{{ $readFilter === 'unread' ? ' is-active' : '' }}"
                    href="{{ $buildFilterUrl('unread') }}"
                    role="tab"
                    aria-selected="{{ $readFilter === 'unread' ? 'true' : 'false' }}"
                >Chưa đọc{{ $unreadCount > 0 ? ' ('.$unreadCount.')' : '' }}</a>
                <a
                    class="notifications-tab{{ $readFilter === 'read' ? ' is-active' : '' }}"
                    href="{{ $buildFilterUrl('read') }}"
                    role="tab"
                    aria-selected="{{ $readFilter === 'read' ? 'true' : 'false' }}"
                >Đã đọc</a>
            </div>

            <div class="notifications-filter-type">
                <label class="notifications-filter-label" for="notification-type-filter">Loại</label>
                <select class="notifications-type-select" id="notification-type-filter" name="loai" onchange="this.form.submit()">
                    @foreach ($typeOptions as $option)
                        <option
                            value="{{ $option['value'] ?? '' }}"
                            @selected(($option['value'] ?? '') === $typeFilter)
                        >{{ $option['label'] ?? '' }}</option>
                    @endforeach
                </select>
                @if ($readFilter !== 'all')
                    <input type="hidden" name="doc" value="{{ $readFilter }}">
                @endif
            </div>

            @if ($unreadCount > 0)
                <button type="button" class="op-btn-ghost notifications-mark-all" data-notifications-mark-all>
                    <i class="fa-solid fa-check-double"></i>
                    Đánh dấu tất cả đã đọc
                </button>
            @endif
        </form>

        @if ($typeCounts !== [])
            <div class="notifications-type-chips" aria-label="Lọc nhanh theo loại">
                @foreach ($typeCounts as $type => $count)
                    @if ((int) $count <= 0)
                        @continue
                    @endif
                    <a
                        class="notifications-type-chip{{ $typeFilter === $type ? ' is-active' : '' }}"
                        href="{{ $buildFilterUrl($readFilter, (string) $type) }}"
                    >
                        {{ $typeLabel((string) $type) }}
                        <span>{{ $count }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="notifications-panel">
        @if ($items === [])
            <div class="notifications-empty">
                <div class="notification-empty-visual" aria-hidden="true">
                    <span class="notification-empty-glow"></span>
                    <span class="notification-empty-ring"></span>
                    <span class="notification-empty-icon"><i class="fa-solid fa-bell-slash"></i></span>
                </div>
                <strong class="notification-empty-title">
                    @if ($readFilter === 'unread')
                        Không còn thông báo chưa đọc
                    @elseif ($readFilter === 'read')
                        Chưa có thông báo đã đọc
                    @else
                        Chưa có thông báo
                    @endif
                </strong>
                <p class="notification-empty-text">
                    @if ($typeFilter !== '')
                        Thử chọn loại khác hoặc xem tất cả thông báo.
                    @else
                        Khi có buổi học, đơn xin nghỉ hoặc bài kiểm tra, chúng tôi sẽ hiển thị tại đây.
                    @endif
                </p>
                @if ($typeFilter !== '' || $readFilter !== 'all')
                    <a class="op-btn-ghost" href="{{ route('notifications.index') }}">Xem tất cả thông báo</a>
                @endif
            </div>
        @else
            <div class="notifications-list" data-notifications-list>
                @foreach ($items as $item)
                    @php
                        $icon = (string) ($item['icon'] ?? 'fa-bell');
                        $tone = $toneForItem($item);
                        $isUnread = ! empty($item['unread']);
                    @endphp
                    <a
                        class="notifications-row{{ $isUnread ? ' is-unread' : '' }}"
                        href="{{ $item['url'] ?? '#' }}"
                        data-notification-id="{{ $item['id'] ?? '' }}"
                        data-notification-link
                    >
                        <span class="notification-item-icon tone-{{ $tone }}">
                            <i class="fa-solid {{ $icon }}"></i>
                        </span>
                        <div class="notifications-row-body">
                            <div class="notifications-row-head">
                                <strong>{{ $item['title'] ?? 'Thông báo' }}</strong>
                                @if (! empty($item['type']))
                                    <span class="notifications-row-type">{{ $typeLabel((string) $item['type']) }}</span>
                                @endif
                            </div>
                            <p>{{ $item['text'] ?? '' }}</p>
                        </div>
                        <div class="notifications-row-meta">
                            @if (! empty($item['time']))
                                <time>{{ $item['time'] }}</time>
                            @endif
                            @if ($isUnread)
                                <span class="notifications-unread-dot" aria-label="Chưa đọc"></span>
                            @endif
                            <i class="fa-solid fa-chevron-right notifications-row-chevron" aria-hidden="true"></i>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/notifications-page.js') }}"></script>
@endpush
