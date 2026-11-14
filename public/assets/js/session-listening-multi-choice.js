(() => {
  const boards = document.querySelectorAll("[data-session-listening-multi-choice-board]");

  if (!boards.length) return;

  boards.forEach((board) => {
    const completeInput = board.querySelector("[data-listening-multi-choice-complete-input]");
    const choices = [...board.querySelectorAll("[data-listening-multi-choice-choice]")];
    const status = board.querySelector("[data-listening-multi-choice-status]");
    const items = board.querySelectorAll(".session-listening-multi-choice-student-item");

    if (!completeInput || items.length === 0) return;

    const sync = () => {
      const answers = [];
      let answered = 0;

      items.forEach((item, index) => {
        const selected = item.querySelector("[data-listening-multi-choice-choice]:checked");
        const value = selected ? selected.value : "";
        answers[index] = value;
        if (value !== "") answered += 1;
      });

      const allAnswered = answered === items.length && items.length > 0;
      const nextValue = allAnswered ? JSON.stringify({ answers }) : "";

      if (completeInput.value !== nextValue) {
        completeInput.value = nextValue;
        completeInput.dispatchEvent(new Event("input", { bubbles: true }));
      }

      if (status) {
        if (allAnswered) {
          status.textContent = "Đã chọn đủ — bạn có thể nộp bài.";
          status.classList.add("is-complete");
        } else {
          status.textContent = `Đã trả lời ${answered}/${items.length} câu — chọn đáp án cho từng câu hỏi.`;
          status.classList.remove("is-complete");
        }
      }
    };

    choices.forEach((choice) => {
      choice.addEventListener("change", sync);
    });

    board.closest("form")?.addEventListener("submit", () => {
      sync();
    });

    sync();
  });
})();
