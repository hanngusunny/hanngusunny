(() => {
  const cfg = window.classroomRoomConfig;
  if (!cfg || !cfg.pollFeedUrl) return;

  const isModerator = Boolean(cfg.isModerator);
  let activePollId = null;
  let pendingPoll = null;
  let pollTimer = null;
  let statsTimer = null;

  const studentOverlay = document.getElementById("studentPollOverlay");
  const studentTitle = document.getElementById("studentPollTitle");
  const studentTypeTag = document.getElementById("studentPollTypeTag");
  const studentIcon = document.getElementById("studentPollIcon");
  const studentBody = document.getElementById("studentPollBody");
  const studentFoot = document.getElementById("studentPollFoot");
  const submitAnswerBtn = document.getElementById("submitPollAnswerBtn");
  const closeStudentPollBtn = document.getElementById("closeStudentPollBtn");
  const studentPollCountdown = document.getElementById("studentPollCountdown");
  const studentPollCountdownText = document.getElementById("studentPollCountdownText");
  const studentAnswerList = document.getElementById("studentAnswerList");
  const studentAnswerCount = document.getElementById("studentAnswerCount");
  const openStudentAnswersBtn = document.getElementById("openStudentAnswersBtn");

  let studentModalMode = "answer";
  let lastShownPendingPollId = null;
  let pollCountdownTimer = null;
  let pollCountdownRemaining = 0;

  const POLL_ANSWER_TIMEOUT_SEC = 60;

  const pollDurationSeconds = (poll) => {
    const seconds = Number(poll?.durationSeconds || 0);
    if (seconds === 120 || seconds === 180 || seconds === 300) return seconds;
    const minutes = Number(poll?.durationMinutes || 0);
    if (minutes === 2 || minutes === 3 || minutes === 5) return minutes * 60;
    return POLL_ANSWER_TIMEOUT_SEC;
  };

  const remainingPollSeconds = (poll) => {
    const duration = pollDurationSeconds(poll);
    const createdAt = String(poll?.createdAt || "").replace(" ", "T");
    const createdMs = createdAt ? Date.parse(createdAt) : NaN;

    if (!Number.isFinite(createdMs)) {
      return duration;
    }

    return Math.max(0, duration - Math.floor((Date.now() - createdMs) / 1000));
  };

  const createOverlay = document.getElementById("createPollOverlay");
  const openCreateBtn = document.getElementById("openCreatePollBtn");
  const closeCreateBtn = document.getElementById("closeCreatePollBtn");
  const cancelCreateBtn = document.getElementById("cancelCreatePollBtn");
  const submitCreateBtn = document.getElementById("submitCreatePollBtn");
  const createQuestion = document.getElementById("createPollQuestion");
  const createOptionsWrap = document.getElementById("createPollOptionsWrap");
  const pollTypeRadios = document.querySelectorAll('input[name="pollType"]');
  const createPollHint = document.getElementById("createPollHint");

  const moderatorPollList = document.getElementById("moderatorPollList");
  const moderatorPresetList = document.getElementById("moderatorPresetList");
  const moderatorPollMain = document.getElementById("moderatorPollMain");
  const moderatorPollStats = document.getElementById("moderatorPollStats");
  const statsPollQuestion = document.getElementById("statsPollQuestion");
  const statsPollSummary = document.getElementById("statsPollSummary");
  const statsBody = document.getElementById("moderatorPollStatsBody");
  const backToPollList = document.getElementById("backToPollList");
  const closeActivePollBtn = document.getElementById("closeActivePollBtn");

  const sidebarTabs = document.querySelectorAll(".meeting-sidebar-tab");
  const sidebarPanels = document.querySelectorAll(".meeting-sidebar-panel");

  const postForm = (url, fields) => {
    if (window.classroomPostForm) {
      return window.classroomPostForm(url, fields);
    }
    const body = new URLSearchParams();
    body.set(cfg.csrfName, window.getClassroomCsrfToken ? window.getClassroomCsrfToken() : cfg.csrfHash);
    Object.entries(fields).forEach(([key, value]) => {
      if (Array.isArray(value)) {
        value.forEach((v) => body.append(key + "[]", v));
      } else {
        body.set(key, value);
      }
    });
    return fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body.toString(),
    }).then((res) => res.json());
  };

  const formatTime = (value) => {
    if (!value) return "—";
    const d = new Date(value.replace(" ", "T"));
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleTimeString("vi-VN", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
  };

  const switchSidebarTab = (tab) => {
    sidebarTabs.forEach((btn) => {
      const active = btn.dataset.tab === tab;
      btn.classList.toggle("is-active", active);
      btn.setAttribute("aria-selected", active ? "true" : "false");
    });
    sidebarPanels.forEach((panel) => {
      panel.classList.toggle("is-active", panel.dataset.panel === tab);
    });
  };

  sidebarTabs.forEach((btn) => {
    btn.addEventListener("click", () => switchSidebarTab(btn.dataset.tab || "participants"));
  });

  const formatDateTime = (value) => {
    if (!value) return "—";
    const d = new Date(value.replace(" ", "T"));
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString("vi-VN", {
      hour: "2-digit",
      minute: "2-digit",
      day: "2-digit",
      month: "2-digit",
    });
  };

  const escapeHtml = (text) => {
    return String(text || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  };

  const stripHtml = (html) => {
    let raw = String(html || "");
    raw = raw.replace(/<\s*br\s*\/?>/gi, "\n");
    raw = raw.replace(/<\/\s*(p|div|li|tr|h[1-6])\s*>/gi, "\n");

    const div = document.createElement("div");
    div.innerHTML = raw;
    let text = div.textContent || div.innerText || "";

    if (/<[a-z][\s\S]*>/i.test(text)) {
      text = text.replace(/<[^>]+>/g, " ");
    }

    text = text.replace(/\u00a0/g, " ");
    text = text.replace(/[ \t]+/g, " ");
    text = text.replace(/\n{3,}/g, "\n\n");

    return text.trim();
  };

  const decodeHtmlEntities = (text) => {
    const ta = document.createElement("textarea");
    ta.innerHTML = String(text || "");
    return ta.value;
  };

  const stripGroupLabel = (text) =>
    String(text || "").replace(
      /^\[(?:Kiểm tra bài cũ|Trắc nghiệm trong buổi|Ôn tập sau buổi)\]\s*/u,
      ""
    );

  const plainText = (value) => {
    let text = stripHtml(String(value || ""));
    text = decodeHtmlEntities(text);
    text = decodeHtmlEntities(text);
    return stripGroupLabel(text).trim();
  };

  const formatPromptHtml = (text) =>
    plainText(text)
      .split("\n")
      .map((line) => escapeHtml(line))
      .join("<br>");

  const getStudentPollTitle = (poll) => {
    const type = poll.type || "fill";
    const full = plainText(poll.question);

    if (type === "fill") {
      return "Điền từ vào chỗ trống";
    }

    const firstLine = full.split("\n")[0].trim();
    if (firstLine.length > 120) {
      return firstLine.slice(0, 120) + "…";
    }

    return firstLine || "Câu hỏi";
  };

  const typeLabel = (type) => (type === "choice" ? "Trắc nghiệm" : "Điền từ");

  const formatCountdown = (seconds) => {
    const safe = Math.max(0, seconds);
    const m = Math.floor(safe / 60);
    const s = safe % 60;
    return String(m).padStart(2, "0") + ":" + String(s).padStart(2, "0");
  };

  const clearPollCountdown = () => {
    if (pollCountdownTimer) {
      clearInterval(pollCountdownTimer);
      pollCountdownTimer = null;
    }
    if (studentPollCountdown) studentPollCountdown.hidden = true;
    if (studentOverlay) studentOverlay.classList.remove("is-countdown-urgent");
  };

  const updatePollCountdownUi = () => {
    if (studentPollCountdownText) {
      studentPollCountdownText.textContent = formatCountdown(pollCountdownRemaining);
    }
    if (studentPollCountdown) studentPollCountdown.hidden = false;
    if (studentOverlay) {
      studentOverlay.classList.toggle("is-countdown-urgent", pollCountdownRemaining <= 10);
    }
  };

  const startPollCountdown = (poll) => {
    clearPollCountdown();
    pollCountdownRemaining = remainingPollSeconds(poll);

    if (pollCountdownRemaining <= 0) {
      hideStudentPoll();
      return;
    }

    updatePollCountdownUi();
    pollCountdownTimer = setInterval(() => {
      pollCountdownRemaining -= 1;
      if (pollCountdownRemaining <= 0) {
        clearPollCountdown();
        hideStudentPoll();
        return;
      }
      updatePollCountdownUi();
    }, 1000);
  };

  const buildChoiceHtml = (options, selectedValue, readOnly) => {
    const letters = ["A", "B", "C", "D", "E", "F"];
    return (
      '<div class="meeting-poll-choices">' +
      options
        .map((opt, i) => {
          const checked = selectedValue === opt;
          const selectedClass = checked ? " is-selected" : "";
          if (readOnly) {
            return (
              '<div class="meeting-poll-choice meeting-poll-choice-readonly' +
              selectedClass +
              '">' +
              '<span class="meeting-poll-choice-letter">' +
              (letters[i] || "?") +
              "</span>" +
              "<span>" +
              escapeHtml(plainText(opt)) +
              "</span>" +
              (checked ? '<i class="fa-solid fa-check meeting-poll-choice-check"></i>' : "") +
              "</div>"
            );
          }
          const optText = plainText(opt);
          return (
            '<label class="meeting-poll-choice">' +
            '<input type="radio" name="studentPollAnswer" value="' +
            escapeHtml(optText) +
            '">' +
            '<span class="meeting-poll-choice-letter">' +
            (letters[i] || "?") +
            "</span>" +
            "<span>" +
            escapeHtml(optText) +
            "</span></label>"
          );
        })
        .join("") +
      "</div>"
    );
  };

  const buildFillHtml = (questionText, selectedValue, readOnly) => {
    const promptHtml = plainText(questionText)
      ? '<div class="meeting-poll-fill-prompt">' + formatPromptHtml(questionText) + "</div>"
      : "";

    if (readOnly) {
      return (
        promptHtml +
        '<div class="meeting-poll-answer-result">' +
        '<span class="meeting-poll-answer-result-label">Câu trả lời của bạn</span>' +
        '<strong class="meeting-poll-answer-result-value">' +
        escapeHtml(selectedValue || "—") +
        "</strong></div>"
      );
    }
    return (
      promptHtml +
      '<div class="meeting-poll-fill-wrap">' +
      '<label class="meeting-poll-fill-label" for="studentPollTextAnswer">Điền từ còn thiếu</label>' +
      '<input class="form-control meeting-poll-fill-input" type="text" id="studentPollTextAnswer" autocomplete="off" placeholder="Nhập từ điền vào chỗ trống...">' +
      "</div>"
    );
  };

  const setStudentModalMeta = (poll, readOnly) => {
    const type = poll.type || "fill";
    if (studentTypeTag) studentTypeTag.textContent = typeLabel(type);
    if (studentTitle) studentTitle.textContent = getStudentPollTitle(poll);
    if (studentIcon) {
      studentIcon.innerHTML =
        type === "choice"
          ? '<i class="fa-solid fa-list-check"></i>'
          : '<i class="fa-solid fa-i-cursor"></i>';
    }
    if (studentOverlay) {
      studentOverlay.classList.toggle("is-readonly", readOnly);
      studentOverlay.classList.toggle("is-active-poll", !readOnly);
    }
    if (readOnly) {
      clearPollCountdown();
    }
  };

  const showStudentPollModal = (poll, options) => {
    if (!studentOverlay || !poll) return;

    const readOnly = Boolean(options?.readOnly);
    const savedAnswer = options?.answer || "";
    studentModalMode = readOnly ? "review" : "answer";
    pendingPoll = readOnly ? null : poll;

    setStudentModalMeta(poll, readOnly);

    if (poll.type === "choice" && Array.isArray(poll.options) && poll.options.length) {
      studentBody.innerHTML = buildChoiceHtml(poll.options, savedAnswer, readOnly);
    } else {
      studentBody.innerHTML = buildFillHtml(poll.question, savedAnswer, readOnly);
    }

    if (studentFoot) {
      if (readOnly) {
        studentFoot.innerHTML =
          '<button class="btn btn-ghost meeting-poll-submit-btn" type="button" id="closeStudentPollReviewBtn">Đóng</button>';
        document.getElementById("closeStudentPollReviewBtn")?.addEventListener("click", hideStudentPoll);
      } else {
        studentFoot.innerHTML =
          '<button class="btn btn-sunny btn-with-icon meeting-poll-submit-btn" type="button" id="submitPollAnswerBtn">' +
          '<i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>Gửi câu trả lời</span></button>';
        document.getElementById("submitPollAnswerBtn")?.addEventListener("click", submitStudentAnswer);
        startPollCountdown(poll);
      }
    }

    studentOverlay.hidden = false;
  };

  const showStudentSuccess = (answer) => {
    if (!studentBody || !studentFoot) return;

    clearPollCountdown();
    studentModalMode = "success";
    pendingPoll = null;
    if (studentTypeTag) studentTypeTag.textContent = "Đã gửi";
    if (studentIcon) studentIcon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
    if (studentOverlay) {
      studentOverlay.classList.remove("is-active-poll");
      studentOverlay.classList.add("is-success");
    }

    studentBody.innerHTML =
      '<div class="meeting-poll-success">' +
      '<div class="meeting-poll-success-ring"><i class="fa-solid fa-check"></i></div>' +
      "<h3>Đã gửi câu trả lời!</h3>" +
      "<p>Câu trả lời của bạn đã được lưu lại.</p>" +
      '<div class="meeting-poll-success-answer">' +
      escapeHtml(answer) +
      "</div></div>";

    studentFoot.innerHTML =
      '<button class="btn btn-sunny meeting-poll-submit-btn" type="button" id="closeStudentSuccessBtn">Tiếp tục học</button>';
    document.getElementById("closeStudentSuccessBtn")?.addEventListener("click", hideStudentPoll);

    setTimeout(() => {
      if (studentModalMode === "success") hideStudentPoll();
    }, 2800);
  };

  const renderStudentPoll = (poll) => {
    if (!poll || poll.id === lastShownPendingPollId) return;
    if (remainingPollSeconds(poll) <= 0) {
      lastShownPendingPollId = poll.id;
      return;
    }
    lastShownPendingPollId = poll.id;
    showStudentPollModal(poll, { readOnly: false });
  };

  const hideStudentPoll = () => {
    clearPollCountdown();
    if (studentOverlay) {
      studentOverlay.hidden = true;
      studentOverlay.classList.remove("is-readonly", "is-success", "is-active-poll", "is-countdown-urgent");
    }
    pendingPoll = null;
    studentModalMode = "answer";
  };

  closeStudentPollBtn?.addEventListener("click", () => {
    if (studentModalMode === "answer") return;
    hideStudentPoll();
  });

  studentOverlay?.addEventListener("click", (e) => {
    if (e.target === studentOverlay && studentModalMode !== "answer") hideStudentPoll();
  });

  const renderStudentAnswerHistory = (items) => {
    if (!studentAnswerList) return;

    const count = items?.length || 0;
    if (studentAnswerCount) {
      studentAnswerCount.textContent = String(count);
      studentAnswerCount.hidden = count === 0;
    }

    if (!count) {
      studentAnswerList.innerHTML = '<p class="meeting-poll-empty">Chưa trả lời câu hỏi nào.</p>';
      return;
    }

    studentAnswerList.innerHTML = items
      .map(
        (item) =>
          '<button class="meeting-student-answer-card" type="button" data-poll-id="' +
          item.pollId +
          '">' +
          '<span class="meeting-student-answer-type">' +
          typeLabel(item.type) +
          "</span>" +
          "<strong>" +
          escapeHtml(getStudentPollTitle({ question: item.question, type: item.type })) +
          "</strong>" +
          '<span class="meeting-student-answer-value"><i class="fa-solid fa-reply"></i> ' +
          escapeHtml(item.answer) +
          "</span>" +
          '<span class="meeting-student-answer-time">' +
          formatDateTime(item.answeredAt) +
          "</span></button>"
      )
      .join("");

    studentAnswerList.querySelectorAll(".meeting-student-answer-card").forEach((btn, index) => {
      btn.addEventListener("click", () => {
        const item = items[index];
        if (item) {
          showStudentPollModal(
            {
              id: item.pollId,
              question: item.question,
              type: item.type,
              options: item.options || [],
            },
            { readOnly: true, answer: item.answer }
          );
        }
      });
    });
  };

  openStudentAnswersBtn?.addEventListener("click", () => {
    switchSidebarTab("myanswers");
    document.getElementById("meetingSidebar")?.scrollIntoView({ behavior: "smooth", block: "nearest" });
  });

  const getStudentAnswer = () => {
    const radio = document.querySelector('input[name="studentPollAnswer"]:checked');
    if (radio) return radio.value;
    const text = document.getElementById("studentPollTextAnswer");
    return text ? text.value.trim() : "";
  };

  const submitStudentAnswer = async () => {
    if (!pendingPoll) return;

    const answer = getStudentAnswer();
    if (!answer) {
      alert("Vui lòng nhập hoặc chọn câu trả lời.");
      return;
    }

    const btn = document.getElementById("submitPollAnswerBtn");
    if (btn) btn.disabled = true;

    try {
      const data = await postForm(cfg.pollBaseUrl + "/" + pendingPoll.id + "/tra-loi", { answer });
      if (data.ok) {
        lastShownPendingPollId = pendingPoll.id;
        showStudentSuccess(answer);
        fetchPollFeed();
      } else {
        alert(data.message || "Không gửi được câu trả lời.");
      }
    } catch (e) {
      alert("Lỗi kết nối. Vui lòng thử lại.");
    } finally {
      if (btn) btn.disabled = false;
    }
  };

  const renderModeratorPresetList = (presets) => {
    if (!moderatorPresetList) return;

    if (!presets || !presets.length) {
      moderatorPresetList.innerHTML =
        '<p class="meeting-poll-empty">Chưa có câu hỏi soạn sẵn. Thêm tại quản lý phòng học hoặc tab câu hỏi trong buổi học chương trình.</p>';
      return;
    }

    moderatorPresetList.innerHTML = presets
      .map((preset, index) => {
        const typeLabel = preset.type === "choice" ? "Trắc nghiệm" : "Điền từ";
        return (
          '<button class="meeting-preset-card" type="button" data-preset-id="' +
          preset.id +
          '">' +
          '<span class="meeting-preset-no">Câu ' +
          (index + 1) +
          "</span>" +
          '<span class="meeting-poll-card-type">' +
          typeLabel +
          "</span>" +
          "<strong>" +
          escapeHtml(plainText(preset.question || "—")) +
          "</strong>" +
          '<span class="meeting-preset-action"><i class="fa-solid fa-paper-plane"></i> Phát câu này</span>' +
          "</button>"
        );
      })
      .join("");

    moderatorPresetList.querySelectorAll(".meeting-preset-card").forEach((btn) => {
      btn.addEventListener("click", () => {
        const presetId = btn.dataset.presetId;
        if (presetId !== undefined && presetId !== "") {
          launchPreset(presetId);
        }
      });
    });
  };

  const launchPreset = async (presetId) => {
    if (!cfg.pollPresetBaseUrl) return;

    try {
      const data = await postForm(
        cfg.pollPresetBaseUrl + "/" + encodeURIComponent(String(presetId)) + "/phat",
        {}
      );
      if (data.ok && data.poll?.id) {
        switchSidebarTab("polls");
        fetchPollFeed();
        showPollStats(data.poll.id);
      } else {
        alert(data.message || "Không phát được câu hỏi.");
      }
    } catch (e) {
      alert("Lỗi kết nối: " + (e.message || "Không gửi được yêu cầu."));
    }
  };

  const renderModeratorPollList = (polls) => {
    if (!moderatorPollList) return;

    if (!polls || !polls.length) {
      moderatorPollList.innerHTML = '<p class="meeting-poll-empty">Chưa phát câu hỏi nào.</p>';
      return;
    }

    moderatorPollList.innerHTML = polls
      .map((poll) => {
        const statusClass = poll.status === "open" ? "is-open" : "is-closed";
        const statusLabel = poll.status === "open" ? "Đang mở" : "Đã đóng";
        const count = poll.answerCount || 0;
        const total = poll.totalStudents || poll.onlineStudents || 0;

        const typeLabel = poll.type === "choice" ? "Trắc nghiệm" : "Điền từ";
        const minutes = Number(poll.durationMinutes || pollDurationSeconds(poll) / 60) || 1;

        return (
          '<button class="meeting-poll-card ' +
          statusClass +
          '" type="button" data-poll-id="' +
          poll.id +
          '">' +
          '<span class="meeting-poll-card-status">' +
          statusLabel +
          "</span>" +
          '<span class="meeting-poll-card-type">' +
          typeLabel +
          " · " +
          minutes +
          " phút</span>" +
          "<strong>" +
          escapeHtml(plainText(poll.question || "—")) +
          "</strong>" +
          '<span class="meeting-poll-card-meta">' +
          count +
          "/" +
          total +
          " trong lớp đã trả lời</span>" +
          "</button>"
        );
      })
      .join("");

    moderatorPollList.querySelectorAll(".meeting-poll-card").forEach((btn) => {
      btn.addEventListener("click", () => {
        const pollId = parseInt(btn.dataset.pollId || "0", 10);
        if (pollId) showPollStats(pollId);
      });
    });
  };

  const renderStatsTable = (data) => {
    if (!statsBody || !data) return;

    statsPollQuestion.textContent = plainText(data.poll?.question || "") || "—";
    const rows = data.stats || [];
    const answered = rows.filter((row) => row.answered).length;
    const pending = rows.length - answered;
    statsPollSummary.textContent =
      answered + "/" + rows.length + " đã trả lời · " + pending + " chưa trả lời";

    if (!rows.length) {
      statsBody.innerHTML =
        '<p class="meeting-poll-stats-empty">Chưa có học viên nào trong lớp.</p>';
      return;
    }

    const list = rows
      .map((row) => {
        const answered = Boolean(row.answered);
        const badgeClass = answered
          ? "meeting-poll-stats-badge--done"
          : "meeting-poll-stats-badge--pending";
        const badgeLabel = answered ? "Đã trả lời" : "Chưa trả lời";
        const itemClass = answered
          ? "meeting-poll-stats-item--answered"
          : "meeting-poll-stats-item--pending";

        let body = "";

        if (answered) {
          body =
            '<div class="meeting-poll-stats-item-answer">' +
            escapeHtml(row.answer || "—") +
            "</div>" +
            '<span class="meeting-poll-stats-item-time">' +
            formatTime(row.answeredAt) +
            "</span>";
        } else {
          body =
            '<span class="meeting-poll-stats-item-hint">Học viên đang trong lớp, chưa gửi câu trả lời.</span>';
        }

        return (
          '<article class="meeting-poll-stats-item ' +
          itemClass +
          '">' +
          '<div class="meeting-poll-stats-item-head">' +
          '<div class="meeting-poll-stats-item-title">' +
          "<strong>" +
          escapeHtml(row.name || "—") +
          "</strong>" +
          "</div>" +
          '<span class="meeting-poll-stats-badge ' +
          badgeClass +
          '">' +
          badgeLabel +
          "</span>" +
          "</div>" +
          body +
          "</article>"
        );
      })
      .join("");

    statsBody.innerHTML =
      '<header class="meeting-poll-stats-all-head">' +
      "<h3>Học viên trong lớp</h3>" +
      '<span class="meeting-poll-stats-group-count">' +
      rows.length +
      "</span>" +
      "</header>" +
      '<div class="meeting-poll-stats-group-list meeting-poll-stats-group-list--all">' +
      list +
      "</div>";
  };

  const showPollStats = async (pollId) => {
    activePollId = pollId;
    if (moderatorPollMain) moderatorPollMain.hidden = true;
    if (moderatorPollStats) moderatorPollStats.hidden = false;

    try {
      const res = await fetch(cfg.pollBaseUrl + "/" + pollId + "/thong-ke", { credentials: "same-origin" });
      const data = await res.json();
      if (window.syncClassroomCsrf) window.syncClassroomCsrf(data);
      if (data.ok) renderStatsTable(data);
    } catch (e) {
      /* ignore */
    }

    clearInterval(statsTimer);
    statsTimer = setInterval(async () => {
      if (!activePollId) return;
      try {
        const res = await fetch(cfg.pollBaseUrl + "/" + activePollId + "/thong-ke", { credentials: "same-origin" });
        const data = await res.json();
        if (window.syncClassroomCsrf) window.syncClassroomCsrf(data);
        if (data.ok) renderStatsTable(data);
      } catch (e) {
        /* ignore */
      }
    }, 5000);
  };

  backToPollList?.addEventListener("click", () => {
    activePollId = null;
    clearInterval(statsTimer);
    if (moderatorPollMain) moderatorPollMain.hidden = false;
    if (moderatorPollStats) moderatorPollStats.hidden = true;
  });

  closeActivePollBtn?.addEventListener("click", async () => {
    if (!activePollId) return;

    const proceed = window.AppConfirm
      ? await window.AppConfirm.ask("Đóng câu hỏi này? Học viên sẽ không trả lời được nữa.", {
          title: "Xác nhận đóng câu hỏi",
          confirmLabel: "Đóng câu hỏi",
        })
      : confirm("Đóng câu hỏi này? Học viên sẽ không trả lời được nữa.");

    if (!proceed) return;

    try {
      const data = await postForm(cfg.pollBaseUrl + "/" + activePollId + "/dong", {});
      if (data.ok) {
        fetchPollFeed();
        backToPollList?.click();
      }
    } catch (e) {
      /* ignore */
    }
  });

  const fetchPollFeed = async () => {
    try {
      const res = await fetch(cfg.pollFeedUrl, { credentials: "same-origin" });
      const data = await res.json();
      if (!data.ok) return;

      if (window.syncClassroomCsrf) window.syncClassroomCsrf(data);

      if (data.pendingPoll && !isModerator) {
        renderStudentPoll(data.pendingPoll);
      }

      if (!isModerator && data.myAnswers) {
        renderStudentAnswerHistory(data.myAnswers);
      }

      if (isModerator && data.polls) {
        renderModeratorPollList(data.polls);
      }

      if (isModerator && data.presets) {
        renderModeratorPresetList(data.presets);
      }
    } catch (e) {
      /* ignore */
    }
  };

  const showCreateModal = () => {
    if (createOverlay) createOverlay.hidden = false;
    if (createQuestion) createQuestion.focus();
  };

  const hideCreateModal = () => {
    if (createOverlay) createOverlay.hidden = true;
    if (createQuestion) createQuestion.value = "";
    document.querySelectorAll('input[name="pollType"]').forEach((r) => {
      r.checked = r.value === "fill";
    });
    if (createOptionsWrap) createOptionsWrap.hidden = true;
    updateCreatePollHint("fill");
    document.querySelectorAll('#createPollOptions input[name="pollOption"]').forEach((inp) => {
      inp.value = "";
    });
  };

  const updateCreatePollHint = (type) => {
    if (!createPollHint) return;
    createPollHint.textContent =
      type === "choice"
        ? "Trắc nghiệm: nhập câu hỏi và ít nhất 2 lựa chọn bên dưới."
        : "Điền từ: dùng ___ hoặc [...] trong câu hỏi — học viên gõ từ còn thiếu.";
  };

  pollTypeRadios.forEach((radio) => {
    radio.addEventListener("change", () => {
      if (!radio.checked) return;
      if (createOptionsWrap) {
        createOptionsWrap.hidden = radio.value !== "choice";
      }
      updateCreatePollHint(radio.value);
    });
  });

  openCreateBtn?.addEventListener("click", showCreateModal);
  closeCreateBtn?.addEventListener("click", hideCreateModal);
  cancelCreateBtn?.addEventListener("click", hideCreateModal);

  submitCreateBtn?.addEventListener("click", async () => {
    const question = createQuestion?.value.trim() || "";
    const type = document.querySelector('input[name="pollType"]:checked')?.value || "fill";

    if (question.length < 2) {
      alert("Vui lòng nhập nội dung câu hỏi.");
      return;
    }

    const duration = Number(document.querySelector('input[name="pollDuration"]:checked')?.value || 1);
    const fields = { question, type, duration };

    if (type === "choice") {
      const options = [];
      document.querySelectorAll('#createPollOptions input[name="pollOption"]').forEach((inp) => {
        const v = inp.value.trim();
        if (v) options.push(v);
      });
      fields.options = options;
    }

    submitCreateBtn.disabled = true;

    try {
      const data = await postForm(cfg.pollCreateUrl, fields);
      if (data.ok) {
        hideCreateModal();
        switchSidebarTab("polls");
        fetchPollFeed();
        if (data.poll?.id) showPollStats(data.poll.id);
      } else {
        alert(data.message || "Không tạo được câu hỏi.");
      }
    } catch (e) {
      alert("Lỗi kết nối: " + (e.message || "Không gửi được yêu cầu."));
    } finally {
      submitCreateBtn.disabled = false;
    }
  });

  fetchPollFeed().then(() => {
    if (window.classroomStartPresencePing) {
      window.classroomStartPresencePing();
    }
  });
  pollTimer = setInterval(fetchPollFeed, 4000);

  window.addEventListener("beforeunload", () => {
    clearInterval(pollTimer);
    clearInterval(statsTimer);
  });
})();
