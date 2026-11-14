@php
    $courseId = (int) ($course?->id ?? 0);
    $members = $members ?? collect();
    $sessionAttendanceMap = is_array($sessionAttendanceMap ?? null) ? $sessionAttendanceMap : [];
    $session = $session ?? null;
    $memberCount = $members instanceof \Illuminate\Support\Collection ? $members->count() : count($members);
    $attendedCount = count($sessionAttendanceMap);
    $showSessionAttendance = $session !== null && (int) ($session['id'] ?? 0) > 0;
    $sessionId = $showSessionAttendance ? (int) ($session['id'] ?? 0) : 0;
@endphp

<article class="teacher-course-members-panel">
    <div class="teacher-course-members-head">
        <div class="teacher-course-panel-title">
            <h2><i class="fa-solid fa-users"></i> Học viên trong khóa</h2>
            <span>{{ $memberCount }} học viên</span>
        </div>
        @if ($showSessionAttendance)
            <span class="teacher-course-members-attendance-pill">
                <i class="fa-solid fa-square-check"></i>
                {{ $attendedCount }}/{{ $memberCount }} điểm danh buổi này
            </span>
        @endif
    </div>

    @if ($memberCount === 0)
        <div class="teacher-course-members-empty">
            <span class="teacher-course-empty-icon"><i class="fa-solid fa-user-xmark"></i></span>
            <p>Chưa có học viên trong khóa.</p>
        </div>
    @else
        <div class="teacher-course-members-list">
            @foreach ($members as $member)
                @php
                    $user = is_object($member) ? $member->user : null;
                    $userId = (int) (is_object($member) ? $member->user_id : ($member['user_id'] ?? 0));
                    $displayName = trim((string) ($user?->name ?: ($member['full_name'] ?? $member['username'] ?? 'Học viên')));
                    $contact = trim((string) ($user?->email ?? $member['email'] ?? ''));
                    if ($contact === '') {
                        $contact = trim((string) ($user?->phone ?? $member['phone'] ?? ''));
                    }
                    $initial = \App\Models\User::initialsFrom($displayName);
                    $joinedAt = $sessionAttendanceMap[$userId] ?? null;
                    $hasAttended = $joinedAt !== null && $joinedAt !== '';
                    $studentUrl = ($showSessionAttendance && $sessionId > 0 && $userId > 0)
                        ? route('teacher.course.student', ['id' => $courseId, 'sessionId' => $sessionId, 'userId' => $userId])
                        : '';
                @endphp

                @if ($studentUrl !== '')
                    <a class="teacher-course-member-item teacher-course-member-item--link{{ $hasAttended ? ' is-attended' : '' }}" href="{{ $studentUrl }}">
                        <div class="teacher-course-member-avatar" aria-hidden="true">{{ $initial }}</div>
                        <div class="teacher-course-member-body">
                            <strong>{{ $displayName }}</strong>
                            @if ($contact !== '')
                                <span>{{ $contact }}</span>
                            @endif
                        </div>
                        <div class="teacher-course-member-status">
                            @if ($hasAttended)
                                <span class="teacher-attendance-icon teacher-attendance-icon--yes" title="Đã điểm danh" aria-label="Đã điểm danh">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                </span>
                                <small>{{ date('H:i d/m', strtotime((string) $joinedAt) ?: time()) }}</small>
                            @else
                                <span class="teacher-attendance-icon teacher-attendance-icon--no" title="Chưa điểm danh" aria-label="Chưa điểm danh">
                                    <i class="fa-regular fa-circle-xmark" aria-hidden="true"></i>
                                </span>
                            @endif
                        </div>
                        <i class="fa-solid fa-chevron-right teacher-course-member-chevron" aria-hidden="true"></i>
                    </a>
                @else
                    <article class="teacher-course-member-item{{ $hasAttended ? ' is-attended' : '' }}">
                        <div class="teacher-course-member-avatar" aria-hidden="true">{{ $initial }}</div>
                        <div class="teacher-course-member-body">
                            <strong>{{ $displayName }}</strong>
                            @if ($contact !== '')
                                <span>{{ $contact }}</span>
                            @endif
                        </div>
                        <div class="teacher-course-member-status">
                            <span class="teacher-course-member-pill">Đang học</span>
                        </div>
                    </article>
                @endif
            @endforeach
        </div>
    @endif
</article>
