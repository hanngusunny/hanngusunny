@php
    use App\Models\CourseMonthlyEvaluation;

    $scoreFields = [
        'score_attitude' => 'Thái độ học tập',
        'score_progress' => 'Tiến độ',
        'score_participation' => 'Tham gia lớp',
    ];
    $scoreLabels = $scoreLabels ?? CourseMonthlyEvaluation::scoreLabels();
    $evaluation = $evaluation ?? null;
    $weekOptions = $weekOptions ?? [];
    $canEdit = (bool) ($canEdit ?? false);

    $value = static function (string $field) use ($evaluation): string {
        $old = old($field);

        if ($old !== null) {
            return (string) $old;
        }

        return (string) ($evaluation?->{$field} ?? '');
    };
@endphp

@if ($member === null)
    <article class="op-panel teacher-eval-empty-panel">
        <div class="teacher-eval-empty-note">
            <span class="op-panel-ico"><i class="fa-solid fa-users"></i></span>
            <p>Chọn khóa học có học viên để bắt đầu đánh giá.</p>
        </div>
    </article>
@else
    <article class="op-panel teacher-eval-week-picker">
        <form id="teacher-eval-week-form" method="get" action="{{ route('teacher.evaluations') }}" class="teacher-eval-week-form">
            <input type="hidden" name="khoa" value="{{ (int) $courseId }}">
            <input type="hidden" name="hoc-vien" value="{{ (int) $userId }}">
            <label for="teacher-eval-week-select">Tuần đánh giá</label>
            <select class="select" id="teacher-eval-week-select" name="tuan">
                @foreach ($weekOptions as $option)
                    <option value="{{ $option }}" @selected($option === $evalWeek)>
                        {{ CourseMonthlyEvaluation::formatWeekLabel($option) }}
                    </option>
                @endforeach
            </select>
        </form>
    </article>

    @if ($canEdit)
        <form method="post" action="{{ route('teacher.evaluations.save') }}" class="course-eval-form">
            @csrf
            <input type="hidden" name="course_id" value="{{ (int) $courseId }}">
            <input type="hidden" name="user_id" value="{{ (int) $userId }}">
            <input type="hidden" name="eval_week" value="{{ $evalWeek }}">

            <article class="op-panel">
                <div class="op-panel-head is-static">
                    <span class="op-panel-ico"><i class="fa-solid fa-star-half-stroke"></i></span>
                    <span class="op-panel-title">Đánh giá tuần</span>
                    <span class="te-side-meta">Thang điểm 1–5</span>
                </div>
                <div class="op-panel-body">
                    <div class="te-score-grid">
                        @foreach ($scoreFields as $field => $label)
                            <div class="te-field">
                                <label for="{{ $field }}">{{ $label }}</label>
                                <select id="{{ $field }}" name="{{ $field }}">
                                    <option value="">— Chọn —</option>
                                    @foreach ($scoreLabels as $score => $scoreLabel)
                                        <option value="{{ $score }}" @selected((int) $value($field) === (int) $score)>
                                            {{ $score }} — {{ $scoreLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <div class="te-field te-field-full">
                        <label for="comment">Nhận xét chi tiết</label>
                        <textarea class="js-course-eval-editor" id="comment" name="comment" rows="8" placeholder="Nhận xét về thái độ, tiến độ, điểm cần cải thiện trong tuần...">{{ $value('comment') }}</textarea>
                    </div>
                    <div class="te-form-actions">
                        <button class="op-btn" type="submit">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            <span>Lưu đánh giá</span>
                        </button>
                    </div>
                </div>
            </article>
        </form>
    @else
        <article class="op-panel">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-eye"></i></span>
                <span class="op-panel-title">Đánh giá tuần</span>
                <span class="te-side-meta">Chế độ xem</span>
            </div>
            <div class="op-panel-body">
                @if ($evaluation !== null && $evaluation->isFilled())
                    <div class="course-eval-scores-readonly">
                        @foreach ($scoreFields as $field => $label)
                            @php $score = (int) ($evaluation->{$field} ?? 0); @endphp
                            @if ($score >= 1 && $score <= 5)
                                <span><strong>{{ $label }}:</strong> {{ $score }} — {{ $scoreLabels[$score] ?? '' }}</span>
                            @endif
                        @endforeach
                    </div>
                    @if ($evaluation->hasComment())
                        <div class="course-eval-comment-readonly course-rich-content">{!! $evaluation->renderComment() !!}</div>
                    @endif
                @else
                    <p class="muted">Chưa có đánh giá cho tuần này.</p>
                @endif
            </div>
        </article>
    @endif
@endif
