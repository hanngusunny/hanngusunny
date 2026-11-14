(function () {
  var pulseUrl = window.ACTIVITY_PULSE_URL;
  var intervalSeconds = Number(window.ACTIVITY_PULSE_SECONDS || 60);

  if (!pulseUrl || !intervalSeconds) {
    return;
  }

  var hiddenAt = null;

  function sendPulse() {
    if (document.hidden) {
      return;
    }

    var url = pulseUrl + (pulseUrl.indexOf("?") >= 0 ? "&" : "?") + "seconds=" + encodeURIComponent(intervalSeconds);

    fetch(url, {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    }).catch(function () {});
  }

  function onVisibilityChange() {
    if (document.hidden) {
      hiddenAt = Date.now();
      return;
    }

    if (hiddenAt !== null) {
      hiddenAt = null;
      sendPulse();
    }
  }

  document.addEventListener("visibilitychange", onVisibilityChange);
  window.setInterval(sendPulse, intervalSeconds * 1000);
  window.setTimeout(sendPulse, 15000);
})();
