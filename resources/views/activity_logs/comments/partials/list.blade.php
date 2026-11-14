@php
    $rows = $rows ?? collect();
    $scoreLabels = \App\Models\CourseMonthlyEvaluation::scoreLabels();
@endphp

<div class="tc-list" id="tc-list">
    @forelse ($rows as $row)
        @php
            $periodLabel = \App\Models\CourseMonthlyEvaluation::formatPeriodLabel((string) $row->eval_month);
            $summary = $row->summary();
        @endphp
        <article class="op-panel tc-card">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-comment-dots"></i></span>
                <span class="op-panel-title">{{ $row->student?->name ?: 'Học viên #'.$row->user_id }}</span>
                <span class="tc-card-meta">{{ $periodLabel }}</span>
            </div>
            <div class="op-panel-body">
                <div class="tc-grid">
                    <aside class="tc-meta">
                        <div class="tc-kv">
                            <span>Khóa học</span>
                            <strong>{{ $row->course?->title ?: '—' }}</strong>
                        </div>
                        <div class="tc-kv">
                            <span>Giáo viên</span>
                            <strong>{{ $row->teacher?->name ?: '—' }}</strong>
                        </div>
                        <div class="tc-kv">
                            <span>SĐT</span>
                            <strong>{{ $row->student?->phone ?: '—' }}</strong>
                        </div>
                        <div class="tc-card-foot">
                            Cập nhật {{ optional($row->updated_at)->format('H:i d/m/Y') ?: '—' }}
                        </div>
                    </aside>
                    <div class="tc-content">
                        @if ($summary !== '')
                            <div class="tc-scores">
                                @foreach ([
                                    'score_attitude' => 'Thái độ',
                                    'score_progress' => 'Tiến độ',
                                    'score_participation' => 'Tham gia',
                                ] as $field => $label)
                                    @php $score = (int) ($row->{$field} ?? 0); @endphp
                                    @if ($score >= 1 && $score <= 5)
                                        <span class="tc-score">
                                            <em>{{ $label }}</em>
                                            <strong>{{ $scoreLabels[$score] ?? $score }}</strong>
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <div class="tc-comment course-rich-content">
                            {!! $row->renderComment() !!}
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
