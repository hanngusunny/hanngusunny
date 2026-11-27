@php
    $items = $resultsData['items'] ?? [];
    $counts = $resultsData['counts'] ?? ['all' => 0, 'pre' => 0, 'post' => 0, 'kr' => 0, 'exam' => 0];
    $activeFilter = (string) ($activeFilter ?? 'all');

    $filters = [
        'all' => 'Tất cả',
        'pre' => 'Trước buổi',
        'post' => 'Sau buổi',
        'kr' => 'Bài kiểm tra',
        'exam' => 'Bài thi',
    ];

    $visibleCount = $activeFilter === 'all'
        ? (int) ($counts['all'] ?? 0)
        : (int) ($counts[$activeFilter] ?? 0);
@endphp

<div class="sn-res-list-wrap" data-sn-results-root data-active-filter="{{ $activeFilter }}">
    <div class="sn-res-list-head">
        <h1 class="sn-eval-section-title">Kết quả bài làm</h1>

        <div class="sn-res-filters" role="tablist" aria-label="Lọc loại bài làm">
            @foreach ($filters as $key => $label)
                <button
                    type="button"
                    class="sn-res-filter-pill{{ $activeFilter === $key ? ' is-active' : '' }}"
                    data-sn-filter="{{ $key }}"
                    role="tab"
                    aria-selected="{{ $activeFilter === $key ? 'true' : 'false' }}"
                >
                    {{ $label }} ({{ (int) ($counts[$key] ?? 0) }})
                </button>
            @endforeach
        </div>
    </div>

    <div class="sn-res-well" id="snResWell" @if ($visibleCount === 0) hidden @endif>
        @foreach ($items as $item)
            @php
                $isHidden = $activeFilter !== 'all' && ($item['type'] ?? '') !== $activeFilter;
                $detailHref = route('student.evaluations', [
                    'tab' => 'ket-qua',
                    'loai' => $item['type'],
                    'id' => $item['id'],
                    'loc' => $activeFilter,
                ]);
                $icoTone = match ($item['type'] ?? '') {
                    'exam' => 'orange',
                    'kr' => 'purple',
                    default => 'blue',
                };
            @endphp
            <a
                href="{{ $detailHref }}"
                class="sn-res-row"
                data-sn-res-item
                data-res-type="{{ $item['type'] }}"
                data-base-url="{{ $item['detail_url'] }}"
                @if ($isHidden) hidden @endif
            >
                <span class="sn-res-row-ico sn-res-row-ico--{{ $icoTone }}" aria-hidden="true">
                    @if ($icoTone === 'orange')
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M15 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V7L15 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 2V6C14 6.53043 14.2107 7.03914 14.5858 7.41421C14.9609 7.78929 15.4696 8 16 8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 15L11 17L15 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    @elseif ($icoTone === 'purple')
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M9 2H15C15.5523 2 16 2.44772 16 3V5C16 5.55228 15.5523 6 15 6H9C8.44772 6 8 5.55228 8 5V3C8 2.44772 8.44772 2 9 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 4H18C18.5304 4 19.0391 4.21071 19.4142 4.58579C19.7893 4.96086 20 5.46957 20 6V20C20 20.5304 19.7893 21.0391 19.4142 21.4142C19.0391 21.7893 18.5304 22 18 22H6C5.46957 22 4.96086 21.7893 4.58579 21.4142C4.21071 21.0391 4 20.5304 4 20V6C4 5.46957 4.21071 4.96086 4.58579 4.58579C4.96086 4.21071 5.46957 4 6 4H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 14L11 16L15 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    @else
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M4 19.5V4.5C4 3.83696 4.26339 3.20107 4.73223 2.73223C5.20107 2.26339 5.83696 2 6.5 2H20V17M4 19.5C4 20.163 4.26339 20.7989 4.73223 21.2678C5.20107 21.7366 5.83696 22 6.5 22H20V17M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    @endif
                </span>

                <div class="sn-res-row-main">
                    <div class="sn-res-row-title-line">
                        <strong class="sn-res-row-title">{{ $item['title'] }}</strong>
                        @if (! empty($item['is_new']))
                            <span class="sn-res-new-pill">Mới</span>
                        @endif
                    </div>
                    <p class="sn-res-row-sub">{{ $item['subtitle'] }}</p>
                </div>

                <div class="sn-res-row-status">
                    <span class="sn-res-status-pill sn-res-status-pill--{{ $item['status_key'] }}">
                        {{ $item['status_label'] }}
                    </span>
                    <span class="sn-res-status-sub">{{ $item['status_sub'] }}</span>
                </div>

                <div class="sn-res-row-score">
                    <strong class="{{ ! empty($item['is_pending']) ? 'is-pending' : '' }}">{{ $item['score_display'] }}</strong>
                </div>

                <svg class="sn-res-row-chev" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M7.5 15L12.5 10L7.5 5" stroke="#63758D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        @endforeach
    </div>

    {{-- Trạng thái danh sách trống (#717:25834) --}}
    <div class="sn-eval-empty-state" id="snResEmpty" @if ($visibleCount > 0) hidden @endif>
        <div class="sn-eval-empty-ico" aria-hidden="true">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                <circle cx="40" cy="40" r="40" fill="#E8EFF9"/>
                <path d="M27 25C27 22.8 28.8 21 31 21H45L53 29V55C53 57.2 51.2 59 49 59H31C28.8 59 27 57.2 27 55V25Z" fill="#FFFFFF"/>
                <path d="M33 49H47M33 53H42" stroke="#E4E8ED" stroke-width="2.4" stroke-linecap="round"/>
                <circle cx="40" cy="37" r="10" fill="#0D99FF"/>
                <path d="M36.5 33.5L43.5 40.5M43.5 33.5L36.5 40.5" stroke="#FFFFFF" stroke-width="2.4" stroke-linecap="round"/>
            </svg>
        </div>
        <h2 class="sn-eval-empty-title">Chưa có kết quả</h2>
        <p class="sn-eval-empty-desc">
            Kết quả bài tập trước buổi, sau buổi, bài thi định kỳ và bài kiểm tra sẽ hiển thị tại đây sau khi bạn hoàn thành và giáo viên chấm.
        </p>
    </div>
</div>
