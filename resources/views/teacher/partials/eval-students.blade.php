@php
    $members = $members ?? collect();
    $memberCount = $members->count();
    $evaluatedCount = (int) ($evaluatedCount ?? 0);
    $pendingCount = max(0, $memberCount - $evaluatedCount);
    $progressPct = $memberCount > 0 ? (int) round(($evaluatedCount / $memberCount) * 100) : 0;
    $evalMap = $evalMap ?? [];
    $attendanceMap = $attendanceMap ?? [];
    $weekSessionCount = (int) ($weekSessionCount ?? 0);
    $evaluationService = $evaluationService ?? app(\App\Services\TeacherEvaluationService::class);
@endphp

<article class="op-panel teacher-eval-student-card">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-users"></i></span>
        <span class="op-panel-title">Học viên</span>
        <span class="te-side-meta">{{ $memberCount }} học viên</span>
    </div>

    <div class="teacher-eval-student-summary">
        <div class="teacher-eval-student-summary-stat">
            <span>Đã đánh giá</span>
            <strong>{{ $evaluatedCount }}/{{ $memberCount }}</strong>
        </div>
        <div class="teacher-eval-student-summary-stat">
            <span>Chưa xong</span>
            <strong>{{ $pendingCount }}</strong>
        </div>
        <div class="teacher-eval-student-summary-progress" style="--pct: {{ $progressPct }}">
            <span>{{ $progressPct }}%</span>
        </div>
    </div>

    @if ($members->isEmpty())
        <div class="teacher-eval-empty-note">
            <p>Chưa có học viên trong khóa.</p>
        </div>
    @else
        <div class="teacher-eval-student-list" role="tablist" aria-label="Chọn học viên để đánh giá">
            @foreach ($members as $row)
                @php
                    $memberUserId = (int) $row->user_id;
                    $isActive = $memberUserId === (int) $userId;
                    $displayName = trim((string) ($row->user?->name ?? ''));
                    $contact = trim((string) ($row->user?->email ?? ''));
                    if ($contact === '') {
                        $contact = trim((string) ($row->user?->phone ?? ''));
                    }
                    $eval = $evalMap[$memberUserId] ?? null;
                    $filled = $eval !== null && $eval->isFilled();
                    $attended = (int) ($attendanceMap[$memberUserId]['attended'] ?? 0);
                    $itemUrl = $evaluationService->pageUrl((int) $courseId, $memberUserId, (string) $evalWeek);
                @endphp
                <a
                    class="teacher-eval-student-item{{ $isActive ? ' is-active' : '' }}{{ $filled ? ' is-evaluated' : ' is-pending' }}"
                    href="{{ $itemUrl }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    @if ($isActive) id="teacher-eval-student-active" @endif
                >
                    <x-user-avatar
                        class="teacher-course-member-avatar"
                        :url="$row->user?->avatarUrl()"
                        :name="$displayName"
                    />
                    <div class="teacher-course-member-body">
                        <strong>{{ $displayName !== '' ? $displayName : 'Học viên' }}</strong>
                        @if ($contact !== '')
                            <span>{{ $contact }}</span>
                        @endif
                        @if ($weekSessionCount > 0)
                            <small>Điểm danh: {{ $attended }}/{{ $weekSessionCount }}</small>
                        @endif
                    </div>
                    <div class="teacher-eval-student-item-status">
                        @if ($filled)
                            <span class="teacher-eval-status-badge teacher-eval-status-badge--done" title="Đã đánh giá">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            </span>
                        @else
                            <span class="teacher-eval-status-badge teacher-eval-status-badge--pending" title="Chưa đánh giá">
                                <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</article>
