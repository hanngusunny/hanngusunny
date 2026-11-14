(() => {
  const pairImage = window.ProgramPairImageEditor;

  const resolveMediaUrl = (value) => pairImage?.resolveMediaUrl?.(value) || String(value || "").trim();

  const toStoredPath = (inputOrValue) => {
    if (inputOrValue && typeof inputOrValue === "object" && inputOrValue.value !== undefined) {
      return pairImage?.getValue(inputOrValue) || String(inputOrValue.value || "").trim();
    }

    return String(inputOrValue || "").trim();
  };

  const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

  const round1 = (value) => Math.round(value * 10) / 10;

  const readPanelState = (questionItem) => {
    const jsonInput = questionItem.querySelector("[data-session-image-hotspot-json]");
    let data = { image: "", tolerance: 8, targets: [] };

    if (jsonInput?.value) {
      try {
        const parsed = JSON.parse(jsonInput.value);
        if (parsed && typeof parsed === "object") data = parsed;
      } catch (_error) {
        data = { image: "", tolerance: 8, targets: [] };
      }
    }

    const imageInput = questionItem.querySelector("[data-session-image-hotspot-image]");
    const toleranceInput = questionItem.querySelector("[data-session-image-hotspot-tolerance]");

    return {
      image: imageInput ? toStoredPath(imageInput) || imageInput.value.trim() : String(data.image || "").trim(),
      tolerance: clamp(Number(toleranceInput?.value ?? data.tolerance ?? 8), 3, 25),
      targets: Array.isArray(data.targets) ? data.targets : [],
    };
  };

  const renderTargetList = (questionItem, targets) => {
    const list = questionItem.querySelector("[data-session-image-hotspot-target-list]");
    if (!list) return;

    list.innerHTML = "";

    if (!targets.length) {
      list.innerHTML = '<li class="session-image-hotspot-target-empty muted">Chưa có điểm — bấm lên ảnh để thêm.</li>';
      return;
    }

    targets.forEach((target, index) => {
      const li = document.createElement("li");
      li.className = "session-image-hotspot-target-item";
      li.dataset.sessionImageHotspotTargetItem = "";
      li.innerHTML =
        `<span class="session-image-hotspot-target-no">${index + 1}</span>` +
        `<input type="text" class="form-control form-control-sm session-image-hotspot-target-label" data-session-image-hotspot-target-label value="${String(target.label || `Vị trí ${index + 1}`).replace(/"/g, "&quot;")}">` +
        `<span class="session-image-hotspot-target-coords">${round1(Number(target.x || 0))}%, ${round1(Number(target.y || 0))}%</span>` +
        `<button class="btn sunny-mini btn-delete-user" type="button" data-session-image-hotspot-remove-target title="Xóa điểm"><i class="bi bi-trash"></i></button>`;

      li.querySelector("[data-session-image-hotspot-target-label]")?.addEventListener("input", () => {
        syncImageHotspotJson(questionItem);
        scheduleAutosave?.();
      });

      li.querySelector("[data-session-image-hotspot-remove-target]")?.addEventListener("click", () => {
        const state = readPanelState(questionItem);
        state.targets.splice(index, 1);
        writePanelState(questionItem, state);
        scheduleAutosave?.();
      });

      list.appendChild(li);
    });
  };

  const renderMarkers = (questionItem, targets, tolerance) => {
    const overlay = questionItem.querySelector("[data-session-image-hotspot-overlay]");
    if (!overlay) return;

    overlay.innerHTML = "";

    targets.forEach((target, index) => {
      const marker = document.createElement("span");
      marker.className = "session-image-hotspot-marker";
      marker.style.left = `${target.x}%`;
      marker.style.top = `${target.y}%`;
      marker.style.setProperty("--hotspot-radius", `${tolerance}%`);
      marker.innerHTML = `<span class="session-image-hotspot-marker-no">${index + 1}</span>`;
      overlay.appendChild(marker);
    });
  };

  const writePanelState = (questionItem, state, onChange) => {
    const jsonInput = questionItem.querySelector("[data-session-image-hotspot-json]");
    const imageInput = questionItem.querySelector("[data-session-image-hotspot-image]");
    const toleranceInput = questionItem.querySelector("[data-session-image-hotspot-tolerance]");
    const canvas = questionItem.querySelector("[data-session-image-hotspot-canvas]");
    const img = questionItem.querySelector("[data-session-image-hotspot-preview-img]");

    const normalized = {
      image: imageInput ? toStoredPath(imageInput) || toStoredPath(state.image) : toStoredPath(state.image),
      tolerance: round1(state.tolerance),
      targets: state.targets.map((target, index) => ({
        id: target.id || `t${index + 1}`,
        label: String(target.label || `Vị trí ${index + 1}`).trim() || `Vị trí ${index + 1}`,
        x: round1(clamp(Number(target.x || 0), 0, 100)),
        y: round1(clamp(Number(target.y || 0), 0, 100)),
      })),
    };

    if (jsonInput) jsonInput.value = JSON.stringify(normalized);
    if (toleranceInput) toleranceInput.value = String(normalized.tolerance);

    const displayUrl = resolveMediaUrl(normalized.image);
    if (img) {
      img.src = displayUrl;
      img.hidden = displayUrl === "";
    }
    if (canvas) canvas.classList.toggle("is-empty", displayUrl === "");

    renderTargetList(questionItem, normalized.targets);
    renderMarkers(questionItem, normalized.targets, normalized.tolerance);

    if (typeof onChange === "function") onChange();
  };

  const syncImageHotspotJson = (questionItem) => {
    const state = readPanelState(questionItem);
    const labels = [...questionItem.querySelectorAll("[data-session-image-hotspot-target-label]")].map((input) => input.value.trim());
    state.targets = state.targets.map((target, index) => ({
      ...target,
      label: labels[index] || target.label || `Vị trí ${index + 1}`,
    }));
    writePanelState(questionItem, state);
  };

  let scheduleAutosave = null;

  const addTargetFromClick = (questionItem, event) => {
    const canvas = questionItem.querySelector("[data-session-image-hotspot-canvas]");
    const img = questionItem.querySelector("[data-session-image-hotspot-preview-img]");
    if (!canvas || !img || img.hidden) return;

    const rect = img.getBoundingClientRect();
    if (!rect.width || !rect.height) return;

    const x = round1(clamp(((event.clientX - rect.left) / rect.width) * 100, 0, 100));
    const y = round1(clamp(((event.clientY - rect.top) / rect.height) * 100, 0, 100));
    const state = readPanelState(questionItem);

    state.targets.push({
      id: `t${Date.now()}`,
      label: `Vị trí ${state.targets.length + 1}`,
      x,
      y,
    });

    writePanelState(questionItem, state, () => scheduleAutosave?.());
  };

  const bindImageHotspotPanel = (questionItem, options = {}) => {
    scheduleAutosave = typeof options.scheduleAutosave === "function" ? options.scheduleAutosave : null;

    const imageWrap = questionItem.querySelector("[data-session-image-hotspot-image-wrap]");
    pairImage?.init?.(imageWrap, () => {
      const imageInput = questionItem.querySelector("[data-session-image-hotspot-image]");
      const state = readPanelState(questionItem);
      state.image = imageInput ? toStoredPath(imageInput) : state.image;
      writePanelState(questionItem, state, () => scheduleAutosave?.());
    });

    questionItem.querySelector("[data-session-image-hotspot-tolerance]")?.addEventListener("input", (event) => {
      const output = questionItem.querySelector("[data-session-image-hotspot-tolerance-value]");
      if (output) output.textContent = `${event.target.value}%`;
      syncImageHotspotJson(questionItem);
      scheduleAutosave?.();
    });

    questionItem.querySelector("[data-session-image-hotspot-canvas]")?.addEventListener("click", (event) => {
      if (event.target.closest("[data-session-image-hotspot-remove-target]")) return;
      addTargetFromClick(questionItem, event);
    });

    questionItem.querySelector("[data-session-image-hotspot-clear-targets]")?.addEventListener("click", () => {
      const state = readPanelState(questionItem);
      state.targets = [];
      writePanelState(questionItem, state, () => scheduleAutosave?.());
    });

    syncImageHotspotJson(questionItem);
  };

  window.ProgramImageHotspotPanel = {
    bind: bindImageHotspotPanel,
    sync: syncImageHotspotJson,
  };
})();
