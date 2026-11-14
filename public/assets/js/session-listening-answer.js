(() => {
  const fillEditorContent = (input) => {
    if (!input) {
      return "";
    }

    if (typeof tinymce !== "undefined") {
      if (typeof tinymce.triggerSave === "function") {
        tinymce.triggerSave();
      }

      if (input.id) {
        const editor = tinymce.get(input.id);

        if (editor && typeof editor.getContent === "function") {
          return String(editor.getContent() || "").trim();
        }
      }
    }

    return String(input.value || "").trim();
  };

  const readChoiceList = (itemWrap, rowSelector, inputSelector) => {
    const rows = [...itemWrap.querySelectorAll(rowSelector)];

    if (rows.length === 0) {
      return "";
    }

    const answers = rows.map(
      (row) => row.querySelector(`${inputSelector}:checked`)?.value.trim() || ""
    );

    if (!answers.some((value) => value !== "")) {
      return "";
    }

    return JSON.stringify({ answers });
  };

  const readFillBlanksPart = (itemWrap) => {
    const blanks = [...itemWrap.querySelectorAll("[data-fill-blank-input]")].map((input) =>
      String(input.value || "").trim()
    );

    if (!blanks.some((value) => value !== "")) {
      return itemWrap.querySelector("[data-listening-fill-blanks-output]")?.value.trim() || "";
    }

    return JSON.stringify({ blanks });
  };

  const readAnswerPart = (itemWrap) => {
    const mode = itemWrap.dataset.listeningAnswerMode || "choice";

    if (mode === "fill") {
      return fillEditorContent(itemWrap.querySelector("[data-listening-fill-input]"));
    }

    if (mode === "choice") {
      return itemWrap.querySelector("[data-listening-choice-input]:checked")?.value.trim() || "";
    }

    if (mode === "true_false") {
      return (
        readChoiceList(
          itemWrap,
          ".session-listening-true-false-student-item",
          "[data-listening-true-false-choice]"
        ) || itemWrap.querySelector("[data-listening-true-false-output]")?.value.trim() || ""
      );
    }

    if (mode === "multi_choice") {
      return (
        readChoiceList(
          itemWrap,
          ".session-listening-multi-choice-student-item",
          "[data-listening-multi-choice-choice]"
        ) || itemWrap.querySelector("[data-listening-multi-choice-output]")?.value.trim() || ""
      );
    }

    if (mode === "word_link") {
      return itemWrap.querySelector("[data-listening-word-link-output]")?.value.trim() || "";
    }

    if (mode === "fill_blanks") {
      return readFillBlanksPart(itemWrap);
    }

    return "";
  };

  const buildAnswerPayloadObject = (itemWrap) => {
    const answerPart = readAnswerPart(itemWrap);
    const mode = itemWrap.dataset.listeningAnswerMode || "choice";
    const payload = {};

    if (answerPart === "") {
      return payload;
    }

    if (mode === "choice") {
      payload.choice = answerPart;
    } else if (mode === "fill") {
      payload.text = answerPart;
    } else {
      try {
        const decoded = JSON.parse(answerPart);
        if (decoded && typeof decoded === "object") {
          Object.assign(payload, decoded);
        }
      } catch {
        payload.text = answerPart;
      }
    }

    return payload;
  };

  const buildPayload = (wrap) => {
    if (wrap.dataset.listeningSyncing === "1") {
      return;
    }

    const output = wrap.querySelector("[data-session-listening-answer-output]");
    if (!output) return;

    wrap.dataset.listeningSyncing = "1";

    try {
      const itemWraps = [...wrap.querySelectorAll("[data-session-listening-student-item]")];
      if (itemWraps.length === 0) {
        output.value = "";
        return;
      }

      const answers = itemWraps.map((itemWrap) => buildAnswerPayloadObject(itemWrap));
      const hasAnyAnswer = answers.some((answer) => Object.keys(answer).length > 0);
      const nextValue = hasAnyAnswer ? JSON.stringify({ answers }) : "";

      if (output.value !== nextValue) {
        output.value = nextValue;
        output.dispatchEvent(new Event("input", { bubbles: true }));
      }
    } finally {
      wrap.dataset.listeningSyncing = "0";
    }
  };

  const parseSavedAnswers = (raw) => {
    try {
      const decoded = JSON.parse(String(raw || "").trim() || "null");

      if (decoded && Array.isArray(decoded.answers)) {
        return decoded.answers;
      }
    } catch (_error) {
      /* ignore */
    }

    return [];
  };

  const setCheckedValue = (scope, selector, value) => {
    if (!scope || value === "" || value == null) {
      return;
    }

    const match = String(value);

    scope.querySelectorAll(selector).forEach((input) => {
      input.checked = String(input.value || "") === match;
    });
  };

  const restoreFillEditor = (input, text) => {
    if (!input || text === "") {
      return;
    }

    input.value = text;

    if (input.id && typeof tinymce !== "undefined") {
      const editor = tinymce.get(input.id);

      if (editor && typeof editor.setContent === "function") {
        editor.setContent(text);
      }
    }
  };

  const restoreItem = (itemWrap, answer) => {
    if (!itemWrap || !answer || typeof answer !== "object") {
      return;
    }

    const mode = itemWrap.dataset.listeningAnswerMode || "choice";

    if (mode === "choice" && answer.choice) {
      setCheckedValue(itemWrap, "[data-listening-choice-input]", answer.choice);
      return;
    }

    if (mode === "fill" && answer.text) {
      restoreFillEditor(itemWrap.querySelector("[data-listening-fill-input]"), String(answer.text));
      return;
    }

    if (mode === "true_false" && Array.isArray(answer.answers)) {
      itemWrap.querySelectorAll(".session-listening-true-false-student-item").forEach((row, index) => {
        setCheckedValue(row, "[data-listening-true-false-choice]", answer.answers[index]);
      });
      return;
    }

    if (mode === "multi_choice" && Array.isArray(answer.answers)) {
      itemWrap.querySelectorAll(".session-listening-multi-choice-student-item").forEach((row, index) => {
        setCheckedValue(row, "[data-listening-multi-choice-choice]", answer.answers[index]);
      });
      return;
    }

    if (mode === "fill_blanks" && Array.isArray(answer.blanks)) {
      itemWrap.querySelectorAll("[data-fill-blank-input]").forEach((input, index) => {
        if (typeof answer.blanks[index] === "string" && !input.value) {
          input.value = answer.blanks[index];
        }
      });
      return;
    }

    if (mode === "word_link" && (Array.isArray(answer.links) || answer.status)) {
      const hidden = itemWrap.querySelector("[data-listening-word-link-output], [data-word-link-complete-input]");

      if (hidden) {
        hidden.value = JSON.stringify({
          status: answer.status || "in_progress",
          moves: Number(answer.moves || 0),
          links: Array.isArray(answer.links) ? answer.links : [],
        });
      }
    }
  };

  const restoreWrap = (wrap) => {
    const output = wrap.querySelector("[data-session-listening-answer-output]");
    const savedAnswers = parseSavedAnswers(output?.value);

    wrap.querySelectorAll("[data-session-listening-student-item]").forEach((itemWrap, index) => {
      restoreItem(itemWrap, savedAnswers[index]);
    });

    wrap.querySelectorAll("[data-session-word-link-board]").forEach((board) => {
      if (typeof board._restoreWordLinkFromInput === "function") {
        board._restoreWordLinkFromInput();
      }
    });

    wrap
      .querySelectorAll("[data-listening-true-false-choice]:checked, [data-listening-multi-choice-choice]:checked")
      .forEach((input) => {
        input.dispatchEvent(new Event("change", { bubbles: true }));
      });
  };

  const syncAll = () => {
    document.querySelectorAll("[data-session-listening-student]").forEach((wrap) => {
      restoreWrap(wrap);
      buildPayload(wrap);
    });
  };

  window.syncListeningStudentPayload = buildPayload;

  document.querySelectorAll("[data-session-listening-student]").forEach((wrap) => {
    wrap.addEventListener("change", () => buildPayload(wrap));
    wrap.addEventListener("input", () => buildPayload(wrap));
  });

  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", () => {
      document.querySelectorAll("[data-session-listening-student]").forEach((wrap) => buildPayload(wrap));
    }, true);
  });

  syncAll();
  window.setTimeout(syncAll, 900);
})();
