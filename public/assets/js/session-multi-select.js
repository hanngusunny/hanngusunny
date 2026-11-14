(() => {
  const groups = document.querySelectorAll("[data-session-multi-select-group]");
  if (!groups.length) return;

  groups.forEach((group) => {
    const hidden = group.querySelector("[data-multi-select-complete-input]");
    if (!hidden) return;

    const sync = () => {
      const keys = [...group.querySelectorAll("[data-multi-select-choice]:checked")]
        .map((input) => String(input.value || "").trim().toLowerCase())
        .filter(Boolean)
        .sort();
      hidden.value = keys.join(",");
      hidden.dispatchEvent(new Event("input", { bubbles: true }));
    };

    const savedKeys = new Set(
      String(hidden.value || "")
        .split(",")
        .map((key) => key.trim().toLowerCase())
        .filter(Boolean)
    );

    group.querySelectorAll("[data-multi-select-choice]").forEach((input) => {
      const key = String(input.value || "").trim().toLowerCase();

      if (savedKeys.has(key)) {
        input.checked = true;
      }

      input.addEventListener("change", sync);
    });

    sync();
  });
})();
