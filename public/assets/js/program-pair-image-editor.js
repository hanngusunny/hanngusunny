(() => {
  const getCsrfToken = () => {
    const fromMeta = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
    if (fromMeta) return fromMeta;

    return (
      document.querySelector('input[name="\_token"]')?.value ||
      document.querySelector('input[name="_token"], input[name^="csrf"]')?.value ||
      ""
    );
  };

  const getCsrfHeaders = () => {
    const headers = { "X-Requested-With": "XMLHttpRequest" };
    const token = getCsrfToken();
    if (token) headers["X-CSRF-TOKEN"] = token;
    return headers;
  };

  const getUploadUrl = () => document.body.dataset.pairImageUploadUrl || "";

  const getAppBase = () => (document.body.dataset.appBase || "").replace(/\/$/, "");

  const resolveMediaUrl = (value) => {
    const trimmed = String(value || "").trim().replace(/\\/g, "/");
    if (trimmed === "") return "";

    if (
      trimmed.startsWith("data:image/") ||
      trimmed.startsWith("blob:") ||
      trimmed.startsWith("offline-asset:")
    ) {
      return trimmed;
    }

    const uploads = trimmed.match(/(?:^|\/)(?:(?:\.\.\/)|(?:\.\/))*(?:public\/)?(uploads\/.+)$/i);
    if (uploads) {
      const base = getAppBase();
      return base ? `${base}/${uploads[1]}` : `/${uploads[1]}`;
    }

    if (/^https?:\/\//i.test(trimmed) || trimmed.startsWith("//")) {
      return trimmed;
    }

    if (trimmed.startsWith("/")) {
      const stripped = trimmed.replace(/^\/+(?:giaovienhanlvn|giaovienhan|public)\//i, "/");
      const base = getAppBase();
      if (base && stripped.startsWith("/uploads/")) {
        return `${base}${stripped}`;
      }
      return stripped;
    }

    const base = getAppBase();
    return base ? `${base}/${trimmed.replace(/^\.\//, "")}` : trimmed;
  };

  const toStoredPath = (value) => {
    const trimmed = String(value || "").trim();
    if (trimmed === "") return "";

    const base = getAppBase();
    if (base && trimmed.startsWith(base + "/")) {
      return trimmed.slice(base.length + 1);
    }

    return trimmed.replace(/^\.\//, "");
  };

  const uploadImageBlobOnline = (blob, filename) => {
    const uploadUrl = getUploadUrl();

    if (!uploadUrl) {
      return Promise.reject(new Error("Chưa cấu hình URL tải ảnh."));
    }

    const formData = new FormData();
    formData.append("image", blob, filename || "pair-image.png");

    return fetch(uploadUrl, {
      method: "POST",
      body: formData,
      headers: getCsrfHeaders(),
      credentials: "same-origin",
    })
      .then((response) => response.json().then((data) => ({ response, data })))
      .then(({ response, data }) => {
        if (!response.ok || !data.ok) {
          throw new Error(data.message || "Không tải được ảnh.");
        }

        const csrfInput =
          document.querySelector('input[name="\_token"]') ||
          document.querySelector('input[name="_token"], input[name^="csrf"]');

        if (csrfInput && data.csrfHash) {
          csrfInput.value = data.csrfHash;
          if (data.csrfName) csrfInput.name = data.csrfName;
        }

        return {
          path: data.path || "",
          url: data.url || data.location || "",
        };
      });
  };

  const isNetworkFailure = (error) => {
    if (window.AssessmentOffline?.isOffline?.()) {
      return true;
    }

    if (typeof navigator.onLine === "boolean" && !navigator.onLine) {
      return true;
    }

    return error instanceof TypeError;
  };

  const uploadImageBlob = (blob, filename) => {
    return uploadImageBlobOnline(blob, filename).catch((error) => {
      if (!isNetworkFailure(error) || !window.AssessmentOffline?.putBlob) {
        return Promise.reject(error);
      }

      return window.AssessmentOffline.putBlob("image", blob, filename || "image.png").then((stored) => {
        window.dispatchEvent(new Event("assessment:network-error"));
        return { path: stored.marker, url: stored.url };
      });
    });
  };

  const setStatus = (wrap, message, type = "") => {
    const status = wrap?.querySelector("[data-session-pair-image-status]");
    if (!status) return;

    status.textContent = message || "";
    status.classList.remove("is-uploading", "is-error", "is-success");

    if (type) status.classList.add(type);
  };

  const fillInputWithUpload = (input, result, onChange) => {
    const link = result.url || resolveMediaUrl(result.path);
    input.value = link;
    input.dispatchEvent(new Event("input", { bubbles: true }));
    if (typeof onChange === "function") onChange();
  };

  const uploadFileToInput = (file, input, wrap, onChange) => {
    if (!file || !file.type.startsWith("image/")) {
      setStatus(wrap, "Chỉ hỗ trợ file ảnh (JPG, PNG, GIF, WEBP).", "is-error");
      return Promise.resolve();
    }

    setStatus(wrap, "Đang lưu ảnh vào thư mục...", "is-uploading");

    return uploadImageBlob(file, file.name)
      .then((result) => {
        fillInputWithUpload(input, result, onChange);
        setStatus(wrap, "Đã lưu ảnh — link đã điền vào ô.", "is-success");
        window.setTimeout(() => setStatus(wrap, ""), 2500);
      })
      .catch((error) => {
        setStatus(wrap, error.message || "Không tải được ảnh.", "is-error");
      });
  };

  const dataUrlToFile = (dataUrl) => {
    const match = String(dataUrl).trim().match(/^data:(image\/[a-z0-9.+-]+);base64,([a-z0-9+/=\s]+)$/i);
    if (!match) return null;

    const mime = match[1].toLowerCase();
    const base64 = match[2].replace(/\s+/g, "");

    try {
      const binary = atob(base64);
      const bytes = new Uint8Array(binary.length);
      for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
      }
      const ext = mime.split("/")[1]?.replace("jpeg", "jpg") || "png";
      return new File([bytes], `pasted-image.${ext}`, { type: mime });
    } catch (error) {
      return null;
    }
  };

  const uploadDataUrlToInput = (dataUrl, input, wrap, onChange) => {
    const file = dataUrlToFile(dataUrl);
    if (!file) {
      setStatus(wrap, "Không đọc được ảnh dán (base64).", "is-error");
      return Promise.resolve();
    }

    return uploadFileToInput(file, input, wrap, onChange);
  };

  const readPasteImageDataUrl = (event) => {
    const clipboard = event.clipboardData;
    if (!clipboard) return "";

    const plain = clipboard.getData("text/plain")?.trim() || "";
    if (plain.startsWith("data:image/")) return plain;

    const html = clipboard.getData("text/html") || "";
    const htmlMatch = html.match(/src=["'](data:image\/[^"']+)["']/i);
    if (htmlMatch?.[1]) return htmlMatch[1];

    return "";
  };

  const maybeUploadInputDataUrl = (input, wrap, onChange) => {
    const value = input.value.trim();
    if (!value.startsWith("data:image/") || input.dataset.uploadingDataUrl === "1") {
      return false;
    }

    input.dataset.uploadingDataUrl = "1";
    uploadDataUrlToInput(value, input, wrap, onChange).finally(() => {
      delete input.dataset.uploadingDataUrl;
    });

    return true;
  };

  const readPasteImageFile = (event) => {
    const items = event.clipboardData?.items;
    if (!items) return null;

    for (const item of items) {
      if (item.type.startsWith("image/")) {
        return item.getAsFile();
      }
    }

    return null;
  };

  const IMAGE_INPUT_SELECTOR =
    ".session-pair-image-url, [data-session-memory-pair-image], [data-session-pair-slot-image-value], [data-session-image-hotspot-image], [data-session-image-label-drop-image]";

  const bindWrap = (wrap, onChange) => {
    if (!wrap || wrap.dataset.pairImageInputReady === "1") return;

    const input = wrap.querySelector(IMAGE_INPUT_SELECTOR);
    const fileInput = wrap.querySelector(".session-pair-image-file");

    if (!input) return;

    wrap.dataset.pairImageInputReady = "1";

    const handlePaste = (event) => {
      const file = readPasteImageFile(event);
      if (file) {
        event.preventDefault();
        uploadFileToInput(file, input, wrap, onChange);
        return;
      }

      const dataUrl = readPasteImageDataUrl(event);
      if (dataUrl) {
        event.preventDefault();
        uploadDataUrlToInput(dataUrl, input, wrap, onChange);
      }
    };

    wrap.addEventListener("paste", handlePaste);
    input.addEventListener("paste", handlePaste);

    input.addEventListener("input", () => {
      if (maybeUploadInputDataUrl(input, wrap, onChange)) return;
      if (typeof onChange === "function") onChange();
    });

    fileInput?.addEventListener("change", () => {
      const file = fileInput.files?.[0];
      fileInput.value = "";
      if (!file) return;
      uploadFileToInput(file, input, wrap, onChange);
    });
  };

  const init = (target, onChange) => {
    const wrap =
      target?.matches?.("[data-session-pair-image-wrap]")
        ? target
        : target?.closest?.("[data-session-pair-image-wrap]") ||
          (target?.classList?.contains("session-pair-image-url") ||
          target?.hasAttribute?.("data-session-memory-pair-image") ||
          target?.hasAttribute?.("data-session-pair-slot-image-value") ||
          target?.hasAttribute?.("data-session-image-hotspot-image") ||
          target?.hasAttribute?.("data-session-image-label-drop-image")
            ? (() => {
                const existing = target.closest("[data-session-pair-image-wrap]");
                if (existing) return existing;

                const created = document.createElement("div");
                created.className = "session-pair-image-input-wrap";
                created.dataset.sessionPairImageWrap = "";
                created.innerHTML =
                  `<p class="session-pair-image-hint" data-session-pair-image-status aria-live="polite"></p>`;

                const parent = target.parentElement;
                if (!parent) return null;

                parent.insertBefore(created, target);
                created.insertBefore(target, created.firstChild);

                if (!target.classList.contains("session-pair-image-url")) {
                  target.classList.add("session-pair-image-url");
                }

                return created;
              })()
            : null);

    if (!wrap) return;

    bindWrap(wrap, onChange);
  };

  const destroy = (target) => {
    const wrap = target?.closest?.("[data-session-pair-image-wrap]");
    if (!wrap) return;

    delete wrap.dataset.pairImageInputReady;
  };

  const getValue = (target) => {
    const input = target?.matches?.(IMAGE_INPUT_SELECTOR)
        ? target
        : target?.querySelector?.(IMAGE_INPUT_SELECTOR);

    if (!input) return "";

    return toStoredPath(input.value.trim());
  };

  const setValue = (target, url) => {
    const input = target?.matches?.(IMAGE_INPUT_SELECTOR)
        ? target
        : target?.querySelector?.(IMAGE_INPUT_SELECTOR);

    if (!input) return;

    input.value = url ? resolveMediaUrl(url) : "";
  };

  const saveAll = () => {};

  const initIn = (root, onChange) => {
    (root || document).querySelectorAll("[data-session-pair-image-wrap]").forEach((wrap) => {
      bindWrap(wrap, onChange);
    });

    (root || document)
      .querySelectorAll(
        "[data-session-memory-pair-image]:not([data-session-pair-image-wrap] *), [data-session-pair-slot-image-value]:not([data-session-pair-image-wrap] *), [data-session-image-hotspot-image]:not([data-session-pair-image-wrap] *), [data-session-image-label-drop-image]:not([data-session-pair-image-wrap] *)"
      )
      .forEach((input) => init(input, onChange));
  };

  const destroyIn = (root) => {
    (root || document).querySelectorAll("[data-session-pair-image-wrap]").forEach((wrap) => {
      delete wrap.dataset.pairImageInputReady;
    });
  };

  const renderFieldHtml = (attrs = "", value = "") => {
    const safeValue = String(value).replace(/"/g, "&quot;");
    const attrStr = attrs ? " " + attrs.trim() : "";

    return (
      `<div class="session-pair-image-input-wrap" data-session-pair-image-wrap>` +
      `<div class="session-pair-image-input-row">` +
      `<input type="text" class="form-control form-control-sm session-pair-image-url"${attrStr}` +
      ` value="${safeValue}" placeholder="Dán ảnh (Ctrl+V) — tự lưu file và điền link">` +
      `<label class="btn btn-outline btn-sm session-pair-image-file-btn" title="Chọn ảnh từ máy">` +
      `<i class="bi bi-upload" aria-hidden="true"></i>` +
      `<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="session-pair-image-file" hidden>` +
      `</label>` +
      `</div>` +
      `<p class="session-pair-image-hint" data-session-pair-image-status aria-live="polite"></p>` +
      `</div>`
    );
  };

  /** Cấu hình TinyMCE: dán/chèn ảnh → upload server → chèn link. */
  const getTinyMceUploadConfig = () => {
    if (!getUploadUrl()) return {};

    return {
      paste_data_images: true,
      automatic_uploads: true,
      images_upload_handler: (blobInfo, progress) =>
        uploadImageBlob(blobInfo.blob(), blobInfo.filename()).then((result) => {
          progress(100);
          return result.url || resolveMediaUrl(result.path);
        }),
    };
  };

  window.ProgramPairImageEditor = {
    init,
    destroy,
    initIn,
    destroyIn,
    getValue,
    setValue,
    saveAll,
    uploadImageBlob,
    uploadImageBlobOnline,
    resolveMediaUrl,
    renderFieldHtml,
    getTinyMceUploadConfig,
  };
})();
