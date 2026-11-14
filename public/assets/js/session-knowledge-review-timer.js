(() => {
  const pad = (n) => String(n).padStart(2, "0");

  const formatTime = (seconds) => {
    const s = Math.max(0, seconds);
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;

    if (h > 0) {
      return `${h}:${pad(m)}:${pad(sec)}`;
    }

    return `${pad(m)}:${pad(sec)}`;
  };

  const findTimerBar = (form) => {
    const prev = form.previousElementSibling;

    if (prev instanceof HTMLElement && prev.hasAttribute("data-quiz-timer")) {
      return prev;
    }

    return form.parentElement?.querySelector("[data-quiz-timer]")
      || form.closest(".sw-quiz, article, .pwa-session-work, main")?.querySelector("[data-quiz-timer]")
      || null;
  };

  const bindTimer = (form) => {
    if (!(form instanceof HTMLFormElement) || form.dataset.quizTimerBound === "1") {
      return;
    }

    if (form.dataset.timed !== "1") {
      return;
    }

    const bar = findTimerBar(form);

    if (!bar) {
      return;
    }

    const label = bar.querySelector("[data-timer-label]");
    const timedOutInput = form.querySelector(".js-timed-out, [name='timed_out']");
    let expiresTs = parseInt(bar.dataset.expiresTs || "0", 10);
    let remaining = parseInt(bar.dataset.remaining || "0", 10);
    let submitted = false;

    if (expiresTs <= 0 && remaining > 0) {
      expiresTs = Math.floor(Date.now() / 1000) + remaining;
    }

    if (expiresTs <= 0) {
      return;
    }

    form.dataset.quizTimerBound = "1";

    const leftSeconds = () => expiresTs - Math.floor(Date.now() / 1000);

    const autoSubmit = () => {
      if (submitted) {
        return;
      }

      submitted = true;

      if (timedOutInput) {
        timedOutInput.value = "1";
      }

      bar.classList.add("is-expired");

      if (label) {
        label.textContent = formatTime(0);
      }

      if (typeof window.quizAutosaveFlush === "function") {
        window.quizAutosaveFlush();
      }

      if (form.hasAttribute("data-assessment-leave-guard") && window.AssessmentOffline?.isOffline?.()) {
        window.AssessmentOffline.queueSubmit({ timedOut: true });
        return;
      }

      if (typeof form.requestSubmit === "function") {
        form.requestSubmit();
        return;
      }

      form.submit();
    };

    const tick = () => {
      if (submitted) {
        return;
      }

      const left = leftSeconds();

      if (label) {
        label.textContent = formatTime(left);
      }

      bar.classList.toggle("is-warning", left > 0 && left <= 300);
      bar.classList.toggle("is-critical", left > 0 && left <= 60);

      if (left <= 0) {
        autoSubmit();
      }
    };

    tick();
    window.setInterval(tick, 1000);
    document.addEventListener("visibilitychange", () => {
      if (document.visibilityState === "visible") {
        tick();
      }
    });
  };

  document.querySelectorAll("form.js-student-quiz").forEach((form) => bindTimer(form));
})();
