(() => {
  const defaultQuestion = () => ({
    answer_mode: "choice",
    choice_question: "",
    options: ["", "", "", ""],
    correct: "a",
    correct_text: "",
    true_false_items: [{ statement: "", answer: true }],
    multi_choice_items: [{ question: "", options: ["", ""], correct: "a" }],
    word_link_pairs: [{ a_type: "image", a_value: "", b_type: "text", b_value: "" }],
    fill_blanks: { template: "", answers: ["", ""] },
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
    itemRow.querySelector("[data-listening-item-answer-mode-radio]:checked")?.value || "choice";

  const syncItemModePanels = (itemRow) => {
    const mode = getMode(itemRow);
    itemRow.querySelector("[data-session-listening-item-choice-panel]")?.toggleAttribute("hidden", mode !== "choice");
    itemRow.querySelector("[data-session-listening-item-fill-panel]")?.toggleAttribute("hidden", mode !== "fill");
    itemRow.querySelector("[data-session-listening-item-tf-panel]")?.toggleAttribute("hidden", mode !== "true_false");
    itemRow.querySelector("[data-session-listening-item-mc-panel]")?.toggleAttribute("hidden", mode !== "multi_choice");
    itemRow.querySelector("[data-session-listening-item-word-link-panel]")?.toggleAttribute("hidden", mode !== "word_link");
    itemRow.querySelector("[data-session-listening-item-fill-blanks-panel]")?.toggleAttribute("hidden", mode !== "fill_blanks");
  };

  const getWordLinkSlotValue = (row, slot) => {
    const type = row.querySelector(`[data-session-pair-slot-type="${slot}"]`)?.value || (slot === "a" ? "image" : "text");
    if (type === "image") {
      const imageField = row.querySelector(`[data-session-pair-slot-image-value="${slot}"]`);
      return window.ProgramPairImageEditor?.getValue?.(imageField) || imageField?.value.trim() || "";
    }
    return row.querySelector(`[data-session-pair-slot-text-value="${slot}"]`)?.value.trim() || "";
  };

  const collectWordLinkPairs = (itemRow) => {
    const list = itemRow.querySelector("[data-session-listening-item-word-link-list]");
    if (!list) return [];
    const pairs = [];
    list.querySelectorAll("[data-session-word-link-pair-item]").forEach((row) => {
      pairs.push({
        a_type: row.querySelector('[data-session-pair-slot-type="a"]')?.value || "image",
        a_value: getWordLinkSlotValue(row, "a"),
        b_type: row.querySelector('[data-session-pair-slot-type="b"]')?.value || "text",
        b_value: getWordLinkSlotValue(row, "b"),
      });
    });
    return pairs;
  };

  const collectFillBlanks = (itemRow) => {
    const template = itemRow.querySelector("[data-session-listening-item-fill-blanks-template]")?.value.trim() || "";
    const answers = [...itemRow.querySelectorAll("[data-session-listening-item-fill-blanks-answer-input]")].map(
      (input) => input.value.trim()
    );
    return { template, answers };
  };

  const collectChoiceOptions = (itemRow) => {
    const options = [];
    itemRow.querySelectorAll("[data-session-listening-item-option]").forEach((input) => {
      const value = input.value.trim();
      if (value !== "") options.push(value);
    });
    return options;
  };

  const collectTrueFalseItems = (itemRow) =>
    [...itemRow.querySelectorAll("[data-session-listening-item-tf-row]")].map((row) => ({
      statement: row.querySelector("[data-session-listening-item-tf-statement]")?.value.trim() || "",
      answer: row.querySelector("[data-session-listening-item-tf-answer]:checked")?.value === "true",
    }));

  const collectMultiChoiceItems = (itemRow) =>
    [...itemRow.querySelectorAll("[data-session-listening-item-mc-row]")].map((row) => {
      const options = [];
      row.querySelectorAll("[data-session-listening-item-mc-option]").forEach((input) => {
        const value = input.value.trim();
        if (value !== "") options.push(value);
      });
      return {
        question: row.querySelector("[data-session-listening-item-mc-question]")?.value.trim() || "",
        options,
        correct: row.querySelector("[data-session-listening-item-mc-correct]")?.value.trim() || "a",
      };
    });

  const collectQuestion = (itemRow) => {
    window.ProgramQuestionEditors?.save?.(itemRow);
    const mode = getMode(itemRow);
    const payload = {
      answer_mode: mode,
      choice_question: itemRow.querySelector("[data-session-listening-item-choice-question]")?.value.trim() || "",
      options: collectChoiceOptions(itemRow),
      correct: itemRow.querySelector("[data-session-listening-item-correct]")?.value.trim() || "a",
      correct_text: itemRow.querySelector("[data-session-listening-item-correct-text]")?.value.trim() || "",
      true_false_items: collectTrueFalseItems(itemRow),
      multi_choice_items: collectMultiChoiceItems(itemRow),
      word_link_pairs: collectWordLinkPairs(itemRow),
      fill_blanks: collectFillBlanks(itemRow),
    };
    const points = parseOptionalPoints(itemRow.querySelector("[data-session-item-points]"));
    if (points !== null) payload.points = points;
    return payload;
  };

  const syncListeningItemsJson = (questionItem) => {
    const jsonInput = questionItem.querySelector("[data-session-listening-json]");
    if (!jsonInput) return;

    const audio = questionItem.querySelector("[data-qb-audio-saved]")?.value.trim() || "";
    const questions = [...questionItem.querySelectorAll("[data-session-listening-question]")].map((row) => collectQuestion(row));

    jsonInput.value = JSON.stringify({ text: "", audio, lang: "ko-KR", questions });
    syncParentPointsFromItems(questionItem);
  };

  const assignModeRadioNames = (questionItem) => {
    const baseId = questionItem.dataset.questionIndex || questionItem.id || "q";
    questionItem.querySelectorAll("[data-session-listening-question]").forEach((row, index) => {
      const groupName = `listening-mode-${baseId}-${index}`;
      row.querySelectorAll("[data-listening-item-answer-mode-radio]").forEach((radio) => {
        radio.name = groupName;
      });
    });
  };

  const reindexQuestions = (questionItem, list) => {
    list.querySelectorAll("[data-session-listening-question]").forEach((row, index) => {
      const badge = row.querySelector("[data-session-listening-item-badge]");
      if (badge) badge.textContent = String(index + 1);
      const removeBtn = row.querySelector("[data-session-listening-remove-question]");
      if (removeBtn) removeBtn.hidden = list.querySelectorAll("[data-session-listening-question]").length <= 1;
    });
    assignModeRadioNames(questionItem);
  };

  const reindexTfRows = (list) => {
    list.querySelectorAll("[data-session-listening-item-tf-row]").forEach((row, index) => {
      const badge = row.querySelector(".session-listening-true-false-badge");
      if (badge) badge.textContent = String(index + 1);
      const removeBtn = row.querySelector("[data-session-listening-item-tf-remove]");
      if (removeBtn) removeBtn.hidden = list.querySelectorAll("[data-session-listening-item-tf-row]").length <= 1;
    });
  };

  const reindexMcRows = (list) => {
    list.querySelectorAll("[data-session-listening-item-mc-row]").forEach((row, index) => {
      const badge = row.querySelector(".session-listening-multi-choice-badge");
      if (badge) badge.textContent = String(index + 1);
      const removeBtn = row.querySelector("[data-session-listening-item-mc-remove]");
      if (removeBtn) removeBtn.hidden = list.querySelectorAll("[data-session-listening-item-mc-row]").length <= 1;
    });
  };

  const countBlanksInTemplate = (template) => (template.match(/_{3,}/g) || []).length;

  const rebuildFillBlanksAnswerRows = (itemRow, onChange) => {
    const template = itemRow.querySelector("[data-session-listening-item-fill-blanks-template]")?.value || "";
    const list = itemRow.querySelector("[data-session-listening-item-fill-blanks-answers-list]");
    if (!list) return;

    const existing = [...list.querySelectorAll("[data-session-listening-item-fill-blanks-answer-input]")].map((input) => input.value);
    let blankCount = countBlanksInTemplate(template);
    if (blankCount < 1) blankCount = Math.max(existing.length, 2);

    list.innerHTML = "";
    for (let i = 0; i < blankCount; i += 1) {
      const li = document.createElement("li");
      li.className = "session-fill-blanks-answer-item";
      li.innerHTML =
        `<span class="session-fill-blanks-answer-no">Ô ${i + 1}</span>` +
        `<input type="text" class="form-control form-control-sm session-fill-blanks-answer-input" data-session-listening-item-fill-blanks-answer-input placeholder="Đáp án cho ô ${i + 1}" value="${(existing[i] || "").replace(/"/g, "&quot;")}">`;
      list.appendChild(li);
      li.querySelector("input")?.addEventListener("input", () => onChange?.());
    }
  };

  const bindChoiceMarks = (itemRow, onChange) => {
    itemRow.querySelectorAll("[data-session-listening-item-mark]").forEach((button) => {
      button.addEventListener("click", () => {
        const key = button.dataset.sessionListeningItemMark || "a";
        itemRow.querySelectorAll("[data-session-listening-item-mark]").forEach((mark) => mark.classList.remove("is-correct"));
        button.classList.add("is-correct");
        const hidden = itemRow.querySelector("[data-session-listening-item-correct]");
        if (hidden) hidden.value = key;
        onChange();
      });
    });
  };

  const bindMcMarks = (mcRow, onChange) => {
    mcRow.querySelectorAll("[data-session-listening-item-mc-mark]").forEach((button) => {
      button.addEventListener("click", () => {
        const key = button.dataset.sessionListeningItemMcMark || "a";
        mcRow.querySelectorAll("[data-session-listening-item-mc-mark]").forEach((mark) => mark.classList.remove("is-correct"));
        button.classList.add("is-correct");
        const hidden = mcRow.querySelector("[data-session-listening-item-mc-correct]");
        if (hidden) hidden.value = key;
        onChange();
      });
    });
  };

  const createTfRow = () => {
    const li = document.createElement("li");
    li.className = "session-listening-true-false-item";
    li.dataset.sessionListeningItemTfRow = "";
    li.innerHTML =
      `<span class="session-listening-true-false-badge">1</span>` +
      `<input type="text" class="form-control form-control-sm session-listening-true-false-statement" data-session-listening-item-tf-statement placeholder="VD: Học sinh đi học lúc 8 giờ sáng.">` +
      `<div class="session-listening-true-false-answer-toggle" role="group">` +
      `<label class="session-listening-true-false-answer-option"><input type="radio" value="true" data-session-listening-item-tf-answer checked><span>Đúng</span></label>` +
      `<label class="session-listening-true-false-answer-option"><input type="radio" value="false" data-session-listening-item-tf-answer><span>Sai</span></label>` +
      `</div>` +
      `<button class="btn sunny-mini session-listening-true-false-remove" type="button" data-session-listening-item-tf-remove title="Xóa câu"><i class="bi bi-x-lg"></i></button>`;
    return li;
  };

  const createMcRow = () => {
    const li = document.createElement("li");
    li.className = "session-listening-multi-choice-item";
    li.dataset.sessionListeningItemMcRow = "";
    li.innerHTML =
      `<div class="session-listening-multi-choice-item-head">` +
      `<span class="session-listening-multi-choice-badge">1</span>` +
      `<input type="text" class="form-control form-control-sm session-listening-multi-choice-question" data-session-listening-item-mc-question placeholder="Câu hỏi trắc nghiệm">` +
      `<button class="btn sunny-mini session-listening-multi-choice-remove-item" type="button" data-session-listening-item-mc-remove title="Xóa câu"><i class="bi bi-x-lg"></i></button>` +
      `</div>` +
      `<div class="session-listening-multi-choice-options" data-session-listening-item-mc-options>` +
      `<div class="session-listening-mc-option-row" data-session-listening-item-mc-option-row data-option-key="a">` +
      `<button type="button" class="session-choice-option-mark is-correct" data-session-listening-item-mc-mark="a">A</button>` +
      `<input type="text" class="form-control form-control-sm" placeholder="Lựa chọn A" data-session-listening-item-mc-option>` +
      `</div>` +
      `<div class="session-listening-mc-option-row" data-session-listening-item-mc-option-row data-option-key="b">` +
      `<button type="button" class="session-choice-option-mark" data-session-listening-item-mc-mark="b">B</button>` +
      `<input type="text" class="form-control form-control-sm" placeholder="Lựa chọn B" data-session-listening-item-mc-option>` +
      `</div>` +
      `</div>` +
      `<input type="hidden" value="a" data-session-listening-item-mc-correct>`;
    return li;
  };

  const bindTfRows = (itemRow, onChange) => {
    itemRow.querySelectorAll("[data-session-listening-item-tf-row]").forEach((row) => {
      if (row.dataset.boundListeningTf === "1") return;
      row.dataset.boundListeningTf = "1";
      row.querySelector("[data-session-listening-item-tf-remove]")?.addEventListener("click", () => {
        const list = itemRow.querySelector("[data-session-listening-item-tf-list]");
        if ((list?.querySelectorAll("[data-session-listening-item-tf-row]").length || 0) <= 1) return;
        row.remove();
        if (list) reindexTfRows(list);
        onChange();
      });
      row.querySelector("[data-session-listening-item-tf-statement]")?.addEventListener("input", onChange);
      row.querySelectorAll("[data-session-listening-item-tf-answer]").forEach((field) => field.addEventListener("change", onChange));
    });
  };

  const bindMcRows = (itemRow, onChange) => {
    itemRow.querySelectorAll("[data-session-listening-item-mc-row]").forEach((row) => {
      if (row.dataset.boundListeningMc === "1") return;
      row.dataset.boundListeningMc = "1";
      bindMcMarks(row, onChange);
      row.querySelector("[data-session-listening-item-mc-remove]")?.addEventListener("click", () => {
        const list = itemRow.querySelector("[data-session-listening-item-mc-list]");
        if ((list?.querySelectorAll("[data-session-listening-item-mc-row]").length || 0) <= 1) return;
        row.remove();
        if (list) reindexMcRows(list);
        onChange();
      });
      row.querySelector("[data-session-listening-item-mc-question]")?.addEventListener("input", onChange);
      row.querySelectorAll("[data-session-listening-item-mc-option]").forEach((field) => field.addEventListener("input", onChange));
    });
  };

  const resetListeningQuestionRow = (questionItem, itemRow, onChange) => {
    itemRow.querySelectorAll("[data-session-listening-item-tf-row]").forEach((row) => {
      delete row.dataset.boundListeningTf;
    });
    itemRow.querySelectorAll("[data-session-listening-item-mc-row]").forEach((row) => {
      delete row.dataset.boundListeningMc;
    });
    itemRow.querySelectorAll("[data-session-word-link-pair-item]").forEach((row) => {
      delete row.dataset.wordLinkRowBound;
    });

    const tfList = itemRow.querySelector("[data-session-listening-item-tf-list]");
    if (tfList) {
      const tfRows = [...tfList.querySelectorAll("[data-session-listening-item-tf-row]")];
      tfRows.slice(1).forEach((row) => row.remove());
      if (tfRows[0]) {
        const statement = tfRows[0].querySelector("[data-session-listening-item-tf-statement]");
        if (statement) statement.value = "";
        tfRows[0].querySelectorAll("[data-session-listening-item-tf-answer]").forEach((field, index) => {
          field.checked = index === 0;
        });
      }
      reindexTfRows(tfList);
    }

    const mcList = itemRow.querySelector("[data-session-listening-item-mc-list]");
    if (mcList) {
      const mcRows = [...mcList.querySelectorAll("[data-session-listening-item-mc-row]")];
      mcRows.slice(1).forEach((row) => row.remove());
      if (mcRows[0]) {
        mcRows[0].querySelector("[data-session-listening-item-mc-question]") &&
          (mcRows[0].querySelector("[data-session-listening-item-mc-question]").value = "");
        mcRows[0].querySelectorAll("[data-session-listening-item-mc-option]").forEach((field) => {
          field.value = "";
        });
        mcRows[0].querySelectorAll("[data-session-listening-item-mc-mark]").forEach((mark, index) => {
          mark.classList.toggle("is-correct", index === 0);
        });
        const mcCorrect = mcRows[0].querySelector("[data-session-listening-item-mc-correct]");
        if (mcCorrect) mcCorrect.value = "a";
      }
      reindexMcRows(mcList);
    }

    const wordLinkList = itemRow.querySelector("[data-session-listening-item-word-link-list]");
    if (wordLinkList) {
      const pairRows = [...wordLinkList.querySelectorAll("[data-session-word-link-pair-item]")];
      pairRows.slice(1).forEach((row) => row.remove());
      const firstPair = pairRows[0];
      if (firstPair) {
        window.ProgramWordLinkPairHelpers?.resetRow?.(firstPair);
      }
      questionItem.dispatchEvent(new CustomEvent("listening-bind-word-link-pairs", { detail: { itemRow }, bubbles: true }));
    }

    rebuildFillBlanksAnswerRows(itemRow, onChange);
    syncItemModePanels(itemRow);
  };

  const bindQuestionRow = (questionItem, itemRow, onChange) => {
    syncItemModePanels(itemRow);
    bindChoiceMarks(itemRow, onChange);

    itemRow.querySelectorAll(
      "[data-session-listening-item-choice-question], [data-session-listening-item-option], [data-session-listening-item-correct-text], [data-session-listening-item-tf-statement], [data-session-listening-item-fill-blanks-template], [data-session-listening-item-fill-blanks-answer-input], [data-session-item-points]"
    ).forEach((field) => field.addEventListener("input", onChange));

    itemRow.querySelectorAll("[data-session-listening-item-tf-answer]").forEach((field) => field.addEventListener("change", onChange));

    itemRow.querySelector("[data-session-listening-remove-question]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-listening-questions-list]");
      if ((list?.querySelectorAll("[data-session-listening-question]").length || 0) <= 1) return;
      window.ProgramQuestionEditors?.destroy?.(itemRow);
      itemRow.remove();
      if (list) reindexQuestions(questionItem, list);
      onChange();
    });

    itemRow.querySelector("[data-session-listening-item-tf-add]")?.addEventListener("click", () => {
      const list = itemRow.querySelector("[data-session-listening-item-tf-list]");
      if (!list) return;
      list.appendChild(createTfRow());
      reindexTfRows(list);
      bindTfRows(itemRow, onChange);
      onChange();
    });

    itemRow.querySelector("[data-session-listening-item-mc-add]")?.addEventListener("click", () => {
      const list = itemRow.querySelector("[data-session-listening-item-mc-list]");
      if (!list) return;
      const row = createMcRow();
      list.appendChild(row);
      bindMcMarks(row, onChange);
      reindexMcRows(list);
      bindMcRows(itemRow, onChange);
      onChange();
    });

    itemRow.querySelector("[data-session-listening-item-fill-blanks-insert]")?.addEventListener("click", () => {
      const templateInput = itemRow.querySelector("[data-session-listening-item-fill-blanks-template]");
      if (!templateInput) return;
      const start = templateInput.selectionStart ?? templateInput.value.length;
      const end = templateInput.selectionEnd ?? start;
      templateInput.value = `${templateInput.value.slice(0, start)} ___ ${templateInput.value.slice(end)}`;
      templateInput.focus();
      templateInput.selectionStart = templateInput.selectionEnd = start + 4;
      rebuildFillBlanksAnswerRows(itemRow, onChange);
      onChange();
    });

    itemRow.querySelector("[data-session-listening-item-fill-blanks-template]")?.addEventListener("input", () => {
      rebuildFillBlanksAnswerRows(itemRow, onChange);
      onChange();
    });

    itemRow.querySelector("[data-session-listening-item-word-link-add]")?.addEventListener("click", () => {
      questionItem.dispatchEvent(new CustomEvent("listening-add-word-link-pair", { detail: { itemRow }, bubbles: true }));
      onChange();
    });

    questionItem.dispatchEvent(new CustomEvent("listening-bind-word-link-pairs", { detail: { itemRow }, bubbles: true }));

    bindTfRows(itemRow, onChange);
    bindMcRows(itemRow, onChange);
  };

  const bindQuestionItem = (questionItem, onChange) => {
    const schedule = () => {
      syncListeningItemsJson(questionItem);
      onChange?.();
    };

    const questionsList = questionItem.querySelector("[data-session-listening-questions-list]");

    if (questionsList && questionsList.dataset.listeningModeBound !== "1") {
      questionsList.dataset.listeningModeBound = "1";
      questionsList.addEventListener("change", (event) => {
        const radio = event.target.closest("[data-listening-item-answer-mode-radio]");
        if (!radio) return;
        const itemRow = radio.closest("[data-session-listening-question]");
        if (!itemRow) return;
        syncItemModePanels(itemRow);
        if (getMode(itemRow) === "fill_blanks") {
          rebuildFillBlanksAnswerRows(itemRow, schedule);
        }
        schedule();
      });
    }

    questionItem.querySelector("[data-qb-audio-saved]")?.addEventListener("input", schedule);
    questionItem.querySelector("[data-qb-audio-saved]")?.addEventListener("change", schedule);

    assignModeRadioNames(questionItem);
    questionItem.querySelectorAll("[data-session-listening-question]").forEach((itemRow) => {
      syncItemModePanels(itemRow);
      bindQuestionRow(questionItem, itemRow, schedule);
    });

    questionItem.querySelector("[data-session-listening-add-question]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-listening-questions-list]");
      const template = list?.querySelector("[data-session-listening-question]");
      if (!list || !template) return;

      const row = template.cloneNode(true);
      window.ProgramQuestionEditors?.cleanupClone?.(row);
      row.querySelectorAll("input[type='text'], input[type='number'], textarea").forEach((field) => {
        field.value = "";
      });
      row.querySelectorAll("[data-listening-item-answer-mode-radio]").forEach((radio, index) => {
        radio.checked = index === 0;
      });
      row.querySelectorAll("[data-session-listening-item-mark], [data-session-listening-item-mc-mark]").forEach((mark, index) => {
        mark.classList.toggle("is-correct", index === 0);
      });
      const correctHidden = row.querySelector("[data-session-listening-item-correct]");
      if (correctHidden) correctHidden.value = "a";

      list.appendChild(row);
      resetListeningQuestionRow(questionItem, row, schedule);
      bindQuestionRow(questionItem, row, schedule);
      reindexQuestions(questionItem, list);
      window.ProgramQuestionEditors?.init?.(row);
      schedule();
    });

    syncListeningItemsJson(questionItem);
  };

  window.ProgramListeningItems = {
    bindIn(questionItem, onChange) {
      if (!questionItem?.querySelector("[data-session-listening-questions-list]")) return;
      bindQuestionItem(questionItem, onChange);
    },
    syncIn(questionItem) {
      if (!questionItem) return;
      assignModeRadioNames(questionItem);
      questionItem.querySelectorAll("[data-session-listening-question]").forEach((row) => syncItemModePanels(row));
      syncListeningItemsJson(questionItem);
    },
    defaultQuestion,
    rebuildFillBlanksAnswerRows,
    resetListeningQuestionRow,
    syncItemModePanels,
    collectQuestion,
    syncListeningItemsJson,
  };
})();
