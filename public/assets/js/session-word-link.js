(() => {
  const boards = document.querySelectorAll("[data-session-word-link-board]");

  if (!boards.length) return;

  const shuffle = (items) => {
    const list = items.slice();

    for (let i = list.length - 1; i > 0; i -= 1) {
      const j = Math.floor(Math.random() * (i + 1));
      [list[i], list[j]] = [list[j], list[i]];
    }

    return list;
  };

  const escapeHtml = (value) =>
    String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  const buildItemMeta = (el) => ({
    el,
    pairId: el.dataset.pairId || "",
    slot: el.dataset.itemSlot || "",
  });

  const resolveMediaUrl = (value, baseUrl = "") => {
    const trimmed = String(value || "").trim();
    if (trimmed === "") return "";

    if (/^https?:\/\//i.test(trimmed) || trimmed.startsWith("//")) {
      return trimmed;
    }

    if (trimmed.startsWith("/")) {
      return trimmed;
    }

    const base = String(baseUrl || "").replace(/\/$/, "");
    const path = trimmed.replace(/^\.?\//, "");

    return base ? `${base}/${path}` : path;
  };

  const renderItemHtml = (type, value, baseUrl = "") => {
    const trimmed = String(value || "").trim();
    const isImage = type === "image";

    if (isImage) {
      const src = resolveMediaUrl(trimmed, baseUrl);
      const bodyHtml = src
        ? `<img src="${escapeHtml(src)}" alt="" loading="lazy">`
        : '<span class="session-word-link-item-placeholder"><i class="bi bi-image" aria-hidden="true"></i></span>';

      return `<span class="session-word-link-item-body session-word-link-item-body--single session-word-link-item-body--image">${bodyHtml}</span>`;
    }

    const textHtml =
      trimmed !== ""
        ? `<span class="session-word-link-item-text-inner">${escapeHtml(trimmed)}</span>`
        : '<span class="session-word-link-item-placeholder session-word-link-item-placeholder--text"><i class="bi bi-fonts" aria-hidden="true"></i></span>';

    return `<span class="session-word-link-item-body session-word-link-item-body--single session-word-link-item-body--text">${textHtml}</span>`;
  };

  const normalizePair = (pair) => {
    if (pair.a_type || pair.a_value !== undefined) {
      return {
        a_type: pair.a_type === "text" ? "text" : "image",
        a_value: String(pair.a_value || "").trim(),
        b_type: pair.b_type === "image" ? "image" : "text",
        b_value: String(pair.b_value || "").trim(),
      };
    }

    return {
      a_type: "image",
      a_value: String(pair.image || "").trim(),
      b_type: "text",
      b_value: String(pair.label || "").trim(),
    };
  };

  const ensureSvgOverlay = (container) => {
    let svg = container.querySelector(".session-word-link-svg");

    if (!svg) {
      svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
      svg.classList.add("session-word-link-svg");
      svg.setAttribute("aria-hidden", "true");
      container.appendChild(svg);
    }

    const rect = container.getBoundingClientRect();
    svg.setAttribute("width", String(rect.width));
    svg.setAttribute("height", String(rect.height));
    svg.setAttribute("viewBox", `0 0 ${rect.width} ${rect.height}`);

    return svg;
  };

  const drawConnections = (columnsWrap, userLinks) => {
    if (!columnsWrap) return;

    const svg = ensureSvgOverlay(columnsWrap);
    svg.innerHTML = "";

    if (!userLinks.size) return;

    const boardRect = columnsWrap.getBoundingClientRect();
    let index = 0;

    userLinks.forEach((link) => {
      const leftEl = link.left.el;
      const rightEl = link.right.el;

      if (!leftEl || !rightEl) return;

      const leftRect = leftEl.getBoundingClientRect();
      const rightRect = rightEl.getBoundingClientRect();

      const x1 = leftRect.right - boardRect.left;
      const y1 = leftRect.top + leftRect.height / 2 - boardRect.top;
      const x2 = rightRect.left - boardRect.left;
      const y2 = rightRect.top + rightRect.height / 2 - boardRect.top;
      const span = Math.abs(x2 - x1);
      const cpOffset = Math.max(24, Math.min(span * 0.38, 140));

      const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
      path.setAttribute(
        "d",
        `M ${x1} ${y1} C ${x1 + cpOffset} ${y1}, ${x2 - cpOffset} ${y2}, ${x2} ${y2}`
      );
      const pathLength = path.getTotalLength();
      path.style.setProperty("--connection-length", String(pathLength));
      path.style.strokeDasharray = String(pathLength);
      path.style.strokeDashoffset = String(pathLength);
      path.classList.add("session-word-link-connection");
      path.style.animationDelay = `${index * 80}ms`;
      svg.appendChild(path);
      index += 1;
    });
  };

  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      boards.forEach((board) => {
        if (board._drawWordLinkConnections) {
          board._drawWordLinkConnections();
        }
      });
    }, 150);
  });

  boards.forEach((board) => {
    const columnsWrap = board.querySelector(".session-word-link-columns");
    const leftCol = board.querySelector("[data-word-link-left]");
    const rightCol = board.querySelector("[data-word-link-right]");
    const status = board.querySelector("[data-word-link-status]");
    const completeInput = board.querySelector("[data-word-link-complete-input]");
    const pairsRaw = board.dataset.wordLinkPairs || "[]";
    const appBaseUrl = board.dataset.appBase || "";
    let pairs = [];

    try {
      pairs = JSON.parse(pairsRaw);
    } catch (error) {
      pairs = [];
    }

    pairs = pairs.map(normalizePair).filter((pair) => pair.a_value !== "" && pair.b_value !== "");

    if (!leftCol || !rightCol || pairs.length < 1) {
      if (status) status.textContent = "Câu hỏi nối từ chưa được cấu hình đủ cặp (mỗi cặp cần điền cả 2 ô).";
      return;
    }

    const leftItems = [];
    const rightItems = [];

    pairs.forEach((pair, pairIndex) => {
      const leftBtn = document.createElement("button");
      leftBtn.type = "button";
      leftBtn.className = "session-word-link-item";
      leftBtn.draggable = true;
      leftBtn.dataset.pairId = String(pairIndex);
      leftBtn.dataset.itemSlot = "a";
      leftBtn.dataset.contentType = pair.a_type;
      leftBtn.innerHTML = renderItemHtml(pair.a_type, pair.a_value, appBaseUrl);
      leftItems.push(leftBtn);

      const rightBtn = document.createElement("button");
      rightBtn.type = "button";
      rightBtn.className = "session-word-link-item";
      rightBtn.draggable = true;
      rightBtn.dataset.pairId = String(pairIndex);
      rightBtn.dataset.itemSlot = "b";
      rightBtn.dataset.contentType = pair.b_type;
      rightBtn.innerHTML = renderItemHtml(pair.b_type, pair.b_value, appBaseUrl);
      rightItems.push(rightBtn);
    });

    shuffle(leftItems).forEach((item) => leftCol.appendChild(item));
    shuffle(rightItems).forEach((item) => rightCol.appendChild(item));

    let selected = null;
    let linkedCount = 0;
    let linkCounter = 0;
    let moves = 0;
    const totalPairs = pairs.length;
    const userLinks = new Map();

    const setStatus = (message, complete = false) => {
      if (!status) return;
      status.textContent = message;
      status.classList.toggle("is-complete", complete);
    };

    const getLinksPayload = () =>
      Array.from(userLinks.values()).map((link) => ({
        a: link.left.pairId,
        b: link.right.pairId,
      }));

    const syncCompleteInput = () => {
      if (!completeInput) return;

      const links = getLinksPayload();
      const nextValue = links.length > 0
        ? JSON.stringify({
            status: linkedCount >= totalPairs ? "completed" : "in_progress",
            moves,
            links,
          })
        : "";

      if (completeInput.value !== nextValue) {
        completeInput.value = nextValue;
        completeInput.dispatchEvent(new Event("input", { bubbles: true }));
      }

      if (linkedCount >= totalPairs && links.length > 0) {
        board.classList.add("is-complete");
        setStatus(`Đã nối đủ ${totalPairs} cặp. Có thể nối lại hoặc nộp bài để xem đáp án.`, true);
      } else {
        board.classList.remove("is-complete");
        setStatus(
          links.length > 0
            ? `Đã nối ${linkedCount}/${totalPairs} cặp — tiếp tục nối các ô còn lại`
            : "Kéo hoặc bấm chọn: nối từng ô bên trái với một ô bên phải. Đã nối vẫn có thể nối lại."
        );
      }
    };

    const syncConnectors = () => {
      window.requestAnimationFrame(() => drawConnections(columnsWrap, userLinks));
    };

    board._drawWordLinkConnections = () => drawConnections(columnsWrap, userLinks);

    const clearSelection = () => {
      if (selected?.el) selected.el.classList.remove("is-selected");
      selected = null;
    };

    const unlinkItem = (el, silent = false) => {
      const linkId = el.dataset.linkId || "";
      if (!linkId) return;

      const link = userLinks.get(linkId);
      if (!link) return;

      [link.left.el, link.right.el].forEach((itemEl) => {
        itemEl.classList.remove("is-linked");
        itemEl.classList.remove("is-selected", "is-drop-target");
        itemEl.draggable = true;
        delete itemEl.dataset.linkId;
      });

      userLinks.delete(linkId);
      linkedCount -= 1;

      if (!silent) {
        syncCompleteInput();
        syncConnectors();
      }
    };

    const createLink = (first, second, silent = false) => {
      unlinkItem(first.el, true);
      unlinkItem(second.el, true);

      linkCounter += 1;
      const linkId = String(linkCounter);
      const left = first.slot === "a" ? first : second;
      const right = first.slot === "b" ? first : second;

      left.el.dataset.linkId = linkId;
      right.el.dataset.linkId = linkId;

      [left.el, right.el].forEach((el) => {
        el.classList.add("is-linked");
        el.classList.remove("is-selected", "is-drop-target");
        el.draggable = true;
      });

      userLinks.set(linkId, { left, right });
      linkedCount += 1;
      clearSelection();

      if (!silent) {
        syncCompleteInput();
        syncConnectors();
      }
    };

    const tryLink = (first, second) => {
      if (!first || !second || first.el === second.el) return;
      if (first.slot === second.slot) return;

      moves += 1;
      createLink(first, second);
    };

    const handleSelect = (meta) => {
      if (selected?.el === meta.el) {
        clearSelection();
        setStatus(
          linkedCount >= totalPairs
            ? `Đã nối đủ ${totalPairs} cặp. Có thể nối lại hoặc nộp bài.`
            : "Kéo hoặc bấm chọn 2 ô khác cột để nối cặp"
        );
        return;
      }

      if (selected) {
        tryLink(selected, meta);
        return;
      }

      selected = meta;
      meta.el.classList.add("is-selected");
      setStatus(
        meta.el.classList.contains("is-linked")
          ? "Chọn ô khác cột để nối lại (hoặc kéo thả)"
          : meta.slot === "a"
            ? "Chọn ô bên phải để nối (hoặc kéo thả)"
            : "Chọn ô bên trái để nối (hoặc kéo thả)"
      );
    };

    const restoreFromInput = () => {
      let saved = null;

      try {
        saved = JSON.parse(completeInput?.value || "");
      } catch (_error) {
        saved = null;
      }

      const savedLinks = Array.isArray(saved?.links) ? saved.links : [];

      board.querySelectorAll(".session-word-link-item[data-link-id]").forEach((el) => {
        unlinkItem(el, true);
      });
      userLinks.clear();
      linkedCount = 0;
      linkCounter = 0;
      moves = Number.isFinite(Number(saved?.moves)) ? Number(saved.moves) : 0;

      savedLinks.forEach((row) => {
        const leftEl = leftCol.querySelector(
          `.session-word-link-item[data-item-slot="a"][data-pair-id="${CSS.escape(String(row?.a ?? ""))}"]`
        );
        const rightEl = rightCol.querySelector(
          `.session-word-link-item[data-item-slot="b"][data-pair-id="${CSS.escape(String(row?.b ?? ""))}"]`
        );

        if (leftEl && rightEl && !leftEl.dataset.linkId && !rightEl.dataset.linkId) {
          createLink(buildItemMeta(leftEl), buildItemMeta(rightEl), true);
        }
      });

      if (Number.isFinite(Number(saved?.moves))) {
        moves = Number(saved.moves);
      }

      syncCompleteInput();
      syncConnectors();
    };

    board._restoreWordLinkFromInput = restoreFromInput;
    restoreFromInput();

    board.querySelectorAll(".session-word-link-item").forEach((el) => {
      el.addEventListener("click", () => handleSelect(buildItemMeta(el)));

      el.addEventListener("dragstart", (event) => {
        event.dataTransfer?.setData("text/plain", el.dataset.pairId || "");
        el.classList.add("is-dragging");
      });

      el.addEventListener("dragend", () => {
        el.classList.remove("is-dragging");
        syncConnectors();
      });

      el.addEventListener("dragover", (event) => {
        event.preventDefault();
        el.classList.add("is-drop-target");
      });

      el.addEventListener("dragleave", () => {
        el.classList.remove("is-drop-target");
      });

      el.addEventListener("drop", (event) => {
        event.preventDefault();
        el.classList.remove("is-drop-target");

        const dragged = board.querySelector(".session-word-link-item.is-dragging");
        if (!dragged || dragged === el) return;

        tryLink(buildItemMeta(dragged), buildItemMeta(el));
        dragged.classList.remove("is-dragging");
      });
    });

    syncConnectors();
    if (linkedCount === 0) {
      setStatus("Kéo hoặc bấm chọn: nối từng ô bên trái với một ô bên phải. Đã nối vẫn có thể nối lại.");
    } else {
      syncCompleteInput();
    }
  });

})();
