/**
 * SUNNY — Tương tác màn Đánh giá Giáo viên (#681:61594)
 * 1) Lọc tức thì thẻ khóa học theo ô tìm kiếm trên màn tổng quan
 * 2) Giữ nguyên tự động cuộn học viên đang chọn & submit chọn tuần ở chế độ chi tiết
 */
(function () {
  const searchInput = document.getElementById('snTeCourseSearch');
  const courseGrid = document.getElementById('snTeCourseGrid');
  const liveEmpty = document.getElementById('snTeLiveEmpty');

  if (searchInput && courseGrid) {
    const cards = Array.from(courseGrid.querySelectorAll('.sn-te-card'));

    const applyFilter = () => {
      const q = searchInput.value.trim().toLowerCase();
      let visibleCount = 0;

      cards.forEach((card) => {
        const name = (card.dataset.courseName || card.textContent || '').toLowerCase();
        const match = q === '' || name.includes(q);
        card.hidden = !match;
        if (match) visibleCount += 1;
      });

      if (liveEmpty) {
        liveEmpty.hidden = visibleCount > 0;
      }
    };

    searchInput.addEventListener('input', applyFilter);
  }

  const active = document.getElementById('teacher-eval-student-active');
  const list = active && active.closest('.teacher-eval-student-list');

  if (active && list) {
    const top = active.offsetTop - list.offsetTop - (list.clientHeight / 2) + (active.clientHeight / 2);
    list.scrollTop = Math.max(0, top);
  }

  const weekForm = document.getElementById('teacher-eval-week-form');

  if (weekForm) {
    weekForm.addEventListener('change', function () {
      weekForm.submit();
    });
  }
})();
