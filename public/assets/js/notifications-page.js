(function () {
  var root = document.querySelector("[data-notifications-page]");

  if (!root) {
    return;
  }

  var readUrl = root.dataset.notificationReadUrl || "";
  var readAllUrl = root.dataset.notificationReadAllUrl || "";
  var markAllBtn = root.querySelector("[data-notifications-mark-all]");
  var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || "";

  function postNotification(url, formData) {
    return fetch(url, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": csrfToken,
      },
    }).then(function (response) {
      if (!response.ok) {
        throw new Error("Request failed");
      }

      return response.json();
    });
  }

  function markRead(notificationId) {
    if (!readUrl || !notificationId) {
      return Promise.resolve();
    }

    var bodyData = new FormData();
    bodyData.append("id", notificationId);
    bodyData.append("_token", csrfToken);

    return postNotification(readUrl, bodyData).catch(function () {
      return null;
    });
  }

  function markRowRead(link) {
    if (!link) {
      return;
    }

    link.classList.remove("is-unread");

    var dot = link.querySelector(".notifications-unread-dot");

    if (dot) {
      dot.remove();
    }
  }

  function handleLinkClick(link) {
    var url = link.getAttribute("href") || "";
    var id = link.getAttribute("data-notification-id") || "";

    markRowRead(link);

    markRead(id).finally(function () {
      if (url && url !== "#") {
        window.location.assign(url);
      }
    });
  }

  root.addEventListener(
    "click",
    function (event) {
      var link = event.target.closest("[data-notification-link]");

      if (!link || !root.contains(link)) {
        return;
      }

      event.preventDefault();
      handleLinkClick(link);
    },
    true
  );

  if (markAllBtn) {
    markAllBtn.addEventListener("click", function () {
      var unreadLinks = root.querySelectorAll(".notifications-row.is-unread[data-notification-link]");
      var ids = [];

      unreadLinks.forEach(function (link) {
        var id = link.getAttribute("data-notification-id") || "";

        if (id) {
          ids.push(id);
        }

        markRowRead(link);
      });

      if (!readAllUrl || ids.length === 0) {
        markAllBtn.hidden = true;

        return;
      }

      var bodyData = new FormData();

      ids.forEach(function (id) {
        bodyData.append("ids[]", id);
      });

      bodyData.append("_token", csrfToken);

      postNotification(readAllUrl, bodyData)
        .catch(function () {
          return null;
        })
        .finally(function () {
          markAllBtn.hidden = true;
        });
    });
  }
})();
