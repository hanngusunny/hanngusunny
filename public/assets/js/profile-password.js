(function () {
  document.querySelectorAll("[data-toggle-password]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const id = btn.getAttribute("data-toggle-password");
      const input = id ? document.getElementById(id) : null;
      const icon = btn.querySelector("i");

      if (!input) {
        return;
      }

      const show = input.type === "password";
      input.type = show ? "text" : "password";
      btn.setAttribute("aria-label", show ? "Ẩn mật khẩu" : "Hiện mật khẩu");

      if (icon) {
        icon.className = show ? "fa-solid fa-eye-slash" : "fa-solid fa-eye";
      }
    });
  });

  const passwordInput = document.getElementById("password");
  const confirmInput = document.getElementById("password_confirmation");
  const strengthEl = document.getElementById("passwordStrength");
  const matchHint = document.getElementById("passwordMatchHint");

  function scorePassword(value) {
    let score = 0;

    if (value.length >= 6) score += 1;
    if (value.length >= 8) score += 1;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
    if (/\d/.test(value)) score += 1;
    if (/[^A-Za-z0-9]/.test(value)) score += 1;

    return score;
  }

  function updateStrength() {
    if (!passwordInput || !strengthEl) {
      return;
    }

    const value = passwordInput.value;
    const bar = strengthEl.querySelector(".pf-strength-bar span");
    const label = strengthEl.querySelector(".pf-strength-label");

    if (!bar || !label) {
      return;
    }

    if (value === "") {
      strengthEl.className = "pf-strength";
      bar.style.width = "0";
      label.textContent = "Nhập mật khẩu để kiểm tra độ mạnh";
      return;
    }

    const score = scorePassword(value);
    const levels = ["weak", "fair", "good", "strong", "strong"];
    const labels = ["Yếu — nên dài hơn", "Trung bình", "Khá tốt", "Mạnh", "Rất mạnh"];
    const level = levels[Math.min(score, 4)];

    strengthEl.className = "pf-strength is-" + level;
    bar.style.width = Math.min(100, 20 + score * 18) + "%";
    label.textContent = labels[Math.min(score, 4)];
  }

  function updateMatch() {
    if (!passwordInput || !confirmInput || !matchHint) {
      return;
    }

    const match = confirmInput.value !== "" && confirmInput.value === passwordInput.value;
    matchHint.hidden = !match;
  }

  passwordInput?.addEventListener("input", function () {
    updateStrength();
    updateMatch();
  });

  confirmInput?.addEventListener("input", updateMatch);
})();
