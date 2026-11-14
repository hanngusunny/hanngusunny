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
    const jsonInput = questionItem.querySelector("[data-session-image-label-drop-json]");
    let data = { image: "", zone_radius: 10, zones: [], labels: [] };

    if (jsonInput?.value) {
      try {
        const parsed = JSON.parse(jsonInput.value);
        if (parsed && typeof parsed === "object") data = parsed;
      } catch (_error) {
        data = { image: "", zone_radius: 10, zones: [], labels: [] };
      }
    }

    const imageInput = questionItem.querySelector("[data-session-image-label-drop-image]");
    const radiusInput = questionItem.querySelector("[data-session-image-label-drop-radius]");

    return {
      image: imageInput ? toStoredPath(imageInput) || imageInput.value.trim() : String(data.image || "").trim(),
      zone_radius: clamp(Number(radiusInput?.value ?? data.zone_radius ?? 10), 5, 25),
      zones: Array.isArray(data.zones) ? data.zones : [],
      labels: Array.isArray(data.labels) ? data.labels : [],
    };
  };

  const zoneOptionsHtml = (zones, selectedId) =>
    zones
      .map(
        (zone, index) =>
          `<option value="${String(zone.id || "").replace(/"/g, "&quot;")}"${zone.id === selectedId ? " selected" : ""}>${index + 1}. ${String(zone.label || `Vùng ${index + 1}`).replace(/</g, "&lt;")}</option>`
      )
      .join("");

  const renderZoneList = (questionItem, zones) => {
    const list = questionItem.querySelector("[data-session-image-label-drop-zone-list]");
    if (!list) return;

    list.innerHTML = "";

    if (!zones.length) {
      list.innerHTML = '<li class="session-image-label-drop-zone-empty muted">Chưa có vùng — bấm lên ảnh để thêm.</li>';
      return;
    }

    zones.forEach((zone, index) => {
      const li = document.createElement("li");
      li.className = "session-image-label-drop-zone-item";
      li.dataset.sessionImageLabelDropZoneItem = "";
      li.innerHTML =
        `<span class="session-image-label-drop-zone-no">${index + 1}</span>` +
        `<input type="text" class="form-control form-control-sm session-image-label-drop-zone-label" data-session-image-label-drop-zone-label value="${String(zone.label || `Vùng ${index + 1}`).replace(/"/g, "&quot;")}">` +
        `<span class="session-image-label-drop-zone-coords">${round1(Number(zone.x || 0))}%, ${round1(Number(zone.y || 0))}%</span>` +
        `<button class="btn sunny-mini btn-delete-user" type="button" data-session-image-label-drop-remove-zone title="Xóa vùng"><i class="bi bi-trash"></i></button>`;

      li.querySelector("[data-session-image-label-drop-zone-label]")?.addEventListener("input", () => {
        syncImageLabelDropJson(questionItem);
        scheduleAutosave?.();
      });

      li.querySelector("[data-session-image-label-drop-remove-zone]")?.addEventListener("click", () => {
        const state = readPanelState(questionItem);
        const removedId = state.zones[index]?.id;
        state.zones.splice(index, 1);
        if (removedId) {
          state.labels = state.labels.filter((label) => label.zone_id !== removedId);
          state.labels.forEach((label) => {
            if (!state.zones.some((zone) => zone.id === label.zone_id)) {
              label.zone_id = state.zones[0]?.id || "";
            }
          });
        }
        writePanelState(questionItem, state);
        scheduleAutosave?.();
      });

      list.appendChild(li);
    });
  };

  const renderLabelList = (questionItem, zones, labels) => {
    const list = questionItem.querySelector("[data-session-image-label-drop-label-list]");
    if (!list) return;

    list.innerHTML = "";

    if (!labels.length) {
      list.innerHTML = '<li class="session-image-label-drop-label-empty muted">Thêm nhãn chữ để học sinh kéo vào vùng trên ảnh.</li>';
      return;
    }

    labels.forEach((label, index) => {
      const li = document.createElement("li");
      li.className = "session-image-label-drop-label-item";
      li.dataset.sessionImageLabelDropLabelItem = "";
      li.innerHTML =
        `<span class="session-image-label-drop-label-no">${index + 1}</span>` +
        `<input type="text" class="form-control form-control-sm session-image-label-drop-label-text" data-session-image-label-drop-label-text value="${String(label.text || "").replace(/"/g, "&quot;")}" placeholder="Nhãn chữ">` +
        `<select class="form-select form-select-sm session-image-label-drop-label-zone" data-session-image-label-drop-label-zone>${zoneOptionsHtml(zones, label.zone_id)}</select>` +
        `<button class="btn sunny-mini btn-delete-user" type="button" data-session-image-label-drop-remove-label title="Xóa nhãn"><i class="bi bi-trash"></i></button>`;

      li.querySelector("[data-session-image-label-drop-label-text]")?.addEventListener("input", () => {
        syncImageLabelDropJson(questionItem);
        scheduleAutosave?.();
      });

      li.querySelector("[data-session-image-label-drop-label-zone]")?.addEventListener("change", () => {
        syncImageLabelDropJson(questionItem);
        scheduleAutosave?.();
      });

      li.querySelector("[data-session-image-label-drop-remove-label]")?.addEventListener("click", () => {
        const state = readPanelState(questionItem);
        state.labels.splice(index, 1);
        writePanelState(questionItem, state);
        scheduleAutosave?.();
      });

      list.appendChild(li);
    });
  };

  const renderZoneMarkers = (questionItem, zones, zoneRadius) => {
    const overlay = questionItem.querySelector("[data-session-image-label-drop-overlay]");
    if (!overlay) return;

    overlay.innerHTML = "";

    zones.forEach((zone, index) => {
      const marker = document.createElement("span");
      marker.className = "session-image-label-drop-zone-marker";
      marker.style.left = `${zone.x}%`;
      marker.style.top = `${zone.y}%`;
      marker.style.setProperty("--zone-radius", `${zoneRadius}%`);
      marker.innerHTML = `<span class="session-image-label-drop-zone-marker-no">${index + 1}</span>`;
      overlay.appendChild(marker);
    });
  };

  const writePanelState = (questionItem, state, onChange) => {
    const jsonInput = questionItem.querySelector("[data-session-image-label-drop-json]");
    const imageInput = questionItem.querySelector("[data-session-image-label-drop-image]");
    const radiusInput = questionItem.querySelector("[data-session-image-label-drop-radius]");
    const canvas = questionItem.querySelector("[data-session-image-label-drop-canvas]");
    const img = questionItem.querySelector("[data-session-image-label-drop-preview-img]");

    const normalizedZones = state.zones.map((zone, index) => ({
      id: zone.id || `z${index + 1}`,
      label: String(zone.label || `Vùng ${index + 1}`).trim() || `Vùng ${index + 1}`,
      x: round1(clamp(Number(zone.x || 0), 0, 100)),
      y: round1(clamp(Number(zone.y || 0), 0, 100)),
    }));

    const zoneIds = normalizedZones.map((zone) => zone.id);

    const normalizedLabels = state.labels
      .map((label, index) => {
        const text = String(label.text || "").trim();
        let zoneId = String(label.zone_id || "").trim();

        if (!zoneIds.includes(zoneId)) {
          zoneId = zoneIds[0] || "";
        }

        if (!zoneId) return null;

        return {
          id: label.id || `l${index + 1}`,
          text,
          zone_id: zoneId,
        };
      })
      .filter(Boolean);

    const normalized = {
      image: imageInput ? toStoredPath(imageInput) || toStoredPath(state.image) : toStoredPath(state.image),
      zone_radius: round1(clamp(Number(state.zone_radius ?? 10), 5, 25)),
      zones: normalizedZones,
      labels: normalizedLabels,
    };

    if (jsonInput) jsonInput.value = JSON.stringify(normalized);
    if (radiusInput) radiusInput.value = String(normalized.zone_radius);

    const displayUrl = resolveMediaUrl(normalized.image);
    if (img) {
      img.src = displayUrl;
      img.hidden = displayUrl === "";
    }
    if (canvas) canvas.classList.toggle("is-empty", displayUrl === "");

    renderZoneList(questionItem, normalized.zones);
    renderLabelList(questionItem, normalized.zones, normalized.labels);
    renderZoneMarkers(questionItem, normalized.zones, normalized.zone_radius);

    if (typeof onChange === "function") onChange();
  };

  const syncImageLabelDropJson = (questionItem) => {
    const state = readPanelState(questionItem);

    state.zones = state.zones.map((zone, index) => {
      const labelInput = questionItem.querySelectorAll("[data-session-image-label-drop-zone-label]")[index];
      return {
        ...zone,
        label: labelInput?.value.trim() || zone.label || `Vùng ${index + 1}`,
      };
    });

    state.labels = [...questionItem.querySelectorAll("[data-session-image-label-drop-label-item]")].map((item, index) => {
      const existing = state.labels[index] || {};
      const textInput = item.querySelector("[data-session-image-label-drop-label-text]");
      const zoneSelect = item.querySelector("[data-session-image-label-drop-label-zone]");
      return {
        ...existing,
        id: existing.id || `l${index + 1}`,
        text: textInput?.value.trim() || "",
        zone_id: zoneSelect?.value || existing.zone_id || state.zones[0]?.id || "",
      };
    });

    writePanelState(questionItem, state);
  };

  let scheduleAutosave = null;

  const addZoneFromClick = (questionItem, event) => {
    const img = questionItem.querySelector("[data-session-image-label-drop-preview-img]");
    if (!img || img.hidden) return;

    const rect = img.getBoundingClientRect();
    if (!rect.width || !rect.height) return;

    const x = round1(clamp(((event.clientX - rect.left) / rect.width) * 100, 0, 100));
    const y = round1(clamp(((event.clientY - rect.top) / rect.height) * 100, 0, 100));
    const state = readPanelState(questionItem);
    const zoneNo = state.zones.length + 1;
    const zoneId = `z${Date.now()}`;

    state.zones.push({
      id: zoneId,
      label: `Vùng ${zoneNo}`,
      x,
      y,
    });

    writePanelState(questionItem, state, () => scheduleAutosave?.());
  };

  const bindImageLabelDropPanel = (questionItem, options = {}) => {
    scheduleAutosave = typeof options.scheduleAutosave === "function" ? options.scheduleAutosave : null;

    const imageWrap = questionItem.querySelector("[data-session-image-label-drop-image-wrap]");
    pairImage?.init?.(imageWrap, () => {
      const state = readPanelState(questionItem);
      const imageInput = questionItem.querySelector("[data-session-image-label-drop-image]");
      state.image = imageInput ? toStoredPath(imageInput) : state.image;
      writePanelState(questionItem, state, () => scheduleAutosave?.());
    });

    questionItem.querySelector("[data-session-image-label-drop-radius]")?.addEventListener("input", (event) => {
      const output = questionItem.querySelector("[data-session-image-label-drop-radius-value]");
      if (output) output.textContent = `${event.target.value}%`;
      syncImageLabelDropJson(questionItem);
      scheduleAutosave?.();
    });

    questionItem.querySelector("[data-session-image-label-drop-canvas]")?.addEventListener("click", (event) => {
      if (event.target.closest("button")) return;
      addZoneFromClick(questionItem, event);
    });

    questionItem.querySelector("[data-session-image-label-drop-clear-zones]")?.addEventListener("click", () => {
      const state = readPanelState(questionItem);
      state.zones = [];
      state.labels = [];
      writePanelState(questionItem, state, () => scheduleAutosave?.());
    });

    questionItem.querySelector("[data-session-image-label-drop-add-label]")?.addEventListener("click", () => {
      const state = readPanelState(questionItem);
      syncImageLabelDropJson(questionItem);
      const fresh = readPanelState(questionItem);
      if (!fresh.zones.length) return;
      fresh.labels.push({
        id: `l${Date.now()}`,
        text: "",
        zone_id: fresh.zones[0].id,
      });
      writePanelState(questionItem, fresh, () => scheduleAutosave?.());
    });

    syncImageLabelDropJson(questionItem);
  };

  window.ProgramImageLabelDropPanel = {
    bind: bindImageLabelDropPanel,
    sync: syncImageLabelDropJson,
  };
})();
