@php
    $members = $members ?? collect();
    $courseId = (int) ($course->id ?? 0);
    $sessionId = (int) ($sessionId ?? 0);
    $userId = (int) ($userId ?? 0);
    $activeTab = (string) ($activeTab ?? 'tong-quan');
    $sessionAttendanceMap = is_array($sessionAttendanceMap ?? null) ? $sessionAttendanceMap : [];
    $memberCount = $members instanceof \Illuminate\Support\Collection ? $members->count() : count($members);
@endphp

<aside class="sw-nav tc-roster" aria-label="Danh sách học viên">
    <article class="op-panel tc-roster-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-title">Học viên</span>
            <span class="tc-roster-count">{{ $memberCount }}</span>
        </div>
        <div class="op-panel-body tc-roster-body">
            @if ($memberCount === 0)
                <p class="sw-dd-empty">Chưa có học viên trong khóa.</p>
            @else
                <div class="tc-roster-list" id="teacher-student-roster-list">
                    @foreach ($members as $row)
                        @php
                            $user = is_object($row) ? $row->user : null;
                            $rowUserId = (int) (is_object($row) ? $row->user_id : ($row['user_id'] ?? 0));
                            $displayName = trim((string) ($user?->name ?: 'Học viên'));
                            $isActive = $rowUserId === $userId;
                            $joinedAt = $sessionAttendanceMap[$rowUserId] ?? null;
                            $hasAttended = $joinedAt !== null && $joinedAt !== '';
                            $itemUrl = ($courseId > 0 && $sessionId > 0 && $rowUserId > 0)
                                ? route('teacher.course.student', ['id' => $courseId, 'sessionId' => $sessionId, 'userId' => $rowUserId, 'muc' => $activeTab])
                                : '#';
                        @endphp
                        <a
                            class="tc-roster-person{{ $isActive ? ' is-on' : '' }}{{ $hasAttended ? ' is-attended' : '' }}"
                            href="{{ $itemUrl }}"
                            @if ($isActive) id="teacher-student-roster-active" @endif
                        >
                            <x-user-avatar
                                class="tc-roster-avatar"
                                :url="$user?->avatarUrl()"
                                :name="$displayName"
                                :initials="\App\Models\User::initialsFrom($displayName)"
                            />
                            <span class="tc-roster-copy">
                                <strong>{{ $displayName }}</strong>
                                <small>{{ $hasAttended ? 'Đã điểm danh' : 'Chưa điểm danh' }}</small>
                            </span>
                            @if ($hasAttended)
                                <i class="fa-solid fa-circle-check tc-roster-ok" aria-hidden="true"></i>
                            @else
                                <i class="fa-regular fa-circle tc-roster-wait" aria-hidden="true"></i>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </article>
</aside>
