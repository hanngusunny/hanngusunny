/**
 * SUNNY — Tương tác màn Chấm điểm Giáo viên hợp nhất
 * 1) Danh sách bài nộp (#636:53389): popover lọc & sắp xếp, dropdown chọn khóa học
 * 2) Tiện ích xoay ảnh bài làm tự luận (.js-essay-answer-images)
 * 3) Màn chấm bài (#909:123422 & #909:122552): Chấm tay / Tự động, điều hướng câu, AJAX lưu điểm
 * 4) Màn xem kết quả chỉ xem (#652:29958): lọc 3 tab Tất cả / Tự động / Giáo viên chấm & nhảy câu
 */
(() => {
  const filterToggle = document.getElementById("snTgFilterToggle");
  const filterMenu = document.getElementById("snTgFilterMenu");
  const courseDropdowns = Array.from(document.querySelectorAll(".sn-tg-course-dd"));

  const closeFilterMenu = () => {
    if (!filterMenu || !filterToggle) return;
    filterMenu.hidden = true;
    filterToggle.setAttribute("aria-expanded", "false");
  };

  if (filterToggle && filterMenu) {
    filterToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      const willOpen = filterMenu.hidden;
      courseDropdowns.forEach((dd) => dd.removeAttribute("open"));
      filterMenu.hidden = !willOpen;
      filterToggle.setAttribute("aria-expanded", willOpen ? "true" : "false");
    });

    filterMenu.addEventListener("click", (event) => {
      event.stopPropagation();
    });
  }

  document.addEventListener("click", (event) => {
    if (filterMenu && !filterMenu.hidden && !event.target.closest(".sn-tg-filter-wrap")) {
      closeFilterMenu();
    }
    courseDropdowns.forEach((dd) => {
      if (dd.hasAttribute("open") && !dd.contains(event.target)) {
        dd.removeAttribute("open");
      }
    });
  });

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    closeFilterMenu();
    courseDropdowns.forEach((dd) => dd.removeAttribute("open"));
  });
})();

(() => {
  const wraps = [];

  const applyRotation = (wrap) => {
    const frame = wrap.querySelector(".tg-essay-image-frame");
    const img = wrap.querySelector("img");
    if (!frame || !img) {
      return;
    }

    const deg = Number(wrap.dataset.rotation || 0) % 360;
    const sideways = deg % 180 !== 0;

    img.style.transform = "";
    img.style.position = "";
    img.style.left = "";
    img.style.top = "";
    img.style.removeProperty("width");
    img.style.removeProperty("height");
    img.style.removeProperty("max-width");
    frame.style.width = "";
    frame.style.height = "";

    if (!sideways) {
      img.style.transform = deg === 180 ? "rotate(180deg)" : "";
      return;
    }

    const boxW = img.offsetWidth;
    const boxH = img.offsetHeight;
    if (!boxW || !boxH) {
      return;
    }

    const maxW = wrap.clientWidth || boxW;
    let frameW = boxH;
    let frameH = boxW;
    let scale = 1;

    if (frameW > maxW) {
      scale = maxW / frameW;
      frameW = maxW;
      frameH = boxW * scale;
    }

    frame.style.width = `${frameW}px`;
    frame.style.height = `${frameH}px`;
    img.style.position = "absolute";
    img.style.left = "50%";
    img.style.top = "50%";
    img.style.setProperty("width", `${boxW}px`, "important");
    img.style.setProperty("height", `${boxH}px`, "important");
    img.style.setProperty("max-width", "none", "important");
    img.style.transform = `translate(-50%, -50%) rotate(${deg}deg) scale(${scale})`;
  };

  const enhanceImage = (img) => {
    if (img.closest(".tg-essay-image") || img.classList.contains("qb-option-image")) {
      return;
    }

    const wrap = document.createElement("span");
    wrap.className = "tg-essay-image";
    wrap.dataset.rotation = "0";

    const frame = document.createElement("span");
    frame.className = "tg-essay-image-frame";

    const button = document.createElement("button");
    button.type = "button";
    button.className = "tg-essay-image-rotate";
    button.setAttribute("aria-label", "Xoay ảnh 90 độ");
    button.innerHTML = '<i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Xoay ảnh';

    img.replaceWith(wrap);
    frame.appendChild(img);
    wrap.appendChild(frame);
    wrap.appendChild(button);
    wraps.push(wrap);

    const rotate = () => {
      const next = (Number(wrap.dataset.rotation || 0) + 90) % 360;
      wrap.dataset.rotation = String(next);
      applyRotation(wrap);
    };

    button.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      rotate();
    });

    if (img.complete) {
      applyRotation(wrap);
    } else {
      img.addEventListener("load", () => applyRotation(wrap), { once: true });
    }
  };

  document.querySelectorAll(".js-essay-answer-images img").forEach(enhanceImage);

  let resizeTimer = 0;
  window.addEventListener("resize", () => {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(() => {
      wraps.forEach(applyRotation);
    }, 120);
  });
})();

