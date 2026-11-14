(() => {
  const OPTION_LETTERS = ["a", "b", "c", "d", "e", "f", "g", "h"];

  const splitBulkText = (text) =>
    String(text || "")
      .split(/[\n\r|/]+/)
      .map((part) => part.trim())
      .filter(Boolean);

  const bindChoiceOptions = (scope, { onChange } = {}) => {
    const list = scope?.querySelector("[data-session-choice-options-list]");
    if (!list || list.dataset.choiceOptionsBound) return;
    list.dataset.choiceOptionsBound = "1";

    const notify = () => {
      if (typeof onChange === "function") onChange();
    };

    const getCorrectInput = () => scope.querySelector("[data-session-choice-correct]");
    const getOptionRows = () => [...list.querySelectorAll("[data-session-choice-option-row]")];
    const getOptionInputs = () => [...list.querySelectorAll("[data-session-question-option]")];
    const isMulti = () => list.dataset.choiceMulti === "1";

    const setCorrect = (key) => {
      const normalized = String(key || "a").toLowerCase();

      if (isMulti()) {
        const correctInput = getCorrectInput();
        let current = (correctInput?.value || "")
          .split(",")
          .map((part) => part.trim().toLowerCase())
          .filter(Boolean);

        if (current.includes(normalized)) {
          current = current.filter((part) => part !== normalized);
        } else {
          current.push(normalized);
        }

        current.sort();

        if (correctInput) {
          correctInput.value = current.join(",");
        }

        list.querySelectorAll("[data-session-choice-mark]").forEach((button) => {
          button.classList.toggle("is-correct", current.includes(button.dataset.sessionChoiceMark));
        });
        notify();
        return;
      }

      const correctInput = getCorrectInput();
      if (correctInput) correctInput.value = normalized;

      list.querySelectorAll("[data-session-choice-mark]").forEach((button) => {
        button.classList.toggle("is-correct", button.dataset.sessionChoiceMark === normalized);
      });
      notify();
    };

    const syncMarksFromCorrect = () => {
      if (isMulti()) {
        const current = (getCorrectInput()?.value || "")
          .split(",")
          .map((part) => part.trim().toLowerCase())
          .filter(Boolean);

        list.querySelectorAll("[data-session-choice-mark]").forEach((button) => {
          button.classList.toggle("is-correct", current.includes(button.dataset.sessionChoiceMark));
        });
        return;
      }

      const current = (getCorrectInput()?.value || "a").toLowerCase();
      setCorrect(current);
    };

    const reindexRows = () => {
      getOptionRows().forEach((row, index) => {
        const key = OPTION_LETTERS[index] || String.fromCharCode(97 + index);
        row.dataset.optionKey = key;
        const mark = row.querySelector("[data-session-choice-mark]");
        if (mark) {
          mark.dataset.sessionChoiceMark = key;
          mark.textContent = key.toUpperCase();
        }
        const input = row.querySelector("[data-session-question-option]");
        if (input) {
          input.placeholder = `Lựa chọn ${key.toUpperCase()}`;
        }
        const removeBtn = row.querySelector("[data-session-choice-remove]");
        if (removeBtn) removeBtn.hidden = index < 2;
      });

      const correctInput = getCorrectInput();
      if (correctInput) {
        if (isMulti()) {
          const validKeys = new Set(getOptionRows().map((row) => row.dataset.optionKey || ""));
          const current = (correctInput.value || "")
            .split(",")
            .map((part) => part.trim().toLowerCase())
            .filter((part) => part !== "" && validKeys.has(part));
          correctInput.value = current.sort().join(",");
        } else if (!getOptionRows().some((row) => row.dataset.optionKey === correctInput.value)) {
          correctInput.value = getOptionRows()[0]?.dataset.optionKey || "a";
        }
      }
      syncMarksFromCorrect();
    };

    const hasChoiceMarks = () => !!list.querySelector("[data-session-choice-mark]");

    const createRow = (key, value = "") => {
      const existingName = list.querySelector("[data-session-question-option]")?.getAttribute("name") || list.dataset.optionsName || "";
      const prefix = hasChoiceMarks()
        ? `<button type="button" class="session-choice-option-mark" data-session-choice-mark="${key}" title="Đánh dấu đáp án đúng">${key.toUpperCase()}</button>`
        : `<span class="session-choice-option-letter" aria-hidden="true">${key.toUpperCase()}</span>`;
      const row = document.createElement("div");
      row.className = "session-choice-option-row";
      row.dataset.sessionChoiceOptionRow = "";
      row.dataset.optionKey = key;
      row.innerHTML =
        prefix +
        `<input class="form-control form-control-sm session-choice-option-input" type="text" name="${existingName.replace(/"/g, "&quot;")}" value="" placeholder="Lựa chọn ${key.toUpperCase()}" data-session-question-option>` +
        `<button type="button" class="btn sunny-mini session-choice-option-remove" data-session-choice-remove title="Xóa lựa chọn"><i class="bi bi-x-lg" aria-hidden="true"></i></button>`;
      const input = row.querySelector("[data-session-question-option]");
      if (input) input.value = value;
      return row;
    };

    const bindRow = (row) => {
      row.querySelector("[data-session-choice-mark]")?.addEventListener("click", () => {
        setCorrect(row.dataset.optionKey || "a");
      });

      row.querySelector("[data-session-choice-remove]")?.addEventListener("click", () => {
        if (getOptionRows().length <= 2) return;
        row.remove();
        reindexRows();
        notify();
      });

      const input = row.querySelector("[data-session-question-option]");
      input?.addEventListener("input", notify);
      input?.addEventListener("keydown", (event) => {
        if (event.key !== "Enter") return;
        event.preventDefault();
        const inputs = getOptionInputs();
        const index = inputs.indexOf(input);
        if (index >= 0 && index < inputs.length - 1) {
          inputs[index + 1].focus();
          inputs[index + 1].select();
          return;
        }
        scope.querySelector("[data-session-choice-add]")?.click();
        getOptionInputs().at(-1)?.focus();
      });

      input?.addEventListener("paste", (event) => {
        const text = event.clipboardData?.getData("text") || "";
        if (!/[\n\r|/]/.test(text)) return;

        event.preventDefault();
        const parts = splitBulkText(text);
        const inputs = getOptionInputs();
        const start = inputs.indexOf(input);
        if (start < 0) return;

        parts.forEach((part, offset) => {
          const targetIndex = start + offset;
          while (targetIndex >= getOptionInputs().length) {
            scope.querySelector("[data-session-choice-add]")?.click();
          }
          const target = getOptionInputs()[targetIndex];
          if (target) target.value = part;
        });
        notify();
        getOptionInputs()[Math.min(start + parts.length, getOptionInputs().length - 1)]?.focus();
      });
    };

    getOptionRows().forEach(bindRow);
    syncMarksFromCorrect();

    scope.querySelector("[data-session-choice-add]")?.addEventListener("click", () => {
      if (getOptionRows().length >= 8) return;
      const key = OPTION_LETTERS[getOptionRows().length] || "h";
      const row = createRow(key);
      list.appendChild(row);
      bindRow(row);
      reindexRows();
      row.querySelector("[data-session-question-option]")?.focus();
      notify();
    });
  };

  const enhanceWordOrderInput = (scope, { addToken, getAddInput, scheduleAutosave } = {}) => {
    const addInput = typeof getAddInput === "function" ? getAddInput() : scope?.querySelector("[data-session-word-order-input]");
    if (!addInput || typeof addToken !== "function" || addInput.dataset.wordOrderEnhanced) return;
    addInput.dataset.wordOrderEnhanced = "1";

    const addMany = (parts) => {
      parts.forEach((part) => addToken(part, { keepInput: false }));
      if (addInput) addInput.value = "";
      if (typeof scheduleAutosave === "function") scheduleAutosave();
    };

    addInput.addEventListener("keydown", (event) => {
      if (event.key !== "Enter") return;
      event.preventDefault();
      const parts = splitBulkText(addInput.value);
      if (parts.length > 1) addMany(parts);
      else addToken(addInput.value.trim(), { keepInput: true });
    });

    addInput.addEventListener("paste", (event) => {
      const text = event.clipboardData?.getData("text") || "";
      if (!/[\n\r|/]/.test(text)) return;
      event.preventDefault();
      addMany(splitBulkText(text));
    });
  };

  const bindEnterAddsRow = (input, onAdd, { focusSelector } = {}) => {
    if (!input || typeof onAdd !== "function") return;

    input.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" || event.isComposing) return;
      if (input.tagName === "TEXTAREA" && event.shiftKey) return;
      event.preventDefault();
      onAdd();
      const scope = input.closest("[data-session-question-item]") || input.closest("article") || document;
      const target = focusSelector ? scope.querySelector(focusSelector) : null;
      target?.focus();
    });
  };

  const bindBlankInsertShortcut = (templateInput, insertBlank) => {
    if (!templateInput || typeof insertBlank !== "function") return;

    templateInput.addEventListener("keydown", (event) => {
      if (event.key === "F2" || (event.ctrlKey && event.shiftKey && event.key === " ")) {
        event.preventDefault();
        insertBlank();
      }
    });
  };

  const bindMemoryPairFastEntry = (scope, addSelector) => {
    if (!scope || scope.dataset.memoryPairFastEntryBound) return;
    scope.dataset.memoryPairFastEntryBound = "1";

    scope.addEventListener("keydown", (event) => {
      if (event.key !== "Enter") return;
      const input = event.target.closest("[data-session-memory-pair-label-input]");
      if (!input || !scope.contains(input)) return;
      event.preventDefault();

      const row = input.closest("[data-session-memory-pair-item]");
      const list = scope.querySelector("[data-session-memory-pairs-list]");
      const rows = list?.querySelectorAll("[data-session-memory-pair-item]") || [];
      const isLast = row && row === rows[rows.length - 1];
      if (!isLast) {
        const next = row?.nextElementSibling?.querySelector("[data-session-memory-pair-image], [data-session-memory-pair-label-input]");
        next?.focus();
        return;
      }
      scope.querySelector(addSelector)?.click();
      list?.lastElementChild?.querySelector("[data-session-memory-pair-image]")?.focus();
    });
  };

  const bindCategorySortFastEntry = (scope, { addGroupSelector, addItemSelector } = {}) => {
    if (!scope || scope.dataset.categorySortFastEntryBound) return;
    scope.dataset.categorySortFastEntryBound = "1";

    scope.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" || event.isComposing) return;

      const groupInput = event.target.closest("[data-session-category-sort-group-label]");
      if (groupInput && scope.contains(groupInput)) {
        event.preventDefault();
        const row = groupInput.closest("[data-session-category-sort-group-item]");
        const list = scope.querySelector("[data-session-category-sort-groups-list]");
        const rows = list?.querySelectorAll("[data-session-category-sort-group-item]") || [];
        if (row !== rows[rows.length - 1]) {
          row?.nextElementSibling?.querySelector("[data-session-category-sort-group-label]")?.focus();
          return;
        }
        scope.querySelector(addGroupSelector)?.click();
        list?.lastElementChild?.querySelector("[data-session-category-sort-group-label]")?.focus();
        return;
      }

      const itemInput = event.target.closest("[data-session-category-sort-item-text]");
      if (itemInput && scope.contains(itemInput)) {
        event.preventDefault();
        const row = itemInput.closest("[data-session-category-sort-item]");
        const list = scope.querySelector("[data-session-category-sort-items-list]");
        const rows = list?.querySelectorAll("[data-session-category-sort-item]") || [];
        if (row !== rows[rows.length - 1]) {
          row?.nextElementSibling?.querySelector("[data-session-category-sort-item-text]")?.focus();
          return;
        }
        scope.querySelector(addItemSelector)?.click();
        list?.lastElementChild?.querySelector("[data-session-category-sort-item-text]")?.focus();
      }
    });
  };

  const bindWordSelectBankFastEntry = (scope) => {
    if (!scope || scope.dataset.wordSelectBankFastEntryBound) return;
    scope.dataset.wordSelectBankFastEntryBound = "1";

    scope.addEventListener("keydown", (event) => {
      if (event.key !== "Enter") return;
      const input = event.target.closest("[data-session-word-select-bank-input]");
      if (!input || !scope.contains(input)) return;
      event.preventDefault();
      scope.querySelector("[data-session-word-select-add-word]")?.click();
      scope.querySelector("[data-session-word-select-bank-list] [data-session-word-select-bank-input]:last-of-type")?.focus();
    });
  };

  let formShortcutsBound = false;

  const bindFormShortcuts = (form, { prevLink, nextLink } = {}) => {
    if (formShortcutsBound) return;
    formShortcutsBound = true;

    document.addEventListener(
      "keydown",
      (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "s") {
          event.preventDefault();
          event.stopPropagation();
          return;
        }

        const tag = (event.target?.tagName || "").toLowerCase();
        const inTinyMce = event.target?.closest?.(".tox-tinymce, .tox-editor-container");
        if (inTinyMce) return;

        if (!event.altKey || event.ctrlKey || event.metaKey) return;
        if (tag === "select" || tag === "textarea" || tag === "input") return;

        if (event.key === "ArrowLeft" && prevLink) {
          event.preventDefault();
          window.location.href = prevLink;
        } else if (event.key === "ArrowRight" && nextLink) {
          event.preventDefault();
          window.location.href = nextLink;
        }
      },
      true
    );
  };

  const focusQuestionEditor = (scope) => {
    const textarea = scope?.querySelector("[data-session-question-input]");
    if (!textarea) return;

    window.setTimeout(() => {
      if (typeof tinymce !== "undefined" && textarea.id) {
        tinymce.get(textarea.id)?.focus();
        return;
      }
      textarea.focus();
    }, 180);
  };

  const ensureChoiceMarkUi = (scope, allowChoice) => {
    const group = scope?.dataset?.questionGroup || "";
    if (group !== "review" && group !== "post_practice" && group !== "pre") return;
    if (!allowChoice) return;

    const optionsWrap = scope.querySelector("[data-session-question-options]");
    const list = scope.querySelector("[data-session-choice-options-list]");
    if (!optionsWrap || !list) return;

    let upgraded = false;

    list.querySelectorAll("[data-session-choice-option-row]").forEach((row, index) => {
      const key = row.dataset.optionKey || OPTION_LETTERS[index] || "a";
      row.dataset.optionKey = key;

      if (row.querySelector("[data-session-choice-mark]")) return;

      const letter = row.querySelector(".session-choice-option-letter");
      if (!letter) return;

      const button = document.createElement("button");
      button.type = "button";
      button.className = "session-choice-option-mark";
      button.dataset.sessionChoiceMark = key;
      button.title = "Đánh dấu đáp án đúng";
      button.textContent = key.toUpperCase();
      letter.replaceWith(button);
      upgraded = true;
    });

    let correctInput = scope.querySelector("[data-session-choice-correct]");
    if (!correctInput) {
      const fieldName = list.dataset.correctFieldName || "";
      if (fieldName === "") return;

      correctInput = document.createElement("input");
      correctInput.type = "hidden";
      correctInput.name = fieldName;
      correctInput.dataset.originalName = fieldName;
      correctInput.dataset.sessionChoiceCorrect = "";
      correctInput.value = "a";
      optionsWrap.appendChild(correctInput);
      upgraded = true;
    }

    if (upgraded) {
      delete list.dataset.choiceOptionsBound;
      bindChoiceOptions(scope);
    }
  };

  window.ProgramInputHelpers = {
    bindChoiceOptions,
    ensureChoiceMarkUi,
    enhanceWordOrderInput,
    bindEnterAddsRow,
    bindBlankInsertShortcut,
    bindMemoryPairFastEntry,
    bindCategorySortFastEntry,
    bindWordSelectBankFastEntry,
    bindFormShortcuts,
    focusQuestionEditor,
    splitBulkText,
    bindQuestionInputEnhancements(scope, { scheduleAutosave } = {}) {
      this.bindChoiceOptions(scope, { onChange: scheduleAutosave });
      this.bindMemoryPairFastEntry(scope, "[data-add-memory-pair]");
      this.bindCategorySortFastEntry(scope, {
        addGroupSelector: "[data-session-category-sort-add-group]",
        addItemSelector: "[data-session-category-sort-add-item]",
      });
      this.bindWordSelectBankFastEntry(scope);

      const insertFillBlank = () => {
        const templateInput = scope.querySelector("[data-session-fill-blanks-template]");
        if (!templateInput) return;
        const start = templateInput.selectionStart ?? templateInput.value.length;
        const end = templateInput.selectionEnd ?? start;
        templateInput.value = `${templateInput.value.slice(0, start)} ___ ${templateInput.value.slice(end)}`;
        templateInput.focus();
        templateInput.selectionStart = templateInput.selectionEnd = start + 4;
        templateInput.dispatchEvent(new Event("input", { bubbles: true }));
      };

      const insertWordSelectBlank = () => {
        const templateInput = scope.querySelector("[data-session-word-select-template]");
        if (!templateInput) return;
        const start = templateInput.selectionStart ?? templateInput.value.length;
        const end = templateInput.selectionEnd ?? start;
        templateInput.value = `${templateInput.value.slice(0, start)} ___ ${templateInput.value.slice(end)}`;
        templateInput.focus();
        templateInput.selectionStart = templateInput.selectionEnd = start + 4;
        templateInput.dispatchEvent(new Event("input", { bubbles: true }));
      };

      this.bindBlankInsertShortcut(scope.querySelector("[data-session-fill-blanks-template]"), insertFillBlank);
      this.bindBlankInsertShortcut(scope.querySelector("[data-session-word-select-template]"), insertWordSelectBlank);
    },
  };
})();
