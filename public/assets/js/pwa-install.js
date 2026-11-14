(function () {
  var btn = document.getElementById('pwa-install-btn');
  var sheet = document.getElementById('pwa-install-sheet');
  var closeBtn = document.getElementById('pwa-install-close');
  var titleEl = document.getElementById('pwa-install-title');
  var bodyEl = document.getElementById('pwa-install-body');
  if (!btn) return;

  var deferred = null;
  var ios = isIos();
  var standalone = isStandalone();

  if (standalone) {
    btn.hidden = true;
    return;
  }

  btn.hidden = false;

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    deferred = event;
    btn.hidden = false;
  });

  window.addEventListener('appinstalled', function () {
    deferred = null;
    btn.hidden = true;
    hideSheet();
  });

  btn.addEventListener('click', function () {
    if (deferred) {
      deferred.prompt();
      deferred.userChoice.finally(function () {
        deferred = null;
      });
      return;
    }

    if (ios) {
      showSheet(
        'Cài ra màn hình chính',
        'Trên iPhone/iPad: bấm nút <strong>Chia sẻ</strong> trên thanh Safari, rồi chọn <strong>Thêm vào Màn hình chính</strong>.'
      );
      return;
    }

    showSheet(
      'Cài ứng dụng',
      'Trên Chrome điện thoại: mở menu <strong>⋮</strong> rồi chọn <strong>Cài đặt ứng dụng</strong> / <strong>Thêm vào màn hình chính</strong>.'
    );
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', hideSheet);
  }
  if (sheet) {
    sheet.addEventListener('click', function (event) {
      if (event.target === sheet) hideSheet();
    });
  }

  function showSheet(title, html) {
    if (!sheet || !titleEl || !bodyEl) return;
    titleEl.textContent = title;
    bodyEl.innerHTML = html;
    sheet.hidden = false;
  }

  function hideSheet() {
    if (sheet) sheet.hidden = true;
  }

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone === true;
  }

  function isIos() {
    var ua = window.navigator.userAgent || '';
    var iPadOs = window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1;
    return /iPhone|iPad|iPod/i.test(ua) || iPadOs;
  }
})();
