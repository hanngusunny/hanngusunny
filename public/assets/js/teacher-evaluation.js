(function () {
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
