<div id="cmsEmbedPicker" hidden class="border p-3 mt-3" data-blocks="{{ route('admin.embeds.blocks') }}" data-faqs="{{ route('admin.embeds.faqs') }}" data-sliders="{{ route('admin.embeds.sliders') }}">
  <div class="flex justify-between">
    <strong id="cmsEmbedPickerTitle">Chèn</strong>
    <button type="button" data-embed-close>Đóng</button>
  </div>
  <div id="cmsEmbedPickerList" class="mt-2"></div>
</div>
<script src="{{ asset('js/admin-richtext-embeds.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.4/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  if (window.tinymce) {
    tinymce.init({
      selector: 'textarea.js-richtext',
      height: 420,
      menubar: false,
      plugins: 'link lists image code',
      toolbar: 'undo redo | blocks | bold italic | bullist numlist | link image | bunblock bunfaq bunslider | code',
      convert_urls: false,
      relative_urls: false,
      setup: function (editor) {
        editor.ui.registry.addButton('bunblock', {
          text: 'Block',
          onAction: function () { window.bunRichtextEmbed(editor, 'blocks'); }
        });
        editor.ui.registry.addButton('bunfaq', {
          text: 'FAQ',
          onAction: function () { window.bunRichtextEmbed(editor, 'faqs'); }
        });
        editor.ui.registry.addButton('bunslider', {
          text: 'Slider',
          onAction: function () { window.bunRichtextEmbed(editor, 'sliders'); }
        });
      }
    });
  }
</script>
