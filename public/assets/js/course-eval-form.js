(() => {
  const form = document.querySelector('.course-eval-form');
  if (!form) return;

  const tinymceBaseUrl = document.body.dataset.tinymceBase || '/assets/tinymce';

  if (typeof tinymce !== 'undefined') {
    tinymce.init({
      base_url: tinymceBaseUrl,
      suffix: '.min',
      language: 'vi',
      language_url: tinymceBaseUrl + '/langs/vi.js',
      selector: '.js-course-eval-editor',
      height: 320,
      min_height: 220,
      menubar: false,
      plugins: 'lists link autoresize code',
      toolbar:
        'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | removeformat | code',
      block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3',
      content_style:
        "body{font-family:'Inter Tight',ui-sans-serif,system-ui,sans-serif;font-size:15px;line-height:1.65;color:#1f2937}",
      branding: false,
      promotion: false,
      statusbar: true,
      resize: true,
    });
  }

  form.addEventListener('submit', () => {
    if (typeof tinymce !== 'undefined') {
      tinymce.triggerSave();
    }
  });
})();
