(() => {
  const cfg = window.classroomRoomConfig;
  if (!cfg) return;

  const lobby = document.getElementById("pwaLobby");
  const meet = document.getElementById("pwaMeet");
  const enterBtn = document.getElementById("pwaEnterRoomBtn");
  const container = document.getElementById("jitsiContainer");
  const leaveForm = document.getElementById("leaveForm");
  const toast = document.getElementById("meetingBreakToast");
  const overlay = document.getElementById("studentPollOverlay");
  const pollTitle = document.getElementById("studentPollTitle");
  const pollTag = document.getElementById("studentPollTypeTag");
  const pollBody = document.getElementById("studentPollBody");
  const submitBtn = document.getElementById("submitPollAnswerBtn");
  const countdownWrap = document.getElementById("studentPollCountdown");
  const countdownText = document.getElementById("studentPollCountdownText");
  const iosVideoHint = document.getElementById("pwaIosVideoHint");
  const iosVideoHintDismiss = document.getElementById("pwaIosVideoHintDismiss");

  let jitsiApi = null;
  let currentBreakout = 0;
  let switching = false;
  let ignoreClose = false;
  let remountAttempts = 0;
  let pendingPoll = null;
  let shownPollId = null;
  let countdownTimer = null;
  let countdownLeft = 0;
  let toastTimer = null;
  let resizeTimer = null;
  let tileViewRetries = 0;
  let lastAudioMuted = null;

  const displayName = String(cfg.jitsiDisplayName || cfg.userName || "Học viên").trim();
  let inCall = false;

  const isIOSLike = () => {
    const ua = navigator.userAgent || "";
    const isIPadOS = navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1;
    return /iPad|iPhone|iPod/i.test(ua) || isIPadOS;
  };

  const isStandalonePwa = () => {
    try {
      if (window.matchMedia && window.matchMedia("(display-mode: standalone)").matches) {
        return true;
      }
    } catch (e) {
      /* ignore */
    }
    return Boolean(navigator.standalone);
  };

  const preferStableMobileVideo = isIOSLike();

  if (preferStableMobileVideo) {
    document.documentElement.classList.add("is-ios-device");
  }
  if (preferStableMobileVideo && isStandalonePwa()) {
    document.documentElement.classList.add("is-ios-pwa");
  }

  const jitsiClientId = (() => {
    const kind = String(cfg.jitsiClientKind || "pwa").replace(/[^a-z0-9]/gi, "") || "pwa";
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

  const showToast = (message) => {
    if (!toast || !message) return;
    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.hidden = true;
    }, 4000);
  };

  const escapeHtml = (text) =>
    String(text || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  const plain = (value) => {
    const div = document.createElement("div");
    div.innerHTML = String(value || "");
    return (div.textContent || "").replace(/\s+/g, " ").trim();
  };

  const resizeJitsi = () => {
    if (!jitsiApi || !container) return;
    const width = Math.max(1, container.clientWidth || window.innerWidth || 1);
    const height = Math.max(1, container.clientHeight || window.innerHeight || 1);
    try {
      jitsiApi.resize(width, height);
    } catch (e) {
      /* ignore */
    }
    const iframe = container.querySelector("iframe");
    if (iframe) {
      iframe.style.width = "100%";
      iframe.style.height = "100%";
      iframe.style.minHeight = "100%";
      iframe.style.border = "0";
    }
  };

  const scheduleResizeJitsi = () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      resizeJitsi();
      setTimeout(resizeJitsi, 300);
      setTimeout(resizeJitsi, 800);
    }, 50);
  };

  const ensureIframe = () => {
    const iframe = container?.querySelector("iframe");
    if (!iframe) return;
    iframe.setAttribute(
      "allow",
      "camera *; microphone *; display-capture *; autoplay *; clipboard-write *; fullscreen *; screen-wake-lock *; speaker-selection *"
    );
    iframe.setAttribute("playsinline", "true");
    iframe.setAttribute("webkit-playsinline", "true");
    iframe.style.width = "100%";
    iframe.style.height = "100%";
  };

  const forceTileView = () => {
    if (!jitsiApi) return;
    try {
      jitsiApi.executeCommand("setTileView", true);
    } catch (e) {
      /* ignore */
    }
  };

  const applyStableVideoCommands = () => {
    forceTileView();
    tileViewRetries = 0;
    const retry = () => {
      tileViewRetries += 1;
      forceTileView();
      resizeJitsi();
      if (tileViewRetries < 4) {
        setTimeout(retry, 700 * tileViewRetries);
      }
    };
    setTimeout(retry, 400);
  };

  const showIosVideoHint = () => {
    if (!preferStableMobileVideo || !iosVideoHint) return;
    try {
      if (sessionStorage.getItem("sunny-pwa-ios-video-hint") === "1") return;
    } catch (e) {
      /* ignore */
    }
    iosVideoHint.hidden = false;
  };

  const hideIosVideoHint = () => {
    if (iosVideoHint) iosVideoHint.hidden = true;
    try {
      sessionStorage.setItem("sunny-pwa-ios-video-hint", "1");
    } catch (e) {
      /* ignore */
    }
  };

  const fetchToken = async (breakoutIndex = 0) => {
    if (!cfg.useJaas || !cfg.tokenUrl) {
      let roomName = String(cfg.roomCode || "");
      if (breakoutIndex > 0 && roomName) roomName += "-bo-" + breakoutIndex;
      return { ok: true, domain: cfg.jitsiDomain || "meet.jit.si", roomName, jwt: null };
    }

    const params = new URLSearchParams();
    if (breakoutIndex > 0) params.set("breakout", String(breakoutIndex));
    params.set("client", jitsiClientId);
    const res = await fetch(cfg.tokenUrl + "?" + params.toString(), {
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });
    if (res.redirected || res.status === 401 || res.status === 419) {
      setTimeout(() => window.location.reload(), 1200);
      return { ok: false, message: "Phiên đăng nhập hết hạn. Đang tải lại..." };
    }
    if (!res.ok) {
      return { ok: false, message: "Không lấy được token phòng video." };
    }
    return res.json();
  };

  const mountJitsi = (domain, roomName, jwt) => {
    if (!container || typeof JitsiMeetExternalAPI === "undefined") {
      window.pwaLoading?.hide();
      showToast("Không tải được cuộc gọi");
      return;
    }

    const userInfo = { displayName };
    if (cfg.userEmail) userInfo.email = cfg.userEmail;

    const startVideoMuted = preferStableMobileVideo;
    const disableFilmstrip = false;
    const startAudioMuted = lastAudioMuted !== null
      ? lastAudioMuted
      : (cfg.isModerator ? false : true);

    jitsiApi = new JitsiMeetExternalAPI(domain || cfg.jitsiDomain || "8x8.vc", {
      roomName,
      parentNode: container,
      width: "100%",
      height: "100%",
      jwt: jwt || undefined,
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
        hideConferenceSubject: true,
        defaultLanguage: "vi",
        subject: "Lớp học",
        p2p: {
          enabled: false,
        },
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
        startWithAudioMuted: startAudioMuted,
        startWithVideoMuted: startVideoMuted,
        analytics: { disabled: true },
        disableFilmstrip: disableFilmstrip,
        disableResponsiveTiles: false,
        startWithTileView: preferStableMobileVideo,
        toolbarButtons: ["microphone", "camera", "chat", "raisehand", "tileview", "hangup"],
      },
      interfaceConfigOverwrite: {
        APP_NAME: "SUNNY",
        SHOW_JITSI_WATERMARK: false,
        SHOW_WATERMARK_FOR_GUESTS: false,
        SHOW_BRAND_WATERMARK: false,
        MOBILE_APP_PROMO: false,
        SHOW_PROMOTIONAL_CLOSE_PAGE: false,
        HIDE_INVITE_MORE_HEADER: true,
        AUTHENTICATION_ENABLE: false,
        DEFAULT_BACKGROUND: "#101820",
        TILE_VIEW_MAX_COLUMNS: preferStableMobileVideo ? 2 : 5,
      },
    });

    ensureIframe();
    scheduleResizeJitsi();

    if (container && typeof MutationObserver !== "undefined") {
      const iframeObserver = new MutationObserver(() => ensureIframe());
      iframeObserver.observe(container, { childList: true, subtree: true });
    }

    jitsiApi.addListener("videoConferenceJoined", () => {
      const wasRemount = remountAttempts > 0;
      ignoreClose = false;
      remountAttempts = 0;
      window.pwaLoading?.hide();
      ensureIframe();
      scheduleResizeJitsi();
      applyStableVideoCommands();
      showIosVideoHint();
      try {
        jitsiApi.executeCommand("displayName", displayName);
        jitsiApi.executeCommand("subject", " ");
      } catch (e) {
        /* ignore */
      }
      if (wasRemount) {
        showToast("Đã kết nối lại phòng học. Nếu không nghe thấy tiếng, hãy kiểm tra micro hoặc chạm vào màn hình.");
      } else if (preferStableMobileVideo) {
        showToast("Nếu hình đen: bấm Tile view hoặc bật Camera. Giữ app ở màn hình chính.");
      }
    });

    jitsiApi.addListener("audioMuteStatusChanged", (payload) => {
      lastAudioMuted = Boolean(payload && payload.muted);
    });

    let lastMicErrorTime = 0;
    jitsiApi.addListener("micError", (err) => {
      console.warn("[PWA Jitsi Mic Warning]", err);
      const now = Date.now();
      if (now - lastMicErrorTime < 10000) return;
      lastMicErrorTime = now;
      const errStr = String((err && (err.message || err.name || err)) || "");
      if (errStr.includes("NotFoundError") || errStr.includes("DevicesNotFoundError")) {
        showToast("Không tìm thấy micro. Vui lòng kiểm tra lại thiết bị thu âm.");
      } else if (errStr.includes("NotAllowedError") || errStr.includes("PermissionDeniedError")) {
        showToast("Trình duyệt chưa được cấp quyền micro. Vui lòng bật quyền mic.");
      }
    });

    jitsiApi.addListener("cameraError", (err) => {
      console.error("[PWA Jitsi Camera Error]", err);
      showToast("Không truy cập được camera. Hãy kiểm tra quyền thiết bị.");
    });

    jitsiApi.addListener("readyToClose", () => {
      if (switching || ignoreClose) return;
      if (remountAttempts >= 2) {
        showToast("Cuộc gọi đã đóng. Dùng Rời phòng nếu muốn thoát lớp.");
        return;
      }
      remountAttempts += 1;
      switchRoom(currentBreakout, { silent: true });
    });
  };

  const switchRoom = async (breakoutIndex, options = {}) => {
    if (switching) return;
    switching = true;
    ignoreClose = true;
    const target = Math.max(0, Number(breakoutIndex || 0));
    const fromCall = Boolean(jitsiApi);
    const silent = Boolean(options.silent);
    window.pwaLoading?.show(fromCall ? "Đang chuyển phòng…" : "Đang vào phòng…");

    try {
      if (jitsiApi) {
        try {
          jitsiApi.dispose();
        } catch (e) {
          /* ignore */
        }
        jitsiApi = null;
      }
      if (container) container.innerHTML = "";
      const data = await fetchToken(target);
      if (!data.ok) throw new Error(data.message || "Không vào được phòng video.");
      currentBreakout = target;
      mountJitsi(data.domain, data.roomName, data.jwt || null);
      setTimeout(() => window.pwaLoading?.hide(), 15000);
      if (fromCall && !silent) {
        showToast(target > 0 ? "Đang chuyển sang phòng thảo luận " + target : "Đang quay về phòng chính");
      }
    } catch (e) {
      ignoreClose = false;
      window.pwaLoading?.hide();
      showToast(e.message || "Không chuyển được phòng.");
    } finally {
      switching = false;
    }
  };

  const ping = () => {
    if (!cfg.pingUrl || !window.classroomPostForm) return;
    Promise.resolve(window.classroomPostForm(cfg.pingUrl, {}))
      .then((res) => {
        if (res && (res.status === 401 || res.status === 419)) {
          showToast("Phiên đăng nhập hết hạn. Đang tải lại...");
          setTimeout(() => window.location.reload(), 1200);
        }
      })
      .catch(() => {});
  };

  const lockLandscape = async () => {
    if (preferStableMobileVideo) return;

    try {
      if (screen.orientation?.lock) {
        await screen.orientation.lock("landscape");
      }
    } catch (e) {
      /* iOS / desktop: lock may be denied */
    }

    const target = meet || document.documentElement;
    try {
      if (document.fullscreenElement !== target) {
        if (target.requestFullscreen) {
          await target.requestFullscreen();
        } else if (target.webkitRequestFullscreen) {
          target.webkitRequestFullscreen();
        }
      }
    } catch (e) {
      /* ignore */
    }
  };

  const syncBreakout = (session) => {
    const active = Boolean(session && session.active);
    const myRoom = Math.max(0, Number(session?.myBreakoutRoom || 0));
    const target = active ? myRoom : 0;

    if (target !== currentBreakout) {
      switchRoom(target);
    }
  };

  const fetchParticipants = async () => {
    if (!cfg.participantsUrl) return;
    try {
      const res = await fetch(cfg.participantsUrl, { credentials: "same-origin" });
      const data = await res.json();
      if (!data.ok) return;
      syncBreakout(data.breakoutSession || { active: false, myBreakoutRoom: 0 });
    } catch (e) {
      /* ignore */
    }
  };

  const hidePoll = () => {
    if (countdownTimer) {
      clearInterval(countdownTimer);
      countdownTimer = null;
    }
    if (overlay) overlay.hidden = true;
    pendingPoll = null;
  };

  const formatClock = (seconds) => {
    const safe = Math.max(0, seconds);
    return String(Math.floor(safe / 60)).padStart(2, "0") + ":" + String(safe % 60).padStart(2, "0");
  };

  const startCountdown = (seconds) => {
    countdownLeft = seconds;
    if (countdownWrap) countdownWrap.hidden = false;
    if (countdownText) countdownText.textContent = formatClock(countdownLeft);
    countdownTimer = setInterval(() => {
      countdownLeft -= 1;
      if (countdownText) countdownText.textContent = formatClock(countdownLeft);
      if (countdownLeft <= 0) hidePoll();
    }, 1000);
  };

  const showPoll = (poll) => {
    pendingPoll = poll;
    shownPollId = Number(poll.id || 0);
    if (pollTag) pollTag.textContent = poll.type === "choice" ? "Trắc nghiệm" : "Điền từ";
    if (pollTitle) pollTitle.textContent = plain(poll.question) || "Câu hỏi từ giáo viên";
    if (pollBody) {
      if (poll.type === "choice") {
        const options = Array.isArray(poll.options) ? poll.options : [];
        pollBody.innerHTML = options
          .map((opt, i) => {
            const text = plain(opt);
            return (
              '<label class="pwa-choice"><input type="radio" name="studentPollAnswer" value="' +
              escapeHtml(text) +
              '"><span>' +
              String.fromCharCode(65 + i) +
              ". " +
              escapeHtml(text) +
              "</span></label>"
            );
          })
          .join("");
      } else {
        pollBody.innerHTML = '<input class="pwa-fill" type="text" id="studentPollFill" placeholder="Nhập câu trả lời">';
      }
    }
    if (overlay) overlay.hidden = false;
    const createdMs = Date.parse(String(poll.createdAt || "").replace(" ", "T"));
    const duration = Number(poll.durationSeconds || (poll.durationMinutes || 1) * 60) || 60;
    const left = Number.isFinite(createdMs)
      ? Math.max(0, duration - Math.floor((Date.now() - createdMs) / 1000))
      : duration;
    startCountdown(left);
  };

  const fetchPolls = async () => {
    if (!cfg.pollFeedUrl) return;
    try {
      const res = await fetch(cfg.pollFeedUrl, { credentials: "same-origin" });
      const data = await res.json();
      if (!data.ok) return;
      const poll = data.pendingPoll || null;
      const pollId = Number(poll?.id || 0);
      if (poll && pollId > 0 && pollId !== shownPollId) {
        showPoll(poll);
      }
      if (!poll && overlay && !overlay.hidden && !pendingPoll) {
        hidePoll();
      }
    } catch (e) {
      /* ignore */
    }
  };

  const submitPoll = async () => {
    if (!pendingPoll || !cfg.pollBaseUrl) return;
    let answer = "";
    if (pendingPoll.type === "choice") {
      const selected = document.querySelector('input[name="studentPollAnswer"]:checked');
      answer = selected ? selected.value : "";
    } else {
      answer = String(document.getElementById("studentPollFill")?.value || "").trim();
    }
    if (!answer) {
      showToast("Hãy chọn hoặc nhập câu trả lời.");
      return;
    }
    window.pwaLoading?.show("Đang gửi câu trả lời…");
    const data = await window.classroomPostForm(cfg.pollBaseUrl + "/" + pendingPoll.id + "/tra-loi", { answer });
    window.pwaLoading?.hide();
    if (data.ok) {
      showToast("Đã gửi câu trả lời.");
      hidePoll();
    } else {
      showToast(data.message || "Không gửi được câu trả lời.");
    }
  };

  if (submitBtn) submitBtn.addEventListener("click", submitPoll);
  iosVideoHintDismiss?.addEventListener("click", hideIosVideoHint);

  window.addEventListener("resize", scheduleResizeJitsi);
  window.addEventListener("orientationchange", () => {
    if (!inCall) return;
    if (!preferStableMobileVideo && screen.orientation?.lock) {
      screen.orientation.lock("landscape").catch(() => {});
    }
    scheduleResizeJitsi();
  });
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible" && inCall) {
      scheduleResizeJitsi();
      forceTileView();
    }
  });

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

  const startCall = async () => {
    if (inCall) return;
    inCall = true;
    if (enterBtn) {
      enterBtn.disabled = true;
      enterBtn.textContent = "Đang vào phòng…";
    }

    if (lobby) lobby.hidden = true;
    if (meet) meet.hidden = false;
    document.body.classList.remove("is-lobby");
    document.body.classList.add("is-room");
    await lockLandscape();

    await switchRoom(Math.max(0, Number(cfg.initialBreakout || 0)));
    ping();
    fetchParticipants();
    fetchPolls();
    setInterval(ping, Number(cfg.presenceInterval || 20000));
    setInterval(fetchParticipants, 4000);
    setInterval(fetchPolls, 4000);

    if (navigator.wakeLock?.request) {
      try {
        await navigator.wakeLock.request("screen");
      } catch (e) {
        /* ignore */
      }
    }
  };

  if (enterBtn) {
    enterBtn.addEventListener("click", startCall);
  } else {
    startCall();
  }
})();
