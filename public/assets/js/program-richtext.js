(() => {
  const SELECTOR = "textarea.js-richtext";

  const getBase = () => String(document.body.dataset.tinymceBase || "").replace(/\/$/, "");

  const ensureId = (textarea) => {
    if (!textarea.id) {
      textarea.id = "richtext-" + Date.now() + "-" + Math.random().toString(36).slice(2, 8);
    }
    return textarea.id;
  };

  const isHiddenTemplate = (textarea) => {
    if (textarea.closest("[hidden], template")) {
      return true;
    }

    let node = textarea.parentElement;
    while (node) {
      const style = window.getComputedStyle(node);
      if (style.display === "none" && node.id && node.id.endsWith("-tpl")) {
        return true;
      }
      node = node.parentElement;
    }

    return false;
  };

  const contentStyle = [
    'body { font-family: "Inter Tight", ui-sans-serif, system-ui, sans-serif; font-size: 15px; line-height: 1.65; color: #0f172a; padding: 12px 16px; }',
    "h1,h2,h3,h4 { font-weight: 650; line-height: 1.3; margin: 0 0 .6em; }",
    "p { margin: 0 0 .75em; }",
    "a { color: #c2410c; }",
    "video { max-width: 100%; width: 100%; border-radius: 12px; background: #0f172a; }",
    "iframe { width: 100%; aspect-ratio: 16/9; border: 0; border-radius: 12px; }",
    '.session-pre-drive-video iframe, iframe[src*="drive.google.com"] { width: 100%; max-width: 100%; display: block; margin: 12px 0; }',
    '@media (min-width: 768px) { .session-pre-drive-video iframe, iframe[src*="drive.google.com"] { width: 80%; max-width: 80%; margin: 12px auto; } }',
    "table { border-collapse: collapse; width: 100%; }",
    "table td, table th { border: 1px solid #e2e8f0; padding: 8px; }",
    "img { max-width: 100%; height: auto; border-radius: 8px; }",
  ].join(" ");

  const fullToolbar =
    "undo redo | blocks fontsize | bold italic underline strikethrough | " +
    "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | " +
    "forecolor backcolor removeformat | link image uploadvideo embedgoogledrive media table codesample | " +
    "charmap emoticons | preview code fullscreen help";

  const compactToolbar =
    "undo redo | blocks | bold italic underline | bullist numlist | " +
    "alignleft aligncenter alignright | forecolor | link image uploadvideo embedgoogledrive media table | " +
    "removeformat | code fullscreen";

  const buildConfig = (textarea) => {
    const height = Number(textarea.dataset.editorHeight || 320);
    const compact = textarea.dataset.editorCompact === "1";
    const readonly = Boolean(textarea.disabled || textarea.readOnly);
    const imageCfg = window.ProgramPairImageEditor?.getTinyMceUploadConfig?.() || {};
    const videoCfg = window.SunnyPreSessionVideo?.getTinyMceUploadConfig?.() || {};

    return {
      target: textarea,
      base_url: getBase(),
      suffix: ".min",
      height,
      min_height: Math.min(220, height),
      menubar: compact ? false : "file edit view insert format tools table help",
      language: "vi",
      language_url: getBase() + "/langs/vi.js",
      plugins: [
        "advlist",
        "autolink",
        "lists",
        "link",
        "image",
        "charmap",
        "preview",
        "anchor",
        "searchreplace",
        "visualblocks",
        "code",
        "fullscreen",
        "insertdatetime",
        "media",
        "table",
        "help",
        "wordcount",
        "codesample",
        "quickbars",
        "emoticons",
      ].join(" "),
      toolbar: compact ? compactToolbar : fullToolbar,
      quickbars_selection_toolbar: "bold italic underline | forecolor | link",
      quickbars_insert_toolbar: false,
      branding: false,
      promotion: false,
      resize: true,
      statusbar: true,
      paste_as_text: false,
      convert_urls: false,
      relative_urls: false,
      remove_script_host: false,
      sandbox_iframes: false,
      convert_unsafe_embeds: false,
      content_style: contentStyle,
      readonly,
      ...imageCfg,
      ...videoCfg,
      setup(editor) {
        window.SunnyPreSessionVideo?.registerEditorVideoButtons?.(editor);
        editor.on("change input undo redo", () => editor.save());
      },
    };
  };

  const init = (root) => {
    if (typeof tinymce === "undefined") {
      return;
    }

    const scope = root || document;
    scope.querySelectorAll(SELECTOR).forEach((textarea) => {
      if (textarea.dataset.editorReady === "1" || isHiddenTemplate(textarea)) {
        return;
      }

      ensureId(textarea);
      textarea.dataset.editorReady = "1";
      tinymce.init(buildConfig(textarea));
    });
  };

  const destroy = (root) => {
    if (typeof tinymce === "undefined") {
      return;
    }

    const scope = root || document;
    scope.querySelectorAll(SELECTOR).forEach((textarea) => {
      if (!textarea.id) return;
      const editor = tinymce.get(textarea.id);
      if (editor) {
        editor.save();
        editor.remove();
      }
      delete textarea.dataset.editorReady;
    });
  };

  const saveAll = () => {
    if (typeof tinymce !== "undefined") {
      tinymce.triggerSave();
    }
  };

  const widenDrawerIfNeeded = (root) => {
    const panel = document.getElementById("drawer-panel");
    if (!panel) return;
    panel.classList.toggle("is-wide-editor", Boolean(root?.querySelector(SELECTOR)));
  };

  const hookDrawer = () => {
    if (!window.TemplateUI || window.TemplateUI.__richtextHooked) {
      return;
    }

    window.TemplateUI.__richtextHooked = true;
    const originalClose = window.TemplateUI.closeDrawer.bind(window.TemplateUI);

    window.TemplateUI.closeDrawer = () => {
      const body = document.getElementById("drawer-body");
      if (body) destroy(body);
      document.getElementById("drawer-panel")?.classList.remove("is-wide-editor");
      return originalClose();
    };
  };

  document.addEventListener(
    "submit",
    (event) => {
      if (event.target?.matches?.("form")) {
        saveAll();
      }
    },
    true
  );

  const boot = () => {
    hookDrawer();
    init(document);
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }

  window.ProgramRichtext = { init, destroy, saveAll, widenDrawerIfNeeded };
})();
