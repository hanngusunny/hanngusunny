(() => {
  const boards = document.querySelectorAll("[data-session-word-order-board]");

  if (!boards.length) return;

  const usePointerDrag = window.matchMedia("(hover: none), (pointer: coarse)").matches;

  const preventChipCopy = (chip) => {
    chip.addEventListener("selectstart", (event) => event.preventDefault());
    chip.addEventListener("contextmenu", (event) => event.preventDefault());
    chip.addEventListener("copy", (event) => event.preventDefault());
    chip.addEventListener("dragstart", (event) => {
      if (usePointerDrag) event.preventDefault();
    });
  };

  const shuffle = (items) => {
    const list = items.slice();
    for (let i = list.length - 1; i > 0; i -= 1) {
      const j = Math.floor(Math.random() * (i + 1));
      [list[i], list[j]] = [list[j], list[i]];
    }
    return list;
  };

  const escapeHtml = (value) =>
    String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  const createChip = (token, zone) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "session-word-order-chip";
    btn.dataset.tokenId = token.id;
    btn.dataset.zone = zone;
    btn.draggable = !usePointerDrag;
    btn.innerHTML =
      `<span class="session-word-order-chip-text">${escapeHtml(token.text)}</span>` +
      `<span class="session-word-order-chip-grip" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>`;
    preventChipCopy(btn);
    return btn;
  };

  const getInsertBeforeChip = (container, clientX) => {
    const chips = [...container.querySelectorAll(".session-word-order-chip:not(.is-dragging)")];

    return chips.reduce(
      (closest, chip) => {
        const box = chip.getBoundingClientRect();
        const offset = clientX - box.left - box.width / 2;

        if (offset < 0 && offset > closest.offset) {
          return { offset, element: chip };
        }

        return closest;
      },
      { offset: Number.NEGATIVE_INFINITY, element: null }
    ).element;
  };

  boards.forEach((board) => {
    const sentenceZone = board.querySelector("[data-word-order-sentence]");
    const bankZone = board.querySelector("[data-word-order-bank]");
    const status = board.querySelector("[data-word-order-status]");
    const completeInput = board.querySelector("[data-word-order-complete-input]");
    const progress = board.querySelector("[data-word-order-progress]");
    const progressFill = board.querySelector("[data-word-order-progress-fill]");
    const liveWrap = board.querySelector("[data-word-order-live-wrap]");
    const liveText = board.querySelector("[data-word-order-live-text]");

    let tokens = [];
    try {
      tokens = JSON.parse(board.dataset.wordOrderTokens || "[]");
    } catch (error) {
      tokens = [];
    }

    tokens = tokens.filter((token) => String(token.text || "").trim() !== "");

    if (!sentenceZone || !bankZone || tokens.length < 2) {
      if (status) status.textContent = "Câu sắp xếp chưa được cấu hình đủ từ (cần ít nhất 2).";
      return;
    }

    const tokenMap = Object.fromEntries(tokens.map((token) => [String(token.id), token]));
    let dragId = null;
    let activePointerId = null;
    let activeChip = null;
    let pointerStartX = 0;
    let pointerStartY = 0;
    let pointerMoved = false;

    const updateLivePreview = () => {
      const words = [...sentenceZone.querySelectorAll(".session-word-order-chip-text")]
        .map((el) => el.textContent.trim())
        .filter(Boolean);
      const sentence = words.join(" ");

      if (liveText) liveText.textContent = sentence;
      if (liveWrap) liveWrap.classList.toggle("is-empty", words.length === 0);
    };

    const updateProgress = () => {
      const total = tokens.length;
      const placed = sentenceZone.querySelectorAll(".session-word-order-chip").length;
      const pct = total > 0 ? Math.round((placed / total) * 100) : 0;

      if (progressFill) progressFill.style.width = `${pct}%`;
      if (progress) progress.textContent = `${placed}/${total} từ`;
    };

    const syncInput = () => {
      const order = [...sentenceZone.querySelectorAll(".session-word-order-chip")].map((chip) => chip.dataset.tokenId);
      const allPlaced = order.length === tokens.length;

      if (completeInput) {
        completeInput.value = order.length > 0
          ? JSON.stringify({ status: allPlaced ? "completed" : "in_progress", order })
          : "";
        completeInput.dispatchEvent(new Event("input", { bubbles: true }));
      }

      if (status) {
        if (allPlaced) {
          status.textContent = "Đã xếp đủ từ — bạn có thể nộp bài.";
          status.classList.add("is-complete");
        } else {
          status.textContent = "Kéo thả hoặc bấm từ để ghép thành câu hoàn chỉnh.";
          status.classList.remove("is-complete");
        }
      }

      sentenceZone.classList.toggle("is-empty", order.length === 0);
      bankZone.classList.toggle("is-empty", bankZone.querySelectorAll(".session-word-order-chip").length === 0);
      updateProgress();
      updateLivePreview();
    };

    const placeChipInZone = (chip, zoneEl, zoneName, clientX = null) => {
      chip.dataset.zone = zoneName;

      if (zoneName === "sentence" && clientX !== null) {
        const insertBefore = getInsertBeforeChip(zoneEl, clientX);

        if (insertBefore) {
          zoneEl.insertBefore(chip, insertBefore);
        } else {
          zoneEl.appendChild(chip);
        }
      } else {
        zoneEl.appendChild(chip);
      }

      syncInput();
    };

    const moveChipToZone = (chip, zoneEl, zoneName, clientX = null) => {
      placeChipInZone(chip, zoneEl, zoneName, clientX);
    };

    const clearDropTargets = () => {
      sentenceZone.classList.remove("is-drop-target");
      bankZone.classList.remove("is-drop-target");
    };

    const highlightDropTargetAt = (clientX, clientY) => {
      clearDropTargets();
      const target = document.elementFromPoint(clientX, clientY);
      const sentence = target?.closest("[data-word-order-sentence]");
      const bank = target?.closest("[data-word-order-bank]");

      if (sentence && board.contains(sentence)) {
        sentence.classList.add("is-drop-target");
      } else if (bank && board.contains(bank)) {
        bank.classList.add("is-drop-target");
      }
    };

    const dropChipAtPoint = (chip, clientX, clientY) => {
      const target = document.elementFromPoint(clientX, clientY);
      const sentence = target?.closest("[data-word-order-sentence]");
      const bank = target?.closest("[data-word-order-bank]");

      if (sentence && board.contains(sentence)) {
        moveChipToZone(chip, sentenceZone, "sentence", clientX);
        return;
      }

      if (bank && board.contains(bank)) {
        moveChipToZone(chip, bankZone, "bank");
        return;
      }

      chip.classList.remove("is-dragging");
    };

    const toggleChipZone = (chip) => {
      if (chip.dataset.zone === "bank") {
        moveChipToZone(chip, sentenceZone, "sentence");
      } else {
        moveChipToZone(chip, bankZone, "bank");
      }
    };

    const finishPointerDrag = (event) => {
      if (activePointerId === null || activeChip === null) return;
      if (event.pointerId !== activePointerId) return;

      if (activeChip.hasPointerCapture(activePointerId)) {
        activeChip.releasePointerCapture(activePointerId);
      }

      const moved =
        pointerMoved ||
        Math.hypot(event.clientX - pointerStartX, event.clientY - pointerStartY) >= 8;

      if (moved) {
        dropChipAtPoint(activeChip, event.clientX, event.clientY);
      } else {
        activeChip.classList.remove("is-dragging");
        toggleChipZone(activeChip);
      }

      clearDropTargets();
      activePointerId = null;
      activeChip = null;
      dragId = null;
      pointerMoved = false;
    };

    const bindChip = (chip) => {
      if (usePointerDrag) {
        chip.addEventListener("pointerdown", (event) => {
          if (event.pointerType === "mouse" && event.button !== 0) return;

          event.preventDefault();
          activePointerId = event.pointerId;
          activeChip = chip;
          dragId = chip.dataset.tokenId || null;
          pointerStartX = event.clientX;
          pointerStartY = event.clientY;
          pointerMoved = false;
          chip.classList.add("is-dragging");
          chip.setPointerCapture(activePointerId);
        });

        chip.addEventListener("pointermove", (event) => {
          if (activePointerId !== event.pointerId) return;
          event.preventDefault();
          if (Math.hypot(event.clientX - pointerStartX, event.clientY - pointerStartY) >= 8) {
            pointerMoved = true;
          }
          highlightDropTargetAt(event.clientX, event.clientY);
        });

        chip.addEventListener("pointerup", finishPointerDrag);
        chip.addEventListener("pointercancel", finishPointerDrag);
        return;
      }

      chip.addEventListener("click", () => {
        toggleChipZone(chip);
      });

      chip.addEventListener("dragstart", (event) => {
        dragId = chip.dataset.tokenId;
        chip.classList.add("is-dragging");
        event.dataTransfer?.setData("text/plain", dragId);
        event.dataTransfer.effectAllowed = "move";
      });

      chip.addEventListener("dragend", () => {
        chip.classList.remove("is-dragging");
        dragId = null;
        clearDropTargets();
      });
    };

    const setupDropZone = (zoneEl, zoneName) => {
      if (usePointerDrag) return;

      zoneEl.addEventListener("dragover", (event) => {
        event.preventDefault();
        zoneEl.classList.add("is-drop-target");
      });

      zoneEl.addEventListener("dragleave", () => zoneEl.classList.remove("is-drop-target"));

      zoneEl.addEventListener("drop", (event) => {
        event.preventDefault();
        zoneEl.classList.remove("is-drop-target");
        const id = dragId || event.dataTransfer?.getData("text/plain");
        if (!id) return;

        const chip = board.querySelector(`.session-word-order-chip[data-token-id="${CSS.escape(id)}"]`);
        if (!chip) return;

        moveChipToZone(chip, zoneEl, zoneName, zoneName === "sentence" ? event.clientX : null);
      });
    };

    setupDropZone(sentenceZone, "sentence");
    setupDropZone(bankZone, "bank");

    shuffle(tokens).forEach((token) => {
      const chip = createChip(token, "bank");
      bindChip(chip);
      bankZone.appendChild(chip);
    });

    try {
      const saved = JSON.parse(completeInput?.value || "");
      const savedOrder = Array.isArray(saved?.order) ? saved.order.map(String) : [];

      savedOrder.forEach((id) => {
        const chip = board.querySelector(`.session-word-order-chip[data-token-id="${CSS.escape(id)}"]`);

        if (!chip) {
          return;
        }

        chip.dataset.zone = "sentence";
        sentenceZone.appendChild(chip);
      });
    } catch (_error) {
      /* ignore invalid draft */
    }

    board.querySelector("[data-word-order-reset]")?.addEventListener("click", () => {
      sentenceZone.innerHTML = "";
      bankZone.innerHTML = "";
      shuffle(tokens).forEach((token) => {
        const chip = createChip(token, "bank");
        bindChip(chip);
        bankZone.appendChild(chip);
      });
      syncInput();
    });

    syncInput();
  });
})();