(() => {
  const workspace = document.getElementById("snTgGradingWorkspace");
  if (!workspace) {
    return;
  }

  const formatScoreVi = (value) => {
    if (value === null || value === undefined || value === "") {
      return "—";
    }
    const num = Number(String(value).replace(",", "."));
    if (!Number.isFinite(num)) {
      return "—";
    }
    const rounded = Math.round(num * 100) / 100;
    return String(rounded).replace(".", ",");
  };

  const parseDecimal = (raw) => {
    const cleaned = String(raw ?? "").trim().replace(",", ".");
    if (cleaned === "") {
      return NaN;
    }
    return Number(cleaned);
  };

  const clampPointsInput = (input) => {
    const val = parseDecimal(input.value);
    if (!Number.isFinite(val)) {
      return NaN;
    }
    const maxAttr = parseDecimal(input.getAttribute("max"));
    let clamped = Math.max(0, val);
    if (Number.isFinite(maxAttr) && maxAttr >= 0) {
      clamped = Math.min(maxAttr, clamped);
    }
    clamped = Math.round(clamped * 100) / 100;
    if (clamped !== val) {
      input.value = String(clamped);
    }
    return clamped;
  };

  const computeScoreState = (earned, maxPoints) => {
    if (earned === null || earned === undefined || !Number.isFinite(Number(earned))) {
      return "pending";
    }
    const e = Number(earned);
    const m = Number(maxPoints);
    if (m > 0 && e >= m - 0.01) {
      return "full";
    }
    if (e > 0.001) {
      return "partial";
    }
    return "zero";
  };

  const modeTabs = Array.from(workspace.querySelectorAll("[data-grade-tab]"));
  const modePanes = Array.from(workspace.querySelectorAll("[data-grade-pane]"));

  const switchMode = (targetMode) => {
    modeTabs.forEach((btn) => {
      const isMatch = btn.dataset.gradeTab === targetMode;
      btn.classList.toggle("is-active", isMatch);
      btn.setAttribute("aria-selected", isMatch ? "true" : "false");
    });
    modePanes.forEach((pane) => {
      pane.hidden = pane.dataset.gradePane !== targetMode;
    });
  };

  modeTabs.forEach((btn) => {
    btn.addEventListener("click", () => {
      const target = btn.dataset.gradeTab || "manual";
      switchMode(target);
    });
  });

  workspace.querySelectorAll("[data-qnav-btn]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const mode = btn.dataset.qnavBtn;
      const idx = btn.dataset.qnavIndex;
      if (!mode) return;

      workspace.querySelectorAll(`[data-qnav-btn="${mode}"]`).forEach((sib) => {
        const active = sib === btn;
        sib.classList.toggle("is-active", active);
        sib.setAttribute("aria-selected", active ? "true" : "false");
      });

      workspace.querySelectorAll(`[data-qcard="${mode}"]`).forEach((card) => {
        card.hidden = card.dataset.qcardIndex !== idx;
      });
    });
  });

  const totalEarnedEl = workspace.querySelector("[data-total-earned]");
  const pendingHintEl = workspace.querySelector("[data-pending-hint]");
  const globalSaveStateEl = document.getElementById("snTgGlobalSaveState");
  const generalCommentEl = document.getElementById("snTgGeneralComment");
  const saveDraftBtn = document.getElementById("snTgSaveDraftBtn");
  const submitResultBtn = document.getElementById("snTgSubmitResultBtn");
  const publishForm = document.getElementById("snTgPublishForm");

  const setGlobalState = (text, tone) => {
    if (!globalSaveStateEl) return;
    globalSaveStateEl.textContent = text;
    globalSaveStateEl.classList.remove("is-saved", "is-error");
    if (tone === "saved") {
      globalSaveStateEl.classList.add("is-saved");
    } else if (tone === "error") {
      globalSaveStateEl.classList.add("is-error");
    }
  };

  const setFormStatus = (form, text, tone) => {
    const statusEl = form.querySelector("[data-form-status]");
    if (!statusEl) return;
    statusEl.textContent = text;
    statusEl.classList.remove("is-saved", "is-error");
    if (tone === "saved") {
      statusEl.classList.add("is-saved");
    } else if (tone === "error") {
      statusEl.classList.add("is-error");
    }
  };

  const isFormReadyForAutoSave = (form) => {
    const kind = form.dataset.gradeKind || "";
    if (kind === "word_translate" || kind === "composite_fill") {
      const radioNames = new Set();
      form.querySelectorAll('input[type="radio"]').forEach((r) => {
        if (r.name) radioNames.add(r.name);
      });
      if (radioNames.size === 0) return false;
      for (const name of radioNames) {
        if (!form.querySelector(`input[type="radio"][name="${CSS.escape(name)}"]:checked`)) {
          return false;
        }
      }
      return true;
    }

    if (kind === "essay" || kind === "phat_am") {
      return Boolean(form.querySelector('input[type="radio"][name="is_correct"]:checked'));
    }

    if (kind === "points") {
      const inp = form.querySelector("[data-question-points-input]");
      return Boolean(inp && Number.isFinite(parseDecimal(inp.value)));
    }

    if (kind === "composite_items") {
      const subInputs = Array.from(form.querySelectorAll("[data-sub-points]"));
      return subInputs.length > 0 && subInputs.some((inp) => Number.isFinite(parseDecimal(inp.value)));
    }

    return true;
  };

  const syncCompositeTotal = (form) => {
    const subInputs = Array.from(form.querySelectorAll("[data-sub-points]"));
    if (subInputs.length === 0) return;
    let sum = 0;
    subInputs.forEach((inp) => {
      const v = clampPointsInput(inp);
      if (Number.isFinite(v)) {
        sum += v;
      }
    });
    const hiddenTotal = form.querySelector('input[name="points_earned"][data-question-points-input]');
    if (hiddenTotal) {
      hiddenTotal.value = String(Math.round(sum * 100) / 100);
    }
  };

  const updateWordTranslateRowPreview = (form, radio) => {
    const row = radio.closest("tr[data-wt-row]");
    if (!row) return;
    const isOk = radio.value === "1";
    const maxPts = parseDecimal(form.dataset.maxPoints);
    const totalItems = Number(form.dataset.itemCount || 0);
    const itemWeight = totalItems > 0 && Number.isFinite(maxPts) ? Math.round((maxPts / totalItems) * 100) / 100 : 0;

    const studentEl = row.querySelector(".sn-tg-wt-student");
    if (studentEl) {
      studentEl.classList.toggle("is-correct", isOk);
      studentEl.classList.toggle("is-wrong", !isOk);
    }

    const ptsEl = row.querySelector("[data-wt-item-pts]");
    if (ptsEl) {
      ptsEl.textContent = `${formatScoreVi(isOk ? itemWeight : 0)} / ${formatScoreVi(itemWeight)}`;
      ptsEl.classList.toggle("is-zero", !isOk);
    }
  };

  const updateScoreBadge = (qId, earnedDisplay, scoreState) => {
    if (!qId) return;
    const box = workspace.querySelector(`[data-qscore-box="${qId}"]`);
    if (box) {
      box.textContent = earnedDisplay;
      box.classList.remove("is-full", "is-partial", "is-zero", "is-pending");
      box.classList.add(`is-${scoreState}`);
    }
  };

  const updateSummaryUi = (data) => {
    if (totalEarnedEl) {
      if (typeof data.points_earned_display === "string" && data.points_earned_display !== "") {
        totalEarnedEl.textContent = data.points_earned_display;
      } else if (data.points_earned !== null && data.points_earned !== undefined) {
        totalEarnedEl.textContent = formatScoreVi(data.points_earned);
      }
    }

    if (typeof data.view_url === "string" && data.view_url !== "") {
      workspace.dataset.viewUrl = data.view_url;
      if (submitResultBtn && submitResultBtn.dataset.finishUrl !== undefined) {
        submitResultBtn.dataset.finishUrl = data.view_url;
      }
    }

    if (data.pending_manual !== null && data.pending_manual !== undefined) {
      const pending = Math.max(0, Number(data.pending_manual));
      workspace.dataset.pendingManual = String(pending);

      if (pendingHintEl) {
        if (pending === 0) {
          pendingHintEl.textContent = "Tất cả câu hỏi đã có điểm · Sẵn sàng gửi kết quả";
          pendingHintEl.classList.add("is-ready");
        } else {
          const labels = Array.isArray(data.pending_manual_labels) ? data.pending_manual_labels : [];
          if (labels.length > 0) {
            const shown = labels.slice(0, 6).join(", ") + (labels.length > 6 ? "…" : "");
            pendingHintEl.textContent = `Còn ${pending} câu chấm tay (${shown}) chưa có điểm`;
          } else {
            pendingHintEl.textContent = `Còn ${pending} câu chấm tay chưa có điểm`;
          }
          pendingHintEl.classList.remove("is-ready");
        }
      }

      if (submitResultBtn) {
        submitResultBtn.disabled = pending > 0;
        submitResultBtn.classList.toggle("is-ready", pending === 0);
      }
    }
  };

  const saveForm = async (form, options = {}) => {
    const { applyGeneralComment = false } = options;

    if (form.dataset.saving === "1") {
      return false;
    }

    if (form.dataset.gradeKind === "composite_items") {
      syncCompositeTotal(form);
    }

    const singlePtsInput = form.querySelector('input[type="text"][data-question-points-input]');
    if (singlePtsInput) {
      clampPointsInput(singlePtsInput);
    }

    if (applyGeneralComment && generalCommentEl) {
      const generalText = generalCommentEl.value.trim();
      const commentField = form.querySelector('textarea[name="teacher_comment"]');
      if (generalText !== "" && commentField && commentField.value.trim() === "") {
        commentField.value = generalText;
      }
    }

    const formData = new FormData(form);
    for (const [key, value] of Array.from(formData.entries())) {
      if (
        typeof value === "string" &&
        (key === "points_earned" || key.endsWith("[points]"))
      ) {
        formData.set(key, value.trim().replace(",", "."));
      }
    }

    const saveBtn = form.querySelector('button[type="submit"]');
    form.dataset.saving = "1";
    if (saveBtn) saveBtn.disabled = true;
    setFormStatus(form, "Đang lưu…", "");
    setGlobalState("Đang lưu điểm…", "");

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
        },
        credentials: "same-origin",
      });

      let data = {};
      try {
        data = await response.json();
      } catch {
        data = { ok: false, error: "Phản hồi máy chủ không hợp lệ." };
      }

      if (!response.ok || !data.ok) {
        const errMsg = data.error || data.message || "Không thể lưu chấm bài.";
        setFormStatus(form, errMsg, "error");
        setGlobalState(errMsg, "error");
        return false;
      }

      form.dataset.dirty = "0";
      const qId = form.dataset.questionId || String(data.question_id || "");
      const maxPts = parseDecimal(form.dataset.maxPoints);
      const earnedDisplay =
        typeof data.question_earned_display === "string" && data.question_earned_display !== ""
          ? data.question_earned_display
          : formatScoreVi(data.question_earned);
      const scoreState =
        typeof data.score_state === "string" && data.score_state !== ""
          ? data.score_state
          : computeScoreState(data.question_earned, maxPts);

      updateScoreBadge(qId, earnedDisplay, scoreState);

      if (qId && data.complete !== false) {
        const navPill = workspace.querySelector(`[data-qnav-btn="manual"][data-qnav-qid="${qId}"]`);
        if (navPill) {
          navPill.classList.add("is-graded");
        }
      }

      updateSummaryUi(data);

      const savedMsg = data.message || "Đã lưu điểm";
      setFormStatus(form, savedMsg, "saved");
      const remaining = Number(workspace.dataset.pendingManual || 0);
      setGlobalState(
        remaining === 0 ? "Đã lưu đủ tất cả câu chấm tay" : "Đã lưu nháp",
        "saved"
      );

      return true;
    } catch {
      setFormStatus(form, "Lỗi kết nối. Vui lòng thử lưu lại.", "error");
      setGlobalState("Lỗi kết nối khi lưu điểm", "error");
      return false;
    } finally {
      form.dataset.saving = "0";
      if (saveBtn) saveBtn.disabled = false;
    }
  };

  const gradeForms = Array.from(workspace.querySelectorAll("[data-unified-grade-form]"));

  gradeForms.forEach((form) => {
    let debounceTimer = 0;

    const scheduleAutoSave = (delayMs) => {
      window.clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(() => {
        if (isFormReadyForAutoSave(form)) {
          saveForm(form);
        }
      }, delayMs);
    };

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      window.clearTimeout(debounceTimer);
      saveForm(form, { applyGeneralComment: true });
    });

    form.addEventListener("change", (event) => {
      const target = event.target;
      form.dataset.dirty = "1";

      if (target instanceof HTMLInputElement && target.type === "radio") {
        if (form.dataset.gradeKind === "word_translate") {
          updateWordTranslateRowPreview(form, target);
        }
        scheduleAutoSave(160);
        return;
      }

      if (target instanceof HTMLInputElement && target.hasAttribute("data-sub-points")) {
        syncCompositeTotal(form);
        scheduleAutoSave(400);
        return;
      }

      if (target instanceof HTMLInputElement && target.hasAttribute("data-question-points-input")) {
        clampPointsInput(target);
        scheduleAutoSave(400);
      }
    });

    form.addEventListener("input", (event) => {
      const target = event.target;
      form.dataset.dirty = "1";
      setFormStatus(form, "Chưa lưu thay đổi", "");

      if (target instanceof HTMLInputElement && target.hasAttribute("data-sub-points")) {
        syncCompositeTotal(form);
        scheduleAutoSave(750);
      } else if (target instanceof HTMLInputElement && target.hasAttribute("data-question-points-input")) {
        scheduleAutoSave(750);
      } else if (target instanceof HTMLTextAreaElement || target instanceof HTMLInputElement) {
        if (isFormReadyForAutoSave(form)) {
          scheduleAutoSave(900);
        }
      }
    });
  });

  const flushDirtyForms = async (applyGeneralComment) => {
    const dirtyForms = gradeForms.filter((f) => f.dataset.dirty === "1");
    if (dirtyForms.length === 0 && applyGeneralComment && generalCommentEl && generalCommentEl.value.trim() !== "") {
      const visibleManualCard = workspace.querySelector('[data-qcard="manual"]:not([hidden])');
      const activeForm = visibleManualCard?.querySelector("[data-unified-grade-form]");
      if (activeForm && isFormReadyForAutoSave(activeForm)) {
        return saveForm(activeForm, { applyGeneralComment: true });
      }
    }

    let allOk = true;
    for (const form of dirtyForms) {
      const ok = await saveForm(form, { applyGeneralComment });
      if (!ok) allOk = false;
    }
    return allOk;
  };

  if (saveDraftBtn) {
    saveDraftBtn.addEventListener("click", async () => {
      const dirtyForms = gradeForms.filter((f) => f.dataset.dirty === "1");
      if (dirtyForms.length > 0) {
        await flushDirtyForms(true);
        return;
      }

      const visibleManualCard = workspace.querySelector('[data-qcard="manual"]:not([hidden])');
      const activeForm = visibleManualCard?.querySelector("[data-unified-grade-form]");
      if (activeForm && isFormReadyForAutoSave(activeForm)) {
        await saveForm(activeForm, { applyGeneralComment: true });
      } else {
        setGlobalState("Đã lưu nháp hiện tại", "saved");
      }
    });
  }

  if (publishForm) {
    publishForm.addEventListener("submit", async (event) => {
      const hasDirty = gradeForms.some((f) => f.dataset.dirty === "1");
      if (!hasDirty) return;

      event.preventDefault();
      const ok = await flushDirtyForms(true);
      if (ok && Number(workspace.dataset.pendingManual || 0) === 0) {
        publishForm.submit();
      }
    });
  } else if (submitResultBtn && submitResultBtn.type === "button") {
    submitResultBtn.addEventListener("click", async () => {
      const ok = await flushDirtyForms(true);
      if (!ok) return;
      if (Number(workspace.dataset.pendingManual || 0) > 0) {
        setGlobalState("Vui lòng chấm đủ tất cả câu chấm tay trước khi gửi", "error");
        return;
      }
      const finishUrl = submitResultBtn.dataset.finishUrl || workspace.dataset.viewUrl;
      if (finishUrl) {
        window.location.href = finishUrl;
      }
    });
  }
})();

