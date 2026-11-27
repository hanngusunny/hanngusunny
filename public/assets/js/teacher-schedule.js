/**
 * SUNNY — Lịch dạy Giáo viên chuẩn Figma (#602:68769)
 * Hỗ trợ 3 chế độ Ngày (mặc định) / Tuần / Tháng, lọc khóa học và Popover chi tiết buổi dạy.
 */
(function () {
  const root = document.getElementById('teacherScheduleApp');
  if (!root) return;

  let sessions = [];
  let courses = [];
  try {
    sessions = JSON.parse(root.dataset.sessions || '[]');
  } catch (e) {
    sessions = [];
  }
  try {
    courses = JSON.parse(root.dataset.courses || '[]');
  } catch (e) {
    courses = [];
  }

  const todayServerKey = root.dataset.today || '';
  const emptyDayText = root.dataset.emptyDay || 'Không có buổi dạy nào trong ngày này.';
  const bodyEl = root.querySelector('.sn-ts-calendar-body');
  const labelEl = root.querySelector('.sn-ts-date-label');
  const viewButtons = Array.from(root.querySelectorAll('.sn-ts-view-btn[data-view]'));
  const prevBtn = root.querySelector('.sn-ts-prev');
  const nextBtn = root.querySelector('.sn-ts-next');
  const todayBtn = root.querySelector('.sn-ts-today-btn');
  const allChipBtn = root.querySelector('.sn-ts-chip[data-course-filter="all"]');
  const courseChipBtns = Array.from(root.querySelectorAll('.sn-ts-chip[data-course-filter]:not([data-course-filter="all"])'));

  const popoverEl = document.getElementById('teacherSchedulePopover');
  const backdropEl = document.getElementById('teacherScheduleBackdrop');

  const HOUR_HEIGHT = 44;
  const DEFAULT_START_HOUR = 8;
  const DEFAULT_END_HOUR = 23; // 08:00 -> 23:00 (15 hàng giờ: 08:00..22:00 = 660px chuẩn Figma)

  const VI_WEEKDAYS_LONG = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  const VI_WEEKDAYS_DAY_HEAD = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
  const VI_WEEKDAYS_SHORT = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
  const VI_MONTHS = [
    'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
    'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12',
  ];

  const sessionsById = new Map();
  sessions.forEach((s) => {
    sessionsById.set(String(s.id), s);
  });

  const allCourseIds = courses.map((c) => String(c.id));
  const selectedCourseIds = new Set(allCourseIds);

  let view = root.dataset.defaultView || 'day';
  let cursor = todayServerKey ? parseDateKey(todayServerKey) : startOfDay(new Date());
  let activePopoverSessionId = null;
  let activeAnchorEl = null;

  /* ---------- Utilities ---------- */
  function startOfDay(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
  }

  function pad(n) {
    return String(n).padStart(2, '0');
  }

  function formatDateKey(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
  }

  function parseDateKey(key) {
    const parts = String(key || '').split('-').map(Number);
    if (parts.length !== 3 || parts.some(Number.isNaN)) {
      return startOfDay(new Date());
    }
    return new Date(parts[0], parts[1] - 1, parts[2]);
  }

  function getTodayKey() {
    return todayServerKey || formatDateKey(new Date());
  }

  function formatFullDateLabel(date) {
    return `${VI_WEEKDAYS_LONG[date.getDay()]}, ${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`;
  }

  function parseMinutes(hhmm) {
    const parts = String(hhmm || '').split(':');
    const h = parseInt(parts[0], 10);
    const m = parseInt(parts[1] || '0', 10);
    if (Number.isNaN(h)) return 0;
    return h * 60 + (Number.isNaN(m) ? 0 : m);
  }

  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getWeekDays(anchor) {
    const day = anchor.getDay();
    const mondayOffset = day === 0 ? -6 : 1 - day;
    const monday = new Date(anchor.getFullYear(), anchor.getMonth(), anchor.getDate() + mondayOffset);
    return Array.from({ length: 7 }, (_, i) => (
      new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + i)
    ));
  }

  function filteredSessions() {
    if (allCourseIds.length === 0) {
      return sessions;
    }
    return sessions.filter((s) => selectedCourseIds.has(String(s.course_id)));
  }

  function sessionsForDate(dateKey) {
    return filteredSessions()
      .filter((s) => s.date === dateKey)
      .sort((a, b) => parseMinutes(a.start_time) - parseMinutes(b.start_time));
  }

  function nearestSessionDateKey() {
    const list = filteredSessions();
    if (list.length === 0) return null;
    const todayKey = getTodayKey();
    const upcoming = list.find((s) => s.date >= todayKey);
    return upcoming ? upcoming.date : list[list.length - 1].date;
  }

  /* ---------- Controls & Filter Chips ---------- */
  function syncViewButtons() {
    viewButtons.forEach((btn) => {
      const isActive = btn.dataset.view === view;
      btn.classList.toggle('is-active', isActive);
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
  }

  function syncCourseChips() {
    const allSelected = allCourseIds.length === 0 || selectedCourseIds.size === allCourseIds.length;
    if (allChipBtn) {
      allChipBtn.classList.toggle('is-selected', allSelected);
      allChipBtn.setAttribute('aria-pressed', allSelected ? 'true' : 'false');
    }
    courseChipBtns.forEach((btn) => {
      const id = btn.dataset.courseFilter || '';
      const on = selectedCourseIds.has(id);
      btn.classList.toggle('is-selected', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  viewButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const nextView = btn.dataset.view || 'day';
      if (nextView === view) return;
      view = nextView;
      closePopover();
      syncViewButtons();
      render();
    });
  });

  prevBtn?.addEventListener('click', () => {
    shiftCursor(-1);
    closePopover();
    render();
  });

  nextBtn?.addEventListener('click', () => {
    shiftCursor(1);
    closePopover();
    render();
  });

  todayBtn?.addEventListener('click', () => {
    cursor = parseDateKey(getTodayKey());
    closePopover();
    render();
  });

  allChipBtn?.addEventListener('click', () => {
    allCourseIds.forEach((id) => selectedCourseIds.add(id));
    closePopover();
    syncCourseChips();
    render();
  });

  courseChipBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.courseFilter || '';
      if (!id) return;

      if (selectedCourseIds.size === allCourseIds.length && allCourseIds.length > 1) {
        // Khi đang chọn tất cả, bấm 1 khóa sẽ tắt khóa đó theo đúng kiểu filter chip toggle
        selectedCourseIds.delete(id);
      } else if (selectedCourseIds.has(id)) {
        selectedCourseIds.delete(id);
        if (selectedCourseIds.size === 0) {
          // Tự bật lại tất cả để tránh trống toàn bộ vô ý
          allCourseIds.forEach((cid) => selectedCourseIds.add(cid));
        }
      } else {
        selectedCourseIds.add(id);
      }

      closePopover();
      syncCourseChips();
      render();
    });
  });

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

  function updateHeaderLabel() {
    if (!labelEl) return;
    if (view === 'month') {
      labelEl.textContent = `${VI_MONTHS[cursor.getMonth()]}, ${cursor.getFullYear()}`;
      return;
    }
    if (view === 'week') {
      const week = getWeekDays(cursor);
      const first = week[0];
      const last = week[6];
      if (first.getFullYear() === last.getFullYear()) {
        labelEl.textContent = `${pad(first.getDate())}/${pad(first.getMonth() + 1)} – ${pad(last.getDate())}/${pad(last.getMonth() + 1)}/${last.getFullYear()}`;
      } else {
        labelEl.textContent = `${pad(first.getDate())}/${pad(first.getMonth() + 1)}/${first.getFullYear()} – ${pad(last.getDate())}/${pad(last.getMonth() + 1)}/${last.getFullYear()}`;
      }
      return;
    }
    labelEl.textContent = formatFullDateLabel(cursor);
  }

  /* ---------- Timeline Layout Helpers ---------- */
  function computeHourBounds(daySessionLists) {
    let startHour = DEFAULT_START_HOUR;
    let endHour = DEFAULT_END_HOUR;

    daySessionLists.forEach((list) => {
      list.forEach((s) => {
        const startMin = parseMinutes(s.start_time);
        const endMin = Math.max(startMin + 45, parseMinutes(s.end_time || s.start_time));
        const sH = Math.floor(startMin / 60);
        const eH = Math.ceil(endMin / 60);
        if (sH < startHour) startHour = Math.max(0, sH);
        if (eH > endHour) endHour = Math.min(24, eH);
      });
    });

    return { startHour, endHour };
  }

  /**
   * Chia cột ngang cho các buổi học trùng giờ trong cùng một ngày.
   */
  function layoutDayEvents(list) {
    const items = list.map((s) => {
      const startMin = parseMinutes(s.start_time);
      const rawEnd = s.end_time ? parseMinutes(s.end_time) : startMin + 90;
      const endMin = rawEnd > startMin ? rawEnd : startMin + 90;
      return { session: s, startMin, endMin, col: 0, totalCols: 1 };
    });

    items.sort((a, b) => a.startMin - b.startMin || a.endMin - b.endMin);

    let cluster = [];
    let clusterEnd = -1;

    function flushCluster() {
      if (cluster.length === 0) return;
      const colEnds = [];
      cluster.forEach((item) => {
        let placedCol = -1;
        for (let c = 0; c < colEnds.length; c += 1) {
          if (colEnds[c] <= item.startMin) {
            placedCol = c;
            colEnds[c] = item.endMin;
            break;
          }
        }
        if (placedCol === -1) {
          placedCol = colEnds.length;
          colEnds.push(item.endMin);
        }
        item.col = placedCol;
      });
      const total = Math.max(1, colEnds.length);
      cluster.forEach((item) => {
        item.totalCols = total;
      });
      cluster = [];
      clusterEnd = -1;
    }

    items.forEach((item) => {
      if (cluster.length > 0 && item.startMin >= clusterEnd) {
        flushCluster();
      }
      cluster.push(item);
      if (item.endMin > clusterEnd) {
        clusterEnd = item.endMin;
      }
    });
    flushCluster();

    return items;
  }

  function eventPhaseMeta(session) {
    const phase = String(session.phase || 'upcoming');
    const isDone = phase === 'ended' || phase === 'cancelled';
    const isLive = phase === 'live';
    const statusText = isDone
      ? (phase === 'cancelled' ? 'Đã hủy' : 'Đã kết thúc')
      : (isLive ? 'Đang diễn ra' : 'Sắp diễn ra');
    return { isDone, isLive, statusText };
  }

  function buildTimeRangeCompact(session) {
    if (session.start_time && session.end_time) {
      return `${session.start_time}–${session.end_time}`;
    }
    return session.start_time || '';
  }

  function buildTimelineEventHtml(positioned, startHour, isWeekMode) {
    const s = positioned.session;
    const { isDone, isLive, statusText } = eventPhaseMeta(s);
    const color = escapeHtml(s.course_color || s.color || 'orange');
    const topPx = Math.round(((positioned.startMin - startHour * 60) / 60) * HOUR_HEIGHT) + 1;
    const rawHeightPx = Math.round(((positioned.endMin - positioned.startMin) / 60) * HOUR_HEIGHT) - 2;
    const heightPx = Math.max(38, rawHeightPx);

    const padX = isWeekMode ? 3 : 4;
    const widthPct = 100 / positioned.totalCols;
    const leftPct = positioned.col * widthPct;
    const leftStyle = `calc(${leftPct.toFixed(4)}% + ${padX}px)`;
    const widthStyle = `calc(${widthPct.toFixed(4)}% - ${padX * 2}px)`;

    const classes = [
      'sn-ts-event',
      `sn-ts-event--${color}`,
      isDone ? 'is-done' : '',
      isLive ? 'is-live' : '',
      heightPx < 58 ? 'is-short' : '',
      String(s.id) === String(activePopoverSessionId) ? 'is-active' : '',
    ].filter(Boolean).join(' ');

    const shortTitle = escapeHtml(s.short_title || s.title || 'Buổi học');
    const courseTitle = escapeHtml(s.course_title || '');
    const timeRange = escapeHtml(buildTimeRangeCompact(s));
    const liveBadge = isLive ? '<span class="sn-ts-live-tag">LIVE</span>' : '';

    const showCourse = heightPx >= 48 && courseTitle !== '';
    const showTime = heightPx >= 60 && timeRange !== '';
    const showStatus = heightPx >= 76;

    return `<button
      type="button"
      class="${classes}"
      data-session-id="${escapeHtml(s.id)}"
      style="top:${topPx}px;height:${heightPx}px;left:${leftStyle};width:${widthStyle};"
      aria-haspopup="dialog"
    >
      <span class="sn-ts-event-bar" aria-hidden="true"></span>
      <span class="sn-ts-event-body">
        <span class="sn-ts-event-row1">
          <span class="sn-ts-event-title">${shortTitle}</span>
          ${liveBadge}
        </span>
        ${showCourse ? `<span class="sn-ts-event-course">${courseTitle}</span>` : ''}
        ${showTime ? `<span class="sn-ts-event-time">${timeRange}</span>` : ''}
        ${showStatus ? `<span class="sn-ts-event-status">${escapeHtml(statusText)}</span>` : ''}
      </span>
    </button>`;
  }

  function buildNowIndicatorHtml(startHour, endHour) {
    const now = new Date();
    const nowMin = now.getHours() * 60 + now.getMinutes();
    if (nowMin < startHour * 60 || nowMin > endHour * 60) {
      return '';
    }
    const topPx = Math.round(((nowMin - startHour * 60) / 60) * HOUR_HEIGHT);
    return `<div class="sn-ts-now-line" style="top:${topPx}px;" aria-hidden="true"><span class="sn-ts-now-dot"></span></div>`;
  }

  function buildHourAxisHtml(startHour, endHour, gridHeight) {
    let html = `<div class="sn-ts-hour-axis" style="height:${gridHeight}px;">`;
    for (let h = startHour; h < endHour; h += 1) {
      const idx = h - startHour;
      const top = idx === 0 ? 2 : idx * HOUR_HEIGHT - 7;
      html += `<span class="sn-ts-hour-label" style="top:${top}px;">${pad(h)}:00</span>`;
    }
    html += '</div>';
    return html;
  }

  function buildHourLinesHtml(startHour, endHour) {
    let html = '';
    for (let h = startHour; h <= endHour; h += 1) {
      const top = (h - startHour) * HOUR_HEIGHT;
      html += `<div class="sn-ts-hour-line" style="top:${top}px;"></div>`;
    }
    return html;
  }

  /* ---------- Render Day (#602:68793) ---------- */
  function renderDay() {
    const dateKey = formatDateKey(cursor);
    const todayKey = getTodayKey();
    const isToday = dateKey === todayKey;
    const daySessions = sessionsForDate(dateKey);
    const { startHour, endHour } = computeHourBounds([daySessions]);
    const totalHours = endHour - startHour;
    const gridHeight = totalHours * HOUR_HEIGHT;
    const laidOut = layoutDayEvents(daySessions);

    let html = '<div class="sn-ts-timeline sn-ts-timeline--day">';
    html += '<div class="sn-ts-timeline-head">';
    html += '<div class="sn-ts-axis-corner"></div>';
    html += `<div class="sn-ts-day-col-head">
      <span class="sn-ts-day-weekday">${VI_WEEKDAYS_DAY_HEAD[cursor.getDay()]}</span>
      <span class="sn-ts-day-num${isToday ? ' is-today' : ''}">${cursor.getDate()}</span>
    </div>`;
    html += '</div>';

    html += '<div class="sn-ts-timeline-grid">';
    html += buildHourAxisHtml(startHour, endHour, gridHeight);
    html += `<div class="sn-ts-day-col${isToday ? ' is-today' : ''}" style="height:${gridHeight}px;">`;
    html += buildHourLinesHtml(startHour, endHour);
    laidOut.forEach((item) => {
      html += buildTimelineEventHtml(item, startHour, false);
    });
    if (isToday) {
      html += buildNowIndicatorHtml(startHour, endHour);
    }
    html += '</div>';
    html += '</div>';

    if (daySessions.length === 0) {
      const nearestKey = nearestSessionDateKey();
      let jumpBtn = '';
      if (nearestKey && nearestKey !== dateKey) {
        const nearestDate = parseDateKey(nearestKey);
        const jumpLabel = `${pad(nearestDate.getDate())}/${pad(nearestDate.getMonth() + 1)}/${nearestDate.getFullYear()}`;
        jumpBtn = `<button type="button" class="sn-ts-empty-day-jump" data-jump-date="${escapeHtml(nearestKey)}">Đi tới buổi gần nhất (${escapeHtml(jumpLabel)}) →</button>`;
      }
      html += `<div class="sn-ts-empty-day-banner"><span>${escapeHtml(emptyDayText)}</span>${jumpBtn}</div>`;
    }

    html += '</div>';
    bodyEl.innerHTML = html;
  }

  /* ---------- Render Week ---------- */
  function renderWeek() {
    const weekDays = getWeekDays(cursor);
    const todayKey = getTodayKey();
    const listsByDay = weekDays.map((d) => sessionsForDate(formatDateKey(d)));
    const { startHour, endHour } = computeHourBounds(listsByDay);
    const gridHeight = (endHour - startHour) * HOUR_HEIGHT;

    let html = '<div class="sn-ts-timeline sn-ts-timeline--week">';
    html += '<div class="sn-ts-timeline-head">';
    html += '<div class="sn-ts-axis-corner"></div>';
    weekDays.forEach((d) => {
      const dk = formatDateKey(d);
      const isToday = dk === todayKey;
      html += `<button type="button" class="sn-ts-day-col-head is-clickable" data-jump-date="${dk}" title="Xem chi tiết ngày ${pad(d.getDate())}/${pad(d.getMonth() + 1)}">
        <span class="sn-ts-day-weekday">${VI_WEEKDAYS_SHORT[d.getDay()]}</span>
        <span class="sn-ts-day-num${isToday ? ' is-today' : ''}">${d.getDate()}</span>
      </button>`;
    });
    html += '</div>';

    html += '<div class="sn-ts-timeline-grid">';
    html += buildHourAxisHtml(startHour, endHour, gridHeight);
    weekDays.forEach((d, idx) => {
      const dk = formatDateKey(d);
      const isToday = dk === todayKey;
      const laidOut = layoutDayEvents(listsByDay[idx]);
      html += `<div class="sn-ts-day-col${isToday ? ' is-today' : ''}" style="height:${gridHeight}px;">`;
      html += buildHourLinesHtml(startHour, endHour);
      laidOut.forEach((item) => {
        html += buildTimelineEventHtml(item, startHour, true);
      });
      if (isToday) {
        html += buildNowIndicatorHtml(startHour, endHour);
      }
      html += '</div>';
    });
    html += '</div>';
    html += '</div>';

    bodyEl.innerHTML = html;
  }

  /* ---------- Render Month ---------- */
  function renderMonth() {
    const year = cursor.getFullYear();
    const month = cursor.getMonth();
    const firstDay = new Date(year, month, 1);
    const startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
    const gridStart = new Date(year, month, 1 - startOffset);
    const lastDay = new Date(year, month + 1, 0);
    const totalCells = Math.ceil((startOffset + lastDay.getDate()) / 7) * 7;
    const todayKey = getTodayKey();

    let html = '<div class="sn-ts-month-grid">';
    for (let i = 1; i <= 7; i += 1) {
      html += `<div class="sn-ts-month-head">${VI_WEEKDAYS_SHORT[i % 7]}</div>`;
    }

    for (let cell = 0; cell < totalCells; cell += 1) {
      const date = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + cell);
      const dk = formatDateKey(date);
      const inMonth = date.getMonth() === month;
      const isToday = dk === todayKey;
      const daySessions = sessionsForDate(dk);

      const cellClasses = ['sn-ts-month-cell'];
      if (!inMonth) cellClasses.push('is-other');
      if (isToday) cellClasses.push('is-today');

      html += `<div class="${cellClasses.join(' ')}" data-date="${dk}">`;
      html += `<button type="button" class="sn-ts-month-day-btn" data-jump-date="${dk}" title="Xem lịch ngày ${pad(date.getDate())}/${pad(date.getMonth() + 1)}">${date.getDate()}</button>`;
      html += '<div class="sn-ts-month-events">';
      daySessions.forEach((s) => {
        const { isDone, isLive } = eventPhaseMeta(s);
        const color = escapeHtml(s.course_color || s.color || 'orange');
        const classes = [
          'sn-ts-month-event',
          `sn-ts-event--${color}`,
          isDone ? 'is-done' : '',
          isLive ? 'is-live' : '',
          String(s.id) === String(activePopoverSessionId) ? 'is-active' : '',
        ].filter(Boolean).join(' ');

        const shortTitle = escapeHtml(s.short_title || s.title || 'Buổi học');
        const liveBadge = isLive ? '<span class="sn-ts-live-tag">LIVE</span>' : '';
        const timeRange = escapeHtml(buildTimeRangeCompact(s));

        html += `<button type="button" class="${classes}" data-session-id="${escapeHtml(s.id)}" aria-haspopup="dialog">
          <span class="sn-ts-event-bar" aria-hidden="true"></span>
          <span class="sn-ts-event-body">
            <span class="sn-ts-event-row1">
              <span class="sn-ts-event-title">${shortTitle}</span>
              ${liveBadge}
            </span>
            <span class="sn-ts-event-time">${timeRange}</span>
          </span>
        </button>`;
      });
      html += '</div></div>';
    }

    html += '</div>';
    bodyEl.innerHTML = html;
  }

  /* ---------- Popover chi tiết buổi dạy (#602:68840 / #549:2897) ---------- */
  function buildPopoverHtml(session) {
    const color = escapeHtml(session.course_color || session.color || 'orange');
    const courseParts = [session.course_title, session.level_title].filter(Boolean);
    const coursePillLabel = escapeHtml(courseParts.join(' · ') || 'Khóa học');

    const fullTitle = session.lesson_title
      ? `${session.short_title} · ${session.lesson_title}`
      : (session.title || session.short_title || 'Buổi học');

    const dateObj = parseDateKey(session.date);
    const dateFull = formatFullDateLabel(dateObj);
    const timeLine = session.duration_label
      ? `${session.time_label} · ${session.duration_label}`
      : (session.time_label || '');
    const memberCount = Number(session.member_count || 0);
    const memberLine = `${memberCount} học viên`;

    const phase = String(session.phase || 'upcoming');
    let statusClass = 'sn-ts-pop-status--upcoming';
    let statusLabel = session.countdown_label || 'Sắp diễn ra';
    let statusIcon = `<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle cx="12" cy="13" r="8" stroke="currentColor" stroke-width="2"/>
      <path d="M12 9V13L14.5 14.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M5 3L2 6M22 6L19 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>`;
    let footText = 'Lớp học mở trước giờ học 15 phút';

    if (phase === 'ended' || phase === 'cancelled') {
      statusClass = 'sn-ts-pop-status--done';
      statusLabel = phase === 'cancelled' ? 'Đã hủy' : 'Đã học';
      statusIcon = `<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 2C6.49 2 2 6.49 2 12C2 17.51 6.49 22 12 22C17.51 22 22 17.51 22 12C22 6.49 17.51 2 12 2ZM16.78 9.7L11.11 15.37C10.97 15.51 10.78 15.59 10.58 15.59C10.38 15.59 10.19 15.51 10.05 15.37L7.22 12.54C6.93 12.25 6.93 11.77 7.22 11.48C7.51 11.19 7.99 11.19 8.28 11.48L10.58 13.78L15.72 8.64C16.01 8.35 16.49 8.35 16.78 8.64C17.07 8.93 17.07 9.4 16.78 9.7Z" fill="currentColor"/>
      </svg>`;
      footText = session.end_time
        ? `Buổi học đã kết thúc lúc ${session.end_time}`
        : 'Buổi học đã kết thúc';
    } else if (phase === 'live') {
      if (session.is_ongoing) {
        statusClass = 'sn-ts-pop-status--live';
        statusLabel = 'Đang diễn ra';
        statusIcon = `<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="3" fill="currentColor"/>
          <path d="M8.46 15.54a5 5 0 0 1 0-7.08M15.54 8.46a5 5 0 0 1 0 7.08M5.64 18.36a9 9 0 0 1 0-12.72M18.36 5.64a9 9 0 0 1 0 12.72" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>`;
        footText = 'Buổi học đang diễn ra';
      } else {
        statusClass = 'sn-ts-pop-status--ready';
        statusLabel = session.starts_in_minutes
          ? `Còn ${session.starts_in_minutes} phút`
          : 'Sắp bắt đầu';
        footText = session.start_time
          ? `Lớp học đã mở · Bắt đầu lúc ${session.start_time}`
          : 'Lớp học đã mở';
      }
    }

    let ctaHtml = '';
    if (session.is_joinable && session.room_lobby_url) {
      ctaHtml = `<div class="sn-ts-pop-actions">
        <a class="sn-ts-pop-cta sn-ts-pop-cta--primary" href="${escapeHtml(session.room_lobby_url)}">Vào lớp</a>
        ${session.url ? `<a class="sn-ts-pop-cta sn-ts-pop-cta--tertiary" href="${escapeHtml(session.url)}">Xem chi tiết</a>` : ''}
      </div>`;
    } else if (session.url) {
      ctaHtml = `<div class="sn-ts-pop-actions">
        <a class="sn-ts-pop-cta sn-ts-pop-cta--tertiary" href="${escapeHtml(session.url)}">Xem chi tiết</a>
      </div>`;
    }

    return `
      <div class="sn-ts-pop-head">
        <span class="sn-ts-pop-course sn-ts-pop-course--${color}">
          <span class="sn-ts-pop-dot" aria-hidden="true"></span>
          <span class="sn-ts-pop-course-text">${coursePillLabel}</span>
        </span>
        <button type="button" class="sn-ts-pop-close" data-pop-close aria-label="Đóng">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>

      <h3 class="sn-ts-pop-title">${escapeHtml(fullTitle)}</h3>

      <div class="sn-ts-pop-meta">
        <div class="sn-ts-pop-meta-row">
          <svg viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <rect x="2.25" y="3.25" width="13.5" height="12.5" rx="2.5" stroke="currentColor" stroke-width="1.5"/>
            <path d="M6 1.75V4.25M12 1.75V4.25M2.25 7H15.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <span>${escapeHtml(dateFull)}</span>
        </div>
        <div class="sn-ts-pop-meta-row">
          <svg viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <circle cx="9" cy="9" r="6.75" stroke="currentColor" stroke-width="1.5"/>
            <path d="M9 5.5V9L11.5 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>${escapeHtml(timeLine)}</span>
        </div>
        <div class="sn-ts-pop-meta-row">
          <svg viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <circle cx="7" cy="6.25" r="2.5" stroke="currentColor" stroke-width="1.5"/>
            <path d="M2.5 14.5C2.5 12.1 4.5 10.5 7 10.5C9.5 10.5 11.5 12.1 11.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M12 4.5C13.1 4.8 13.9 5.75 13.9 6.95C13.9 8.15 13.1 9.1 12 9.4M13.5 10.9C14.8 11.35 15.75 12.55 15.75 14.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <span>${escapeHtml(memberLine)}</span>
        </div>
      </div>

      <div class="sn-ts-pop-status-row">
        <span class="sn-ts-pop-status ${statusClass}">
          ${statusIcon}
          <span>${escapeHtml(statusLabel)}</span>
        </span>
      </div>

      ${ctaHtml}

      <p class="sn-ts-pop-foot">${escapeHtml(footText)}</p>
    `;
  }

  function positionPopover(anchorEl) {
    if (!popoverEl || !anchorEl) return;
    const isMobile = window.innerWidth <= 640;
    if (backdropEl) {
      backdropEl.hidden = !isMobile;
    }
    if (isMobile) {
      popoverEl.style.left = '';
      popoverEl.style.top = '';
      return;
    }

    const rect = anchorEl.getBoundingClientRect();
    const popRect = popoverEl.getBoundingClientRect();
    const gap = 12;
    const margin = 12;
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    // Ưu tiên đặt bên phải sự kiện (hoặc bên trái nếu thiếu chỗ, hoặc bám góc dưới phải trên block rộng)
    let left;
    if (rect.width > vw * 0.55) {
      // Khối sự kiện kéo dài toàn chiều rộng ở chế độ Ngày: đặt nổi gọn phía bên phải như Figma (#602:68840)
      left = Math.min(vw - popRect.width - margin, rect.right - popRect.width - 20);
    } else if (rect.right + gap + popRect.width <= vw - margin) {
      left = rect.right + gap;
    } else if (rect.left - gap - popRect.width >= margin) {
      left = rect.left - gap - popRect.width;
    } else {
      left = vw - popRect.width - margin;
    }

    let top;
    if (rect.width > vw * 0.55) {
      top = rect.bottom + 8;
      if (top + popRect.height > vh - margin) {
        top = Math.max(margin, rect.top - popRect.height - 8);
      }
    } else {
      top = rect.top;
    }

    left = Math.max(margin, Math.min(left, vw - popRect.width - margin));
    top = Math.max(margin, Math.min(top, vh - popRect.height - margin));

    popoverEl.style.left = `${Math.round(left)}px`;
    popoverEl.style.top = `${Math.round(top)}px`;
  }

  function openPopover(sessionId, anchorEl) {
    if (!popoverEl) return;
    const session = sessionsById.get(String(sessionId));
    if (!session) return;

    if (activePopoverSessionId === String(sessionId) && !popoverEl.hidden) {
      closePopover();
      return;
    }

    if (activeAnchorEl) {
      activeAnchorEl.classList.remove('is-active');
    }

    activePopoverSessionId = String(sessionId);
    activeAnchorEl = anchorEl;
    if (activeAnchorEl) {
      activeAnchorEl.classList.add('is-active');
    }

    popoverEl.innerHTML = buildPopoverHtml(session);
    popoverEl.hidden = false;
    positionPopover(anchorEl);
  }

  function closePopover() {
    if (popoverEl) {
      popoverEl.hidden = true;
    }
    if (backdropEl) {
      backdropEl.hidden = true;
    }
    if (activeAnchorEl) {
      activeAnchorEl.classList.remove('is-active');
      activeAnchorEl = null;
    }
    activePopoverSessionId = null;
  }

  /* ---------- Delegated Events ---------- */
  bodyEl?.addEventListener('click', (event) => {
    const eventBtn = event.target.closest('[data-session-id]');
    if (eventBtn) {
      event.stopPropagation();
      openPopover(eventBtn.dataset.sessionId, eventBtn);
      return;
    }

    const jumpBtn = event.target.closest('[data-jump-date]');
    if (jumpBtn) {
      const targetKey = jumpBtn.dataset.jumpDate;
      if (targetKey) {
        cursor = parseDateKey(targetKey);
        view = 'day';
        closePopover();
        syncViewButtons();
        render();
      }
    }
  });

  popoverEl?.addEventListener('click', (event) => {
    if (event.target.closest('[data-pop-close]')) {
      closePopover();
    }
  });

  backdropEl?.addEventListener('click', () => {
    closePopover();
  });

  document.addEventListener('click', (event) => {
    if (!popoverEl || popoverEl.hidden) return;
    if (popoverEl.contains(event.target)) return;
    if (event.target.closest('[data-session-id]')) return;
    closePopover();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && popoverEl && !popoverEl.hidden) {
      closePopover();
    }
  });

  window.addEventListener('resize', () => {
    if (popoverEl && !popoverEl.hidden && activeAnchorEl) {
      positionPopover(activeAnchorEl);
    }
  });

  window.addEventListener('scroll', () => {
    if (popoverEl && !popoverEl.hidden && activeAnchorEl && window.innerWidth > 640) {
      positionPopover(activeAnchorEl);
    }
  }, { passive: true });

  /* ---------- Main Render ---------- */
  function render() {
    if (!bodyEl) return;
    updateHeaderLabel();
    if (view === 'week') {
      renderWeek();
    } else if (view === 'month') {
      renderMonth();
    } else {
      renderDay();
    }
  }

  syncViewButtons();
  syncCourseChips();
  render();
})();
