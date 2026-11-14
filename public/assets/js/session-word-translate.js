(() => {
  const boards = document.querySelectorAll("[data-session-word-translate-board]");

  if (!boards.length) return;

  boards.forEach((board) => {
    const completeInput = board.querySelector("[data-word-translate-complete-input]");
    const inputs = [...board.querySelectorAll("[data-word-translate-input]")];
    const status = board.querySelector("[data-word-translate-status]");

    if (!completeInput || inputs.length === 0) return;

    try {
      const saved = JSON.parse(completeInput.value || "");

      if (saved && Array.isArray(saved.translations)) {
        inputs.forEach((input, index) => {
          if (typeof saved.translations[index] === "string" && !input.value) {
            input.value = saved.translations[index];
          }
        });
      }
    } catch (_error) {
      /* ignore invalid draft */
    }

    const sync = () => {
      const translations = inputs.map((input) => input.value.trim());
      const filled = translations.filter((value) => value !== "").length;
      const allFilled = filled === inputs.length && inputs.length > 0;
      const hasAny = filled > 0;

      completeInput.value = hasAny ? JSON.stringify({ translations }) : "";
      completeInput.dispatchEvent(new Event("input", { bubbles: true }));

      if (status) {
        if (allFilled) {
          status.textContent = "Đã dịch đủ — bạn có thể nộp bài.";
          status.classList.add("is-complete");
        } else if (hasAny) {
          status.textContent = `Đã dịch ${filled}/${inputs.length} từ — dùng Tab hoặc Enter để chuyển ô.`;
          status.classList.remove("is-complete");
        } else {
          status.textContent = `Đã dịch 0/${inputs.length} từ — dùng Tab hoặc Enter để chuyển ô.`;
          status.classList.remove("is-complete");
        }
      }
    };

    inputs.forEach((input, index) => {
      input.addEventListener("input", sync);

      input.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
          event.preventDefault();
          inputs[index + 1]?.focus();
        }
      });
    });

    board.closest("form")?.addEventListener("submit", sync);
    sync();
  });
})();
