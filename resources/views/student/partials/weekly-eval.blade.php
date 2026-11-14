@php
    use App\Models\CourseMonthlyEvaluation;

    $evaluation = $evaluation ?? null;
    $course = $course ?? null;
    $scoreLabels = $scoreLabels ?? CourseMonthlyEvaluation::scoreLabels();
    $weekLabel = $weekLabel ?? '';
    $evalWeek = $evalWeek ?? CourseMonthlyEvaluation::currentWeekKey();
    $hasEvalForWeek = (bool) ($hasEvalForWeek ?? false);

    $scoreFields = [
        'score_attitude' => ['label' => 'Thái độ', 'icon' => 'fa-face-smile', 'tone' => 'amber'],
        'score_progress' => ['label' => 'Tiến độ', 'icon' => 'fa-arrow-trend-up', 'tone' => 'blue'],
        'score_participation' => ['label' => 'Tham gia', 'icon' => 'fa-thumbs-up', 'tone' => 'teal'],
    ];

    $weekParts = CourseMonthlyEvaluation::parseEvalWeek((string) $evalWeek);
    $weekNum = (int) ($weekParts['week'] ?? 0);
    $yearNum = (int) ($weekParts['year'] ?? 0);

    $scores = [];
    $scoreSum = 0;

    if ($hasEvalForWeek && $evaluation !== null) {
        foreach ($scoreFields as $field => $meta) {
            $score = (int) ($evaluation->{$field} ?? 0);

            if ($score >= 1 && $score <= 5) {
                $scores[$field] = array_merge($meta, [
                    'score' => $score,
                    'labelText' => $scoreLabels[$score] ?? (string) $score,
                ]);
                $scoreSum += $score;
            }
        }
    }

    $scoreCount = count($scores);
    $averageScore = $scoreCount > 0 ? round($scoreSum / $scoreCount, 1) : 0;
    $averagePct = $scoreCount > 0 ? (int) round(($averageScore / 5) * 100) : 0;
    $hasComment = $hasEvalForWeek && $evaluation !== null && $evaluation->hasComment();
    $teacherName = trim((string) ($course?->teacher?->name ?? 'Giáo viên'));
    $updatedRaw = $hasEvalForWeek ? $evaluation?->updated_at : null;
    $updatedAt = '';
    if ($updatedRaw instanceof \DateTimeInterface) {
        $updatedAt = $updatedRaw->format('d/m/Y');
    } elseif (is_string($updatedRaw) && $updatedRaw !== '') {
        $updatedAt = date('d/m/Y', strtotime($updatedRaw)) ?: '';
    }
@endphp

<article class="my-course-eval-card" aria-label="Đánh giá {{ $weekLabel }}">
    <div class="my-course-eval-glow" aria-hidden="true"></div>

    <header class="my-course-eval-head">
        <div class="my-course-eval-month-badge student-eval-week-badge" aria-hidden="true">
            <span class="my-course-eval-month-kicker">Tuần</span>
            <strong>{{ str_pad((string) $weekNum, 2, '0', STR_PAD_LEFT) }}</strong>
            <em>{{ $yearNum }}</em>
        </div>

        <div class="my-course-eval-head-copy">
            <span class="my-course-eval-eyebrow">
                <i class="fa-solid fa-star"></i>
                Nhận xét từ giáo viên
            </span>
            <h2>{{ $weekLabel }}</h2>
            <p>
                <i class="fa-solid fa-id-badge"></i>
                {{ $teacherName }}
                @if ($updatedAt !== '')
                    · Cập nhật {{ $updatedAt }}
                @endif
            </p>
        </div>

        @if ($scoreCount > 0)
            <div class="my-course-eval-average" style="--pct: {{ $averagePct }}">
                <svg viewBox="0 0 44 44" aria-hidden="true">
                    <circle class="my-course-eval-average-bg" cx="22" cy="22" r="18"></circle>
                    <circle class="my-course-eval-average-fill" cx="22" cy="22" r="18"></circle>
                </svg>
                <div class="my-course-eval-average-text">
                    <strong>{{ number_format($averageScore, 1) }}</strong>
                    <span>/ 5</span>
                </div>
            </div>
        @endif
    </header>

    @if (! $hasEvalForWeek)
        <div class="my-course-eval-empty">
            <span class="my-course-eval-empty-ico" aria-hidden="true"><i class="fa-solid fa-clipboard"></i></span>
            <strong>Chưa có đánh giá cho tuần này</strong>
            <p>Giáo viên chưa chấm điểm hoặc ghi nhận xét {{ $weekLabel }}. Chọn tuần khác để xem đánh giá trước đó.</p>
        </div>
    @else
        @if ($scores !== [])
            <div class="my-course-eval-metrics">
                @foreach ($scores as $item)
                    @php $barPct = (int) round(($item['score'] / 5) * 100); @endphp
                    <div class="my-course-eval-metric my-course-eval-metric--{{ $item['tone'] }}" data-score="{{ $item['score'] }}">
                        <span class="my-course-eval-metric-icon" aria-hidden="true">
                            <i class="fa-solid {{ $item['icon'] }}"></i>
                        </span>
                        <div class="my-course-eval-metric-body">
                            <div class="my-course-eval-metric-top">
                                <span>{{ $item['label'] }}</span>
                                <strong>{{ $item['labelText'] }}</strong>
                            </div>
                            <div class="my-course-eval-metric-bar" role="presentation">
                                <span style="width: {{ $barPct }}%"></span>
                            </div>
                        </div>
                        <span class="my-course-eval-metric-num">{{ $item['score'] }}/5</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($hasComment)
            <div class="my-course-eval-comment">
                <div class="my-course-eval-comment-label">
                    <i class="fa-solid fa-quote-left"></i>
                    <span>Nhận xét chi tiết</span>
                </div>
                <div class="my-course-eval-comment-body course-rich-content">
                    {!! $evaluation->renderComment() !!}
                </div>
            </div>
        @endif
    @endif
</article>
