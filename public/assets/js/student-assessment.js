(() => {
  "use strict";

  const ICON_ARROW_WHITE_CIRCLE =
    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFFFFF"/><path d="M10.2 8.4L14 12L10.2 15.6" stroke="#FF7614" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  const ICON_RETURN_DARK =
    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 14L4 9L9 4" stroke="#313B47" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 20V13C20 10.8 18.2 9 16 9H4" stroke="#313B47" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  const ICON_ARROW_UP_RIGHT =
    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 17L17 7M17 7H9M17 7V15" stroke="#313B47" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  /* =========================================================================
     1. Phòng chờ: Modal Xác nhận bắt đầu bài thi (#710:72346)
     ========================================================================= */
  const initWaitingRoom = () => {
    const modal = document.getElementById("snExamStartModal");
    const openBtn = document.querySelector("[data-exam-open-start-modal]");

    if (!modal || !openBtn) {
      return;
    }

    const closeBtns = modal.querySelectorAll("[data-exam-close-start-modal]");
    const startForm = document.getElementById("snExamStartForm");
    const submitBtn = document.getElementById("snExamStartSubmitBtn");

    const open = () => {
      modal.hidden = false;
      document.body.style.overflow = "hidden";
      submitBtn?.focus();
    };

    const close = () => {
      modal.hidden = true;
      document.body.style.overflow = "";
      openBtn.focus();
    };

    openBtn.addEventListener("click", open);
    closeBtns.forEach((btn) => btn.addEventListener("click", close));

    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        close();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !modal.hidden) {
        close();
      }
    });

    startForm?.addEventListener("submit", () => {
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Đang vào bài thi...";
      }
    });
  };

  /* =========================================================================
     2. Chế độ làm bài tập trung: Phân trang, Bảng Câu trả lời, Modals, Overlay
     ========================================================================= */
  const initExamTake = () => {
    const focusRoot = document.querySelector(".sn-exam-focus-mode");
    const form = document.getElementById("snExamQuizForm");

    if (!focusRoot || !(form instanceof HTMLFormElement)) {
      return;
    }

    document.body.classList.add("is-exam-focus");

    const items = [...form.querySelectorAll(".session-quiz-item")];
    const totalQuestions = items.length;
    const perPage = Math.max(1, parseInt(form.dataset.examPerPage || "5", 10));
    const totalPages = Math.max(1, Math.ceil(totalQuestions / perPage));
    let currentPage = 1;

    items.forEach((item, index) => {
      if (!item.dataset.questionNo) {
        item.dataset.questionNo = String(index + 1);
      }
    });

    const paginationEl = document.getElementById("snExamPagination");
    const paletteGrid = document.getElementById("snExamPaletteGrid");
    const paletteBtns = paletteGrid
      ? [...paletteGrid.querySelectorAll("[data-exam-jump]")]
      : [];
    const progressText = document.getElementById("snExamProgressText");
    const progressFill = document.getElementById("snExamProgressFill");

    const actionModal = document.getElementById("snExamActionModal");
    const actionTitle = document.getElementById("snExamActionModalTitle");
    const actionDesc = document.getElementById("snExamActionModalDesc");
    const actionMascotWrap = document.getElementById("snExamActionMascotWrap");
    const actionImg = document.getElementById("snExamActionModalImg");
    const actionBtnsWrap = document.getElementById("snExamActionModalButtons");
    const actionPrimaryBtn = document.getElementById("snExamActionPrimaryBtn");
    const actionPrimaryLabel = document.getElementById("snExamActionPrimaryLabel");
    const actionPrimaryIco = document.getElementById("snExamActionPrimaryIco");
    const actionSecondaryBtn = document.getElementById("snExamActionSecondaryBtn");
    const actionSecondaryLabel = document.getElementById("snExamActionSecondaryLabel");
    const actionSecondaryIco = document.getElementById("snExamActionSecondaryIco");

    const submittingOverlay = document.getElementById("snExamSubmittingOverlay");
    const submittingClock = document.getElementById("snExamSubmittingClock");
    const submittingDesc = document.getElementById("snExamSubmittingDesc");

    let activePrimaryAction = null;
    let activeSecondaryAction = null;
    let modalDismissible = true;

    const setCurrentPalette = (questionNo) => {
      paletteBtns.forEach((btn) => {
        const no = parseInt(btn.dataset.examJump || "0", 10);
        btn.classList.toggle("is-current", no === questionNo);
      });
    };

    const refreshVisibleEditors = () => {
      if (typeof tinymce !== "undefined") {
        items.forEach((item) => {
          if (item.classList.contains("sn-exam-q-hidden")) {
            return;
          }
          item.querySelectorAll(".session-quiz-essay-input").forEach((textarea) => {
            const editor = textarea.id ? tinymce.get(textarea.id) : null;
            if (editor && typeof editor.execCommand === "function") {
              try {
                editor.execCommand("mceAutoResize");
              } catch (_error) {
                // ignore
              }
            }
          });
        });
      }

      window.setTimeout(() => {
        window.dispatchEvent(new Event("resize"));
      }, 40);
    };

    const buildPageTokens = (page, total) => {
      if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
      }

      const tokens = [1];

      if (page > 3) {
        tokens.push("…");
      }

      const start = Math.max(2, page - 1);
      const end = Math.min(total - 1, page + 1);

      for (let p = start; p <= end; p++) {
        tokens.push(p);
      }

      if (page < total - 2) {
        tokens.push("…");
      }

      tokens.push(total);
      return tokens;
    };

    const renderPagination = () => {
      if (!paginationEl) {
        return;
      }

      if (totalPages <= 1) {
        paginationEl.hidden = true;
        paginationEl.innerHTML = "";
        return;
      }

      paginationEl.hidden = false;
      paginationEl.innerHTML = "";

      const prevBtn = document.createElement("button");
      prevBtn.type = "button";
      prevBtn.className = "sn-exam-page-btn";
      prevBtn.disabled = currentPage <= 1;
      prevBtn.setAttribute("aria-label", "Trang trước");
      prevBtn.innerHTML =
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M14.5 6.5L9 12L14.5 17.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      prevBtn.addEventListener("click", () => goToPage(currentPage - 1, { scrollTop: true }));
      paginationEl.appendChild(prevBtn);

      buildPageTokens(currentPage, totalPages).forEach((token) => {
        if (token === "…") {
          const dots = document.createElement("span");
          dots.className = "sn-exam-page-ellipsis";
          dots.textContent = "…";
          dots.setAttribute("aria-hidden", "true");
          paginationEl.appendChild(dots);
          return;
        }

        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "sn-exam-page-btn" + (token === currentPage ? " is-active" : "");
        btn.textContent = String(token);
        btn.setAttribute("aria-label", "Trang " + token);
        if (token === currentPage) {
          btn.setAttribute("aria-current", "page");
        }
        btn.addEventListener("click", () => goToPage(token, { scrollTop: true }));
        paginationEl.appendChild(btn);
      });

      const nextBtn = document.createElement("button");
      nextBtn.type = "button";
      nextBtn.className = "sn-exam-page-btn";
      nextBtn.disabled = currentPage >= totalPages;
      nextBtn.setAttribute("aria-label", "Trang sau");
      nextBtn.innerHTML =
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9.5 6.5L15 12L9.5 17.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      nextBtn.addEventListener("click", () => goToPage(currentPage + 1, { scrollTop: true }));
      paginationEl.appendChild(nextBtn);
    };

    const goToPage = (page, options = {}) => {
      const next = Math.min(totalPages, Math.max(1, page));
      currentPage = next;

      const startIdx = (currentPage - 1) * perPage;
      const endIdx = startIdx + perPage - 1;

      items.forEach((item, index) => {
        const hidden = index < startIdx || index > endIdx;
        item.classList.toggle("sn-exam-q-hidden", hidden);
      });

      renderPagination();
      refreshVisibleEditors();

      if (!options.keepQuestionNo) {
        setCurrentPalette(Math.min(totalQuestions, startIdx + 1));
      }

      if (options.scrollTop) {
        focusRoot.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    };

    const jumpToQuestion = (questionNo) => {
      const index = Math.min(totalQuestions - 1, Math.max(0, questionNo - 1));
      const targetItem = items[index];

      if (!targetItem) {
        return;
      }

      const targetPage = Math.floor(index / perPage) + 1;
      goToPage(targetPage, { scrollTop: false, keepQuestionNo: true });
      setCurrentPalette(index + 1);

      window.requestAnimationFrame(() => {
        targetItem.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    };

    let evaluating = false;

    const evaluateCounts = () => {
      if (typeof window.snEvaluateQuizCompletion !== "function") {
        return { done: 0, total: totalQuestions, rows: [] };
      }

      // snEvaluateQuizCompletion phát lại sự kiện input → chặn đệ quy tự kích hoạt
      evaluating = true;
      let rows;

      try {
        rows = window.snEvaluateQuizCompletion(form);
      } finally {
        evaluating = false;
      }

      let done = 0;

      rows.forEach((row) => {
        if (row.complete) {
          done++;
        }
      });

      return { done, total: rows.length || totalQuestions, rows };
    };

    let refreshTimer = 0;
    const refreshPalette = () => {
      if (document.hidden) {
        return;
      }

      const { done, total, rows } = evaluateCounts();

      const doneByNo = new Map(rows.map((row) => [row.questionNo, Boolean(row.complete)]));
      paletteBtns.forEach((btn) => {
        btn.classList.toggle("is-done", doneByNo.get(parseInt(btn.dataset.examJump || "0", 10)) === true);
      });

      if (progressText) {
        progressText.textContent = `${done}/${total}`;
      }

      if (progressFill) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        progressFill.style.width = `${pct}%`;
      }
    };

    const scheduleRefreshPalette = () => {
      if (evaluating) {
        return;
      }

      window.clearTimeout(refreshTimer);
      refreshTimer = window.setTimeout(refreshPalette, 250);
    };

    paletteBtns.forEach((btn) => {
      btn.addEventListener("click", () => {
        const no = parseInt(btn.dataset.examJump || "1", 10);
        jumpToQuestion(no);
      });
    });

    items.forEach((item, index) => {
      const no = index + 1;
      item.addEventListener("focusin", () => setCurrentPalette(no));
      item.addEventListener("click", () => setCurrentPalette(no));
    });

    form.addEventListener("input", scheduleRefreshPalette);
    form.addEventListener("change", scheduleRefreshPalette);

    const openActionModal = (config) => {
      if (!actionModal) {
        return;
      }

      modalDismissible = config.dismissible !== false;
      activePrimaryAction = config.onPrimary || null;
      activeSecondaryAction = config.onSecondary || null;

      if (actionTitle) {
        actionTitle.textContent = config.title || "";
        actionTitle.classList.toggle("is-danger", Boolean(config.dangerTitle));
      }

      if (actionDesc) {
        actionDesc.textContent = config.desc || "";
      }

      if (actionImg && config.img) {
        actionImg.src = config.img;
      }

      if (actionMascotWrap) {
        actionMascotWrap.classList.toggle("is-wide", Boolean(config.wideMascot));
      }

      if (actionBtnsWrap) {
        actionBtnsWrap.hidden = Boolean(config.hideButtons);
      }

      if (actionPrimaryLabel) {
        actionPrimaryLabel.textContent = config.primaryLabel || "";
      }

      if (actionPrimaryIco) {
        actionPrimaryIco.innerHTML = config.primaryIcon || "";
      }

      if (actionSecondaryLabel) {
        actionSecondaryLabel.textContent = config.secondaryLabel || "";
      }

      if (actionSecondaryIco) {
        actionSecondaryIco.innerHTML = config.secondaryIcon || "";
      }

      actionModal.hidden = false;
      document.body.style.overflow = "hidden";

      if (!config.hideButtons) {
        actionPrimaryBtn?.focus();
      }
    };

    const closeActionModal = () => {
      if (!actionModal) {
        return;
      }

      actionModal.hidden = true;
      document.body.style.overflow = "";
      activePrimaryAction = null;
      activeSecondaryAction = null;
    };

    actionPrimaryBtn?.addEventListener("click", () => {
      const fn = activePrimaryAction;
      closeActionModal();
      fn?.();
    });

    actionSecondaryBtn?.addEventListener("click", () => {
      const fn = activeSecondaryAction;
      closeActionModal();
      fn?.();
    });

    actionModal?.addEventListener("click", (event) => {
      if (event.target === actionModal && modalDismissible) {
        closeActionModal();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && actionModal && !actionModal.hidden && modalDismissible) {
        closeActionModal();
      }
    });

    const flushAutosaveSafe = () => {
      if (typeof tinymce !== "undefined") {
        try {
          tinymce.triggerSave();
        } catch (_error) {
          // ignore
        }
      }

      if (typeof window.quizAutosaveFlush === "function") {
        window.quizAutosaveFlush();
      }

      if (typeof window.assessmentAutosaveFlush === "function") {
        window.assessmentAutosaveFlush();
      }
    };

    const showSubmittingOverlay = () => {
      if (!submittingOverlay) {
        return;
      }

      const { done, total } = evaluateCounts();
      const timerLabel = document.querySelector("[data-quiz-timer] [data-timer-label]");

      if (submittingClock && timerLabel) {
        submittingClock.textContent = timerLabel.textContent || "00:00";
      }

      if (submittingDesc) {
        submittingDesc.textContent =
          `Đang gửi ${done}/${total} câu trả lời. Vui lòng chờ, không đóng trang hoặc nộp lại để tránh gửi trùng.`;
      }

      submittingOverlay.hidden = false;
      document.body.style.overflow = "hidden";
    };

    const performFinalSubmit = ({ timedOut, onConfirmSubmit }) => {
      flushAutosaveSafe();

      if (window.AssessmentOffline?.isOffline?.()) {
        window.AssessmentOffline.queueSubmit({ timedOut: Boolean(timedOut) });
        return;
      }

      if (typeof window.assessmentPermitLeave === "function") {
        window.assessmentPermitLeave();
      }

      showSubmittingOverlay();

      if (typeof onConfirmSubmit === "function") {
        onConfirmSubmit();
        return;
      }

      form.dataset.snConfirmedSubmit = "1";
      HTMLFormElement.prototype.submit.call(form);
    };

    const revealTargetOnCorrectPage = (targetEl, fallbackFn) => {
      const parentItem = targetEl?.closest?.(".session-quiz-item");

      if (parentItem) {
        const idx = items.indexOf(parentItem);
        if (idx >= 0) {
          const targetPage = Math.floor(idx / perPage) + 1;
          goToPage(targetPage, { scrollTop: false, keepQuestionNo: true });
          setCurrentPalette(idx + 1);
        }
      }

      window.requestAnimationFrame(() => {
        fallbackFn?.();
      });
    };

    // Đăng ký hook Modal xác nhận cho session-quiz-submit-guard.js
    window.snCourseQuizSubmitHandler = ({
      targets,
      incompleteCount,
      onConfirmSubmit,
      onContinueIncomplete,
    }) => {
      if (!actionModal) {
        return false;
      }

      if (incompleteCount > 0) {
        openActionModal({
          title: "Bạn vẫn muốn nộp bài?",
          desc: "Bạn chưa trả lời hết các câu hỏi. Bạn muốn tiếp tục làm hay nộp bài ngay?",
          img: actionModal.dataset.imgIncomplete,
          primaryLabel: "Tiếp tục làm",
          primaryIcon: ICON_ARROW_WHITE_CIRCLE,
          secondaryLabel: "Nộp bài",
          secondaryIcon: ICON_ARROW_UP_RIGHT,
          onPrimary: () => revealTargetOnCorrectPage(targets?.[0], onContinueIncomplete),
          onSecondary: () => performFinalSubmit({ timedOut: false, onConfirmSubmit }),
        });
      } else {
        openActionModal({
          title: "Xác nhận nộp bài?",
          desc: "Bạn chắc chắn muốn nộp bài? Sau khi nộp, bạn không thể chỉnh sửa câu trả lời.",
          img: actionModal.dataset.imgSubmit,
          primaryLabel: "Nộp bài",
          primaryIcon: ICON_ARROW_WHITE_CIRCLE,
          secondaryLabel: "Tiếp tục làm bài",
          secondaryIcon: ICON_RETURN_DARK,
          onPrimary: () => performFinalSubmit({ timedOut: false, onConfirmSubmit }),
          onSecondary: () => {},
        });
      }

      return true;
    };

    // Chặn 1 nhịp khi hết thời gian để hiển thị modal "Đã hết thời gian!" (#468:39427) trước khi tự nộp
    form.addEventListener(
      "submit",
      (event) => {
        const timedOutInput = form.querySelector(".js-timed-out, [name='timed_out']");
        const isTimedOut = timedOutInput && timedOutInput.value === "1";

        if (!isTimedOut || form.dataset.snTimeoutHandled === "1") {
          return;
        }

        if (window.AssessmentOffline?.isOffline?.()) {
          return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        form.dataset.snTimeoutHandled = "1";

        openActionModal({
          title: "Đã hết thời gian!",
          desc: "Thời gian làm bài đã kết thúc. Hệ thống sẽ tự động nộp bài của bạn.",
          img: actionModal?.dataset.imgTimeout,
          dangerTitle: true,
          wideMascot: true,
          hideButtons: true,
          dismissible: false,
        });

        flushAutosaveSafe();

        window.setTimeout(() => {
          closeActionModal();
          performFinalSubmit({ timedOut: true });
        }, 1400);
      },
      true
    );

    // Khởi tạo trang 1 và đồng bộ tiến độ
    goToPage(1, { scrollTop: false });
    refreshPalette();
    window.setTimeout(refreshPalette, 450);

    // Widget kéo-thả/ghi âm có thể đổi đáp án mà không phát input → quét thưa 4 giây (chỉ khi tab hiển thị)
    const pollId = window.setInterval(refreshPalette, 4000);
    document.addEventListener("visibilitychange", () => {
      if (!document.hidden) {
        refreshPalette();
      }
    });
    window.addEventListener("pagehide", () => window.clearInterval(pollId));
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      initWaitingRoom();
      initExamTake();
    });
  } else {
    initWaitingRoom();
    initExamTake();
  }
})();
