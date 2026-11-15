(() => {
  const textareas = [...document.querySelectorAll(".session-quiz-essay-input")];

  if (textareas.length === 0 || typeof tinymce === "undefined") return;

  const tinymceBaseUrl = document.body.dataset.tinymceBase || "/assets/tinymce";
  const tinymceImageUpload = window.ProgramPairImageEditor?.getTinyMceUploadConfig?.() || {};

  const escapeAttr = (value) =>
    String(value)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");

  const insertImageFiles = async (editor, fileList) => {
    const uploader = window.ProgramPairImageEditor;
    const files = [...(fileList || [])].filter((file) => file.type && file.type.startsWith("image/"));

    if (!files.length) {
      return;
    }

    if (!uploader?.uploadImageBlob) {
      editor.notificationManager.open({
        text: "Chưa cấu hình tải ảnh.",
        type: "error",
      });
      return;
    }

    editor.setProgressState(true);

    try {
      for (const file of files) {
        const result = await uploader.uploadImageBlob(file, file.name);
        const url = result.url || uploader.resolveMediaUrl(result.path);

        if (!url) {
          continue;
        }

        const alt = escapeAttr((file.name || "ảnh").replace(/\.[^.]+$/, ""));
        editor.insertContent(`<p><img src="${escapeAttr(url)}" alt="${alt}"></p>`);
      }

      editor.save();
      const el = editor.getElement();

      if (el) {
        el.dispatchEvent(new Event("input", { bubbles: true }));
        el.dispatchEvent(new Event("change", { bubbles: true }));
      }
    } catch (error) {
      editor.notificationManager.open({
        text: error.message || "Không tải được ảnh.",
        type: "error",
      });
    } finally {
      editor.setProgressState(false);
    }
  };

  const openMultiImagePicker = (editor) => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/jpeg,image/png,image/gif,image/webp";
    input.multiple = true;
    input.addEventListener("change", () => {
      insertImageFiles(editor, input.files);
    });
    input.click();
  };

  const editorConfig = {
    base_url: tinymceBaseUrl,
    suffix: ".min",
    language: "vi",
    language_url: tinymceBaseUrl + "/langs/vi.js",
    relative_urls: false,
    remove_script_host: false,
    convert_urls: false,
    height: 220,
    min_height: 160,
    menubar: false,
    plugins: "lists link image autoresize code",
    toolbar:
      "undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link essayimages | removeformat | code",
    block_formats: "Paragraph=p; Heading 2=h2; Heading 3=h3",
    branding: false,
    promotion: false,
    statusbar: true,
    resize: true,
    paste_as_text: false,
    autosave_ask_before_unload: false,
    file_picker_types: "image",
    content_style:
      'body{font-family:Google Sans Flex,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:15px;line-height:1.65;color:#1f2937} img{max-width:100%;height:auto;}',
    setup: (editor) => {
      editor.ui.registry.addButton("essayimages", {
        icon: "gallery",
        tooltip: "Chèn ảnh (có thể chọn nhiều file)",
        onAction: () => openMultiImagePicker(editor),
      });

      editor.on("drop", (event) => {
        const files = [...(event.dataTransfer?.files || [])].filter(
          (file) => file.type && file.type.startsWith("image/")
        );

        if (!files.length) {
          return;
        }

        event.preventDefault();
        insertImageFiles(editor, files);
      });

      editor.on("change input undo redo keyup", () => {
        editor.save();
        const el = editor.getElement();

        if (el) {
          el.dispatchEvent(new Event("input", { bubbles: true }));
          el.dispatchEvent(new Event("change", { bubbles: true }));
        }
      });
    },
    file_picker_callback: (callback, value, meta) => {
      if (meta.filetype !== "image") {
        return;
      }

      const input = document.createElement("input");
      input.type = "file";
      input.accept = "image/jpeg,image/png,image/gif,image/webp";
      input.multiple = true;
      input.addEventListener("change", async () => {
        const files = [...(input.files || [])].filter(
          (file) => file.type && file.type.startsWith("image/")
        );

        if (!files.length || !window.ProgramPairImageEditor?.uploadImageBlob) {
          return;
        }

        try {
          const first = files[0];
          const firstResult = await window.ProgramPairImageEditor.uploadImageBlob(first, first.name);
          const firstUrl =
            firstResult.url || window.ProgramPairImageEditor.resolveMediaUrl(firstResult.path);

          if (firstUrl) {
            callback(firstUrl, { alt: first.name.replace(/\.[^.]+$/, "") });
          }

          const rest = files.slice(1);

          if (rest.length && tinymce.activeEditor) {
            await insertImageFiles(tinymce.activeEditor, rest);
          }
        } catch (error) {
          tinymce.activeEditor?.notificationManager.open({
            text: error.message || "Không tải được ảnh.",
            type: "error",
          });
        }
      });
      input.click();
    },
    ...tinymceImageUpload,
  };

  textareas.forEach((textarea) => {
    if (!textarea.id) {
      textarea.id = `session-quiz-essay-${Math.random().toString(36).slice(2, 10)}`;
    }

    tinymce.init({
      ...editorConfig,
      selector: "#" + CSS.escape(textarea.id),
    });
  });

  document.querySelectorAll(".session-quiz-form").forEach((form) => {
    form.addEventListener("submit", () => {
      if (typeof tinymce !== "undefined") {
        tinymce.triggerSave();
      }
    });
  });
})();
