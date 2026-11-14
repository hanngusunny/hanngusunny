@extends('layouts.pwa', ['body_class' => 'is-lobby'])

@section('title', 'Phòng chờ')

@section('content')
<main class="pwa-lobby" id="pwaLobby">
    <div class="pwa-login-brand">
        <img class="pwa-mark" src="{{ asset('assets/images/pwa-icon.png') }}" alt="Hàn Ngữ SUNNY" width="56" height="56">
        <p>Phòng chờ</p>
        <h1>Sẵn sàng vào lớp</h1>
    </div>

    <section class="pwa-card">
        <div class="pwa-room-box">
            <span>Phòng học online</span>
            <strong>{{ $room->title ?: 'Lớp học SUNNY' }}</strong>
            @if ($room->room_code)
                <small>Mã phòng: {{ $room->room_code }}</small>
            @endif
        </div>
        <p class="pwa-help">Bạn sẽ vào trực tiếp cuộc gọi. Hệ thống dùng tên tài khoản của bạn.</p>
        <button type="button" class="pwa-btn" id="pwaEnterRoomBtn">
            <i class="fa-solid fa-video"></i>
            Vào phòng
        </button>
        <a class="pwa-btn pwa-btn--ghost" href="{{ $homeUrl }}">Quay lại</a>
    </section>
</main>

<div class="pwa-meet" id="pwaMeet" hidden>
    <form action="{{ $leaveUrl }}" method="post" id="leaveForm">
        @csrf
    </form>

    <div id="jitsiContainer" class="pwa-jitsi"></div>
    <div class="pwa-ios-video-hint" id="pwaIosVideoHint" hidden>
        <div class="pwa-ios-video-hint__card">
            <strong>Mẹo xem hình trên iPhone</strong>
            <ul>
                <li>Bấm <em>Tile view</em> (ô lưới) nếu màn hình đen.</li>
                <li>Bật <em>Camera</em> thủ công khi cần (mặc định tắt để vào phòng ổn định).</li>
                <li>Giữ app ở màn hình chính, tắt chế độ tiết kiệm pin.</li>
                <li>Khi giáo viên chia sẻ PPT mà vẫn đen: thử xoay ngang máy hoặc mở lại phòng.</li>
            </ul>
            <button type="button" class="pwa-btn pwa-btn--ghost pwa-ios-video-hint__btn" id="pwaIosVideoHintDismiss">Đã hiểu</button>
        </div>
    </div>
    <div class="pwa-toast" id="meetingBreakToast" hidden></div>

    <div class="pwa-poll" id="studentPollOverlay" hidden>
        <div class="pwa-poll-card" role="dialog" aria-modal="true" aria-labelledby="studentPollTitle">
            <span class="pwa-poll-tag" id="studentPollTypeTag">Câu hỏi</span>
            <p class="pwa-poll-time" id="studentPollCountdown" hidden>
                Còn <strong id="studentPollCountdownText">01:00</strong>
            </p>
            <h2 id="studentPollTitle">—</h2>
            <div id="studentPollBody"></div>
            <div class="pwa-poll-foot" id="studentPollFoot">
                <button type="button" class="pwa-btn" id="submitPollAnswerBtn">Gửi câu trả lời</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.getClassroomCsrfToken = function () {
        return (window.classroomRoomConfig && window.classroomRoomConfig.csrfHash) || '';
    };
    window.classroomPostForm = async function (url, fields) {
        const cfg = window.classroomRoomConfig || {};
        const body = new URLSearchParams();
        body.set(cfg.csrfName || '_token', window.getClassroomCsrfToken());
        Object.entries(fields || {}).forEach(([key, value]) => body.set(key, value));
        const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        });
        try { return await res.json(); } catch (e) { return { ok: false }; }
    };
    window.classroomRoomConfig = {
        roomId: @json($roomId),
        roomCode: @json((string) $room->room_code),
        roomTitle: @json((string) $room->title),
        jitsiDomain: @json($jitsiDomain),
        useJaas: @json(! empty($useJaas)),
        tokenUrl: @json($tokenUrl),
        jitsiClientKind: "pwa",
        userName: @json($userName),
        userEmail: @json($userEmail ?? ''),
        userId: @json($userId ?? 0),
        jitsiDisplayName: @json($jitsiDisplayName ?? $userName),
        isModerator: @json(! empty($isModerator)),
        pingUrl: @json($pingUrl),
        participantsUrl: @json($participantsUrl),
        pollFeedUrl: @json($pollFeedUrl),
        pollBaseUrl: @json($pollBaseUrl),
        leaveUrl: @json($leaveUrl),
        dashboardUrl: @json($homeUrl),
        initialBreakout: {{ (int) ($initialBreakout ?? 0) }},
        presenceInterval: {{ (int) $presenceInterval }},
        csrfName: '_token',
        csrfHash: @json(csrf_token())
    };
</script>
<script src="https://{{ $jitsiDomain }}/external_api.js"></script>
<script src="{{ asset('assets/js/pwa-room.js') }}?v=7"></script>
@endpush
