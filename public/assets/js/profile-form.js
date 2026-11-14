(function () {
  const input = document.getElementById("avatar");
  const preview = document.getElementById("avatarPreview");
  const fallback = document.querySelector("[data-avatar-fallback]");
  const removeCheckbox = document.querySelector('input[name="remove_avatar"]');
  const wrap = preview ? preview.closest("[data-user-avatar]") : null;

  if (!input || !preview) {
    return;
  }

  const showFallback = function () {
    preview.hidden = true;
    preview.removeAttribute("src");
    if (fallback) {
      fallback.hidden = false;
    }
    wrap?.classList.add("is-fallback");
  };

  const showPhoto = function (src) {
    preview.src = src;
    preview.hidden = false;
    if (fallback) {
      fallback.hidden = false;
    }
    wrap?.classList.remove("is-fallback");
  };

  preview.addEventListener("error", function () {
    if (!preview.getAttribute("src")) {
      return;
    }
    showFallback();
  });

  input.addEventListener("change", function () {
    const file = input.files && input.files[0];

    if (!file) {
      return;
    }

    if (removeCheckbox) {
      removeCheckbox.checked = false;
    }

    const reader = new FileReader();
    reader.onload = function (event) {
      if (typeof event.target?.result === "string") {
        showPhoto(event.target.result);
      }
    };
    reader.readAsDataURL(file);
  });

  if (removeCheckbox) {
    removeCheckbox.addEventListener("change", function () {
      if (!removeCheckbox.checked) {
        return;
      }

      input.value = "";
      showFallback();
    });
  }
})();
