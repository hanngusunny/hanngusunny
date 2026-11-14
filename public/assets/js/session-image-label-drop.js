(() => {
  const boards = document.querySelectorAll("[data-session-image-label-drop-board]");

  if (!boards.length) return;

  const usePointerDrag = window.matchMedia("(hover: none), (pointer: coarse)").matches;

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

  const preventLabelCopy = (chip) => {
    chip.addEventListener("selectstart", (event) => event.preventDefault());
    chip.addEventListener("contextmenu", (event) => event.preventDefault());
    chip.addEventListener("copy", (event) => event.preventDefault());
    chip.addEventListener("dragstart", (event) => {
      if (usePointerDrag) event.preventDefault();
    });
  };

  boards.forEach((board) => {
    const bank = board.querySelector("[data-image-label-drop-bank]");
    const overlay = board.querySelector("[data-image-label-drop-overlay]");
    const status = board.querySelector("[data-image-label-drop-status]");
    const completeInput = board.querySelector("[data-image-label-drop-complete-input]");
    const resetBtn = board.querySelector("[data-image-label-drop-reset]");

    let zones = [];
    let labels = [];

    try {
      zones = JSON.parse(board.dataset.imageLabelDropZones || "[]");
      labels = JSON.parse(board.dataset.imageLabelDropLabels || "[]");
    } catch (_error) {
      zones = [];
      labels = [];
    }

    labels = labels.filter((label) => String(label.text || "").trim() !== "");
    zones = zones.filter((zone) => String(zone.id || "").trim() !== "");

    if (!bank || !overlay || labels.length < 1 || zones.length < 1) {
      if (status) status.textContent = "Câu hỏi kéo nhãn chưa được cấu hình đủ vùng và nhãn.";
      return;
    }

    let dragId = null;
    let activePointerId = null;
    let activeChip = null;

    const zoneElements = () => [...overlay.querySelectorAll("[data-image-label-drop-zone]")];

    const clearDropTargets = () => {
      zoneElements().forEach((zone) => zone.classList.remove("is-drop-target"));
      bank.classList.remove("is-drop-target");
    };

    const highlightDropTargetAt = (clientX, clientY) => {
      clearDropTargets();
      const target = document.elementFromPoint(clientX, clientY);
      const zone = target?.closest("[data-image-label-drop-zone]");
      const bankTarget = target?.closest("[data-image-label-drop-bank]");

      if (zone && board.contains(zone)) {
        zone.classList.add("is-drop-target");
      } else if (bankTarget && board.contains(bankTarget)) {
        bankTarget.classList.add("is-drop-target");
      }
    };

    const dropChipToZone = (chip, zoneEl) => {
      const existingChip = zoneEl.querySelector(".session-image-label-drop-chip");
      if (existingChip && existingChip !== chip) {
        bank.appendChild(existingChip);
      }

      zoneEl.appendChild(chip);
      chip.classList.remove("is-dragging");
    };

    const dropChipToBank = (chip) => {
      bank.appendChild(chip);
      chip.classList.remove("is-dragging");
    };

    const dropChipAtPoint = (chip, clientX, clientY) => {
      const target = document.elementFromPoint(clientX, clientY);
      const zone = target?.closest("[data-image-label-drop-zone]");
      const bankTarget = target?.closest("[data-image-label-drop-bank]");

      if (zone && board.contains(zone)) {
        dropChipToZone(chip, zone);
        return;
      }

      if (bankTarget && board.contains(bankTarget)) {
        dropChipToBank(chip);
        return;
      }

      chip.classList.remove("is-dragging");
    };

    const finishPointerDrag = (event) => {
      if (activePointerId === null || activeChip === null) return;
      if (event.pointerId !== activePointerId) return;

      if (activeChip.hasPointerCapture(activePointerId)) {
        activeChip.releasePointerCapture(activePointerId);
      }

      dropChipAtPoint(activeChip, event.clientX, event.clientY);
      clearDropTargets();

      activePointerId = null;
      activeChip = null;
      dragId = null;
    };

    const createChip = (label) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "session-image-label-drop-chip";
      btn.dataset.labelId = label.id;
      btn.draggable = !usePointerDrag;
      btn.innerHTML =
        `<span class="session-image-label-drop-chip-text">${escapeHtml(label.text)}</span>` +
        `<span class="session-image-label-drop-chip-grip" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>`;
      return btn;
    };

    const renderZones = () => {
      const radius = Number(board.dataset.zoneRadius || 10);
      overlay.innerHTML = "";

      zones.forEach((zone, index) => {
        const dropzone = document.createElement("div");
        dropzone.className = "session-image-label-drop-zone";
        dropzone.dataset.imageLabelDropZone = "";
        dropzone.dataset.zoneId = zone.id;
        dropzone.style.left = `${zone.x}%`;
        dropzone.style.top = `${zone.y}%`;
        dropzone.style.setProperty("--zone-radius", `${radius}%`);
        dropzone.innerHTML = `<span class="session-image-label-drop-zone-no">${index + 1}</span>`;
        overlay.appendChild(dropzone);
      });
    };

    const collectAssignments = () => {
      const assignments = [];

      zoneElements().forEach((zoneEl) => {
        const zoneId = zoneEl.dataset.zoneId || "";
        const chip = zoneEl.querySelector(".session-image-label-drop-chip");

        if (chip) {
          assignments.push({
            label_id: chip.dataset.labelId,
            zone_id: zoneId,
          });
        }
      });

      return assignments;
    };

    const syncInput = () => {
      const assignments = collectAssignments();
      const bankCount = bank.querySelectorAll(".session-image-label-drop-chip").length;
      const allPlaced = bankCount === 0 && assignments.length === labels.length;

      if (completeInput) {
        completeInput.value = assignments.length > 0
          ? JSON.stringify({ status: allPlaced ? "completed" : "in_progress", assignments })
          : "";
        completeInput.dispatchEvent(new Event("input", { bubbles: true }));
      }

      if (status) {
        if (allPlaced) {
          status.textContent = "Đã kéo đủ nhãn vào vùng trên ảnh — bạn có thể nộp bài.";
          status.classList.add("is-complete");
        } else {
          status.textContent = `Kéo từng nhãn vào đúng vùng trên ảnh (${assignments.length}/${labels.length}).`;
          status.classList.remove("is-complete");
        }
      }

      bank.classList.toggle("is-empty", bankCount === 0);
    };

    const bindChip = (chip) => {
      preventLabelCopy(chip);

      chip.addEventListener("click", () => {
        if (chip.parentElement?.matches("[data-image-label-drop-zone]")) {
          bank.appendChild(chip);
          syncInput();
        }
      });

      if (usePointerDrag) {
        chip.addEventListener("pointerdown", (event) => {
          if (event.pointerType === "mouse" && event.button !== 0) return;

          event.preventDefault();
          activePointerId = event.pointerId;
          activeChip = chip;
          dragId = chip.dataset.labelId || null;
          chip.classList.add("is-dragging");
          chip.setPointerCapture(activePointerId);
        });

        chip.addEventListener("pointermove", (event) => {
          if (activePointerId !== event.pointerId) return;
          event.preventDefault();
          highlightDropTargetAt(event.clientX, event.clientY);
        });

        chip.addEventListener("pointerup", (event) => {
          finishPointerDrag(event);
          syncInput();
        });

        chip.addEventListener("pointercancel", (event) => {
          finishPointerDrag(event);
          syncInput();
        });

        return;
      }

      chip.addEventListener("dragstart", (event) => {
        dragId = chip.dataset.labelId;
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

    const setupDropZone = (zoneEl) => {
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

        const chip = board.querySelector(`.session-image-label-drop-chip[data-label-id="${CSS.escape(id)}"]`);
        if (!chip) return;

        dropChipToZone(chip, zoneEl);
        syncInput();
      });
    };

    const setupBankDrop = () => {
      if (usePointerDrag) return;

      bank.addEventListener("dragover", (event) => {
        event.preventDefault();
        bank.classList.add("is-drop-target");
      });

      bank.addEventListener("dragleave", () => bank.classList.remove("is-drop-target"));

      bank.addEventListener("drop", (event) => {
        event.preventDefault();
        bank.classList.remove("is-drop-target");
        const id = dragId || event.dataTransfer?.getData("text/plain");
        if (!id) return;

        const chip = board.querySelector(`.session-image-label-drop-chip[data-label-id="${CSS.escape(id)}"]`);
        if (chip) {
          dropChipToBank(chip);
          syncInput();
        }
      });
    };

    renderZones();
    zoneElements().forEach(setupDropZone);
    setupBankDrop();

    shuffle(labels).forEach((label) => {
      const chip = createChip(label);
      bindChip(chip);
      bank.appendChild(chip);
    });

    try {
      const saved = JSON.parse(completeInput?.value || "");
      const savedAssignments = Array.isArray(saved?.assignments) ? saved.assignments : [];

      savedAssignments.forEach((row) => {
        const labelId = String(row?.label_id || "");
        const zoneId = String(row?.zone_id || "");
        const chip = labelId
          ? board.querySelector(`.session-image-label-drop-chip[data-label-id="${CSS.escape(labelId)}"]`)
          : null;
        const zone = zoneId
          ? overlay.querySelector(`[data-image-label-drop-zone][data-zone-id="${CSS.escape(zoneId)}"]`)
          : null;

        if (chip && zone) {
          dropChipToZone(chip, zone);
        }
      });
    } catch (_error) {
      /* ignore invalid draft */
    }

    resetBtn?.addEventListener("click", () => {
      zoneElements().forEach((zone) => {
        const chip = zone.querySelector(".session-image-label-drop-chip");
        if (chip) bank.appendChild(chip);
      });
      bank.innerHTML = "";
      shuffle(labels).forEach((label) => {
        const chip = createChip(label);
        bindChip(chip);
        bank.appendChild(chip);
      });
      syncInput();
    });

    board.closest("form")?.addEventListener("submit", syncInput);
    syncInput();
  });
})();
