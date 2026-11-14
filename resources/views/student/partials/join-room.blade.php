@if (! empty($session['is_joinable']) || ! empty($session['waiting_teacher']) || ! empty($session['has_attended']))
    <div class="sw-join">
        <div>
            <strong>{{ $session['room_title'] ?: 'Phòng học online' }}</strong>
            @if (! empty($session['room_code']))
                <small>Mã phòng: {{ $session['room_code'] }}</small>
            @endif
            @if (! empty($session['waiting_teacher']))
                <small>Giáo viên chưa vào lớp — bạn có thể vào phòng và đợi.</small>
            @elseif (! empty($session['is_joinable']))
                <small>Buổi học đang mở. Vào lớp để điểm danh.</small>
            @elseif (! empty($session['has_attended']))
                <small>Bạn đã điểm danh buổi này.</small>
            @endif
        </div>
        @if (! empty($session['is_joinable']) || ! empty($session['waiting_teacher']))
            <form method="post" action="{{ route('student.session.join', $publicCode) }}">
                @csrf
                <button type="submit" class="op-btn">
                    <i class="fa-solid fa-video"></i> Vào lớp
                </button>
            </form>
        @endif
    </div>
@endif
