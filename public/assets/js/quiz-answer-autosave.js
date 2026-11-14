(() => {
  const tokenMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = () => (tokenMeta ? tokenMeta.getAttribute('content') : '');
  const completeSelector = [
    "[data-memory-complete-input]",
    "[data-word-link-complete-input]",
    "[data-word-order-complete-input]",
    "[data-category-sort-complete-input]",
    "[data-fill-blanks-complete-input]",
    "[data-word-select-complete-input]",
    "[data-word-translate-complete-input]",
    "[data-image-hotspot-complete-input]",
    "[data-image-label-drop-complete-input]",
    "[data-multi-select-complete-input]",
    "[data-listening-true-false-complete-input]",
    "[data-listening-multi-choice-complete-input]",
    "[data-session-listening-answer-output]",
    "[data-session-phat-am-answer-output]",
  ].join(", ");
  const hiddenInputSelector = [
    'input[type="hidden"][name^="answers["]',
    "input[data-word-translate-complete-input]",
    "input[data-fill-blanks-complete-input]",
    "input[data-memory-complete-input]",
    "input[data-word-link-complete-input]",
    "input[data-word-order-complete-input]",
    "input[data-category-sort-complete-input]",
    "input[data-word-select-complete-input]",
    "input[data-image-hotspot-complete-input]",
    "input[data-image-label-drop-complete-input]",
    "input[data-multi-select-complete-input]",
    "input[data-listening-true-false-complete-input]",
    "input[data-listening-multi-choice-complete-input]",
    "input[data-session-listening-answer-output]",
    "input[data-session-phat-am-answer-output]",
  ].join(", ");

  const hasEssayAnswer = (editor, textarea) => {
    const html = editor ? editor.getContent() : String(textarea?.value || "");
    const plain = editor
      ? editor.getContent({ format: "text" }).replace(/\u00a0/g, " ").trim()
      : html.replace(/<[^>]+>/g, " ").replace(/\u00a0/g, " ").replace(/\s+/g, " ").trim();

    return plain !== "" || /<img\b[^>]*\bsrc\s*=\s*["'][^"']+["']/i.test(html);
  };

  const wordTranslateHasProgress = (input) => {
    try {
      const parsed = JSON.parse(input.value || "");
      const translations = parsed?.translations;
      return Array.isArray(translations) && translations.some((item) => String(item || "").trim() !== "");
    } catch (_error) {
      return false;
    }
  };

  const syncCompositePayload = (item) => {
    const listeningWrap = item.querySelector("[data-session-listening-student]");
    if (listeningWrap && typeof window.syncListeningStudentPayload === "function") {
      window.syncListeningStudentPayload(listeningWrap);
    }

    const phatAmWrap = item.querySelector("[data-session-phat-am-student]");
    if (phatAmWrap && typeof window.syncPhatAmStudentPayload === "function") {
      window.syncPhatAmStudentPayload(phatAmWrap);
    }
  };

  const answerValue = (item) => {
    if (typeof tinymce !== "undefined") {
      tinymce.triggerSave();
    }

    const composite = item.querySelector(
      "[data-session-listening-answer-output], [data-session-phat-am-answer-output]"
    );

    if (composite) {
      syncCompositePayload(item);
      return String(composite.value || "").trim();
    }

    const essay = item.querySelector(".session-quiz-essay-input");
    if (essay) {
      const editor = typeof tinymce !== "undefined" && essay.id ? tinymce.get(essay.id) : null;
      if (!hasEssayAnswer(editor, essay)) {
        return "";
      }
      return editor ? editor.getContent() : String(essay.value || "").trim();
    }

    if (item.querySelector('input[type="radio"][name^="answers["]')) {
      const checked = item.querySelector('input[type="radio"][name^="answers["]:checked');
      return checked ? String(checked.value || "").trim() : "";
    }

    const translateInput = item.querySelector("[data-word-translate-complete-input]");
    if (translateInput) {
      return wordTranslateHasProgress(translateInput) ? String(translateInput.value || "").trim() : "";
    }

    const hidden = item.querySelector(".js-blank-value, .js-order-value");
    if (hidden && hidden.value) {
      return String(hidden.value).trim();
    }

    const complete = item.querySelector(completeSelector);
    if (complete) {
      return String(complete.value || "").trim();
    }

    const checks = item.querySelectorAll('input[type="checkbox"][name^="answers["]:checked');
    if (item.querySelector('input[type="checkbox"][name^="answers["]')) {
      return Array.from(checks)
        .map((input) => input.value)
        .filter(Boolean)
        .join(",");
    }

    const field = item.querySelector('textarea[name^="answers["], input[name^="answers["]');
    return field ? String(field.value || "").trim() : "";
  };

  const questionNo = (form, item) => {
    const fromData = Number(item.dataset.questionNo || 0);
    if (fromData > 0) {
      return fromData;
    }

    const items = Array.from(form.querySelectorAll(".session-quiz-item[data-question-id]"));
    return items.indexOf(item) + 1;
  };

  let toastTimer = 0;

  const showToast = (message, isError) => {
    if (window.TemplateUI?.toast) {
      TemplateUI.toast(isError ? "error" : "success", message);
      return;
    }

    let toast = document.querySelector(".session-quiz-autosave-toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.className = "session-quiz-autosave-toast";
      toast.setAttribute("role", "status");
      toast.setAttribute("aria-live", "polite");
      document.body.appendChild(toast);
    }

    toast.classList.toggle("is-error", Boolean(isError));
    toast.innerHTML =
      '<i class="bi ' +
      (isError ? "bi-exclamation-circle" : "bi-cloud-check") +
      '" aria-hidden="true"></i> ' +
      message;
    toast.classList.add("show");
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove("show"), 2400);
  };

  const markSaved = (item, saved) => {
    const badge = item.querySelector("[data-question-autosave-badge]");
    if (!badge) {
      return;
    }

    badge.hidden = !saved;
    badge.classList.remove("is-error", "is-local");
    if (saved) {
      badge.innerHTML = '<i class="bi bi-cloud-check" aria-hidden="true"></i> Đã lưu';
    }
  };

  const markSavedLocal = (item) => {
    const badge = item.querySelector("[data-question-autosave-badge]");
    if (!badge) {
      return;
    }

    badge.hidden = false;
    badge.classList.remove("is-error");
    badge.classList.add("is-local");
    badge.innerHTML = '<i class="fa-solid fa-laptop" aria-hidden="true"></i> Đã lưu máy';
  };

  const markError = (item) => {
    const badge = item.querySelector("[data-question-autosave-badge]");
    if (!badge) {
      return;
    }

    badge.hidden = false;
    badge.classList.add("is-error");
    badge.classList.remove("is-local");
    badge.innerHTML = '<i class="bi bi-exclamation-circle" aria-hidden="true"></i> Chưa lưu được';
  };

  const bindForm = (form) => {
    const url = form.getAttribute("data-autosave-url");
    if (!url) {
      return null;
    }

    const saveItem = async (item, options = {}) => {
      const questionId = item.dataset.questionId || "";
      let value = answerValue(item);

      if (window.AssessmentOffline?.serializeValue) {
        value = window.AssessmentOffline.serializeValue(value);
      }

      if (!questionId || !value || (!options.force && value === item.dataset.lastAutosaved)) {
        return;
      }

      if (window.AssessmentOffline?.saveAnswer) {
        window.AssessmentOffline.saveAnswer(questionId, value);
      }

      if (window.AssessmentOffline?.isOffline?.()) {
        item.dataset.lastAutosaved = value;
        markSavedLocal(item);
        return;
      }

      if (item.dataset.autosaving === "1" && !options.keepalive) {
        item.dataset.autosaveQueued = "1";
        return;
      }

      item.dataset.autosaving = "1";

      const body = new FormData();
      body.append("_token", csrfToken());
      body.append("question_id", questionId);
      body.append("answers[" + questionId + "]", value);

      try {
        const response = await fetch(url, {
          method: "POST",
          body,
          credentials: "same-origin",
          keepalive: Boolean(options.keepalive),
          headers: {
            "X-CSRF-TOKEN": csrfToken(),
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
          },
        });
        const data = await response.json().catch(() => null);

        if (!response.ok || !data?.ok) {
          if (response.status >= 500 || response.status === 0) {
            markSavedLocal(item);
            window.dispatchEvent(new Event("assessment:network-error"));
            return;
          }

          markError(item);
          if (!options.silent) {
            showToast(data?.error || "Chưa lưu được câu " + questionNo(form, item), true);
          }
          return;
        }

        window.dispatchEvent(new Event("assessment:network-ok"));
        item.dataset.lastAutosaved = value;
        markSaved(item, true);

        if (!options.silent) {
          showToast("Đã lưu câu " + questionNo(form, item));
        }
      } catch (_error) {
        markSavedLocal(item);
        window.dispatchEvent(new Event("assessment:network-error"));
      } finally {
        item.dataset.autosaving = "0";
        if (item.dataset.autosaveQueued === "1") {
          item.dataset.autosaveQueued = "0";
          saveItem(item);
        }
      }
    };

    const debounceSave = (item, delay) => {
      clearTimeout(Number(item.dataset.autosaveTimer || 0));
      item.dataset.autosaveTimer = String(window.setTimeout(() => saveItem(item), delay));
    };

    const flush = () => {
      if (typeof tinymce !== "undefined") {
        tinymce.triggerSave();
      }

      const jobs = [];
      form.querySelectorAll(".session-quiz-item[data-question-id]").forEach((item) => {
        jobs.push(Promise.resolve(saveItem(item, { force: true, silent: true, keepalive: true })));
      });

      return Promise.all(jobs);
    };

    form.querySelectorAll(".session-quiz-item[data-question-id]").forEach((item) => {
      if (item.dataset.savedDraft === "1" && answerValue(item)) {
        item.dataset.lastAutosaved = answerValue(item);
      }

      item.addEventListener("change", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
          return;
        }

        if (item.querySelector("[data-session-listening-answer-output], [data-session-phat-am-answer-output]")) {
          saveItem(item);
          return;
        }

        if (target.matches('input[type="radio"], input[type="checkbox"], select')) {
          saveItem(item);
        }
      });

      item.addEventListener("input", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
          return;
        }

        if (item.querySelector("[data-session-listening-answer-output], [data-session-phat-am-answer-output]")) {
          debounceSave(item, 400);
          return;
        }

        if (target.matches("textarea, .session-quiz-essay-input, [data-word-translate-input], [data-fill-blank-input]")) {
          debounceSave(item, 900);
          return;
        }

        if (target.matches(hiddenInputSelector)) {
          debounceSave(item, 400);
        }
      });
    });

    const bindEditors = () => {
      if (typeof tinymce === "undefined") {
        return;
      }

      form.querySelectorAll(".session-quiz-essay-input").forEach((textarea) => {
        const editor = textarea.id ? tinymce.get(textarea.id) : null;
        const item = textarea.closest(".session-quiz-item");
        if (!editor || !item || editor._quizAutosaveBound) {
          return;
        }

        editor._quizAutosaveBound = true;
        editor.on("change keyup input undo redo", () => debounceSave(item, 400));
      });
    };

    bindEditors();
    [400, 900, 1600, 2800].forEach((delay) => window.setTimeout(bindEditors, delay));
    if (typeof tinymce !== "undefined" && typeof tinymce.on === "function") {
      tinymce.on("AddEditor", () => window.setTimeout(bindEditors, 0));
    }

    form.addEventListener("submit", flush);

    return { flush };
  };

  const bound = [];
  document.querySelectorAll("form[data-quiz-autosave]").forEach((form) => {
    const api = bindForm(form);
    if (api) {
      bound.push(api);
    }
  });

  const flushAll = () => Promise.all(bound.map((api) => Promise.resolve(api.flush())));
  window.quizAutosaveFlush = flushAll;
  window.assessmentAutosaveAll = flushAll;

  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") {
      flushAll();
    }
  });
  window.addEventListener("pagehide", flushAll);
})();
