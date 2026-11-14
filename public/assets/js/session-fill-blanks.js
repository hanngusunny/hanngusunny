(() => {
  const boards = document.querySelectorAll("[data-session-fill-blanks-board]");

  if (!boards.length) return;

  const measureCanvas = document.createElement("canvas");
  const measureCtx = measureCanvas.getContext("2d");
  const supportsFieldSizing =
    typeof CSS !== "undefined" && CSS.supports && CSS.supports("field-sizing", "content");

  const resizeInput = (input) => {
    if (supportsFieldSizing || !measureCtx) {
      return;
    }

    const style = window.getComputedStyle(input);
    measureCtx.font = [
      style.fontStyle,
      style.fontWeight,
      style.fontSize,
      style.fontFamily,
    ].join(" ");

    const sample = input.value || input.placeholder || "…";
    const padding =
      (parseFloat(style.paddingLeft) || 0) + (parseFloat(style.paddingRight) || 0);
    const minWidth = parseFloat(style.minWidth) || 72;
    const parent =
      input.closest(".session-fill-blanks-sentence") || input.parentElement;
    const maxWidth = Math.max(
      minWidth,
      (parent && parent.clientWidth ? parent.clientWidth : window.innerWidth) - 16
    );
    const next = Math.ceil(measureCtx.measureText(sample).width + padding + 10);

    input.style.width = Math.min(Math.max(next, minWidth), maxWidth) + "px";
  };

  boards.forEach((board) => {
    const completeInput = board.querySelector("[data-fill-blanks-complete-input]");
    const inputs = [...board.querySelectorAll("[data-fill-blank-input]")];
    const status = board.querySelector("[data-fill-blanks-status]");

    if (!completeInput || inputs.length === 0) return;

    try {
      const saved = JSON.parse(completeInput.value || "");

      if (saved && Array.isArray(saved.blanks)) {
        inputs.forEach((input, index) => {
          if (typeof saved.blanks[index] === "string" && !input.value) {
            input.value = saved.blanks[index];
          }
        });
      }
    } catch (_error) {
      /* ignore invalid draft */
    }

    const sync = () => {
      const blanks = inputs.map((input) => input.value.trim());
      const filled = blanks.filter((value) => value !== "").length;
      const allFilled = filled === inputs.length && inputs.length > 0;

      completeInput.value = filled > 0 ? JSON.stringify({ blanks }) : "";
      completeInput.dispatchEvent(new Event("input", { bubbles: true }));

      if (status) {
        if (allFilled) {
          status.textContent = "Đã điền đủ — bạn có thể nộp bài.";
          status.classList.add("is-complete");
        } else {
          status.textContent = `Đã điền ${filled}/${inputs.length} ô — dùng Tab hoặc Enter để chuyển ô.`;
          status.classList.remove("is-complete");
        }
      }
    };

    inputs.forEach((input, index) => {
      input.addEventListener("input", () => {
        resizeInput(input);
        sync();
      });

      input.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
          event.preventDefault();
          inputs[index + 1]?.focus();
        }
      });

      resizeInput(input);
    });

    board.closest("form")?.addEventListener("submit", sync);
    sync();
  });
})();
