(() => {
  const getCsrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
    document.querySelector('input[name="_token"]')?.value ||
    document.querySelector('input[name^="csrf"]')?.value ||
    "";

  const getUploadUrl = () => document.body.dataset.preVideoUploadUrl || "";

  const resolvePublicUrl = (raw) => {
    const value = String(raw || "").trim();
    if (value === "") return "";
    if (/^https?:\/\//i.test(value) || value.startsWith("//")) return value;
    const base = String(document.body.dataset.appBase || "").replace(/\/$/, "");
    return base ? `${base}/${value.replace(/^\//, "")}` : value;
  };

  const parseGoogleDriveFileId = (raw) => {
    const value = String(raw || "").trim();
    if (value === "") return "";

    const iframeMatch = value.match(/<iframe[^>]+src=["']([^"']+)["']/i);
    const candidate = iframeMatch?.[1] ? iframeMatch[1] : value;

    const patterns = [
      /\/file\/d\/([a-zA-Z0-9_-]+)/,
      /\/d\/([a-zA-Z0-9_-]+)/,
      /[?&]id=([a-zA-Z0-9_-]+)/,
    ];

    for (const pattern of patterns) {
      const match = candidate.match(pattern);
      if (match?.[1]) return match[1];
    }

    if (/^[a-zA-Z0-9_-]{10,}$/.test(candidate)) {
      return candidate;
    }

    return "";
  };

  const buildGoogleDriveEmbedHtml = (raw) => {
    const fileId = parseGoogleDriveFileId(raw);
    if (!fileId) return "";

    const src = `https://drive.google.com/file/d/${encodeURIComponent(fileId)}/preview`;

    return (
      '<p class="session-pre-drive-video">' +
      `<iframe src="${src}" ` +
      'allow="autoplay; encrypted-media; fullscreen" allowfullscreen loading="lazy" ' +
      'referrerpolicy="no-referrer-when-downgrade" ' +
      'style="aspect-ratio:16/9;border:0;border-radius:12px;display:block;"></iframe>' +
      "</p>"
    );
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
          reject(new Error("Không tải được video."));
          return;
        }

        if (xhr.status >= 200 && xhr.status < 300 && data.ok) {
          resolve(data);
          return;
        }

        reject(new Error(data.message || "Không tải được video."));
      });

      xhr.addEventListener("error", () => reject(new Error("Không tải được video.")));
      xhr.addEventListener("abort", () => reject(new Error("Đã hủy tải video.")));
      xhr.send(formData);
    });

  const uploadVideoBlob = (file, onProgress) => {
    const uploadUrl = getUploadUrl();

    if (!uploadUrl) {
      return Promise.reject(new Error("Chưa cấu hình URL tải video. Lưu buổi học trước khi tải lên."));
    }

    if (!file) {
      return Promise.reject(new Error("Không có file video."));
    }

    const formData = new FormData();
    formData.append("video", file);
    const csrf = getCsrfToken();
    if (csrf) formData.append("_token", csrf);

    return postFormWithProgress(uploadUrl, formData, onProgress).then((data) => ({
      path: String(data.path || "").trim(),
      url: String(data.url || data.location || "").trim(),
    }));
  };

  const buildVideoInsertHtml = (rawUrl) => {
    const url = resolvePublicUrl(rawUrl).replace(/"/g, "&quot;");
    if (!url) return "";

    return `<p><video controls preload="metadata" playsinline style="max-width:100%;width:100%;" src="${url}"></video></p>`;
  };

  const pickVideoFile = () =>
    new Promise((resolve) => {
      const input = document.createElement("input");
      input.type = "file";
      input.accept = "video/mp4,video/webm,video/quicktime,video/ogg,.mp4,.webm,.mov,.m4v";
      input.addEventListener("change", () => resolve(input.files?.[0] || null));
      input.click();
    });

  const insertUploadedVideo = async (editor) => {
    const file = await pickVideoFile();
    if (!file) return;

    try {
      const result = await uploadVideoBlob(file);
      const storedUrl = result.path !== "" ? result.path : result.url;
      const html = buildVideoInsertHtml(storedUrl);

      if (!html) {
        throw new Error("Không tạo được video sau khi tải lên.");
      }

      editor.insertContent(html);
      editor.fire("change");
    } catch (error) {
      window.alert(error instanceof Error ? error.message : "Không tải được video.");
    }
  };

  const openGoogleDriveDialog = (editor) => {
    editor.windowManager.open({
      title: "Chèn video Google Drive",
      size: "medium",
      body: {
        type: "panel",
        items: [
          {
            type: "textarea",
            name: "url",
            label: "Link chia sẻ hoặc mã nhúng",
            placeholder: "https://drive.google.com/file/d/.../view?usp=sharing",
          },
          {
            type: "htmlpanel",
            html:
              '<p style="margin:8px 0 0;font-size:12px;line-height:1.5;color:#64748b;">' +
              "Trên Google Drive: mở video → <strong>Chia sẻ</strong> → quyền <strong>Bất kỳ ai có liên kết</strong> (Người xem). " +
              "Dán link chia sẻ hoặc iframe embed vào ô trên." +
              "</p>",
          },
        ],
      },
      buttons: [
        { type: "cancel", text: "Hủy" },
        { type: "submit", text: "Chèn video", primary: true },
      ],
      onSubmit: (api) => {
        const html = buildGoogleDriveEmbedHtml(api.getData().url || "");

        if (!html) {
          editor.windowManager.alert(
            "Không nhận dạng được link Google Drive. Hãy dán link dạng drive.google.com/file/d/..."
          );
          return;
        }

        editor.insertContent(html);
        editor.fire("change");
        api.close();
      },
    });
  };

  const registerEditorUploadButton = (editor) => {
    if (!editor?.ui?.registry || editor.__uploadVideoButtonRegistered) {
      return;
    }

    editor.__uploadVideoButtonRegistered = true;
    editor.ui.registry.addButton("uploadvideo", {
      icon: "embed",
      tooltip: "Tải video lên",
      onAction: () => insertUploadedVideo(editor),
    });
  };

  const registerGoogleDriveButton = (editor) => {
    if (!editor?.ui?.registry || editor.__googleDriveButtonRegistered) {
      return;
    }

    editor.__googleDriveButtonRegistered = true;
    editor.ui.registry.addButton("embedgoogledrive", {
      icon: "browse",
      tooltip: "Chèn video Google Drive",
      onAction: () => openGoogleDriveDialog(editor),
    });
  };

  const registerEditorVideoButtons = (editor) => {
    registerEditorUploadButton(editor);
    registerGoogleDriveButton(editor);
  };

  const getTinyMceUploadConfig = () => {
    const config = {
      extended_valid_elements:
        "iframe[src|width|height|frameborder|allow|allowfullscreen|referrerpolicy|loading|style|class]," +
        "video[src|controls|preload|playsinline|width|height|style|class|poster]," +
        "source[src|type]",
      media_live_embeds: true,
      sandbox_iframes: false,
      convert_unsafe_embeds: false,
      media_url_resolver: (data, resolve) => {
        const driveHtml = buildGoogleDriveEmbedHtml(data?.url || data?.embed || "");
        if (driveHtml) {
          resolve({ html: driveHtml });
          return;
        }

        resolve({ html: "" });
      },
    };

    if (!getUploadUrl()) {
      return config;
    }

    return Object.assign(config, {
      file_picker_callback: (callback, value, meta) => {
        if (meta.filetype !== "media" && meta.filetype !== "file") {
          return;
        }

        pickVideoFile().then((file) => {
          if (!file) return;

          uploadVideoBlob(file)
            .then((result) => {
              const storedUrl = result.path !== "" ? result.path : result.url;
              callback(resolvePublicUrl(storedUrl), { alt: file.name, poster: "" });
            })
            .catch((error) => {
              window.alert(error instanceof Error ? error.message : "Không tải được video.");
            });
        });
      },
    });
  };

  window.SunnyPreSessionVideo = {
    uploadVideoBlob,
    parseGoogleDriveFileId,
    buildGoogleDriveEmbedHtml,
    getTinyMceUploadConfig,
    registerEditorUploadButton,
    registerGoogleDriveButton,
    registerEditorVideoButtons,
  };
})();
