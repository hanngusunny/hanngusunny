(function () {
  // 1. Toggle ẩn/hiện mật khẩu
  document.querySelectorAll("[data-toggle-password]").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      const id = btn.getAttribute("data-toggle-password");
      const input = id ? document.getElementById(id) : null;
      const icon = btn.querySelector("i");

      if (!input) {
        return;
      }

      const isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";
      btn.setAttribute("aria-label", isPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu");

      if (icon) {
        icon.className = isPassword ? "fa-solid fa-eye-slash" : "fa-solid fa-eye";
      }
    });
  });

  // 2. Realtime Password Validation (4 tiêu chí chuẩn Figma Node #406:26819)
  const passwordInput = document.getElementById("password");
  const confirmInput = document.getElementById("password_confirmation");
  const rules = {
    length: document.querySelector('[data-rule="length"]'),
    uppercase: document.querySelector('[data-rule="uppercase"]'),
    lowercase: document.querySelector('[data-rule="lowercase"]'),
    number: document.querySelector('[data-rule="number"]'),
  };

  const mismatchEl = document.getElementById("confirmMismatchError");
  const matchEl = document.getElementById("confirmMatchSuccess");

  function validateRules() {
    if (!passwordInput) return;
    const val = passwordInput.value || "";

    const checks = {
      length: val.length >= 8,
      uppercase: /[A-Z]/.test(val),
      lowercase: /[a-z]/.test(val),
      number: /\d/.test(val),
    };

    for (const key in checks) {
      if (rules[key]) {
        if (checks[key]) {
          rules[key].classList.add("is-valid");
        } else {
          rules[key].classList.remove("is-valid");
        }
      }
    }
  }

  function validateMatch() {
    if (!passwordInput || !confirmInput) return;
    const pw = passwordInput.value || "";
    const cpw = confirmInput.value || "";
    const wrap = confirmInput.closest(".sn-input-wrap");

    if (cpw === "") {
      if (mismatchEl) mismatchEl.hidden = true;
      if (matchEl) matchEl.hidden = true;
      confirmInput.classList.remove("is-invalid");
      wrap?.classList.remove("is-invalid");
      return;
    }

    if (pw === cpw) {
      if (mismatchEl) mismatchEl.hidden = true;
      if (matchEl) matchEl.hidden = false;
      confirmInput.classList.remove("is-invalid");
      wrap?.classList.remove("is-invalid");
    } else {
      if (mismatchEl) mismatchEl.hidden = false;
      if (matchEl) matchEl.hidden = true;
      confirmInput.classList.add("is-invalid");
      wrap?.classList.add("is-invalid");
    }
  }

  passwordInput?.addEventListener("input", function () {
    validateRules();
    if (confirmInput && confirmInput.value !== "") {
      validateMatch();
    }
  });

  confirmInput?.addEventListener("input", validateMatch);
})();
