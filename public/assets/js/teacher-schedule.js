/**
 * Lịch dạy giáo viên — ngày / tuần / tháng (mặc định: tháng).
 */
(function () {
  const root = document.getElementById('studentScheduleApp');
  if (!root) return;

  const sessions = JSON.parse(root.dataset.sessions || '[]');
  const sessionUrlBase = (root.dataset.sessionUrlBase || '').replace(/\/$/, '');
  const joinUrlBase = (root.dataset.joinUrlBase || sessionUrlBase).replace(/\/$/, '');
  const assessmentUrlBase = (root.dataset.assessmentUrlBase || '').replace(/\/$/, '');
  const csrfName = root.dataset.csrfName || '';
  const csrfHash = root.dataset.csrfHash || '';
  const emptyDayText = root.dataset.emptyDay || 'Không có buổi dạy trong ngày này.';
  const bodyEl = root.querySelector('.student-schedule-body');
  const labelEl = root.querySelector('.student-schedule-label');
  const viewButtons = root.querySelectorAll('[data-view]');
  const miniGridEl = document.querySelector('.student-schedule-mini-grid');
  const miniLabelEl = document.querySelector('.student-schedule-mini-label');
  const miniPrevBtn = document.querySelector('.student-schedule-mini-prev');
  const miniNextBtn = document.querySelector('.student-schedule-mini-next');

  const VI_DAYS_SHORT = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
  const VI_MONTHS = [
    'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
    'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12',
  ];

  let view = 'month';
  let cursor = startOfDay(new Date());

  viewButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      view = btn.dataset.view || 'month';
      viewButtons.forEach((b) => {
        const active = b === btn;
        b.classList.toggle('active', active);
        b.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      render();
    });
  });

  root.querySelector('.student-schedule-prev')?.addEventListener('click', () => {
    shiftCursor(-1);
    render();
  });

  root.querySelector('.student-schedule-next')?.addEventListener('click', () => {
    shiftCursor(1);
    render();
  });

  root.querySelector('.student-schedule-today')?.addEventListener('click', () => {
    cursor = startOfDay(new Date());
    render();
  });

  miniPrevBtn?.addEventListener('click', () => {
    cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
    if (view !== 'month') {
      view = 'month';
      syncViewButtons('month');
    }
    render();
  });

  miniNextBtn?.addEventListener('click', () => {
    cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
    if (view !== 'month') {
      view = 'month';
      syncViewButtons('month');
    }
    render();
  });

  function syncViewButtons(activeView) {
    viewButtons.forEach((b) => {
      const active = b.dataset.view === activeView;
      b.classList.toggle('active', active);
      b.setAttribute('aria-selected', active ? 'true' : 'false');
    });
  }

  function startOfDay(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
  }

  function pad(n) {
    return String(n).padStart(2, 0);
  }

  function formatDateKey(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
  }

  function parseDateKey(key) {
    const [y, m, d] = key.split('-').map(Number);
    return new Date(y, m - 1, d);
  }

  function sessionsForDate(dateKey) {
    return sessions
      .filter((s) => s.date === dateKey)
      .sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')));
  }

  function shiftCursor(direction) {
    if (view === 'month') {
      cursor = new Date(cursor.getFullYear(), cursor.getMonth() + direction, 1);
      return;
    }
    if (view === 'week') {
      cursor = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate() + direction * 7);
      return;
    }
    cursor = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate() + direction);
  }

  function updateLabel() {
    if (!labelEl) return;

    if (view === 'month') {
      labelEl.textContent = `${VI_MONTHS[cursor.getMonth()]}, ${cursor.getFullYear()}`;
      return;
    }
    if (view === 'week') {
      const week = getWeekDays(cursor);
      const first = week[0];
      const last = week[6];
      labelEl.textContent = `${first.getDate()}/${first.getMonth() + 1} – ${last.getDate()}/${last.getMonth() + 1}, ${last.getFullYear()}`;
      return;
    }
    labelEl.textContent = `${cursor.getDate()}/${cursor.getMonth() + 1}/${cursor.getFullYear()}`;
  }

  function getWeekDays(anchor) {
    const day = anchor.getDay();
    const mondayOffset = day === 0 ? -6 : 1 - day;
    const monday = new Date(anchor.getFullYear(), anchor.getMonth(), anchor.getDate() + mondayOffset);
    return Array.from({ length: 7 }, (_, i) =>
      new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + i)
    );
  }

  function isAssessment(item) {
    return item.event_type === 'assessment';
  }

  function sessionUrlCode(item) {
    if (isAssessment(item)) {
      return item.id;
    }

    return item.public_code || item.id;
  }

  function itemUrl(item) {
    if (item.url) {
      return item.url;
    }

    if (isAssessment(item)) {
      return `${assessmentUrlBase}/${item.id}`;
    }
    return `${sessionUrlBase}/${sessionUrlCode(item)}`;
  }

  function eventSubtitle(session) {
    if (isAssessment(session)) {
      return '';
    }

    return session.course_title || session.teacher_name || '';
  }

  function joinUrl(session) {
    return `${joinUrlBase}/${sessionUrlCode(session)}/vao`;
  }

  function isEndedSession(session) {
    return !isAssessment(session) && (session.phase === 'ended' || session.phase === 'cancelled');
  }

  function isLiveJoinable(session) {
    return !isAssessment(session) && session.phase === 'live' && !!session.is_joinable;
  }

  function isAssessmentTakeable(item) {
    return isAssessment(item) && item.phase === 'open' && !!item.is_takeable;
  }

  function isSessionLocked(session) {
    return !isAssessment(session) && session.is_viewable === false;
  }

  function buildJoinButtonHtml(session, compact) {
    if (!isLiveJoinable(session) || !csrfName || !csrfHash) {
      return '';
    }

    const label = session.has_attended
      ? (compact ? 'Vào lại' : 'Vào lại lớp học')
      : (compact ? 'Tham gia ngay' : 'Tham gia lớp học ngay');
    const icon = compact ? '' : '<i class="fa-solid fa-video"></i>';

    return `<form class="event-join-form" action="${escapeHtml(joinUrl(session))}" method="post">
      <input type="hidden" name="${escapeHtml(csrfName)}" value="${escapeHtml(csrfHash)}">
      <button type="submit" class="event-join-btn${compact ? ' event-join-btn--compact' : ''}">${icon}<span>${escapeHtml(label)}</span></button>
    </form>`;
  }

  function buildTakeButtonHtml(item, compact) {
    if (!isAssessmentTakeable(item)) {
      return '';
    }

    const label = compact ? 'Làm bài' : 'Làm bài kiểm tra';
    return `<a class="event-join-btn event-take-btn${compact ? ' event-join-btn--compact' : ''}" href="${escapeHtml(itemUrl(item))}">
      <i class="fa-solid fa-list-check"></i><span>${escapeHtml(label)}</span>
    </a>`;
  }

  function buildLockedEventHtml(session) {
    const color = escapeHtml(session.color || 'green');
    const teacher = eventSubtitle(session) ? `<small>${escapeHtml(eventSubtitle(session))}</small>` : '';
    const typeBadge = isAssessment(session)
      ? `<span class="event-assessment-badge"><i class="fa-solid fa-list-check"></i> Kiểm tra</span>`
      : '';

    return `<div class="event event-link event-locked ${color}" aria-disabled="true">
      ${typeBadge}
      <b>${escapeHtml(session.title)}</b>
      ${escapeHtml(session.time_label)}<br>
      ${teacher}
      <em class="event-locked-label"><i class="fa-solid fa-lock"></i> Chưa mở</em>
    </div>`;
  }

  function buildEventHtml(session) {
    if (isSessionLocked(session)) {
      return buildLockedEventHtml(session);
    }

    const ended = isEndedSession(session);
    const endedClass = ended ? ' event-ended' : '';
    const liveClass = isLiveJoinable(session) ? ' event-live' : '';
    const livePhaseClass = !isAssessment(session) && session.phase === 'live' ? ' event-live-phase' : '';
    const assessmentClass = isAssessment(session) ? ' event-assessment' : '';
    const openClass = isAssessment(session) && session.phase === 'open' ? ' event-assessment-open' : '';
    const teacher = eventSubtitle(session) ? `<small>${escapeHtml(eventSubtitle(session))}</small>` : '';
    const summaryHint = ended ? '<em class="event-ended-label">Xem tổng kết</em>' : '';
    const color = escapeHtml(session.color || 'green');
    const joinBtn = buildJoinButtonHtml(session, true);
    const takeBtn = buildTakeButtonHtml(session, true);
    const typeBadge = isAssessment(session)
      ? `<span class="event-assessment-badge"><i class="fa-solid fa-list-check"></i> Kiểm tra</span>`
      : '';

    if (isLiveJoinable(session)) {
      return `<div class="event event-card ${color}${liveClass}">
        <a class="event-link event-link-main" href="${escapeHtml(itemUrl(session))}">
          <span class="event-live-badge">LIVE</span>
          <b>${escapeHtml(session.title)}</b>
          ${escapeHtml(session.time_label)}<br>
          ${teacher}
        </a>
        ${joinBtn}
      </div>`;
    }

    if (isAssessmentTakeable(session)) {
      return `<div class="event event-card ${color}${assessmentClass}${openClass}">
        <a class="event-link event-link-main" href="${escapeHtml(itemUrl(session))}">
          ${typeBadge}
          <b>${escapeHtml(session.title)}</b>
          ${escapeHtml(session.time_label)}<br>
        </a>
        ${takeBtn}
      </div>`;
    }

    const liveBadge = livePhaseClass
      ? '<span class="event-live-badge">LIVE</span>'
      : '';

    return `<a class="event event-link ${color}${endedClass}${assessmentClass}${livePhaseClass}" href="${escapeHtml(itemUrl(session))}">
      ${typeBadge}
      ${liveBadge}
      <b>${escapeHtml(session.title)}</b>
      ${escapeHtml(session.time_label)}<br>
      ${teacher}
      ${summaryHint}
    </a>`;
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderMonth() {
    const year = cursor.getFullYear();
    const month = cursor.getMonth();
    const firstDay = new Date(year, month, 1);
    const startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
    const gridStart = new Date(year, month, 1 - startOffset);
    const todayKey = formatDateKey(new Date());

    let html = '<div class="month-grid">';
    for (let i = 1; i <= 7; i += 1) {
      html += `<div class="month-head">${VI_DAYS_SHORT[i % 7]}</div>`;
    }

    for (let cell = 0; cell < 42; cell += 1) {
      const date = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + cell);
      const dateKey = formatDateKey(date);
      const inMonth = date.getMonth() === month;
      const isToday = dateKey === todayKey;
      const daySessions = sessionsForDate(dateKey);

      let classes = 'month-cell';
      if (!inMonth) classes += ' other-month';
      if (isToday) classes += ' today';

      html += `<div class="${classes}" data-date="${dateKey}">`;
      html += `<span class="month-day-num">${date.getDate()}</span>`;
      html += '<div class="month-events">';
      daySessions.forEach((session) => {
        html += buildEventHtml(session);
      });
      html += '</div></div>';
    }

    html += '</div>';
    bodyEl.innerHTML = html;
  }

  function renderWeek() {
    const weekDays = getWeekDays(cursor);
    const hours = [];
    for (let h = 7; h <= 21; h += 1) {
      hours.push(`${pad(h)}:00`);
    }
    const todayKey = formatDateKey(new Date());

    let html = '<div class="week-grid">';
    html += '<div class="week-head">Giờ</div>';
    weekDays.forEach((date) => {
      const active = formatDateKey(date) === todayKey ? ' active' : '';
      html += `<div class="week-head${active}">${VI_DAYS_SHORT[date.getDay()]}<br>${pad(date.getDate())}/${pad(date.getMonth() + 1)}</div>`;
    });

    hours.forEach((hourLabel, hourIndex) => {
      const hour = 7 + hourIndex;
      html += `<div class="hour-cell">${hourLabel}</div>`;
      weekDays.forEach((date) => {
        const dateKey = formatDateKey(date);
        const daySessions = sessionsForDate(dateKey).filter((s) => {
          const startHour = parseInt(String(s.start_time || '0').split(':')[0], 10);
          return startHour === hour;
        });
        html += '<div class="slot">';
        daySessions.forEach((session) => {
          html += buildEventHtml(session);
        });
        html += '</div>';
      });
    });

    html += '</div>';
    bodyEl.innerHTML = html;
  }

  function renderDay() {
    const dateKey = formatDateKey(cursor);
    const daySessions = sessionsForDate(dateKey);
    const todayKey = formatDateKey(new Date());
    const isToday = dateKey === todayKey;

    let html = `<div class="day-schedule${isToday ? ' is-today' : ''}">`;
    html += `<div class="day-schedule-head">
      <strong>${VI_DAYS_SHORT[cursor.getDay()]}, ${cursor.getDate()}/${cursor.getMonth() + 1}/${cursor.getFullYear()}</strong>
      <span class="pill ${daySessions.length ? 'orange' : 'gray'}">${daySessions.length} buổi</span>
    </div>`;

    if (daySessions.length === 0) {
      html += `<div class="day-schedule-empty"><p class="muted">${escapeHtml(emptyDayText)}</p></div>`;
    } else {
      html += '<div class="day-schedule-list">';
      daySessions.forEach((session) => {
        const locked = isSessionLocked(session);
        const ended = isEndedSession(session);
        const liveJoinable = isLiveJoinable(session);
        const isLive = !isAssessment(session) && session.phase === 'live';
        const itemClass = `day-schedule-item${locked ? ' is-locked' : ''}${ended ? ' is-ended' : ''}${liveJoinable || isLive ? ' is-live' : ''}`;

        html += `<div class="${itemClass}">`;
        const subtitle = eventSubtitle(session);
        const meta = isAssessment(session)
          ? escapeHtml(session.course_title || '')
          : escapeHtml(subtitle || session.course_title || '');
        const pillClass = locked
          ? 'gray'
          : (isAssessment(session)
            ? (session.phase === 'open' ? 'purple' : (session.phase === 'completed' ? 'green' : 'orange'))
            : (session.phase === 'live' ? 'red' : (ended ? 'gray' : 'purple')));

        if (locked) {
          html += `<div class="day-schedule-item-link day-schedule-item-link--locked">
            <div class="time-range"><b>${escapeHtml(session.start_time)}</b>`;
          if (session.end_time) {
            html += `<small>${escapeHtml(session.end_time)}</small>`;
          }
          html += `</div>
            <div class="day-schedule-item-main">
              <strong>${escapeHtml(session.title)}</strong>
              <small>${meta}</small>
              <span class="pill ${pillClass}"><i class="fa-solid fa-lock"></i> Chưa mở</span>
            </div>
          </div>`;
        } else {
          html += `<a class="day-schedule-item-link" href="${escapeHtml(itemUrl(session))}">
            <div class="time-range"><b>${escapeHtml(session.start_time)}</b>`;
          if (session.end_time) {
            html += `<small>${escapeHtml(session.end_time)}</small>`;
          }
          html += `</div>
            <div class="day-schedule-item-main">
              <strong>${isAssessment(session) ? '<i class="fa-solid fa-list-check"></i> ' : ''}${escapeHtml(session.title)}</strong>
              <small>${meta}</small>
              <span class="pill ${pillClass}">${ended ? 'Xem tổng kết' : escapeHtml(session.phase_label)}</span>
            </div>
          </a>`;
          html += buildJoinButtonHtml(session, false);
          html += buildTakeButtonHtml(session, false);
        }
        html += '</div>';
      });
      html += '</div>';
    }

    html += '</div>';
    bodyEl.innerHTML = html;
  }

  function renderMiniMonth() {
    if (!miniGridEl || !miniLabelEl) return;

    const year = cursor.getFullYear();
    const month = cursor.getMonth();
    miniLabelEl.textContent = `${VI_MONTHS[month]}, ${year}`;

    const firstDay = new Date(year, month, 1);
    const startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
    const gridStart = new Date(year, month, 1 - startOffset);
    const todayKey = formatDateKey(new Date());

    let html = '';
    for (let i = 1; i <= 7; i += 1) {
      html += `<span class="month-mini-head">${VI_DAYS_SHORT[i % 7]}</span>`;
    }

    for (let cell = 0; cell < 42; cell += 1) {
      const date = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + cell);
      const dateKey = formatDateKey(date);
      const inMonth = date.getMonth() === month;
      const classes = [];

      if (!inMonth) classes.push('muted-day');
      if (dateKey === todayKey) classes.push('today');
      if (inMonth && sessionsForDate(dateKey).length > 0) classes.push('has-event');

      html += `<button type="button" class="month-mini-day ${classes.join(' ')}" data-date="${dateKey}">${date.getDate()}</button>`;
    }

    miniGridEl.innerHTML = html;

    miniGridEl.querySelectorAll('[data-date]').forEach((btn) => {
      btn.addEventListener('click', () => {
        cursor = parseDateKey(btn.dataset.date || '');
        view = 'day';
        syncViewButtons('day');
        render();
      });
    });
  }

  function render() {
    if (!bodyEl) return;

    updateLabel();
    bodyEl.className = `student-schedule-body student-schedule-body--${view}`;

    if (view === 'week') {
      renderWeek();
      renderMiniMonth();
      return;
    }
    if (view === 'day') {
      renderDay();
      renderMiniMonth();
      return;
    }
    renderMonth();
    renderMiniMonth();
  }

  render();
})();
