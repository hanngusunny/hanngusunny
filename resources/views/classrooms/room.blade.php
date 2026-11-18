@extends('layouts.classroom')

@section('title', $room->title.' · Lớp học')
@section('body_class', 'page-classroom-room')

@php
    $roomId = (int) $room->id;
@endphp

@section('content')
<div class="meeting-shell">
    <header class="meeting-topbar">
        <div class="meeting-topbar-left">
            <a class="meeting-back" href="{{ $leaveUrl }}" title="Rời phòng">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </a>
            <div>
                <strong class="meeting-title">{{ $room->title }}</strong>
                <span class="meeting-subtitle" id="participantCountLabel">Đang tải...</span>
                @if (! empty($isModerator))
                    <span class="meeting-room-meta">
                        ID phòng: <code>{{ $roomId }}</code>
                        · Mã: <code id="meetingRoomCode">{{ $room->room_code }}</code>
                        <button type="button" class="meeting-room-meta-copy" id="copyRoomMetaBtn" title="Sao chép ID và mã phòng">
                            <i class="fa-solid fa-copy" aria-hidden="true"></i>
                        </button>
                    </span>
                @endif
            </div>
        </div>
        <div class="meeting-topbar-right">
            @if (! empty($isModerator))
                <button class="meeting-breakout-btn" type="button" id="openBreakoutModalBtn" title="Chia phòng thảo luận">
                    <span class="meeting-breakout-btn__icon" aria-hidden="true">
                        <i class="fa-solid fa-people-group"></i>
                    </span>
                    <span class="meeting-breakout-btn__text">Chia phòng</span>
                </button>
                <button class="meeting-create-poll-btn" type="button" id="openCreatePollBtn">
                    <span class="meeting-create-poll-btn__icon" aria-hidden="true">
                        <i class="fa-solid fa-circle-question"></i>
                    </span>
                    <span class="meeting-create-poll-btn__text">Tạo câu hỏi</span>
                </button>
                <button class="meeting-pip-btn" type="button" id="toggleMeetingPipBtn" title="Mở danh sách lớp ở cửa sổ PiP">
                    <span class="meeting-pip-btn__icon" aria-hidden="true">
                        <i class="fa-solid fa-window-restore"></i>
                    </span>
                    <span class="meeting-pip-btn__text">PiP</span>
                </button>
            @endif
            <span class="meeting-live-badge" aria-label="Đang phát trực tiếp">
                <span class="meeting-live-badge__signal" aria-hidden="true">
                    <span class="meeting-live-badge__dot"></span>
                    <span class="meeting-live-badge__ring"></span>
                </span>
                <span class="meeting-live-badge__text">LIVE</span>
            </span>
            <div class="meeting-user meeting-user--{{ $userRole }}" title="{{ $userName }}">
                <span class="meeting-user__avatar" aria-hidden="true">
                    <img class="meeting-user__avatar-img" src="{{ $userAvatarUrl }}" alt="">
                </span>
                <span class="meeting-user__info">
                    <span class="meeting-user__name">{{ $userName }}</span>
                    <span class="meeting-user__role">{{ $userRoleLabel }}</span>
                </span>
            </div>
        </div>
    </header>

    <div class="meeting-body">
        <div class="meeting-video-wrap">
            @if (! empty($isModerator))
                <div class="meeting-breakout-teacher-bar" id="breakoutTeacherBar" hidden>
                    <span class="meeting-breakout-teacher-bar__label">
                        <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        Vào phòng trò chuyện:
                    </span>
                    <div class="meeting-breakout-teacher-bar__rooms" id="breakoutTeacherBarRooms"></div>
                    <button type="button" class="btn btn-ghost btn-sm meeting-breakout-teacher-bar__end" id="endBreakoutBarBtn">
                        <i class="fa-solid fa-door-closed" aria-hidden="true"></i>
                        Kết thúc
                    </button>
                </div>
            @endif
            <div id="jitsiContainer" class="meeting-jitsi"></div>
        </div>

        <aside class="meeting-sidebar" id="meetingSidebar">
            @if (! empty($isModerator))
                <div class="meeting-sidebar-tabs" role="tablist">
                    <button class="meeting-sidebar-tab is-active" type="button" data-tab="participants" role="tab" aria-selected="true">
                        <i class="fa-solid fa-users" aria-hidden="true"></i> Người tham gia
                    </button>
                    <button class="meeting-sidebar-tab" type="button" data-tab="polls" role="tab" aria-selected="false">
                        <i class="fa-solid fa-chart-column" aria-hidden="true"></i> Câu hỏi
                    </button>
                </div>
            @else
                <div class="meeting-sidebar-tabs" role="tablist">
                    <button class="meeting-sidebar-tab is-active" type="button" data-tab="participants" role="tab" aria-selected="true">
                        <i class="fa-solid fa-users" aria-hidden="true"></i> Người tham gia
                    </button>
                    <button class="meeting-sidebar-tab" type="button" data-tab="myanswers" role="tab" aria-selected="false">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Câu đã trả lời
                        <span class="meeting-tab-badge" id="studentAnswerCount">0</span>
                    </button>
                </div>
            @endif

            <div class="meeting-sidebar-panel is-active" id="participantsPanel" data-panel="participants">
                @if (! empty($isModerator) && ! empty($waitingRoomEnabled))
                    <div class="meeting-join-requests" id="meetingJoinRequests" hidden>
                        <div class="meeting-sidebar-head">
                            <h2>Chờ duyệt vào lớp</h2>
                            <span class="meeting-tab-badge" id="joinRequestCount">0</span>
                        </div>
                        @if (! empty($guestJoinUrl))
                            <p class="meeting-guest-link-note">
                                Link khách: <a href="{{ $guestJoinUrl }}" target="_blank" rel="noopener">{{ $guestJoinUrl }}</a>
                            </p>
                        @endif
                        <ul class="meeting-join-request-list" id="joinRequestList"></ul>
                    </div>
                @endif
                <div class="meeting-breakout-active-banner" id="breakoutActiveBanner" hidden>
                    <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                    <span id="breakoutActiveBannerText">Đang trong phòng thảo luận</span>
                </div>
                @if (! empty($isModerator))
                    <div class="meeting-break-panel" id="breakOutPanel" hidden>
                        <div class="meeting-break-panel-head">
                            <i class="fa-solid fa-person-walking-arrow-right" aria-hidden="true"></i>
                            <span>Học viên xin ra ngoài</span>
                            <span class="meeting-break-count" id="breakOutCount">0</span>
                        </div>
                        <ul class="meeting-break-list" id="breakOutList"></ul>
                    </div>
                    <div class="meeting-breakout-panel" id="breakoutRoomsPanel" hidden>
                        <div class="meeting-breakout-panel-head">
                            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                            <span>Phòng thảo luận</span>
                            <button class="btn btn-ghost btn-sm meeting-breakout-end-btn" type="button" id="endBreakoutBtn" title="Kết thúc chia phòng">
                                <i class="fa-solid fa-door-closed" aria-hidden="true"></i>
                                Kết thúc
                            </button>
                        </div>
                        <p class="meeting-breakout-note" id="breakoutSessionNote">Bấm <strong>Vào trò chuyện</strong> để tham gia phòng và nói chuyện với học viên trong nhóm.</p>
                        <ul class="meeting-breakout-room-list" id="breakoutRoomList"></ul>
                    </div>
                @endif
                <div class="meeting-sidebar-head">
                    <h2>Danh sách tham gia</h2>
                    <button class="btn btn-ghost btn-sm" type="button" id="refreshParticipants" title="Làm mới">
                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                    </button>
                </div>
                <ul class="meeting-participant-list" id="participantList">
                    <li class="meeting-participant-empty" id="participantListEmpty">Đang tải danh sách...</li>
                </ul>
            </div>

            @if (empty($isModerator))
                <div class="meeting-sidebar-panel" id="myAnswersPanel" data-panel="myanswers">
                    <div class="meeting-sidebar-head">
                        <h2>Lịch sử trả lời</h2>
                    </div>
                    <div class="meeting-student-answers" id="studentAnswerList">
                        <p class="meeting-poll-empty">Chưa trả lời câu hỏi nào.</p>
                    </div>
                </div>
            @endif

            @if (! empty($isModerator))
                <div class="meeting-sidebar-panel" id="pollsPanel" data-panel="polls">
                    <div id="moderatorPollMain">
                        <div class="meeting-review-quiz-block" id="moderatorReviewQuizBlock" hidden>
                            <div class="meeting-sidebar-head">
                                <h2>Kiểm tra bài cũ</h2>
                            </div>
                            <div class="meeting-review-quiz-card" id="moderatorReviewQuizCard">
                                <p class="meeting-review-quiz-desc" id="moderatorReviewQuizDesc">—</p>
                                <div class="meeting-review-quiz-actions" id="moderatorReviewQuizActions"></div>
                            </div>
                            <div class="meeting-review-quiz-stats" id="moderatorReviewQuizStats" hidden>
                                <div class="meeting-review-quiz-stats-head">
                                    <strong id="moderatorReviewQuizStatsSummary">0/0 đã nộp</strong>
                                    <button class="btn btn-ghost btn-sm text-danger" type="button" id="closeReviewQuizBtn" title="Đóng kiểm tra">
                                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="meeting-review-quiz-stats-body" id="moderatorReviewQuizStatsBody"></div>
                            </div>
                        </div>
                        <div class="meeting-preset-block">
                            <div class="meeting-sidebar-head">
                                <h2>Câu hỏi soạn sẵn</h2>
                            </div>
                            <div class="meeting-preset-list" id="moderatorPresetList">
                                <p class="meeting-poll-empty">Chưa có câu hỏi soạn sẵn. Admin thêm tại quản lý phòng học.</p>
                            </div>
                        </div>
                        <div class="meeting-poll-block">
                            <div class="meeting-sidebar-head">
                                <h2>Đã phát trong buổi</h2>
                            </div>
                            <div class="meeting-poll-list" id="moderatorPollList">
                                <p class="meeting-poll-empty">Chưa phát câu hỏi nào.</p>
                            </div>
                        </div>
                    </div>
                    <div class="meeting-poll-stats" id="moderatorPollStats" hidden>
                        <div class="meeting-poll-stats-head">
                            <button class="btn btn-ghost btn-sm" type="button" id="backToPollList" title="Quay lại">
                                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            </button>
                            <div>
                                <strong id="statsPollQuestion">—</strong>
                                <span id="statsPollSummary">0/0 đã trả lời</span>
                            </div>
                            <button class="btn btn-ghost btn-sm text-danger" type="button" id="closeActivePollBtn" title="Đóng câu hỏi">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="meeting-poll-stats-table-wrap">
                            <div class="meeting-poll-stats-groups" id="moderatorPollStatsBody"></div>
                        </div>
                    </div>
                </div>
            @endif
        </aside>
    </div>

    @if (! empty($isModerator))
        <div class="meeting-pip-prompt" id="meetingPipPrompt" hidden>
            <div class="meeting-pip-prompt__card">
                <strong>Đang chia sẻ màn hình</strong>
                <p>Mở cửa sổ PiP để vẫn thấy danh sách học viên / câu hỏi khi sang PowerPoint. Jitsi giữ nguyên trên trang chính nên không bị đăng nhập trùng.</p>
                <button type="button" class="btn btn-sunny btn-with-icon" id="meetingPipPromptOpenBtn">
                    <i class="fa-solid fa-window-restore"></i>
                    <span>Mở cửa sổ theo dõi lớp</span>
                </button>
            </div>
        </div>
    @endif

    <footer class="meeting-footer">
        @if (empty($isModerator))
            <div class="meeting-footer-student">
                <div class="meeting-breakout-student-status" id="studentBreakoutStatus" hidden>
                    <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                    <span>Phòng thảo luận <strong id="studentBreakoutRoomLabel">—</strong></span>
                </div>
                <div class="meeting-break-status" id="studentBreakStatus" hidden>
                    <i class="fa-solid fa-person-walking-arrow-right" aria-hidden="true"></i>
                    <span>Đang ra ngoài: <strong id="studentBreakDuration">—</strong></span>
                </div>
                <button class="btn btn-warning btn-with-icon" type="button" id="breakOutBtn">
                    <i class="fa-solid fa-person-walking-arrow-right" aria-hidden="true"></i>
                    <span>Xin ra ngoài</span>
                </button>
                <button class="btn btn-success btn-with-icon" type="button" id="breakReturnBtn" hidden>
                    <i class="fa-solid fa-door-open" aria-hidden="true"></i>
                    <span>Đã quay lại</span>
                </button>
                <button class="btn btn-ghost btn-with-icon meeting-mobile-answers-btn" type="button" id="openStudentAnswersBtn">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span>Câu đã trả lời</span>
                </button>
            </div>
        @endif
        <form action="{{ $leaveUrl }}" method="post" class="meeting-footer-leave-form" id="leaveForm">
            @csrf
            <button type="submit" class="btn btn-danger btn-with-icon meeting-footer-leave" id="leaveRoomBtn">
                <i class="fa-solid fa-phone-slash" aria-hidden="true"></i>
                <span>Rời phòng</span>
            </button>
        </form>
    </footer>

    <div class="meeting-break-toast" id="meetingBreakToast" hidden role="status" aria-live="polite"></div>
