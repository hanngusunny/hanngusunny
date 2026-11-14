@php
    $rows = $rows ?? collect();
@endphp

<div class="tc-list" id="tc-list">
    @forelse ($rows as $row)
        @php
            $session = $row->session;
            $course = $session?->course;
            $student = $row->user;
            $rating = (int) ($row->session_rating ?? 0);
            $date = $session?->dateForInput() ?: '';
            $dateLabel = $date !== '' ? date('d/m/Y', strtotime($date) ?: time()) : '—';
            $timeLabel = trim(
                ($session?->timeForInput($session->start_time) ?: '')
                .(($session?->timeForInput($session->end_time) ?: '') !== ''
                    ? ' – '.$session->timeForInput($session->end_time)
                    : '')
            );
            $comment = trim((string) ($row->student_comment ?? ''));
        @endphp
        <article class="op-panel tc-card">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-star"></i></span>
                <span class="op-panel-title">{{ $student?->name ?: 'Học viên #'.$row->user_id }}</span>
                <span class="tc-card-meta">{{ $dateLabel }}{{ $timeLabel !== '' ? ' · '.$timeLabel : '' }}</span>
            </div>
            <div class="op-panel-body">
                <div class="tc-grid">
                    <aside class="tc-meta">
                        <div class="tc-kv">
                            <span>Khóa học</span>
                            <strong>{{ $course?->title ?: '—' }}</strong>
                        </div>
                        <div class="tc-kv">
                            <span>Buổi học</span>
                            <strong>{{ $session?->title ?: '—' }}</strong>
                        </div>
                        <div class="tc-kv">
                            <span>Giáo viên</span>
                            <strong>{{ $course?->teacher?->name ?: '—' }}</strong>
                        </div>
                        <div class="tc-kv">
                            <span>SĐT</span>
                            <strong>{{ $student?->phone ?: '—' }}</strong>
                        </div>
                        <div class="tc-card-foot">
                            Gửi lúc {{ optional($row->updated_at)->format('H:i d/m/Y') ?: '—' }}
                        </div>
                    </aside>
                    <div class="tc-content">
                        @if ($rating >= 1 && $rating <= 5)
                            <div class="tc-scores sf-rating">
                                <span class="tc-score">
                                    <em>Đánh giá buổi học</em>
                                    <strong>
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fa-{{ $i <= $rating ? 'solid' : 'regular' }} fa-star"></i>
                                        @endfor
                                        <span>{{ $rating }}/5</span>
                                    </strong>
                                </span>
                            </div>
                        @endif
                        <div class="tc-comment course-rich-content">
                            @if ($comment !== '')
                                {!! nl2br(e($comment)) !!}
                            @else
                                <span class="text-slate-400">Học viên chưa viết nhận xét (chỉ chấm điểm).</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <section class="op-panel">
            <div class="op-panel-body">
                <p class="text-center text-slate-500 py-8">Chưa có nhận xét nào khớp bộ lọc.</p>
            </div>
        </section>
    @endforelse
</div>

<div class="tc-pager" id="tc-pager">
    @if ($rows instanceof \Illuminate\Contracts\Pagination\Paginator && $rows->hasPages())
        {{ $rows->onEachSide(1)->links('pagination.office') }}
    @endif
</div>
