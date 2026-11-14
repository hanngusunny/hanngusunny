(() => {
  const waitingBox = document.getElementById("classroomJoinWaiting");

  if (!waitingBox) return;

  const statusUrl = waitingBox.dataset.statusUrl;

  if (!statusUrl) return;

  const poll = async () => {
    try {
      const res = await fetch(statusUrl, { credentials: "same-origin", headers: { Accept: "application/json" } });
      const data = await res.json();

      if (!data.ok) return;

      if (data.status === "approved") {
        window.location.href = data.redirectUrl || window.location.href.replace("/phong-cho", "/vao");
        return;
      }

      if (data.status === "rejected") {
        window.location.reload();
      }
    } catch (error) {
      /* ignore transient network errors */
    }
  };

  poll();
  window.setInterval(poll, 4000);
})();
