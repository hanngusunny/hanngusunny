(() => {
  const formatTime = (seconds) => {
    if (!Number.isFinite(seconds) || seconds < 0) return "0:00";
    const total = Math.floor(seconds);
    const mins = Math.floor(total / 60);
    const secs = total % 60;
    return `${mins}:${String(secs).padStart(2, "0")}`;
  };

  const buildWaveform = (container) => {
    if (!container || container.childElementCount > 0) return;

    const heights = [38, 52, 44, 68, 56, 72, 48, 64, 42, 58, 76, 50, 62, 40, 70, 54, 66, 46, 60, 74, 52, 68, 44, 58, 72, 48, 64, 56];

    heights.forEach((height, index) => {
      const bar = document.createElement("span");
      bar.className = "session-phat-am-waveform-bar";
      bar.style.setProperty("--bar-height", `${height}%`);
      bar.style.setProperty("--bar-delay", `${(index % 7) * 0.08}s`);
      container.appendChild(bar);
    });
  };

  const initPhatAmPlayer = (root) => {
    if (!root || root.dataset.phatAmPlayerReady === "1") return null;

    const playback = root.querySelector("[data-phat-am-playback]");
    const playBtn = root.querySelector("[data-phat-am-play]");
    const waveform = root.querySelector("[data-phat-am-waveform]");
    const progressFill = root.querySelector("[data-phat-am-progress-fill]");
    const seekInput = root.querySelector("[data-phat-am-seek]");
    const currentEl = root.querySelector("[data-phat-am-current]");
    const durationEl = root.querySelector("[data-phat-am-duration]");

    if (!playback || !playBtn) return null;

    buildWaveform(waveform);
    root.dataset.phatAmPlayerReady = "1";

    /** @type {"idle"|"playing"|"paused"|"error"} */
    let playbackState = "idle";

    const updateProgressUi = (current, total) => {
      const safeTotal = Number.isFinite(total) && total > 0 ? total : 0;
      const safeCurrent = Number.isFinite(current) && current > 0 ? Math.min(current, safeTotal || current) : 0;
      const pct = safeTotal > 0 ? (safeCurrent / safeTotal) * 100 : 0;

      if (progressFill) progressFill.style.width = `${pct}%`;
      if (seekInput) seekInput.value = String(pct);
      if (currentEl) currentEl.textContent = formatTime(safeCurrent);
      if (durationEl) durationEl.textContent = safeTotal > 0 ? formatTime(safeTotal) : "0:00";
    };

    const readableDuration = () => {
      const duration = Number(playback.duration);
      return Number.isFinite(duration) && duration > 0 ? duration : 0;
    };

    const resolveWebmDuration = () => {
      if (readableDuration() > 0 || !playback.src) {
        updateProgressUi(playback.currentTime || 0, readableDuration());
        return;
      }

      const onSeeked = () => {
        playback.removeEventListener("seeked", onSeeked);
        const found = readableDuration();
        if (found > 0) {
          const restore = playback.currentTime;
          playback.currentTime = 0;
          updateProgressUi(0, found);
          if (restore > 0 && restore < found) {
            playback.currentTime = restore;
          }
          return;
        }
        updateProgressUi(playback.currentTime || 0, 0);
      };

      try {
        playback.currentTime = 1e101;
        playback.addEventListener("seeked", onSeeked);
      } catch {
        updateProgressUi(playback.currentTime || 0, 0);
      }
    };

    const setPlaybackState = (state) => {
      playbackState = state;
      root.classList.toggle("is-playing", state === "playing");
      root.classList.toggle("is-paused", state === "paused");
      root.classList.toggle("is-error", state === "error");
      const iconClass = root.closest(".tg-page") ? "fa-solid" : "bi";
      if (state === "playing") {
        playBtn.innerHTML =
          iconClass === "fa-solid"
            ? '<i class="fa-solid fa-pause" aria-hidden="true"></i>'
            : '<i class="bi bi-pause-fill" aria-hidden="true"></i>';
      } else {
        playBtn.innerHTML =
          iconClass === "fa-solid"
            ? '<i class="fa-solid fa-play" aria-hidden="true"></i>'
            : '<i class="bi bi-play-fill" aria-hidden="true"></i>';
      }
    };

    const onDone = () => {
      const duration = readableDuration();
      if (duration > 0) {
        updateProgressUi(duration, duration);
      }
      setPlaybackState("idle");
    };

    const pausePlayback = () => {
      playback.pause();
      setPlaybackState("paused");
    };

    const resumePlayback = () => {
      playback.play().catch(() => setPlaybackState("error"));
      setPlaybackState("playing");
    };

    const startFromBeginning = () => {
      playback.pause();
      playback.currentTime = 0;
      updateProgressUi(0, readableDuration());
      playback.play().catch(() => setPlaybackState("error"));
      setPlaybackState("playing");
    };

    playback.addEventListener("loadedmetadata", resolveWebmDuration);
    playback.addEventListener("durationchange", () => {
      const duration = readableDuration();
      if (duration > 0) {
        updateProgressUi(playback.currentTime || 0, duration);
      }
    });
    playback.addEventListener("loadeddata", resolveWebmDuration);

    playback.addEventListener("timeupdate", () => {
      if (playbackState !== "playing") return;
      updateProgressUi(playback.currentTime, readableDuration());
    });

    playback.addEventListener("ended", onDone);

    playback.addEventListener("pause", () => {
      if (playback.ended || playbackState !== "playing") return;
      setPlaybackState("paused");
    });

    playback.addEventListener("error", () => {
      setPlaybackState("error");
      if (durationEl) durationEl.textContent = "—";
      if (currentEl) currentEl.textContent = "Lỗi";
      root.title = "Không phát được bản ghi. File có thể bị thiếu hoặc hỏng.";
    });

    seekInput?.addEventListener("input", () => {
      const duration = readableDuration();
      if (duration <= 0) return;
      const pct = Number(seekInput.value) / 100;
      playback.currentTime = pct * duration;
      updateProgressUi(playback.currentTime, duration);
    });

    playBtn.addEventListener("click", () => {
      if (playbackState === "playing") {
        pausePlayback();
        return;
      }

      if (playbackState === "paused") {
        resumePlayback();
        return;
      }

      if (playback.error) {
        setPlaybackState("error");
        return;
      }

      startFromBeginning();
    });

    updateProgressUi(0, 0);
    if (playback.readyState >= 1) {
      resolveWebmDuration();
    }

    return {
      reset() {
        playback.pause();
        playback.removeAttribute("src");
        playback.load();
        setPlaybackState("idle");
        updateProgressUi(0, 0);
      },
      setSource(url) {
        playback.src = url;
        playback.load();
        updateProgressUi(0, 0);
        setPlaybackState("idle");
      },
      pause() {
        if (playbackState === "playing") pausePlayback();
      },
    };
  };

  document.querySelectorAll("[data-session-phat-am-player]").forEach((player) => {
    initPhatAmPlayer(player);
  });

  const boards = document.querySelectorAll("[data-session-phat-am-board]");

  boards.forEach((board) => {
    const completeInput = board.querySelector("[data-phat-am-complete-input]");
    const recordBtn = board.querySelector("[data-phat-am-record]");
    const stopBtn = board.querySelector("[data-phat-am-stop]");
    const rerecordBtn = board.querySelector("[data-phat-am-rerecord]");
    const playerWrap = board.querySelector("[data-phat-am-player-wrap]");
    const status = board.querySelector("[data-phat-am-status]");
    const uploadUrl = board.dataset.phatAmUploadUrl || "";
    const csrfName = board.dataset.csrfName || "";
    const csrfHash = board.dataset.csrfHash || "";
    const valueMode = board.dataset.phatAmValueMode || "json";
    const requireSubmit = board.dataset.phatAmRequireSubmit !== "0";
    const existingUrl = board.dataset.phatAmExistingUrl || "";

    const idleTitle = board.dataset.phatAmIdleTitle || "Sẵn sàng ghi âm";
    const idleHint = board.dataset.phatAmIdleHint || (
      requireSubmit
        ? "Bấm Ghi âm rồi đọc theo câu mẫu phía trên."
        : "Bấm Ghi âm để thu phản hồi cho học viên."
    );

    if (!completeInput || !recordBtn || !playerWrap) return;

    const player = initPhatAmPlayer(playerWrap);

    let mediaRecorder = null;
    let chunks = [];
    let blobUrl = "";
    let uploadedPath = "";

    const escapeHtml = (value) =>
      String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");

    const setStatus = (payload = {}) => {
      if (!status) return;

      const state = payload.state || "idle";
      const title = payload.title || "";
      const hint = payload.hint || "";
      const complete = state === "complete" || payload.complete === true;

      status.dataset.state = state;
      status.classList.toggle("is-complete", complete);
      status.classList.toggle("is-recording", state === "recording");
      status.classList.toggle("is-idle", state === "idle");
      status.classList.toggle("is-uploading", state === "uploading");
      status.classList.toggle("is-error", state === "error");
      status.innerHTML =
        `<span class="session-phat-am-status-icon" aria-hidden="true"></span>` +
        `<span class="session-phat-am-status-copy">` +
        `<strong class="session-phat-am-status-title">${escapeHtml(title)}</strong>` +
        (hint ? `<small class="session-phat-am-status-hint">${escapeHtml(hint)}</small>` : "") +
        `</span>`;
    };

    const readStoredPath = () => {
      const raw = completeInput.value.trim();
      if (!raw) return "";

      if (valueMode === "path") {
        return raw;
      }

      try {
        const parsed = JSON.parse(raw);
        return typeof parsed?.recording === "string" ? parsed.recording.trim() : "";
      } catch {
        return "";
      }
    };

    const writeStoredPath = (path) => {
      if (valueMode === "path") {
        completeInput.value = path;
        return;
      }

      completeInput.value = path ? JSON.stringify({ recording: path }) : "";
    };

    const syncInput = () => {
      if (uploadedPath) {
        writeStoredPath(uploadedPath);
        setStatus({
          state: "complete",
          title: "Đã ghi âm xong",
          hint: requireSubmit
            ? "Nghe lại bản ghi, hoặc nộp bài khi sẵn sàng."
            : "Nghe lại bản ghi trước khi lưu chấm.",
        });
      } else {
        writeStoredPath("");
        setStatus({
          state: "idle",
          title: idleTitle,
          hint: idleHint,
        });
      }

      const studentWrap = board.closest("[data-session-phat-am-student]");
      if (studentWrap && typeof window.syncPhatAmStudentPayload === "function") {
        window.syncPhatAmStudentPayload(studentWrap);
      }
    };

    const showPlayback = (url) => {
      board.classList.remove("is-recording");
      playerWrap.hidden = false;
      player?.setSource(url);
      if (rerecordBtn) rerecordBtn.hidden = false;
      if (stopBtn) stopBtn.hidden = true;
      recordBtn.hidden = true;
    };

    const resetRecordingUi = () => {
      board.classList.remove("is-recording");
      player?.pause();
      player?.reset();

      if (blobUrl) {
        URL.revokeObjectURL(blobUrl);
        blobUrl = "";
      }

      uploadedPath = "";
      playerWrap.hidden = true;
      if (rerecordBtn) rerecordBtn.hidden = true;
      recordBtn.hidden = false;
      if (stopBtn) stopBtn.hidden = true;
      syncInput();
    };

    const uploadBlob = async (blob) => {
      if (!uploadUrl) {
        setStatus({
          state: "error",
          title: "Không tải được",
          hint: "Thiếu đường dẫn tải lên ghi âm.",
        });
        return;
      }

      setStatus({
        state: "uploading",
        title: "Đang tải bản ghi",
        hint: "Vui lòng đợi trong giây lát...",
      });

      const formData = new FormData();
      formData.append("recording", blob, `recording.${blob.type.includes("ogg") ? "ogg" : "webm"}`);

      if (csrfName && csrfHash) {
        formData.append(csrfName, csrfHash);
      }

      try {
        const response = await fetch(uploadUrl, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });
        const data = await response.json();

        if (!response.ok || !data.ok) {
          throw new Error(data.message || "Upload thất bại");
        }

        uploadedPath = data.path || "";
        if (data.csrfName && data.csrfHash) {
          board.dataset.csrfName = data.csrfName;
          board.dataset.csrfHash = data.csrfHash;
        }
        syncInput();
        const studentWrap = board.closest("[data-session-phat-am-student]");
        if (studentWrap && typeof window.syncPhatAmStudentPayload === "function") {
          window.syncPhatAmStudentPayload(studentWrap);
        }
      } catch (error) {
        const offline =
          window.AssessmentOffline?.isOffline?.() ||
          (typeof navigator.onLine === "boolean" && !navigator.onLine) ||
          error instanceof TypeError;

        if (offline && window.AssessmentOffline?.putBlob) {
          const stored = await window.AssessmentOffline.putBlob(
            "audio",
            blob,
            `recording.${blob.type.includes("ogg") ? "ogg" : "webm"}`
          );
          uploadedPath = stored.marker;
          blobUrl = stored.url;
          showPlayback(stored.url);
          syncInput();
          setStatus({
            state: "complete",
            title: "Đã lưu trên máy",
            hint: "Sẽ tự tải lên khi có mạng trở lại.",
          });
          window.dispatchEvent(new Event("assessment:network-error"));
          return;
        }

        setStatus({
          state: "error",
          title: "Tải lên thất bại",
          hint: error instanceof Error ? error.message : "Không tải được bản ghi âm.",
        });
      }
    };

    recordBtn.addEventListener("click", async () => {
      if (!navigator.mediaDevices?.getUserMedia) {
        setStatus({
          state: "error",
          title: "Không hỗ trợ ghi âm",
          hint: "Trình duyệt hiện tại không cho phép thu âm.",
        });
        return;
      }

      try {
        player?.pause();

        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const mimeType = MediaRecorder.isTypeSupported("audio/webm;codecs=opus")
          ? "audio/webm;codecs=opus"
          : MediaRecorder.isTypeSupported("audio/webm")
            ? "audio/webm"
            : "";

        mediaRecorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);
        chunks = [];

        mediaRecorder.addEventListener("dataavailable", (event) => {
          if (event.data.size > 0) chunks.push(event.data);
        });

        mediaRecorder.addEventListener("stop", () => {
          stream.getTracks().forEach((track) => track.stop());
          const blob = new Blob(chunks, { type: mediaRecorder.mimeType || "audio/webm" });
          blobUrl = URL.createObjectURL(blob);
          showPlayback(blobUrl);
          uploadBlob(blob);
        });

        mediaRecorder.start();
        board.classList.add("is-recording");
        recordBtn.hidden = true;
        if (stopBtn) stopBtn.hidden = false;
        if (rerecordBtn) rerecordBtn.hidden = true;
        playerWrap.hidden = true;
        setStatus({
          state: "recording",
          title: "Đang ghi âm",
          hint: "Bấm Dừng khi bạn đọc xong.",
        });
      } catch {
        board.classList.remove("is-recording");
        setStatus({
          state: "error",
          title: "Chưa mở được micro",
          hint: "Hãy cho phép quyền ghi âm rồi thử lại.",
        });
      }
    });

    stopBtn?.addEventListener("click", () => {
      if (mediaRecorder && mediaRecorder.state === "recording") {
        mediaRecorder.stop();
      }
    });

    rerecordBtn?.addEventListener("click", () => {
      resetRecordingUi();
    });

    uploadedPath = readStoredPath();

    if (uploadedPath) {
      const playFromUrl = (playbackUrl) => {
        if (playbackUrl) {
          showPlayback(playbackUrl);
          syncInput();
        }
      };

      if (uploadedPath.startsWith("offline-asset:") && window.AssessmentOffline?.resolveUrl) {
        window.AssessmentOffline.resolveUrl(uploadedPath).then(playFromUrl);
      } else {
        let playbackUrl = existingUrl;

        if (!playbackUrl) {
          playbackUrl = uploadedPath.startsWith("http")
            ? uploadedPath
            : `${window.location.origin}/${uploadedPath.replace(/^\//, "")}`;
        }

        playFromUrl(playbackUrl);
      }
    } else {
      syncInput();
    }
  });
})();
