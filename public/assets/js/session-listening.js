(() => {
  const boards = document.querySelectorAll("[data-session-listening-board]");

  if (!boards.length) return;

  const langPrefixOf = (lang) => (lang || "ko-KR").split("-")[0].toLowerCase();

  const formatTime = (seconds) => {
    if (!Number.isFinite(seconds) || seconds < 0) return "0:00";
    const total = Math.floor(seconds);
    const mins = Math.floor(total / 60);
    const secs = total % 60;
    return `${mins}:${String(secs).padStart(2, "0")}`;
  };

  const resolveVoice = (voiceUri, lang) => {
    if (typeof window.speechSynthesis === "undefined") return null;
    const voices = window.speechSynthesis.getVoices();
    const uri = (voiceUri || "").trim();

    if (uri) {
      const byUri = voices.find((v) => v.voiceURI === uri);
      if (byUri) return byUri;
      const byName = voices.find((v) => v.name === uri);
      if (byName) return byName;
    }

    const prefix = langPrefixOf(lang);
    const matching = voices.filter((v) => v.lang.toLowerCase().startsWith(prefix));

    return matching[0] || null;
  };

  const supportsTts = () => typeof window.speechSynthesis !== "undefined";

  const pauseTts = () => {
    if (!supportsTts()) return;
    if (window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
      window.speechSynthesis.pause();
    }
  };

  const resumeTts = () => {
    if (!supportsTts()) return;
    if (window.speechSynthesis.paused) {
      window.speechSynthesis.resume();
    }
  };

  const stopTts = () => {
    if (!supportsTts()) return;
    window.speechSynthesis.cancel();
  };

  const speak = (text, lang, voiceUri, onEnd) => {
    if (!supportsTts()) return;
    stopTts();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = lang || "ko-KR";
    utterance.rate = 0.92;

    const applyVoice = () => {
      const voice = resolveVoice(voiceUri, lang);
      if (voice) utterance.voice = voice;
      if (typeof onEnd === "function") utterance.onend = onEnd;
      window.speechSynthesis.speak(utterance);
    };

    if (window.speechSynthesis.getVoices().length === 0) {
      window.speechSynthesis.addEventListener("voiceschanged", applyVoice, { once: true });
      return;
    }

    applyVoice();
  };

  const buildWaveform = (container) => {
    if (!container || container.childElementCount > 0) return;

    const heights = [38, 52, 44, 68, 56, 72, 48, 64, 42, 58, 76, 50, 62, 40, 70, 54, 66, 46, 60, 74, 52, 68, 44, 58, 72, 48, 64, 56];

    heights.forEach((height, index) => {
      const bar = document.createElement("span");
      bar.className = "session-listening-waveform-bar";
      bar.style.setProperty("--bar-height", `${height}%`);
      bar.style.setProperty("--bar-delay", `${(index % 7) * 0.08}s`);
      container.appendChild(bar);
    });
  };

  boards.forEach((board) => {
    const mode = board.dataset.listeningMode || (board.dataset.listeningAudioUrl ? "audio" : "tts");
    const text = (board.dataset.listeningText || "").trim();
    const lang = board.dataset.listeningLang || "ko-KR";
    const voiceUri = (board.dataset.listeningVoice || "").trim();
    const audioUrl = (board.dataset.listeningAudioUrl || "").trim();
    const playBtn = board.querySelector("[data-listening-play]");
    const replayBtn = board.querySelector("[data-listening-replay]");
    const status = board.querySelector("[data-listening-status]");
    const waveform = board.querySelector("[data-listening-waveform]");
    const progressFill = board.querySelector("[data-listening-progress-fill]");
    const seekInput = board.querySelector("[data-listening-seek]");
    const currentEl = board.querySelector("[data-listening-current]");
    const durationEl = board.querySelector("[data-listening-duration]");
    const audioEl = board.querySelector("[data-listening-audio]");

    buildWaveform(waveform);

    const canPlay =
      (mode === "audio" && audioUrl !== "") || (mode === "tts" && text !== "" && supportsTts());

    if (!canPlay || !playBtn) {
      board.hidden = true;
      return;
    }

    board.hidden = false;

    /** @type {"idle"|"playing"|"paused"} */
    let playbackState = "idle";
    let useTts = mode !== "audio" || !audioEl;
    let ttsElapsed = 0;
    let ttsStartedAt = 0;
    let ttsTimer = null;
    let ttsEstimatedDuration = 0;

    const estimateTtsDuration = (value) => Math.max(4, value.length * 0.07);

    const updateProgressUi = (current, total) => {
      const safeTotal = Number.isFinite(total) && total > 0 ? total : 0;
      const safeCurrent = Number.isFinite(current) && current > 0 ? Math.min(current, safeTotal || current) : 0;
      const pct = safeTotal > 0 ? (safeCurrent / safeTotal) * 100 : 0;

      if (progressFill) progressFill.style.width = `${pct}%`;
      if (seekInput) seekInput.value = String(pct);
      if (currentEl) currentEl.textContent = formatTime(safeCurrent);
      if (durationEl) durationEl.textContent = safeTotal > 0 ? formatTime(safeTotal) : "0:00";
    };

    const clearTtsTimer = () => {
      if (ttsTimer) {
        window.clearInterval(ttsTimer);
        ttsTimer = null;
      }
    };

    const stopTtsTimer = () => {
      clearTtsTimer();
      if (playbackState === "playing" && ttsStartedAt > 0) {
        ttsElapsed += (Date.now() - ttsStartedAt) / 1000;
      }
      ttsStartedAt = 0;
    };

    const startTtsTimer = () => {
      clearTtsTimer();
      ttsEstimatedDuration = estimateTtsDuration(text);
      ttsStartedAt = Date.now();
      updateProgressUi(ttsElapsed, ttsEstimatedDuration);
      ttsTimer = window.setInterval(() => {
        if (playbackState !== "playing") return;
        const elapsed = ttsElapsed + (Date.now() - ttsStartedAt) / 1000;
        updateProgressUi(Math.min(elapsed, ttsEstimatedDuration), ttsEstimatedDuration);
      }, 120);
    };

    const resetProgressUi = () => {
      stopTtsTimer();
      ttsElapsed = 0;
      updateProgressUi(0, useTts ? estimateTtsDuration(text) : audioEl?.duration || 0);
    };

    const setPlaybackState = (state) => {
      playbackState = state;
      board.classList.toggle("is-playing", state === "playing");
      board.classList.toggle("is-paused", state === "paused");

      if (!playBtn) return;

      playBtn.disabled = false;
      playBtn.innerHTML =
        state === "playing"
          ? '<i class="bi bi-pause-fill" aria-hidden="true"></i>'
          : '<i class="bi bi-play-fill" aria-hidden="true"></i>';
    };

    const onDone = () => {
      stopTtsTimer();
      if (useTts) {
        updateProgressUi(ttsEstimatedDuration || estimateTtsDuration(text), ttsEstimatedDuration || estimateTtsDuration(text));
      } else if (audioEl && Number.isFinite(audioEl.duration)) {
        updateProgressUi(audioEl.duration, audioEl.duration);
      }
      setPlaybackState("idle");
      if (status) status.textContent = "Bạn có thể nghe lại trước khi nộp bài.";
    };

    const startTts = () => {
      useTts = true;
      if (seekInput) seekInput.disabled = true;
      setPlaybackState("playing");
      startTtsTimer();
      if (status) status.textContent = "Đang phát âm thanh — hãy lắng nghe kỹ.";
      speak(text, lang, voiceUri, onDone);
    };

    const startAudio = () => {
      if (!audioEl) {
        startTts();
        return;
      }

      useTts = false;
      if (seekInput) seekInput.disabled = false;
      setPlaybackState("playing");
      if (status) status.textContent = "Đang phát file MP3 — hãy lắng nghe kỹ.";
      audioEl.play().catch(() => {
        setPlaybackState("idle");
        if (status) status.textContent = "Không phát được file âm thanh.";
      });
    };

    const startFromBeginning = () => {
      stopTts();
      clearTtsTimer();
      ttsElapsed = 0;
      ttsStartedAt = 0;

      if (audioEl) {
        audioEl.pause();
        audioEl.currentTime = 0;
      }

      if (mode === "audio" && audioUrl !== "" && audioEl) {
        resetProgressUi();
        startAudio();
        return;
      }

      resetProgressUi();
      startTts();
    };

    const pausePlayback = () => {
      if (useTts) {
        stopTtsTimer();
        pauseTts();
      } else if (audioEl) {
        audioEl.pause();
      }

      setPlaybackState("paused");
      if (status) status.textContent = "Đã tạm dừng — bấm phát để nghe tiếp.";
    };

    const resumePlayback = () => {
      if (useTts) {
        if (window.speechSynthesis.speaking && window.speechSynthesis.paused) {
          resumeTts();
          startTtsTimer();
        } else {
          startTts();
          return;
        }
      } else if (audioEl) {
        audioEl.play().catch(() => {
          setPlaybackState("idle");
          if (status) status.textContent = "Không phát được file âm thanh.";
        });
      }

      setPlaybackState("playing");
      if (status) {
        status.textContent = useTts
          ? "Đang phát âm thanh — hãy lắng nghe kỹ."
          : "Đang phát file MP3 — hãy lắng nghe kỹ.";
      }
    };

    if (audioEl) {
      audioEl.addEventListener("loadedmetadata", () => {
        if (!useTts) updateProgressUi(audioEl.currentTime || 0, audioEl.duration || 0);
      });

      audioEl.addEventListener("timeupdate", () => {
        if (useTts || playbackState !== "playing") return;
        updateProgressUi(audioEl.currentTime, audioEl.duration || 0);
      });

      audioEl.addEventListener("ended", onDone);

      audioEl.addEventListener("pause", () => {
        if (audioEl.ended || playbackState !== "playing") return;
        setPlaybackState("paused");
        if (status) status.textContent = "Đã tạm dừng — bấm phát để nghe tiếp.";
      });
    }

    seekInput?.addEventListener("input", () => {
      if (!audioEl || useTts || !Number.isFinite(audioEl.duration)) return;
      const pct = Number(seekInput.value) / 100;
      audioEl.currentTime = pct * audioEl.duration;
      updateProgressUi(audioEl.currentTime, audioEl.duration);
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

      startFromBeginning();
    });

    replayBtn?.addEventListener("click", startFromBeginning);

    if (seekInput) seekInput.disabled = useTts;
    resetProgressUi();
  });
})();
