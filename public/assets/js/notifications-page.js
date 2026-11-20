(function () {
  var root = document.querySelector("[data-notifications-page]");

  if (!root) {
    return;
  }

  var readUrl = root.dataset.notificationReadUrl || "";
  var readAllUrl = root.dataset.notificationReadAllUrl || "";
  var markAllBtn = root.querySelector("[data-notifications-mark-all]");
  var unreadCountEl = root.querySelector("[data-unread-count]");
  var topbarBadge = document.querySelector("[data-notification-badge]");
  var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || "";
  var tabs = root.querySelectorAll("[data-filter-tab]");
  var emptyEl = root.querySelector("[data-notifications-empty]");
  var emptyTitleEl = root.querySelector("[data-empty-title]");

  var activeTabEl = root.querySelector(".sn-notif-tab.is-active[data-filter-tab]");
  var currentFilter = activeTabEl ? (activeTabEl.getAttribute("data-filter-tab") || "all") : "all";

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

  function applyFilter(filter) {
    currentFilter = filter;

    // 1. Cập nhật trạng thái active trên các tab con nhộng
    tabs.forEach(function (tab) {
      var tabVal = tab.getAttribute("data-filter-tab");
      var isActive = tabVal === filter;
      tab.classList.toggle("is-active", isActive);
      tab.setAttribute("aria-selected", isActive ? "true" : "false");
    });

    var cards = root.querySelectorAll("[data-notification-link]");
    var visibleTotal = 0;

    cards.forEach(function (card) {
      var isUnread = card.classList.contains("is-unread");
      var shouldShow = true;

      if (filter === "unread") {
        shouldShow = isUnread;
      } else if (filter === "read") {
        shouldShow = !isUnread;
      }

      if (shouldShow) {
        card.style.display = "";
        visibleTotal++;
      } else {
        card.style.display = "none";
      }
    });

    // 2. Cập nhật hiển thị của từng nhóm ngày ("Hôm nay", "Hôm qua", "Trước đó")
    var groups = root.querySelectorAll("[data-notif-group]");
    groups.forEach(function (group) {
      var groupCards = group.querySelectorAll("[data-notification-link]");
      var hasVisibleInGroup = false;

      groupCards.forEach(function (gc) {
        if (gc.style.display !== "none") {
          hasVisibleInGroup = true;
        }
      });

      group.style.display = hasVisibleInGroup ? "" : "none";
    });

    // 3. Cập nhật hiển thị và tiêu đề thông báo rỗng (Empty state)
    if (emptyEl) {
      if (visibleTotal === 0) {
        emptyEl.style.display = "";
        if (emptyTitleEl) {
          if (filter === "unread") {
            emptyTitleEl.textContent = "Không có thông báo chưa đọc";
          } else if (filter === "read") {
            emptyTitleEl.textContent = "Chưa có thông báo đã đọc";
          } else {
            emptyTitleEl.textContent = "Chưa có thông báo mới";
          }
        }
      } else {
        emptyEl.style.display = "none";
      }
    }
  }

  function markCardRead(link) {
    if (!link || !link.classList.contains("is-unread")) {
      return;
    }

    link.classList.remove("is-unread");
    link.classList.add("is-read");
    link.setAttribute("data-read-state", "read");

    var dot = link.querySelector("[data-unread-dot], .notifications-unread-dot, .sn-notif-unread-dot");
    if (dot) {
      var statusContainer = dot.parentElement;
      dot.remove();
      if (statusContainer) {
        statusContainer.innerHTML = '<span class="sn-notif-read-badge">Đã đọc</span>';
      }
    }

    updateCountAfterRead();

    // Nếu đang ở tab Chưa đọc, ẩn ngay card vừa được đánh dấu đọc
    if (currentFilter === "unread") {
      applyFilter("unread");
    }
  }

  function updateCountAfterRead() {
    var unreadLinks = root.querySelectorAll(".is-unread[data-notification-link]");
    var count = unreadLinks.length;

    if (unreadCountEl) {
      unreadCountEl.textContent = String(count);
    }

    if (topbarBadge) {
      if (count <= 0) {
        topbarBadge.textContent = "0";
        topbarBadge.classList.add("is-hidden");
        topbarBadge.setAttribute("aria-hidden", "true");
      } else {
        topbarBadge.textContent = count > 99 ? "99+" : String(count);
        topbarBadge.classList.remove("is-hidden");
        topbarBadge.setAttribute("aria-hidden", "false");
      }
    }

    if (markAllBtn) {
      markAllBtn.disabled = count <= 0;
      if (count <= 0) {
        markAllBtn.classList.add("is-disabled");
      } else {
        markAllBtn.classList.remove("is-disabled");
      }
    }
  }

  function handleLinkClick(link) {
    var url = link.getAttribute("href") || "";
    var id = link.getAttribute("data-notification-id") || "";

    markCardRead(link);

    markRead(id).finally(function () {
      if (url && url !== "#") {
        window.location.assign(url);
      }
    });
  }

  // 4. Lắng nghe click chuyển tab con nhộng (Instant 0ms Switching)
  tabs.forEach(function (tab) {
    tab.addEventListener("click", function (event) {
      event.preventDefault();
      var filter = tab.getAttribute("data-filter-tab") || "all";
      applyFilter(filter);

      var href = tab.getAttribute("href");
      if (href && window.history && window.history.replaceState) {
        window.history.replaceState(null, "", href);
      }
    });
  });

  // Hỗ trợ nút Back/Forward trên trình duyệt
  window.addEventListener("popstate", function () {
    var urlParams = new URLSearchParams(window.location.search);
    var docParam = urlParams.get("doc") || "all";
    if (["all", "unread", "read"].indexOf(docParam) !== -1) {
      applyFilter(docParam);
    }
  });

  // 5. Bắt sự kiện click vào từng thông báo
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

  // 6. Xử lý nút "Đánh dấu tất cả đã đọc"
  if (markAllBtn) {
    markAllBtn.addEventListener("click", function () {
      var unreadLinks = root.querySelectorAll(".is-unread[data-notification-link]");
      var ids = [];

      unreadLinks.forEach(function (link) {
        var id = link.getAttribute("data-notification-id") || "";
        if (id) {
          ids.push(id);
        }
        markCardRead(link);
      });

      if (currentFilter === "unread") {
        applyFilter("unread");
      }

      if (!readAllUrl || ids.length === 0) {
        markAllBtn.disabled = true;
        markAllBtn.classList.add("is-disabled");
        return;
      }

      var bodyData = new FormData();
      bodyData.append("all", "1");
      ids.forEach(function (id) {
        bodyData.append("ids[]", id);
      });
      bodyData.append("_token", csrfToken);

      postNotification(readAllUrl, bodyData)
        .catch(function () {
          return null;
        })
        .finally(function () {
          markAllBtn.disabled = true;
          markAllBtn.classList.add("is-disabled");
        });
    });
  }
})();
