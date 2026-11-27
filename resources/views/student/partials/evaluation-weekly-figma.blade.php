@php
    use App\Models\CourseMonthlyEvaluation;

    $evaluation = $evaluation ?? null;
    $course = $course ?? null;
    $scoreLabels = $scoreLabels ?? CourseMonthlyEvaluation::scoreLabels();
    $evalWeek = (string) ($evalWeek ?? CourseMonthlyEvaluation::currentWeekKey());
    $weekOptions = $weekOptions ?? [];
    $filledWeeks = $filledWeeks ?? [];
    $hasEvalForWeek = (bool) ($hasEvalForWeek ?? false);
    $attendanceReport = $attendanceReport ?? ['items' => [], 'attended' => 0, 'total' => 0];
    $currentWeek = (string) ($currentWeek ?? '');

    $formatFigmaWeek = static function (string $weekKey): array {
        $parsed = CourseMonthlyEvaluation::parseEvalWeek($weekKey);
        $start = CourseMonthlyEvaluation::weekToDate($weekKey);
        $weekNum = str_pad((string) ($parsed['week'] ?? 0), 2, '0', STR_PAD_LEFT);
        $yearNum = (string) ($parsed['year'] ?? date('Y'));
        $range = '';

        if ($start !== null) {
            $end = (clone $start)->modify('+6 days');
            $range = $start->format('d/m').'–'.$end->format('d/m/Y');
        }

        return [
            'short' => 'Tuần '.$weekNum.'/'.$yearNum,
            'range' => $range,
            'full' => 'Tuần '.$weekNum.'/'.$yearNum.($range !== '' ? ' · '.$range : ''),
        ];
    };

    $selectedWeekMeta = $formatFigmaWeek($evalWeek);
    $courseTitle = trim((string) ($course?->title ?? ''));
    $teacherName = trim((string) ($course?->teacher?->name ?? 'Giáo viên'));
    $teacherAvatar = $course?->teacher?->avatarUrl() ?? '';

    $criteriaMeta = [
        'score_attitude' => [
            'label' => 'Thái độ học tập',
            'tone' => 'orange',
        ],
        'score_progress' => [
            'label' => 'Tiến độ học tập',
            'tone' => 'blue',
        ],
        'score_participation' => [
            'label' => 'Chuyên cần',
            'tone' => 'purple',
        ],
    ];

    $scores = [];
    $scoreSum = 0;

    if ($hasEvalForWeek && $evaluation !== null) {
        foreach ($criteriaMeta as $field => $meta) {
            $val = (int) ($evaluation->{$field} ?? 0);
            if ($val >= 1 && $val <= 5) {
                $scores[$field] = array_merge($meta, [
                    'score' => $val,
                    'score_display' => number_format($val, 1),
                    'text' => $scoreLabels[$val] ?? (string) $val,
                    'pct' => (int) round(($val / 5) * 100),
                ]);
                $scoreSum += $val;
            }
        }
    }

    $scoreCount = count($scores);
    $avgScore = $scoreCount > 0 ? round($scoreSum / $scoreCount, 1) : 0.0;
    $avgPct = $scoreCount > 0 ? (int) round(($avgScore / 5) * 100) : 0;
    $ringCircumference = 194.53;
    $ringOffset = round($ringCircumference * (1 - ($avgPct / 100)), 2);

    $updatedLabel = '';
    if ($hasEvalForWeek && $evaluation?->updated_at) {
        $ts = $evaluation->updated_at instanceof \DateTimeInterface
            ? $evaluation->updated_at->getTimestamp()
            : (strtotime((string) $evaluation->updated_at) ?: 0);
        if ($ts > 0) {
            $updatedLabel = 'Cập nhật lúc '.date('H:i · d/m/Y', $ts);
        }
    }

    $hasComment = $hasEvalForWeek && $evaluation !== null && $evaluation->hasComment();
    $attendanceItems = $attendanceReport['items'] ?? [];
    $attendanceTotal = (int) ($attendanceReport['total'] ?? 0);
    $attendanceAttended = (int) ($attendanceReport['attended'] ?? 0);
@endphp

