(() => {
  const getCsrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
    document.querySelector('input[name="\_token"]')?.value ||
    document.querySelector('input[name="_token"], input[name^="csrf"]')?.value ||
    "";

  const getUploadUrl = () =>
    document.body.dataset.listeningAudioUploadUrl || document.body.dataset.qbAudioUploadUrl || "";

  const getFetchUrl = () =>
    document.body.dataset.listeningAudioFetchUrl ||
    document.body.dataset.qbAudioFetchUrl ||
    "";

  const withCacheBust = (url, token = Date.now()) => {
    const safeUrl = String(url || "").trim();
    if (!safeUrl) return "";
    const joiner = safeUrl.includes("?") ? "&" : "?";
    return `${safeUrl}${joiner}v=${encodeURIComponent(String(token))}`;
  };

  const resolvePublicUrl = (data, options = {}) => {
    const path = String(data?.path || "").trim();
    let url = "";

    if (path) {
      if (/^https?:\/\//i.test(path) || path.startsWith("//")) {
        url = path;
      } else {
        const base = String(document.body.dataset.appBase || "").replace(/\/$/, "");
        url = base ? `${base}/${path.replace(/^\//, "")}` : path;
      }
    }

    if (!url) {
      url = String(data?.url || data?.location || "").trim();
    }

    if (url && options.bustCache) {
      return withCacheBust(url, options.bustCache === true ? Date.now() : options.bustCache);
    }

    return url;
  };

  const postFormWithProgress = (url, formData, onProgress) =>
    new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", url);
      xhr.withCredentials = true;
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      if (getCsrfToken()) xhr.setRequestHeader("X-CSRF-TOKEN", getCsrfToken());

      xhr.upload.addEventListener("progress", (event) => {
        if (event.lengthComputable && typeof onProgress === "function") {
          onProgress(Math.min(99, Math.round((event.loaded / event.total) * 100)));
        }
      });

      xhr.addEventListener("load", () => {
        let data = {};
        try {
          data = JSON.parse(xhr.responseText || "{}");
        } catch {
          reject(new Error("Không tải được MP3."));
          return;
        }

        if (xhr.status >= 200 && xhr.status < 300 && data.ok) {
          resolve(data);
          return;
        }

        reject(new Error(data.message || "Không tải được MP3."));
      });

      xhr.addEventListener("error", () => reject(new Error("Không tải được MP3.")));
      xhr.addEventListener("abort", () => reject(new Error("Đã hủy tải MP3.")));
      xhr.send(formData);
    });

  const postFormWithSimulatedProgress = (url, formData, onProgress) => {
    let progress = 4;
    let finished = false;

    if (typeof onProgress === "function") onProgress(progress);

    const timer = window.setInterval(() => {
      if (finished) return;
      progress = Math.min(92, progress + Math.random() * 7 + 2);
      onProgress(Math.round(progress));
    }, 350);

    return postFormWithProgress(url, formData, (pct) => {
      progress = Math.max(progress, pct);
      onProgress(Math.min(92, progress));
    })
      .then((data) => {
        finished = true;
        window.clearInterval(timer);
        onProgress(100);
        return data;
      })
      .catch((error) => {
        finished = true;
        window.clearInterval(timer);
        throw error;
      });
  };

  const bindMp3Upload = (root) => {
    if (!root || root.dataset.programMp3Bound === "1") return;
    root.dataset.programMp3Bound = "1";

    const savedInput = root.querySelector("[data-qb-audio-saved]");
    const urlInput = root.querySelector("[data-qb-audio-url]");
    const fileInput = root.querySelector("[data-qb-audio-file]");
    const fetchBtn = root.querySelector("[data-qb-audio-fetch]");
    const fetchStatus = root.querySelector("[data-qb-audio-fetch-status]");
    const previewWrap = root.querySelector("[data-qb-audio-preview-wrap]");
    const preview = root.querySelector("[data-qb-audio-preview]");
    const previewLabel = root.querySelector("[data-qb-audio-preview-label]");
    const clearBtn = root.querySelector("[data-qb-audio-clear]");

    let progressWrap = root.querySelector("[data-qb-audio-progress]");
    if (!progressWrap) {
      progressWrap = document.createElement("div");
      progressWrap.className = "qb-mp3-progress";
      progressWrap.dataset.qbAudioProgress = "";
      progressWrap.hidden = true;
      progressWrap.innerHTML =
        `<div class="qb-mp3-progress-head">` +
        `<span class="qb-mp3-progress-label" data-qb-audio-progress-label>Đang tải MP3...</span>` +
        `<span class="qb-mp3-progress-percent" data-qb-audio-progress-percent>0%</span>` +
        `</div>` +
        `<div class="qb-mp3-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">` +
        `<div class="qb-mp3-progress-fill" data-qb-audio-progress-fill></div>` +
        `</div>`;
      const anchor = previewWrap || root.querySelector("[data-qb-audio-fetch-status]") || root.lastElementChild;
      if (anchor) anchor.insertAdjacentElement("beforebegin", progressWrap);
      else root.appendChild(progressWrap);
    }

    const progressLabel = progressWrap.querySelector("[data-qb-audio-progress-label]");
    const progressFill = progressWrap.querySelector("[data-qb-audio-progress-fill]");
    const progressPercent = progressWrap.querySelector("[data-qb-audio-progress-percent]");
    const progressTrack = progressWrap.querySelector(".qb-mp3-progress-track");

    let uploadActive = false;
    let loadToken = 0;

    const setFetchStatus = (message, type = "") => {
      if (!fetchStatus) return;
      fetchStatus.textContent = message || "";
      fetchStatus.classList.remove("is-error", "is-success", "is-loading");
      if (type) fetchStatus.classList.add(type);
    };

    const setControlsDisabled = (disabled) => {
      if (fileInput) fileInput.disabled = disabled;
      if (fetchBtn) fetchBtn.disabled = disabled;
      if (urlInput) urlInput.disabled = disabled;
      if (clearBtn) clearBtn.disabled = disabled;
    };

    const showProgress = (label, percent = 0) => {
      uploadActive = true;
      hidePreview();
      progressWrap.hidden = false;
      progressWrap.classList.add("is-active");
      if (progressLabel) progressLabel.textContent = label;
      updateProgress(percent);
      setControlsDisabled(true);
    };

    const updateProgress = (percent) => {
      const safe = Math.max(0, Math.min(100, Number(percent) || 0));
      if (progressFill) progressFill.style.width = `${safe}%`;
      if (progressPercent) progressPercent.textContent = `${safe}%`;
      if (progressTrack) progressTrack.setAttribute("aria-valuenow", String(safe));
    };

    const hideProgress = () => {
      uploadActive = false;
      progressWrap.hidden = true;
      progressWrap.classList.remove("is-active");
      updateProgress(0);
      setControlsDisabled(false);
    };

    const revealPreviewWrap = (label = "MP3 đã tải") => {
      if (!previewWrap || !preview) return;
      previewWrap.hidden = false;
      previewWrap.removeAttribute("hidden");
      previewWrap.classList.remove("hidden", "is-hidden");
      preview.hidden = false;
      preview.removeAttribute("hidden");
      preview.controls = true;
      preview.preload = "auto";
      if (previewLabel) previewLabel.textContent = label;
    };

    const hidePreview = () => {
      if (!previewWrap || !preview) return;
      loadToken += 1;
      previewWrap.hidden = true;
      previewWrap.classList.add("is-hidden");
      preview.pause?.();
      preview.removeAttribute("src");
      preview.load?.();
      if (previewLabel) previewLabel.textContent = "MP3 đã tải";
    };

    const loadPreviewAudio = (url, label = "MP3 đã tải") =>
      new Promise((resolve) => {
        if (!previewWrap || !preview) {
          resolve(false);
          return;
        }

        const baseUrl = String(url || "").trim();
        if (!baseUrl) {
          resolve(false);
          return;
        }

        const token = ++loadToken;
        revealPreviewWrap(label);

        let attempt = 0;
        const maxAttempts = 4;

        const tryLoad = () => {
          if (token !== loadToken) {
            resolve(false);
            return;
          }

          attempt += 1;
          const srcUrl = withCacheBust(baseUrl, `${Date.now()}-${attempt}`);

          const cleanup = () => {
            preview.removeEventListener("canplaythrough", onReady);
            preview.removeEventListener("canplay", onReady);
            preview.removeEventListener("loadeddata", onReady);
            preview.removeEventListener("error", onError);
          };

          const onReady = () => {
            if (token !== loadToken) return;
            cleanup();
            resolve(true);
          };

          const onError = () => {
            if (token !== loadToken) return;
            cleanup();
            if (attempt < maxAttempts) {
              window.setTimeout(tryLoad, 350 * attempt);
              return;
            }
            resolve(false);
          };

          preview.addEventListener("canplaythrough", onReady, { once: true });
          preview.addEventListener("canplay", onReady, { once: true });
          preview.addEventListener("loadeddata", onReady, { once: true });
          preview.addEventListener("error", onError, { once: true });

          preview.pause?.();
          preview.src = srcUrl;
          preview.load?.();
        };

        tryLoad();
      });

    const showPreview = (url, label = "MP3 đã tải") => {
      loadPreviewAudio(url, label);
    };

    const revealPreview = async (url, label) => {
      updateProgress(100);
      if (progressLabel) progressLabel.textContent = "Đang chuẩn bị phát MP3...";
      const ok = await loadPreviewAudio(url, label);
      hideProgress();
      if (!ok && preview && url) {
        revealPreviewWrap(label);
        preview.src = withCacheBust(url);
        preview.load?.();
      }
      return ok;
    };

    const syncPreviewFromSaved = () => {
      if (uploadActive) return;

      const path = savedInput?.value.trim() || "";
      if (!path) {
        hidePreview();
        return;
      }

      const currentSrc = preview?.currentSrc || preview?.getAttribute("src") || "";
      if (currentSrc && !previewWrap?.hidden && !previewWrap?.classList.contains("is-hidden")) {
        return;
      }

      loadPreviewAudio(resolvePublicUrl({ path }), "MP3 hiện tại");
    };

    const applyUploadResult = async (data, label = "MP3 đã tải về") => {
      const csrfInput =
        document.querySelector('input[name="\_token"]') ||
        document.querySelector('input[name="_token"], input[name^="csrf"]');

      if (csrfInput && data.csrfHash) {
        csrfInput.value = data.csrfHash;
        if (data.csrfName) csrfInput.name = data.csrfName;
      }

      if (savedInput) savedInput.value = data.path || "";
      if (fileInput) fileInput.value = "";

      const publicUrl = resolvePublicUrl(data, { bustCache: Date.now() });
      await revealPreview(publicUrl, label);

      window.requestAnimationFrame(() => {
        if (savedInput) {
          savedInput.dispatchEvent(new Event("input", { bubbles: true }));
        }
      });
    };

    fileInput?.addEventListener("change", () => {
      const file = fileInput.files?.[0];
      if (!file) return;

      const uploadUrl = getUploadUrl();
      if (!uploadUrl) {
        setFetchStatus("Chưa cấu hình URL tải MP3.", "is-error");
        return;
      }

      setFetchStatus("");
      showProgress("Đang tải MP3 từ máy...", 0);

      const formData = new FormData();
      formData.append("audio", file);

      postFormWithProgress(uploadUrl, formData, updateProgress)
        .then(async (data) => {
          await applyUploadResult(data, "MP3 đã tải lên");
          setFetchStatus(data.message || "Đã tải MP3 lên hệ thống.", "is-success");
        })
        .catch((error) => {
          hideProgress();
          hidePreview();
          setFetchStatus(error.message || "Không tải được MP3.", "is-error");
        });
    });

    clearBtn?.addEventListener("click", () => {
      if (savedInput) {
        savedInput.value = "";
        savedInput.dispatchEvent(new Event("input", { bubbles: true }));
      }
      if (fileInput) fileInput.value = "";
      if (urlInput) urlInput.value = "";
      hideProgress();
      hidePreview();
      setFetchStatus("");
    });

    fetchBtn?.addEventListener("click", () => {
      const url = urlInput?.value.trim() || "";
      const fetchUrl = getFetchUrl();

      if (!url) {
        setFetchStatus("Vui lòng dán link MP3 trước.", "is-error");
        return;
      }

      if (!fetchUrl) {
        setFetchStatus("Chưa cấu hình API tải MP3.", "is-error");
        return;
      }

      setFetchStatus("");
      showProgress("Đang tải MP3 từ link...", 4);

      const formData = new FormData();
      formData.append("url", url);

      postFormWithSimulatedProgress(fetchUrl, formData, updateProgress)
        .then(async (data) => {
          await applyUploadResult(data, "MP3 đã tải về");
          setFetchStatus(data.message || "Đã tải MP3 về hệ thống.", "is-success");
        })
        .catch((error) => {
          hideProgress();
          hidePreview();
          setFetchStatus(error.message || "Không tải được MP3.", "is-error");
        });
    });

    syncPreviewFromSaved();
  };

  const bindIn = (root) => {
    (root || document)
      .querySelectorAll("[data-session-listening-mp3-upload], [data-qb-mp3-upload]")
      .forEach((block) => {
        bindMp3Upload(block);
      });
  };

  window.ProgramMp3Upload = { bind: bindMp3Upload, bindIn };
})();
