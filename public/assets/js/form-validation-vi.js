(() => {
  const messageFor = (el) => {
    const validity = el.validity;

    if (!validity || validity.valid) {
      return "";
    }

    if (validity.valueMissing) {
      if (el.type === "radio" || el.type === "checkbox") {
        return "Vui lòng chọn một mục.";
      }

      if (el.tagName === "SELECT") {
        return "Vui lòng chọn một mục.";
      }

      return "Vui lòng điền thông tin này.";
    }

    if (validity.typeMismatch) {
      if (el.type === "email") {
        return "Vui lòng nhập địa chỉ email hợp lệ.";
      }

      if (el.type === "url") {
        return "Vui lòng nhập đường dẫn hợp lệ.";
      }

      return "Giá trị không đúng định dạng.";
    }

    if (validity.tooShort) {
      return "Nội dung quá ngắn (tối thiểu " + el.minLength + " ký tự).";
    }

    if (validity.tooLong) {
      return "Nội dung quá dài (tối đa " + el.maxLength + " ký tự).";
    }

    if (validity.rangeUnderflow) {
      return "Giá trị phải lớn hơn hoặc bằng " + el.min + ".";
    }

    if (validity.rangeOverflow) {
      return "Giá trị phải nhỏ hơn hoặc bằng " + el.max + ".";
    }

    if (validity.stepMismatch) {
      return "Giá trị không hợp lệ.";
    }

    if (validity.patternMismatch) {
      return "Giá trị không đúng định dạng yêu cầu.";
    }

    if (validity.badInput) {
      return "Giá trị không hợp lệ.";
    }

    return "Vui lòng kiểm tra lại thông tin.";
  };

  const applyVietnameseValidity = (el) => {
    if (!el || typeof el.setCustomValidity !== "function") {
      return;
    }

    el.setCustomValidity(el.validity && !el.validity.valid ? messageFor(el) : "");
  };

  document.addEventListener(
    "invalid",
    (event) => {
      applyVietnameseValidity(event.target);
    },
    true
  );

  document.addEventListener(
    "input",
    (event) => {
      const el = event.target;
      if (el && typeof el.setCustomValidity === "function") {
        el.setCustomValidity("");
      }
    },
    true
  );

  document.addEventListener(
    "change",
    (event) => {
      const el = event.target;
      if (el && typeof el.setCustomValidity === "function") {
        el.setCustomValidity("");
      }
    },
    true
  );
})();
