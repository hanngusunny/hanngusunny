(() => {
  const overlay = document.getElementById("pwaLoading");
  const textEl = document.getElementById("pwaLoadingText");
  let hideTimer = null;

  const setText = (message) => {
    if (!textEl) return;
    textEl.textContent = message || "";
    textEl.hidden = true;
  };

  const show = (message) => {
    if (!overlay) return;
    clearTimeout(hideTimer);
    setText(message);
    overlay.hidden = false;
    overlay.classList.add("is-on");
    document.body.classList.add("is-waiting");
  };

  const hide = () => {
    if (!overlay) return;
    clearTimeout(hideTimer);
    overlay.classList.remove("is-on");
    overlay.hidden = true;
    document.body.classList.remove("is-waiting");
  };

  const hideWhenReady = () => {
    hide();
  };

  if (document.readyState === "complete") {
    hideWhenReady();
  } else {
    document.addEventListener("DOMContentLoaded", hideWhenReady);
    window.addEventListener("load", hideWhenReady);
  }

  const messageForForm = (form) => {
    const custom = form.getAttribute("data-pwa-wait");
    if (custom) return custom;

    const action = String(form.getAttribute("action") || "");

    if (action.includes("dang-nhap")) return "Đang đăng nhập…";
    if (action.includes("vao-lop")) return "Đang vào lớp…";
    if (action.includes("roi") || form.id === "leaveForm") return "Đang rời lớp…";
    if (action.includes("dang-xuat")) return "Đang đăng xuất…";
    if (action.includes("doi-mat-khau") || form.id === "mat-khau") return "Đang đổi mật khẩu…";
    if (action.includes("ho-so") || form.id === "thong-tin") return "Đang lưu hồ sơ…";
    if (action.includes("bat-dau")) return "Đang bắt đầu…";
    if (action.includes("lam-lai")) return "Đang mở luyện tập…";
    if (action.includes("ket-qua-da-nop")) return "Đang tải kết quả…";
    if (action.includes("nhan-xet")) return "Đang lưu đánh giá…";
    if (
      action.includes("nop") ||
      action.includes("trac-nghiem") ||
      action.includes("on-tap") ||
      form.classList.contains("session-quiz-form")
    ) {
      return "Đang nộp bài…";
    }

    return "Đang xử lý…";
  };

  const isInternalNav = (anchor) => {
    if (!anchor || anchor.target === "_blank" || anchor.hasAttribute("download")) {
      return false;
    }

    if (anchor.closest("[data-pwa-skip-loading]")) {
      return false;
    }

    const raw = anchor.getAttribute("href") || "";
    if (!raw || raw.startsWith("#") || raw.startsWith("javascript:") || raw.startsWith("mailto:") || raw.startsWith("tel:")) {
      return false;
    }

    let url;
    try {
      url = new URL(anchor.href, window.location.href);
    } catch (e) {
      return false;
    }

    if (url.origin !== window.location.origin) {
      return false;
    }

    if (url.pathname === window.location.pathname && url.search === window.location.search) {
      return false;
    }

    return true;
  };

  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (event.defaultPrevented) return;
    if (form.hasAttribute("data-pwa-skip-loading")) return;

    const submitter = event.submitter;
    if (submitter) {
      submitter.classList.add("is-wait");
      submitter.setAttribute("aria-busy", "true");
    }

    show(messageForForm(form));
    window.requestAnimationFrame(() => {
      if (!event.defaultPrevented) {
        return;
      }

      hide();
      submitter?.classList.remove("is-wait");
      submitter?.removeAttribute("aria-busy");
    });
  });

  document.addEventListener("click", (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    const anchor = event.target.closest("a[href]");
    if (!isInternalNav(anchor)) return;

    const label = anchor.getAttribute("data-pwa-wait") || "Đang tải…";
    show(label);
  });

  window.addEventListener("pageshow", hide);
  window.addEventListener("pagehide", hide);

  const menu = document.getElementById("pwaAccountMenu");
  const menuOpen = document.querySelector("[data-pwa-menu-open]");

  const setMenu = (open) => {
    if (!menu || !menuOpen) {
      return;
    }

    menu.hidden = !open;
    menu.classList.toggle("is-open", open);
    document.body.classList.toggle("is-pwa-menu", open);
    menuOpen.setAttribute("aria-expanded", open ? "true" : "false");
  };

  menuOpen?.addEventListener("click", () => {
    setMenu(menu.hidden);
  });

  menu?.querySelectorAll("[data-pwa-menu-close]").forEach((btn) => {
    btn.addEventListener("click", () => setMenu(false));
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      setMenu(false);
    }
  });

  window.pwaLoading = { show, hide };
})();

