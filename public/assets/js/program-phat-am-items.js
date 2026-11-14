(() => {
  const defaultQuestion = () => ({
    answer_mode: "choice",
    choice_question: "",
    options: ["", "", "", ""],
    correct: "a",
    correct_text: "",
    true_false_items: [{ statement: "", answer: true }],
    multi_choice_items: [{ question: "", options: ["", ""], correct: "a" }],
    points: null,
  });

  const parseOptionalPoints = (input) => {
    const raw = String(input?.value || "").trim().replace(",", ".");
    if (raw === "") return null;
    const n = Number(raw);
    if (!Number.isFinite(n) || n <= 0) return null;
    return Math.round(n * 100) / 100;
  };

  const formatOptionalPoints = (n) => String(n.toFixed(2)).replace(/\.?0+$/, "");

  const syncParentPointsFromItems = (questionItem) => {
    const parent = questionItem.querySelector(".program-question-points-input");
    if (!parent) return;
    const values = [...questionItem.querySelectorAll("[data-session-item-points]")]
      .map((input) => parseOptionalPoints(input))
      .filter((value) => value !== null);
    if (values.length === 0) return;
    parent.value = formatOptionalPoints(values.reduce((sum, value) => sum + value, 0));
  };

  const getMode = (itemRow) =>
    itemRow.querySelector("[data-phat-am-item-answer-mode-radio]:checked")?.value || "choice";

  const syncItemModePanels = (itemRow) => {
    const mode = getMode(itemRow);
    const isChoiceLike = mode === "choice" || mode === "multi_select";
    itemRow.querySelector("[data-session-phat-am-item-choice-panel]")?.toggleAttribute("hidden", !isChoiceLike);
    itemRow.querySelector("[data-session-phat-am-item-fill-panel]")?.toggleAttribute("hidden", mode !== "fill");
    itemRow.querySelector("[data-session-phat-am-item-tf-panel]")?.toggleAttribute("hidden", mode !== "true_false");
    itemRow.querySelector("[data-session-phat-am-item-mc-panel]")?.toggleAttribute("hidden", mode !== "multi_choice");

    const optionsWrap = itemRow.querySelector("[data-session-phat-am-item-options]");
    if (optionsWrap) {
      optionsWrap.dataset.choiceMulti = mode === "multi_select" ? "1" : "0";
    }

    const choiceLabel = itemRow.querySelector("[data-session-phat-am-item-choice-label]");
    if (choiceLabel) {
      choiceLabel.textContent =
        mode === "multi_select"
          ? "Các lựa chọn (có thể chọn nhiều đáp án đúng)"
          : "Các lựa chọn trả lời";
    }

    syncChoiceMarks(itemRow);
  };

  const syncChoiceMarks = (itemRow) => {
    const optionsWrap = itemRow.querySelector("[data-session-phat-am-item-options]");
    const hidden = itemRow.querySelector("[data-session-phat-am-item-correct]");
    if (!optionsWrap || !hidden) return;

    const isMulti = optionsWrap.dataset.choiceMulti === "1";
    const current = isMulti
      ? (hidden.value || "")
          .split(",")
          .map((part) => part.trim().toLowerCase())
          .filter(Boolean)
      : [(hidden.value || "a").toLowerCase()];

    itemRow.querySelectorAll("[data-session-phat-am-item-mark]").forEach((mark) => {
      const key = mark.dataset.sessionPhatAmItemMark || "";
      mark.classList.toggle("is-correct", current.includes(key));
    });
  };

  const bindChoiceMarks = (itemRow, onChange) => {
    itemRow.querySelectorAll("[data-session-phat-am-item-mark]").forEach((button) => {
      if (button.dataset.phatAmMarkBound === "1") return;
      button.dataset.phatAmMarkBound = "1";

      button.addEventListener("click", () => {
        const key = button.dataset.sessionPhatAmItemMark || "a";
        const optionsWrap = itemRow.querySelector("[data-session-phat-am-item-options]");
        const hidden = itemRow.querySelector("[data-session-phat-am-item-correct]");
        if (!hidden) return;

        if (optionsWrap?.dataset.choiceMulti === "1") {
          let current = (hidden.value || "")
            .split(",")
            .map((part) => part.trim().toLowerCase())
            .filter(Boolean);

          if (current.includes(key)) {
            current = current.filter((part) => part !== key);
          } else {
            current.push(key);
          }

          current.sort();
          hidden.value = current.join(",");
        } else {
          hidden.value = key;
        }

        syncChoiceMarks(itemRow);
        onChange();
      });
    });

    syncChoiceMarks(itemRow);
  };

  const collectChoiceOptions = (itemRow) => {
    const options = [];
    itemRow.querySelectorAll("[data-session-phat-am-item-option]").forEach((input) => {
      const value = input.value.trim();
      if (value !== "") options.push(value);
    });
    return options;
  };

  const collectTrueFalseItems = (itemRow) =>
    [...itemRow.querySelectorAll("[data-session-phat-am-item-tf-row]")].map((row) => ({
      statement: row.querySelector("[data-session-phat-am-item-tf-statement]")?.value.trim() || "",
      answer: row.querySelector("[data-session-phat-am-item-tf-answer]:checked")?.value === "true",
    }));

  const collectMultiChoiceItems = (itemRow) =>
    [...itemRow.querySelectorAll("[data-session-phat-am-item-mc-row]")].map((row) => {
      const options = [];
      row.querySelectorAll("[data-session-phat-am-item-mc-option]").forEach((input) => {
        const value = input.value.trim();
        if (value !== "") options.push(value);
      });

      return {
        question: row.querySelector("[data-session-phat-am-item-mc-question]")?.value.trim() || "",
        options,
        correct: row.querySelector("[data-session-phat-am-item-mc-correct]")?.value.trim() || "a",
      };
    });

  const collectQuestion = (itemRow) => {
    window.ProgramQuestionEditors?.save?.(itemRow);
    const mode = getMode(itemRow);
    const payload = {
      answer_mode: mode,
      choice_question: itemRow.querySelector("[data-session-phat-am-item-choice-question]")?.value.trim() || "",
      options: collectChoiceOptions(itemRow),
      correct: itemRow.querySelector("[data-session-phat-am-item-correct]")?.value.trim() || "a",
      correct_text: itemRow.querySelector("[data-session-phat-am-item-correct-text]")?.value.trim() || "",
      true_false_items: collectTrueFalseItems(itemRow),
      multi_choice_items: collectMultiChoiceItems(itemRow),
    };
    const points = parseOptionalPoints(itemRow.querySelector("[data-session-item-points]"));
    if (points !== null) payload.points = points;
    return payload;
  };

  const syncPhatAmItemsJson = (questionItem) => {
    const jsonInput = questionItem.querySelector("[data-session-phat-am-json]");
    if (!jsonInput) return;

    window.ProgramQuestionEditors?.save?.(questionItem);
    const prompt = questionItem.querySelector("[data-session-phat-am-prompt]")?.value.trim() || "";
    const questions = [...questionItem.querySelectorAll("[data-session-phat-am-question]")].map((row) => collectQuestion(row));
    const requireRecording =
      questionItem.querySelector("[data-session-phat-am-require-recording-radio]:checked")?.value !== "0";
    jsonInput.value = JSON.stringify({ prompt, questions, require_recording: requireRecording });
    syncParentPointsFromItems(questionItem);
  };

  const syncPhatAmRecordingHint = (questionItem) => {
    const requireRecording =
      questionItem.querySelector("[data-session-phat-am-require-recording-radio]:checked")?.value !== "0";
    questionItem
      .querySelector("[data-session-phat-am-recording-hint-required]")
      ?.toggleAttribute("hidden", !requireRecording);
    questionItem
      .querySelector("[data-session-phat-am-recording-hint-optional]")
      ?.toggleAttribute("hidden", requireRecording);
  };

  const assignModeRadioNames = (questionItem) => {
    const baseId = questionItem.dataset.questionIndex || questionItem.id || "q";

    questionItem.querySelectorAll("[data-session-phat-am-question]").forEach((row, index) => {
      const groupName = `phat-am-mode-${baseId}-${index}`;
      row.querySelectorAll("[data-phat-am-item-answer-mode-radio]").forEach((radio) => {
        radio.name = groupName;
      });
    });
  };

  const reindexQuestions = (questionItem, list) => {
    list.querySelectorAll("[data-session-phat-am-question]").forEach((row, index) => {
      const badge = row.querySelector("[data-session-phat-am-item-badge]");
      if (badge) badge.textContent = String(index + 1);
      const removeBtn = row.querySelector("[data-session-phat-am-remove-question]");
      if (removeBtn) removeBtn.hidden = list.querySelectorAll("[data-session-phat-am-question]").length <= 1;
    });
    assignModeRadioNames(questionItem);
  };

  const reindexTfRows = (list) => {
    list.querySelectorAll("[data-session-phat-am-item-tf-row]").forEach((row, index) => {
      const badge = row.querySelector(".session-listening-true-false-badge");
      if (badge) badge.textContent = String(index + 1);
      const removeBtn = row.querySelector("[data-session-phat-am-item-tf-remove]");
      if (removeBtn) removeBtn.hidden = list.querySelectorAll("[data-session-phat-am-item-tf-row]").length <= 1;
    });
  };

  const reindexMcRows = (list) => {
    list.querySelectorAll("[data-session-phat-am-item-mc-row]").forEach((row, index) => {
      const badge = row.querySelector(".session-listening-multi-choice-badge");
      if (badge) badge.textContent = String(index + 1);
      const removeBtn = row.querySelector("[data-session-phat-am-item-mc-remove]");
      if (removeBtn) removeBtn.hidden = list.querySelectorAll("[data-session-phat-am-item-mc-row]").length <= 1;
    });
  };

  const bindMcMarks = (mcRow, onChange) => {
    mcRow.querySelectorAll("[data-session-phat-am-item-mc-mark]").forEach((button) => {
      button.addEventListener("click", () => {
        const key = button.dataset.sessionPhatAmItemMcMark || "a";
        mcRow.querySelectorAll("[data-session-phat-am-item-mc-mark]").forEach((mark) => mark.classList.remove("is-correct"));
        button.classList.add("is-correct");
        const hidden = mcRow.querySelector("[data-session-phat-am-item-mc-correct]");
        if (hidden) hidden.value = key;
        onChange();
      });
    });
  };

  const createTfRow = () => {
    const li = document.createElement("li");
    li.className = "session-listening-true-false-item";
    li.dataset.sessionPhatAmItemTfRow = "";
    li.innerHTML =
      `<span class="session-listening-true-false-badge">1</span>` +
      `<input type="text" class="form-control form-control-sm session-listening-true-false-statement" data-session-phat-am-item-tf-statement placeholder="VD: Cách phát âm này là đúng.">` +
      `<div class="session-listening-true-false-answer-toggle" role="group">` +
      `<label class="session-listening-true-false-answer-option"><input type="radio" value="true" data-session-phat-am-item-tf-answer checked><span>Đúng</span></label>` +
      `<label class="session-listening-true-false-answer-option"><input type="radio" value="false" data-session-phat-am-item-tf-answer><span>Sai</span></label>` +
      `</div>` +
      `<button class="btn sunny-mini session-listening-true-false-remove" type="button" data-session-phat-am-item-tf-remove title="Xóa câu"><i class="bi bi-x-lg"></i></button>`;
    return li;
  };

  const createMcRow = () => {
    const li = document.createElement("li");
    li.className = "session-listening-multi-choice-item";
    li.dataset.sessionPhatAmItemMcRow = "";
    li.innerHTML =
      `<div class="session-listening-multi-choice-item-head">` +
      `<span class="session-listening-multi-choice-badge">1</span>` +
      `<input type="text" class="form-control form-control-sm session-listening-multi-choice-question" data-session-phat-am-item-mc-question placeholder="Câu hỏi trắc nghiệm">` +
      `<button class="btn sunny-mini session-listening-multi-choice-remove-item" type="button" data-session-phat-am-item-mc-remove title="Xóa câu"><i class="bi bi-x-lg"></i></button>` +
      `</div>` +
      `<div class="session-listening-multi-choice-options" data-session-phat-am-item-mc-options>` +
      `<div class="session-listening-mc-option-row" data-session-phat-am-item-mc-option-row data-option-key="a">` +
      `<button type="button" class="session-choice-option-mark is-correct" data-session-phat-am-item-mc-mark="a">A</button>` +
      `<input type="text" class="form-control form-control-sm" placeholder="Lựa chọn A" data-session-phat-am-item-mc-option>` +
      `</div>` +
      `<div class="session-listening-mc-option-row" data-session-phat-am-item-mc-option-row data-option-key="b">` +
      `<button type="button" class="session-choice-option-mark" data-session-phat-am-item-mc-mark="b">B</button>` +
      `<input type="text" class="form-control form-control-sm" placeholder="Lựa chọn B" data-session-phat-am-item-mc-option>` +
      `</div>` +
      `</div>` +
      `<input type="hidden" value="a" data-session-phat-am-item-mc-correct>`;
    return li;
  };

  const createQuestionRow = () => {
    const li = document.createElement("li");
    li.className = "session-phat-am-item";
    li.dataset.sessionPhatAmQuestion = "";
    const modeGroupName = `phat-am-mode-temp-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    li.innerHTML =
      `<div class="session-phat-am-item-head">` +
      `<span class="session-phat-am-item-badge" data-session-phat-am-item-badge>1</span>` +
      `<strong class="session-phat-am-item-title">Câu hỏi</strong>` +
      `<label class="session-item-points-field"><span class="form-label form-label-sm mb-0">Điểm câu này</span>` +
      `<input type="number" class="form-control form-control-sm session-item-points-input" min="0.01" max="1000" step="0.01" inputmode="decimal" placeholder="vd: 2" data-session-item-points></label>` +
      `<button class="btn sunny-mini session-phat-am-item-remove" type="button" data-session-phat-am-remove-question title="Xóa câu hỏi"><i class="bi bi-trash"></i></button>` +
      `</div>` +
      `<div class="session-listening-meta mt-0 mb-3"><div class="session-listening-mode-wrap">` +
      `<label class="form-label form-label-sm">Kiểu trả lời của học sinh</label>` +
      `<div class="session-listening-mode-toggle">` +
      `<label class="session-listening-mode-option"><input type="radio" name="${modeGroupName}" value="choice" data-phat-am-item-answer-mode-radio checked><span>Trắc nghiệm</span></label>` +
      `<label class="session-listening-mode-option"><input type="radio" name="${modeGroupName}" value="multi_select" data-phat-am-item-answer-mode-radio><span>Nhiều đáp án</span></label>` +
      `<label class="session-listening-mode-option"><input type="radio" name="${modeGroupName}" value="fill" data-phat-am-item-answer-mode-radio><span>Tự luận</span></label>` +
      `<label class="session-listening-mode-option"><input type="radio" name="${modeGroupName}" value="true_false" data-phat-am-item-answer-mode-radio><span>Đúng / Sai</span></label>` +
      `<label class="session-listening-mode-option"><input type="radio" name="${modeGroupName}" value="multi_choice" data-phat-am-item-answer-mode-radio><span>Nhiều TN</span></label>` +
      `</div></div></div>` +
      `<div class="mb-3"><label class="form-label form-label-sm">Câu hỏi</label>` +
      `<textarea class="form-control form-control-sm richtext-question richtext-compact" rows="2" data-session-phat-am-item-choice-question placeholder="Ví dụ: Cách phát âm nào sau đây là đúng?"></textarea></div>` +
      `<div class="session-phat-am-item-mode-panel" data-session-phat-am-item-choice-panel>` +
      `<label class="form-label form-label-sm" data-session-phat-am-item-choice-label>Các lựa chọn trả lời</label>` +
      `<div class="session-phat-am-item-options" data-session-phat-am-item-options data-choice-multi="0">` +
      ["a", "b", "c", "d"].map((key, index) =>
        `<div class="session-phat-am-item-option-row" data-session-phat-am-item-option-row data-option-key="${key}">` +
        `<button type="button" class="session-choice-option-mark${index === 0 ? " is-correct" : ""}" data-session-phat-am-item-mark="${key}">${key.toUpperCase()}</button>` +
        `<input class="form-control form-control-sm" type="text" placeholder="Lựa chọn ${key.toUpperCase()}" data-session-phat-am-item-option>` +
        `</div>`
      ).join("") +
      `</div><input type="hidden" value="a" data-session-phat-am-item-correct></div>` +
      `<div class="session-phat-am-item-mode-panel" data-session-phat-am-item-fill-panel hidden>` +
      `<label class="form-label form-label-sm">Đáp án mẫu (tự luận)</label>` +
      `<textarea class="form-control form-control-sm richtext-model-answer richtext-compact" rows="3" data-session-phat-am-item-correct-text placeholder="Nhập đáp án mẫu..."></textarea></div>` +
      `<div class="session-phat-am-item-mode-panel" data-session-phat-am-item-tf-panel hidden>` +
      `<label class="form-label form-label-sm">Danh sách câu đúng / sai</label>` +
      `<ol class="session-listening-true-false-list session-phat-am-item-tf-list" data-session-phat-am-item-tf-list></ol>` +
      `<button class="btn btn-outline btn-sm session-listening-true-false-add mt-2" type="button" data-session-phat-am-item-tf-add><i class="bi bi-plus-lg"></i> Thêm câu</button></div>` +
      `<div class="session-phat-am-item-mode-panel" data-session-phat-am-item-mc-panel hidden>` +
      `<label class="form-label form-label-sm">Danh sách câu trắc nghiệm</label>` +
      `<ol class="session-listening-multi-choice-list session-phat-am-item-mc-list" data-session-phat-am-item-mc-list></ol>` +
      `<button class="btn btn-outline btn-sm session-listening-multi-choice-add mt-2" type="button" data-session-phat-am-item-mc-add><i class="bi bi-plus-lg"></i> Thêm câu TN</button></div>`;

    const tfList = li.querySelector("[data-session-phat-am-item-tf-list]");
    if (tfList) {
      tfList.appendChild(createTfRow());
      reindexTfRows(tfList);
    }

    const mcList = li.querySelector("[data-session-phat-am-item-mc-list]");
    if (mcList) {
      mcList.appendChild(createMcRow());
      reindexMcRows(mcList);
    }

    return li;
  };

  const bindQuestionRow = (questionItem, itemRow, onChange) => {
    syncItemModePanels(itemRow);
    bindChoiceMarks(itemRow, onChange);

    itemRow.querySelectorAll("[data-session-phat-am-item-choice-question], [data-session-phat-am-item-option], [data-session-phat-am-item-correct-text], [data-session-phat-am-item-tf-statement], [data-session-item-points]").forEach((field) => {
      field.addEventListener("input", onChange);
    });

    itemRow.querySelectorAll("[data-session-phat-am-item-tf-answer]").forEach((field) => {
      field.addEventListener("change", onChange);
    });

    itemRow.querySelector("[data-session-phat-am-remove-question]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-phat-am-questions-list]");
      if ((list?.querySelectorAll("[data-session-phat-am-question]").length || 0) <= 1) return;
      window.ProgramQuestionEditors?.destroy?.(itemRow);
      itemRow.remove();
      if (list) reindexQuestions(questionItem, list);
      onChange();
    });

    const tfList = itemRow.querySelector("[data-session-phat-am-item-tf-list]");
    itemRow.querySelector("[data-session-phat-am-item-tf-add]")?.addEventListener("click", () => {
      if (!tfList) return;
      tfList.appendChild(createTfRow());
      reindexTfRows(tfList);
      bindTfRows(itemRow, onChange);
      onChange();
    });

    const mcList = itemRow.querySelector("[data-session-phat-am-item-mc-list]");
    itemRow.querySelector("[data-session-phat-am-item-mc-add]")?.addEventListener("click", () => {
      if (!mcList) return;
      const row = createMcRow();
      mcList.appendChild(row);
      bindMcMarks(row, onChange);
      reindexMcRows(mcList);
      bindMcRows(itemRow, onChange);
      onChange();
    });

    bindTfRows(itemRow, onChange);
    bindMcRows(itemRow, onChange);
  };

  const bindTfRows = (itemRow, onChange) => {
    itemRow.querySelectorAll("[data-session-phat-am-item-tf-row]").forEach((row) => {
      if (row.dataset.boundPhatAmTf === "1") return;
      row.dataset.boundPhatAmTf = "1";
      row.querySelector("[data-session-phat-am-item-tf-remove]")?.addEventListener("click", () => {
        const list = itemRow.querySelector("[data-session-phat-am-item-tf-list]");
        if ((list?.querySelectorAll("[data-session-phat-am-item-tf-row]").length || 0) <= 1) return;
        row.remove();
        if (list) reindexTfRows(list);
        onChange();
      });
      row.querySelector("[data-session-phat-am-item-tf-statement]")?.addEventListener("input", onChange);
      row.querySelectorAll("[data-session-phat-am-item-tf-answer]").forEach((field) => field.addEventListener("change", onChange));
    });
  };

  const bindMcRows = (itemRow, onChange) => {
    itemRow.querySelectorAll("[data-session-phat-am-item-mc-row]").forEach((row) => {
      if (row.dataset.boundPhatAmMc === "1") return;
      row.dataset.boundPhatAmMc = "1";
      bindMcMarks(row, onChange);
      row.querySelector("[data-session-phat-am-item-mc-remove]")?.addEventListener("click", () => {
        const list = itemRow.querySelector("[data-session-phat-am-item-mc-list]");
        if ((list?.querySelectorAll("[data-session-phat-am-item-mc-row]").length || 0) <= 1) return;
        row.remove();
        if (list) reindexMcRows(list);
        onChange();
      });
      row.querySelector("[data-session-phat-am-item-mc-question]")?.addEventListener("input", onChange);
      row.querySelectorAll("[data-session-phat-am-item-mc-option]").forEach((field) => field.addEventListener("input", onChange));
    });
  };

  const bindQuestionItem = (questionItem, onChange) => {
    const schedule = () => {
      syncPhatAmRecordingHint(questionItem);
      syncPhatAmItemsJson(questionItem);
      onChange?.();
    };

    const questionsList = questionItem.querySelector("[data-session-phat-am-questions-list]");

    if (questionsList && questionsList.dataset.phatAmModeBound !== "1") {
      questionsList.dataset.phatAmModeBound = "1";
      questionsList.addEventListener("change", (event) => {
        const radio = event.target.closest("[data-phat-am-item-answer-mode-radio]");
        if (!radio) return;
        const itemRow = radio.closest("[data-session-phat-am-question]");
        if (!itemRow) return;
        syncItemModePanels(itemRow);
        schedule();
      });
    }

    questionItem.querySelector("[data-session-phat-am-prompt]")?.addEventListener("input", schedule);

    questionItem.querySelectorAll("[data-session-phat-am-require-recording-radio]").forEach((radio) => {
      radio.addEventListener("change", schedule);
    });

    assignModeRadioNames(questionItem);
    questionItem.querySelectorAll("[data-session-phat-am-question]").forEach((itemRow) => {
      syncItemModePanels(itemRow);
      bindQuestionRow(questionItem, itemRow, schedule);
    });

    questionItem.querySelector("[data-session-phat-am-add-question]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-phat-am-questions-list]");
      if (!list) return;
      const row = createQuestionRow();
      list.appendChild(row);
      bindQuestionRow(questionItem, row, schedule);
      reindexQuestions(questionItem, list);
      window.ProgramQuestionEditors?.init?.(row);
      schedule();
    });

    syncPhatAmRecordingHint(questionItem);
    syncPhatAmItemsJson(questionItem);
  };

  window.ProgramPhatAmItems = {
    bindIn(questionItem, onChange) {
      if (!questionItem?.querySelector("[data-session-phat-am-questions-list]")) return;
      bindQuestionItem(questionItem, onChange);
    },
    syncIn(questionItem) {
      if (!questionItem) return;
      assignModeRadioNames(questionItem);
      questionItem.querySelectorAll("[data-session-phat-am-question]").forEach((row) => syncItemModePanels(row));
      syncPhatAmRecordingHint(questionItem);
      syncPhatAmItemsJson(questionItem);
    },
    defaultQuestion,
    defaultItem: defaultQuestion,
  };
})();
