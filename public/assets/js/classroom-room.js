(() => {
  const cfg = window.classroomRoomConfig;
  if (!cfg) return;

  const container = document.getElementById("jitsiContainer");
  const participantList = document.getElementById("participantList");
  const participantCountLabel = document.getElementById("participantCountLabel");
  const refreshBtn = document.getElementById("refreshParticipants");
  const leaveBtn = document.getElementById("leaveRoomBtn");
  const leaveForm = document.getElementById("leaveForm");
  const breakOutBtn = document.getElementById("breakOutBtn");
  const breakReturnBtn = document.getElementById("breakReturnBtn");
  const studentBreakStatus = document.getElementById("studentBreakStatus");
  const studentBreakDuration = document.getElementById("studentBreakDuration");
  const breakOutPanel = document.getElementById("breakOutPanel");
  const breakOutList = document.getElementById("breakOutList");
  const breakOutCount = document.getElementById("breakOutCount");
  const breakToast = document.getElementById("meetingBreakToast");
  const meetingShell = document.querySelector(".meeting-shell");
  const meetingSidebar = document.getElementById("meetingSidebar");
  const toggleMeetingPipBtn = document.getElementById("toggleMeetingPipBtn");
  const meetingPipPrompt = document.getElementById("meetingPipPrompt");
  const meetingPipPromptOpenBtn = document.getElementById("meetingPipPromptOpenBtn");
  const supportsDocumentPip = () =>
    typeof window !== "undefined" && "documentPictureInPicture" in window;
  const joinRequestsBox = document.getElementById("meetingJoinRequests");
  const joinRequestList = document.getElementById("joinRequestList");
  const joinRequestCount = document.getElementById("joinRequestCount");
  const openBreakoutModalBtn = document.getElementById("openBreakoutModalBtn");
  const breakoutModalOverlay = document.getElementById("breakoutModalOverlay");
  const closeBreakoutModalBtn = document.getElementById("closeBreakoutModalBtn");
  const cancelBreakoutModalBtn = document.getElementById("cancelBreakoutModalBtn");
  const startBreakoutBtn = document.getElementById("startBreakoutBtn");
  const breakoutRoomCountInput = document.getElementById("breakoutRoomCountInput");
  const breakoutCapacityInput = document.getElementById("breakoutCapacityInput");
  const breakoutRoomsPanel = document.getElementById("breakoutRoomsPanel");
  const breakoutSessionNote = document.getElementById("breakoutSessionNote");
  const breakoutRoomList = document.getElementById("breakoutRoomList");
  const endBreakoutBtn = document.getElementById("endBreakoutBtn");
  const breakoutActiveBanner = document.getElementById("breakoutActiveBanner");
  const breakoutActiveBannerText = document.getElementById("breakoutActiveBannerText");
  const studentBreakoutStatus = document.getElementById("studentBreakoutStatus");
  const studentBreakoutRoomLabel = document.getElementById("studentBreakoutRoomLabel");
  const breakoutTeacherBar = document.getElementById("breakoutTeacherBar");
  const breakoutTeacherBarRooms = document.getElementById("breakoutTeacherBarRooms");
  const endBreakoutBarBtn = document.getElementById("endBreakoutBarBtn");
  const breakoutModalDesc = document.getElementById("breakoutModalDesc");
  const breakoutManualPanel = document.getElementById("breakoutManualPanel");
  const breakoutManualAssignList = document.getElementById("breakoutManualAssignList");
  const breakoutManualStudentCount = document.getElementById("breakoutManualStudentCount");
  const breakoutCapacityField = document.getElementById("breakoutCapacityField");
  const breakoutAssignModeInputs = document.querySelectorAll('input[name="breakoutAssignMode"]');

  let jitsiApi = null;
  let jitsiDomain = cfg.jitsiDomain || "8x8.vc";
  let localScreenSharing = false;
  let remoteScreenSharing = false;
  let pipWindow = null;
  let pipSidebarPlaceholder = null;
  let pingTimer = null;
  let participantsTimer = null;
  let breakDurationTimer = null;
  let breakToastTimer = null;
  let studentBreakOutAt = null;
  let lastBreakouts = [];
  let lastBreakoutSession = null;
  let lastParticipants = [];
  let manualBreakoutAssignments = {};
  let currentJitsiBreakoutIndex = 0;
  let switchingJitsi = false;
  let ignoreJitsiClose = false;
  let jitsiRemountAttempts = 0;
  let lastAudioMuted = null;
  let teacherWasPresent = false;
  let teacherAbsentStreak = 0;
  let redirectingAfterTeacherLeft = false;
  const TEACHER_LEFT_CONFIRM_POLLS = 4;

  const displayName = String(cfg.jitsiDisplayName || cfg.userName || "Học viên").trim();
  const userEmail = String(cfg.userEmail || "").trim();
  const jitsiClientId = (() => {
    const kind = String(cfg.jitsiClientKind || "web").replace(/[^a-z0-9]/gi, "") || "web";
    const key = "sunny-jitsi-client-" + kind;
    try {
      const existing = sessionStorage.getItem(key);
      if (existing && /^[a-z0-9][a-z0-9:_-]{7,79}$/i.test(existing)) {
        return existing;
      }
      const id = kind + "-" + Math.random().toString(36).slice(2, 10) + Math.random().toString(36).slice(2, 10);
      sessionStorage.setItem(key, id);
      return id;
    } catch (e) {
      return kind + "-" + String(Date.now());
    }
  })();

  const jitsiTokenUrl = (breakoutIndex = 0) => {
    const params = new URLSearchParams();
    if (breakoutIndex > 0) {
      params.set("breakout", String(breakoutIndex));
    }
    params.set("client", jitsiClientId);
    return cfg.tokenUrl + "?" + params.toString();
  };

  const isIOSLike = () => {
    const ua = navigator.userAgent || "";
    const isIPadOS = navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1;

    return /iPad|iPhone|iPod/i.test(ua) || isIPadOS;
  };

  const isMobileLike = () => isIOSLike() || /Android/i.test(navigator.userAgent || "");

  const supportsWebScreenShare = () => {
    if (typeof navigator === "undefined" || !navigator.mediaDevices) return false;

    return typeof navigator.mediaDevices.getDisplayMedia === "function";
  };

  const buildToolbarButtons = () => {
    const buttons = ["microphone", "camera", "closedcaptions"];

    if (!isIOSLike()) {
      buttons.push("desktop");
    }

    buttons.push(
      "fodeviceselection",
      "hangup",
      "chat",
      "raisehand",
      "tileview",
      "settings",
      "overflowmenu"
    );

    return buttons;
  };

  const copyTextToClipboard = async (text) => {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      return;
    }

    const input = document.createElement("textarea");
    input.value = text;
    input.setAttribute("readonly", "");
    input.style.position = "fixed";
    input.style.opacity = "0";
    document.body.appendChild(input);
    input.select();
    document.execCommand("copy");
    document.body.removeChild(input);
  };

  const setupRoomMetaCopy = () => {
    const btn = document.getElementById("copyRoomMetaBtn");
    if (!btn || btn.dataset.bound) return;

    btn.dataset.bound = "1";
    btn.addEventListener("click", async () => {
      const roomId = String(cfg.roomId || "");
      const roomCode = document.getElementById("meetingRoomCode")?.textContent?.trim() || String(cfg.roomCode || "");
      const text = ["ID phòng: " + roomId, "Mã phòng: " + roomCode].join("\n");

      try {
        await copyTextToClipboard(text);
        showBreakToast("Đã sao chép ID và mã phòng.", "success");
      } catch (error) {
        showBreakToast("Không sao chép được thông tin phòng.", "error");
      }
    });
  };

  const ensureJitsiIframePermissions = () => {
    if (!container) return;

    const iframe = container.querySelector("iframe");

    if (!iframe) return;

    iframe.setAttribute(
      "allow",
      [
        "camera *",
        "microphone *",
        "display-capture *",
        "autoplay *",
        "clipboard-write *",
        "fullscreen *",
        "screen-wake-lock *",
        "speaker-selection *",
      ].join("; ")
    );
  };

  const showBreakToast = (message, type = "info") => {
    if (!breakToast || !message) return;
    breakToast.textContent = message;
    breakToast.dataset.type = type;
    breakToast.hidden = false;
    clearTimeout(breakToastTimer);
    breakToastTimer = setTimeout(() => {
      breakToast.hidden = true;
    }, 4500);
  };

  const parseServerTime = (value) => {
    if (!value) return null;
    const normalized = String(value).trim().replace(" ", "T");
    const date = new Date(normalized);
    return Number.isNaN(date.getTime()) ? null : date;
  };

  const formatBreakDuration = (startValue) => {
    const start = parseServerTime(startValue);
    if (!start) return "—";
    const sec = Math.max(0, Math.floor((Date.now() - start.getTime()) / 1000));
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    if (m > 0) return m + " phút " + s + " giây";
    return s + " giây";
  };

  const roleLabel = (p) => {
    const roomRole = p.role || "";
    if (roomRole === "host") return "Chủ phòng";
    if (roomRole === "moderator") return "Giáo viên";

    const userRole = p.userRole || "";
    if (userRole === "admin") return "Quản trị";
    if (userRole === "teacher") return "Giáo viên";

    return "Học viên";
  };

  const updateStudentBreakUi = () => {
    const onBreak = studentBreakOutAt !== null;

    if (breakOutBtn) breakOutBtn.hidden = onBreak;
    if (breakReturnBtn) breakReturnBtn.hidden = !onBreak;
    if (studentBreakStatus) studentBreakStatus.hidden = !onBreak;

    if (onBreak && studentBreakDuration) {
      studentBreakDuration.textContent = formatBreakDuration(studentBreakOutAt);
    }
  };

  const startBreakDurationTicker = () => {
    if (breakDurationTimer) return;
    breakDurationTimer = setInterval(() => {
      if (studentBreakOutAt && studentBreakDuration) {
        studentBreakDuration.textContent = formatBreakDuration(studentBreakOutAt);
      }
      if (cfg.isModerator && breakOutList && lastBreakouts.length) {
        breakOutList.querySelectorAll("[data-break-out-at]").forEach((el) => {
          const span = el.querySelector(".meeting-break-duration");
          if (span) span.textContent = formatBreakDuration(el.getAttribute("data-break-out-at"));
        });
      }
    }, 1000);
  };

  const renderBreakouts = (items) => {
    if (!cfg.isModerator || !breakOutList) return;

    lastBreakouts = items || [];

    if (breakOutPanel) breakOutPanel.hidden = !lastBreakouts.length;
    if (breakOutCount) breakOutCount.textContent = String(lastBreakouts.length);

    breakOutList.innerHTML = "";

    if (!lastBreakouts.length) return;

    lastBreakouts.forEach((p) => {
      const li = document.createElement("li");
      li.className = "meeting-break-item";
      li.setAttribute("data-break-out-at", p.breakOutAt || "");
      li.innerHTML =
        '<span class="meeting-break-avatar">' +
        (p.name || "?").charAt(0).toUpperCase() +
        '</span><div class="meeting-break-meta"><strong>' +
        (p.name || "—") +
        '</strong><small class="meeting-break-duration">' +
        formatBreakDuration(p.breakOutAt) +
        "</small></div>";
      breakOutList.appendChild(li);
    });

    startBreakDurationTicker();
  };

  const postJoinReview = async (requestId, action) => {
    const base = String(cfg.approveJoinBaseUrl || "").replace(/\/$/, "");

    if (!base || !requestId) {
      throw new Error("Thiếu cấu hình duyệt vào lớp.");
    }

    const suffix = action === "approve" ? "duyet" : "tu-choi";
    const body = new URLSearchParams();

    if (cfg.csrfName && cfg.csrfHash) {
      body.set(cfg.csrfName, cfg.csrfHash);
    }

    const res = await fetch(base + "/" + requestId + "/" + suffix, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: body.toString(),
    });

    const data = await res.json();

    if (!data.ok) {
      throw new Error(data.message || "Không xử lý được yêu cầu.");
    }

    return data;
  };

  const renderJoinRequests = (items) => {
    if (!cfg.isModerator || !cfg.waitingRoomEnabled || !joinRequestList) return;

    const rows = items || [];

    if (joinRequestsBox) {
      joinRequestsBox.hidden = rows.length === 0;
    }

    if (joinRequestCount) {
      joinRequestCount.textContent = String(rows.length);
      joinRequestCount.hidden = rows.length === 0;
    }

    joinRequestList.innerHTML = "";

    if (!rows.length) return;

    rows.forEach((row) => {
      const li = document.createElement("li");
      li.className = "meeting-join-request-item";
      const guestTag = row.isGuest ? ' <span class="meeting-join-request-guest">Nick ngoài</span>' : "";

      li.innerHTML =
        '<div class="meeting-join-request-meta"><strong>' +
        (row.name || "—") +
        guestTag +
        '</strong><small>Đang chờ duyệt</small></div>' +
        '<div class="meeting-join-request-actions">' +
        '<button type="button" class="btn btn-success btn-sm" data-join-action="approve" data-request-id="' +
        row.requestId +
        '">Duyệt</button>' +
        '<button type="button" class="btn btn-outline btn-sm text-danger" data-join-action="reject" data-request-id="' +
        row.requestId +
        '">Từ chối</button>' +
        "</div>";

      joinRequestList.appendChild(li);
    });

    joinRequestList.querySelectorAll("[data-join-action]").forEach((btn) => {
      if (btn.dataset.joinBound) return;
      btn.dataset.joinBound = "1";
      btn.addEventListener("click", async () => {
        const requestId = Number(btn.dataset.requestId || 0);
        const action = btn.dataset.joinAction;

        if (!requestId) return;

        btn.disabled = true;

        try {
          const data = await postJoinReview(requestId, action);
          showBreakToast(data.message || "Đã cập nhật yêu cầu.", action === "approve" ? "success" : "info");
          fetchParticipants();
        } catch (error) {
          showBreakToast(error.message || "Không xử lý được yêu cầu.", "error");
          btn.disabled = false;
        }
      });
    });
  };

  const sortParticipants = (items) => {
    return [...(items || [])].sort((a, b) => {
      const aBreak = Boolean(a.onBreak);
      const bBreak = Boolean(b.onBreak);
      if (aBreak !== bBreak) return aBreak ? -1 : 1;

      const aRole = a.role === "host" || a.role === "moderator" ? 0 : 1;
      const bRole = b.role === "host" || b.role === "moderator" ? 0 : 1;
      if (aRole !== bRole) return aRole - bRole;

      return String(a.name || "").localeCompare(String(b.name || ""), "vi");
    });
  };

  const renderParticipants = (items) => {
    if (!participantList) return;

    const sorted = sortParticipants(items);
    participantList.innerHTML = "";

    if (!sorted.length) {
      const empty = document.createElement("li");
      empty.className = "meeting-participant-empty";
      empty.textContent = "Chưa có ai trong phòng hoặc đang chờ kết nối.";
      participantList.appendChild(empty);
    } else {
      sorted.forEach((p) => {
        const li = document.createElement("li");
        const isSelf = Number(p.userId) === Number(cfg.userId);
        const onBreak = Boolean(p.onBreak);
        const breakoutRoom = Number(p.breakoutRoom || 0);
        li.className =
          "meeting-participant-item" +
          (isSelf ? " is-self" : "") +
          (onBreak ? " is-on-break" : "");
        li.innerHTML =
          '<span class="meeting-participant-avatar">' +
          (p.name || "?").charAt(0).toUpperCase() +
          '</span><div class="meeting-participant-meta"><strong>' +
          (p.name || "—") +
          (isSelf ? ' <em class="meeting-participant-you">(Bạn)</em>' : "") +
          (onBreak ? ' <span class="meeting-participant-break-tag">Ra ngoài</span>' : "") +
          (breakoutRoom > 0 ? ' <span class="meeting-participant-breakout-tag">Phòng ' + breakoutRoom + "</span>" : "") +
          "</strong><small>" +
          roleLabel(p) +
          (onBreak ? " · " + formatBreakDuration(p.breakOutAt) : "") +
          "</small></div>";
        participantList.appendChild(li);
      });
    }

    if (participantCountLabel) {
      participantCountLabel.textContent = sorted.length + " người đang tham gia";
    }
  };

  const syncSelfBreakState = (participants) => {
    if (cfg.isModerator) return;
    const self = (participants || []).find((p) => Number(p.userId) === Number(cfg.userId));
    if (self && self.onBreak && self.breakOutAt) {
      studentBreakOutAt = self.breakOutAt;
    } else if (!self || !self.onBreak) {
      studentBreakOutAt = null;
    }
    updateStudentBreakUi();
    startBreakDurationTicker();
  };

  const hasModeratorOnline = (participants) =>
    (participants || []).some((p) => p.role === "host" || p.role === "moderator");

  const isTeacherPresentInPoll = (data) => {
    const serverFlag = data.teacherInRoom !== undefined ? Boolean(data.teacherInRoom) : null;
    const moderatorOnline = hasModeratorOnline(data.participants);

    if (serverFlag === true || moderatorOnline) {
      return true;
    }

    if (serverFlag === false) {
      return false;
    }

    return moderatorOnline;
  };

  const redirectStudentToDashboard = (message) => {
    if (redirectingAfterTeacherLeft || cfg.isModerator) return;
    redirectingAfterTeacherLeft = true;
    cleanup();
    showBreakToast(message || "Giáo viên đã rời phòng. Đang chuyển về trang chủ...", "info");
    window.setTimeout(() => {
      if (leaveForm) {
        leaveForm.submit();
      } else if (cfg.leaveUrl) {
        window.location.href = cfg.leaveUrl;
      } else if (cfg.dashboardUrl) {
        window.location.href = cfg.dashboardUrl;
      }
    }, 1500);
  };

  const checkTeacherPresence = (data) => {
    if (cfg.isModerator || redirectingAfterTeacherLeft) return;

    const teacherInRoom = isTeacherPresentInPoll(data);

    if (teacherInRoom) {
      teacherWasPresent = true;
      teacherAbsentStreak = 0;
      return;
    }

    if (!teacherWasPresent) {
      return;
    }

    teacherAbsentStreak += 1;

    if (teacherAbsentStreak < TEACHER_LEFT_CONFIRM_POLLS) {
      return;
    }

    redirectStudentToDashboard("Giáo viên đã kết thúc buổi học. Bạn được chuyển về trang chủ.");
  };

  const isBreakoutActive = (session) =>
    Boolean(session && session.active === true && Number(session.roomCount || 0) >= 2);

  const updateBreakoutBanner = (session) => {
    const active = isBreakoutActive(session);
    const myRoom = Number(session && session.myBreakoutRoom ? session.myBreakoutRoom : 0);

    if (breakoutActiveBanner) {
      if (cfg.isModerator) {
        breakoutActiveBanner.hidden = !active;
        if (active && breakoutActiveBannerText) {
          breakoutActiveBannerText.textContent = myRoom > 0
            ? "Bạn đang trò chuyện tại phòng " + myRoom
            : "Đang chia phòng — chọn phòng để vào trò chuyện với học viên";
        }
      } else {
        breakoutActiveBanner.hidden = !active || myRoom <= 0;
        if (active && breakoutActiveBannerText && myRoom > 0) {
          breakoutActiveBannerText.textContent = "Đang trong phòng thảo luận " + myRoom;
        }
      }
    }

    if (studentBreakoutStatus) {
      studentBreakoutStatus.hidden = !active || myRoom <= 0 || cfg.isModerator;
    }

    if (studentBreakoutRoomLabel && myRoom > 0) {
      studentBreakoutRoomLabel.textContent = String(myRoom);
    }
  };

  const renderBreakoutTeacherBar = (session) => {
    if (!cfg.isModerator || !breakoutTeacherBar || !breakoutTeacherBarRooms) return;

    const active = isBreakoutActive(session);
    breakoutTeacherBar.hidden = !active;

    if (!active) {
      breakoutTeacherBarRooms.innerHTML = "";
      return;
    }

    const myRoom = Number(session.myBreakoutRoom || 0);
    const rooms = Array.isArray(session.rooms) ? session.rooms : [];
    breakoutTeacherBarRooms.innerHTML = "";

    const addRoomChip = (index, label, count) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className =
        "meeting-breakout-teacher-bar__room" + (index === myRoom ? " is-active" : "");
      btn.innerHTML = label + (count >= 0 ? ' <small>(' + count + ")</small>" : "");
      btn.addEventListener("click", () => {
        if (index === myRoom) return;
        if (index === 0) {
          returnTeacherToMainRoom();
        } else {
          joinTeacherBreakoutRoom(index);
        }
      });
      breakoutTeacherBarRooms.appendChild(btn);
    };

    addRoomChip(0, "Phòng chính", -1);

    rooms.forEach((room) => {
      const index = Number(room.index || 0);
      if (index <= 0) return;
      addRoomChip(index, "Phòng " + index, Number(room.count || (room.members || []).length || 0));
    });
  };

  const renderBreakoutSession = (session) => {
    lastBreakoutSession = session || null;
    const active = isBreakoutActive(session);

    if (openBreakoutModalBtn) {
      openBreakoutModalBtn.hidden = active;
    }

    if (breakoutRoomsPanel) {
      breakoutRoomsPanel.hidden = !cfg.isModerator || !active;
    }

    updateBreakoutBanner(session);
    renderBreakoutTeacherBar(session);

    if (!cfg.isModerator || !breakoutRoomList) {
      return;
    }

    breakoutRoomList.innerHTML = "";

    if (!active) {
      if (breakoutSessionNote) breakoutSessionNote.textContent = "—";
      return;
    }

    const myRoom = Number(session.myBreakoutRoom || 0);
    const rooms = Array.isArray(session.rooms) ? session.rooms : [];

    if (breakoutSessionNote) {
      breakoutSessionNote.innerHTML =
        session.roomCount +
        " phòng · tối đa " +
        session.perRoomCapacity +
        " người/phòng. Bấm <strong>Vào trò chuyện</strong> để tham gia phòng và nói chuyện với học viên.";
    }

    const addMainRoomRow = () => {
      const backLi = document.createElement("li");
      backLi.className = "meeting-breakout-room-item" + (myRoom === 0 ? " is-current" : "");
      backLi.innerHTML =
        '<div class="meeting-breakout-room-head"><strong>Phòng chính</strong><button type="button" class="btn btn-ghost btn-sm" data-breakout-index="0">' +
        (myRoom === 0 ? "Đang ở đây" : "Vào trò chuyện") +
        "</button></div>";
      const joinBtn = backLi.querySelector("button[data-breakout-index]");
      if (joinBtn) {
        joinBtn.disabled = myRoom === 0;
        joinBtn.addEventListener("click", () => returnTeacherToMainRoom());
      }
      breakoutRoomList.appendChild(backLi);
    };

    addMainRoomRow();

    rooms.forEach((room) => {
      const index = Number(room.index || 0);
      const members = Array.isArray(room.members) ? room.members : [];
      const li = document.createElement("li");
      li.className = "meeting-breakout-room-item" + (index === myRoom ? " is-current" : "");

      const memberNames = members.length
        ? members.map((m) => m.name || "—").join(", ")
        : "Chưa có ai";

      li.innerHTML =
        '<div class="meeting-breakout-room-head"><strong>Phòng ' +
        index +
        " (" +
        (room.count || members.length) +
        ')</strong><button type="button" class="btn btn-ghost btn-sm" data-breakout-index="' +
        index +
        '">' +
        (index === myRoom ? "Đang ở đây" : "Vào trò chuyện") +
        '</button></div><ul class="meeting-breakout-room-members"><li>' +
        memberNames +
        "</li></ul>";

      const joinBtn = li.querySelector("button[data-breakout-index]");
      if (joinBtn) {
        joinBtn.disabled = index === myRoom;
        joinBtn.addEventListener("click", () => joinTeacherBreakoutRoom(index));
      }

      breakoutRoomList.appendChild(li);
    });
  };

  const getTargetBreakoutIndex = (session) => {
    if (!isBreakoutActive(session)) {
      return 0;
    }

    return Math.max(0, Number(session.myBreakoutRoom || 0));
  };

  const syncBreakoutFromPoll = async (session) => {
    renderBreakoutSession(session);

    const targetIndex = getTargetBreakoutIndex(session);

    if (targetIndex !== currentJitsiBreakoutIndex && !switchingJitsi) {
      await switchJitsiRoom(targetIndex);
    }

    const pollMs =
      isBreakoutActive(session) ? 4000 : cfg.waitingRoomEnabled && cfg.isModerator ? 4000 : 8000;

    if (participantsTimer) {
      clearInterval(participantsTimer);
    }

    participantsTimer = setInterval(fetchParticipants, pollMs);
  };

  const openBreakoutModal = async () => {
    if (breakoutModalOverlay) breakoutModalOverlay.hidden = false;
    updateBreakoutModeUi();
    await fetchParticipants();
    renderManualAssignList();
  };

  const closeBreakoutModal = () => {
    if (breakoutModalOverlay) breakoutModalOverlay.hidden = true;
  };

  const getBreakoutAssignMode = () => {
    const checked = document.querySelector('input[name="breakoutAssignMode"]:checked');
    return checked && checked.value === "manual" ? "manual" : "auto";
  };

  const getBreakoutStudents = () =>
    (lastParticipants || []).filter(
      (p) =>
        p.role === "participant" &&
        !p.onBreak &&
        Number(p.userId) !== Number(cfg.userId)
    );

  const updateBreakoutModeUi = () => {
    const isManual = getBreakoutAssignMode() === "manual";

    if (breakoutManualPanel) breakoutManualPanel.hidden = !isManual;
    if (breakoutCapacityField) breakoutCapacityField.hidden = isManual;

    if (breakoutModalDesc) {
      breakoutModalDesc.textContent = isManual
        ? "Chọn phòng cho từng học viên đang online. Học viên không được chọn sẽ ở lại phòng chính."
        : "Học viên sẽ tự động được phân ngẫu nhiên vào các phòng con. Bạn có thể vào từng phòng và kết thúc khi xong.";
    }
  };

  const buildBreakoutRoomOptions = (roomCount, selectedValue) => {
    let html = '<option value="">Phòng chính</option>';

    for (let i = 1; i <= roomCount; i += 1) {
      html +=
        '<option value="' +
        i +
        '"' +
        (Number(selectedValue) === i ? " selected" : "") +
        ">Phòng " +
        i +
        "</option>";
    }

    return html;
  };

  const renderManualAssignList = () => {
    if (!breakoutManualAssignList) return;

    const students = getBreakoutStudents();
    const roomCount = Math.max(2, Math.min(12, Number(breakoutRoomCountInput?.value || 0)));

    if (breakoutManualStudentCount) {
      breakoutManualStudentCount.textContent = students.length + " học viên online";
    }

    breakoutManualAssignList.innerHTML = "";

    if (!students.length) {
      breakoutManualAssignList.innerHTML =
        '<li class="meeting-breakout-manual-empty">Chưa có học viên online để phân phòng.</li>';
      return;
    }

    students.forEach((student) => {
      const userId = Number(student.userId || 0);
      if (userId <= 0) return;

      const selectedValue = manualBreakoutAssignments[userId] || "";
      const li = document.createElement("li");
      li.className = "meeting-breakout-manual-item";
      li.innerHTML =
        "<strong>" +
        (student.name || "Học viên") +
        '</strong><select data-breakout-user-id="' +
        userId +
        '">' +
        buildBreakoutRoomOptions(roomCount, selectedValue) +
        "</select>";

      const select = li.querySelector("select");
      select?.addEventListener("change", () => {
        const value = Number(select.value || 0);
        if (value > 0) {
          manualBreakoutAssignments[userId] = value;
        } else {
          delete manualBreakoutAssignments[userId];
        }
      });

      breakoutManualAssignList.appendChild(li);
    });
  };

  const collectManualAssignments = () => {
    const assignments = {};
    const roomCount = Math.max(2, Math.min(12, Number(breakoutRoomCountInput?.value || 0)));

    Object.entries(manualBreakoutAssignments).forEach(([userId, roomIndex]) => {
      const uid = Number(userId);
      const room = Number(roomIndex);

      if (uid > 0 && room >= 1 && room <= roomCount) {
        assignments[uid] = room;
      }
    });

    if (breakoutManualAssignList) {
      breakoutManualAssignList.querySelectorAll("select[data-breakout-user-id]").forEach((select) => {
        const uid = Number(select.getAttribute("data-breakout-user-id") || 0);
        const room = Number(select.value || 0);

        if (uid > 0 && room >= 1 && room <= roomCount) {
          assignments[uid] = room;
        }
      });
    }

    return assignments;
  };

  const startBreakoutSession = async () => {
    if (!cfg.breakoutStartUrl || !window.classroomPostForm) {
      showBreakToast("Chưa cấu hình chia phòng. Hãy tải lại trang.", "error");
      return;
    }

    const roomCount = Number(breakoutRoomCountInput?.value || 0);
    const perRoomCapacity = Number(breakoutCapacityInput?.value || 0);
    const assignMode = getBreakoutAssignMode();

    if (roomCount < 2) {
      showBreakToast("Nhập ít nhất 2 phòng.", "error");
      return;
    }

    if (assignMode === "auto" && perRoomCapacity < 2) {
      showBreakToast("Nhập ít nhất 2 người/phòng.", "error");
      return;
    }

    const payload = {
      room_count: String(roomCount),
      per_room_capacity: String(perRoomCapacity),
      assign_mode: assignMode,
    };

    if (assignMode === "manual") {
      const assignments = collectManualAssignments();

      if (Object.keys(assignments).length === 0) {
        showBreakToast("Chọn ít nhất một học viên vào phòng thảo luận.", "error");
        return;
      }

      payload.assignments = JSON.stringify(assignments);
    }

    if (startBreakoutBtn) startBreakoutBtn.disabled = true;

    try {
      const data = await window.classroomPostForm(cfg.breakoutStartUrl, payload);

      if (data.ok) {
        closeBreakoutModal();
        manualBreakoutAssignments = {};
        showBreakToast(data.message || "Đã bắt đầu chia phòng.", "success");
        await syncBreakoutFromPoll(data.breakoutSession || null);
        fetchParticipants();
      } else {
        showBreakToast(data.message || "Không chia được phòng.", "error");
      }
    } catch (e) {
      showBreakToast("Lỗi kết nối. Vui lòng thử lại.", "error");
    } finally {
      if (startBreakoutBtn) startBreakoutBtn.disabled = false;
    }
  };

  const endBreakoutSession = async () => {
    if (!cfg.breakoutEndUrl || !window.classroomPostForm) return;

    if (!window.confirm("Kết thúc chia phòng và đưa mọi người về phòng chính?")) {
      return;
    }

    if (endBreakoutBtn) endBreakoutBtn.disabled = true;

    try {
      const data = await window.classroomPostForm(cfg.breakoutEndUrl, {});

      if (data.ok) {
        showBreakToast(data.message || "Đã kết thúc chia phòng.", "success");
        await syncBreakoutFromPoll(data.breakoutSession || { active: false, myBreakoutRoom: 0 });
        fetchParticipants();
      } else {
        showBreakToast(data.message || "Không kết thúc được chia phòng.", "error");
      }
    } catch (e) {
      showBreakToast("Lỗi kết nối. Vui lòng thử lại.", "error");
    } finally {
      if (endBreakoutBtn) endBreakoutBtn.disabled = false;
    }
  };

  const joinTeacherBreakoutRoom = async (breakoutIndex) => {
    if (!cfg.breakoutJoinUrl || !window.classroomPostForm) return;

    try {
      const data = await window.classroomPostForm(cfg.breakoutJoinUrl, {
        breakout_index: String(breakoutIndex),
      });

      if (data.ok) {
        showBreakToast(data.message || "Đang chuyển vào phòng " + breakoutIndex + "...", "success");
        const session = data.breakoutSession || lastBreakoutSession;
        if (session) {
          session.myBreakoutRoom = breakoutIndex;
        }
        renderBreakoutSession(session);
        await switchJitsiRoom(breakoutIndex);
        fetchParticipants();
      } else {
        showBreakToast(data.message || "Không vào được phòng.", "error");
      }
    } catch (e) {
      showBreakToast("Lỗi kết nối. Vui lòng thử lại.", "error");
    }
  };

  const returnTeacherToMainRoom = async () => {
    if (!cfg.breakoutMainUrl || !window.classroomPostForm) return;

    try {
      const data = await window.classroomPostForm(cfg.breakoutMainUrl, {});

      if (data.ok) {
        showBreakToast(data.message || "Đang quay về phòng chính.", "success");
        const session = data.breakoutSession || lastBreakoutSession;
        if (session) {
          session.myBreakoutRoom = 0;
        }
        renderBreakoutSession(session);
        await switchJitsiRoom(0);
        fetchParticipants();
      } else {
        showBreakToast(data.message || "Không quay về được phòng chính.", "error");
      }
    } catch (e) {
      showBreakToast("Lỗi kết nối. Vui lòng thử lại.", "error");
    }
  };

  const fetchParticipants = async () => {
    if (!cfg.participantsUrl) return;

    try {
      const res = await fetch(cfg.participantsUrl, {
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      });
      const data = await res.json();
      if (data.ok) {
        lastParticipants = data.participants || [];
        renderParticipants(data.participants || []);
        renderBreakouts(data.breakouts || []);
        renderJoinRequests(data.pendingJoinRequests || []);
        syncSelfBreakState(data.participants || []);
        checkTeacherPresence(data);
        await syncBreakoutFromPoll(data.breakoutSession || null);
        return;
      }
      renderParticipants([]);
      renderBreakouts([]);
      renderJoinRequests([]);
    } catch (e) {
      if (participantList) {
        participantList.innerHTML =
          '<li class="meeting-participant-empty">Không tải được danh sách. Nhấn làm mới để thử lại.</li>';
      }
    }
  };

  const requestBreakOut = async () => {
    if (!cfg.breakOutUrl) {
      showBreakToast("Chưa cấu hình xin ra ngoài. Hãy tải lại trang.", "error");
      return;
    }
    if (!window.classroomPostForm) {
      showBreakToast("Lỗi hệ thống. Hãy tải lại trang.", "error");
      return;
    }

    const originalLabel = breakOutBtn?.querySelector("span")?.textContent || "Xin ra ngoài";
    if (breakOutBtn) {
      breakOutBtn.disabled = true;
      const label = breakOutBtn.querySelector("span");
      if (label) label.textContent = "Đang gửi...";
    }

    try {
      const data = await window.classroomPostForm(cfg.breakOutUrl, {});
      if (data.ok) {
        studentBreakOutAt = data.breakOutAt || new Date().toISOString().slice(0, 19).replace("T", " ");
        updateStudentBreakUi();
        startBreakDurationTicker();
        fetchParticipants();
        showBreakToast(data.message || "Đã báo giáo viên bạn xin ra ngoài.", "success");
      } else {
        showBreakToast(data.message || "Không gửi được yêu cầu xin ra ngoài.", "error");
      }
    } catch (e) {
      showBreakToast("Lỗi kết nối. Vui lòng thử lại.", "error");
    } finally {
      if (breakOutBtn) {
        breakOutBtn.disabled = false;
        const label = breakOutBtn.querySelector("span");
        if (label) label.textContent = originalLabel;
      }
    }
  };

  const returnFromBreak = async () => {
    if (!cfg.breakReturnUrl) {
      showBreakToast("Chưa cấu hình quay lại. Hãy tải lại trang.", "error");
      return;
    }
    if (!window.classroomPostForm) {
      showBreakToast("Lỗi hệ thống. Hãy tải lại trang.", "error");
      return;
    }

    const originalLabel = breakReturnBtn?.querySelector("span")?.textContent || "Đã quay lại";
    if (breakReturnBtn) {
      breakReturnBtn.disabled = true;
      const label = breakReturnBtn.querySelector("span");
      if (label) label.textContent = "Đang gửi...";
    }

    try {
      const data = await window.classroomPostForm(cfg.breakReturnUrl, {});
      if (data.ok) {
        studentBreakOutAt = null;
        updateStudentBreakUi();
        fetchParticipants();
        showBreakToast(data.message || "Đã báo giáo viên bạn quay lại lớp.", "success");
      } else {
        showBreakToast(data.message || "Không gửi được thông báo quay lại.", "error");
      }
    } catch (e) {
      showBreakToast("Lỗi kết nối. Vui lòng thử lại.", "error");
    } finally {
      if (breakReturnBtn) {
        breakReturnBtn.disabled = false;
        const label = breakReturnBtn.querySelector("span");
        if (label) label.textContent = originalLabel;
      }
    }
  };

  const pingPresence = async () => {
    if (!cfg.pingUrl) return;

    try {
      const body = new URLSearchParams();
      body.set(cfg.csrfName, window.getClassroomCsrfToken ? window.getClassroomCsrfToken() : cfg.csrfHash);
      const res = await fetch(cfg.pingUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: body.toString(),
      });
      if (res.redirected || res.status === 401 || res.status === 419) {
        if (pingTimer) {
          clearInterval(pingTimer);
          pingTimer = null;
        }
        showBreakToast("Phiên đăng nhập hết hạn. Đang tải lại trang...", "error");
        setTimeout(() => window.location.reload(), 1500);
        return;
      }
      if (!res.ok) return;
      const data = await res.json();
      if (window.syncClassroomCsrf) window.syncClassroomCsrf(data);
    } catch (e) {
      /* ignore */
    }
  };

  const focusParticipantsPanel = () => {
    document.querySelectorAll(".meeting-sidebar-tab").forEach((btn) => {
      const active = btn.dataset.tab === "participants";
      btn.classList.toggle("is-active", active);
      btn.setAttribute("aria-selected", active ? "true" : "false");
    });
    document.querySelectorAll(".meeting-sidebar-panel").forEach((panel) => {
      panel.classList.toggle("is-active", panel.dataset.panel === "participants");
    });
  };

  const exitBrowserFullscreen = () => {
    try {
      if (document.fullscreenElement) {
        document.exitFullscreen();
      }
    } catch (e) {
      /* ignore */
    }
  };

  const syncScreenShareLayout = () => {
    const active = localScreenSharing || remoteScreenSharing;
    if (meetingShell) {
      meetingShell.classList.toggle("is-screen-sharing", active);
    }
    if (meetingPipPrompt) {
      meetingPipPrompt.hidden = !(localScreenSharing && supportsDocumentPip() && !isMeetingPipOpen());
    }
    if (active) {
      exitBrowserFullscreen();
      focusParticipantsPanel();
    }
  };

  const isMeetingPipOpen = () => Boolean(pipWindow && !pipWindow.closed);

  const copyStylesToPip = (targetDoc) => {
    [...document.styleSheets].forEach((styleSheet) => {
      try {
        const cssRules = [...styleSheet.cssRules].map((rule) => rule.cssText).join("");
        const style = targetDoc.createElement("style");
        style.textContent = cssRules;
        targetDoc.head.appendChild(style);
      } catch (e) {
        const link = targetDoc.createElement("link");
        link.rel = "stylesheet";
        link.type = styleSheet.type || "text/css";
        if (styleSheet.media && styleSheet.media.mediaText) {
          link.media = styleSheet.media.mediaText;
        }
        if (styleSheet.href) {
          link.href = styleSheet.href;
          targetDoc.head.appendChild(link);
        }
      }
    });
  };

  const updateMeetingPipButton = () => {
    if (!toggleMeetingPipBtn) return;
    toggleMeetingPipBtn.hidden = false;
    toggleMeetingPipBtn.classList.toggle("is-on", isMeetingPipOpen());
    toggleMeetingPipBtn.classList.toggle("is-unsupported", !supportsDocumentPip());
    const label = toggleMeetingPipBtn.querySelector(".meeting-pip-btn__text");
    if (label) {
      label.textContent = isMeetingPipOpen() ? "Hiện PiP" : "PiP";
    }
    toggleMeetingPipBtn.title = !supportsDocumentPip()
      ? "Trình duyệt không hỗ trợ Document PiP (cần Chrome/Edge)"
      : isMeetingPipOpen()
        ? "Đưa cửa sổ theo dõi lớp (PiP) lên trên"
        : "Mở danh sách lớp ở cửa sổ PiP (Jitsi giữ nguyên trên trang chính)";
  };

  const restoreSidebarFromPip = () => {
    if (!meetingSidebar) return;

    if (pipSidebarPlaceholder && pipSidebarPlaceholder.parentNode) {
      pipSidebarPlaceholder.replaceWith(meetingSidebar);
    } else if (meetingShell && meetingSidebar.ownerDocument !== document) {
      const body = meetingShell.querySelector(".meeting-body");
      if (body) body.appendChild(meetingSidebar);
    }

    pipSidebarPlaceholder = null;
    pipWindow = null;
    if (meetingShell) meetingShell.classList.remove("is-pip-active");

    if (meetingPipPrompt) {
      meetingPipPrompt.hidden = !(localScreenSharing && supportsDocumentPip());
    }
    updateMeetingPipButton();
  };

  const closeMeetingPip = () => {
    if (!pipWindow || pipWindow.closed) {
      restoreSidebarFromPip();
      return;
    }
    try {
      pipWindow.close();
    } catch (e) {
      restoreSidebarFromPip();
    }
  };

  const focusMeetingPip = () => {
    if (!pipWindow || pipWindow.closed) return false;
    try {
      pipWindow.focus();
      return true;
    } catch (e) {
      return false;
    }
  };

  const openMeetingPip = async () => {
    if (!meetingSidebar) return;
    if (!supportsDocumentPip()) {
      window.alert("Trình duyệt không hỗ trợ Document Picture-in-Picture. Hãy dùng Chrome hoặc Edge mới.");
      return;
    }
    if (isMeetingPipOpen()) {
      focusMeetingPip();
      return;
    }

    try {
      pipWindow = await window.documentPictureInPicture.requestWindow({
        width: 380,
        height: 720,
        preferInitialWindowPlacement: true,
      });
    } catch (err) {
      window.alert(err && err.message ? err.message : "Không mở được cửa sổ PiP.");
      return;
    }

    copyStylesToPip(pipWindow.document);
    pipWindow.document.documentElement.lang = "vi";
    pipWindow.document.documentElement.style.height = "100%";
    pipWindow.document.title = (cfg.roomTitle || "Phòng học") + " · Theo dõi lớp";
    pipWindow.document.body.className = "cr-body page-classroom-room meeting-pip-body meeting-pip-body--sidebar";
    pipWindow.document.body.style.margin = "0";
    pipWindow.document.body.style.height = "100%";
    pipWindow.document.body.style.minHeight = "100%";
    pipWindow.document.body.style.background = "#0b1220";
    pipWindow.document.body.style.overflow = "hidden";

    pipSidebarPlaceholder = document.createElement("div");
    pipSidebarPlaceholder.className = "meeting-sidebar-pip-placeholder";
    pipSidebarPlaceholder.innerHTML =
      '<div class="meeting-sidebar-pip-placeholder__card">' +
      '<i class="fa-solid fa-users" aria-hidden="true"></i>' +
      "<strong>Danh sách lớp đang ở cửa sổ PiP</strong>" +
      "<p>Jitsi vẫn chạy trên trang chính (không đăng nhập lại). Bấm <em>Hiện PiP</em> trên header để đưa cửa sổ lên khi đang mở PowerPoint.</p>" +
      '<button type="button" class="btn btn-sunny btn-sm" id="focusMeetingPipBtn">Hiện cửa sổ PiP</button>' +
      '<button type="button" class="btn btn-ghost btn-sm" id="closeMeetingPipFromMainBtn">Đóng PiP</button>' +
      "</div>";
    meetingSidebar.after(pipSidebarPlaceholder);
    pipWindow.document.body.appendChild(meetingSidebar);
    if (meetingShell) meetingShell.classList.add("is-pip-active");

    const focusBtn = pipSidebarPlaceholder.querySelector("#focusMeetingPipBtn");
    const closeBtn = pipSidebarPlaceholder.querySelector("#closeMeetingPipFromMainBtn");
    if (focusBtn) {
      focusBtn.addEventListener("click", () => focusMeetingPip());
    }
    if (closeBtn) {
      closeBtn.addEventListener("click", () => closeMeetingPip());
    }

    pipWindow.addEventListener("pagehide", () => {
      restoreSidebarFromPip();
    });

    if (meetingPipPrompt) meetingPipPrompt.hidden = true;
    updateMeetingPipButton();
  };

  const toggleMeetingPip = async () => {
    if (isMeetingPipOpen()) {
      focusMeetingPip();
      return;
    }
    await openMeetingPip();
  };

  const attachScreenShareListeners = (api) => {
    if (!api) return;

    api.addListener("screenSharingStatusChanged", (payload) => {
      localScreenSharing = Boolean(payload && payload.on);
      syncScreenShareLayout();
    });

    api.addListener("contentSharingParticipantsChanged", (participants) => {
      remoteScreenSharing = Array.isArray(participants) && participants.length > 0;
      syncScreenShareLayout();
    });
  };

  const cleanup = () => {
    clearInterval(pingTimer);
    clearInterval(participantsTimer);
    clearInterval(breakDurationTimer);
    clearTimeout(breakToastTimer);
    closeMeetingPip();
    if (jitsiApi) {
      try {
        jitsiApi.dispose();
      } catch (e) {
        /* ignore */
      }
    }
  };

  const buildJitsiOptions = (roomName, jwt, breakoutIndex = 0) => {
    const userInfo = { displayName };
    if (userEmail) userInfo.email = userEmail;

    let subject = cfg.roomTitle || "Lớp học online";
    if (breakoutIndex > 0) {
      subject += " — Phòng thảo luận " + breakoutIndex;
    }

    const startAudioMuted = lastAudioMuted !== null
      ? lastAudioMuted
      : (cfg.isModerator ? false : true);

    const options = {
      roomName,
      parentNode: container,
      width: "100%",
      height: "100%",
      userInfo,
      configOverwrite: {
        prejoinPageEnabled: false,
        prejoinConfig: {
          enabled: false,
          hideDisplayName: true,
          hideExtraJoinButtons: true,
        },
        requireDisplayName: false,
        readOnlyName: true,
        enableWelcomePage: false,
        enableClosePage: false,
        disableDeepLinking: true,
        enableInsecureRoomNameWarning: false,
        enableLobby: false,
        disableThirdPartyRequests: true,
        disableProfile: true,
        hideEmailInSettings: true,
        defaultLanguage: "vi",
        subject,
        p2p: {
          enabled: false,
        },
        disableFilmstrip: false,
        disableResponsiveTiles: false,
        enableNoAudioDetection: false,
        enableTalkWhileMuted: false,
        disableAudioLevels: true,
        enableNoisyMicDetection: false,
        enableOpusRed: true,
        websocketKeepAlive: 10000,
        disabledNotifications: [
          "notify.havingTroubleWithMic",
          "notify.noisyMic",
          "notify.talkWhileMuted",
        ],
        audioQuality: {
          stereo: false,
          enableOpusDtx: false,
        },
        resolution: 720,
        constraints: {
          video: {
            height: { ideal: 720, max: 720, min: 240 },
          },
        },
        startWithAudioMuted: startAudioMuted,
        startWithVideoMuted: false,
        analytics: { disabled: true },
        disableFullscreen: false,
        disableScreensharing: false,
        toolbarButtons: buildToolbarButtons(),
      },
      interfaceConfigOverwrite: {
        APP_NAME: "Phòng học SUNNY",
        DISABLE_FILMSTRIP: false,
        DISABLE_FULLSCREEN: false,
        SHOW_JITSI_WATERMARK: false,
        SHOW_WATERMARK_FOR_GUESTS: false,
        SHOW_BRAND_WATERMARK: false,
        MOBILE_APP_PROMO: false,
        SHOW_PROMOTIONAL_CLOSE_PAGE: false,
        HIDE_INVITE_MORE_HEADER: true,
        AUTHENTICATION_ENABLE: false,
        TOOLBAR_BUTTONS: buildToolbarButtons(),
        SETTINGS_SECTIONS: ["devices", "language", "moderator", "sounds", "videoquality"],
        DEFAULT_BACKGROUND: "#1a2332",
      },
    };

    if (jwt) {
      options.jwt = jwt;
    }

    return options;
  };

  const fetchJitsiCredentials = async (breakoutIndex = 0) => {
    if (!cfg.useJaas || !cfg.tokenUrl) {
      let roomName = String(cfg.roomCode || "");
      if (breakoutIndex > 0 && roomName !== "") {
        roomName += "-bo-" + breakoutIndex;
      }

      return {
        ok: true,
        domain: cfg.jitsiDomain || "meet.jit.si",
        roomName,
        jwt: null,
      };
    }

    const res = await fetch(jitsiTokenUrl(breakoutIndex), {
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });
    if (res.redirected || res.status === 401 || res.status === 419) {
      setTimeout(() => window.location.reload(), 1500);
      throw new Error("Phiên đăng nhập hết hạn. Đang tải lại trang...");
    }
    if (!res.ok) {
      throw new Error("Không lấy được token phòng video.");
    }
    const data = await res.json();

    if (!data.ok) {
      throw new Error(data.message || "Không lấy được token phòng video.");
    }

    return data;
  };

  const mountJitsi = (domain, roomName, jwt, breakoutIndex = 0) => {
    if (!container || typeof JitsiMeetExternalAPI === "undefined") {
      return;
    }

    jitsiDomain = domain || jitsiDomain;
    jitsiApi = new JitsiMeetExternalAPI(jitsiDomain, buildJitsiOptions(roomName, jwt, breakoutIndex));
    attachScreenShareListeners(jitsiApi);
    ensureJitsiIframePermissions();

    if (container && typeof MutationObserver !== "undefined") {
      const iframeObserver = new MutationObserver(() => ensureJitsiIframePermissions());
      iframeObserver.observe(container, { childList: true, subtree: true });
    }

    jitsiApi.addListener("videoConferenceJoined", () => {
      const wasRemount = jitsiRemountAttempts > 0;
      ignoreJitsiClose = false;
      jitsiRemountAttempts = 0;
      ensureJitsiIframePermissions();
      try {
        jitsiApi.executeCommand("displayName", displayName);
      } catch (e) {
        /* ignore */
      }
      if (wasRemount) {
        unlockClassroomAudio();
        showBreakToast("Đã kết nối lại phòng học. Nếu không nghe thấy tiếng, hãy kiểm tra micro hoặc chạm vào màn hình.", "info");
      }
      pingPresence();
      fetchParticipants();
    });

    jitsiApi.addListener("audioMuteStatusChanged", (payload) => {
      lastAudioMuted = Boolean(payload && payload.muted);
    });

    let audioUnavailableTimer = null;
    let lastMicErrorTime = 0;

    jitsiApi.addListener("micError", (err) => {
      console.warn("[Jitsi Mic Warning]", err);
      const now = Date.now();
      if (now - lastMicErrorTime < 10000) return;
      lastMicErrorTime = now;
      const errStr = String((err && (err.message || err.name || err)) || "");
      if (errStr.includes("NotFoundError") || errStr.includes("DevicesNotFoundError")) {
        showBreakToast("Không tìm thấy micro. Hãy kiểm tra lại thiết bị thu âm.", "error");
      } else if (errStr.includes("NotAllowedError") || errStr.includes("PermissionDeniedError")) {
        showBreakToast("Chưa cấp quyền truy cập micro cho trình duyệt. Vui lòng cho phép trên thanh địa chỉ.", "error");
      }
    });

    jitsiApi.addListener("cameraError", (err) => {
      console.error("[Jitsi Camera Error]", err);
      showBreakToast("Không truy cập được camera. Hãy kiểm tra quyền thiết bị trên trình duyệt.", "error");
    });

    jitsiApi.addListener("audioAvailabilityChanged", (payload) => {
      if (payload && payload.available === false) {
        if (!audioUnavailableTimer) {
          audioUnavailableTimer = setTimeout(async () => {
            audioUnavailableTimer = null;
            try {
              if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
                const devs = await navigator.mediaDevices.enumerateDevices();
                const hasMic = devs.some((d) => d.kind === "audioinput");
                if (hasMic) return;
              }
            } catch (e) {
              /* ignore */
            }
            showBreakToast("Không tìm thấy thiết bị micro nào được kết nối.", "error");
          }, 3500);
        }
      } else {
        if (audioUnavailableTimer) {
          clearTimeout(audioUnavailableTimer);
          audioUnavailableTimer = null;
        }
      }
    });

    jitsiApi.addListener("readyToClose", () => {
      if (switchingJitsi || ignoreJitsiClose) {
        return;
      }
      recoverJitsiCall();
    });
  };

  const recoverJitsiCall = () => {
    if (switchingJitsi || ignoreJitsiClose) {
      return;
    }
    if (jitsiRemountAttempts >= 2) {
      showBreakToast("Cuộc gọi đã đóng. Dùng Rời phòng nếu muốn thoát lớp.", "error");
      return;
    }
    jitsiRemountAttempts += 1;
    switchJitsiRoom(currentJitsiBreakoutIndex, { silent: true });
  };

  const switchJitsiRoom = async (breakoutIndex = 0, options = {}) => {
    if (switchingJitsi) return;
    switchingJitsi = true;
    ignoreJitsiClose = true;

    const targetIndex = Math.max(0, Number(breakoutIndex || 0));
    const silent = Boolean(options.silent);

    try {
      if (jitsiApi) {
        try {
          jitsiApi.dispose();
        } catch (e) {
          /* ignore */
        }
        jitsiApi = null;
      }

      if (container) {
        container.innerHTML = "";
      }

      const data = await fetchJitsiCredentials(targetIndex);
      currentJitsiBreakoutIndex = targetIndex;
      mountJitsi(data.domain || "8x8.vc", data.roomName, data.jwt || null, targetIndex);

      if (!silent) {
        if (targetIndex > 0) {
          showBreakToast("Đã vào phòng thảo luận " + targetIndex + " — bạn có thể trò chuyện với nhóm.", "info");
        } else if (lastBreakoutSession && lastBreakoutSession.active === false) {
          showBreakToast("Đã quay về phòng chính.", "success");
        }
      }
    } catch (e) {
      ignoreJitsiClose = false;
      showBreakToast(e.message || "Không chuyển được phòng video.", "error");
    } finally {
      switchingJitsi = false;
      pingPresence();
    }
  };

  const initJitsi = async () => {
    if (!container || typeof JitsiMeetExternalAPI === "undefined") {
      if (container) {
        container.innerHTML =
          '<div class="p-4 text-white"><p>Không tải được Jitsi. Các tính năng khác vẫn hoạt động.</p></div>';
      }
      return;
    }

    let domain = cfg.jitsiDomain || "8x8.vc";
    let roomName = cfg.roomCode;
    let jwt = null;

    if (cfg.useJaas) {
      if (!cfg.tokenUrl) {
        container.innerHTML =
          '<div class="p-4 text-white"><p>JaaS chưa cấu hình token URL.</p></div>';
        return;
      }

      try {
        const data = await fetchJitsiCredentials(Math.max(0, Number(cfg.initialBreakout || 0)));
        if (!data.ok) {
          const warn = Array.isArray(data.jaasWarnings) && data.jaasWarnings.length
            ? "<ul class=\"meeting-jitsi-setup-list\">" +
              data.jaasWarnings.map((item) => "<li>" + String(item).replace(/</g, "&lt;") + "</li>").join("") +
              "</ul>"
            : "";
          container.innerHTML =
            '<div class="p-4 text-white"><p><strong>Không vào được phòng JaaS</strong></p><p>' +
            (data.message || "Thiếu private.pk trong writable/jaas/") +
            "</p>" + warn +
            '<p class="small">Kiểm tra <code>writable/jaas/private.pk</code>, <code>classroom.jaasKid</code> và public key trên JaaS Console phải cùng một cặp API key.</p></div>';
          return;
        }
        if (Array.isArray(data.jaasWarnings) && data.jaasWarnings.length) {
          console.warn("[JaaS setup warnings]", data.jaasWarnings);
        }
        domain = data.domain || "8x8.vc";
        roomName = data.roomName || roomName;
        jwt = data.jwt;
      } catch (e) {
        container.innerHTML =
          '<div class="p-4 text-white"><p>Lỗi kết nối JaaS. Kiểm tra file private.pk và .env</p></div>';
        return;
      }
    } else if (domain === "meet.jit.si") {
      const issues = Array.isArray(cfg.jaasSetupIssues) ? cfg.jaasSetupIssues : [];
      const issuesHtml = issues.length
        ? "<ul class=\"meeting-jitsi-setup-list\">" +
          issues.map((item) => "<li>" + String(item).replace(/</g, "&lt;") + "</li>").join("") +
          "</ul>"
        : "<p class=\"small\">Đặt file <code>writable/jaas/private.pk</code> trên server và bật <code>classroom.jaasEnabled = true</code> trong .env.</p>";
      container.innerHTML =
        '<div class="p-4 text-white meeting-jitsi-setup-error">' +
        "<p><strong>Chưa vào được phòng video JaaS (8x8)</strong></p>" +
        issuesHtml +
        "<p class=\"small\">Tải private key tại <a href=\"https://developer.8x8.com/jaas/docs/jaas-console-api-keys\" target=\"_blank\" rel=\"noopener\" style=\"color:#fde68a\">JaaS Console → API Keys</a>. File <code>private.pk</code> không được đưa lên git — phải upload thủ công lên host.</p></div>";
      return;
    }

    currentJitsiBreakoutIndex = Math.max(0, Number(cfg.initialBreakout || 0));
    mountJitsi(domain, roomName, jwt, currentJitsiBreakoutIndex);
  };

  leaveForm?.addEventListener("submit", () => {
    cleanup();
  });

  toggleMeetingPipBtn?.addEventListener("click", () => {
    toggleMeetingPip();
  });
  meetingPipPromptOpenBtn?.addEventListener("click", () => {
    openMeetingPip();
  });
  updateMeetingPipButton();

  breakOutBtn?.addEventListener("click", requestBreakOut);
  breakReturnBtn?.addEventListener("click", returnFromBreak);
  openBreakoutModalBtn?.addEventListener("click", openBreakoutModal);
  closeBreakoutModalBtn?.addEventListener("click", closeBreakoutModal);
  cancelBreakoutModalBtn?.addEventListener("click", closeBreakoutModal);
  breakoutAssignModeInputs.forEach((input) => {
    input.addEventListener("change", () => {
      updateBreakoutModeUi();
      renderManualAssignList();
    });
  });
  breakoutRoomCountInput?.addEventListener("change", renderManualAssignList);
  breakoutRoomCountInput?.addEventListener("input", renderManualAssignList);
  startBreakoutBtn?.addEventListener("click", startBreakoutSession);
  endBreakoutBtn?.addEventListener("click", endBreakoutSession);
  endBreakoutBarBtn?.addEventListener("click", endBreakoutSession);
  breakoutModalOverlay?.addEventListener("click", (event) => {
    if (event.target === breakoutModalOverlay) closeBreakoutModal();
  });

  window.addEventListener("beforeunload", cleanup);

  refreshBtn?.addEventListener("click", fetchParticipants);

  window.classroomStartPresencePing = () => {
    if (pingTimer) return;
    pingPresence();
    const baseInterval = cfg.presenceInterval || 20000;
    const interval = cfg.isModerator ? Math.min(baseInterval, 15000) : baseInterval;
    pingTimer = setInterval(pingPresence, interval);
  };

  const bindPresenceKeepAlive = () => {
    const refreshPresence = () => {
      if (document.visibilityState && document.visibilityState !== "visible") {
        return;
      }
      pingPresence();
    };

    document.addEventListener("visibilitychange", refreshPresence);
    window.addEventListener("focus", refreshPresence);
    window.addEventListener("online", refreshPresence);
  };

  const unlockClassroomAudio = () => {
    try {
      if (container) {
        const audios = container.querySelectorAll("audio");
        audios.forEach((a) => {
          if (a.paused && !a.muted) {
            a.play().catch(() => {});
          }
        });
      }
    } catch (e) {
      /* ignore */
    }
  };

  ["click", "touchstart", "keydown"].forEach((evt) => {
    document.addEventListener(evt, unlockClassroomAudio, { passive: true });
  });
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      unlockClassroomAudio();
    }
  });

  initJitsi();
  setupRoomMetaCopy();
  bindPresenceKeepAlive();
  window.classroomStartPresencePing();
  fetchParticipants();
  participantsTimer = setInterval(fetchParticipants, cfg.waitingRoomEnabled && cfg.isModerator ? 4000 : 8000);
})();
