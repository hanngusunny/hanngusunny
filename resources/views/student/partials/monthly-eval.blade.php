@php
    use App\Models\CourseMonthlyEvaluation;

    $monthlyEvaluation = $monthlyEvaluation ?? null;
    $course = $course ?? null;
    $scoreLabels = $scoreLabels ?? CourseMonthlyEvaluation::scoreLabels();
    $evalMonthLabel = $evalMonthLabel ?? '';
    $selectedEvalMonth = $selectedEvalMonth ?? date('Y-m');
    $hasEvalForMonth = (bool) ($hasEvalForMonth ?? false);
    $evalFormAction = $evalFormAction ?? route('student.progress');

    $scoreFields = [
        'score_attitude' => ['label' => 'Thái độ', 'icon' => 'fa-face-smile', 'tone' => 'amber'],
        'score_progress' => ['label' => 'Tiến độ', 'icon' => 'fa-arrow-trend-up', 'tone' => 'blue'],
        'score_participation' => ['label' => 'Tham gia', 'icon' => 'fa-thumbs-up', 'tone' => 'teal'],
    ];

    $evalMonth = $monthlyEvaluation?->eval_month ?: $selectedEvalMonth;
    $monthParts = CourseMonthlyEvaluation::parseEvalMonth((string) $evalMonth);
    $monthNum = (int) ($monthParts['month'] ?? (int) date('n'));
    $yearNum = (int) ($monthParts['year'] ?? (int) date('Y'));
    $currentMonth = date('Y-m');
    $currentYear = (int) date('Y');

    $scores = [];
    $scoreSum = 0;

    if ($hasEvalForMonth && $monthlyEvaluation !== null) {
        foreach ($scoreFields as $field => $meta) {
            $score = (int) ($monthlyEvaluation->{$field} ?? 0);

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
    $hasComment = $hasEvalForMonth && $monthlyEvaluation !== null && $monthlyEvaluation->hasComment();
    $teacherName = trim((string) ($course?->teacher?->name ?? 'Giáo viên'));
    $updatedRaw = $hasEvalForMonth ? $monthlyEvaluation?->updated_at : null;
    $updatedAt = '';
    if ($updatedRaw instanceof \DateTimeInterface) {
        $updatedAt = $updatedRaw->format('d/m/Y');
    } elseif (is_string($updatedRaw) && $updatedRaw !== '') {
        $updatedAt = date('d/m/Y', strtotime($updatedRaw)) ?: '';
    }
@endphp

<article class="my-course-eval-card" aria-label="Đánh giá {{ $evalMonthLabel }}">
    <div class="my-course-eval-glow" aria-hidden="true"></div>

    <header class="my-course-eval-head">
        <div class="my-course-eval-month-badge" aria-hidden="true">
            <span class="my-course-eval-month-kicker">Tháng</span>
            <strong>{{ $monthNum }}</strong>
            <em>{{ $yearNum }}</em>
        </div>

        <div class="my-course-eval-head-copy">
            <span class="my-course-eval-eyebrow">
                <i class="fa-solid fa-star"></i>
                Nhận xét từ giáo viên
            </span>
            <h2>Đánh giá {{ $evalMonthLabel }}</h2>
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

    <form class="my-course-eval-month-form" method="get" action="{{ $evalFormAction }}" aria-label="Chọn tháng xem đánh giá">
        <div class="my-course-eval-month-picker-head">
            <span class="my-course-eval-month-picker-label">
                <i class="fa-regular fa-calendar"></i>
                Xem đánh giá theo tháng
            </span>
            @if ($selectedEvalMonth === $currentMonth)
                <span class="my-course-eval-month-current">Tháng hiện tại</span>
            @endif
        </div>

        <div class="my-course-eval-month-fields">
            <div class="field">
                <label for="myCourseEvalMonth">Tháng</label>
                <select class="input" id="myCourseEvalMonth" name="thang" required>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($monthNum === $m)>Tháng {{ $m }}</option>
                    @endfor
                </select>
            </div>
            <div class="field">
                <label for="myCourseEvalYear">Năm</label>
                <input
                    class="input"
                    type="number"
                    id="myCourseEvalYear"
                    name="nam"
                    min="2020"
                    max="{{ $currentYear }}"
                    step="1"
                    required
                    value="{{ $yearNum }}"
                    placeholder="{{ $currentYear }}"
                >
            </div>
            <div class="my-course-eval-month-submit">
                <button class="op-btn my-course-eval-view-btn" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Xem</span>
                </button>
            </div>
        </div>
    </form>

    @if (! $hasEvalForMonth)
        <div class="my-course-eval-empty">
            <span class="my-course-eval-empty-ico"><i class="fa-solid fa-clipboard"></i></span>
            <strong>Chưa có đánh giá cho tháng này</strong>
            <p>Giáo viên chưa chấm điểm hoặc ghi nhận xét {{ $evalMonthLabel }}. Hãy nhập tháng và năm khác rồi bấm Xem.</p>
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
                    {!! $monthlyEvaluation->renderComment() !!}
                </div>
            </div>
        @endif
    @endif
</article>
