(() => {
  const form = document.querySelector(".rules-form");
  if (!form) return;

  const tinymceBaseUrl = document.body.dataset.tinymceBase || "/assets/tinymce";

  if (typeof tinymce !== "undefined") {
    tinymce.init({
      base_url: tinymceBaseUrl,
      suffix: ".min",
      language: "vi",
      language_url: tinymceBaseUrl + "/langs/vi.js",
      selector: ".js-rules-editor",
      height: 480,
      min_height: 320,
      menubar: false,
      plugins: "lists link table code autoresize",
      toolbar:
        "undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | link table | removeformat | code",
      block_formats: "Paragraph=p; Heading 2=h2; Heading 3=h3; Blockquote=blockquote",
      content_style:
        'body{font-family:"Inter Tight",ui-sans-serif,system-ui,sans-serif;font-size:15px;line-height:1.65;color:#111}',
      branding: false,
      promotion: false,
      statusbar: true,
      resize: true,
      setup(editor) {
        editor.on("change input undo redo", () => {
          editor.save();
        });
      },
    });
  }

  form.addEventListener("submit", () => {
    if (typeof tinymce !== "undefined") {
      tinymce.triggerSave();
    }
  });
})();
