(() => {
  const optionKeyAt = (index) => String.fromCharCode(97 + index);

  const bindMultiChoicePanel = (scope, options = {}) => {
    const root = scope?.matches?.("[data-session-listening-multi-choice-panel]")
      ? scope
      : scope?.querySelector?.("[data-session-listening-multi-choice-panel]");

    if (!root || root.dataset.listeningMcBound === "1") return;
    root.dataset.listeningMcBound = "1";

    const scheduleAutosave = typeof options.scheduleAutosave === "function" ? options.scheduleAutosave : () => {};
    const jsonInput = root.querySelector("[data-session-listening-multi-choice-json]");
    const list = root.querySelector("[data-session-listening-multi-choice-list]");
    if (!jsonInput || !list) return;

    const getCorrectMark = (item) => item.querySelector("[data-session-listening-mc-mark].is-correct")?.dataset.sessionListeningMcMark || "a";

    const setCorrectMark = (item, key) => {
      item.querySelectorAll("[data-session-listening-mc-mark]").forEach((button) => {
        button.classList.toggle("is-correct", button.dataset.sessionListeningMcMark === key);
      });
    };

    const reindexMcOptions = (item) => {
      const rows = [...item.querySelectorAll("[data-session-listening-mc-option-row]")];
      const currentCorrect = getCorrectMark(item);
      let correctKey = currentCorrect;

      rows.forEach((row, index) => {
        const key = optionKeyAt(index);
        row.dataset.optionKey = key;
        const mark = row.querySelector("[data-session-listening-mc-mark]");
        const input = row.querySelector("[data-session-listening-mc-option]");
        const removeBtn = row.querySelector("[data-session-listening-mc-option-remove]");

        if (mark) {
          mark.dataset.sessionListeningMcMark = key;
          mark.textContent = key.toUpperCase();
          mark.classList.toggle("is-correct", key === correctKey);
        }
        if (input) input.placeholder = `Lựa chọn ${key.toUpperCase()}`;
        if (removeBtn) removeBtn.hidden = rows.length <= 2;
      });

      if (!rows.some((row) => row.dataset.optionKey === correctKey)) {
        correctKey = rows[0]?.dataset.optionKey || "a";
        setCorrectMark(item, correctKey);
      }

      const addOptionBtn = item.querySelector("[data-session-listening-mc-add-option]");
      if (addOptionBtn) addOptionBtn.hidden = rows.length >= 8;
    };

    const reindexMcItems = () => {
      const items = list.querySelectorAll("[data-session-listening-multi-choice-item]");
      items.forEach((item, index) => {
        const badge = item.querySelector(".session-listening-multi-choice-badge");
        if (badge) badge.textContent = String(index + 1);
        const removeItemBtn = item.querySelector("[data-session-listening-multi-choice-remove-item]");
        if (removeItemBtn) removeItemBtn.hidden = items.length <= 1;
        reindexMcOptions(item);
      });
    };

    const syncListeningMultiChoiceJson = () => {
      const items = [];
      list.querySelectorAll("[data-session-listening-multi-choice-item]").forEach((item) => {
        const question = item.querySelector("[data-session-listening-multi-choice-question]")?.value.trim() || "";
        const options = [];
        item.querySelectorAll("[data-session-listening-mc-option]").forEach((input) => {
          options.push(input.value.trim());
        });
        items.push({
          question,
          options,
          correct: getCorrectMark(item),
        });
      });
      jsonInput.value = JSON.stringify(items);
    };

    const createMcOptionRow = (key, isCorrect = false) => {
      const row = document.createElement("div");
      row.className = "session-choice-option-row session-listening-mc-option-row";
      row.dataset.sessionListeningMcOptionRow = "";
      row.dataset.optionKey = key;
      row.innerHTML =
        `<button type="button" class="session-choice-option-mark${isCorrect ? " is-correct" : ""}" data-session-listening-mc-mark="${key}" title="Đánh dấu đáp án đúng">${key.toUpperCase()}</button>` +
        `<input class="form-control form-control-sm session-choice-option-input session-listening-mc-option-input" type="text" data-session-listening-mc-option placeholder="Lựa chọn ${key.toUpperCase()}">` +
        `<button class="btn sunny-mini session-choice-option-remove session-listening-mc-option-remove" type="button" data-session-listening-mc-option-remove title="Xóa lựa chọn"><i class="bi bi-x-lg" aria-hidden="true"></i></button>`;
      return row;
    };

    const createMcItem = () => {
      const item = document.createElement("li");
      item.className = "session-listening-multi-choice-item";
      item.dataset.sessionListeningMultiChoiceItem = "";
      item.innerHTML =
        `<div class="session-listening-multi-choice-item-head">` +
        `<span class="session-listening-multi-choice-badge">1</span>` +
        `<input type="text" class="form-control form-control-sm session-listening-multi-choice-question" data-session-listening-multi-choice-question placeholder="Nội dung câu hỏi con...">` +
        `<button class="btn sunny-mini session-listening-multi-choice-remove-item" type="button" data-session-listening-multi-choice-remove-item title="Xóa câu"><i class="bi bi-x-lg" aria-hidden="true"></i></button>` +
        `</div>` +
        `<div class="session-listening-multi-choice-options" data-session-listening-multi-choice-options></div>` +
        `<button class="btn btn-outline btn-sm session-listening-mc-add-option mt-1" type="button" data-session-listening-mc-add-option><i class="bi bi-plus-lg"></i> Thêm lựa chọn</button>`;

      const optionsWrap = item.querySelector("[data-session-listening-multi-choice-options]");
      optionsWrap.appendChild(createMcOptionRow("a", true));
      optionsWrap.appendChild(createMcOptionRow("b", false));
      return item;
    };

    const bindMcOptionRow = (row, item) => {
      row.querySelector("[data-session-listening-mc-mark]")?.addEventListener("click", () => {
        setCorrectMark(item, row.dataset.optionKey || "a");
        syncListeningMultiChoiceJson();
        scheduleAutosave();
      });

      row.querySelector("[data-session-listening-mc-option]")?.addEventListener("input", () => {
        syncListeningMultiChoiceJson();
        scheduleAutosave();
      });

      row.querySelector("[data-session-listening-mc-option-remove]")?.addEventListener("click", () => {
        const optionsWrap = item.querySelector("[data-session-listening-multi-choice-options]");
        if (!optionsWrap || optionsWrap.querySelectorAll("[data-session-listening-mc-option-row]").length <= 2) return;
        row.remove();
        reindexMcOptions(item);
        syncListeningMultiChoiceJson();
        scheduleAutosave();
      });
    };

    const bindMcItem = (item) => {
      item.querySelectorAll("[data-session-listening-mc-option-row]").forEach((row) => bindMcOptionRow(row, item));

      item.querySelector("[data-session-listening-multi-choice-question]")?.addEventListener("input", () => {
        syncListeningMultiChoiceJson();
        scheduleAutosave();
      });

      item.querySelector("[data-session-listening-mc-add-option]")?.addEventListener("click", () => {
        const optionsWrap = item.querySelector("[data-session-listening-multi-choice-options]");
        if (!optionsWrap || optionsWrap.querySelectorAll("[data-session-listening-mc-option-row]").length >= 8) return;
        const nextIndex = optionsWrap.querySelectorAll("[data-session-listening-mc-option-row]").length;
        const row = createMcOptionRow(optionKeyAt(nextIndex), false);
        optionsWrap.appendChild(row);
        bindMcOptionRow(row, item);
        reindexMcOptions(item);
        syncListeningMultiChoiceJson();
        row.querySelector("[data-session-listening-mc-option]")?.focus();
        scheduleAutosave();
      });

      item.querySelector("[data-session-listening-multi-choice-remove-item]")?.addEventListener("click", () => {
        if (list.querySelectorAll("[data-session-listening-multi-choice-item]").length <= 1) return;
        item.remove();
        reindexMcItems();
        syncListeningMultiChoiceJson();
        scheduleAutosave();
      });
    };

    list.querySelectorAll("[data-session-listening-multi-choice-item]").forEach(bindMcItem);

    root.querySelector("[data-session-listening-multi-choice-add]")?.addEventListener("click", () => {
      const item = createMcItem();
      list.appendChild(item);
      bindMcItem(item);
      reindexMcItems();
      syncListeningMultiChoiceJson();
      item.querySelector("[data-session-listening-multi-choice-question]")?.focus();
      scheduleAutosave();
    });

    reindexMcItems();
    syncListeningMultiChoiceJson();
  };

  const syncPanel = (panel) => {
    if (!panel) return;
    const jsonInput = panel.querySelector("[data-session-listening-multi-choice-json]");
    const list = panel.querySelector("[data-session-listening-multi-choice-list]");
    if (!jsonInput || !list) return;

    const items = [];
    list.querySelectorAll("[data-session-listening-multi-choice-item]").forEach((item) => {
      const question = item.querySelector("[data-session-listening-multi-choice-question]")?.value.trim() || "";
      const options = [];
      item.querySelectorAll("[data-session-listening-mc-option]").forEach((input) => {
        options.push(input.value.trim());
      });
      const correct =
        item.querySelector("[data-session-listening-mc-mark].is-correct")?.dataset.sessionListeningMcMark || "a";
      items.push({ question, options, correct });
    });
    jsonInput.value = JSON.stringify(items);
  };

  const syncIn = (scope) => {
    const container = scope || document;
    container.querySelectorAll("[data-session-listening-multi-choice-panel]").forEach(syncPanel);
  };

  const bindIn = (scope, options = {}) => {
    const container = scope || document;
    if (container.matches?.("[data-session-listening-multi-choice-panel]")) {
      bindMultiChoicePanel(container, options);
      return;
    }
    container.querySelectorAll("[data-session-listening-multi-choice-panel]").forEach((panel) => {
      bindMultiChoicePanel(panel, options);
    });
  };

  window.ProgramListeningMultiChoice = { bind: bindMultiChoicePanel, bindIn, syncIn, syncPanel };
})();
