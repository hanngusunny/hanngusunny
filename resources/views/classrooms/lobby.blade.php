@extends('layouts.app', ['active' => 'classroom'])

@php
    $roomId = (int) $room->id;
    $status = (string) ($room->status ?? 'scheduled');
    $statusLabel = $statuses[$status] ?? $status;
    $startLabel = $room->scheduled_start ? $room->scheduled_start->format('d/m/Y H:i') : '—';
    $endLabel = $room->scheduled_end ? $room->scheduled_end->format('d/m/Y H:i') : '—';
    $joinStatus = (string) ($joinRequest->status ?? '');
    $isPendingApproval = ! empty($requiresApproval) && $joinStatus === 'pending';
    $wasRejected = ! empty($requiresApproval) && $joinStatus === 'rejected';
    $authUser = auth()->user();
    $backUrl = $authUser?->isGuest() ? route('login') : route('classroom.index');
    $backLabel = $authUser?->isGuest() ? 'Thoát' : 'Quay lại danh sách';
    $enterLabel = ! empty($isHost) ? 'Bắt đầu lớp học' : (! empty($requiresApproval) ? 'Xin vào lớp' : 'Tham gia lớp học');
@endphp

@section('title', $room->title.' · Phòng chờ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/classroom-lobby.css') }}">
@endpush

