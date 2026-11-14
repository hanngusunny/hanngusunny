(() => {
  const video = document.getElementById("lobbyPreview");
  const placeholder = document.getElementById("lobbyPreviewPlaceholder");
  const toggleMic = document.getElementById("toggleMic");
  const toggleCam = document.getElementById("toggleCam");

  if (!video) return;

  let stream = null;
  let micOn = true;
  let camOn = true;

  const setDeviceButton = (btn, on, onIcon, offIcon) => {
    if (!btn) return;
    btn.classList.toggle("is-off", !on);
    btn.setAttribute("aria-pressed", on ? "true" : "false");
    const icon = btn.querySelector("i");
    if (icon) icon.className = `fa-solid ${on ? onIcon : offIcon}`;
  };

  const updateButtons = () => {
    setDeviceButton(toggleMic, micOn, "fa-microphone", "fa-microphone-slash");
    setDeviceButton(toggleCam, camOn, "fa-video", "fa-video-slash");
  };

  const startPreview = async () => {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
      video.srcObject = stream;
      if (placeholder) placeholder.hidden = true;
    } catch (e) {
      if (placeholder) {
        placeholder.innerHTML = "<i class=\"fa-solid fa-video-slash\"></i><span>Không truy cập được camera</span>";
      }
    }
  };

  toggleMic?.addEventListener("click", () => {
    micOn = !micOn;
    stream?.getAudioTracks().forEach((t) => {
      t.enabled = micOn;
    });
    updateButtons();
  });

  toggleCam?.addEventListener("click", () => {
    camOn = !camOn;
    stream?.getVideoTracks().forEach((t) => {
      t.enabled = camOn;
    });
    video.style.opacity = camOn ? "1" : "0.35";
    updateButtons();
  });

  window.addEventListener("beforeunload", () => {
    stream?.getTracks().forEach((t) => t.stop());
  });

  startPreview();
  updateButtons();

  const copyJitsiBtn = document.getElementById("copyJitsiMeetUrlBtn");
  const jitsiUrlEl = document.getElementById("lobbyJitsiMeetUrl");

  const bindCopy = (btn, source) => {
    btn?.addEventListener("click", async () => {
      const text = source?.textContent?.trim() || "";

      if (!text) return;

      try {
        await navigator.clipboard.writeText(text);
        btn.innerHTML = "<i class=\"fa-solid fa-check\"></i> Đã copy";
        window.setTimeout(() => {
          btn.innerHTML = "<i class=\"fa-solid fa-copy\"></i> Copy";
        }, 2000);
      } catch (e) {
        window.prompt("Sao chép link:", text);
      }
    });
  };

  bindCopy(document.getElementById("copyGuestJoinUrlBtn"), document.getElementById("lobbyGuestJoinUrl"));

  copyJitsiBtn?.addEventListener("click", async () => {
    const text = jitsiUrlEl?.textContent?.trim() || "";

    if (!text) return;

    try {
      await navigator.clipboard.writeText(text);
      copyJitsiBtn.innerHTML = "<i class=\"fa-solid fa-check\"></i> Đã copy";
      window.setTimeout(() => {
        copyJitsiBtn.innerHTML = "<i class=\"fa-solid fa-copy\"></i> Copy";
      }, 2000);
    } catch (e) {
      window.prompt("Sao chép link Jitsi:", text);
    }
  });
})();
