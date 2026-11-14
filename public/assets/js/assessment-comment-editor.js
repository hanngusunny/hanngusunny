(() => {
  const textareas = [...document.querySelectorAll(".js-assessment-comment-editor")];

  if (typeof tinymce === "undefined" || textareas.length === 0) {
    return;
  }

  const tinymceBaseUrl = document.body.dataset.tinymceBase || "/assets/tinymce";

  textareas.forEach((textarea) => {
    const compact = textarea.classList.contains("js-assessment-comment-editor--compact");

    if (!textarea.id) {
      textarea.id = `assessment-comment-${Math.random().toString(36).slice(2, 10)}`;
    }

    tinymce.init({
      base_url: tinymceBaseUrl,
      suffix: ".min",
      language: "vi",
      language_url: tinymceBaseUrl + "/langs/vi.js",
      selector: "#" + CSS.escape(textarea.id),
      height: compact ? 160 : 240,
      min_height: compact ? 120 : 180,
      menubar: false,
      plugins: compact ? "lists link" : "lists link autoresize code",
      toolbar: compact
        ? "bold italic underline | bullist numlist | link | removeformat"
        : "undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | removeformat | code",
      block_formats: "Paragraph=p; Heading 2=h2; Heading 3=h3",
      content_style:
        "body{font-family:'Inter Tight',ui-sans-serif,system-ui,sans-serif;font-size:15px;line-height:1.65;color:#1f2937}",
      branding: false,
      promotion: false,
      statusbar: !compact,
      resize: !compact,
      autosave_ask_before_unload: false,
      setup: (editor) => {
        editor.on("change input undo redo keyup", () => {
          editor.save();
          const el = editor.getElement();

          if (el) {
            el.dispatchEvent(new Event("input", { bubbles: true }));
          }
        });
      },
    });
  });

  document.querySelectorAll("form[data-assessment-autosave]").forEach((form) => {
    form.addEventListener("submit", () => {
      tinymce.triggerSave();
    });
  });

  document.querySelectorAll(".js-assessment-comment-editor").forEach((textarea) => {
    const form = textarea.closest("form");

    if (!form || form.dataset.assessmentAutosave !== undefined) {
      return;
    }

    form.addEventListener("submit", () => {
      tinymce.triggerSave();
    });
  });
})();
