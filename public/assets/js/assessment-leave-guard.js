(() => {
  const form = document.querySelector("form[data-assessment-leave-guard]");

  if (!form) {
    return;
  }

  const LEAVE_TITLE = form.dataset.leaveTitle || "Đang làm bài";
  const LEAVE_MESSAGE =
    form.dataset.leaveMessage ||
    "Bạn đang làm bài. Có muốn thoát không? Nếu thoát thì hệ thống sẽ không tính điểm của bạn.";

  let allowLeave = false;
  let pendingAsk = null;

  const flushAutosave = () => {
    if (typeof window.quizAutosaveFlush === "function") {
      window.quizAutosaveFlush();
      return Promise.resolve();
    }

    if (typeof window.assessmentAutosaveFlush === "function") {
      window.assessmentAutosaveFlush();
      return Promise.resolve();
    }

    if (typeof window.assessmentAutosaveAll === "function") {
      return Promise.resolve(window.assessmentAutosaveAll()).catch(() => undefined);
    }

    return Promise.resolve();
  };

  const permitLeave = () => {
    allowLeave = true;
  };

  window.assessmentPermitLeave = permitLeave;

  const askLeave = () => {
    if (allowLeave) {
      return Promise.resolve(true);
    }

    if (pendingAsk) {
      return pendingAsk;
    }

    if (window.AppConfirm?.ask) {
      pendingAsk = window.AppConfirm.ask(LEAVE_MESSAGE, {
        title: LEAVE_TITLE,
        confirmLabel: "Đồng ý thoát",
        danger: true,
      }).finally(() => {
        pendingAsk = null;
      });

      return pendingAsk;
    }

    return Promise.resolve(window.confirm(LEAVE_MESSAGE));
  };

  const leaveTo = (action) => {
    askLeave().then((ok) => {
      if (!ok) {
        return;
      }

      flushAutosave().finally(() => {
        permitLeave();
        action();
      });
    });
  };

  const isReloadShortcut = (event) => {
    if (event.key === "F5" || event.code === "F5") {
      return true;
    }

    const reloadKey = event.key === "r" || event.key === "R" || event.code === "KeyR";

    return reloadKey && (event.ctrlKey || event.metaKey);
  };

  const isInternalHref = (anchor) => {
    const href = anchor.getAttribute("href");

    if (!href || href === "#" || href.startsWith("#") || href.startsWith("javascript:")) {
      return false;
    }

    if (anchor.target === "_blank" || anchor.hasAttribute("download")) {
      return false;
    }

    try {
      const url = new URL(anchor.href, window.location.href);

      return url.origin === window.location.origin;
    } catch (_error) {
      return true;
    }
  };

  window.addEventListener("beforeunload", (event) => {
    flushAutosave();

    if (allowLeave) {
      return;
    }

    event.preventDefault();
    event.returnValue = LEAVE_MESSAGE;

    return LEAVE_MESSAGE;
  });

  document.addEventListener(
    "keydown",
    (event) => {
      if (allowLeave || !isReloadShortcut(event)) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      leaveTo(() => {
        window.location.reload();
      });
    },
    true
  );

  document.addEventListener(
    "click",
    (event) => {
      if (allowLeave) {
        return;
      }

      const anchor = event.target instanceof Element ? event.target.closest("a[href]") : null;

      if (!anchor || !isInternalHref(anchor)) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      leaveTo(() => {
        window.location.href = anchor.href;
      });
    },
    true
  );

  document.addEventListener(
    "submit",
    (event) => {
      const submitted = event.target;

      if (!(submitted instanceof HTMLFormElement)) {
        return;
      }

      if (submitted.matches("[data-assessment-leave-guard]")) {
        if (window.AssessmentOffline?.isOffline?.()) {
          return;
        }

        flushAutosave();
        permitLeave();
        return;
      }

      if (allowLeave || submitted.dataset.assessmentLeaveConfirmed === "1") {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      leaveTo(() => {
        submitted.dataset.assessmentLeaveConfirmed = "1";
        submitted.submit();
      });
    },
    true
  );

  history.pushState({ assessmentLeaveGuard: true }, "", window.location.href);

  window.addEventListener("popstate", () => {
    if (allowLeave) {
      return;
    }

    history.pushState({ assessmentLeaveGuard: true }, "", window.location.href);
    leaveTo(() => {
      history.go(-2);
    });
  });
})();
