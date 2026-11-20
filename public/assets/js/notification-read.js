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
  var pendingMarkAllPromise = null;

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
    return root.querySelectorAll("[data-notification-link].is-unread").length;
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
      viewAll.textContent = "Xem tất cả";
    }

    var markAllBtn = root.querySelector("[data-notification-mark-all]");
    if (markAllBtn) {
      markAllBtn.disabled = count <= 0;
      if (count <= 0) {
        markAllBtn.classList.add("is-disabled");
      } else {
        markAllBtn.classList.remove("is-disabled");
      }
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
      '<div class="sn-notify-empty" data-notification-empty>' +
        '<div class="sn-notify-empty-icon" aria-hidden="true">' +
          '<img src="/assets/images/figma-notification.svg" width="28" height="28" alt="" style="opacity: 0.35;">' +
        '</div>' +
        '<strong class="sn-notify-empty-title">Chưa có thông báo mới</strong>' +
        '<p class="sn-notify-empty-text">Bạn sẽ nhận thông báo khi có lịch học, phản hồi giáo viên hoặc cập nhật khóa học.</p>' +
      '</div>'
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
    if (!readAllUrl) {
      return Promise.resolve();
    }

    var bodyData = new FormData();
    bodyData.append("all", "1");

    if (ids && ids.length) {
      ids.forEach(function (id) {
        bodyData.append("ids[]", id);
      });
    }

    bodyData.append("_token", csrfToken);

    pendingMarkAllPromise = postNotification(readAllUrl, bodyData)
      .catch(function () {
        return null;
      })
      .finally(function () {
        pendingMarkAllPromise = null;
      });

    return pendingMarkAllPromise;
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
      item.classList.remove("is-unread");
      item.classList.add("is-read");
    });

    updateBadge();
  }

  function hideNotification(link) {
    if (!link) {
      return;
    }

    link.classList.remove("is-unread");
    link.classList.add("is-read");
    updateBadge();
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
      // 1. Nhấp nút "Xem tất cả"
      var viewAllLink = event.target.closest(".notification-view-all");
      if (viewAllLink) {
        var href = viewAllLink.getAttribute("href");
        if (pendingMarkAllPromise && href && href !== "#") {
          event.preventDefault();
          event.stopPropagation();
          viewAllLink.style.opacity = "0.7";
          viewAllLink.style.pointerEvents = "none";
          pendingMarkAllPromise.finally(function () {
            window.location.assign(href);
          });
          return;
        }
      }

      // 2. Nhấp nút "Đánh dấu đã đọc"
      var markAll = event.target.closest("[data-notification-mark-all]");
      if (markAll) {
        event.preventDefault();
        event.stopPropagation();

        var ids = collectNotificationIds();
        clearAllNotifications();
        markAll.disabled = true;
        markAll.classList.add("is-disabled");
        markAllRead(ids);

        return;
      }

      // 3. Nhấp vào 1 thông báo lẻ
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
