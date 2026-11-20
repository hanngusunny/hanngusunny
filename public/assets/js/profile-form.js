(function () {
  const input = document.getElementById("avatar");
  const btnChooseAvatar = document.getElementById("btnChooseAvatar");
  const preview = document.getElementById("avatarPreview");
  const fallback = document.querySelector("[data-avatar-fallback]");
  const wrap = preview ? preview.closest("[data-user-avatar]") : null;
  const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB chuẩn Figma

  // Kích hoạt input file ẩn khi bấm nút "Chọn ảnh mới"
  if (btnChooseAvatar && input) {
    btnChooseAvatar.addEventListener("click", function (e) {
      e.preventDefault();
      input.click();
    });
  }

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
      fallback.hidden = true;
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

    // Kiểm tra dung lượng tối đa 5MB
    if (file.size > MAX_FILE_SIZE) {
      alert("Ảnh đại diện tối đa 5MB. Vui lòng chọn ảnh có dung lượng nhỏ hơn.");
      input.value = "";
      return;
    }

    const reader = new FileReader();
    reader.onload = function (event) {
      if (typeof event.target?.result === "string") {
        showPhoto(event.target.result);
      }
    };
    reader.readAsDataURL(file);
  });
})();
