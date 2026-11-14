(() => {
  const boards = document.querySelectorAll("[data-session-word-select-board]");

  if (!boards.length) return;

  boards.forEach((board) => {
    const completeInput = board.querySelector("[data-word-select-complete-input]");
    const status = board.querySelector("[data-word-select-status]");
    const slots = [...board.querySelectorAll("[data-word-select-slot]")];
    const chips = [...board.querySelectorAll("[data-word-select-chip]")];
    const dropdowns = [...board.querySelectorAll("[data-word-select-dropdown]")];

    if (!completeInput || slots.length === 0) return;

    let activeIndex = 0;

    const applySavedBlanks = () => {
      try {
        const saved = JSON.parse(completeInput.value || "");
        const blanks = Array.isArray(saved?.blanks) ? saved.blanks : [];

        blanks.forEach((value, index) => {
          const hidden = slots[index]?.querySelector("[data-word-select-value]");

          if (hidden && typeof value === "string" && value.trim() !== "" && !hidden.value) {
            hidden.value = value.trim();
          }
        });
      } catch (_error) {
        /* ignore invalid draft */
      }
    };

    const getValues = () =>
      slots.map((slot) => {
        const hidden = slot.querySelector("[data-word-select-value]");
        return (hidden?.value || "").trim();
      });

    const updateChipStates = () => {
      const usedCounts = new Map();
      getValues().forEach((value) => {
        if (!value) return;
        usedCounts.set(value, (usedCounts.get(value) || 0) + 1);
      });

      const bankCounts = new Map();
      chips.forEach((chip) => {
        const word = chip.dataset.word || "";
        bankCounts.set(word, (bankCounts.get(word) || 0) + 1);
      });

      chips.forEach((chip) => {
        const word = chip.dataset.word || "";
        const used = usedCounts.get(word) || 0;
        const total = bankCounts.get(word) || 0;
        chip.disabled = used >= total;
        chip.classList.toggle("is-used", used >= total);
      });
    };

    const renderSlot = (slot, value) => {
      const btn = slot.querySelector("[data-word-select-slot-btn]");
      const hidden = slot.querySelector("[data-word-select-value]");
      const dropdown = slot.querySelector("[data-word-select-dropdown]");
      const index = Number(slot.dataset.blankIndex || 0);

      if (hidden) hidden.value = value;
      if (btn) {
        btn.textContent = value || "Chọn…";
        btn.classList.toggle("is-filled", value !== "");
      }
      if (dropdown && dropdown.value !== value) {
        dropdown.value = value;
      }

      slot.classList.toggle("is-filled", value !== "");
      slot.classList.toggle("is-active", index === activeIndex);
    };

    const sync = () => {
      const blanks = getValues();
      const filled = blanks.filter((value) => value !== "").length;
      const allFilled = filled === slots.length && slots.length > 0;

      completeInput.value = filled > 0 ? JSON.stringify({ blanks }) : "";
      completeInput.dispatchEvent(new Event("input", { bubbles: true }));

      if (status) {
        if (allFilled) {
          status.textContent = "Đã chọn đủ từ — bạn có thể nộp bài.";
          status.classList.add("is-complete");
        } else {
          status.textContent = `Đã chọn ${filled}/${slots.length} ô — bấm ô trống rồi chọn từ, hoặc dùng menu.`;
          status.classList.remove("is-complete");
        }
      }

      updateChipStates();
    };

    const setSlotValue = (index, value, advance = true) => {
      const slot = slots[index];
      if (!slot) return;

      renderSlot(slot, value);
      sync();

      if (advance && value !== "") {
        const nextEmpty = slots.findIndex((item, itemIndex) => itemIndex > index && !(item.querySelector("[data-word-select-value]")?.value || "").trim());
        if (nextEmpty >= 0) {
          activeIndex = nextEmpty;
        } else {
          const firstEmpty = slots.findIndex((item) => !(item.querySelector("[data-word-select-value]")?.value || "").trim());
          activeIndex = firstEmpty >= 0 ? firstEmpty : index;
        }
        slots.forEach((item, itemIndex) => item.classList.toggle("is-active", itemIndex === activeIndex));
      }
    };

    const clearSlot = (index) => {
      setSlotValue(index, "", false);
      activeIndex = index;
      slots.forEach((item, itemIndex) => item.classList.toggle("is-active", itemIndex === activeIndex));
      sync();
    };

    slots.forEach((slot, index) => {
      slot.querySelector("[data-word-select-slot-btn]")?.addEventListener("click", () => {
        activeIndex = index;
        slots.forEach((item, itemIndex) => item.classList.toggle("is-active", itemIndex === activeIndex));

        const current = slot.querySelector("[data-word-select-value]")?.value || "";
        if (current !== "" && slot.classList.contains("is-active")) {
          clearSlot(index);
        }
      });

      slot.querySelector("[data-word-select-clear]")?.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        clearSlot(index);
      });
    });

    chips.forEach((chip) => {
      chip.addEventListener("click", () => {
        if (chip.disabled) return;
        const word = chip.dataset.word || "";
        if (!word) return;
        setSlotValue(activeIndex, word);
      });
    });

    dropdowns.forEach((dropdown) => {
      dropdown.addEventListener("change", () => {
        const slot = dropdown.closest("[data-word-select-slot]");
        const index = Number(slot?.dataset.blankIndex || 0);
        setSlotValue(index, dropdown.value.trim());
      });
    });

    applySavedBlanks();
    slots.forEach((slot, index) => renderSlot(slot, slot.querySelector("[data-word-select-value]")?.value || ""));
    sync();

    board.closest("form")?.addEventListener("submit", sync);
  });
})();
