(() => {
  const boards = document.querySelectorAll("[data-session-image-hotspot-board]");

  if (!boards.length) return;

  const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
  const round1 = (value) => Math.round(value * 10) / 10;

  boards.forEach((board) => {
    const completeInput = board.querySelector("[data-image-hotspot-complete-input]");
    const status = board.querySelector("[data-image-hotspot-status]");
    const img = board.querySelector("[data-image-hotspot-img]");
    const overlay = board.querySelector("[data-image-hotspot-overlay]");
    const resetBtn = board.querySelector("[data-image-hotspot-reset]");
    const targetCount = Number(board.dataset.targetCount || 0);

    if (!completeInput || !img || !overlay || targetCount < 1) return;

    let clicks = [];

    try {
      const saved = JSON.parse(completeInput.value || "");
      const savedClicks = Array.isArray(saved?.clicks) ? saved.clicks : [];

      clicks = savedClicks
        .filter((click) => click && Number.isFinite(Number(click.x)) && Number.isFinite(Number(click.y)))
        .slice(0, targetCount)
        .map((click) => ({
          x: round1(clamp(Number(click.x), 0, 100)),
          y: round1(clamp(Number(click.y), 0, 100)),
        }));
    } catch (_error) {
      clicks = [];
    }

    const render = () => {
      overlay.innerHTML = "";

      clicks.forEach((click, index) => {
        const marker = document.createElement("span");
        marker.className = "session-image-hotspot-click-marker";
        marker.style.left = `${click.x}%`;
        marker.style.top = `${click.y}%`;
        marker.innerHTML = `<span>${index + 1}</span>`;
        overlay.appendChild(marker);
      });

      const filled = clicks.length;
      const allFilled = filled === targetCount;

      completeInput.value = filled > 0 ? JSON.stringify({ clicks }) : "";
      completeInput.dispatchEvent(new Event("input", { bubbles: true }));

      if (status) {
        if (allFilled) {
          status.textContent = "Đã chọn đủ vị trí — bạn có thể nộp bài.";
          status.classList.add("is-complete");
        } else {
          status.textContent = `Đã chọn ${filled}/${targetCount} điểm — bấm đúng vị trí trên ảnh.`;
          status.classList.remove("is-complete");
        }
      }
    };

    board.querySelector("[data-image-hotspot-canvas]")?.addEventListener("click", (event) => {
      if (clicks.length >= targetCount) return;

      const rect = img.getBoundingClientRect();
      if (!rect.width || !rect.height) return;

      const x = round1(clamp(((event.clientX - rect.left) / rect.width) * 100, 0, 100));
      const y = round1(clamp(((event.clientY - rect.top) / rect.height) * 100, 0, 100));

      clicks.push({ x, y });
      render();
    });

    resetBtn?.addEventListener("click", () => {
      clicks = [];
      render();
    });

    board.closest("form")?.addEventListener("submit", render);
    render();
  });
})();
