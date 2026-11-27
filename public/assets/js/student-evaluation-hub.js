(() => {
  "use strict";

  /* =========================================================================
     1. Dropdown Chọn tuần chuẩn Figma (#714:27299)
     ========================================================================= */
  const initWeekPicker = () => {
    const root = document.querySelector("[data-sn-week-picker]");
    const trigger = document.getElementById("snEvalWeekTrigger");
    const popover = document.getElementById("snEvalWeekPopover");
    const list = document.getElementById("snEvalWeekList");

    if (!root || !trigger || !popover) {
      return;
    }

    const setOpen = (open) => {
      popover.hidden = !open;
      trigger.classList.toggle("is-open", open);
      trigger.setAttribute("aria-expanded", open ? "true" : "false");

      if (open && list) {
        const selected = list.querySelector("[data-sn-week-selected]");
        if (selected instanceof HTMLElement) {
          selected.scrollIntoView({ block: "nearest" });
        }
      }
    };

    trigger.addEventListener("click", () => {
      setOpen(popover.hidden);
    });

    document.addEventListener("click", (event) => {
      if (!popover.hidden && !root.contains(event.target)) {
        setOpen(false);
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !popover.hidden) {
        setOpen(false);
        trigger.focus();
      }
    });
  };

  /* =========================================================================
     2. Lọc nhanh danh sách Kết quả (#710:76989 / #717:25669)
     ========================================================================= */
  const initResultsFilter = () => {
    const root = document.querySelector("[data-sn-results-root]");
    if (!root) {
      return;
    }

    const filterBtns = [...root.querySelectorAll("[data-sn-filter]")];
    const rows = [...root.querySelectorAll("[data-sn-res-item]")];
    const well = document.getElementById("snResWell");
    const empty = document.getElementById("snResEmpty");

    const applyFilter = (key, pushUrl = true) => {
      let shown = 0;

      filterBtns.forEach((btn) => {
        const active = btn.dataset.snFilter === key;
        btn.classList.toggle("is-active", active);
        btn.setAttribute("aria-selected", active ? "true" : "false");
      });

      rows.forEach((row) => {
        const match = key === "all" || row.dataset.resType === key;
        row.hidden = !match;
        if (match) {
          shown++;
        }

        const baseUrl = row.dataset.baseUrl;
        if (baseUrl) {
          try {
            const url = new URL(baseUrl, window.location.origin);
            if (key !== "all") {
              url.searchParams.set("loc", key);
            } else {
              url.searchParams.delete("loc");
            }
            row.setAttribute("href", url.pathname + url.search);
          } catch (_error) {
            // ignore
          }
        }
      });

      if (well) {
        well.hidden = shown === 0;
      }
      if (empty) {
        empty.hidden = shown > 0;
      }

      if (pushUrl && window.history?.replaceState) {
        try {
          const cur = new URL(window.location.href);
          cur.searchParams.set("tab", "ket-qua");
          if (key !== "all") {
            cur.searchParams.set("loc", key);
          } else {
            cur.searchParams.delete("loc");
          }
          window.history.replaceState({}, "", cur.pathname + cur.search);
        } catch (_error) {
          // ignore
        }
      }
    };

    filterBtns.forEach((btn) => {
      btn.addEventListener("click", () => {
        applyFilter(btn.dataset.snFilter || "all", true);
      });
    });
  };

  /* =========================================================================
     3. Chi tiết kết quả: Nhảy nhanh tới câu hỏi & Audio Player
     ========================================================================= */
  const initResultDetail = () => {
    document.querySelectorAll("[data-sn-res-jump]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const targetId = btn.getAttribute("data-sn-res-jump");
        const target = targetId ? document.getElementById(targetId) : null;
        if (target) {
          target.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    });

    const players = [...document.querySelectorAll("[data-sn-audio-player]")];
    const formatSec = (sec) => {
      const s = Math.max(0, Math.floor(sec || 0));
      const m = Math.floor(s / 60);
      const r = s % 60;
      return String(m).padStart(2, "0") + ":" + String(r).padStart(2, "0");
    };

    players.forEach((player) => {
      const audio = player.querySelector("[data-audio-el]");
      const toggle = player.querySelector("[data-audio-toggle]");
      const playIco = player.querySelector(".ico-play");
      const pauseIco = player.querySelector(".ico-pause");
      const track = player.querySelector("[data-audio-track]");
      const fill = player.querySelector("[data-audio-fill]");
      const timeEl = player.querySelector("[data-audio-time]");

      if (!(audio instanceof HTMLAudioElement) || !toggle) {
        return;
      }

      const syncUi = () => {
        const playing = !audio.paused && !audio.ended;
        if (playIco) playIco.hidden = playing;
        if (pauseIco) pauseIco.hidden = !playing;
        const dur = Number.isFinite(audio.duration) ? audio.duration : 0;
        const cur = audio.currentTime || 0;
        if (fill) {
          fill.style.width = dur > 0 ? `${Math.min(100, (cur / dur) * 100)}%` : "0%";
        }
        if (timeEl) {
          timeEl.textContent = dur > 0 ? `${formatSec(cur)} / ${formatSec(dur)}` : formatSec(cur);
        }
      };

      toggle.addEventListener("click", () => {
        if (audio.paused) {
          players.forEach((other) => {
            if (other === player) return;
            const otherAudio = other.querySelector("[data-audio-el]");
            if (otherAudio instanceof HTMLAudioElement && !otherAudio.paused) {
              otherAudio.pause();
            }
          });
          audio.play().catch(() => {});
        } else {
          audio.pause();
        }
      });

      track?.addEventListener("click", (event) => {
        if (!Number.isFinite(audio.duration) || audio.duration <= 0) {
          return;
        }
        const rect = track.getBoundingClientRect();
        const ratio = Math.max(0, Math.min(1, (event.clientX - rect.left) / Math.max(1, rect.width)));
        audio.currentTime = ratio * audio.duration;
        syncUi();
      });

      audio.addEventListener("loadedmetadata", syncUi);
      audio.addEventListener("timeupdate", syncUi);
      audio.addEventListener("play", syncUi);
      audio.addEventListener("pause", syncUi);
      audio.addEventListener("ended", syncUi);
    });
  };

  const boot = () => {
    initWeekPicker();
    initResultsFilter();
    initResultDetail();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