@section('content')
<div class="cr-lobby-page">
    <header class="cr-lobby-hero">
        <div class="cr-lobby-hero-copy">
            <p class="cr-lobby-kicker">Phòng chờ</p>
            <h1>{{ $room->title }}</h1>
            <p class="cr-lobby-lead">Kiểm tra camera và micro trước khi vào lớp học trực tuyến.</p>
        </div>
        <div class="cr-lobby-hero-badges">
            <span class="cr-lobby-pill is-{{ $status }}">
                @if ($status === 'live')
                    <span class="cr-lobby-dot" aria-hidden="true"></span>
                @endif
                {{ $statusLabel }}
            </span>
            <span class="cr-lobby-pill is-people"><i class="fa-solid fa-users"></i> {{ $onlineCount }} đang online</span>
        </div>
    </header>

    <div class="cr-lobby-stage">
        <section class="cr-lobby-preview-card" aria-label="Xem trước camera">
            <div class="cr-preview-box">
                <video id="lobbyPreview" autoplay muted playsinline aria-label="Xem trước camera"></video>
                <div class="cr-preview-placeholder" id="lobbyPreviewPlaceholder">
                    <span class="cr-preview-placeholder-ico"><i class="fa-solid fa-video"></i></span>
                    <strong>Bật camera để xem trước</strong>
                    <span>Trình duyệt sẽ hỏi quyền camera và micro</span>
                </div>
                <div class="cr-preview-overlay">
                    <div class="cr-preview-name">
                        <x-user-avatar class="cr-preview-avatar" :url="$authUser?->avatarUrl()" :name="$userName" />
                        <span>{{ $userName }}</span>
                    </div>
                    <div class="cr-preview-controls">
                        <button class="cr-device-btn" type="button" id="toggleMic" aria-pressed="false" title="Micro">
                            <i class="fa-solid fa-microphone"></i>
                            <span>Mic</span>
                        </button>
                        <button class="cr-device-btn" type="button" id="toggleCam" aria-pressed="false" title="Camera">
                            <i class="fa-solid fa-video"></i>
                            <span>Camera</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="cr-lobby-rail">
            <section class="cr-lobby-card cr-lobby-join" aria-label="Vào lớp">
                @if ($room->description)
                    <p class="cr-lobby-desc">{{ $room->description }}</p>
                @endif

                @if (! empty($waitingForTeacher) && is_array($watchSession ?? null))
                    <p class="cr-lobby-note is-wait">
                        <i class="fa-solid fa-hourglass-half"></i>
                        Giáo viên chưa vào lớp — bạn vẫn có thể vào phòng và đợi.
                    </p>
                @endif

                @if ($isPendingApproval)
                    <div class="cr-lobby-state is-pending" id="classroomJoinWaiting" data-status-url="{{ $joinStatusUrl }}">
                        <span class="cr-lobby-state-ico"><i class="fa-solid fa-hourglass-half"></i></span>
                        <strong>Đang chờ giáo viên duyệt</strong>
                        <p>Yêu cầu vào lớp của bạn đã được gửi. Trang sẽ tự chuyển khi được duyệt.</p>
                    </div>
                @elseif ($wasRejected)
                    <div class="cr-lobby-state is-rejected">
                        <span class="cr-lobby-state-ico"><i class="fa-solid fa-circle-xmark"></i></span>
                        <strong>Yêu cầu vào lớp đã bị từ chối</strong>
                        @if (! empty($joinRequest->review_note))
                            <p>Lý do: {{ $joinRequest->review_note }}</p>
                        @endif
                    </div>
                    <form action="{{ route('classroom.enter', $roomId) }}" method="post" class="cr-enter-form">
                        @csrf
                        <button class="op-btn cr-lobby-cta" type="submit">
                            <i class="fa-solid fa-paper-plane"></i> Gửi lại yêu cầu
                        </button>
                    </form>
                @else
                    <form action="{{ route('classroom.enter', $roomId) }}" method="post" class="cr-enter-form">
                        @csrf
                        @if (! empty($needsPassword) && empty($isPendingApproval))
                            <label class="cr-lobby-field">
                                <span>Mật khẩu phòng</span>
                                <input type="password" name="room_password" required placeholder="Nhập mật khẩu phòng">
                            </label>
                        @endif
                        <div class="cr-lobby-account">
                            <x-user-avatar class="cr-lobby-account-avatar" :url="$authUser?->avatarUrl()" :name="$userName" />
                            <div>
                                <strong>{{ $userName }}</strong>
                                <small>Vào phòng với tài khoản · {{ $roleLabel }}</small>
                            </div>
                        </div>
                        <button class="op-btn cr-lobby-cta" type="submit">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            {{ $enterLabel }}
                        </button>
                    </form>
                @endif

                <a class="cr-lobby-back" href="{{ $backUrl }}">
                    <i class="fa-solid fa-arrow-left"></i> {{ $backLabel }}
                </a>
            </section>

            <section class="cr-lobby-card" aria-label="Thông tin phòng">
                <h2>Thông tin buổi học</h2>
                <dl class="cr-lobby-meta">
                    <div>
                        <dt><i class="fa-solid fa-chalkboard-user"></i> Giáo viên</dt>
                        <dd>{{ $room->host?->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt><i class="fa-regular fa-clock"></i> Bắt đầu</dt>
                        <dd>{{ $startLabel }}</dd>
                    </div>
                    <div>
                        <dt><i class="fa-regular fa-flag"></i> Kết thúc</dt>
                        <dd>{{ $endLabel }}</dd>
                    </div>
                    <div>
                        <dt><i class="fa-solid fa-key"></i> Mã phòng</dt>
                        <dd><code>{{ $room->room_code }}</code></dd>
                    </div>
                    @if (! empty($showTeacherLobbyLinks))
                        <div>
                            <dt><i class="fa-solid fa-hashtag"></i> ID phòng</dt>
                            <dd><code>{{ $roomId }}</code></dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="cr-lobby-card" aria-label="Hướng dẫn">
                <h2>Trước khi vào lớp</h2>
                <ul class="cr-lobby-tips">
                    <li><i class="fa-solid fa-circle-check"></i> Cho phép trình duyệt dùng camera và micro</li>
                    <li><i class="fa-solid fa-circle-check"></i> Ngồi nơi yên tĩnh, ánh sáng đủ</li>
                    <li><i class="fa-solid fa-circle-check"></i> Tắt thông báo để không bị gián đoạn</li>
                    <li><i class="fa-solid fa-circle-check"></i> Dùng tai nghe để giảm tiếng vọng</li>
                </ul>
            </section>

            @if (! empty($showTeacherLobbyLinks) && ! empty($jitsiMeetUrl))
                <section class="cr-lobby-card cr-lobby-link-card" aria-label="Link phòng Jitsi">
                    <h2>Link phòng Jitsi</h2>
                    <div class="cr-link-row">
                        <code id="lobbyJitsiMeetUrl">{{ $jitsiMeetUrl }}</code>
                        <button type="button" class="cr-copy-btn" id="copyJitsiMeetUrlBtn"><i class="fa-solid fa-copy"></i> Copy</button>
                    </div>
                    @if (! empty($jitsiRoomName))
                        <p class="cr-lobby-link-note">Tên phòng: <code>{{ $jitsiRoomName }}</code>@if (! empty($jitsiDomain)) · Domain: <code>{{ $jitsiDomain }}</code>@endif</p>
                    @endif
                    <p class="cr-lobby-link-note">Dùng link này để mở app Jitsi Meet. Phòng JaaS cần vào lớp qua nút bên trên — hệ thống cấp token khi vào video.</p>
                </section>
            @endif

            @if (! empty($showGuestJoinLink) && ! empty($guestJoinUrl))
                <section class="cr-lobby-card cr-lobby-link-card is-guest" aria-label="Link khách nick ngoài">
                    <h2>Link khách nick ngoài</h2>
                    <div class="cr-link-row">
                        <code id="lobbyGuestJoinUrl">{{ $guestJoinUrl }}</code>
                        <button type="button" class="cr-copy-btn" id="copyGuestJoinUrlBtn"><i class="fa-solid fa-copy"></i> Copy</button>
                    </div>
                    <p class="cr-lobby-link-note">Gửi link này cho người chưa có tài khoản. Họ nhập nick và chờ bạn duyệt.</p>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/classroom-lobby.js') }}"></script>
    <script src="{{ asset('assets/js/classroom-join-wait.js') }}"></script>
@endpush
