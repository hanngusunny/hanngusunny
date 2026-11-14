(() => {
  const list = document.getElementById("classroomList");
  const search = document.getElementById("classroomSearch");
  const filterBtns = document.querySelectorAll(".cr-filter[data-filter]");

  if (!list) return;

  const cards = () => Array.from(list.querySelectorAll(".cr-hub-card"));

  const applyFilters = () => {
    const query = (search?.value || "").trim().toLowerCase();
    const activeBtn = document.querySelector(".cr-filter[data-filter].is-on");
    const filter = activeBtn?.dataset.filter || "all";

    cards().forEach((card) => {
      const status = card.dataset.status || "";
      const text = card.dataset.search || "";
      const matchFilter = filter === "all" || status === filter;
      const matchSearch = !query || text.includes(query);
      card.hidden = !(matchFilter && matchSearch);
    });
  };

  search?.addEventListener("input", applyFilters);

  filterBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      filterBtns.forEach((item) => item.classList.remove("is-on"));
      btn.classList.add("is-on");
      applyFilters();
    });
  });
})();
