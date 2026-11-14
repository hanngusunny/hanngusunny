(function () {
  const root = document.querySelector("[data-cvl-root]");

  if (!root) {
    return;
  }

  const iframe = root.querySelector("[data-cvl-iframe]");
  const videoEl = root.querySelector("[data-cvl-video]");
  const titleEl = root.querySelector("[data-cvl-title]");
  const sessionEl = root.querySelector("[data-cvl-session]");
  const detailEl = root.querySelector("[data-cvl-detail]");
  const playerMedia = root.querySelector(".cvl-player-frame");
  const playlistItems = root.querySelectorAll("[data-cvl-play]");

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

  const updateButton = (button, isActive) => {
    const icon = button.querySelector("i");

    button.setAttribute("aria-label", isActive ? "Thu nhỏ video" : "Xem full màn hình");
    button.setAttribute("title", isActive ? "Thu nhỏ" : "Xem full màn hình");

    if (icon) {
      icon.className = isActive ? "fa-solid fa-compress" : "fa-solid fa-expand";
    }
  };

  const setActiveItem = (button) => {
    playlistItems.forEach((item) => {
      const active = item === button;
      item.classList.toggle("is-active", active);
      item.setAttribute("aria-current", active ? "true" : "false");
    });
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

  const applyVideo = (button) => {
    const embedSrc = button.getAttribute("data-embed-src") || "";
    const title = button.getAttribute("data-title") || "Video";
    const detailUrl = button.getAttribute("data-detail-url") || "";
    const sessionLabel = button.getAttribute("data-session-label") || "";
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

    if (sessionEl) {
      if (sessionLabel) {
        sessionEl.textContent = sessionLabel;
        sessionEl.hidden = false;
      } else {
        sessionEl.textContent = "";
        sessionEl.hidden = true;
      }
    }

    if (detailEl) {
      if (detailUrl) {
        detailEl.setAttribute("href", detailUrl);
        detailEl.hidden = false;
      } else {
        detailEl.setAttribute("href", "#");
        detailEl.hidden = true;
      }
    }

    setActiveItem(button);
    button.scrollIntoView({ block: "nearest", behavior: "smooth" });
  };

  playlistItems.forEach((button) => {
    button.addEventListener("click", () => {
      applyVideo(button);
    });
  });

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
      updateButton(fullscreenBtn, getFullscreenElement() === playerMedia);
    };

    document.addEventListener("fullscreenchange", syncButton);
    document.addEventListener("webkitfullscreenchange", syncButton);
    syncButton();
  }
})();
