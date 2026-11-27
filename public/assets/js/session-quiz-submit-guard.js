(() => {
  const form = document.querySelector(".session-quiz-form");

  if (!form) return;

  const UNANSWERED_CLASS = "is-unanswered";
  const UNANSWERED_SELECTOR =
    ".session-quiz-item, .session-phat-am-student-item, .session-listening-student-item";
  const INTERACTIVE_SELECTOR = [
    "[data-memory-complete-input]",
    "[data-word-link-complete-input]",
    "[data-word-order-complete-input]",
    "[data-listening-true-false-complete-input]",
    "[data-listening-multi-choice-complete-input]",
    "[data-category-sort-complete-input]",
    "[data-fill-blanks-complete-input]",
    "[data-word-select-complete-input]",
    "[data-image-hotspot-complete-input]",
    "[data-multi-select-complete-input]",
    "[data-image-label-drop-complete-input]",
    "[data-word-translate-complete-input]",
  ].join(", ");

  const htmlLooksAnswered = (html) => {
    const source = String(html || "");
    const plain = source
      .replace(/<[^>]+>/g, " ")
      .replace(/\u00a0/g, " ")
      .replace(/\s+/g, " ")
      .trim();

    if (plain !== "") {
      return true;
    }

    if (/<img\b[^>]*\bsrc\s*=\s*["'][^"']+["']/i.test(source)) {
      return true;
    }

    if (/<(video|iframe|object|embed)\b/i.test(source)) {
      return true;
    }

    try {
      const doc = new DOMParser().parseFromString(source, "text/html");

      return Boolean(doc.querySelector("img[src], video[src], iframe[src], table td, table th, li, a[href]"));
    } catch (_error) {
      return false;
    }
  };

  const hasEssayAnswer = (editor, textarea) => {
    if (editor) {
      try {
        editor.save();
      } catch (_error) {
        // keep checking raw textarea
      }

      const editorHtml = editor.getContent();
      const editorPlain = editor.getContent({ format: "text" }).replace(/\u00a0/g, " ").trim();

      if (editorPlain !== "" || htmlLooksAnswered(editorHtml)) {
        return true;
      }
    }

    const raw = String(textarea?.value || "");

    if (!htmlLooksAnswered(raw)) {
      return false;
    }

    if (editor) {
      try {
        editor.setContent(raw);
      } catch (_error) {
        // textarea already counts as answered
      }
    }

    return true;
  };

  const parseJson = (raw) => {
    try {
      return JSON.parse(raw);
    } catch (_error) {
      return null;
    }
  };

  const isInteractiveIncomplete = (input) => {
    if (!input) {
      return true;
    }

    const raw = String(input.value || "").trim();

    if (!raw) {
      return true;
    }

    const parsed = parseJson(raw);

    if (input.matches("[data-fill-blanks-complete-input]")) {
      const blanks = parsed?.blanks;
      const expected = input
        .closest("[data-session-fill-blanks-board]")
        ?.querySelectorAll("[data-fill-blank-input]").length || 0;

      return !Array.isArray(blanks)
        || (expected > 0 && blanks.length < expected)
        || blanks.some((item) => String(item || "").trim() === "");
    }

    if (input.matches("[data-word-select-complete-input]")) {
      const blanks = parsed?.blanks;
      const expected = input
        .closest("[data-session-word-select-board]")
        ?.querySelectorAll("[data-word-select-slot]").length || 0;

      return !Array.isArray(blanks)
        || (expected > 0 && blanks.length < expected)
        || blanks.some((item) => String(item || "").trim() === "");
    }

    if (input.matches("[data-word-translate-complete-input]")) {
      const translations = parsed?.translations;
      const expected = input
        .closest("[data-session-word-translate-board]")
        ?.querySelectorAll("[data-word-translate-input]").length || 0;

      return !Array.isArray(translations)
        || (expected > 0 && translations.length < expected)
        || translations.some((item) => String(item || "").trim() === "");
    }

    if (input.matches("[data-image-hotspot-complete-input]")) {
      const clicks = parsed?.clicks;
      const target = Number(input.closest("[data-session-image-hotspot-board]")?.dataset.targetCount || 0);

      return !Array.isArray(clicks) || (target > 0 && clicks.length < target);
    }

    if (
      input.matches(
        "[data-word-order-complete-input], [data-category-sort-complete-input], [data-image-label-drop-complete-input], [data-word-link-complete-input], [data-memory-complete-input]"
      )
    ) {
      return (parsed?.status || "") !== "completed";
    }

    if (input.matches("[data-listening-true-false-complete-input], [data-listening-multi-choice-complete-input]")) {
      const answers = parsed?.answers;

      return !Array.isArray(answers) || answers.length === 0 || answers.some((item) => String(item || "").trim() === "");
    }

    if (input.matches("[data-multi-select-complete-input]")) {
      return raw === "";
    }

    return false;
  };

  const isInsideComposite = (el) =>
    Boolean(el?.closest("[data-session-listening-student], [data-session-phat-am-student]"));

  const isCompositeItemIncomplete = (item) => {
    const mode =
      item.dataset.listeningAnswerMode || item.dataset.phatAmAnswerMode || "choice";

    if (mode === "fill") {
      const textarea = item.querySelector(".session-quiz-essay-input");
      const editor = typeof tinymce !== "undefined" && textarea?.id ? tinymce.get(textarea.id) : null;

      return !hasEssayAnswer(editor, textarea);
    }

    if (mode === "choice") {
      return !item.querySelector(
        "[data-listening-choice-input]:checked, [data-phat-am-choice-input]:checked"
      );
    }

    if (mode === "true_false") {
      const rows = [...item.querySelectorAll(".session-listening-true-false-student-item")];

      return (
        rows.length === 0 ||
        rows.some((row) => !row.querySelector("[data-listening-true-false-choice]:checked"))
      );
    }

    if (mode === "multi_choice") {
      const rows = [...item.querySelectorAll(".session-listening-multi-choice-student-item")];

      return (
        rows.length === 0 ||
        rows.some((row) => !row.querySelector("[data-listening-multi-choice-choice]:checked"))
      );
    }

    if (mode === "multi_select") {
      return !item.querySelector("[data-phat-am-multi-select-choice]:checked");
    }

    if (mode === "word_link") {
      const parsed = parseJson(
        item.querySelector("[data-word-link-complete-input], [data-listening-word-link-output]")?.value || ""
      );

      return (parsed?.status || "") !== "completed";
    }

    if (mode === "fill_blanks") {
      const blanks = [...item.querySelectorAll("[data-fill-blank-input]")];

      return blanks.length === 0 || blanks.some((input) => String(input.value || "").trim() === "");
    }

    return false;
  };

  const wrapHasRecording = (wrap) => {
    const input = wrap.querySelector("[data-phat-am-complete-input]");
    const raw = String(input?.value || "").trim();

    if (!raw) {
      return false;
    }

    if (input?.closest("[data-session-phat-am-board]")?.dataset.phatAmValueMode === "path") {
      return true;
    }

    try {
      const parsed = JSON.parse(raw);

      return typeof parsed?.recording === "string" && parsed.recording.trim() !== "";
    } catch (_error) {
      return true;
    }
  };

  const isQuizItemIncomplete = (item) => {
    const essay = [...item.querySelectorAll(".session-quiz-essay-input")].find((el) => !isInsideComposite(el));

    if (essay) {
      const editor = typeof tinymce !== "undefined" && essay.id ? tinymce.get(essay.id) : null;

      return !hasEssayAnswer(editor, essay);
    }

    const radios = [...item.querySelectorAll('input[type="radio"][name^="answers["]')];

    if (radios.length > 0) {
      const names = [...new Set(radios.map((radio) => radio.name))];

      return names.some(
        (name) => !item.querySelector(`input[type="radio"][name="${CSS.escape(name)}"]:checked`)
      );
    }

    const interactive = [...item.querySelectorAll(INTERACTIVE_SELECTOR)].find((el) => !isInsideComposite(el));

    if (interactive) {
      return isInteractiveIncomplete(interactive);
    }

    return false;
  };

  const syncInteractiveAnswers = () => {
    form.querySelectorAll("[data-session-listening-student]").forEach((wrap) => {
      window.syncListeningStudentPayload?.(wrap);
    });
    form.querySelectorAll("[data-session-phat-am-student]").forEach((wrap) => {
      window.syncPhatAmStudentPayload?.(wrap);
    });
    form.querySelectorAll("[data-word-translate-input]").forEach((input) => {
      input.dispatchEvent(new Event("input", { bubbles: true }));
    });
  };

  const collectIncompleteTargets = () => {
    syncInteractiveAnswers();

    const targets = [];

    form.querySelectorAll(".session-quiz-item").forEach((item) => {
      const children = [...item.querySelectorAll("[data-session-listening-student-item], [data-session-phat-am-student-item]")];

      if (children.length > 0) {
        const wrap = item.querySelector("[data-session-phat-am-student]");

        if (wrap && wrap.dataset.phatAmRequireRecording !== "0" && !wrapHasRecording(wrap)) {
          targets.push(item);
        }

        children.forEach((child) => {
          if (isCompositeItemIncomplete(child)) {
            targets.push(child);
          }
        });

        return;
      }

      if (isQuizItemIncomplete(item)) {
        targets.push(item);
      }
    });

    return targets;
  };

  const clearUnansweredMarks = () => {
    form.querySelectorAll("." + UNANSWERED_CLASS).forEach((el) => {
      el.classList.remove(UNANSWERED_CLASS);
    });
  };

  const markUnanswered = (targets) => {
    clearUnansweredMarks();
    targets.forEach((el) => el.classList.add(UNANSWERED_CLASS));
  };

  const isVisibleField = (node) => {
    if (!(node instanceof HTMLElement) || node.disabled || node.hidden) {
      return false;
    }

    if (node.closest("[hidden]")) {
      return false;
    }

    const style = window.getComputedStyle(node);

    if (style.display === "none" || style.visibility === "hidden") {
      return false;
    }

    const rect = node.getBoundingClientRect();

    return rect.width > 0 && rect.height > 0;
  };

  const revealAnchor = (el) => {
    if (el.matches(".session-quiz-item")) {
      const wrap = el.querySelector("[data-session-phat-am-student]");

      if (wrap && wrap.dataset.phatAmRequireRecording !== "0" && !wrapHasRecording(wrap)) {
        return wrap.querySelector(".session-phat-am-record-section") || el;
      }
    }

    return el;
  };

  const focusUnanswered = (el) => {
    const essay = el.querySelector(".session-quiz-essay-input");
    const editor = essay?.id && typeof tinymce !== "undefined" ? tinymce.get(essay.id) : null;

    if (editor && typeof editor.focus === "function") {
      editor.focus();
      return;
    }

    const recordBtn = el.querySelector("[data-phat-am-record]");

    if (recordBtn && isVisibleField(recordBtn)) {
      recordBtn.focus({ preventScroll: true });
      return;
    }

    const field = [...el.querySelectorAll("textarea, input, select")].find((node) => {
      const type = String(node.type || "").toLowerCase();

      return (
        isVisibleField(node) &&
        type !== "hidden" &&
        type !== "range" &&
        type !== "radio" &&
        type !== "checkbox" &&
        type !== "file" &&
        !node.classList.contains("session-quiz-essay-input")
      );
    });

    if (field && typeof field.focus === "function") {
      field.focus({ preventScroll: true });
    }
  };

  const revealFirstUnanswered = (el) => {
    const target = revealAnchor(el);

    target.scrollIntoView({ behavior: "smooth", block: "center" });
    window.setTimeout(() => focusUnanswered(target), 200);
  };

  const releaseSubmitUi = () => {
    window.pwaLoading?.hide();
    form.querySelectorAll(".is-wait[aria-busy='true']").forEach((btn) => {
      btn.classList.remove("is-wait");
      btn.removeAttribute("aria-busy");
    });
  };

  const isStillUnanswered = (el) => {
    if (el.matches("[data-session-listening-student-item], [data-session-phat-am-student-item]")) {
      return isCompositeItemIncomplete(el);
    }

    if (el.matches(".session-quiz-item")) {
      const children = [...el.querySelectorAll("[data-session-listening-student-item], [data-session-phat-am-student-item]")];

      if (children.length > 0) {
        const wrap = el.querySelector("[data-session-phat-am-student]");

        return Boolean(wrap && wrap.dataset.phatAmRequireRecording !== "0" && !wrapHasRecording(wrap));
      }

      return isQuizItemIncomplete(el);
    }

    return false;
  };

  form.addEventListener("input", (event) => {
    const card = event.target.closest(UNANSWERED_SELECTOR);

    if (!card?.classList.contains(UNANSWERED_CLASS)) {
      return;
    }

    window.setTimeout(() => {
      if (!isStillUnanswered(card)) {
        card.classList.remove(UNANSWERED_CLASS);
      }
    }, 40);
  });

  form.addEventListener("change", (event) => {
    const card = event.target.closest(UNANSWERED_SELECTOR);

    if (!card?.classList.contains(UNANSWERED_CLASS)) {
      return;
    }

    if (!isStillUnanswered(card)) {
      card.classList.remove(UNANSWERED_CLASS);
    }
  });

  window.snEvaluateQuizCompletion = (targetForm) => {
    const scope = targetForm || form;
    if (!scope) {
      return [];
    }

    const items = [...scope.querySelectorAll(".session-quiz-item")];

    return items.map((item, idx) => {
      const no = Number(item.dataset.questionNo || idx + 1);
      const children = [...item.querySelectorAll("[data-session-listening-student-item], [data-session-phat-am-student-item]")];
      let incomplete = false;

      if (children.length > 0) {
        const wrap = item.querySelector("[data-session-phat-am-student]");
        if (wrap && wrap.dataset.phatAmRequireRecording !== "0" && !wrapHasRecording(wrap)) {
          incomplete = true;
        } else {
          incomplete = children.some((child) => isCompositeItemIncomplete(child));
        }
      } else {
        incomplete = isQuizItemIncomplete(item);
      }

      return {
        questionNo: no,
        complete: !incomplete,
      };
    });
  };

  form.addEventListener("submit", (event) => {
    if (typeof tinymce !== "undefined") {
      tinymce.triggerSave();
    }

    const timedOutInput = form.querySelector("#sessionKnowledgeReviewTimedOut, [name='timed_out']");
    if (timedOutInput && timedOutInput.value === "1") {
      return;
    }

    if (form.dataset.snConfirmedSubmit === "1") {
      form.dataset.snConfirmedSubmit = "";
      clearUnansweredMarks();
      return;
    }

    const targets = collectIncompleteTargets();

    // Hook tuỳ chọn cho Modal xác nhận chuẩn Figma trên màn Khóa học (giữ nguyên window.confirm cho PWA/màn khác)
    if (form.hasAttribute("data-sn-figma-quiz-form") && typeof window.snCourseQuizSubmitHandler === "function") {
      event.preventDefault();
      releaseSubmitUi();

      const handled = window.snCourseQuizSubmitHandler({
        form,
        targets,
        incompleteCount: targets.length,
        onConfirmSubmit: () => {
          clearUnansweredMarks();
          form.dataset.snConfirmedSubmit = "1";
          form.submit();
        },
        onContinueIncomplete: () => {
          if (targets.length > 0) {
            markUnanswered(targets);
            revealFirstUnanswered(targets[0]);
          }
        },
      });

      if (handled) {
        return;
      }
    }

    if (targets.length === 0) {
      clearUnansweredMarks();
      return;
    }

    const count = targets.length;
    const confirmMsg =
      count === 1
        ? "Bạn còn 1 câu chưa làm xong. Bạn có chắc chắn muốn nộp bài không?"
        : "Bạn còn " + count + " câu chưa làm xong. Bạn có chắc chắn muốn nộp bài không?";

    if (!window.confirm(confirmMsg)) {
      event.preventDefault();
      markUnanswered(targets);
      releaseSubmitUi();
      revealFirstUnanswered(targets[0]);
      return;
    }

    clearUnansweredMarks();
  });
})();
