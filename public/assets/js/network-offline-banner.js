(() => {
  if (window.__networkOfflineBannerBound) {
    return;
  }

  window.__networkOfflineBannerBound = true;

  const takingQuiz = Boolean(
    document.querySelector("form[data-assessment-leave-guard], form[data-assessment-answer-autosave]")
  );

  const MESSAGE_DEFAULT = "Bạn đang không kết nối được mạng. Vui lòng kết nối lại mạng.";
  const MESSAGE_QUIZ =
    "Bạn đang không kết nối được mạng. Có thể tiếp tục làm bài — hệ thống sẽ tự gửi khi có mạng.";
  const MESSAGE_PENDING = "Đã ghi nhận nộp bài. Hệ thống sẽ tự gửi khi kết nối lại mạng.";
  const MESSAGE_TIMEOUT = "Hết giờ làm bài. Bài sẽ tự nộp khi có mạng.";

  const scriptEl = document.querySelector(
    'script[src*="network-offline-banner.js"], script[src*="assessment-offline-banner.js"]'
  );
  let banner = null;
  let probeTimer = 0;
  let pendingSubmit = false;
  let timedOut = false;

  const pingUrl = () => {
    const url = new URL(scriptEl?.src || window.location.href, window.location.href);
    url.searchParams.set("ping", String(Date.now()));

    return url.toString();
  };

  const bannerText = () => {
    if (takingQuiz && timedOut) {
      return MESSAGE_TIMEOUT;
    }

    if (takingQuiz && pendingSubmit) {
      return MESSAGE_PENDING;
    }

    if (takingQuiz) {
      return MESSAGE_QUIZ;
    }

    return MESSAGE_DEFAULT;
  };

  const ensureBanner = () => {
    if (banner) {
      return banner;
    }

    banner = document.createElement("div");
    banner.className = "network-offline-banner";
    banner.setAttribute("role", "status");
    banner.setAttribute("aria-live", "assertive");
    banner.hidden = true;
    banner.innerHTML =
      '<span class="network-offline-banner-icon" aria-hidden="true"><i class="fa-solid fa-wifi"></i></span>' +
      '<span class="network-offline-banner-text"></span>' +
      '<span class="network-offline-banner-icon network-offline-banner-loading" aria-hidden="true"><i class="fa-solid fa-rotate"></i></span>';
    document.body.appendChild(banner);

    return banner;
  };

  const setOffline = (offline) => {
    const el = ensureBanner();
    const text = el.querySelector(".network-offline-banner-text");

    if (text) {
      text.textContent = bannerText();
    }

    el.hidden = !offline;
    el.classList.toggle("is-visible", offline);
    el.classList.toggle("is-pending", pendingSubmit);
  };

  const probe = async () => {
    if (document.visibilityState === "hidden") {
      return;
    }

    if (typeof navigator.onLine === "boolean" && !navigator.onLine) {
      setOffline(true);
      return;
    }

    try {
      await fetch(pingUrl(), {
        method: "GET",
        cache: "no-store",
        credentials: "omit",
      });
      setOffline(false);
      window.dispatchEvent(new Event("assessment:network-ok"));
    } catch (_error) {
      setOffline(true);
      window.dispatchEvent(new Event("assessment:network-error"));
    }
  };

  window.addEventListener("offline", () => setOffline(true));
  window.addEventListener("online", () => {
    probe();
  });
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      probe();
    }
  });
  window.addEventListener("assessment:network-error", () => setOffline(true));
  window.addEventListener("assessment:network-ok", () => setOffline(false));
  window.addEventListener("assessment:offline-state", (event) => {
    pendingSubmit = Boolean(event.detail?.pendingSubmit);
    timedOut = Boolean(event.detail?.timedOut);

    if (typeof event.detail?.offline === "boolean") {
      setOffline(event.detail.offline);
    } else {
      setOffline(ensureBanner().classList.contains("is-visible"));
    }
  });

  if (window.AssessmentOffline?.getState) {
    const state = window.AssessmentOffline.getState();
    pendingSubmit = Boolean(state.pendingSubmit);
    timedOut = Boolean(state.timedOut);
  }

  if (typeof navigator.onLine === "boolean" && !navigator.onLine) {
    setOffline(true);
  }

  probe();
  probeTimer = window.setInterval(probe, 6000);
  window.addEventListener("pagehide", () => window.clearInterval(probeTimer));
})();
