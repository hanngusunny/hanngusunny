(() => {
  const container = document.getElementById("presetQuestionsContainer");
  const addBtn = document.getElementById("addPresetQuestionBtn");
  const template = document.getElementById("presetQuestionTemplate");

  if (!container || !addBtn || !template) return;

  const renumber = () => {
    container.querySelectorAll("[data-preset-item]").forEach((item, index) => {
      const num = item.querySelector("[data-preset-number]");
      if (num) num.textContent = String(index + 1);
    });
  };

  const bindPresetCard = (item) => {
    item.querySelector(".cr-preset-toggle")?.addEventListener("click", () => {
      const collapsed = item.classList.toggle("is-collapsed");
      item.querySelector(".cr-preset-toggle")?.setAttribute("aria-expanded", collapsed ? "false" : "true");
    });

    item.querySelector(".btn-remove-preset")?.addEventListener("click", () => {
      item.remove();
      renumber();
    });

    const optionsWrap = item.querySelector("[data-preset-options]");
    item.querySelectorAll("[data-preset-type-radio]").forEach((radio) => {
      radio.addEventListener("change", () => {
        if (!radio.checked || !optionsWrap) return;
        optionsWrap.hidden = radio.value !== "choice";
      });
    });

    item.querySelector("[data-preset-question-input]")?.addEventListener("input", (e) => {
      const preview = item.querySelector("[data-preset-preview]");
      if (!preview) return;
      const text = e.target.value.trim();
      preview.textContent = text || "Chưa nhập nội dung câu hỏi";
    });
  };

  const addPreset = () => {
    const index = container.querySelectorAll("[data-preset-item]").length;
    const html = template.innerHTML
      .replace(/__INDEX__/g, String(index))
      .replace(/__NUMBER__/g, String(index + 1));
    const wrap = document.createElement("div");
    wrap.innerHTML = html.trim();
    const item = wrap.firstElementChild;
    if (!item) return;
    container.appendChild(item);
    bindPresetCard(item);
    renumber();
    item.classList.remove("is-collapsed");
    item.querySelector(".cr-preset-toggle")?.setAttribute("aria-expanded", "true");
    item.querySelector("[data-preset-question-input]")?.focus();
  };

  container.querySelectorAll("[data-preset-item]").forEach(bindPresetCard);
  addBtn.addEventListener("click", addPreset);
})();
