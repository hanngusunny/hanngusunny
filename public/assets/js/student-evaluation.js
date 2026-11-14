(function () {
  const form = document.getElementById('student-eval-week-form');
  const select = document.getElementById('studentEvalWeekSelect');

  if (form && select) {
    select.addEventListener('change', function () {
      form.submit();
    });
  }
})();
