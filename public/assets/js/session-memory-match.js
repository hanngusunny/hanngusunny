(() => {
  const boards = document.querySelectorAll("[data-session-memory-board]");

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

  const buildCardFront = (card) => {
    if (card.kind === "image") {
      return `<span class="session-memory-card-face session-memory-card-front session-memory-card-front--image"><img src="${escapeHtml(card.image)}" alt=""></span>`;
    }

    return `<span class="session-memory-card-face session-memory-card-front session-memory-card-front--text"><span class="session-memory-card-text">${escapeHtml(card.label)}</span></span>`;
  };

  const getMaxColumns = (grid) => {
    const isWide = window.matchMedia("(min-width: 768px)").matches;
    const gap = isWide ? 16 : 14;
    const minCard = isWide ? 148 : 128;
    const maxWidth = isWide ? 920 : 820;
    const containerWidth = Math.min(grid.clientWidth || maxWidth, maxWidth);

    return Math.max(2, Math.floor((containerWidth + gap) / (minCard + gap)));
  };

  const pickColumnCount = (totalCards, maxCols) => {
    if (totalCards <= 1) return 1;

    const findDivisorUpTo = (limit) => {
      for (let cols = Math.min(limit, totalCards); cols >= 2; cols -= 1) {
        if (totalCards % cols === 0) return cols;
      }

      return null;
    };

    const inRange = findDivisorUpTo(maxCols);
    if (inRange !== null) return inRange;

    if (totalCards <= maxCols + 2) return totalCards;

    for (let cols = maxCols + 1; cols <= totalCards; cols += 1) {
      if (totalCards % cols === 0) return cols;
    }

    return totalCards;
  };

  const applyBalancedGrid = (grid, totalCards) => {
    const cols = pickColumnCount(totalCards, getMaxColumns(grid));
    grid.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;
    grid.dataset.memoryCols = String(cols);
  };

  const spawnMatchBurst = (board, first, second) => {
    const boardRect = board.getBoundingClientRect();
    const firstRect = first.getBoundingClientRect();
    const secondRect = second.getBoundingClientRect();
    const x = (firstRect.left + firstRect.right + secondRect.left + secondRect.right) / 4 - boardRect.left;
    const y = (firstRect.top + firstRect.bottom + secondRect.top + secondRect.bottom) / 4 - boardRect.top;

    const burst = document.createElement("div");
    burst.className = "session-memory-match-burst";
    burst.style.left = `${x}px`;
    burst.style.top = `${y}px`;
    burst.setAttribute("aria-hidden", "true");

    for (let i = 0; i < 8; i += 1) {
      const spark = document.createElement("span");
      spark.className = "session-memory-match-spark";
      spark.style.setProperty("--spark-angle", `${i * 45}deg`);
      burst.appendChild(spark);
    }

    board.appendChild(burst);
    window.setTimeout(() => burst.remove(), 900);
  };

  const assignPairNumber = (first, second, pairNumber) => {
    [first, second].forEach((card) => {
      if (card.querySelector(".session-memory-pair-number")) return;

      const badge = document.createElement("span");
      badge.className = "session-memory-pair-number";
      badge.textContent = String(pairNumber);
      badge.setAttribute("aria-label", `Cặp ${pairNumber}`);
      badge.dataset.pairTone = String((pairNumber - 1) % 6);
      card.appendChild(badge);
      card.dataset.matchedPairNo = String(pairNumber);
    });
  };

  const celebrateMatch = (board, first, second, status) => {
    [first, second].forEach((card) => {
      card.classList.add("is-match-celebrate");

      const badge = document.createElement("span");
      badge.className = "session-memory-match-check";
      badge.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i>';
      card.querySelector(".session-memory-card-inner")?.appendChild(badge);

      window.setTimeout(() => {
        card.classList.remove("is-match-celebrate");
        badge.remove();
      }, 900);
    });

    spawnMatchBurst(board, first, second);

    if (status) {
      status.classList.add("is-match-hit");
      window.setTimeout(() => status.classList.remove("is-match-hit"), 700);
    }
  };

  boards.forEach((board) => {
    const grid = board.querySelector("[data-session-memory-grid]");
    const status = board.querySelector("[data-session-memory-status]");
    const completeInput = board.querySelector("[data-memory-complete-input]");
    const pairsRaw = board.dataset.memoryPairs || "[]";
    let pairs = [];

    try {
      pairs = JSON.parse(pairsRaw);
    } catch (error) {
      pairs = [];
    }

    pairs = pairs.filter((pair) => String(pair.image || "").trim() !== "" && String(pair.label || "").trim() !== "");

    if (!grid || pairs.length < 2) {
      if (status) status.textContent = "Câu hỏi lật ảnh chưa được cấu hình đủ cặp (mỗi cặp cần ảnh + chữ).";
      return;
    }

    const cards = [];

    pairs.forEach((pair, pairIndex) => {
      const image = String(pair.image || "").trim();
      const label = String(pair.label || "").trim();
      if (!image || !label) return;

      cards.push({ pairId: String(pairIndex), kind: "image", image, label });
      cards.push({ pairId: String(pairIndex), kind: "label", image, label });
    });

    if (cards.length < 4) {
      if (status) status.textContent = "Cần ít nhất 2 cặp hợp lệ (ảnh + chữ).";
      return;
    }

    shuffle(cards).forEach((card) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "session-memory-card";
      button.dataset.pairId = card.pairId;
      button.dataset.cardKind = card.kind;
      button.innerHTML =
        '<span class="session-memory-card-inner">' +
        '<span class="session-memory-card-face session-memory-card-back"><i class="bi bi-question-lg" aria-hidden="true"></i></span>' +
        buildCardFront(card) +
        "</span>";
      grid.appendChild(button);
    });

    applyBalancedGrid(grid, cards.length);

    let resizeTimer = null;
    window.addEventListener("resize", () => {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(() => applyBalancedGrid(grid, cards.length), 120);
    });

    let flipped = [];
    let matchedCount = 0;
    let moves = 0;
    let lock = false;
    const totalPairs = cards.length / 2;

    const matchedPairIds = () =>
      [
        ...new Set(
          [...grid.querySelectorAll(".session-memory-card.is-matched")]
            .map((card) => String(card.dataset.pairId || ""))
            .filter(Boolean)
        ),
      ];

    const syncProgress = () => {
      if (!completeInput) return;

      const pairIds = matchedPairIds();

      if (pairIds.length === 0) {
        completeInput.value = "";
        completeInput.dispatchEvent(new Event("input", { bubbles: true }));
        return;
      }

      completeInput.value = JSON.stringify({
        status: matchedCount >= totalPairs ? "completed" : "in_progress",
        moves,
        pairs: totalPairs,
        matched_pair_ids: pairIds,
      });
      completeInput.dispatchEvent(new Event("input", { bubbles: true }));
    };

    const setComplete = () => {
      if (!completeInput) return;

      completeInput.value = JSON.stringify({
        status: "completed",
        moves,
        pairs: totalPairs,
        matched_pair_ids: matchedPairIds(),
      });
      completeInput.dispatchEvent(new Event("input", { bubbles: true }));

      if (status) {
        status.textContent = `Hoàn thành! Bạn ghép đủ ${totalPairs} cặp ảnh–chữ sau ${moves} lượt lật.`;
        status.classList.add("is-complete");
      }

      board.classList.add("is-complete");

      window.setTimeout(() => {
        board.classList.add("is-complete-celebrate");
      }, 120);
    };

    const resetFlipped = () => {
      flipped.forEach((card) => card.classList.remove("is-flipped"));
      flipped = [];
      lock = false;
    };

    grid.querySelectorAll(".session-memory-card").forEach((cardEl) => {
      cardEl.addEventListener("click", () => {
        if (lock || cardEl.classList.contains("is-flipped") || cardEl.classList.contains("is-matched")) {
          return;
        }

        cardEl.classList.add("is-flipped");
        flipped.push(cardEl);

        if (flipped.length < 2) {
          if (status) status.textContent = "Chọn thêm 1 thẻ nữa để ghép ảnh với chữ";
          return;
        }

        lock = true;
        moves += 1;
        const [first, second] = flipped;
        const isMatch =
          first.dataset.pairId === second.dataset.pairId && first.dataset.cardKind !== second.dataset.cardKind;

        if (isMatch) {
          window.setTimeout(() => {
            const pairNumber = matchedCount + 1;

            first.classList.add("is-matched");
            second.classList.add("is-matched");
            assignPairNumber(first, second, pairNumber);
            celebrateMatch(board, first, second, status);
            matchedCount += 1;
            flipped = [];
            lock = false;

            if (matchedCount >= totalPairs) {
              window.setTimeout(() => setComplete(), 520);
              return;
            }

            syncProgress();

            if (status) {
              status.textContent = `Ghép đúng! ${matchedCount}/${totalPairs} cặp — tiếp tục nhé!`;
            }
          }, 320);
          return;
        }

        [first, second].forEach((card) => card.classList.add("is-shake"));
        window.setTimeout(() => {
          [first, second].forEach((card) => card.classList.remove("is-shake"));
        }, 420);

        if (status) status.textContent = "Chưa đúng cặp — ghép ảnh với chữ giải thích tương ứng";

        window.setTimeout(() => {
          resetFlipped();
          if (status && matchedCount < totalPairs) {
            status.textContent = "Lật 2 thẻ: ghép ảnh với chữ giải thích đúng cặp";
          }
        }, 900);
      });
    });

    try {
      const saved = JSON.parse(completeInput?.value || "");
      const savedIds = Array.isArray(saved?.matched_pair_ids) ? saved.matched_pair_ids : [];

      savedIds.forEach((pairId) => {
        const pairCards = [
          ...grid.querySelectorAll(`.session-memory-card[data-pair-id="${CSS.escape(String(pairId))}"]`),
        ];

        if (pairCards.length !== 2 || pairCards.some((card) => card.classList.contains("is-matched"))) {
          return;
        }

        pairCards.forEach((card) => card.classList.add("is-flipped", "is-matched"));
        matchedCount += 1;
        assignPairNumber(pairCards[0], pairCards[1], matchedCount);
      });

      if (Number.isFinite(Number(saved?.moves))) {
        moves = Number(saved.moves);
      }

      if (matchedCount >= totalPairs && savedIds.length > 0) {
        setComplete();
      } else if (matchedCount > 0) {
        syncProgress();
        if (status) {
          status.textContent = `Đã ghép ${matchedCount}/${totalPairs} cặp — tiếp tục nhé!`;
        }
      }
    } catch (_error) {
      /* ignore invalid draft */
    }

    if (status && matchedCount === 0) status.textContent = "Lật 2 thẻ: ghép ảnh với chữ giải thích đúng cặp";
  });
})();
