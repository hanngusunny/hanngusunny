(() => {
  const root = document.getElementById("questionEditRoot");
  const questionForm = document.querySelector(".program-question-edit-form");
  const autosaveUrl = questionForm?.dataset.autosaveUrl || "";
  const autosaveStatus = document.getElementById("programQuestionAutosaveStatus");
  const tinymceBaseUrl = document.body.dataset.tinymceBase || "/assets/tinymce";

  if (!root || !questionForm) return;

  let autosaveTimer = null;
  let autosaveInFlight = false;
  let autosaveQueued = false;
  let autosaveWarmup = true;
  let formDirty = false;

  window.setTimeout(() => {
    autosaveWarmup = false;
  }, 2000);

  const markDirty = () => {
    formDirty = true;
  };

  const canAutosave = () => Boolean(autosaveUrl && questionForm && !autosaveWarmup && formDirty);

  const questionItem = root.querySelector("[data-session-question-item]");
  if (!questionItem) return;

  const setAutosaveStatus = (state, message = "") => {
    if (!autosaveStatus) return;

    autosaveStatus.classList.remove("is-saving", "is-saved", "is-error");

    if (state === "saving") {
      autosaveStatus.classList.add("is-saving");
      autosaveStatus.textContent = "Đang lưu...";
      return;
    }

    if (state === "saved") {
      autosaveStatus.classList.add("is-saved");
      autosaveStatus.textContent = message ? `Lưu lúc ${message}` : "Đã lưu";
      return;
    }

    if (state === "error") {
      autosaveStatus.classList.add("is-error");
      autosaveStatus.textContent = message || "Không lưu được — thử lại sau";
      return;
    }

    autosaveStatus.textContent = "";
  };

  const getCsrfField = () =>
    questionForm.querySelector('input[name="_token"]') || questionForm.querySelector('input[name^="csrf"]');

  const getCsrfHeaders = () => {
    const token = getCsrfField()?.value || "";
    const headers = { "X-Requested-With": "XMLHttpRequest" };
    if (token) headers["X-CSRF-TOKEN"] = token;
    return headers;
  };

  const updateCsrfToken = (data) => {
    if (!data?.csrfHash) return;

    document.querySelectorAll('input[name="_token"], input[name^="csrf"]').forEach((input) => {
      input.value = data.csrfHash;

      if (data.csrfName) {
        input.name = data.csrfName;
      }
    });
  };

  const tinymceImageUpload = window.ProgramPairImageEditor?.getTinyMceUploadConfig?.() || {};

  const editorConfig = {
    base_url: tinymceBaseUrl,
    suffix: ".min",
    height: 260,
    menubar: "file edit view insert format tools table help",
    language: "vi",
    language_url: tinymceBaseUrl + "/langs/vi.js",
    plugins: [
      "advlist", "autolink", "lists", "link", "image", "charmap", "preview",
      "anchor", "searchreplace", "visualblocks", "code", "fullscreen",
      "insertdatetime", "media", "table", "help", "wordcount", "codesample",
    ].join(" "),
    toolbar:
      "undo redo | blocks fontsize | bold italic underline strikethrough | " +
      "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | " +
      "forecolor backcolor removeformat | link image media table codesample | " +
      "charmap preview code fullscreen help",
    branding: false,
    promotion: false,
    resize: true,
    paste_as_text: false,
    content_style: 'body { font-family: "Google Sans Flex", Arial, sans-serif; font-size: 14px; line-height: 1.6; }',
    ...tinymceImageUpload,
  };

  const questionEditorConfig = Object.assign({}, editorConfig, {
    height: 280,
    menubar: false,
    toolbar:
      "undo redo | blocks | bold italic underline | bullist numlist | " +
      "alignleft aligncenter alignright | link image | removeformat | code",
  });

  const ensureEditorId = (textarea, prefix) => {
    if (!textarea.id) {
      textarea.id = prefix + Date.now() + "-" + Math.random().toString(36).slice(2, 8);
    }
  };

  const pairImage = window.ProgramPairImageEditor;

  const onPairImageEditorChange = () => {
    questionItem.querySelectorAll("[data-session-memory-pair-item]").forEach((row) => updateMemoryPairPreview(row));
    questionItem.querySelectorAll("[data-session-word-link-pair-item]").forEach((row) => updateWordLinkPairPreview(row));
    syncPairsJson();
    syncWordTranslateJson();
    window.ProgramPhatAmItems?.syncIn?.(questionItem);
    userScheduleAutosave();
  };

  const getMemoryPairImageValue = (row) =>
    pairImage?.getValue(row.querySelector("[data-session-memory-pair-image]")) || "";

  const getWordLinkSlotValue = (row, slot) => {
    const type = row.querySelector(`[data-session-pair-slot-type="${slot}"]`)?.value || "image";

    if (type === "image") {
      return pairImage?.getValue(row.querySelector(`[data-session-pair-slot-image-value="${slot}"]`)) || "";
    }

    return row.querySelector(`[data-session-pair-slot-text-value="${slot}"]`)?.value.trim() || "";
  };

  const renderWordLinkSlotField = (slot, type, value = "") => {
    if (type === "image") {
      const display = value ? pairImage?.resolveMediaUrl(value) || value : "";

      return (
        pairImage?.renderFieldHtml(
          `data-session-pair-slot-image-value="${slot}" class="session-pair-slot-image-value session-memory-pair-input"`,
          display
        ) || ""
      );
    }

    return (
      `<input class="form-control form-control-sm session-pair-slot-text-value" type="text"` +
      ` data-session-pair-slot-text-value="${slot}" value="${String(value).replace(/"/g, "&quot;")}"` +
      ` placeholder="Nhập chữ / từ / cụm từ">`
    );
  };

  const mountWordLinkSlotField = (row, slot, typeValue) => {
    const wrap = row.querySelector(`[data-session-pair-slot-value-wrap="${slot}"]`);
    if (!wrap) return;

    const preserved = getWordLinkSlotValue(row, slot);
    pairImage?.destroyIn(wrap);
    wrap.innerHTML = renderWordLinkSlotField(slot, typeValue, preserved);

    if (typeValue === "image") {
      const imageWrap = wrap.querySelector("[data-session-pair-image-wrap]") || wrap;
      pairImage?.init(imageWrap, () => {
        updateWordLinkPairPreview(row);
        onPairImageEditorChange();
      });
      return;
    }

    wrap.querySelector("input")?.addEventListener("input", () => {
      updateWordLinkPairPreview(row);
      syncWordLinkRowChange(row);
    });
  };

  const initMemoryPairImageEditor = (row) => {
    const imageWrap = row.querySelector("[data-session-pair-image-wrap]");
    if (!imageWrap || imageWrap.hidden) return;

    pairImage?.init(imageWrap, () => {
      updateMemoryPairPreview(row);
      onPairImageEditorChange();
    });
  };

  const initWordLinkImageEditors = () => {
    questionItem.querySelectorAll("[data-session-word-link-pair-item]").forEach((row) => {
      ["a", "b"].forEach((slot) => {
        const type = row.querySelector(`[data-session-pair-slot-type="${slot}"]`)?.value || "image";
        if (type !== "image") return;

        const imageWrap = row.querySelector(`[data-session-pair-slot-value-wrap="${slot}"] [data-session-pair-image-wrap]`);
        if (imageWrap) {
          pairImage?.init(imageWrap, () => {
            updateWordLinkPairPreview(row);
            onPairImageEditorChange();
          });
        }
      });
    });
  };

  const ensureWordLinkPairFields = () => {
    questionItem.querySelectorAll("[data-session-word-link-pair-item]").forEach((row) => {
      ["a", "b"].forEach((slot) => {
        const wrap = row.querySelector(`[data-session-pair-slot-value-wrap="${slot}"]`);
        const typeSelect = row.querySelector(`[data-session-pair-slot-type="${slot}"]`);
        if (!wrap || !typeSelect) return;

        const typeValue = typeSelect.value === "text" ? "text" : "image";
        const hasField =
          typeValue === "image"
            ? wrap.querySelector("[data-session-pair-image-wrap], [data-session-pair-slot-image-value]")
            : wrap.querySelector("[data-session-pair-slot-text-value]");

        if (!hasField) {
          mountWordLinkSlotField(row, slot, typeValue);
        }
      });
    });

    initWordLinkImageEditors();
  };

  const ensureMemoryPairEditors = () => {
    questionItem.querySelectorAll("[data-session-memory-pair-item]").forEach(initMemoryPairImageEditor);
  };

  const setPanelVisible = (panel, visible) => {
    if (!panel) return;
    panel.hidden = !visible;
    if (visible) panel.removeAttribute("hidden");
  };

  const createMemoryPairRow = () => {
    const imageField =
      pairImage?.renderFieldHtml(
        'data-session-memory-pair-image class="form-control form-control-sm session-pair-image-url session-memory-pair-input"',
        ""
      ) || "";

    const row = document.createElement("div");
    row.className = "session-memory-pair-item";
    row.dataset.sessionMemoryPairItem = "";
    row.innerHTML =
      `<div class="session-memory-pair-row">` +
      `<span class="session-memory-pair-label" data-session-memory-pair-label>Cặp 1</span>` +
      `<div class="session-memory-pair-fields">` +
      `<div class="session-memory-pair-field">` +
      `<label class="session-memory-pair-field-label">Ảnh</label>` +
      imageField +
      `</div>` +
      `<div class="session-memory-pair-field">` +
      `<label class="session-memory-pair-field-label">Chữ / giải thích</label>` +
      `<input class="form-control form-control-sm session-memory-pair-input" type="text" value="" placeholder="VD: Quả táo / Apple / Danh từ chỉ đồ vật..." data-session-memory-pair-label-input>` +
      `</div>` +
      `</div>` +
      `<div class="session-memory-pair-preview" data-session-memory-pair-preview hidden title="Xem trước 2 thẻ của cặp"></div>` +
      `<button class="btn sunny-mini btn-delete-user btn-remove-memory-pair" type="button" title="Xóa cặp"><i class="bi bi-trash" aria-hidden="true"></i></button>` +
      `</div>`;
    return row;
  };

  const resetMemoryPairRow = (row) => {
    pairImage?.destroyIn(row);
    row.querySelectorAll("[data-session-pair-image-wrap]").forEach((imageWrap) => {
      const input = imageWrap.querySelector("[data-session-memory-pair-image]");
      if (input) input.value = "";
      const status = imageWrap.querySelector("[data-session-pair-image-status]");
      if (status) status.textContent = "";
    });
    const labelInput = row.querySelector("[data-session-memory-pair-label-input]");
    if (labelInput) labelInput.value = "";
    const preview = row.querySelector("[data-session-memory-pair-preview]");
    if (preview) {
      preview.hidden = true;
      preview.innerHTML = "";
    }
  };

  const ensureMinimumMemoryPairs = (minimum = 2) => {
    const list = questionItem.querySelector("[data-session-memory-pairs-list]");
    if (!list) return;

    while (list.querySelectorAll("[data-session-memory-pair-item]").length < minimum) {
      const templateRow = list.querySelector("[data-session-memory-pair-item]");
      const row = templateRow ? templateRow.cloneNode(true) : createMemoryPairRow();
      resetMemoryPairRow(row);
      list.appendChild(row);
      bindMemoryPairRow(row);
      initMemoryPairImageEditor(row);
    }

    reindexMemoryPairs();
  };

  const createWordLinkPairRow = () => {
    const imageFieldA =
      pairImage?.renderFieldHtml(
        'data-session-pair-slot-image-value="a" class="session-pair-slot-image-value session-memory-pair-input"',
        ""
      ) || "";
    const textFieldB =
      `<input class="form-control form-control-sm session-pair-slot-text-value" type="text" value="" data-session-pair-slot-text-value="b" placeholder="Nhập chữ / từ / cụm từ">`;

    const row = document.createElement("div");
    row.className = "session-word-link-pair-item";
    row.dataset.sessionWordLinkPairItem = "";
    row.innerHTML =
      `<div class="session-memory-pair-row session-word-link-pair-row">` +
      `<span class="session-memory-pair-label" data-session-word-link-pair-label>Cặp 1</span>` +
      `<div class="session-word-link-pair-slots">` +
      `<div class="session-pair-slot" data-session-pair-slot="a">` +
      `<div class="session-pair-slot-head"><span class="session-pair-slot-title">Ô trái</span>` +
      `<select class="form-select form-select-sm session-pair-slot-type" data-session-pair-slot-type="a"><option value="image" selected>Ảnh</option><option value="text">Chữ</option></select></div>` +
      `<div class="session-pair-slot-value-wrap" data-session-pair-slot-value-wrap="a">${imageFieldA}</div>` +
      `</div>` +
      `<div class="session-pair-slot" data-session-pair-slot="b">` +
      `<div class="session-pair-slot-head"><span class="session-pair-slot-title">Ô phải</span>` +
      `<select class="form-select form-select-sm session-pair-slot-type" data-session-pair-slot-type="b"><option value="image">Ảnh</option><option value="text" selected>Chữ</option></select></div>` +
      `<div class="session-pair-slot-value-wrap" data-session-pair-slot-value-wrap="b">${textFieldB}</div>` +
      `</div>` +
      `</div>` +
      `<div class="session-memory-pair-preview session-word-link-pair-preview" data-session-word-link-pair-preview hidden>` +
      `<span class="session-memory-pair-preview-card session-word-link-preview-card session-word-link-preview-card--a is-empty" data-session-word-link-preview-a"><i class="bi bi-image" aria-hidden="true"></i></span>` +
      `<span class="session-memory-pair-preview-card session-word-link-preview-card session-word-link-preview-card--b is-empty" data-session-word-link-preview-b>Chữ</span>` +
      `</div>` +
      `<button class="btn sunny-mini btn-delete-user btn-remove-word-link-pair" type="button" title="Xóa cặp"><i class="bi bi-trash" aria-hidden="true"></i></button>` +
      `</div>`;
    return row;
  };

  const resetWordLinkPairRow = (row) => {
    pairImage?.destroyIn(row);
    row.querySelectorAll("[data-session-pair-slot-text-value]").forEach((input) => {
      input.value = "";
    });
    row.querySelectorAll("[data-session-pair-image-wrap]").forEach((imageWrap) => {
      const input = imageWrap.querySelector("[data-session-pair-slot-image-value]");
      if (input) input.value = "";
      const status = imageWrap.querySelector("[data-session-pair-image-status]");
      if (status) status.textContent = "";
    });
    const typeA = row.querySelector('[data-session-pair-slot-type="a"]');
    const typeB = row.querySelector('[data-session-pair-slot-type="b"]');
    if (typeA) typeA.value = "image";
    if (typeB) typeB.value = "text";
    ["a", "b"].forEach((slot) => {
      const typeValue = row.querySelector(`[data-session-pair-slot-type="${slot}"]`)?.value === "text" ? "text" : "image";
      mountWordLinkSlotField(row, slot, typeValue);
    });
    const preview = row.querySelector("[data-session-word-link-pair-preview]");
    if (preview) preview.hidden = true;
  };

  const ensureMinimumWordLinkPairs = (minimum = 2) => {
    const list = questionItem.querySelector("[data-session-word-link-pairs-list]");
    if (!list) return;

    while (list.querySelectorAll("[data-session-word-link-pair-item]").length < minimum) {
      const templateRow = list.querySelector("[data-session-word-link-pair-item]");
      const row = templateRow ? templateRow.cloneNode(true) : createWordLinkPairRow();
      resetWordLinkPairRow(row);
      list.appendChild(row);
      bindWordLinkPairRow(row);
    }

    reindexWordLinkPairs();
    initWordLinkImageEditors();
  };

  const syncMemoryPairsJson = () => {
    const jsonInput = questionItem.querySelector("[data-session-memory-pairs-json]");
    const list = questionItem.querySelector("[data-session-memory-pairs-list]");
    if (!jsonInput || !list) return;

    const pairs = [];
    list.querySelectorAll("[data-session-memory-pair-item]").forEach((row) => {
      pairs.push({
        image: getMemoryPairImageValue(row),
        label: row.querySelector("[data-session-memory-pair-label-input]")?.value.trim() || "",
      });
    });

    jsonInput.value = JSON.stringify(pairs);
  };

  const syncWordLinkPairsJson = () => {
    const jsonInput = questionItem.querySelector("[data-session-word-link-pairs-json]");
    const list = questionItem.querySelector("[data-session-word-link-pairs-list]");
    if (!jsonInput || !list) return;

    const pairs = [];
    list.querySelectorAll("[data-session-word-link-pair-item]").forEach((row) => {
      pairs.push({
        a_type: row.querySelector('[data-session-pair-slot-type="a"]')?.value || "image",
        a_value: getWordLinkSlotValue(row, "a"),
        b_type: row.querySelector('[data-session-pair-slot-type="b"]')?.value || "text",
        b_value: getWordLinkSlotValue(row, "b"),
      });
    });

    jsonInput.value = JSON.stringify(pairs);
  };

  const syncPairsJson = () => {
    syncMemoryPairsJson();
    syncWordLinkPairsJson();
  };

  const runAutosave = async () => {
    if (!autosaveUrl) return;

    if (autosaveInFlight) {
      autosaveQueued = true;
      return;
    }

    autosaveInFlight = true;
    setAutosaveStatus("saving");

    if (typeof tinymce !== "undefined") tinymce.triggerSave();
    pairImage?.saveAll(questionItem);
    syncPairsJson();
    syncWordOrderJson();
    syncCategorySortJson();
    syncFillBlanksJson();
    syncWordSelectJson();
    syncWordTranslateJson();
    window.ProgramPhatAmItems?.syncIn?.(questionItem);
    window.ProgramListeningItems?.syncIn?.(questionItem);
    syncListeningTrueFalseJson();
    window.ProgramListeningMultiChoice?.syncIn?.(questionItem);
    window.ProgramImageHotspotPanel?.sync?.(questionItem);
    window.ProgramImageLabelDropPanel?.sync?.(questionItem);

    try {
      const response = await fetch(autosaveUrl, {
        method: "POST",
        body: new FormData(questionForm),
        headers: getCsrfHeaders(),
        credentials: "same-origin",
      });

      const data = await response.json().catch(() => ({}));

      if (response.status === 403) {
        setAutosaveStatus(
          "error",
          data.message || "Không lưu tự động — hãy bấm «Lưu câu hỏi» hoặc tải lại trang"
        );
        return;
      }

      if (!response.ok || !data.ok) {
        setAutosaveStatus("error", data.message || "Không lưu được");
        return;
      }

      updateCsrfToken(data);
      setAutosaveStatus("saved", data.saved_at || "");
    } catch (error) {
      setAutosaveStatus("error");
    } finally {
      autosaveInFlight = false;
      if (autosaveQueued) {
        autosaveQueued = false;
        scheduleAutosave();
      }
    }
  };

  const scheduleAutosave = () => {
    if (!canAutosave()) return;
    window.clearTimeout(autosaveTimer);
    autosaveTimer = window.setTimeout(runAutosave, 900);
  };

  const userScheduleAutosave = () => {
    markDirty();
    scheduleAutosave();
  };

  const syncListeningItemsPanelState = (isListening) => {
    const panel = questionItem.querySelector("[data-session-listening-items-panel]");
    if (!panel) return;

    panel.querySelectorAll("[data-listening-item-answer-mode-radio]").forEach((radio) => {
      radio.disabled = !isListening;
    });

    panel.querySelectorAll(
      "[data-session-listening-add-question], [data-session-listening-remove-question], " +
        "[data-session-listening-item-choice-question], [data-session-listening-item-option], " +
        "[data-session-listening-item-correct-text], [data-session-listening-item-mark], " +
        "[data-session-listening-item-tf-statement], [data-session-listening-item-tf-answer], " +
        "[data-session-listening-item-tf-add], [data-session-listening-item-tf-remove], " +
        "[data-session-listening-item-mc-question], [data-session-listening-item-mc-option], " +
        "[data-session-listening-item-mc-mark], [data-session-listening-item-mc-add], [data-session-listening-item-mc-remove], " +
        "[data-session-listening-item-word-link-add], [data-session-listening-item-fill-blanks-template], " +
        "[data-session-listening-item-fill-blanks-answer-input], [data-session-listening-item-fill-blanks-insert], " +
        "[data-session-item-points], " +
        ".btn-remove-word-link-pair, .session-pair-image-file, [data-session-pair-slot-type], " +
        "[data-session-pair-slot-text-value], [data-session-pair-slot-image-value]"
    ).forEach((field) => {
      field.disabled = !isListening;
    });

    if (!isListening) return;

    window.ProgramListeningItems?.syncIn?.(questionItem);
    questionItem.querySelectorAll("[data-session-listening-question]").forEach((row) => {
      window.ProgramListeningItems?.syncItemModePanels?.(row);
    });
    initWordLinkImageEditors();
    bindUnboundWordLinkPairRows();
  };

  const syncQuestionTypeFields = () => {
    const group = questionItem.dataset.questionGroup || "";
    const supportsTypeToggle = ["review", "post_practice", "pre", "quiz", "assessment", "knowledgeReview"].includes(group);
    if (!supportsTypeToggle) return;

    const typeRadio = questionItem.querySelector("[data-session-question-type-radio]:checked");
    const typeValue = typeRadio ? typeRadio.value : "choice";
    const isChoice = typeValue === "choice";
    const isMultiSelect = typeValue === "multi_select";
    const isFill = typeValue === "fill";
    const isMemory = typeValue === "memory_match";
    const isWordLink = typeValue === "word_link";

    const isWordOrder = typeValue === "word_order";
    const isListening = typeValue === "listening";
    const isPhatAmType = typeValue === "phat_am";
    const allowChoice = isChoice || isMultiSelect;
    const allowFill = isFill;

    const optionsWrap = questionItem.querySelector("[data-session-question-options]");
    const fillWrap = questionItem.querySelector("[data-session-review-fill-answer]");
    const memoryPanel = questionItem.querySelector("[data-session-memory-pairs-panel]");
    const wordLinkPanel = questionItem.querySelector("[data-session-word-link-pairs-panel]");
    const fillInput = questionItem.querySelector("[data-session-fill-correct]");
    const choiceSelect = questionItem.querySelector("[data-session-choice-correct]");

    if (optionsWrap) optionsWrap.hidden = !allowChoice;

    const choiceOptionsList = questionItem.querySelector("[data-session-choice-options-list]");
    if (choiceOptionsList) {
      choiceOptionsList.dataset.choiceMulti = isMultiSelect ? "1" : "0";
    }

    questionItem.querySelectorAll("[data-session-question-option]").forEach((input) => {
      if (!input.dataset.originalName && input.getAttribute("name")) {
        input.dataset.originalName = input.getAttribute("name");
      }

      if (allowChoice) {
        input.disabled = false;
        input.setAttribute("name", input.dataset.originalName || "");
      } else {
        input.disabled = true;
        input.removeAttribute("name");
      }
    });

    if (fillWrap) fillWrap.hidden = !allowFill;
    setPanelVisible(memoryPanel, isMemory);
    setPanelVisible(wordLinkPanel, isWordLink);

    const trueFalsePanel = questionItem.querySelector("[data-session-listening-true-false-panel]");
    const trueFalseJsonInput = questionItem.querySelector("[data-session-listening-true-false-json]");

    if (trueFalsePanel) trueFalsePanel.hidden = true;

    if (trueFalseJsonInput) {
      trueFalseJsonInput.disabled = true;
      trueFalseJsonInput.removeAttribute("name");
    }

    const multiChoicePanel = questionItem.querySelector("[data-session-listening-multi-choice-panel]");
    const multiChoiceJsonInput = questionItem.querySelector("[data-session-listening-multi-choice-json]");

    if (multiChoicePanel) multiChoicePanel.hidden = true;

    if (multiChoiceJsonInput) {
      multiChoiceJsonInput.disabled = true;
      multiChoiceJsonInput.removeAttribute("name");
    }

    if (fillInput) {
      if (typeof tinymce !== "undefined" && fillInput.id) tinymce.get(fillInput.id)?.save();
      fillInput.disabled = !allowFill;
      if (!allowFill) fillInput.removeAttribute("name");
      else fillInput.setAttribute("name", fillInput.dataset.originalName || "");
    }

    if (choiceSelect) {
      choiceSelect.disabled = !allowChoice;
      if (!allowChoice) choiceSelect.removeAttribute("name");
      else choiceSelect.setAttribute("name", choiceSelect.dataset.originalName || "");
    }

    if (allowChoice) {
      window.ProgramInputHelpers?.ensureChoiceMarkUi?.(questionItem, true);
      const refreshedChoiceSelect = questionItem.querySelector("[data-session-choice-correct]");
      if (refreshedChoiceSelect) {
        refreshedChoiceSelect.disabled = false;
        refreshedChoiceSelect.setAttribute("name", refreshedChoiceSelect.dataset.originalName || refreshedChoiceSelect.name);
      }
    }

    questionItem.querySelectorAll("[data-session-memory-pair-label-input]").forEach((input) => {
      input.disabled = !isMemory;
    });

    const setWordLinkPairControlEnabled = (field) => {
      const inListeningWordLink = !!field.closest("[data-session-listening-item-word-link-panel]");
      field.disabled = inListeningWordLink ? !isListening : !isWordLink;
    };

    questionItem.querySelectorAll("[data-session-pair-slot-text-value], [data-session-pair-slot-image-value]").forEach(setWordLinkPairControlEnabled);

    questionItem.querySelectorAll("[data-session-pair-slot-type]").forEach(setWordLinkPairControlEnabled);

    questionItem
      .querySelectorAll(
        "[data-session-listening-item-word-link-panel] .session-pair-image-file, [data-session-listening-item-word-link-panel] .btn-remove-word-link-pair, [data-session-listening-item-word-link-add]"
      )
      .forEach((field) => {
        field.disabled = !isListening;
      });

    const memoryJsonInput = questionItem.querySelector("[data-session-memory-pairs-json]");
    const wordLinkJsonInput = questionItem.querySelector("[data-session-word-link-pairs-json]");

    if (memoryJsonInput) {
      if (isMemory) {
        memoryJsonInput.disabled = false;
        memoryJsonInput.setAttribute("name", memoryJsonInput.dataset.originalName || memoryJsonInput.name);
      } else {
        memoryJsonInput.disabled = true;
        memoryJsonInput.removeAttribute("name");
      }
    }

    if (wordLinkJsonInput) {
      if (isWordLink) {
        wordLinkJsonInput.disabled = false;
        wordLinkJsonInput.setAttribute("name", wordLinkJsonInput.dataset.originalName || wordLinkJsonInput.name);
      } else {
        wordLinkJsonInput.disabled = true;
        wordLinkJsonInput.removeAttribute("name");
      }
    }

    if (isMemory) {
      ensureMinimumMemoryPairs();
      syncMemoryPairsJson();
      ensureMemoryPairEditors();
    }
    if (isWordLink) {
      ensureMinimumWordLinkPairs();
      syncWordLinkPairsJson();
      ensureWordLinkPairFields();
    }

    const wordOrderPanel = questionItem.querySelector("[data-session-word-order-panel]");
    const wordOrderJsonInput = questionItem.querySelector("[data-session-word-order-json]");

    if (wordOrderPanel) wordOrderPanel.hidden = !isWordOrder;

    if (wordOrderJsonInput) {
      if (isWordOrder) {
        wordOrderJsonInput.disabled = false;
        wordOrderJsonInput.setAttribute("name", wordOrderJsonInput.dataset.originalName || wordOrderJsonInput.name);
        syncWordOrderJson();
      } else {
        wordOrderJsonInput.disabled = true;
        wordOrderJsonInput.removeAttribute("name");
      }
    }

    const listeningPanel = questionItem.querySelector("[data-session-listening-panel]");
    if (listeningPanel) listeningPanel.hidden = !isListening;

    const listeningItemsPanel = questionItem.querySelector("[data-session-listening-items-panel]");
    if (listeningItemsPanel) listeningItemsPanel.hidden = !isListening;

    const listeningJsonInput = questionItem.querySelector("[data-session-listening-json]");
    if (listeningJsonInput) {
      if (!listeningJsonInput.dataset.originalName && listeningJsonInput.getAttribute("name")) {
        listeningJsonInput.dataset.originalName = listeningJsonInput.getAttribute("name");
      }
      if (isListening) {
        listeningJsonInput.disabled = false;
        listeningJsonInput.setAttribute("name", listeningJsonInput.dataset.originalName || "");
        window.ProgramListeningItems?.syncIn?.(questionItem);
      } else {
        listeningJsonInput.disabled = true;
        listeningJsonInput.removeAttribute("name");
      }
    }

    const listeningMp3Block = questionItem.querySelector("[data-session-listening-mp3-upload]");
    if (listeningMp3Block) {
      listeningMp3Block.querySelectorAll("input, button, select, textarea").forEach((field) => {
        if (field.matches("[data-qb-audio-saved]")) {
          if (!field.dataset.originalName && field.getAttribute("name")) {
            field.dataset.originalName = field.getAttribute("name");
          }
          field.disabled = !isListening;
          if (!isListening) field.removeAttribute("name");
          else field.setAttribute("name", field.dataset.originalName || "");
          return;
        }
        field.disabled = !isListening;
      });
    }

    questionItem.querySelectorAll("[data-listening-answer-mode-radio]").forEach((radio) => {
      radio.disabled = !isListening;
    });

    if (isListening) {
      window.ProgramMp3Upload?.bindIn?.(questionItem);
      ensureWordLinkPairFields();
    }

    syncListeningItemsPanelState(isListening);

    const isCategorySort = typeValue === "category_sort";
    const categorySortPanel = questionItem.querySelector("[data-session-category-sort-panel]");
    const categorySortJsonInput = questionItem.querySelector("[data-session-category-sort-json]");

    if (categorySortPanel) categorySortPanel.hidden = !isCategorySort;

    if (categorySortJsonInput) {
      if (isCategorySort) {
        categorySortJsonInput.disabled = false;
        categorySortJsonInput.setAttribute("name", categorySortJsonInput.dataset.originalName || categorySortJsonInput.name);
        syncCategorySortJson();
      } else {
        categorySortJsonInput.disabled = true;
        categorySortJsonInput.removeAttribute("name");
      }
    }

    const isFillBlanks = typeValue === "fill_blanks";
    const fillBlanksPanel = questionItem.querySelector("[data-session-fill-blanks-panel]");
    const fillBlanksJsonInput = questionItem.querySelector("[data-session-fill-blanks-json]");

    if (fillBlanksPanel) fillBlanksPanel.hidden = !isFillBlanks;

    questionItem.querySelectorAll("[data-session-fill-blanks-template], [data-session-fill-blanks-answer-input]").forEach((field) => {
      field.disabled = !isFillBlanks;
    });

    questionItem.querySelector("[data-session-fill-blanks-insert]")?.toggleAttribute("disabled", !isFillBlanks);

    if (fillBlanksJsonInput) {
      if (isFillBlanks) {
        fillBlanksJsonInput.disabled = false;
        fillBlanksJsonInput.setAttribute("name", fillBlanksJsonInput.dataset.originalName || fillBlanksJsonInput.name);
        syncFillBlanksJson();
      } else {
        fillBlanksJsonInput.disabled = true;
        fillBlanksJsonInput.removeAttribute("name");
      }
    }

    const isWordSelect = typeValue === "word_select";
    const wordSelectPanel = questionItem.querySelector("[data-session-word-select-panel]");
    const wordSelectJsonInput = questionItem.querySelector("[data-session-word-select-json]");

    if (wordSelectPanel) wordSelectPanel.hidden = !isWordSelect;

    questionItem.querySelectorAll("[data-session-word-select-template], [data-session-word-select-answer-input], [data-session-word-select-bank-input]").forEach((field) => {
      field.disabled = !isWordSelect;
    });

    questionItem.querySelector("[data-session-word-select-insert]")?.toggleAttribute("disabled", !isWordSelect);
    questionItem.querySelector("[data-session-word-select-sync-bank]")?.toggleAttribute("disabled", !isWordSelect);
    questionItem.querySelector("[data-session-word-select-add-word]")?.toggleAttribute("disabled", !isWordSelect);
    questionItem.querySelectorAll("[data-session-word-select-remove-word]").forEach((button) => {
      button.toggleAttribute("disabled", !isWordSelect);
    });

    if (wordSelectJsonInput) {
      if (isWordSelect) {
        wordSelectJsonInput.disabled = false;
        wordSelectJsonInput.setAttribute("name", wordSelectJsonInput.dataset.originalName || wordSelectJsonInput.name);
        syncWordSelectJson();
      } else {
        wordSelectJsonInput.disabled = true;
        wordSelectJsonInput.removeAttribute("name");
      }
    }

    const isWordTranslate = typeValue === "word_translate";
    const wordTranslatePanel = questionItem.querySelector("[data-session-word-translate-panel]");
    const wordTranslateJsonInput = questionItem.querySelector("[data-session-word-translate-json]");

    if (wordTranslatePanel) wordTranslatePanel.hidden = !isWordTranslate;

    questionItem.querySelectorAll("[data-session-word-translate-source-type], [data-session-word-translate-source-text], [data-session-word-translate-answer-input]").forEach((field) => {
      field.disabled = !isWordTranslate;
    });

    questionItem.querySelectorAll("[data-session-word-translate-source-image]").forEach((field) => {
      field.disabled = !isWordTranslate;
    });

    questionItem.querySelectorAll("[data-session-word-translate-columns-radio], [data-session-word-translate-layout-radio]").forEach((field) => {
      field.disabled = !isWordTranslate;
    });

    questionItem.querySelector("[data-session-word-translate-add-item]")?.toggleAttribute("disabled", !isWordTranslate);
    questionItem.querySelectorAll("[data-session-word-translate-remove-item]").forEach((button) => {
      button.toggleAttribute("disabled", !isWordTranslate);
    });

    if (wordTranslateJsonInput) {
      if (isWordTranslate) {
        wordTranslateJsonInput.disabled = false;
        wordTranslateJsonInput.setAttribute("name", wordTranslateJsonInput.dataset.originalName || wordTranslateJsonInput.name);
      } else {
        wordTranslateJsonInput.disabled = true;
        wordTranslateJsonInput.removeAttribute("name");
      }
    }

    const isPhatAm = typeValue === "phat_am";
    const phatAmPanel = questionItem.querySelector("[data-session-phat-am-panel]");
    const phatAmJsonInput = questionItem.querySelector("[data-session-phat-am-json]");

    if (phatAmPanel) phatAmPanel.hidden = !isPhatAm;

    questionItem.querySelectorAll("[data-session-phat-am-add-question], [data-session-phat-am-remove-question], [data-session-phat-am-prompt], [data-phat-am-item-answer-mode-radio], [data-session-phat-am-require-recording-radio]").forEach((field) => {
      field.disabled = !isPhatAm;
    });

    phatAmPanel?.querySelectorAll("[data-session-phat-am-item-choice-question], [data-session-phat-am-item-option], [data-session-phat-am-item-correct-text], [data-session-phat-am-item-tf-statement], [data-session-phat-am-item-tf-answer], [data-session-phat-am-item-mc-question], [data-session-phat-am-item-mc-option], [data-session-phat-am-item-tf-add], [data-session-phat-am-item-mc-add], [data-session-phat-am-item-tf-remove], [data-session-phat-am-item-mc-remove], [data-session-item-points]").forEach((field) => {
      field.disabled = !isPhatAm;
    });

    if (phatAmJsonInput) {
      if (isPhatAm) {
        phatAmJsonInput.disabled = false;
        phatAmJsonInput.setAttribute("name", phatAmJsonInput.dataset.originalName || phatAmJsonInput.name);
        window.ProgramPhatAmItems?.syncIn?.(questionItem);
      } else {
        phatAmJsonInput.disabled = true;
        phatAmJsonInput.removeAttribute("name");
      }
    }

    const explanationWrap = questionItem.querySelector("[data-session-review-explanation]");
    const explanationInput = explanationWrap?.querySelector("textarea.richtext-explanation");

    if (explanationWrap) explanationWrap.hidden = isPhatAm;

    if (explanationInput) {
      if (!explanationInput.dataset.originalName && explanationInput.getAttribute("name")) {
        explanationInput.dataset.originalName = explanationInput.getAttribute("name");
      }

      if (isPhatAm) {
        if (typeof tinymce !== "undefined" && explanationInput.id) {
          tinymce.get(explanationInput.id)?.save();
        }
        explanationInput.disabled = true;
        explanationInput.removeAttribute("name");
      } else {
        explanationInput.disabled = false;
        explanationInput.setAttribute("name", explanationInput.dataset.originalName || "");
      }
    }

    const isImageHotspot = typeValue === "image_hotspot";
    const imageHotspotPanel = questionItem.querySelector("[data-session-image-hotspot-panel]");
    const imageHotspotJsonInput = questionItem.querySelector("[data-session-image-hotspot-json]");

    if (imageHotspotPanel) imageHotspotPanel.hidden = !isImageHotspot;

    questionItem.querySelectorAll("[data-session-image-hotspot-image], [data-session-image-hotspot-tolerance], [data-session-image-hotspot-target-label]").forEach((field) => {
      field.disabled = !isImageHotspot;
    });

    questionItem.querySelectorAll(".session-pair-image-file").forEach((field) => {
      if (field.closest("[data-session-image-hotspot-panel]")) {
        field.disabled = !isImageHotspot;
      }
    });

    questionItem.querySelector("[data-session-image-hotspot-clear-targets]")?.toggleAttribute("disabled", !isImageHotspot);
    questionItem.querySelectorAll("[data-session-image-hotspot-remove-target]").forEach((button) => {
      button.toggleAttribute("disabled", !isImageHotspot);
    });

    if (imageHotspotJsonInput) {
      if (isImageHotspot) {
        imageHotspotJsonInput.disabled = false;
        imageHotspotJsonInput.setAttribute("name", imageHotspotJsonInput.dataset.originalName || imageHotspotJsonInput.name);
        window.ProgramImageHotspotPanel?.sync?.(questionItem);
        const imageWrap = questionItem.querySelector("[data-session-image-hotspot-image-wrap]");
        pairImage?.init?.(imageWrap, () => {
          window.ProgramImageHotspotPanel?.sync?.(questionItem);
          userScheduleAutosave();
        });
      } else {
        imageHotspotJsonInput.disabled = true;
        imageHotspotJsonInput.removeAttribute("name");
      }
    }

    const isImageLabelDrop = typeValue === "image_label_drop";
    const imageLabelDropPanel = questionItem.querySelector("[data-session-image-label-drop-panel]");
    const imageLabelDropJsonInput = questionItem.querySelector("[data-session-image-label-drop-json]");

    if (imageLabelDropPanel) imageLabelDropPanel.hidden = !isImageLabelDrop;

    questionItem.querySelectorAll("[data-session-image-label-drop-image], [data-session-image-label-drop-radius], [data-session-image-label-drop-zone-label], [data-session-image-label-drop-label-text], [data-session-image-label-drop-label-zone]").forEach((field) => {
      field.disabled = !isImageLabelDrop;
    });

    questionItem.querySelectorAll(".session-pair-image-file").forEach((field) => {
      if (field.closest("[data-session-image-label-drop-panel]")) {
        field.disabled = !isImageLabelDrop;
      }
    });

    questionItem.querySelector("[data-session-image-label-drop-clear-zones]")?.toggleAttribute("disabled", !isImageLabelDrop);
    questionItem.querySelector("[data-session-image-label-drop-add-label]")?.toggleAttribute("disabled", !isImageLabelDrop);
    questionItem.querySelectorAll("[data-session-image-label-drop-remove-zone], [data-session-image-label-drop-remove-label]").forEach((button) => {
      button.toggleAttribute("disabled", !isImageLabelDrop);
    });

    if (imageLabelDropJsonInput) {
      if (isImageLabelDrop) {
        imageLabelDropJsonInput.disabled = false;
        imageLabelDropJsonInput.setAttribute("name", imageLabelDropJsonInput.dataset.originalName || imageLabelDropJsonInput.name);
        window.ProgramImageLabelDropPanel?.sync?.(questionItem);
        const imageWrap = questionItem.querySelector("[data-session-image-label-drop-image-wrap]");
        pairImage?.init?.(imageWrap, () => {
          window.ProgramImageLabelDropPanel?.sync?.(questionItem);
          userScheduleAutosave();
        });
      } else {
        imageLabelDropJsonInput.disabled = true;
        imageLabelDropJsonInput.removeAttribute("name");
      }
    }
  };

  const bindListeningPanel = () => {
    questionItem.querySelectorAll("[data-listening-answer-mode-radio]").forEach((radio) => {
      radio.addEventListener("change", () => {
        if (!radio.checked) return;
        syncQuestionTypeFields();
        userScheduleAutosave();
      });
    });

    questionItem.querySelector("[data-session-listening-mp3-upload] [data-qb-audio-saved]")?.addEventListener("input", () => {
      userScheduleAutosave();
    });

    window.ProgramMp3Upload?.bindIn?.(questionItem);
  };

  const reindexListeningTrueFalseRows = () => {
    const list = questionItem.querySelector("[data-session-listening-true-false-list]");
    if (!list) return;

    list.querySelectorAll("[data-session-listening-true-false-item]").forEach((row, index) => {
      const badge = row.querySelector(".session-listening-true-false-badge");
      if (badge) badge.textContent = String(index + 1);

      row.querySelectorAll("[data-session-listening-true-false-answer]").forEach((radio) => {
        radio.name = `listening-tf-${questionItem.dataset.questionIndex || "q"}-${index}`;
      });

      const removeBtn = row.querySelector("[data-session-listening-true-false-remove]");
      if (removeBtn) {
        removeBtn.hidden = list.querySelectorAll("[data-session-listening-true-false-item]").length <= 1;
      }
    });
  };

  const syncListeningTrueFalseJson = () => {
    const jsonInput = questionItem.querySelector("[data-session-listening-true-false-json]");
    const list = questionItem.querySelector("[data-session-listening-true-false-list]");
    if (!jsonInput || !list) return;

    const items = [];
    list.querySelectorAll("[data-session-listening-true-false-item]").forEach((row) => {
      const statement = row.querySelector("[data-session-listening-true-false-statement]")?.value.trim() || "";
      const answerRadio = row.querySelector("[data-session-listening-true-false-answer]:checked");
      items.push({
        statement,
        answer: answerRadio?.value === "true",
      });
    });

    jsonInput.value = JSON.stringify(items);
  };

  const createListeningTrueFalseRow = () => {
    const row = document.createElement("li");
    row.className = "session-listening-true-false-item";
    row.dataset.sessionListeningTrueFalseItem = "";
    row.innerHTML =
      `<span class="session-listening-true-false-badge">1</span>` +
      `<input type="text" class="form-control form-control-sm session-listening-true-false-statement" data-session-listening-true-false-statement placeholder="VD: Học sinh đi học lúc 8 giờ sáng.">` +
      `<div class="session-listening-true-false-answer-toggle" role="group">` +
      `<label class="session-listening-true-false-answer-option"><input type="radio" value="true" data-session-listening-true-false-answer checked><span>Đúng</span></label>` +
      `<label class="session-listening-true-false-answer-option"><input type="radio" value="false" data-session-listening-true-false-answer><span>Sai</span></label>` +
      `</div>` +
      `<button class="btn sunny-mini session-listening-true-false-remove" type="button" data-session-listening-true-false-remove title="Xóa câu"><i class="bi bi-x-lg" aria-hidden="true"></i></button>`;
    return row;
  };

  const bindListeningTrueFalseRow = (row) => {
    row.querySelector("[data-session-listening-true-false-statement]")?.addEventListener("input", () => {
      syncListeningTrueFalseJson();
      userScheduleAutosave();
    });

    row.querySelectorAll("[data-session-listening-true-false-answer]").forEach((radio) => {
      radio.addEventListener("change", () => {
        syncListeningTrueFalseJson();
        userScheduleAutosave();
      });
    });

    row.querySelector("[data-session-listening-true-false-remove]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-listening-true-false-list]");
      if (!list || list.querySelectorAll("[data-session-listening-true-false-item]").length <= 1) return;
      row.remove();
      reindexListeningTrueFalseRows();
      syncListeningTrueFalseJson();
      userScheduleAutosave();
    });
  };

  const bindListeningTrueFalsePanel = () => {
    questionItem.querySelectorAll("[data-session-listening-true-false-item]").forEach(bindListeningTrueFalseRow);

    questionItem.querySelector("[data-session-listening-true-false-add]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-listening-true-false-list]");
      if (!list) return;
      const row = createListeningTrueFalseRow();
      list.appendChild(row);
      bindListeningTrueFalseRow(row);
      reindexListeningTrueFalseRows();
      syncListeningTrueFalseJson();
      row.querySelector("[data-session-listening-true-false-statement]")?.focus();
      userScheduleAutosave();
    });

    reindexListeningTrueFalseRows();
    syncListeningTrueFalseJson();
  };

  const reindexCategorySortRows = () => {
    questionItem.querySelectorAll("[data-session-category-sort-group-item]").forEach((row, index) => {
      const badge = row.querySelector(".session-category-sort-group-badge");
      if (badge) badge.textContent = String(index + 1);
      row.dataset.groupId = String(index);
    });

    questionItem.querySelectorAll("[data-session-category-sort-item]").forEach((row, index) => {
      const badge = row.querySelector(".session-category-sort-item-badge");
      if (badge) badge.textContent = String(index + 1);
    });
  };

  const rebuildCategorySortGroupSelects = () => {
    const groups = [];
    questionItem.querySelectorAll("[data-session-category-sort-group-item]").forEach((row, index) => {
      const label = row.querySelector("[data-session-category-sort-group-label]")?.value.trim() || "";
      groups.push({ id: String(index), label: label || `Nhóm ${index + 1}` });
    });

    questionItem.querySelectorAll("[data-session-category-sort-item-group]").forEach((select) => {
      const current = select.value;
      select.innerHTML = groups
        .map((group) => `<option value="${group.id}">${group.label.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</option>`)
        .join("");
      if (groups.some((group) => group.id === current)) select.value = current;
      else if (groups[0]) select.value = groups[0].id;
    });
  };

  const syncCategorySortJson = () => {
    const jsonInput = questionItem.querySelector("[data-session-category-sort-json]");
    const groupsList = questionItem.querySelector("[data-session-category-sort-groups-list]");
    const itemsList = questionItem.querySelector("[data-session-category-sort-items-list]");
    if (!jsonInput || !groupsList || !itemsList) return;

    const groups = [];
    groupsList.querySelectorAll("[data-session-category-sort-group-item]").forEach((row, index) => {
      const label = row.querySelector("[data-session-category-sort-group-label]")?.value.trim() || "";
      if (label) groups.push({ id: String(index), label });
    });

    const items = [];
    itemsList.querySelectorAll("[data-session-category-sort-item]").forEach((row, index) => {
      const text = row.querySelector("[data-session-category-sort-item-text]")?.value.trim() || "";
      const groupId = row.querySelector("[data-session-category-sort-item-group]")?.value || "0";
      if (text) items.push({ id: String(index), text, group_id: groupId });
    });

    jsonInput.value = JSON.stringify({ groups, items });
    reindexCategorySortRows();
  };

  const createCategorySortGroupRow = () => {
    const li = document.createElement("li");
    li.className = "session-category-sort-group-item";
    li.dataset.sessionCategorySortGroupItem = "";
    li.innerHTML =
      `<span class="session-category-sort-group-badge">1</span>` +
      `<input type="text" class="form-control form-control-sm session-category-sort-group-label" data-session-category-sort-group-label placeholder="VD: Danh từ / Động từ / Tính từ">` +
      `<button class="btn sunny-mini btn-delete-user" type="button" data-session-category-sort-remove-group title="Xóa nhóm"><i class="bi bi-trash"></i></button>`;
    return li;
  };

  const createCategorySortItemRow = () => {
    const li = document.createElement("li");
    li.className = "session-category-sort-item-row";
    li.dataset.sessionCategorySortItem = "";
    li.innerHTML =
      `<span class="session-category-sort-item-badge">1</span>` +
      `<input type="text" class="form-control form-control-sm session-category-sort-item-text" data-session-category-sort-item-text placeholder="VD: 학교 / 가다">` +
      `<select class="form-select form-select-sm session-category-sort-item-group" data-session-category-sort-item-group></select>` +
      `<button class="btn sunny-mini btn-delete-user" type="button" data-session-category-sort-remove-item title="Xóa thẻ"><i class="bi bi-trash"></i></button>`;
    return li;
  };

  const bindCategorySortGroupRow = (row) => {
    row.querySelector("[data-session-category-sort-group-label]")?.addEventListener("input", () => {
      rebuildCategorySortGroupSelects();
      syncCategorySortJson();
      userScheduleAutosave();
    });

    row.querySelector("[data-session-category-sort-remove-group]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-category-sort-groups-list]");
      if ((list?.querySelectorAll("[data-session-category-sort-group-item]").length || 0) <= 2) return;
      row.remove();
      rebuildCategorySortGroupSelects();
      syncCategorySortJson();
      userScheduleAutosave();
    });
  };

  const bindCategorySortItemRow = (row) => {
    row.querySelector("[data-session-category-sort-item-text]")?.addEventListener("input", () => {
      syncCategorySortJson();
      userScheduleAutosave();
    });

    row.querySelector("[data-session-category-sort-item-group]")?.addEventListener("change", () => {
      syncCategorySortJson();
      userScheduleAutosave();
    });

    row.querySelector("[data-session-category-sort-remove-item]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-category-sort-items-list]");
      if ((list?.querySelectorAll("[data-session-category-sort-item]").length || 0) <= 2) return;
      row.remove();
      syncCategorySortJson();
      userScheduleAutosave();
    });
  };

  const bindCategorySortPanel = () => {
    const groupsList = questionItem.querySelector("[data-session-category-sort-groups-list]");
    const itemsList = questionItem.querySelector("[data-session-category-sort-items-list]");

    groupsList?.querySelectorAll("[data-session-category-sort-group-item]").forEach(bindCategorySortGroupRow);
    itemsList?.querySelectorAll("[data-session-category-sort-item]").forEach(bindCategorySortItemRow);
    rebuildCategorySortGroupSelects();

    questionItem.querySelector("[data-session-category-sort-add-group]")?.addEventListener("click", () => {
      if (!groupsList) return;
      const row = createCategorySortGroupRow();
      groupsList.appendChild(row);
      bindCategorySortGroupRow(row);
      rebuildCategorySortGroupSelects();
      syncCategorySortJson();
      userScheduleAutosave();
    });

    questionItem.querySelector("[data-session-category-sort-add-item]")?.addEventListener("click", () => {
      if (!itemsList) return;
      const row = createCategorySortItemRow();
      itemsList.appendChild(row);
      bindCategorySortItemRow(row);
      rebuildCategorySortGroupSelects();
      syncCategorySortJson();
      userScheduleAutosave();
    });
  };

  const countFillBlanksInTemplate = (template) => {
    const matches = String(template || "").match(/_{3,}/g);
    return matches ? matches.length : 0;
  };

  const rebuildFillBlanksAnswerRows = () => {
    const list = questionItem.querySelector("[data-session-fill-blanks-answers-list]");
    const template = questionItem.querySelector("[data-session-fill-blanks-template]")?.value || "";
    if (!list) return;

    const count = Math.max(countFillBlanksInTemplate(template), 1);
    const existing = [...list.querySelectorAll("[data-session-fill-blanks-answer-input]")].map((input) => input.value);
    list.innerHTML = "";

    for (let i = 0; i < count; i += 1) {
      const li = document.createElement("li");
      li.className = "session-fill-blanks-answer-item";
      li.dataset.sessionFillBlanksAnswerItem = "";
      li.innerHTML =
        `<span class="session-fill-blanks-answer-no">Ô ${i + 1}</span>` +
        `<input type="text" class="form-control form-control-sm session-fill-blanks-answer-input" data-session-fill-blanks-answer-input placeholder="Đáp án cho ô ${i + 1}">`;
      const input = li.querySelector("[data-session-fill-blanks-answer-input]");
      if (input) input.value = existing[i] || "";
      list.appendChild(li);
      input?.addEventListener("input", () => {
        syncFillBlanksJson();
        userScheduleAutosave();
      });
    }
  };

  const syncFillBlanksJson = () => {
    const jsonInput = questionItem.querySelector("[data-session-fill-blanks-json]");
    const template = questionItem.querySelector("[data-session-fill-blanks-template]")?.value.trim() || "";
    if (!jsonInput) return;

    const answers = [...questionItem.querySelectorAll("[data-session-fill-blanks-answer-input]")].map((input) => input.value.trim());
    jsonInput.value = JSON.stringify({ template, answers });
    renderFillBlanksPreview(template, answers);
  };

  const renderFillBlanksPreview = (template, answers) => {
    const previewText = questionItem.querySelector("[data-session-fill-blanks-preview-text]");
    if (!previewText) return;

    if (!template.trim()) {
      previewText.textContent = "";
      return;
    }

    let answerIndex = 0;
    const html = template.split(/(_{3,})/g).map((chunk) => {
      if (/^_{3,}$/.test(chunk)) {
        const answer = answers[answerIndex] || "___";
        answerIndex += 1;
        const safe = answer.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        return `<span class="session-fill-blanks-preview-slot">${safe}</span>`;
      }
      return chunk.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }).join("");

    previewText.innerHTML = html;
  };

  const bindFillBlanksPanel = () => {
    const templateInput = questionItem.querySelector("[data-session-fill-blanks-template]");

    templateInput?.addEventListener("input", () => {
      rebuildFillBlanksAnswerRows();
      syncFillBlanksJson();
      userScheduleAutosave();
    });

    questionItem.querySelector("[data-session-fill-blanks-insert]")?.addEventListener("click", () => {
      if (!templateInput) return;
      const start = templateInput.selectionStart ?? templateInput.value.length;
      const end = templateInput.selectionEnd ?? start;
      const before = templateInput.value.slice(0, start);
      const after = templateInput.value.slice(end);
      templateInput.value = `${before} ___ ${after}`;
      templateInput.focus();
      templateInput.selectionStart = templateInput.selectionEnd = start + 4;
      rebuildFillBlanksAnswerRows();
      syncFillBlanksJson();
      userScheduleAutosave();
    });

    questionItem.querySelectorAll("[data-session-fill-blanks-answer-input]").forEach((input) => {
      input.addEventListener("input", () => {
        syncFillBlanksJson();
        userScheduleAutosave();
      });
    });

    syncFillBlanksJson();
  };

  const createWordSelectBankRow = (value = "") => {
    const li = document.createElement("li");
    li.className = "session-word-select-bank-item";
    li.dataset.sessionWordSelectBankItem = "";
    li.innerHTML =
      `<input type="text" class="form-control form-control-sm session-word-select-bank-input" data-session-word-select-bank-input placeholder="Từ trong danh sách">` +
      `<button class="btn sunny-mini btn-delete-user" type="button" data-session-word-select-remove-word title="Xóa từ"><i class="bi bi-trash"></i></button>`;
    const input = li.querySelector("[data-session-word-select-bank-input]");
    if (input) input.value = value;
    input?.addEventListener("input", () => {
      syncWordSelectJson();
      userScheduleAutosave();
    });
    li.querySelector("[data-session-word-select-remove-word]")?.addEventListener("click", () => {
      li.remove();
      syncWordSelectJson();
      userScheduleAutosave();
    });
    return li;
  };

  const rebuildWordSelectAnswerRows = () => {
    const list = questionItem.querySelector("[data-session-word-select-answers-list]");
    const template = questionItem.querySelector("[data-session-word-select-template]")?.value || "";
    if (!list) return;

    const count = Math.max(countFillBlanksInTemplate(template), 1);
    const existing = [...list.querySelectorAll("[data-session-word-select-answer-input]")].map((input) => input.value);
    list.innerHTML = "";

    for (let i = 0; i < count; i += 1) {
      const li = document.createElement("li");
      li.className = "session-word-select-answer-item";
      li.dataset.sessionWordSelectAnswerItem = "";
      li.innerHTML =
        `<span class="session-word-select-answer-no">Ô ${i + 1}</span>` +
        `<input type="text" class="form-control form-control-sm session-word-select-answer-input" data-session-word-select-answer-input placeholder="Đáp án cho ô ${i + 1}">`;
      const input = li.querySelector("[data-session-word-select-answer-input]");
      if (input) input.value = existing[i] || "";
      list.appendChild(li);
      input?.addEventListener("input", () => {
        syncWordSelectJson();
        userScheduleAutosave();
      });
    }
  };

  const renderWordSelectPreview = (template, answers, wordBank) => {
    const previewText = questionItem.querySelector("[data-session-word-select-preview-text]");
    const previewBank = questionItem.querySelector("[data-session-word-select-preview-bank]");
    if (!previewText) return;

    if (!template.trim()) {
      previewText.textContent = "";
      if (previewBank) previewBank.innerHTML = "";
      return;
    }

    let answerIndex = 0;
    previewText.innerHTML = template
      .split(/(_{3,})/g)
      .map((chunk) => {
        if (/^_{3,}$/.test(chunk)) {
          const answer = answers[answerIndex] || "…";
          answerIndex += 1;
          const safe = answer.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
          return `<span class="session-word-select-preview-slot">${safe}</span>`;
        }
        return chunk.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
      })
      .join("");

    if (previewBank) {
      previewBank.innerHTML = wordBank.length
        ? wordBank.map((word) => {
            const safe = word.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            return `<span class="session-word-select-preview-chip">${safe}</span>`;
          }).join("")
        : '<span class="muted">Chưa có từ trong danh sách</span>';
    }
  };

  const syncWordSelectJson = () => {
    const jsonInput = questionItem.querySelector("[data-session-word-select-json]");
    const template = questionItem.querySelector("[data-session-word-select-template]")?.value.trim() || "";
    if (!jsonInput) return;

    const answers = [...questionItem.querySelectorAll("[data-session-word-select-answer-input]")].map((input) => input.value.trim());
    const wordBank = [...questionItem.querySelectorAll("[data-session-word-select-bank-input]")]
      .map((input) => input.value.trim())
      .filter((word, index, list) => word !== "" && list.indexOf(word) === index);

    answers.filter(Boolean).forEach((word) => {
      if (!wordBank.includes(word)) wordBank.push(word);
    });

    jsonInput.value = JSON.stringify({ template, answers, word_bank: wordBank });
    renderWordSelectPreview(template, answers, wordBank);
  };

  const bindWordSelectPanel = () => {
    const templateInput = questionItem.querySelector("[data-session-word-select-template]");

    templateInput?.addEventListener("input", () => {
      rebuildWordSelectAnswerRows();
      syncWordSelectJson();
      userScheduleAutosave();
    });

    questionItem.querySelector("[data-session-word-select-insert]")?.addEventListener("click", () => {
      if (!templateInput) return;
      const start = templateInput.selectionStart ?? templateInput.value.length;
      const end = templateInput.selectionEnd ?? start;
      templateInput.value = `${templateInput.value.slice(0, start)} ___ ${templateInput.value.slice(end)}`;
      templateInput.focus();
      templateInput.selectionStart = templateInput.selectionEnd = start + 4;
      rebuildWordSelectAnswerRows();
      syncWordSelectJson();
      userScheduleAutosave();
    });

    questionItem.querySelectorAll("[data-session-word-select-answer-input]").forEach((input) => {
      input.addEventListener("input", () => {
        syncWordSelectJson();
        userScheduleAutosave();
      });
    });

    questionItem.querySelectorAll("[data-session-word-select-bank-input]").forEach((input) => {
      input.addEventListener("input", () => {
        syncWordSelectJson();
        userScheduleAutosave();
      });
    });

    questionItem.querySelectorAll("[data-session-word-select-remove-word]").forEach((button) => {
      button.addEventListener("click", () => {
        button.closest("[data-session-word-select-bank-item]")?.remove();
        syncWordSelectJson();
        userScheduleAutosave();
      });
    });

    questionItem.querySelector("[data-session-word-select-sync-bank]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-word-select-bank-list]");
      if (!list) return;
      const answers = [...questionItem.querySelectorAll("[data-session-word-select-answer-input]")]
        .map((input) => input.value.trim())
        .filter(Boolean);
      const existing = [...list.querySelectorAll("[data-session-word-select-bank-input]")]
        .map((input) => input.value.trim())
        .filter(Boolean);
      const merged = [...existing];
      answers.forEach((word) => {
        if (!merged.includes(word)) merged.push(word);
      });
      list.innerHTML = "";
      if (merged.length === 0) {
        list.appendChild(createWordSelectBankRow());
      } else {
        merged.forEach((word) => list.appendChild(createWordSelectBankRow(word)));
      }
      syncWordSelectJson();
      userScheduleAutosave();
    });

    questionItem.querySelector("[data-session-word-select-add-word]")?.addEventListener("click", () => {
      questionItem.querySelector("[data-session-word-select-bank-list]")?.appendChild(createWordSelectBankRow());
      syncWordSelectJson();
      userScheduleAutosave();
    });

    syncWordSelectJson();
  };

  const reindexWordTranslateItems = (list) => {
    list.querySelectorAll("[data-session-word-translate-item]").forEach((row, index) => {
      const badge = row.querySelector(".session-word-translate-item-no");
      if (badge) badge.textContent = String(index + 1);
    });
  };

  const parseWordTranslateJsonPayload = () => {
    const jsonInput = questionItem.querySelector("[data-session-word-translate-json]");
    if (!jsonInput?.value) return null;

    try {
      const parsed = JSON.parse(jsonInput.value);
      return parsed && typeof parsed === "object" ? parsed : null;
    } catch (_error) {
      return null;
    }
  };

  const readWordTranslateRowFromJson = (row) => {
    const parsed = parseWordTranslateJsonPayload();
    if (!parsed) return null;

    const items = Array.isArray(parsed.items) ? parsed.items : [];
    const rows = [...questionItem.querySelectorAll("[data-session-word-translate-item]")];
    const index = rows.indexOf(row);

    if (index < 0 || !items[index]) return null;

    return items[index];
  };

  const cacheWordTranslateRowDraft = (row) => {
    const sourceType = row.querySelector("[data-session-word-translate-source-type]")?.value === "image" ? "image" : "text";
    const source = getWordTranslateSourceValue(row, { includeJsonFallback: false });
    const answer = row.querySelector("[data-session-word-translate-answer-input]")?.value.trim() || "";

    row.dataset.wordTranslateSourceTypeDraft = sourceType;
    row.dataset.wordTranslateSourceDraft = source;
    row.dataset.wordTranslateAnswerDraft = answer;
  };

  const getWordTranslateSourceValue = (row, options = {}) => {
    const includeJsonFallback = options.includeJsonFallback !== false;
    const type = row.querySelector("[data-session-word-translate-source-type]")?.value || "text";

    let live = "";

    if (type === "image") {
      live = pairImage?.getValue(row.querySelector("[data-session-word-translate-source-image]")) || "";
    } else {
      const active = document.activeElement;
      if (active && row.contains(active) && active.matches("[data-session-word-translate-source-text]")) {
        live = active.value ?? "";
      } else {
        live =
          row.querySelector("[data-session-word-translate-source-text]")?.value ??
          row.querySelector("[data-session-word-translate-source-value-wrap] input:not([type='file']):not([type='hidden'])")?.value ??
          "";
      }
    }

    live = String(live).trim();
    if (live !== "") return live;

    if (row.dataset.wordTranslateSourceDraft?.trim()) {
      return row.dataset.wordTranslateSourceDraft.trim();
    }

    if (!includeJsonFallback) return "";

    const cached = readWordTranslateRowFromJson(row);
    if (!cached) return "";

    return String(cached.source || "").trim();
  };

  const getWordTranslateAnswerValue = (row) => {
    const live = row.querySelector("[data-session-word-translate-answer-input]")?.value.trim() || "";
    if (live !== "") return live;

    if (row.dataset.wordTranslateAnswerDraft?.trim()) {
      return row.dataset.wordTranslateAnswerDraft.trim();
    }

    const cached = readWordTranslateRowFromJson(row);
    return cached ? String(cached.answer || "").trim() : "";
  };

  const renderWordTranslateSourceField = (type, value = "") => {
    if (type === "image") {
      const display = value ? pairImage?.resolveMediaUrl(value) || value : "";

      return (
        pairImage?.renderFieldHtml(
          'data-session-word-translate-source-image class="session-word-translate-source-image session-memory-pair-input"',
          display
        ) || ""
      );
    }

    return (
      `<input type="text" class="form-control form-control-sm session-word-translate-source-text" data-session-word-translate-source-text` +
      `" value="${String(value).replace(/"/g, "&quot;")}" placeholder="Từ / cụm cần dịch">`
    );
  };

  const mountWordTranslateSourceField = (row) => {
    const wrap = row.querySelector("[data-session-word-translate-source-value-wrap]");
    const typeSelect = row.querySelector("[data-session-word-translate-source-type]");
    if (!wrap || !typeSelect) return;

    const active = document.activeElement;
    if (
      active &&
      row.contains(active) &&
      (active.matches("[data-session-word-translate-source-text]") ||
        active.matches("[data-session-word-translate-source-image]") ||
        active.matches(".session-pair-image-url"))
    ) {
      return;
    }

    cacheWordTranslateRowDraft(row);

    const typeValue = typeSelect.value === "image" ? "image" : "text";
    const preserved = getWordTranslateSourceValue(row);

    pairImage?.destroyIn(wrap);
    wrap.innerHTML = renderWordTranslateSourceField(typeValue, preserved);

    const mountedInput =
      typeValue === "image"
        ? wrap.querySelector("[data-session-word-translate-source-image], .session-pair-image-url")
        : wrap.querySelector("[data-session-word-translate-source-text]");

    if (mountedInput && preserved !== "") {
      if (typeValue === "image") {
        pairImage?.setValue?.(mountedInput, preserved);
      } else {
        mountedInput.value = preserved;
      }
    }

    if (typeValue === "image") {
      const imageWrap = wrap.querySelector("[data-session-pair-image-wrap]") || wrap;
      pairImage?.init(imageWrap, () => {
        cacheWordTranslateRowDraft(row);
        syncWordTranslateJson();
        userScheduleAutosave();
      });
      return;
    }

    mountedInput?.addEventListener("input", () => {
      cacheWordTranslateRowDraft(row);
      syncWordTranslateJson();
      userScheduleAutosave();
    });
    mountedInput?.addEventListener("blur", () => {
      cacheWordTranslateRowDraft(row);
      syncWordTranslateJson();
    });
  };

  const initWordTranslateImageEditors = () => {
    questionItem.querySelectorAll("[data-session-word-translate-item]").forEach((row) => {
      const type = row.querySelector("[data-session-word-translate-source-type]")?.value || "text";
      if (type !== "image") return;

      const imageWrap = row.querySelector("[data-session-word-translate-source-value-wrap] [data-session-pair-image-wrap]");
      if (imageWrap) {
        pairImage?.init(imageWrap, () => {
          syncWordTranslateJson();
          userScheduleAutosave();
        });
      }
    });
  };

  const ensureWordTranslateDefaultItems = () => {
    const list = questionItem.querySelector("[data-session-word-translate-items-list]");
    if (!list) return;

    const rows = list.querySelectorAll("[data-session-word-translate-item]");
    if (rows.length > 0) return;

    for (let i = 0; i < 2; i += 1) {
      list.appendChild(createWordTranslateRow());
    }

    reindexWordTranslateItems(list);
  };

  const ensureWordTranslateSourceFields = () => {
    questionItem.querySelectorAll("[data-session-word-translate-item]").forEach((row) => {
      const wrap = row.querySelector("[data-session-word-translate-source-value-wrap]");
      const typeSelect = row.querySelector("[data-session-word-translate-source-type]");
      if (!wrap || !typeSelect) return;

      const typeValue = typeSelect.value === "image" ? "image" : "text";
      const hasField =
        typeValue === "image"
          ? wrap.querySelector("[data-session-pair-image-wrap], [data-session-word-translate-source-image], .session-pair-image-url")
          : wrap.querySelector("[data-session-word-translate-source-text], input[type='text']");

      if (!hasField) {
        mountWordTranslateSourceField(row);
      }
    });

    initWordTranslateImageEditors();
  };

  const escapeWordTranslateHtml = (value) =>
    String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  const renderWordTranslatePreview = (items, columns, layout) => {
    const previewGrid = questionItem.querySelector("[data-session-word-translate-preview-grid]");
    if (!previewGrid) return;

    previewGrid.className = "session-word-translate-preview-grid";
    previewGrid.classList.toggle("session-word-translate-preview-grid--cols-2", Number(columns) === 2);
    previewGrid.classList.toggle("session-word-translate-preview-grid--horizontal", layout === "horizontal");
    previewGrid.classList.toggle("session-word-translate-preview-grid--vertical", layout !== "horizontal");

    if (!items.length) {
      previewGrid.innerHTML = '<span class="muted">Chưa có từ nào — bấm «Thêm từ» để bắt đầu</span>';
      return;
    }

    previewGrid.innerHTML = items
      .map((item) => {
        const sourceType = item.source_type === "image" ? "image" : "text";
        const source = String(item.source || "");
        const answer = escapeWordTranslateHtml(item.answer || "…");
        const sourceHtml =
          sourceType === "image" && source
            ? `<img src="${escapeWordTranslateHtml(pairImage?.resolveMediaUrl(source) || source)}" alt="" class="session-word-translate-preview-image">`
            : `<span class="session-word-translate-preview-source">${escapeWordTranslateHtml(source) || "Từ gốc"}</span>`;

        return (
          `<div class="session-word-translate-preview-item session-word-translate-preview-item--${layout}">` +
          sourceHtml +
          `<span class="session-word-translate-preview-input">${answer}</span>` +
          `</div>`
        );
      })
      .join("");
  };

  const syncWordTranslateJson = () => {
    const jsonInput = questionItem.querySelector("[data-session-word-translate-json]");
    if (!jsonInput) return;

    const items = [...questionItem.querySelectorAll("[data-session-word-translate-item]")].map((row) => {
      cacheWordTranslateRowDraft(row);

      return {
        source_type: row.querySelector("[data-session-word-translate-source-type]")?.value === "image" ? "image" : "text",
        source: getWordTranslateSourceValue(row),
        answer: getWordTranslateAnswerValue(row),
      };
    });

    const columns = Number(
      questionItem.querySelector("[data-session-word-translate-columns-radio]:checked")?.value || "1"
    );
    const layout = questionItem.querySelector("[data-session-word-translate-layout-radio]:checked")?.value || "vertical";

    jsonInput.value = JSON.stringify({ items, columns, layout });
    renderWordTranslatePreview(items, columns, layout);
  };

  const bindPhatAmPanel = () => {
    window.ProgramPhatAmItems?.bindIn?.(questionItem, userScheduleAutosave);
  };

  const bindListeningItemsPanel = () => {
    window.ProgramListeningItems?.bindIn?.(questionItem, userScheduleAutosave);
  };

  const createWordTranslateRow = (item = {}) => {
    const sourceType = item.source_type === "image" ? "image" : "text";
    const source = String(item.source || "");
    const answer = String(item.answer || "");
    const li = document.createElement("li");
    li.className = "session-word-translate-item";
    li.setAttribute("data-session-word-translate-item", "");
    li.innerHTML =
      `<span class="session-word-translate-item-no">1</span>` +
      `<div class="session-word-translate-source-cell">` +
      `<select class="form-select form-select-sm session-word-translate-source-type" data-session-word-translate-source-type aria-label="Loại từ gốc">` +
      `<option value="text"${sourceType === "text" ? " selected" : ""}>Chữ</option>` +
      `<option value="image"${sourceType === "image" ? " selected" : ""}>Ảnh</option>` +
      `</select>` +
      `<div class="session-word-translate-source-value-wrap" data-session-word-translate-source-value-wrap></div>` +
      `</div>` +
      `<input type="text" class="form-control form-control-sm session-word-translate-answer-input" data-session-word-translate-answer-input placeholder="Đáp án dịch đúng">` +
      `<button class="btn sunny-mini btn-delete-user" type="button" data-session-word-translate-remove-item title="Xóa dòng"><i class="bi bi-trash"></i></button>`;
    const answerInput = li.querySelector("[data-session-word-translate-answer-input]");
    if (answerInput) answerInput.value = answer;
    const wrap = li.querySelector("[data-session-word-translate-source-value-wrap]");
    if (wrap) {
      wrap.innerHTML = renderWordTranslateSourceField(sourceType, source);
    }
    bindWordTranslateRow(li);
    cacheWordTranslateRowDraft(li);
    if (sourceType === "image") {
      const imageWrap = wrap?.querySelector("[data-session-pair-image-wrap]");
      if (imageWrap) {
        pairImage?.init(imageWrap, () => {
          syncWordTranslateJson();
          userScheduleAutosave();
        });
      }
    }
    return li;
  };

  const bindWordTranslateRow = (row) => {
    if (row.dataset.wordTranslateBound === "1") return;
    row.dataset.wordTranslateBound = "1";

    row.querySelector("[data-session-word-translate-source-type]")?.addEventListener("change", () => {
      mountWordTranslateSourceField(row);
      syncWordTranslateJson();
      userScheduleAutosave();
    });
    row.querySelector("[data-session-word-translate-answer-input]")?.addEventListener("input", () => {
      cacheWordTranslateRowDraft(row);
      syncWordTranslateJson();
      userScheduleAutosave();
    });
    row.querySelector("[data-session-word-translate-answer-input]")?.addEventListener("blur", () => {
      cacheWordTranslateRowDraft(row);
      syncWordTranslateJson();
    });
    row.querySelector("[data-session-word-translate-remove-item]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-word-translate-items-list]");
      if ((list?.querySelectorAll("[data-session-word-translate-item]").length || 0) <= 1) return;
      pairImage?.destroyIn(row);
      row.remove();
      reindexWordTranslateItems(list);
      syncWordTranslateJson();
      userScheduleAutosave();
    });

    const bindSourceField = (input) => {
      if (!input || input.dataset.wordTranslateSourceBound === "1") return;
      input.dataset.wordTranslateSourceBound = "1";
      input.addEventListener("input", () => {
        cacheWordTranslateRowDraft(row);
        syncWordTranslateJson();
        userScheduleAutosave();
      });
      input.addEventListener("blur", () => {
        cacheWordTranslateRowDraft(row);
        syncWordTranslateJson();
      });
    };

    bindSourceField(row.querySelector("[data-session-word-translate-source-text]"));
  };

  const restoreWordTranslateRowsFromHiddenJson = () => {
    const list = questionItem.querySelector("[data-session-word-translate-items-list]");
    if (!list) return;

    const payload = parseWordTranslateJsonPayload();
    if (!payload) return;

    const items = Array.isArray(payload.items) ? payload.items : [];
    const domRows = [...list.querySelectorAll("[data-session-word-translate-item]")];

    if (items.length === 0) return;

    if (domRows.length === 0) {
      items.forEach((item) => {
        list.appendChild(createWordTranslateRow(item));
      });
      reindexWordTranslateItems(list);
      return;
    }

    if (items.length > domRows.length) {
      for (let index = domRows.length; index < items.length; index += 1) {
        list.appendChild(createWordTranslateRow(items[index]));
      }
      reindexWordTranslateItems(list);
    }

    list.querySelectorAll("[data-session-word-translate-item]").forEach((row, index) => {
      const item = items[index];
      if (!item) return;

      const sourceType = item.source_type === "image" ? "image" : "text";
      const typeSelect = row.querySelector("[data-session-word-translate-source-type]");
      if (typeSelect && typeSelect.value !== sourceType) {
        typeSelect.value = sourceType;
        mountWordTranslateSourceField(row);
      }

      const sourceValue = String(item.source || "").trim();
      const answerValue = String(item.answer || "").trim();

      if (sourceType === "image") {
        const imageInput = row.querySelector("[data-session-word-translate-source-image], .session-pair-image-url");
        if (imageInput && sourceValue !== "" && !String(pairImage?.getValue(imageInput) || "").trim()) {
          pairImage?.setValue?.(imageInput, sourceValue);
        }
      } else {
        const sourceInput = row.querySelector("[data-session-word-translate-source-text]");
        if (sourceInput && sourceValue !== "" && !sourceInput.value.trim()) {
          sourceInput.value = sourceValue;
        }
      }

      const answerInput = row.querySelector("[data-session-word-translate-answer-input]");
      if (answerInput && answerValue !== "" && !answerInput.value.trim()) {
        answerInput.value = answerValue;
      }

      cacheWordTranslateRowDraft(row);
    });
  };

  const bindWordTranslatePanel = () => {
    const typeValue =
      questionItem.querySelector("[data-session-question-type-radio]:checked")?.value || "";

    if (typeValue === "word_translate") {
      ensureWordTranslateDefaultItems();
      restoreWordTranslateRowsFromHiddenJson();
    }

    questionItem.querySelectorAll("[data-session-word-translate-item]").forEach((row) => {
      bindWordTranslateRow(row);
      cacheWordTranslateRowDraft(row);
    });
    ensureWordTranslateSourceFields();

    questionItem.querySelectorAll("[data-session-word-translate-columns-radio], [data-session-word-translate-layout-radio]").forEach((radio) => {
      radio.addEventListener("change", () => {
        syncWordTranslateJson();
        userScheduleAutosave();
      });
    });

    questionItem.querySelector("[data-session-word-translate-add-item]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-word-translate-items-list]");
      if (!list) return;
      const row = createWordTranslateRow();
      list.appendChild(row);
      reindexWordTranslateItems(list);
      syncWordTranslateJson();
      userScheduleAutosave();
      row.querySelector("[data-session-word-translate-source-text]")?.focus();
    });

    syncWordTranslateJson();
  };

  const reindexWordOrderTokens = (list) => {
    list.querySelectorAll("[data-session-word-order-item]").forEach((row, index) => {
      const badge = row.querySelector(".session-word-order-token-index");
      if (badge) badge.textContent = String(index + 1);
    });
  };

  const updateWordOrderPreview = () => {
    const previewBox = questionItem.querySelector("[data-session-word-order-preview]");
    const previewText = questionItem.querySelector("[data-session-word-order-preview-text]");
    const tokens = [];

    questionItem.querySelectorAll("[data-session-word-order-token-text]").forEach((input) => {
      const text = input.value.trim();
      if (text) tokens.push(text);
    });

    if (!previewBox || !previewText) return;

    if (tokens.length === 0) {
      previewBox.hidden = true;
      previewText.textContent = "";
      return;
    }

    previewBox.hidden = false;
    previewText.textContent = tokens.join(" ");
  };

  const syncWordOrderJson = () => {
    const jsonInput = questionItem.querySelector("[data-session-word-order-json]");
    const list = questionItem.querySelector("[data-session-word-order-list]");
    if (!jsonInput || !list) return;

    const tokens = [];
    list.querySelectorAll("[data-session-word-order-item]").forEach((row) => {
      const text = row.querySelector("[data-session-word-order-token-text]")?.value.trim() || "";
      if (text) tokens.push(text);
    });

    jsonInput.value = JSON.stringify(tokens);
    updateWordOrderPreview();
  };

  const bindWordOrderRow = (row) => {
    row.querySelector("[data-session-word-order-token-text]")?.addEventListener("input", () => {
      syncWordOrderJson();
      userScheduleAutosave();
    });

    row.querySelector("[data-session-word-order-remove]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-word-order-list]");
      if ((list?.querySelectorAll("[data-session-word-order-item]").length || 0) <= 2) return;
      row.remove();
      reindexWordOrderTokens(list);
      syncWordOrderJson();
      userScheduleAutosave();
    });

    row.querySelector('[data-session-word-order-move="up"]')?.addEventListener("click", () => {
      const prev = row.previousElementSibling;
      if (!prev) return;
      row.parentElement?.insertBefore(row, prev);
      reindexWordOrderTokens(row.parentElement);
      syncWordOrderJson();
      userScheduleAutosave();
    });

    row.querySelector('[data-session-word-order-move="down"]')?.addEventListener("click", () => {
      const next = row.nextElementSibling;
      if (!next) return;
      row.parentElement?.insertBefore(next, row);
      reindexWordOrderTokens(row.parentElement);
      syncWordOrderJson();
      userScheduleAutosave();
    });
  };

  const createWordOrderRow = () => {
    const li = document.createElement("li");
    li.className = "session-word-order-token-item";
    li.dataset.sessionWordOrderItem = "";
    li.innerHTML =
      `<span class="session-word-order-token-index">1</span>` +
      `<input type="text" class="form-control form-control-sm session-word-order-token-text" data-session-word-order-token-text placeholder="Từ / cụm từ">` +
      `<div class="session-word-order-token-actions">` +
      `<button class="btn sunny-mini btn-outline" type="button" data-session-word-order-move="up" title="Lên"><i class="bi bi-arrow-up"></i></button>` +
      `<button class="btn sunny-mini btn-outline" type="button" data-session-word-order-move="down" title="Xuống"><i class="bi bi-arrow-down"></i></button>` +
      `<button class="btn sunny-mini btn-delete-user" type="button" data-session-word-order-remove title="Xóa"><i class="bi bi-trash"></i></button>` +
      `</div>`;
    return li;
  };

  const bindWordOrderTokens = () => {
    const list = questionItem.querySelector("[data-session-word-order-list]");
    const addInput = questionItem.querySelector("[data-session-word-order-input]");
    const addBtn = questionItem.querySelector("[data-session-word-order-add]");

    list?.querySelectorAll("[data-session-word-order-item]").forEach(bindWordOrderRow);

    const addToken = (rawText, { keepInput = true } = {}) => {
      const text = String(rawText || "").trim();
      if (!text || !list) return;
      const row = createWordOrderRow();
      row.querySelector("[data-session-word-order-token-text]").value = text;
      list.appendChild(row);
      bindWordOrderRow(row);
      reindexWordOrderTokens(list);
      if (addInput && keepInput) addInput.value = "";
      syncWordOrderJson();
      userScheduleAutosave();
    };

    addBtn?.addEventListener("click", () => addToken(addInput?.value || ""));

    window.ProgramInputHelpers?.enhanceWordOrderInput(questionItem, {
      addToken,
      getAddInput: () => addInput,
      scheduleAutosave: userScheduleAutosave,
    });
  };

  const updateMemoryPairPreview = (row) => {
    const imageInput = row.querySelector("[data-session-memory-pair-image]");
    const labelInput = row.querySelector("[data-session-memory-pair-label-input]");
    const preview = row.querySelector("[data-session-memory-pair-preview]");
    if (!preview) return;

    const url = getMemoryPairImageValue(row);
    const label = labelInput?.value.trim() || "";
    const hasUrl = url !== "";
    const previewUrl = hasUrl ? pairImage?.resolveMediaUrl(url) || url : "";

    if (!hasUrl && label === "") {
      preview.hidden = true;
      preview.innerHTML = "";
      return;
    }

    preview.hidden = false;
    preview.innerHTML =
      `<span class="session-memory-pair-preview-card session-memory-pair-preview-card--image${hasUrl ? "" : " is-empty"}">` +
      (hasUrl ? `<img src="${previewUrl.replace(/"/g, "&quot;")}" alt="">` : `<i class="bi bi-image" aria-hidden="true"></i>`) +
      "</span>" +
      `<span class="session-memory-pair-preview-card session-memory-pair-preview-card--text${label ? "" : " is-empty"}">` +
      (label ? label.replace(/</g, "&lt;").replace(/>/g, "&gt;") : "Chữ") +
      "</span>";
  };

  const reindexMemoryPairs = () => {
    const list = questionItem.querySelector("[data-session-memory-pairs-list]");
    if (!list) return;

    list.querySelectorAll("[data-session-memory-pair-item]").forEach((row, index) => {
      const label = row.querySelector("[data-session-memory-pair-label]");
      if (label) label.textContent = `Cặp ${index + 1}`;
    });

    syncPairsJson();
  };

  const bindMemoryPairRow = (row) => {
    initMemoryPairImageEditor(row);

    row.querySelector("[data-session-memory-pair-label-input]")?.addEventListener("input", () => {
      updateMemoryPairPreview(row);
      syncPairsJson();
      userScheduleAutosave();
    });

    row.querySelector(".btn-remove-memory-pair")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-memory-pairs-list]");
      const rows = list?.querySelectorAll("[data-session-memory-pair-item]") || [];
      if (rows.length <= 2) return;
      row.remove();
      reindexMemoryPairs();
      userScheduleAutosave();
    });
  };

  const renderWordLinkPreviewCard = (type, value) => {
    const trimmed = value.trim();
    const isImage = type === "image";

    if (isImage) {
      if (trimmed) {
        const previewUrl = pairImage?.resolveMediaUrl(trimmed) || trimmed;
        return `<img src="${previewUrl.replace(/"/g, "&quot;")}" alt="">`;
      }

      return `<i class="bi bi-image" aria-hidden="true"></i>`;
    }

    return trimmed ? trimmed.replace(/</g, "&lt;").replace(/>/g, "&gt;") : "Chữ";
  };

  const updateWordLinkSlotInput = (row, slot) => {
    const typeSelect = row.querySelector(`[data-session-pair-slot-type="${slot}"]`);
    if (!typeSelect) return;

    const typeValue = typeSelect.value === "text" ? "text" : "image";
    mountWordLinkSlotField(row, slot, typeValue);
  };

  const updateWordLinkPairPreview = (row) => {
    const preview = row.querySelector("[data-session-word-link-pair-preview]");
    const previewA = row.querySelector("[data-session-word-link-preview-a]");
    const previewB = row.querySelector("[data-session-word-link-preview-b]");
    if (!preview || !previewA || !previewB) return;

    const aType = row.querySelector('[data-session-pair-slot-type="a"]')?.value || "image";
    const bType = row.querySelector('[data-session-pair-slot-type="b"]')?.value || "text";
    const aValue = getWordLinkSlotValue(row, "a");
    const bValue = getWordLinkSlotValue(row, "b");

    if (aValue === "" && bValue === "") {
      preview.hidden = true;
      return;
    }

    preview.hidden = false;
    previewA.className = `session-memory-pair-preview-card session-word-link-preview-card session-word-link-preview-card--a${aValue ? "" : " is-empty"}`;
    previewB.className = `session-memory-pair-preview-card session-word-link-preview-card session-word-link-preview-card--b${bValue ? "" : " is-empty"}`;
    previewA.innerHTML = renderWordLinkPreviewCard(aType, aValue);
    previewB.innerHTML = renderWordLinkPreviewCard(bType, bValue);
  };

  const syncWordLinkRowChange = (row) => {
    if (row.closest("[data-session-listening-item-word-link-panel]")) {
      window.ProgramListeningItems?.syncListeningItemsJson?.(questionItem);
    } else {
      syncPairsJson();
    }
    userScheduleAutosave();
  };

  const reindexWordLinkPairsInList = (list) => {
    if (!list) return;

    list.querySelectorAll("[data-session-word-link-pair-item]").forEach((row, index) => {
      const label = row.querySelector("[data-session-word-link-pair-label]");
      if (label) label.textContent = `Cặp ${index + 1}`;
    });

    const firstRow = list.querySelector("[data-session-word-link-pair-item]");
    if (firstRow) {
      syncWordLinkRowChange(firstRow);
    }
  };

  const reindexWordLinkPairs = () => {
    const list = questionItem.querySelector("[data-session-word-link-pairs-list]");
    if (!list) return;
    reindexWordLinkPairsInList(list);
  };

  const bindWordLinkPairRow = (row) => {
    if (row.dataset.wordLinkRowBound === "1") return;
    row.dataset.wordLinkRowBound = "1";

    ["a", "b"].forEach((slot) => {
      row.querySelector(`[data-session-pair-slot-type="${slot}"]`)?.addEventListener("change", () => {
        updateWordLinkSlotInput(row, slot);
        updateWordLinkPairPreview(row);
        syncWordLinkRowChange(row);
      });

      const type = row.querySelector(`[data-session-pair-slot-type="${slot}"]`)?.value || "image";
      if (type === "text") {
        row.querySelector(`[data-session-pair-slot-text-value="${slot}"]`)?.addEventListener("input", () => {
          updateWordLinkPairPreview(row);
          syncWordLinkRowChange(row);
        });
      }
    });

    row.querySelector(".btn-remove-word-link-pair")?.addEventListener("click", () => {
      const list = row.closest("[data-session-word-link-pairs-list], [data-session-listening-item-word-link-list]");
      const rows = list?.querySelectorAll("[data-session-word-link-pair-item]") || [];
      const minRows = list?.matches("[data-session-listening-item-word-link-list]") ? 1 : 2;
      if (rows.length <= minRows) return;
      pairImage?.destroyIn(row);
      row.remove();
      reindexWordLinkPairsInList(list);
      const firstRow = list?.querySelector("[data-session-word-link-pair-item]");
      if (firstRow) syncWordLinkRowChange(firstRow);
    });
  };

  const bindUnboundWordLinkPairRows = () => {
    questionItem.querySelectorAll("[data-session-listening-item-word-link-list] [data-session-word-link-pair-item]").forEach((row) => {
      bindWordLinkPairRow(row);
    });
  };

  const bindWordLinkPairs = () => {
    questionItem.querySelectorAll("[data-session-word-link-pairs-list] [data-session-word-link-pair-item]").forEach((row) => {
      bindWordLinkPairRow(row);
    });

    questionItem.querySelector("[data-add-word-link-pair]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-word-link-pairs-list]");
      if (!list) return;

      ensureMinimumWordLinkPairs(1);

      const templateRow = list.querySelector("[data-session-word-link-pair-item]");
      if (!templateRow) return;

      const row = templateRow.cloneNode(true);
      resetWordLinkPairRow(row);
      list.appendChild(row);
      reindexWordLinkPairs();
      bindWordLinkPairRow(row);
      userScheduleAutosave();
    });
  };

  const bindMemoryPairs = () => {
    questionItem.querySelectorAll("[data-session-memory-pair-item]").forEach(bindMemoryPairRow);

    questionItem.querySelector("[data-add-memory-pair]")?.addEventListener("click", () => {
      const list = questionItem.querySelector("[data-session-memory-pairs-list]");
      if (!list) return;

      ensureMinimumMemoryPairs(1);

      const templateRow = list.querySelector("[data-session-memory-pair-item]");
      if (!templateRow) return;

      const row = templateRow.cloneNode(true);
      resetMemoryPairRow(row);
      list.appendChild(row);
      reindexMemoryPairs();
      bindMemoryPairRow(row);
      initMemoryPairImageEditor(row);
      userScheduleAutosave();
    });
  };

  const richtextSelector = "textarea.richtext-explanation, textarea.richtext-question, textarea.richtext-model-answer";

  const saveEditors = (scope) => {
    if (typeof tinymce === "undefined") return;
    (scope || questionItem).querySelectorAll(richtextSelector).forEach((textarea) => {
      if (textarea.id) tinymce.get(textarea.id)?.save();
    });
  };

  const destroyEditors = (scope) => {
    if (typeof tinymce === "undefined") return;
    (scope || questionItem).querySelectorAll(richtextSelector).forEach((textarea) => {
      if (!textarea.id) return;
      tinymce.get(textarea.id)?.remove();
      delete textarea.dataset.editorReady;
    });
  };

  const cleanupClonedEditors = (scope) => {
    if (!scope) return;
    scope.querySelectorAll(".tox-tinymce").forEach((el) => el.remove());
    scope.querySelectorAll(richtextSelector).forEach((textarea) => {
      delete textarea.dataset.editorReady;
      textarea.removeAttribute("id");
      textarea.style.display = "";
      textarea.removeAttribute("aria-hidden");
    });
  };

  const initEditors = (scope) => {
    if (typeof tinymce === "undefined") return;

    (scope || questionItem).querySelectorAll(richtextSelector).forEach((textarea) => {
      if (textarea.dataset.editorReady === "1" || textarea.closest("template")) return;

      textarea.dataset.editorReady = "1";
      const isExplanation = textarea.classList.contains("richtext-explanation");
      const isModel = textarea.classList.contains("richtext-model-answer");
      const compact = textarea.classList.contains("richtext-compact");
      ensureEditorId(
        textarea,
        isExplanation ? "question-explanation-" : (isModel ? "question-model-" : "question-text-")
      );

      tinymce.init(Object.assign({}, questionEditorConfig, {
        selector: "#" + CSS.escape(textarea.id),
        height: isExplanation ? 220 : (compact ? 180 : (isModel ? 200 : 280)),
        setup(editor) {
          editor.on("init", () => {
            editor._autosaveReady = false;
            window.setTimeout(() => {
              editor._autosaveReady = true;
            }, 1200);
          });
          editor.on("change keyup undo redo", () => {
            if (!editor._autosaveReady) return;
            editor.save();
            userScheduleAutosave();
          });
        },
      }));
    });
  };

  window.ProgramQuestionEditors = {
    init: initEditors,
    save: saveEditors,
    destroy: destroyEditors,
    cleanupClone: cleanupClonedEditors,
  };

  questionItem.addEventListener("change", (event) => {
    if (!event.target.matches("[data-session-question-type-radio]")) return;
    syncQuestionTypeFields();
    if (event.target.value === "word_translate") {
      ensureWordTranslateDefaultItems();
      questionItem.querySelectorAll("[data-session-word-translate-item]").forEach((row) => {
        bindWordTranslateRow(row);
        cacheWordTranslateRowDraft(row);
      });
      ensureWordTranslateSourceFields();
      syncWordTranslateJson();
    }
    initEditors();
    userScheduleAutosave();
  });

  questionItem.addEventListener("listening-bind-word-link-pairs", () => {
    bindUnboundWordLinkPairRows();
    initWordLinkImageEditors();
  });

  questionItem.addEventListener("listening-add-word-link-pair", (event) => {
    const itemRow = event.detail?.itemRow;
    const list = itemRow?.querySelector("[data-session-listening-item-word-link-list]");
    const template = list?.querySelector("[data-session-word-link-pair-item]");
    if (!list || !template) return;

    const pairRow = template.cloneNode(true);
    pairRow.dataset.wordLinkRowBound = "";
    resetWordLinkPairRow(pairRow);
    list.appendChild(pairRow);
    bindWordLinkPairRow(pairRow);
    reindexWordLinkPairsInList(list);
    initWordLinkImageEditors();
    window.ProgramListeningItems?.syncListeningItemsJson?.(questionItem);
    userScheduleAutosave();
  });

  bindMemoryPairs();
  bindWordLinkPairs();
  bindWordOrderTokens();
  bindListeningPanel();
  bindListeningTrueFalsePanel();
  window.ProgramListeningMultiChoice?.bindIn?.(questionItem, { scheduleAutosave: userScheduleAutosave });
  bindCategorySortPanel();
  bindFillBlanksPanel();
  bindWordSelectPanel();
  bindWordTranslatePanel();
  syncQuestionTypeFields();
  bindPhatAmPanel();
  bindListeningItemsPanel();
  syncListeningItemsPanelState(
    (questionItem.querySelector("[data-session-question-type-radio]:checked")?.value || "choice") === "listening"
  );
  window.ProgramImageHotspotPanel?.bind?.(questionItem, { scheduleAutosave: userScheduleAutosave });
  window.ProgramImageLabelDropPanel?.bind?.(questionItem, { scheduleAutosave: userScheduleAutosave });
  window.ProgramInputHelpers?.bindQuestionInputEnhancements?.(questionItem, { scheduleAutosave: userScheduleAutosave });
  window.ProgramInputHelpers?.focusQuestionEditor?.(questionItem);
  window.ProgramInputHelpers?.bindFormShortcuts?.(questionForm, {
    prevLink: questionForm.dataset.prevQuestionUrl || "",
    nextLink: questionForm.dataset.nextQuestionUrl || "",
  });
  initEditors();
  ensureMemoryPairEditors();
  ensureWordLinkPairFields();

  questionForm.addEventListener("input", userScheduleAutosave);
  questionForm.addEventListener("change", userScheduleAutosave);
  window.ProgramWordLinkPairHelpers = {
    resetRow: resetWordLinkPairRow,
    bindRow: bindWordLinkPairRow,
    reindexList: reindexWordLinkPairsInList,
    initImageEditors: initWordLinkImageEditors,
  };

  questionForm.addEventListener("submit", () => {
    if (typeof tinymce !== "undefined") tinymce.triggerSave();
    pairImage?.saveAll(questionItem);
    syncPairsJson();
    syncWordOrderJson();
    syncCategorySortJson();
    syncFillBlanksJson();
    syncWordSelectJson();
    syncWordTranslateJson();
    window.ProgramPhatAmItems?.syncIn?.(questionItem);
    window.ProgramListeningItems?.syncIn?.(questionItem);
    syncListeningTrueFalseJson();
    window.ProgramListeningMultiChoice?.syncIn?.(questionItem);
    window.ProgramImageHotspotPanel?.sync?.(questionItem);
    window.ProgramImageLabelDropPanel?.sync?.(questionItem);
  });
})();
