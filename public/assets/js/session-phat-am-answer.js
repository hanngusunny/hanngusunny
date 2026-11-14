(() => {
  const readRecordingPath = (scope) => {
    const recordingInput = scope.querySelector("[data-phat-am-complete-input]");
    const raw = recordingInput?.value.trim() || "";

    if (!raw) {
      return "";
    }

    if (recordingInput?.closest("[data-session-phat-am-board]")?.dataset.phatAmValueMode === "path") {
      return raw;
    }

    try {
      const parsed = JSON.parse(raw);
      return typeof parsed?.recording === "string" ? parsed.recording.trim() : "";
    } catch {
      return "";
    }
  };

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

  const readAnswerPart = (itemWrap) => {
    const mode = itemWrap.dataset.phatAmAnswerMode || "choice";

    if (mode === "fill") {
      return fillEditorContent(itemWrap.querySelector("[data-phat-am-fill-input]"));
    }

    if (mode === "choice") {
      return itemWrap.querySelector("[data-phat-am-choice-input]:checked")?.value.trim() || "";
    }

    if (mode === "true_false") {
      return (
        readChoiceList(
          itemWrap,
          ".session-listening-true-false-student-item",
          "[data-listening-true-false-choice]"
        ) || itemWrap.querySelector("[data-phat-am-true-false-output]")?.value.trim() || ""
      );
    }

    if (mode === "multi_choice") {
      return (
        readChoiceList(
          itemWrap,
          ".session-listening-multi-choice-student-item",
          "[data-listening-multi-choice-choice]"
        ) || itemWrap.querySelector("[data-phat-am-multi-choice-output]")?.value.trim() || ""
      );
    }

    if (mode === "multi_select") {
      return itemWrap.querySelector("[data-phat-am-multi-select-output]")?.value.trim() || "";
    }

    return "";
  };

  const buildAnswerPayloadObject = (itemWrap) => {
    const answerPart = readAnswerPart(itemWrap);
    const mode = itemWrap.dataset.phatAmAnswerMode || "choice";
    const payload = {};

    if (answerPart === "") {
      return payload;
    }

    if (mode === "choice" || mode === "multi_select") {
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

    const mode = itemWrap.dataset.phatAmAnswerMode || "choice";

    if ((mode === "choice" || mode === "multi_select") && answer.choice) {
      if (mode === "multi_select") {
        const selected = String(answer.choice)
          .split(",")
          .map((item) => item.trim().toLowerCase())
          .filter(Boolean);

        itemWrap.querySelectorAll("[data-phat-am-multi-select-choice]").forEach((input) => {
          input.checked = selected.includes(String(input.value || "").trim().toLowerCase());
        });
        const hidden = itemWrap.querySelector("[data-phat-am-multi-select-output]");
        if (hidden) {
          hidden.value = selected.sort().join(",");
        }
        return;
      }

      setCheckedValue(itemWrap, "[data-phat-am-choice-input]", answer.choice);
      return;
    }

    if (mode === "fill" && answer.text) {
      restoreFillEditor(itemWrap.querySelector("[data-phat-am-fill-input]"), String(answer.text));
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
    }
  };

  const parseSavedPayload = (raw) => {
    try {
      const decoded = JSON.parse(String(raw || "").trim() || "null");

      return decoded && typeof decoded === "object" ? decoded : {};
    } catch (_error) {
      return {};
    }
  };

  const resyncNestedBoards = (wrap) => {
    wrap
      .querySelectorAll("[data-listening-true-false-choice]:checked, [data-listening-multi-choice-choice]:checked")
      .forEach((input) => {
        input.dispatchEvent(new Event("change", { bubbles: true }));
      });
  };

  const restoreWrap = (wrap) => {
    const output = wrap.querySelector("[data-session-phat-am-answer-output]");
    const saved = parseSavedPayload(output?.value);
    const answers = Array.isArray(saved.answers) ? saved.answers : [];

    wrap.querySelectorAll("[data-session-phat-am-student-item]").forEach((itemWrap, index) => {
      restoreItem(itemWrap, answers[index]);
    });

    resyncNestedBoards(wrap);
  };

  const buildPayload = (wrap) => {
    if (wrap.dataset.phatAmSyncing === "1") {
      return;
    }

    const output = wrap.querySelector("[data-session-phat-am-answer-output]");
    if (!output) return;

    wrap.dataset.phatAmSyncing = "1";

    try {
      buildPayloadInner(wrap, output);
    } finally {
      wrap.dataset.phatAmSyncing = "0";
    }
  };

  const writeOutput = (output, nextValue) => {
    if (output.value === nextValue) {
      return;
    }

    output.value = nextValue;
    output.dispatchEvent(new Event("input", { bubbles: true }));
  };

  const buildPayloadInner = (wrap, output) => {
    const itemWraps = [...wrap.querySelectorAll("[data-session-phat-am-student-item]")];
    const recording = readRecordingPath(wrap);

    if (itemWraps.length > 0) {
      const answers = itemWraps.map((itemWrap) => buildAnswerPayloadObject(itemWrap));
      const hasAnyAnswer = answers.some((answer) => Object.keys(answer).length > 0);

      if (recording === "" && !hasAnyAnswer) {
        writeOutput(output, "");
        return;
      }

      const payload = {};

      if (recording !== "") {
        payload.recording = recording;
      }

      if (hasAnyAnswer) {
        payload.answers = answers;
      }

      writeOutput(output, JSON.stringify(payload));
      return;
    }

    const answerPart = readAnswerPart(wrap);
    const mode = wrap.dataset.phatAmAnswerMode || "choice";

    if (recording === "" && answerPart === "") {
      writeOutput(output, "");
      return;
    }

    if (recording === "") {
      writeOutput(output, answerPart);
      return;
    }

    if (answerPart === "") {
      writeOutput(output, JSON.stringify({ recording }));
      return;
    }

    const payload = { recording };

    if (mode === "choice" || mode === "multi_select") {
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

    writeOutput(output, JSON.stringify(payload));
  };

  const syncAll = () => {
    document.querySelectorAll("[data-session-phat-am-student]").forEach((wrap) => {
      restoreWrap(wrap);
      buildPayload(wrap);
    });
  };

  window.syncPhatAmStudentPayload = buildPayload;

  document.querySelectorAll("[data-session-phat-am-student]").forEach((wrap) => restoreWrap(wrap));

  document.querySelectorAll("[data-session-phat-am-student]").forEach((wrap) => {
    wrap.addEventListener("change", () => buildPayload(wrap));
    wrap.addEventListener("input", () => buildPayload(wrap));

    wrap.querySelectorAll("[data-session-phat-am-multi-select-group]").forEach((group) => {
      const hidden = group.querySelector("[data-phat-am-multi-select-output]");
      if (!hidden) return;

      const sync = () => {
        const keys = [...group.querySelectorAll("[data-phat-am-multi-select-choice]:checked")]
          .map((input) => String(input.value || "").trim().toLowerCase())
          .filter(Boolean)
          .sort();
        hidden.value = keys.join(",");
        buildPayload(wrap);
      };

      group.querySelectorAll("[data-phat-am-multi-select-choice]").forEach((input) => {
        input.addEventListener("change", sync);
      });

      sync();
    });
  });

  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener(
      "submit",
      () => {
        document.querySelectorAll("[data-session-phat-am-student]").forEach((wrap) => buildPayload(wrap));
      },
      true
    );
  });

  syncAll();
  window.setTimeout(syncAll, 900);
})();
