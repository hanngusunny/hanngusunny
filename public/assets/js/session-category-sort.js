(() => {
  const boards = document.querySelectorAll("[data-session-category-sort-board]");

  if (!boards.length) return;

  const preventChipCopy = (chip) => {
    chip.addEventListener("selectstart", (event) => event.preventDefault());
    chip.addEventListener("contextmenu", (event) => event.preventDefault());
    chip.addEventListener("copy", (event) => event.preventDefault());
    chip.addEventListener("dragstart", (event) => event.preventDefault());
  };

  const shuffle = (list) => {
    const items = list.slice();
    for (let i = items.length - 1; i > 0; i -= 1) {
      const j = Math.floor(Math.random() * (i + 1));
      [items[i], items[j]] = [items[j], items[i]];
    }
    return items;
  };

  const escapeHtml = (value) =>
    String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  const createChip = (item) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "session-category-sort-chip";
    btn.dataset.itemId = item.id;
    btn.draggable = false;
    btn.innerHTML =
      `<span class="session-category-sort-chip-text">${escapeHtml(item.text)}</span>` +
      `<span class="session-category-sort-chip-grip" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>`;
    preventChipCopy(btn);
    return btn;
  };

  boards.forEach((board) => {
    const bank = board.querySelector("[data-category-sort-bank]");
    const status = board.querySelector("[data-category-sort-status]");
    const completeInput = board.querySelector("[data-category-sort-complete-input]");
    const progress = board.querySelector("[data-category-sort-progress]");
    const progressFill = board.querySelector("[data-category-sort-progress-fill]");

    let groups = [];
    let items = [];

    try {
      groups = JSON.parse(board.dataset.categorySortGroups || "[]");
      items = JSON.parse(board.dataset.categorySortItems || "[]");
    } catch (error) {
      groups = [];
      items = [];
    }

    items = items.filter((item) => String(item.text || "").trim() !== "");
    groups = groups.filter((group) => String(group.label || "").trim() !== "");

    if (!bank || groups.length < 2 || items.length < 2) {
      if (status) status.textContent = "Trò phân loại chưa được cấu hình đủ nhóm và thẻ.";
      return;
    }

    let activePointerId = null;
    let activeChip = null;
    let pointerStartX = 0;
    let pointerStartY = 0;
    let pointerMoved = false;
    let ghostEl = null;

    const dropzones = () => [bank, ...board.querySelectorAll("[data-category-sort-dropzone]")];

    const updateProgress = () => {
      const total = items.length;
      const placed = total - bank.querySelectorAll(".session-category-sort-chip").length;
      const pct = total > 0 ? Math.round((placed / total) * 100) : 0;
      if (progressFill) progressFill.style.width = `${pct}%`;
      if (progress) progress.textContent = `${placed}/${total} thẻ`;
    };

    const collectAssignments = () => {
      const assignments = [];

      board.querySelectorAll("[data-category-sort-dropzone]").forEach((zone) => {
        const groupId = zone.closest("[data-category-sort-column]")?.dataset.categorySortGroupId || "";

        zone.querySelectorAll(".session-category-sort-chip").forEach((chip) => {
          assignments.push({
            item_id: chip.dataset.itemId,
            group_id: groupId,
          });
        });
      });

      return assignments;
    };

    const syncInput = () => {
      const assignments = collectAssignments();
      const bankCount = bank.querySelectorAll(".session-category-sort-chip").length;
      const allPlaced = bankCount === 0 && assignments.length === items.length;

      if (completeInput) {
        completeInput.value = assignments.length > 0
          ? JSON.stringify({ status: allPlaced ? "completed" : "in_progress", assignments })
          : "";
        completeInput.dispatchEvent(new Event("input", { bubbles: true }));
      }

      if (status) {
        if (allPlaced) {
          status.textContent = "Đã phân loại đủ thẻ — bạn có thể nộp bài.";
          status.classList.add("is-complete");
        } else {
          status.textContent = "Kéo thả từng thẻ vào đúng nhóm bên dưới.";
          status.classList.remove("is-complete");
        }
      }

      bank.classList.toggle("is-empty", bankCount === 0);
      updateProgress();
    };

    const clearDropTargets = () => {
      dropzones().forEach((zone) => zone?.classList.remove("is-drop-target"));
    };

    const targetAtPoint = (clientX, clientY) => {
      const target = document.elementFromPoint(clientX, clientY);
      const zone = target?.closest("[data-category-sort-dropzone]");
      const bankTarget = target?.closest("[data-category-sort-bank]");

      if (zone && board.contains(zone)) {
        return zone;
      }

      if (bankTarget && board.contains(bankTarget)) {
        return bank;
      }

      return null;
    };

    const highlightDropTargetAt = (clientX, clientY) => {
      clearDropTargets();
      const zone = targetAtPoint(clientX, clientY);
      zone?.classList.add("is-drop-target");
    };

    const removeGhost = () => {
      ghostEl?.remove();
      ghostEl = null;
    };

    const startGhost = (chip, clientX, clientY) => {
      removeGhost();
      const clone = chip.cloneNode(true);
      clone.removeAttribute("data-item-id");
      clone.classList.add("is-ghost");
      clone.setAttribute("aria-hidden", "true");
      clone.style.position = "fixed";
      clone.style.left = `${clientX + 10}px`;
      clone.style.top = `${clientY + 10}px`;
      clone.style.zIndex = "12000";
      clone.style.pointerEvents = "none";
      clone.style.margin = "0";
      document.body.appendChild(clone);
      ghostEl = clone;
    };

    const moveGhost = (clientX, clientY) => {
      if (!ghostEl) return;
      ghostEl.style.left = `${clientX + 10}px`;
      ghostEl.style.top = `${clientY + 10}px`;
    };

    const dropChipAtPoint = (chip, clientX, clientY) => {
      const zone = targetAtPoint(clientX, clientY);

      if (zone) {
        zone.appendChild(chip);
        syncInput();
      }

      chip.classList.remove("is-dragging");
      chip.style.pointerEvents = "";
    };

    const returnChipToBankIfPlaced = (chip) => {
      if (chip.parentElement?.matches("[data-category-sort-dropzone]")) {
        bank.appendChild(chip);
        syncInput();
      }
    };

    const stopDocDrag = () => {
      document.removeEventListener("pointermove", onDocPointerMove);
      document.removeEventListener("pointerup", onDocPointerUp);
      document.removeEventListener("pointercancel", onDocPointerUp);
    };

    const onDocPointerMove = (event) => {
      if (activePointerId === null || event.pointerId !== activePointerId || !activeChip) return;
      event.preventDefault();
      if (Math.hypot(event.clientX - pointerStartX, event.clientY - pointerStartY) >= 6) {
        pointerMoved = true;
        if (!ghostEl) startGhost(activeChip, event.clientX, event.clientY);
      }
      moveGhost(event.clientX, event.clientY);
      highlightDropTargetAt(event.clientX, event.clientY);
    };

    const onDocPointerUp = (event) => {
      if (activePointerId === null || event.pointerId !== activePointerId || !activeChip) return;

      if (activeChip.hasPointerCapture?.(activePointerId)) {
        try {
          activeChip.releasePointerCapture(activePointerId);
        } catch (_error) {
          /* already released */
        }
      }

      const moved =
        pointerMoved ||
        Math.hypot(event.clientX - pointerStartX, event.clientY - pointerStartY) >= 6;

      if (moved) {
        dropChipAtPoint(activeChip, event.clientX, event.clientY);
      } else {
        activeChip.classList.remove("is-dragging");
        activeChip.style.pointerEvents = "";
        returnChipToBankIfPlaced(activeChip);
      }

      removeGhost();
      clearDropTargets();
      stopDocDrag();
      activePointerId = null;
      activeChip = null;
      pointerMoved = false;
    };

    const bindChip = (chip) => {
      chip.addEventListener("pointerdown", (event) => {
        if (event.pointerType === "mouse" && event.button !== 0) return;

        event.preventDefault();
        activePointerId = event.pointerId;
        activeChip = chip;
        pointerStartX = event.clientX;
        pointerStartY = event.clientY;
        pointerMoved = false;
        chip.classList.add("is-dragging");
        chip.style.pointerEvents = "none";

        try {
          chip.setPointerCapture(event.pointerId);
        } catch (_error) {
          /* capture is optional; document listeners still work */
        }

        document.addEventListener("pointermove", onDocPointerMove, { passive: false });
        document.addEventListener("pointerup", onDocPointerUp);
        document.addEventListener("pointercancel", onDocPointerUp);
      });
    };

    shuffle(items).forEach((item) => {
      const chip = createChip(item);
      bindChip(chip);
      bank.appendChild(chip);
    });

    try {
      const saved = JSON.parse(completeInput?.value || "");
      const savedAssignments = Array.isArray(saved?.assignments) ? saved.assignments : [];

      savedAssignments.forEach((row) => {
        const itemId = String(row?.item_id || "");
        const groupId = String(row?.group_id || "");
        const chip = itemId
          ? board.querySelector(`.session-category-sort-chip[data-item-id="${CSS.escape(itemId)}"]`)
          : null;
        const zone = groupId
          ? board.querySelector(
              `[data-category-sort-column][data-category-sort-group-id="${CSS.escape(groupId)}"] [data-category-sort-dropzone]`
            )
          : null;

        if (chip && zone) {
          zone.appendChild(chip);
        }
      });
    } catch (_error) {
      /* ignore invalid draft */
    }

    board.querySelector("[data-category-sort-reset]")?.addEventListener("click", () => {
      board.querySelectorAll("[data-category-sort-dropzone]").forEach((zone) => {
        zone.innerHTML = "";
      });
      bank.innerHTML = "";
      shuffle(items).forEach((item) => {
        const chip = createChip(item);
        bindChip(chip);
        bank.appendChild(chip);
      });
      syncInput();
    });

    syncInput();
  });
})();