</div>

<div class="meeting-poll-overlay meeting-review-quiz-overlay" id="studentReviewQuizOverlay" hidden>
    <div class="meeting-poll-modal meeting-review-quiz-modal" role="dialog" aria-modal="true" aria-labelledby="studentReviewQuizTitle">
        <div class="meeting-poll-modal-hero meeting-review-quiz-hero">
            <div class="meeting-poll-modal-icon" aria-hidden="true">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <span class="meeting-poll-type-tag">Kiểm tra bài cũ</span>
            <h2 id="studentReviewQuizTitle">Trắc nghiệm kiểm tra bài cũ</h2>
            <p class="meeting-review-quiz-note">Trả lời đủ tất cả câu hỏi trước khi nộp bài.</p>
        </div>
        <div class="meeting-poll-modal-body meeting-review-quiz-body" id="studentReviewQuizBody"></div>
        <div class="meeting-poll-modal-foot meeting-review-quiz-foot" id="studentReviewQuizFoot">
            <button class="btn btn-sunny btn-with-icon meeting-poll-submit-btn" type="button" id="submitReviewQuizBtn">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Nộp bài kiểm tra</span>
            </button>
        </div>
    </div>
</div>

<div class="meeting-poll-overlay" id="studentPollOverlay" hidden>
    <div class="meeting-poll-modal meeting-poll-modal-student" role="dialog" aria-modal="true" aria-labelledby="studentPollTitle">
        <div class="meeting-poll-modal-hero">
            <button class="meeting-poll-close" type="button" id="closeStudentPollBtn" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="meeting-poll-modal-icon" id="studentPollIcon" aria-hidden="true">
                <i class="fa-solid fa-circle-question"></i>
            </div>
            <span class="meeting-poll-type-tag" id="studentPollTypeTag">Câu hỏi</span>
            <p class="meeting-poll-countdown" id="studentPollCountdown" hidden>
                <i class="fa-solid fa-clock" aria-hidden="true"></i>
                <span>Còn <strong id="studentPollCountdownText">01:00</strong> để trả lời</span>
            </p>
            <h2 id="studentPollTitle">—</h2>
        </div>
        <div class="meeting-poll-modal-body" id="studentPollBody"></div>
        <div class="meeting-poll-modal-foot" id="studentPollFoot">
            <button class="btn btn-sunny btn-with-icon meeting-poll-submit-btn" type="button" id="submitPollAnswerBtn">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Gửi câu trả lời</span>
            </button>
        </div>
    </div>
