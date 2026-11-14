(function () {
  var root = document.querySelector("[data-notification-root]");

  if (!root) {
    return;
  }

  var readUrl = root.dataset.notificationReadUrl || "";
  var readAllUrl = root.dataset.notificationReadAllUrl || "";
  var badge = root.querySelector("[data-notification-badge]");
  var subtitle = root.querySelector("[data-notification-subtitle]");
  var list = root.querySelector("[data-notification-list]");
  var body = root.querySelector("[data-notification-body]");
  var foot = root.querySelector("[data-notification-foot]");
  var viewAll = root.querySelector(".notification-view-all");
  var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || "";

  document.querySelectorAll(".os-tools details.os-drop").forEach(function (el) {
    el.addEventListener("toggle", function () {
      if (!el.open) {
        return;
      }

      document.querySelectorAll(".os-tools details.os-drop").forEach(function (other) {
        if (other !== el) {
          other.open = false;
        }
      });
    });
  });

  function remainingCount() {
    return root.querySelectorAll("[data-notification-link]").length;
  }

  function updateBadge() {
    var count = remainingCount();

    if (badge) {
      if (count <= 0) {
        badge.textContent = "0";
        badge.classList.add("is-hidden");
        badge.setAttribute("aria-hidden", "true");
      } else {
        badge.textContent = count > 99 ? "99+" : String(count);
        badge.classList.remove("is-hidden");
        badge.setAttribute("aria-hidden", "false");
      }
    }

    if (subtitle) {
      subtitle.textContent = count > 0 ? count + " mục chưa đọc" : "Tất cả đã cập nhật";
    }

    if (viewAll) {
      viewAll.textContent = count > 0 ? "Xem tất cả" : "Xem trang thông báo";
    }

    if (foot) {
      foot.hidden = count <= 0;
    }
  }

  function showEmptyState() {
    if (!body || body.querySelector("[data-notification-empty]")) {
      return;
    }

    if (list) {
      list.remove();
    }

    body.insertAdjacentHTML(
      "beforeend",
      '<div class="notification-empty" data-notification-empty>' +
        '<div class="notification-empty-visual" aria-hidden="true">' +
          '<span class="notification-empty-glow"></span>' +
          '<span class="notification-empty-ring"></span>' +
          '<span class="notification-empty-icon"><i class="fa-solid fa-bell-slash"></i></span>' +
        "</div>" +
        '<strong class="notification-empty-title">Chưa có thông báo mới</strong>' +
        '<p class="notification-empty-text">Khi có đơn xin nghỉ, phản hồi giáo viên hoặc cập nhật khóa học, chúng tôi sẽ hiển thị tại đây.</p>' +
      "</div>"
    );
  }

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

  function markAllRead(ids) {
    if (!readAllUrl || !ids.length) {
      return Promise.resolve();
    }

    var bodyData = new FormData();

    ids.forEach(function (id) {
      bodyData.append("ids[]", id);
    });

    bodyData.append("_token", csrfToken);

    return postNotification(readAllUrl, bodyData).catch(function () {
      return null;
    });
  }

  function collectNotificationIds() {
    var ids = [];

    root.querySelectorAll("[data-notification-link]").forEach(function (item) {
      var id = item.getAttribute("data-notification-id") || "";

      if (id) {
        ids.push(id);
      }
    });

    return ids;
  }

  function clearAllNotifications() {
    root.querySelectorAll("[data-notification-link]").forEach(function (item) {
      item.remove();
    });

    updateBadge();
    showEmptyState();
  }

  function hideNotification(link) {
    if (!link) {
      return;
    }

    link.remove();
    updateBadge();

    if (remainingCount() === 0) {
      showEmptyState();
    }
  }

  function handleNotificationClick(link) {
    var url = link.getAttribute("href") || "";
    var id = link.getAttribute("data-notification-id") || "";

    hideNotification(link);

    markRead(id).finally(function () {
      if (url && url !== "#") {
        window.location.assign(url);
      }
    });
  }

  root.addEventListener(
    "click",
    function (event) {
      var markAll = event.target.closest("[data-notification-mark-all]");

      if (markAll) {
        event.preventDefault();
        event.stopPropagation();

        var ids = collectNotificationIds();
        clearAllNotifications();
        markAllRead(ids);

        return;
      }

      var link = event.target.closest("[data-notification-link]");

      if (link) {
        event.preventDefault();
        event.stopPropagation();
        handleNotificationClick(link);
      }
    },
    true
  );

  updateBadge();
})();