<div class="sn-eval-weekly">
    {{-- Bộ chọn tuần chuẩn Figma (#479:43516 & #714:27299) --}}
    <div class="sn-eval-week-picker" data-sn-week-picker>
        <h1 class="sn-eval-section-title">Chọn tuần được nhận xét</h1>

        <div class="sn-eval-dropdown-wrap">
            <button
                type="button"
                class="sn-eval-week-trigger"
                id="snEvalWeekTrigger"
                aria-haspopup="listbox"
                aria-expanded="false"
                aria-controls="snEvalWeekPopover"
            >
                <span class="sn-eval-week-trigger-copy">
                    <span class="sn-eval-week-trigger-label">Tuần</span>
                    <strong class="sn-eval-week-trigger-value">{{ $selectedWeekMeta['full'] }}</strong>
                </span>
                <svg class="sn-eval-week-chevron" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9L11.2929 14.2929C11.6262 14.6262 11.7929 14.7929 12 14.7929C12.2071 14.7929 12.3738 14.6262 12.7071 14.2929L18 9" stroke="#141B34" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="sn-eval-week-popover" id="snEvalWeekPopover" role="listbox" aria-label="Danh sách tuần" hidden>
                <div class="sn-eval-week-popover-head">Danh sách tuần</div>
                <div class="sn-eval-week-list" id="snEvalWeekList">
                    @foreach ($weekOptions as $option)
                        @php
                            $optMeta = $formatFigmaWeek((string) $option);
                            $isSelected = $option === $evalWeek;
                            $isFilled = in_array($option, $filledWeeks, true);
                        @endphp
                        <a
                            href="{{ route('student.evaluations', ['tuan' => $option]) }}"
                            class="sn-eval-week-option{{ $isSelected ? ' is-selected' : '' }}"
                            role="option"
                            aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                            @if ($isSelected) data-sn-week-selected @endif
                        >
                            <span class="sn-eval-week-option-top">
                                <span>Tuần{{ $option === $currentWeek ? ' · Hiện tại' : '' }}</span>
                                @if ($isFilled)
                                    <span class="sn-eval-week-dot">Đã nhận xét</span>
                                @endif
                            </span>
                            <strong class="sn-eval-week-option-label">{{ $optMeta['full'] }}</strong>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if (! $hasEvalForWeek)
        {{-- Trạng thái trống tuần chưa có nhận xét (#479:41505) --}}
        <div class="sn-eval-empty-state">
            <div class="sn-eval-empty-ico" aria-hidden="true">
                <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                    <circle cx="40" cy="40" r="40" fill="#E8EFF9"/>
                    <path d="M27 25C27 22.8 28.8 21 31 21H45L53 29V55C53 57.2 51.2 59 49 59H31C28.8 59 27 57.2 27 55V25Z" fill="#FFFFFF"/>
                    <path d="M33 49H47M33 53H42" stroke="#E4E8ED" stroke-width="2.4" stroke-linecap="round"/>
                    <circle cx="40" cy="37" r="10" fill="#0D99FF"/>
                    <path d="M36.5 33.5L43.5 40.5M43.5 33.5L36.5 40.5" stroke="#FFFFFF" stroke-width="2.4" stroke-linecap="round"/>
                </svg>
            </div>
            <h2 class="sn-eval-empty-title">Chưa có nhận xét</h2>
            <p class="sn-eval-empty-desc">
                Chưa có nhận xét cho {{ mb_strtolower($selectedWeekMeta['full'], 'UTF-8') }}. Em có thể chọn một tuần khác để xem nhận xét của giáo viên.
            </p>
        </div>
    @else
        {{-- Card Nhận xét tuần chuẩn Figma (#479:43516) --}}
        <article class="sn-eval-card" aria-label="Nhận xét {{ $selectedWeekMeta['short'] }}">
            <div class="sn-eval-card-top">
                <h2 class="sn-eval-card-title">Nhận xét {{ mb_strtolower($selectedWeekMeta['short'], 'UTF-8') }}</h2>
                <span class="sn-eval-badge-done">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7.75 11.9999L10.58 14.8299L16.25 9.16992" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Đã nhận xét</span>
                </span>
            </div>

            <div class="sn-eval-summary-row">
                <div class="sn-eval-teacher-info">
                    <x-user-avatar class="sn-eval-teacher-avatar" :url="$teacherAvatar" :name="$teacherName" />
                    <div class="sn-eval-teacher-copy">
                        <strong>{{ $teacherName }}</strong>
                        @if ($courseTitle !== '')
                            <span>{{ $courseTitle }}</span>
                        @endif
                    </div>
                </div>

                <div class="sn-eval-date-info">
                    @if ($selectedWeekMeta['range'] !== '')
                        <strong>{{ $selectedWeekMeta['range'] }}</strong>
                    @endif
                    @if ($updatedLabel !== '')
                        <span>{{ $updatedLabel }}</span>
                    @endif
                </div>

                @if ($scoreCount > 0)
                    <div class="sn-eval-ring-box">
                        <div class="sn-eval-ring" aria-label="Điểm tổng quan {{ number_format($avgScore, 1) }} trên 5">
                            <svg width="72" height="72" viewBox="0 0 72 72" aria-hidden="true">
                                <circle cx="36" cy="36" r="30.96" fill="none" stroke="#E4E8ED" stroke-width="10.08"/>
                                <circle
                                    cx="36"
                                    cy="36"
                                    r="30.96"
                                    fill="none"
                                    stroke="#0D99FF"
                                    stroke-width="10.08"
                                    stroke-linecap="butt"
                                    stroke-dasharray="{{ $ringCircumference }}"
                                    stroke-dashoffset="{{ $ringOffset }}"
                                    transform="rotate(-90 36 36)"
                                />
                            </svg>
                            <strong class="sn-eval-ring-value">{{ number_format($avgScore, 1) }}/5</strong>
                        </div>
                        <span class="sn-eval-ring-caption">Điểm tổng quan</span>
                    </div>
                @endif
            </div>

            @if ($scores !== [])
                <div class="sn-eval-block">
                    <h3 class="sn-eval-sub-heading">Tiêu chí đánh giá</h3>
                    <div class="sn-eval-criteria-grid">
                        @foreach ($scores as $item)
                            <div class="sn-eval-criterion-card">
                                <div class="sn-eval-criterion-top">
                                    <span class="sn-eval-criterion-ico sn-eval-criterion-ico--{{ $item['tone'] }}" aria-hidden="true">
                                        @if ($item['tone'] === 'orange')
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12.33 17C12.15 17.06 11.84 17.06 11.66 17C10.1 16.47 6.6 14.24 6.6 10.46C6.6 8.79 7.94 7.44 9.6 7.44C10.58 7.44 11.45 7.91 12 8.65C12.54 7.92 13.42 7.44 14.4 7.44C16.06 7.44 17.4 8.79 17.4 10.46C17.4 14.24 13.9 16.47 12.33 17Z" fill="currentColor"/>
                                            </svg>
                                        @elseif ($item['tone'] === 'blue')
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                <path d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81V16.19C2 19.83 4.17 22 7.81 22H16.19C19.83 22 22 19.83 22 16.19V7.81C22 4.17 19.83 2 16.19 2ZM9.97 14.9L7.72 17.15C7.57 17.3 7.38 17.37 7.19 17.37C7 17.37 6.8 17.3 6.66 17.15L5.91 16.4C5.61 16.11 5.61 15.63 5.91 15.34C6.2 15.05 6.67 15.05 6.97 15.34L7.19 15.56L8.91 13.84C9.2 13.55 9.67 13.55 9.97 13.84C10.26 14.13 10.26 14.61 9.97 14.9ZM9.97 7.9L7.72 10.15C7.57 10.3 7.38 10.37 7.19 10.37C7 10.37 6.8 10.3 6.66 10.15L5.91 9.4C5.61 9.11 5.61 8.63 5.91 8.34C6.2 8.05 6.67 8.05 6.97 8.34L7.19 8.56L8.91 6.84C9.2 6.55 9.67 6.55 9.97 6.84C10.26 7.13 10.26 7.61 9.97 7.9ZM17.56 16.62H12.31C11.9 16.62 11.56 16.28 11.56 15.87C11.56 15.46 11.9 15.12 12.31 15.12H17.56C17.98 15.12 18.31 15.46 18.31 15.87C18.31 16.28 17.98 16.62 17.56 16.62ZM17.56 9.62H12.31C11.9 9.62 11.56 9.28 11.56 8.87C11.56 8.46 11.9 8.12 12.31 8.12H17.56C17.98 8.12 18.31 8.46 18.31 8.87C18.31 9.28 17.98 9.62 17.56 9.62Z" fill="currentColor"/>
                                            </svg>
                                        @else
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 2C9.38 2 7.25 4.13 7.25 6.75C7.25 9.32 9.26 11.4 11.88 11.49C11.96 11.48 12.04 11.48 12.1 11.49C12.12 11.49 12.13 11.49 12.15 11.49C12.16 11.49 12.16 11.49 12.17 11.49C14.73 11.4 16.74 9.32 16.75 6.75C16.75 4.13 14.62 2 12 2Z" fill="currentColor"/>
                                                <path d="M17.08 14.1509C14.29 12.2909 9.74002 12.2909 6.93002 14.1509C5.66002 15.0009 4.96002 16.1509 4.96002 17.3809C4.96002 18.6109 5.66002 19.7509 6.92002 20.5909C8.32002 21.5309 10.16 22.0009 12 22.0009C13.84 22.0009 15.68 21.5309 17.08 20.5909C18.34 19.7409 19.04 18.6009 19.04 17.3609C19.03 16.1309 18.34 14.9909 17.08 14.1509Z" fill="currentColor"/>
                                            </svg>
                                        @endif
                                    </span>

                                    <div class="sn-eval-criterion-copy">
                                        <strong>{{ $item['label'] }}</strong>
                                        <span>{{ $item['text'] }}</span>
                                    </div>

                                    <span class="sn-eval-criterion-score">{{ $item['score_display'] }}/5</span>
                                </div>

                                <div class="sn-eval-bar-track" aria-hidden="true">
                                    <span class="sn-eval-bar-fill" style="width: {{ $item['pct'] }}%"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($hasComment)
                <div class="sn-eval-block">
                    <h3 class="sn-eval-sub-heading">Nhận xét chi tiết</h3>
                    <div class="sn-eval-comment-box course-rich-content">
                        {!! $evaluation->renderComment() !!}
                    </div>
                </div>
            @endif
        </article>
    @endif

    {{-- Điểm danh tuần (giữ trọn chức năng hiện có, đồng bộ giao diện Sunny) --}}
    @if ($attendanceTotal > 0)
        <section class="sn-eval-attendance-card" aria-label="Điểm danh tuần">
            <div class="sn-eval-card-top">
                <h3 class="sn-eval-sub-heading">Điểm danh tuần</h3>
                <span class="sn-eval-attendance-count">{{ $attendanceAttended }}/{{ $attendanceTotal }} buổi có mặt</span>
            </div>

            <ul class="sn-eval-attendance-list">
                @foreach ($attendanceItems as $item)
                    @php
                        $sess = $item['session'] ?? [];
                        $sTitle = trim((string) ($sess['title'] ?? 'Buổi học'));
                        $sDate = trim((string) ($sess['session_date'] ?? ''));
                        $sStart = substr((string) ($sess['start_time'] ?? ''), 0, 5);
                        $sStatus = (string) ($item['status_label'] ?? '');
                        $sJoined = $item['joined_at'] ?? null;
                        $tone = match ($sStatus) {
                            'Có mặt' => 'present',
                            'Chưa diễn ra' => 'upcoming',
                            default => 'absent',
                        };
                    @endphp
                    <li class="sn-eval-attendance-row">
                        <div class="sn-eval-attendance-copy">
                            <strong>{{ $sTitle }}</strong>
                            <span>
                                {{ $sDate !== '' ? date('d/m/Y', strtotime($sDate)) : '' }}{{ $sStart !== '' ? ' · '.$sStart : '' }}
                            </span>
                        </div>
                        <span class="sn-eval-attendance-pill sn-eval-attendance-pill--{{ $tone }}">
                            {{ $sStatus }}
                            @if ($sJoined)
                                · {{ date('H:i', strtotime((string) $sJoined)) }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
