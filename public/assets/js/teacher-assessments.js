(() => {
  document.querySelectorAll("[data-course-switcher]").forEach((select) => {
    select.addEventListener("change", () => {
      if (select.value) {
        window.location.href = select.value;
      }
    });
  });

  const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

  const formatPoints = (value) => {
    const formatted = (Math.round(Number(value) * 100) / 100).toFixed(2);

    return formatted.replace(/0+$/, "").replace(/\.$/, "");
  };

  const parseNumber = (value) => {
    const parsed = parseFloat(String(value ?? "").replace(",", "."));

    return Number.isFinite(parsed) ? parsed : 0;
  };

  const isIncompleteDecimal = (value) => {
    const raw = String(value ?? "").trim();

    return raw === "" || raw === "." || raw === "," || /^-?\d+[.,]$/.test(raw);
  };

  const nowLabel = () => {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, "0");

    return `${pad(now.getHours())}:${pad(now.getMinutes())} ${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()}`;
  };

  const associatedForm = (target) => {
    if (
      target instanceof HTMLInputElement ||
      target instanceof HTMLTextAreaElement ||
      target instanceof HTMLSelectElement ||
      target instanceof HTMLButtonElement
    ) {
      return target.form;
    }

    return null;
  };

  const namedControl = (form, name) => {
    const found = form.elements.namedItem(name);

    if (found instanceof RadioNodeList) {
      return found[0] instanceof HTMLElement ? found[0] : null;
    }

    return found instanceof HTMLElement ? found : null;
  };

  const gradeModeOf = (form) => String(namedControl(form, "grade_mode")?.value || "");

  const setStatus = (form, text, isError = false) => {
    const status = form.querySelector("[data-autosave-status]");

    if (!status) {
      return;
    }

    status.hidden = false;
    status.classList.toggle("is-error", isError);
    status.innerHTML = isError
      ? `<i class="fa-solid fa-circle-exclamation"></i> ${text}`
      : `<i class="fa-solid fa-circle-check"></i> ${text}`;
  };

  const updatePendingUi = (pending) => {
    if (pending === null || pending === undefined) {
      return;
    }

    const pendingEl = document.querySelector(".session-post-quiz-pending");

    if (pendingEl) {
      pendingEl.textContent = pending > 0 ? `Còn ${pending} câu chưa chấm` : "Chờ công bố kết quả";
    }

    document.querySelectorAll(".teacher-assessment-grade-actions form").forEach((publishForm) => {
      const button = publishForm.querySelector('button[type="submit"]');
      const isPublish = button && /Công bố/.test(button.textContent || "");

      if (!isPublish || !button) {
        return;
      }

      button.disabled = pending > 0;
      button.title = pending > 0 ? "Còn câu chưa chấm" : "";
    });
  };

  const updateLiveTotals = (form, data) => {
    const totalEl = document.querySelector("[data-assessment-live-total]");

    if (data.complete && data.question_earned !== null && data.question_earned !== undefined) {
      form.dataset.savedEarned = formatPoints(data.question_earned);
    }

    if (totalEl && data.points_earned !== null && data.points_earned !== undefined) {
      totalEl.dataset.pageEarned = formatPoints(data.points_earned);
      const liveEarned = totalEl.querySelector("[data-live-earned]");

      if (liveEarned && !form.hasAttribute("data-word-translate-live-score")) {
        liveEarned.textContent = formatPoints(data.points_earned);
      }
    }

    window.dispatchEvent(new CustomEvent("assessment-grade-saved"));
  };

  const POINTS_INPUT_SELECTOR =
    "input[name='points_earned'], input[data-composite-item-points], input[data-composite-part-points]";

  const inputMaxPoints = (input) => {
    const raw = input.getAttribute("max");

    if (raw === null || String(raw).trim() === "") {
      return null;
    }

    return Math.max(0, parseNumber(raw));
  };

  const clampPointsInput = (input, rewrite) => {
    if (!(input instanceof HTMLInputElement)) {
      return 0;
    }

    const max = inputMaxPoints(input);
    const next = max === null ? Math.max(0, parseNumber(input.value)) : Math.max(0, Math.min(max, parseNumber(input.value)));

    if (rewrite && !input.readOnly) {
      input.value = formatPoints(next);
    }

    return next;
  };

  const exceedsPointsLimit = (input) => {
    if (!(input instanceof HTMLInputElement) || input.readOnly || isIncompleteDecimal(input.value)) {
      return false;
    }

    const max = inputMaxPoints(input);
    const value = parseNumber(input.value);

    return value < 0 || (max !== null && value > max);
  };

  const clampFormPoints = (form) => {
    [...form.elements].forEach((el) => {
      if (el instanceof HTMLInputElement && el.matches(POINTS_INPUT_SELECTOR) && !isIncompleteDecimal(el.value)) {
        clampPointsInput(el, true);
      }
    });
  };

  const saveForm = async (form) => {
    if (typeof window.tinymce !== "undefined") {
      window.tinymce.triggerSave();
    }

    if (form.dataset.autosaving === "1") {
      form.dataset.autosaveQueued = "1";
      return;
    }

    const isWordTranslate = gradeModeOf(form) === "word_translate";

    if (isWordTranslate) {
      const hasVerdict = [...form.elements].some(
        (el) => el instanceof HTMLInputElement && el.matches('input[type="radio"][name*="[is_correct]"]:checked')
      );

      if (!hasVerdict) {
        return;
      }
    } else {
      const pointsInput = namedControl(form, "points_earned");

      if (pointsInput instanceof HTMLInputElement && isIncompleteDecimal(pointsInput.value)) {
        return;
      }

      const unfinishedItem = [...form.elements].some(
        (el) =>
          el instanceof HTMLInputElement &&
          (el.hasAttribute("data-composite-item-points") || el.hasAttribute("data-composite-part-points")) &&
          !el.readOnly &&
          isIncompleteDecimal(el.value)
      );

      if (unfinishedItem) {
        return;
      }

      clampFormPoints(form);
    }

    form.dataset.autosaving = "1";
    setStatus(form, "Đang lưu…");

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        credentials: "same-origin",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
          "X-CSRF-TOKEN": csrfToken(),
        },
      });

      const data = await response.json().catch(() => null);

      if (!response.ok || !data?.ok) {
        setStatus(form, data?.error || "Không lưu được.", true);
        return;
      }

      setStatus(form, `Đã lưu lúc ${nowLabel()}`);
      updateLiveTotals(form, data);
      updatePendingUi(data.pending_manual);

      if (data.question_earned !== null && data.question_earned !== undefined) {
        const pointsInput = namedControl(form, "points_earned");

        if (pointsInput instanceof HTMLInputElement && !pointsInput.readOnly) {
          pointsInput.value = formatPoints(data.question_earned);
        }
      }

      const statusBadge = form
        .closest(".teacher-word-translate-grade-edit, .assessment-question-grade-edit")
        ?.querySelector(".teacher-essay-grade-status");

      if (statusBadge && data.complete) {
        statusBadge.classList.remove("is-pending");
        statusBadge.classList.add("is-done");
      }
    } catch (error) {
      setStatus(form, "Không lưu được.", true);
    } finally {
      form.dataset.autosaving = "0";

      if (form.dataset.autosaveQueued === "1") {
        form.dataset.autosaveQueued = "0";
        saveForm(form);
      }
    }
  };

  const debounceSave = (form, delay) => {
    clearTimeout(Number(form.dataset.autosaveTimer || 0));
    form.dataset.autosaveTimer = String(window.setTimeout(() => saveForm(form), delay));
  };

  document.querySelectorAll("form[data-assessment-autosave]").forEach((form) => {
    const submit = form.querySelector("[data-autosave-submit]");

    if (submit) {
      submit.hidden = true;
    }

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      saveForm(form);
    });
  });

  document.addEventListener(
    "change",
    (event) => {
      const form = associatedForm(event.target);

      if (!form || !form.hasAttribute("data-assessment-autosave")) {
        return;
      }

      if (event.target.matches(POINTS_INPUT_SELECTOR) && !isIncompleteDecimal(event.target.value)) {
        clampPointsInput(event.target, true);
      }

      if (event.target.matches('input[type="radio"], input[type="checkbox"], input[name="points_earned"], input[data-composite-item-points], input[data-composite-part-points]')) {
        saveForm(form);
      }
    },
    true
  );

  document.addEventListener(
    "input",
    (event) => {
      const form = associatedForm(event.target);

      if (!form || !form.hasAttribute("data-assessment-autosave")) {
        return;
      }

      if (event.target.matches(POINTS_INPUT_SELECTOR) && exceedsPointsLimit(event.target)) {
        clampPointsInput(event.target, true);
      }

      if (event.target.matches("textarea, input[name='points_earned'], input[data-composite-item-points], input[data-composite-part-points]")) {
        debounceSave(form, event.target.matches("textarea") ? 800 : 500);
      }
    },
    true
  );

  document.addEventListener(
    "blur",
    (event) => {
      if (!(event.target instanceof HTMLInputElement) || !event.target.matches(POINTS_INPUT_SELECTOR)) {
        return;
      }

      if (!isIncompleteDecimal(event.target.value)) {
        clampPointsInput(event.target, true);
      }
    },
    true
  );

  const wordTranslateForms = [...document.querySelectorAll("[data-word-translate-live-score]")];
  const totalEl = document.querySelector("[data-assessment-live-total]");

  const earnedFromWordTranslate = (form) => {
    const maxPoints = parseNumber(form.dataset.maxPoints);
    const itemCount = Math.max(0, parseInt(form.dataset.itemCount || "0", 10));
    const correctCount = form.querySelectorAll('input[type="radio"][name*="[is_correct]"][value="1"]:checked').length;

    if (itemCount <= 0) {
      return 0;
    }

    return Math.round(Math.max(0, Math.min(maxPoints, maxPoints * (correctCount / itemCount))) * 100) / 100;
  };

  const refreshWordTranslate = () => {
    wordTranslateForms.forEach((form) => {
      const itemPoints = parseNumber(form.dataset.maxPoints) / Math.max(1, parseInt(form.dataset.itemCount || "0", 10));
      const earned = earnedFromWordTranslate(form);

      form.querySelectorAll("[data-word-translate-grade-row]").forEach((row) => {
        const isCorrect = !!row.querySelector('input[type="radio"][name*="[is_correct]"][value="1"]:checked');
        const display = row.querySelector("[data-item-points-display]");

        if (display) {
          display.textContent = formatPoints(isCorrect ? itemPoints : 0);
          display.classList.toggle("is-earned", isCorrect);
        }
      });

      const questionTotal = form.querySelector("[data-question-live-earned]");

      if (questionTotal) {
        questionTotal.textContent = formatPoints(earned);
      }
    });

    if (!totalEl) {
      return;
    }

    let pageEarned = parseNumber(totalEl.dataset.pageEarned);

    wordTranslateForms.forEach((form) => {
      pageEarned = pageEarned - parseNumber(form.dataset.savedEarned) + earnedFromWordTranslate(form);
    });

    const liveEarned = totalEl.querySelector("[data-live-earned]");

    if (liveEarned) {
      liveEarned.textContent = formatPoints(Math.max(0, pageEarned));
    }
  };

  wordTranslateForms.forEach((form) => {
    form.addEventListener("change", (event) => {
      if (event.target instanceof HTMLInputElement && event.target.type === "radio") {
        refreshWordTranslate();
      }
    });
  });

  window.addEventListener("assessment-grade-saved", refreshWordTranslate);
  refreshWordTranslate();

  const clampItem = (input, rewrite) => clampPointsInput(input, rewrite);

  const syncComposite = (form, rewriteItems = false) => {
    const parent = namedControl(form, "points_earned");

    if (!(parent instanceof HTMLInputElement)) {
      return;
    }

    const itemInputs = [...form.elements].filter(
      (input) => input instanceof HTMLInputElement && input.hasAttribute("data-composite-item-points")
    );
    const partInputs = [...form.elements].filter(
      (input) => input instanceof HTMLInputElement && input.hasAttribute("data-composite-part-points")
    );
    const partsByItem = {};

    partInputs.forEach((input) => {
      const itemIndex = String(input.getAttribute("data-composite-item-index") || "");

      if (!itemIndex) {
        return;
      }

      if (!partsByItem[itemIndex]) {
        partsByItem[itemIndex] = 0;
      }

      partsByItem[itemIndex] += clampItem(input, rewriteItems && !isIncompleteDecimal(input.value) && !input.readOnly);
    });

    let sum = 0;

    itemInputs.forEach((input) => {
      const match = String(input.name || "").match(/item_grades\[(\d+)\]\[points\]/);
      const itemIndex = match ? match[1] : "";

      if (itemIndex && Object.prototype.hasOwnProperty.call(partsByItem, itemIndex)) {
        const itemMax = Math.max(0, parseNumber(input.getAttribute("max")));
        const next = Math.max(0, Math.min(itemMax, partsByItem[itemIndex]));
        input.value = formatPoints(next);
        sum += next;
        return;
      }

      sum += clampItem(input, rewriteItems && !isIncompleteDecimal(input.value) && !input.readOnly);
    });

    const maxPoints = Math.max(0, parseNumber(form.dataset.maxPoints || parent.getAttribute("max")));
    parent.value = formatPoints(Math.max(0, Math.min(maxPoints, sum)));
  };

  document.querySelectorAll("[data-composite-item-live-score]").forEach((form) => syncComposite(form, true));

  document.addEventListener(
    "input",
    (event) => {
      if (
        !(event.target instanceof HTMLInputElement) ||
        !event.target.matches("[data-composite-item-points], [data-composite-part-points]")
      ) {
        return;
      }

      if (exceedsPointsLimit(event.target)) {
        clampPointsInput(event.target, true);
      }

      if (event.target.form?.hasAttribute("data-composite-item-live-score")) {
        syncComposite(event.target.form, false);
      }
    },
    true
  );

  document.addEventListener(
    "change",
    (event) => {
      if (
        !(event.target instanceof HTMLInputElement) ||
        !event.target.matches("[data-composite-item-points], [data-composite-part-points]")
      ) {
        return;
      }

      if (event.target.form?.hasAttribute("data-composite-item-live-score")) {
        syncComposite(event.target.form, true);
      }
    },
    true
  );

  document.querySelectorAll("[data-fill-blanks-live-score]").forEach((form) => {
    const earnedFromForm = () => {
      const maxPoints = Math.max(0, parseNumber(form.dataset.maxPoints));
      const blankCount = Math.max(0, parseInt(form.dataset.blankCount || "0", 10));
      const correctCount = form.querySelectorAll("input[data-fill-blank-correct]:checked").length;

      if (blankCount <= 0) {
        return 0;
      }

      return Math.round(Math.max(0, Math.min(maxPoints, maxPoints * (correctCount / blankCount))) * 100) / 100;
    };

    const syncMarks = () => {
      form.querySelectorAll(".session-blanks-grade-mark").forEach((label) => {
        const checkbox = label.querySelector("input[data-fill-blank-correct]");
        label.classList.toggle("is-checked", !!(checkbox && checkbox.checked));
      });
    };

    const syncPoints = () => {
      const input = form.querySelector('input[name="points_earned"]');

      if (input instanceof HTMLInputElement) {
        input.value = formatPoints(earnedFromForm());
      }
    };

    syncMarks();

    if (form.getAttribute("data-sync-from-blanks") === "1") {
      syncPoints();
    }

    form.addEventListener(
      "change",
      (event) => {
        if (event.target instanceof HTMLInputElement && event.target.matches("[data-fill-blank-correct]")) {
          syncMarks();
          syncPoints();
        }
      },
      true
    );
  });

  const STORAGE_KEY = "assessmentGradeScroll";
  const currentKey = () => window.location.pathname + window.location.search;

  document.querySelectorAll(".assessment-question-grade-form, .teacher-word-translate-grade-form").forEach((form) => {
    form.addEventListener("submit", () => {
      try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ y: window.scrollY, href: currentKey() }));
      } catch (e) {
        /* ignore */
      }
    });
  });

  const applyScroll = () => {
    try {
      const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || "null");
      sessionStorage.removeItem(STORAGE_KEY);

      if (saved && saved.href === currentKey() && typeof saved.y === "number") {
        window.scrollTo(0, saved.y);
        return;
      }
    } catch (e) {
      /* ignore */
    }

    const hash = window.location.hash || "";

    if (!hash.startsWith("#cau-")) {
      return;
    }

    const target = document.getElementById(hash.slice(1));

    if (target) {
      window.scrollTo(0, Math.max(0, target.getBoundingClientRect().top + window.scrollY - 96));
    }
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", applyScroll);
  } else {
    applyScroll();
  }

  window.addEventListener("load", applyScroll);
})();