(() => {
  const viewRoot = document.getElementById("snTgReadOnlyView");
  if (!viewRoot) {
    return;
  }

  const filterBtns = Array.from(viewRoot.querySelectorAll("[data-view-filter]"));
  const sections = Array.from(viewRoot.querySelectorAll("[data-view-section]"));
  const jumpLinks = Array.from(viewRoot.querySelectorAll("[data-view-group]"));

  const applyViewFilter = (filterKey) => {
    filterBtns.forEach((btn) => {
      const active = btn.dataset.viewFilter === filterKey;
      btn.classList.toggle("is-active", active);
      btn.setAttribute("aria-selected", active ? "true" : "false");
    });

    sections.forEach((sec) => {
      sec.hidden = filterKey !== "all" && sec.dataset.viewSection !== filterKey;
    });

    jumpLinks.forEach((link) => {
      link.hidden = filterKey !== "all" && link.dataset.viewGroup !== filterKey;
    });
  };

  filterBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      applyViewFilter(btn.dataset.viewFilter || "all");
    });
  });

  jumpLinks.forEach((link) => {
    link.addEventListener("click", (event) => {
      const href = link.getAttribute("href") || "";
      if (!href.startsWith("#")) return;
      const target = document.getElementById(href.slice(1));
      if (!target) return;
      event.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  });
})();
