@php
    $courseId = (int) ($course->id ?? 0);
    $sessionId = (int) ($sessionId ?? 0);
    $userId = (int) ($userId ?? 0);
    $formatSubmittedAt = static function (?string $raw): string {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);

        return $ts ? date('H:i d/m/Y', $ts) : $raw;
    };
    $preCount = count($preQuestions ?? []);
    $postCount = count($postQuestions ?? []);
    $reviewCount = count($reviewQuestions ?? []);
    $preUrl = route('teacher.course.student', ['id' => $courseId, 'sessionId' => $sessionId, 'userId' => $userId, 'muc' => 'truoc-buoi']);
    $postUrl = route('teacher.course.student', ['id' => $courseId, 'sessionId' => $sessionId, 'userId' => $userId, 'muc' => 'sau-buoi']);
    $reviewUrl = route('teacher.course.student', ['id' => $courseId, 'sessionId' => $sessionId, 'userId' => $userId, 'muc' => 'kiem-tra']);
    $attendanceAt = $attendanceRow?->joined_at;
    $attendanceLabel = $attendanceAt ? $attendanceAt->format('H:i d/m/Y') : '';
    $reflectionAt = $reflection?->updated_at ?? $reflection?->created_at;
    $reflectionLabel = $reflectionAt ? $reflectionAt->format('H:i d/m/Y') : '';
@endphp

<article class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-title">Bài tập buổi này</span>
    </div>
    <div class="op-panel-body" style="padding:20px">
        <div class="sw-stat-grid tc-work-grid">
            <a class="tc-work-card{{ ! empty($preSubmitted) ? ' is-done' : ($preCount > 0 ? ' is-wait' : '') }}" href="{{ $preUrl }}">
                <span>Trước buổi học</span>
                @if ($preCount <= 0)
                    <strong>Không có bài</strong>
                @elseif (! empty($preSubmitted))
                    <strong>{{ ! empty($preFullySubmitted) ? 'Đã nộp' : 'Nộp một phần' }}</strong>
                    @if ($formatSubmittedAt($preSubmittedAt ?? null) !== '')
                        <small>Nộp lúc {{ $formatSubmittedAt($preSubmittedAt ?? null) }}</small>
                    @endif
                    @if ($preResult ?? null)
                        <small>{{ (int) $preResult['correct'] }}/{{ (int) $preResult['total'] }} câu đúng</small>
                    @endif
                @else
                    <strong>Chưa nộp</strong>
                    <small>{{ $preCount }} câu</small>
                @endif
            </a>
            <a class="tc-work-card{{ ! empty($postSubmitted) ? ' is-done' : ($postCount > 0 ? ' is-wait' : '') }}" href="{{ $postUrl }}">
                <span>Sau buổi học</span>
                @if ($postCount <= 0)
                    <strong>Không có bài</strong>
                @elseif (! empty($postSubmitted))
                    <strong>{{ ! empty($postFullySubmitted) ? 'Đã nộp' : 'Nộp một phần' }}</strong>
                    @if ($formatSubmittedAt($postSubmittedAt ?? null) !== '')
                        <small>Nộp lúc {{ $formatSubmittedAt($postSubmittedAt ?? null) }}</small>
                    @endif
                    @if ($postResult ?? null)
                        <small>{{ (int) $postResult['correct'] }}/{{ (int) $postResult['total'] }} câu đúng</small>
                    @endif
                @else
                    <strong>Chưa nộp</strong>
                    <small>{{ $postCount }} câu</small>
                @endif
            </a>
            @if ($hasSessionKnowledgeReview ?? false)
                <a class="tc-work-card{{ ! empty($reviewSubmitted) ? ' is-done' : ($reviewCount > 0 ? ' is-wait' : '') }}" href="{{ $reviewUrl }}">
                    <span>Ôn tập kiến thức</span>
                    @if ($reviewCount <= 0)
                        <strong>Không có bài</strong>
                    @elseif (! empty($reviewSubmitted))
                        <strong>{{ ! empty($reviewFullySubmitted) ? 'Đã nộp' : 'Nộp một phần' }}</strong>
                        @if ($formatSubmittedAt($reviewSubmittedAt ?? null) !== '')
                            <small>Nộp lúc {{ $formatSubmittedAt($reviewSubmittedAt ?? null) }}</small>
                        @endif
                        @if ($reviewResult ?? null)
                            <small>{{ (int) $reviewResult['correct'] }}/{{ (int) $reviewResult['total'] }} câu đúng</small>
                        @endif
                    @else
                        <strong>Chưa nộp</strong>
                        <small>{{ $reviewCount }} câu</small>
                    @endif
                </a>
            @endif
            <article class="tc-work-card{{ ! empty($hasReflection) ? ' is-done' : '' }}">
                <span>Nhận xét buổi học</span>
                @if (! empty($hasReflection))
                    <strong>Đã gửi · {{ (int) ($reflection->session_rating ?? 0) }}/5</strong>
                    @if ($reflectionLabel !== '')
                        <small>Gửi lúc {{ $reflectionLabel }}</small>
                    @endif
                @else
                    <strong>Chưa gửi</strong>
                @endif
            </article>
        </div>
    </div>
</article>

<article class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-title">Điểm danh</span>
        @if ($attendanceLabel !== '')
            <span class="tc-roster-count">Có mặt {{ $attendanceLabel }}</span>
        @endif
    </div>
    <div class="op-panel-body" style="padding:20px">
        <form method="post" action="{{ $attendanceUpdateUrl }}" class="tc-attend-form">
            @csrf
            <label>Có mặt
                <select name="attended">
                    <option value="1" @selected($attendanceRow !== null)>Có</option>
                    <option value="0" @selected($attendanceRow === null)>Vắng</option>
                </select>
            </label>
            <label>Thời gian
                <input type="datetime-local" name="joined_at" value="{{ $attendanceRow?->joined_at?->format('Y-m-d\TH:i') }}">
            </label>
            <button type="submit" class="op-btn">Lưu điểm danh</button>
        </form>
    </div>
</article>

@if (! empty($hasReflection))
    <article class="op-panel">
        <div class="op-panel-head is-static"><span class="op-panel-title">Nhận xét buổi học</span></div>
        <div class="op-panel-body" style="padding:20px">
            <p>Điểm: {{ (int) ($reflection->session_rating ?? 0) }}/5</p>
            <div class="sw-rich">{!! \App\Support\HtmlContent::render((string) ($reflection->student_comment ?? '')) !!}</div>
        </div>
    </article>
@endif
