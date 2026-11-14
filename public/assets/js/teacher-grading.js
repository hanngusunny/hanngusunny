(() => {
  const active = document.querySelector(".teacher-grading-queue-item.is-active");
  const list = document.querySelector(".teacher-grading-queue-list");

  if (active && list) {
    const top = active.offsetTop - list.offsetTop - list.clientHeight / 2 + active.clientHeight / 2;
    list.scrollTop = Math.max(0, top);
  }
})();

(() => {
  const wraps = [];

  const applyRotation = (wrap) => {
    const frame = wrap.querySelector(".tg-essay-image-frame");
    const img = wrap.querySelector("img");
    if (!frame || !img) {
      return;
    }

    const deg = Number(wrap.dataset.rotation || 0) % 360;
    const sideways = deg % 180 !== 0;

    img.style.transform = "";
    img.style.position = "";
    img.style.left = "";
    img.style.top = "";
    img.style.removeProperty("width");
    img.style.removeProperty("height");
    img.style.removeProperty("max-width");
    frame.style.width = "";
    frame.style.height = "";

    if (!sideways) {
      img.style.transform = deg === 180 ? "rotate(180deg)" : "";
      return;
    }

    const boxW = img.offsetWidth;
    const boxH = img.offsetHeight;
    if (!boxW || !boxH) {
      return;
    }

    const maxW = wrap.clientWidth || boxW;
    let frameW = boxH;
    let frameH = boxW;
    let scale = 1;

    if (frameW > maxW) {
      scale = maxW / frameW;
      frameW = maxW;
      frameH = boxW * scale;
    }

    frame.style.width = `${frameW}px`;
    frame.style.height = `${frameH}px`;
    img.style.position = "absolute";
    img.style.left = "50%";
    img.style.top = "50%";
    img.style.setProperty("width", `${boxW}px`, "important");
    img.style.setProperty("height", `${boxH}px`, "important");
    img.style.setProperty("max-width", "none", "important");
    img.style.transform = `translate(-50%, -50%) rotate(${deg}deg) scale(${scale})`;
  };

  const enhanceImage = (img) => {
    if (img.closest(".tg-essay-image") || img.classList.contains("qb-option-image")) {
      return;
    }

    const wrap = document.createElement("span");
    wrap.className = "tg-essay-image";
    wrap.dataset.rotation = "0";

    const frame = document.createElement("span");
    frame.className = "tg-essay-image-frame";

    const button = document.createElement("button");
    button.type = "button";
    button.className = "tg-essay-image-rotate";
    button.setAttribute("aria-label", "Xoay ảnh 90 độ");
    button.innerHTML = '<i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Xoay ảnh';

    img.replaceWith(wrap);
    frame.appendChild(img);
    wrap.appendChild(frame);
    wrap.appendChild(button);
    wraps.push(wrap);

    const rotate = () => {
      const next = (Number(wrap.dataset.rotation || 0) + 90) % 360;
      wrap.dataset.rotation = String(next);
      applyRotation(wrap);
    };

    button.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      rotate();
    });

    if (img.complete) {
      applyRotation(wrap);
    } else {
      img.addEventListener("load", () => applyRotation(wrap), { once: true });
    }
  };

  document.querySelectorAll(".js-essay-answer-images img").forEach(enhanceImage);

  let resizeTimer = 0;
  window.addEventListener("resize", () => {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(() => {
      wraps.forEach(applyRotation);
    }, 120);
  });
})();