</div>

@if (! empty($isModerator))
    <div class="meeting-poll-overlay" id="createPollOverlay" hidden>
        <div class="meeting-poll-modal meeting-poll-modal-create" role="dialog" aria-modal="true" aria-labelledby="createPollTitle">
            <div class="meeting-poll-modal-head">
                <h2 id="createPollTitle">Tạo câu hỏi mới</h2>
                <button class="meeting-poll-close" type="button" id="closeCreatePollBtn" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="meeting-poll-modal-body">
                <label class="form-label" for="createPollQuestion">Nội dung câu hỏi</label>
                <textarea class="form-control" id="createPollQuestion" rows="3" placeholder="Trắc nghiệm: Câu hỏi + 4 đáp án. Điền từ: dùng ___ hoặc [...] cho chỗ trống."></textarea>
                <p class="meeting-poll-hint" id="createPollHint">Điền từ: học viên gõ từ còn thiếu vào ô trả lời.</p>

                <label class="form-label mt-3">Loại câu hỏi</label>
                <div class="meeting-poll-type-toggle">
                    <label class="meeting-poll-type-option">
                        <input type="radio" name="pollType" value="fill" checked>
                        <span><i class="fa-solid fa-i-cursor"></i> Điền từ</span>
                    </label>
                    <label class="meeting-poll-type-option">
                        <input type="radio" name="pollType" value="choice">
                        <span><i class="fa-solid fa-list"></i> Trắc nghiệm</span>
                    </label>
                </div>

                <div id="createPollOptionsWrap" hidden>
                    <label class="form-label mt-3">Các lựa chọn</label>
                    <div id="createPollOptions">
                        <input class="form-control mb-2" type="text" name="pollOption" placeholder="Lựa chọn A">
                        <input class="form-control mb-2" type="text" name="pollOption" placeholder="Lựa chọn B">
                        <input class="form-control mb-2" type="text" name="pollOption" placeholder="Lựa chọn C">
                        <input class="form-control mb-2" type="text" name="pollOption" placeholder="Lựa chọn D">
                    </div>
                </div>

                <label class="form-label mt-3">Thời gian trả lời</label>
                <div class="meeting-poll-duration-toggle" role="radiogroup" aria-label="Thời gian hiển thị câu hỏi">
                    <label class="meeting-poll-duration-option">
                        <input type="radio" name="pollDuration" value="1" checked>
                        <span>1 phút</span>
                    </label>
                    <label class="meeting-poll-duration-option">
                        <input type="radio" name="pollDuration" value="2">
                        <span>2 phút</span>
                    </label>
                    <label class="meeting-poll-duration-option">
                        <input type="radio" name="pollDuration" value="3">
                        <span>3 phút</span>
                    </label>
                    <label class="meeting-poll-duration-option">
                        <input type="radio" name="pollDuration" value="5">
                        <span>5 phút</span>
                    </label>
                </div>
            </div>
            <div class="meeting-poll-modal-foot">
                <button class="btn btn-ghost" type="button" id="cancelCreatePollBtn">Hủy</button>
                <button class="btn btn-sunny btn-with-icon" type="button" id="submitCreatePollBtn">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Gửi cho học viên</span>
                </button>
            </div>
        </div>
    </div>

    <div class="meeting-poll-overlay" id="breakoutModalOverlay" hidden>
        <div class="meeting-poll-modal meeting-breakout-modal" role="dialog" aria-modal="true" aria-labelledby="breakoutModalTitle">
            <div class="meeting-poll-modal-head">
                <h2 id="breakoutModalTitle">Chia phòng thảo luận</h2>
                <button class="meeting-poll-close" type="button" id="closeBreakoutModalBtn" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="meeting-poll-modal-body">
                <p class="meeting-breakout-modal-desc" id="breakoutModalDesc">Chọn cách chia phòng: tự động ngẫu nhiên hoặc chỉ định học viên cho từng phòng.</p>
                <div class="meeting-breakout-mode" role="radiogroup" aria-label="Cách chia phòng">
                    <label class="meeting-breakout-mode-option">
                        <input type="radio" name="breakoutAssignMode" value="auto" checked>
                        <span>Tự động chia</span>
                    </label>
                    <label class="meeting-breakout-mode-option">
                        <input type="radio" name="breakoutAssignMode" value="manual">
                        <span>Chọn người cho từng phòng</span>
                    </label>
                </div>
                <div class="meeting-breakout-form-grid">
                    <label class="meeting-breakout-field">
                        <span>Số phòng</span>
                        <input type="number" id="breakoutRoomCountInput" min="2" max="12" value="4" required>
                    </label>
                    <label class="meeting-breakout-field" id="breakoutCapacityField">
                        <span>Số người / phòng</span>
                        <input type="number" id="breakoutCapacityInput" min="2" max="10" value="4" required>
                    </label>
                </div>
                <div class="meeting-breakout-manual-panel" id="breakoutManualPanel" hidden>
                    <div class="meeting-breakout-manual-head">
                        <strong>Phân học viên vào phòng</strong>
                        <span class="meeting-breakout-manual-count" id="breakoutManualStudentCount">0 học viên online</span>
                    </div>
                    <p class="meeting-breakout-manual-note">Học viên không được chọn sẽ ở lại phòng chính.</p>
                    <ul class="meeting-breakout-manual-list" id="breakoutManualAssignList"></ul>
                </div>
            </div>
            <div class="meeting-poll-modal-foot">
                <button class="btn btn-ghost" type="button" id="cancelBreakoutModalBtn">Hủy</button>
                <button class="btn btn-sunny btn-with-icon" type="button" id="startBreakoutBtn">
                    <i class="fa-solid fa-people-group"></i>
                    <span>Bắt đầu chia phòng</span>
                </button>
            </div>
        </div>
    </div>
