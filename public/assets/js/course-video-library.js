(function () {
  const root = document.querySelector("[data-cvl-root]");

  if (!root) {
    return;
  }

  const iframe = root.querySelector("[data-cvl-iframe]");
  const videoEl = root.querySelector("[data-cvl-video]");
  const titleEl = root.querySelector("[data-cvl-title]");
  const counterEl = root.querySelector("[data-cvl-counter]");
  const statusEl = root.querySelector("[data-cvl-status]");
  const prevBtn = root.querySelector("[data-cvl-prev]");
  const playlistContainer = root.querySelector("[data-cvl-playlist]");
  const playerMedia = root.querySelector(".cvl-player-frame");
  const playlistItems = Array.from(root.querySelectorAll("[data-cvl-play]"));

  let activePos = Math.max(
    0,
    playlistItems.findIndex((item) => item.classList.contains("is-active"))
  );

  const getFullscreenElement = () =>
    document.fullscreenElement || document.webkitFullscreenElement || null;

  const exitFullscreen = () => {
    if (typeof document.exitFullscreen === "function") {
      return document.exitFullscreen();
    }

    if (typeof document.webkitExitFullscreen === "function") {
      document.webkitExitFullscreen();
    }

    return Promise.resolve();
  };

  const requestFullscreen = (element) => {
    if (typeof element.requestFullscreen === "function") {
      return element.requestFullscreen();
    }

    if (typeof element.webkitRequestFullscreen === "function") {
      element.webkitRequestFullscreen();
      return Promise.resolve();
    }

    return Promise.reject(new Error("Fullscreen unsupported"));
  };

  const updateFullscreenButton = (button, isActive) => {
    const labelEl = button.querySelector("[data-cvl-full-label]");

    button.setAttribute("aria-label", isActive ? "Thu nhỏ video" : "Xem toàn màn hình");
    button.setAttribute("title", isActive ? "Thu nhỏ" : "Xem toàn màn hình");

    if (labelEl) {
      labelEl.textContent = isActive ? "Thu nhỏ" : "Toàn màn hình";
    }
  };

  const syncPrevButton = () => {
    if (!prevBtn) {
      return;
    }

    prevBtn.disabled = activePos <= 0;
  };

  const scrollCardIntoPlaylist = (card, behavior) => {
    if (!playlistContainer || !card) {
      return;
    }

    const containerRect = playlistContainer.getBoundingClientRect();
    const cardRect = card.getBoundingClientRect();
    const offsetTop = cardRect.top - containerRect.top + playlistContainer.scrollTop;
    const targetTop = Math.max(
      0,
      offsetTop - (playlistContainer.clientHeight - cardRect.height) / 2
    );

    playlistContainer.scrollTo({
      top: targetTop,
      behavior: behavior || "smooth",
    });
  };

  const setActiveItem = (button) => {
    playlistItems.forEach((item, idx) => {
      const active = item === button;
      item.classList.toggle("is-active", active);
      item.setAttribute("aria-current", active ? "true" : "false");
      if (active) {
        activePos = idx;
      }
    });

    syncPrevButton();
  };

  const showIframe = (src, title) => {
    if (videoEl) {
      videoEl.pause();
      videoEl.removeAttribute("src");
      videoEl.hidden = true;
    }

    if (!iframe) {
      return;
    }

    iframe.hidden = false;

    if (iframe.getAttribute("src") !== src) {
      iframe.setAttribute("src", src);
    }

    iframe.setAttribute("title", title);
  };

  const showFile = (src, title) => {
    if (iframe) {
      iframe.removeAttribute("src");
      iframe.hidden = true;
    }

    if (!videoEl) {
      return;
    }

    videoEl.hidden = false;
    videoEl.setAttribute("title", title);

    if (videoEl.getAttribute("src") !== src) {
      videoEl.setAttribute("src", src);
    }
  };

  const applyVideo = (button, scrollBehavior) => {
    const embedSrc = button.getAttribute("data-embed-src") || "";
    const title = button.getAttribute("data-title") || "Video";
    const counter = button.getAttribute("data-counter") || "";
    const status = button.getAttribute("data-status") || "done";
    const statusLabel = button.getAttribute("data-status-label") || "";
    const playerType = button.getAttribute("data-player-type") || "drive";

    if (!embedSrc) {
      return;
    }

    if (playerType === "file") {
      showFile(embedSrc, title);
    } else {
      showIframe(embedSrc, title);
    }

    if (titleEl) {
      titleEl.textContent = title;
    }

    if (counterEl && counter) {
      counterEl.textContent = counter;
    }

    if (statusEl && statusLabel) {
      statusEl.textContent = statusLabel;
      statusEl.className = "sn-video-status-text is-" + status;
    }

    setActiveItem(button);
    scrollCardIntoPlaylist(button, scrollBehavior || "smooth");
  };

  playlistItems.forEach((button) => {
    button.addEventListener("click", () => {
      applyVideo(button, "smooth");
    });
  });

  if (prevBtn) {
    prevBtn.addEventListener("click", () => {
      if (activePos > 0 && playlistItems[activePos - 1]) {
        applyVideo(playlistItems[activePos - 1], "smooth");
      }
    });
  }

  root.querySelectorAll(".cvl-playlist-thumb-img").forEach((img) => {
    img.addEventListener("error", () => {
      img.classList.add("is-broken");
    });
  });

  const fullscreenBtn = root.querySelector(".cvl-fullscreen-btn");

  if (fullscreenBtn && playerMedia) {
    fullscreenBtn.addEventListener("click", () => {
      const active = getFullscreenElement() === playerMedia;

      if (active) {
        exitFullscreen().catch(() => {});
        return;
      }

      requestFullscreen(playerMedia).catch(() => {});
    });

    const syncButton = () => {
      updateFullscreenButton(fullscreenBtn, getFullscreenElement() === playerMedia);
    };

    document.addEventListener("fullscreenchange", syncButton);
    document.addEventListener("webkitfullscreenchange", syncButton);
    syncButton();
  }

  syncPrevButton();

  if (playlistItems[activePos]) {
    requestAnimationFrame(() => {
      scrollCardIntoPlaylist(playlistItems[activePos], "auto");
    });
  }
})();
