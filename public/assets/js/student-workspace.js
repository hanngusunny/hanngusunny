(function () {
    const active = document.getElementById('student-course-session-active');
    const list = document.getElementById('student-course-session-list');
    if (active && list) {
        const top = active.offsetTop - list.offsetTop - (list.clientHeight / 2) + (active.clientHeight / 2);
        list.scrollTop = Math.max(0, top);
    }
})();