@endif

<script>
    window.syncClassroomCsrf = function (data) {
        if (data && data.csrfHash && window.classroomRoomConfig) {
            window.classroomRoomConfig.csrfHash = data.csrfHash;
        }
    };

    window.getClassroomCsrfToken = function () {
        const cfg = window.classroomRoomConfig || {};
        return cfg.csrfHash || "";
    };

    window.classroomPostForm = async function (url, fields) {
        const cfg = window.classroomRoomConfig || {};
        const body = new URLSearchParams();
        body.set(cfg.csrfName, window.getClassroomCsrfToken());
        Object.entries(fields || {}).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((v) => body.append(key + "[]", v));
            } else {
                body.set(key, value);
            }
        });
        const res = await fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body.toString(),
        });
        const text = await res.text();
        try {
            const data = JSON.parse(text);
            window.syncClassroomCsrf(data);
            return data;
        } catch (e) {
            return {
                ok: false,
                message: res.status === 403 ? "Phiên làm việc hết hạn, hãy tải lại trang." : "Không nhận được phản hồi từ máy chủ (" + res.status + ").",
            };
        }
    };

    window.classroomRoomConfig = {
        roomId: @json($roomId),
        roomCode: @json((string) $room->room_code),
        roomTitle: @json((string) $room->title),
        jitsiDomain: @json($jitsiDomain),
        useJaas: @json(! empty($useJaas)),
        tokenUrl: @json($tokenUrl ?? ''),
        jitsiClientKind: "web",
        initialBreakout: {{ (int) ($breakoutIndex ?? 0) }},
        userName: @json($userName),
        userEmail: @json($userEmail ?? ''),
        userId: @json($userId ?? 0),
        jitsiDisplayName: @json($jitsiDisplayName ?? $userName),
        joinRequestStatusUrl: @json($joinRequestStatusUrl ?? ''),
        approveJoinBaseUrl: @json($approveJoinBaseUrl ?? ''),
        isModerator: @json(! empty($isModerator)),
        waitingRoomEnabled: @json(! empty($waitingRoomEnabled)),
        jaasSetupIssues: @json($jaasSetupIssues ?? []),
        pingUrl: @json($pingUrl ?? ''),
        participantsUrl: @json($participantsUrl ?? ''),
        breakOutUrl: @json($breakOutUrl ?? ''),
        breakReturnUrl: @json($breakReturnUrl ?? ''),
        breakoutStartUrl: @json($breakoutStartUrl ?? ''),
        breakoutEndUrl: @json($breakoutEndUrl ?? ''),
        breakoutJoinUrl: @json($breakoutJoinUrl ?? ''),
        breakoutMainUrl: @json($breakoutMainUrl ?? ''),
        pollFeedUrl: @json($pollFeedUrl ?? ''),
        pollCreateUrl: @json($pollCreateUrl ?? ''),
        pollBaseUrl: @json($pollBaseUrl ?? ''),
        pollPresetBaseUrl: @json($pollPresetBaseUrl ?? ''),
        reviewQuizBaseUrl: @json($reviewQuizBaseUrl ?? ''),
        leaveUrl: @json($leaveUrl ?? ''),
        dashboardUrl: @json($dashboardUrl ?? url('dashboard')),
        fullPageVideoUrl: @json($fullPageVideoUrl ?? ''),
        videoLinkUrl: @json($videoLinkUrl ?? ''),
        presenceInterval: {{ (int) $presenceInterval }},
        csrfName: "_token",
        csrfHash: @json(csrf_token())
    };
</script>
@endsection

@push('scripts')
    <script src="https://{{ $jitsiDomain }}/external_api.js"></script>
    <script src="{{ asset('assets/js/classroom-room.js') }}?v=30"></script>
    <script src="{{ asset('assets/js/classroom-polls.js') }}?v=6"></script>
@endpush
