@php
    $sessionReminders = is_array($sessionReminders ?? null) ? $sessionReminders : [];
@endphp

@if ($sessionReminders !== [])
    @php
        $homeworkCount = count(array_filter($sessionReminders, static fn (array $item): bool => ($item['type'] ?? '') === 'homework'));
        $reviewCount = count($sessionReminders) - $homeworkCount;
    @endphp
    <article class="op-panel so-pending" aria-label="Bài tập và ôn luyện chưa hoàn thành">
        <header class="so-pending-head">
            <div class="so-pending-head-main">
                <span class="op-panel-ico" aria-hidden="true"><i class="fa-solid fa-clipboard-check"></i></span>
                <div>
                    <h2>Bài tập &amp; ôn luyện</h2>
                    <p>Còn bài chưa làm — bấm để vào làm ngay</p>
                </div>
            </div>
            <div class="so-pending-badges">
                @if ($homeworkCount > 0)
                    <span class="so-pending-badge is-homework">{{ $homeworkCount }} về nhà</span>
                @endif
                @if ($reviewCount > 0)
                    <span class="so-pending-badge is-review">{{ $reviewCount }} ôn tập</span>
                @endif
            </div>
        </header>

        <ul class="so-pending-list">
            @foreach ($sessionReminders as $reminder)
                @php $type = (string) ($reminder['type'] ?? ''); @endphp
                <li>
                    <a class="so-pending-link is-{{ $type !== '' ? $type : 'homework' }}" href="{{ $reminder['url'] ?? '#' }}">
                        <span class="so-pending-link-icon">
                            <i class="fa-solid {{ $type === 'review' ? 'fa-lightbulb' : 'fa-file-lines' }}" aria-hidden="true"></i>
                        </span>
                        <span class="so-pending-link-copy">
                            <em>{{ $reminder['sessionTitle'] ?? 'Buổi học' }}</em>
                            <strong>{{ $reminder['label'] ?? '' }}</strong>
                        </span>
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </a>
                </li>
            @endforeach
        </ul>
    </article>
@endif
