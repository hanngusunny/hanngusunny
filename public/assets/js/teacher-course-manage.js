/* ==========================================================================
   Quản lý khóa học (Giáo viên) — Tương tác Màn 1, Màn 2 & Student Drawer
   ========================================================================== */
(function () {
    'use strict';

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function stripVietnamese(str) {
        return String(str || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D')
            .toLowerCase()
            .trim();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function parseJsonAttr(el, attr, fallback) {
        if (!el) return fallback;
        try {
            const raw = el.getAttribute(attr);
            return raw ? JSON.parse(raw) : fallback;
        } catch (e) {
            return fallback;
        }
    }

    /* ======================================================================
       MÀN 1: Danh sách khóa học — Live Autocomplete (#602:58122)
       ====================================================================== */
    function initCourseListPage() {
        const page = document.getElementById('snTcListPage');
        if (!page) return;

        const courses = parseJsonAttr(page, 'data-courses', []);
        const wrap = document.getElementById('snTcCourseSearchWrap');
        const input = document.getElementById('snTcCourseSearchInput');
        const clearBtn = document.getElementById('snTcCourseSearchClear');
        const panel = document.getElementById('snTcCourseSuggest');
        if (!wrap || !input || !panel) return;

        const initialQuery = input.value.trim();

        function closePanel() {
            panel.hidden = true;
            wrap.classList.remove('is-open');
        }

        function renderSuggestions(query) {
            const needle = stripVietnamese(query);
            if (!needle) {
                closePanel();
                return;
            }

            const matches = courses.filter(function (c) {
                return stripVietnamese(c.title).includes(needle)
                    || stripVietnamese(c.level_label).includes(needle);
            }).slice(0, 8);

            if (matches.length === 0) {
                panel.innerHTML = '<p class="sn-tc-suggest-empty">Không tìm thấy khóa học phù hợp</p>';
            } else {
                panel.innerHTML = matches.map(function (c) {
                    return (
                        '<a class="sn-tc-suggest-item" role="option" href="' + escapeHtml(c.url) + '">' +
                            '<span class="sn-tc-suggest-course-copy">' +
                                '<span class="sn-tc-suggest-title">' + escapeHtml(c.title) + '</span>' +
                                (c.level_label ? '<span class="sn-tc-suggest-sub">' + escapeHtml(c.level_label) + '</span>' : '') +
                            '</span>' +
                            '<span class="sn-tc-badge-status">' + escapeHtml(c.status_label || 'Đang hoạt động') + '</span>' +
                        '</a>'
                    );
                }).join('');
            }

            panel.hidden = false;
            wrap.classList.add('is-open');
        }

        input.addEventListener('input', function () {
            const val = input.value;
            if (clearBtn) clearBtn.hidden = val.length === 0;
            renderSuggestions(val);
        });

        input.addEventListener('focus', function () {
            if (input.value.trim() !== '') {
                renderSuggestions(input.value);
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                input.value = '';
                clearBtn.hidden = true;
                closePanel();
                if (initialQuery !== '' && input.form) {
                    input.form.submit();
                } else {
                    input.focus();
                }
            });
        }

        document.addEventListener('click', function (ev) {
            if (!wrap.contains(ev.target)) closePanel();
        });

        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') closePanel();
        });
    }

    /* ======================================================================
       MÀN 2 & MÀN 3: Chi tiết khóa học + Student Slide-over Drawer
       ====================================================================== */
    function initCourseDetailPage() {
        const page = document.getElementById('snTcDetailPage');
        if (!page) return;

        const students = parseJsonAttr(page, 'data-students', []);
        const filterMode = page.getAttribute('data-filter-mode') || 'week';
        const selectedSessionId = parseInt(page.getAttribute('data-selected-session') || '0', 10) || 0;

        /* ------------------------------------------------------------------
           1. Toast thông báo xanh (#1259:33424)
           ------------------------------------------------------------------ */
        const toastEl = document.getElementById('snTcToast');
        const toastMsgEl = document.getElementById('snTcToastMsg');
        let toastTimer = null;

        function showToast(message, isError) {
            if (!toastEl || !toastMsgEl || !message) return;
            toastMsgEl.textContent = message;
            toastEl.classList.toggle('is-error', !!isError);
            toastEl.hidden = false;
            // Trigger CSS transition on next frame
            requestAnimationFrame(function () {
                toastEl.classList.add('is-visible');
            });
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () {
                toastEl.classList.remove('is-visible');
                setTimeout(function () {
                    if (!toastEl.classList.contains('is-visible')) {
                        toastEl.hidden = true;
                    }
                }, 220);
            }, 3400);
        }

        /* ------------------------------------------------------------------
           2. Generic Dropdowns ([data-tc-dd]) + Week/Session Mode Tabs
           ------------------------------------------------------------------ */
        const dropdowns = Array.prototype.slice.call(page.querySelectorAll('[data-tc-dd]'));

        function closeAllDropdowns(except) {
            dropdowns.forEach(function (dd) {
                if (dd === except) return;
                dd.classList.remove('is-open');
                const btn = dd.querySelector('[data-tc-dd-btn]');
                const menu = dd.querySelector('[data-tc-dd-menu]');
                if (btn) btn.setAttribute('aria-expanded', 'false');
                if (menu) menu.hidden = true;
            });
        }

        dropdowns.forEach(function (dd) {
            const btn = dd.querySelector('[data-tc-dd-btn]');
            const menu = dd.querySelector('[data-tc-dd-menu]');
            if (!btn || !menu) return;

            btn.addEventListener('click', function (ev) {
                ev.stopPropagation();
                const willOpen = menu.hidden;
                closeAllDropdowns(dd);
                menu.hidden = !willOpen;
                dd.classList.toggle('is-open', willOpen);
                btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });

            const modeTabs = dd.querySelectorAll('[data-dd-mode-tab]');
            const modePanes = dd.querySelectorAll('[data-dd-mode-pane]');
            modeTabs.forEach(function (tabBtn) {
                tabBtn.addEventListener('click', function (ev) {
                    ev.stopPropagation();
                    const target = tabBtn.getAttribute('data-dd-mode-tab');
                    modeTabs.forEach(function (b) {
                        b.classList.toggle('is-active', b === tabBtn);
                    });
                    modePanes.forEach(function (pane) {
                        pane.hidden = pane.getAttribute('data-dd-mode-pane') !== target;
                    });
                });
            });
        });

        /* ------------------------------------------------------------------
           3. Student Live Search + Dim Backdrop (#602:55261)
           ------------------------------------------------------------------ */
        const studentWrap = document.getElementById('snTcStudentSearchWrap');
        const studentInput = document.getElementById('snTcStudentSearchInput');
        const studentClear = document.getElementById('snTcStudentSearchClear');
        const studentSuggest = document.getElementById('snTcStudentSuggest');
        const searchDim = document.getElementById('snTcSearchDim');
        const initialStudentQuery = studentInput ? studentInput.value.trim() : '';

        function closeStudentSuggest() {
            if (studentSuggest) studentSuggest.hidden = true;
            if (searchDim) searchDim.hidden = true;
            if (studentWrap) studentWrap.classList.remove('is-open');
        }

        function renderStudentSuggest(query) {
            if (!studentSuggest || !studentWrap) return;
            const needle = stripVietnamese(query);
            if (!needle) {
                closeStudentSuggest();
                return;
            }

            const matches = students.filter(function (s) {
                return stripVietnamese(s.name).includes(needle)
                    || stripVietnamese(s.student_code).includes(needle)
                    || stripVietnamese(s.tag_label).includes(needle);
            }).slice(0, 8);

            if (matches.length === 0) {
                studentSuggest.innerHTML = '<p class="sn-tc-suggest-empty">Không tìm thấy học viên phù hợp</p>';
            } else {
                studentSuggest.innerHTML = matches.map(function (s) {
                    const avInner = s.avatar
                        ? '<img src="' + escapeHtml(s.avatar) + '" alt="' + escapeHtml(s.name) + '">'
                        : '<span>' + escapeHtml(s.initials || 'HV') + '</span>';
                    return (
                        '<button type="button" class="sn-tc-suggest-item" role="option" data-suggest-drawer="' + escapeHtml(s.drawer_url) + '">' +
                            '<span class="sn-tc-suggest-student-left">' +
                                '<span class="sn-tc-avatar sn-tc-avatar--' + escapeHtml(s.avatar_tone || 'blue') + '">' + avInner + '</span>' +
                                '<span class="sn-tc-suggest-course-copy">' +
                                    '<span class="sn-tc-suggest-student-name">' + escapeHtml(s.name) + '</span>' +
                                    (s.student_code ? '<span class="sn-tc-suggest-sub">' + escapeHtml(s.student_code) + '</span>' : '') +
                                '</span>' +
                            '</span>' +
                            (s.tag_label ? '<span class="sn-tc-prog-tag is-' + escapeHtml(s.tag_tone || 'purple') + '">' + escapeHtml(s.tag_label) + '</span>' : '') +
                        '</button>'
                    );
                }).join('');
            }

            studentSuggest.hidden = false;
            if (searchDim) searchDim.hidden = false;
            studentWrap.classList.add('is-open');
        }

        if (studentInput && studentSuggest) {
            studentInput.addEventListener('input', function () {
                const val = studentInput.value;
                if (studentClear) studentClear.hidden = val.length === 0;
                renderStudentSuggest(val);
            });

            studentInput.addEventListener('focus', function () {
                if (studentInput.value.trim() !== '') {
                    renderStudentSuggest(studentInput.value);
                }
            });

            studentSuggest.addEventListener('click', function (ev) {
                const btn = ev.target.closest('[data-suggest-drawer]');
                if (!btn) return;
                const url = btn.getAttribute('data-suggest-drawer');
                closeStudentSuggest();
                if (url) openDrawer(url);
            });
        }

        if (studentClear && studentInput) {
            studentClear.addEventListener('click', function () {
                studentInput.value = '';
                studentClear.hidden = true;
                closeStudentSuggest();
                if (initialStudentQuery !== '' && studentInput.form) {
                    studentInput.form.submit();
                } else {
                    studentInput.focus();
                }
            });
        }

        if (searchDim) {
            searchDim.addEventListener('click', closeStudentSuggest);
        }

        /* ------------------------------------------------------------------
           4. Student Slide-over Drawer (#602:56064, #888:40596, #1259:30826)
           ------------------------------------------------------------------ */
        const drawerRoot = document.getElementById('snTcDrawerRoot');
        const drawerBackdrop = document.getElementById('snTcDrawerBackdrop');
        const drawerCloseBtn = document.getElementById('snTcDrawerClose');
        const drawerLoading = document.getElementById('snTcDrawerLoading');
        const drawerBody = document.getElementById('snTcDrawerBody');

        const elAvatar = document.getElementById('snTcDrawerAvatar');
        const elName = document.getElementById('snTcDrawerName');
        const elMeta = document.getElementById('snTcDrawerMeta');
        const elTag = document.getElementById('snTcDrawerTag');
        const elWorkLink = document.getElementById('snTcDrawerWorkLink');

        const elCommitTitle = document.getElementById('snTcCommitTitle');
        const elCommitSub = document.getElementById('snTcCommitSub');
        const elCommitAbsence = document.getElementById('snTcCommitAbsence');
        const elCommitHomework = document.getElementById('snTcCommitHomework');
        const commitButtons = Array.prototype.slice.call(page.querySelectorAll('[data-commit-metric]'));

        const drawerTabs = Array.prototype.slice.call(page.querySelectorAll('[data-drawer-tab]'));
        const drawerPanes = Array.prototype.slice.call(page.querySelectorAll('[data-drawer-pane]'));

        const elAttWeekHeader = document.getElementById('snTcAttWeekHeader');
        const elAttWeekCount = document.getElementById('snTcAttWeekCount');
        const elAttList = document.getElementById('snTcAttList');

        // Evaluation View & Form elements
        const evalViewCard = document.getElementById('snTcEvalViewCard');
        const evalViewWeek = document.getElementById('snTcEvalViewWeek');
        const evalViewRange = document.getElementById('snTcEvalViewRange');
        const evalViewAtt = document.getElementById('snTcViewAttitude');
        const evalViewProg = document.getElementById('snTcViewProgress');
        const evalViewPart = document.getElementById('snTcViewParticipation');
        const evalViewComment = document.getElementById('snTcViewComment');
        const evalStartEditBtn = document.getElementById('snTcEvalStartEdit');

        const evalForm = document.getElementById('snTcEvalForm');
        const evalFormWeek = document.getElementById('snTcEvalFormWeek');
        const evalFormRange = document.getElementById('snTcEvalFormRange');
        const evalFormBadge = document.getElementById('snTcEvalFormBadge');
        const evalError = document.getElementById('snTcEvalError');
        const selAttitude = document.getElementById('snTcSelectAttitude');
        const selProgress = document.getElementById('snTcSelectProgress');
        const selParticipation = document.getElementById('snTcSelectParticipation');
        const evalEditor = document.getElementById('snTcEvalEditor');
        const evalCharCount = document.getElementById('snTcEvalCharCount');
        const evalSecondaryBtn = document.getElementById('snTcEvalSecondaryBtn');
        const evalPrimaryBtn = document.getElementById('snTcEvalPrimaryBtn');

        // Confirm Exception Modal (#882:58193)
        const modalRoot = document.getElementById('snTcConfirmModal');
        const modalBackdrop = document.getElementById('snTcConfirmBackdrop');
        const modalTitle = document.getElementById('snTcConfirmTitle');
        const modalDesc = document.getElementById('snTcConfirmDesc');
        const modalCancel = document.getElementById('snTcConfirmCancel');
        const modalOk = document.getElementById('snTcConfirmOk');

        let currentDrawer = null;
        let activeDrawerTab = 'attendance';
        let evalMode = 'new'; // 'new' | 'draft' | 'edit'
        let pendingCommitment = null;
        let isBusy = false;

        function setDrawerTab(tabName) {
            activeDrawerTab = tabName === 'evaluation' ? 'evaluation' : 'attendance';
            drawerTabs.forEach(function (btn) {
                const on = btn.getAttribute('data-drawer-tab') === activeDrawerTab;
                btn.classList.toggle('is-active', on);
                btn.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            drawerPanes.forEach(function (pane) {
                pane.hidden = pane.getAttribute('data-drawer-pane') !== activeDrawerTab;
            });
        }

        drawerTabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setDrawerTab(btn.getAttribute('data-drawer-tab'));
            });
        });

        function closeAllAttendanceMenus(except) {
            if (!elAttList) return;
            elAttList.querySelectorAll('.sn-tc-att-dd').forEach(function (dd) {
                if (dd === except) return;
                dd.classList.remove('is-open');
                const menu = dd.querySelector('.sn-tc-att-menu');
                if (menu) menu.hidden = true;
            });
        }

        function updateEditorCharCount() {
            if (!evalEditor || !evalCharCount) return;
            const len = (evalEditor.textContent || '').trim().length;
            evalCharCount.textContent = len + ' ký tự';
        }

        function updateEvalSubmitState() {
            if (!evalPrimaryBtn || !selAttitude || !selProgress || !selParticipation) return;
            const allPicked = selAttitude.value !== '' && selProgress.value !== '' && selParticipation.value !== '';
            const canEdit = !currentDrawer || !currentDrawer.week || currentDrawer.week.can_edit !== false;
            evalPrimaryBtn.classList.toggle('is-ready', allPicked && canEdit);
            evalPrimaryBtn.disabled = !canEdit;
            if (evalSecondaryBtn) {
                evalSecondaryBtn.disabled = !canEdit && evalMode !== 'edit';
            }
        }

        function clearEvalFieldErrors() {
            if (evalError) {
                evalError.hidden = true;
                evalError.textContent = '';
            }
            [selAttitude, selProgress, selParticipation].forEach(function (sel) {
                if (sel && sel.parentElement) sel.parentElement.classList.remove('has-error');
            });
        }

        function populateEvalForm(drawer, mode) {
            evalMode = mode; // 'new' | 'draft' | 'edit'
            const ev = drawer.evaluation || {};
            const wk = drawer.week || {};
            const canEdit = wk.can_edit !== false;

            if (evalFormWeek) evalFormWeek.textContent = wk.title || 'Tuần';
            if (evalFormRange) evalFormRange.textContent = wk.range_text || '';

            if (evalFormBadge) {
                evalFormBadge.className = 'sn-tc-eval-badge';
                if (mode === 'edit') {
                    evalFormBadge.classList.add('is-editing');
                    evalFormBadge.textContent = 'Đang chỉnh sửa';
                } else if (mode === 'draft') {
                    evalFormBadge.classList.add('is-draft');
                    evalFormBadge.textContent = 'Bản nháp';
                } else {
                    evalFormBadge.classList.add('is-pending');
                    evalFormBadge.textContent = 'Chưa đánh giá';
                }
            }

            if (selAttitude) selAttitude.value = ev.score_attitude ? String(ev.score_attitude) : '';
            if (selProgress) selProgress.value = ev.score_progress ? String(ev.score_progress) : '';
            if (selParticipation) selParticipation.value = ev.score_participation ? String(ev.score_participation) : '';
            if (evalEditor) evalEditor.innerHTML = ev.comment_html || '';

            if (evalSecondaryBtn) {
                evalSecondaryBtn.textContent = mode === 'edit' ? 'Hủy' : 'Lưu nháp';
            }
            if (evalPrimaryBtn) {
                evalPrimaryBtn.textContent = mode === 'edit' ? 'Lưu thay đổi' : 'Gửi đánh giá';
            }

            clearEvalFieldErrors();
            if (!canEdit && evalError) {
                evalError.textContent = 'Không thể đánh giá cho tuần tương lai.';
                evalError.hidden = false;
            }
            updateEditorCharCount();
            updateEvalSubmitState();

            if (evalViewCard) evalViewCard.hidden = true;
            if (evalForm) evalForm.hidden = false;
        }

        function populateEvalView(drawer) {
            const ev = drawer.evaluation || {};
            const wk = drawer.week || {};
            if (evalViewWeek) evalViewWeek.textContent = wk.title || 'Tuần';
            if (evalViewRange) evalViewRange.textContent = wk.range_text || '';
            if (evalViewAtt) evalViewAtt.textContent = ev.label_attitude || '—';
            if (evalViewProg) evalViewProg.textContent = ev.label_progress || '—';
            if (evalViewPart) evalViewPart.textContent = ev.label_participation || '—';
            if (evalViewComment) {
                evalViewComment.innerHTML = (ev.comment_html && ev.comment_html.trim() !== '')
                    ? ev.comment_html
                    : '<p><em>Chưa có nhận xét chi tiết.</em></p>';
            }
            if (evalStartEditBtn) {
                evalStartEditBtn.hidden = wk.can_edit === false;
            }
            if (evalForm) evalForm.hidden = true;
            if (evalViewCard) evalViewCard.hidden = false;
        }

        function renderAttendanceList(drawer) {
            if (!elAttList) return;
            const wk = drawer.week || {};
            const att = drawer.attendance || {};
            const items = Array.isArray(att.items) ? att.items : [];

            if (elAttWeekHeader) elAttWeekHeader.textContent = wk.full_header || wk.title || 'Tuần';
            if (elAttWeekCount) elAttWeekCount.textContent = items.length + ' buổi trong tuần';

            if (items.length === 0) {
                elAttList.innerHTML = '<div class="sn-tc-att-empty">Không có buổi học nào trong tuần đang chọn.</div>';
                return;
            }

            const choices = [
                { key: 'present', label: 'Có mặt' },
                { key: 'late', label: 'Đi muộn' },
                { key: 'absent', label: 'Vắng' }
            ];

            elAttList.innerHTML = items.map(function (item) {
                const st = item.status || 'absent';
                const optsHtml = choices.map(function (ch) {
                    const sel = ch.key === st;
                    return (
                        '<button type="button" class="sn-tc-att-opt' + (sel ? ' is-selected' : '') + '" ' +
                            'data-att-pick="' + escapeHtml(ch.key) + '" ' +
                            'data-att-url="' + escapeHtml(item.update_url) + '">' +
                            '<span class="sn-tc-att-opt-left">' +
                                '<span class="sn-tc-att-dot is-' + escapeHtml(ch.key) + '"></span>' +
                                '<span>' + escapeHtml(ch.label) + '</span>' +
                            '</span>' +
                            (sel
                                ? '<svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3.5 8.2L6.6 11.3L12.6 5.2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                                : '') +
                        '</button>'
                    );
                }).join('');

                return (
                    '<div class="sn-tc-att-row">' +
                        '<div class="sn-tc-att-info">' +
                            '<strong class="sn-tc-att-title">' + escapeHtml(item.label) + (item.title && item.title !== item.label ? ' · ' + escapeHtml(item.title) : '') + '</strong>' +
                            '<span class="sn-tc-att-date">' + escapeHtml(item.date_label || '—') + '</span>' +
                        '</div>' +
                        '<div class="sn-tc-att-dd">' +
                            '<button type="button" class="sn-tc-att-trigger is-' + escapeHtml(st) + '" data-att-toggle aria-expanded="false">' +
                                '<span>' + escapeHtml(item.status_label || 'Vắng') + '</span>' +
                                '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '</button>' +
                            '<div class="sn-tc-att-menu" hidden>' +
                                optsHtml +
                                '<p class="sn-tc-att-note">2 lần đi muộn = 1 buổi vắng</p>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            }).join('');
        }

        function syncTableRowFromDrawer(drawer) {
            if (!drawer || !drawer.student) return;
            const uid = drawer.student.id;
            const row = page.querySelector('tr[data-student-row="' + uid + '"]');
            if (!row) return;

            // Đồng bộ cột Chuyên cần
            const attPill = row.querySelector('[data-row-att-pill]');
            const att = drawer.attendance || {};
            if (attPill) {
                if (filterMode === 'session' && selectedSessionId > 0) {
                    const hit = (att.items || []).find(function (it) {
                        return parseInt(it.session_id, 10) === selectedSessionId;
                    });
                    if (hit) {
                        attPill.className = 'sn-tc-count-pill';
                        if (hit.status === 'present') {
                            attPill.classList.add('is-green');
                            attPill.textContent = 'Có mặt';
                        } else if (hit.status === 'late') {
                            attPill.classList.add('is-yellow');
                            attPill.textContent = 'Đi muộn';
                        } else {
                            attPill.classList.add('is-red');
                            attPill.textContent = 'Vắng';
                        }
                    }
                } else {
                    const total = parseInt(att.total_count || 0, 10);
                    const done = parseInt(att.attended_count || 0, 10);
                    attPill.className = 'sn-tc-count-pill';
                    if (total <= 0) {
                        attPill.classList.add('is-gray');
                        attPill.textContent = '—';
                    } else {
                        let tone = 'red';
                        if (done === 0) tone = 'gray';
                        else if (done >= total) tone = 'green';
                        else if (done / total >= 0.5) tone = 'yellow';
                        attPill.classList.add('is-' + tone);
                        attPill.textContent = done + '/' + total + ' buổi';
                    }
                }
            }

            // Đồng bộ cột Đánh giá
            const evalEl = row.querySelector('[data-row-eval-text]');
            const evStatus = (drawer.evaluation || {}).status || 'pending';
            if (evalEl) {
                const isDone = evStatus === 'evaluated';
                evalEl.classList.toggle('is-done', isDone);
                evalEl.classList.toggle('is-pending', !isDone);
                evalEl.textContent = isDone ? 'Đã đánh giá' : 'Chưa đánh giá';
            }
        }

        function renderDrawer(drawer) {
            currentDrawer = drawer;
            const st = drawer.student || {};
            const cm = drawer.commitment || {};

            // Header
            if (elAvatar) {
                elAvatar.className = 'sn-tc-avatar sn-tc-avatar--xl sn-tc-avatar--' + (st.avatar_tone || 'blue');
                elAvatar.innerHTML = st.avatar
                    ? '<img src="' + escapeHtml(st.avatar) + '" alt="' + escapeHtml(st.name) + '">'
                    : '<span>' + escapeHtml(st.initials || 'HV') + '</span>';
            }
            if (elName) elName.textContent = st.name || 'Học viên';
            if (elMeta) elMeta.textContent = st.meta_line || '';
            if (elTag) {
                if (st.tag_label) {
                    elTag.className = 'sn-tc-prog-tag is-' + (st.tag_tone || 'purple');
                    elTag.textContent = st.tag_label;
                    elTag.hidden = false;
                } else {
                    elTag.hidden = true;
                }
            }
            if (elWorkLink) {
                if (drawer.detail_work_url) {
                    elWorkLink.href = drawer.detail_work_url;
                    elWorkLink.hidden = false;
                } else {
                    elWorkLink.hidden = true;
                }
            }

            // Commitment / Quỹ vi phạm
            if (elCommitTitle) elCommitTitle.textContent = cm.title || 'Cam kết đầu ra';
            if (elCommitSub) {
                if (cm.subtitle) {
                    elCommitSub.textContent = cm.subtitle;
                    elCommitSub.hidden = false;
                } else {
                    elCommitSub.hidden = true;
                }
            }

            const limit = cm.limit ? parseInt(cm.limit, 10) : null;
            if (elCommitAbsence) {
                elCommitAbsence.textContent = cm.absence_display || '0 buổi';
                elCommitAbsence.className = 'sn-tc-commit-val';
                if (limit !== null && cm.absence_count >= limit) elCommitAbsence.classList.add('is-danger');
                else if (limit !== null && cm.absence_count >= Math.ceil(limit * 0.75)) elCommitAbsence.classList.add('is-warn');
            }
            if (elCommitHomework) {
                elCommitHomework.textContent = cm.homework_display || '0 buổi';
                elCommitHomework.className = 'sn-tc-commit-val';
                if (limit !== null && cm.homework_count >= limit) elCommitHomework.classList.add('is-danger');
                else if (limit !== null && cm.homework_count >= Math.ceil(limit * 0.75)) elCommitHomework.classList.add('is-warn');
            }

            // Tab 1: Điểm danh
            renderAttendanceList(drawer);

            // Tab 2: Đánh giá tuần
            const evStatus = (drawer.evaluation || {}).status || 'pending';
            if (evStatus === 'evaluated') {
                populateEvalView(drawer);
            } else if (evStatus === 'draft') {
                populateEvalForm(drawer, 'draft');
            } else {
                populateEvalForm(drawer, 'new');
            }

            if (drawerLoading) drawerLoading.hidden = true;
            if (drawerBody) drawerBody.hidden = false;
            syncTableRowFromDrawer(drawer);
        }

        function openDrawer(url) {
            if (!drawerRoot || !url) return;
            closeAllDropdowns();
            closeStudentSuggest();

            drawerRoot.hidden = false;
            document.body.classList.add('sn-tc-lock-scroll');
            requestAnimationFrame(function () {
                drawerRoot.classList.add('is-open');
            });

            if (drawerBody) drawerBody.hidden = true;
            if (drawerLoading) drawerLoading.hidden = false;
            setDrawerTab('attendance');

            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (json) {
                    if (!json || !json.ok || !json.drawer) {
                        throw new Error((json && json.message) || 'Không thể tải hồ sơ học viên.');
                    }
                    renderDrawer(json.drawer);
                })
                .catch(function (err) {
                    closeDrawer();
                    showToast(err.message || 'Không thể tải hồ sơ học viên.', true);
                });
        }

        function closeDrawer() {
            if (!drawerRoot || drawerRoot.hidden) return;
            closeConfirmModal();
            drawerRoot.classList.remove('is-open');
            document.body.classList.remove('sn-tc-lock-scroll');
            setTimeout(function () {
                if (!drawerRoot.classList.contains('is-open')) {
                    drawerRoot.hidden = true;
                }
            }, 230);
        }

        // Open drawer triggers on table rows
        page.addEventListener('click', function (ev) {
            const trigger = ev.target.closest('[data-open-drawer]');
            if (!trigger) return;
            const url = trigger.getAttribute('data-open-drawer');
            if (url) openDrawer(url);
        });

        if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', closeDrawer);
        if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeDrawer);

        // Attendance 3-state dropdown inside drawer
        if (elAttList) {
            elAttList.addEventListener('click', function (ev) {
                const toggleBtn = ev.target.closest('[data-att-toggle]');
                if (toggleBtn) {
                    ev.stopPropagation();
                    const dd = toggleBtn.closest('.sn-tc-att-dd');
                    const menu = dd ? dd.querySelector('.sn-tc-att-menu') : null;
                    if (!dd || !menu) return;
                    const willOpen = menu.hidden;
                    closeAllAttendanceMenus(dd);
                    menu.hidden = !willOpen;
                    dd.classList.toggle('is-open', willOpen);
                    toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    return;
                }

                const pickBtn = ev.target.closest('[data-att-pick]');
                if (pickBtn && !isBusy) {
                    ev.stopPropagation();
                    const status = pickBtn.getAttribute('data-att-pick');
                    const url = pickBtn.getAttribute('data-att-url');
                    closeAllAttendanceMenus();
                    if (!url || !status) return;

                    isBusy = true;
                    const payload = {
                        status: status,
                        eval_week: currentDrawer && currentDrawer.week ? currentDrawer.week.key : ''
                    };

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    })
                        .then(function (res) {
                            return res.json().then(function (data) {
                                if (!res.ok || !data.ok) {
                                    throw new Error((data && data.message) || 'Không thể cập nhật điểm danh.');
                                }
                                return data;
                            });
                        })
                        .then(function (data) {
                            if (data.drawer) renderDrawer(data.drawer);
                            showToast(data.message || 'Đã cập nhật trạng thái điểm danh.');
                        })
                        .catch(function (err) {
                            showToast(err.message || 'Lỗi khi cập nhật điểm danh.', true);
                        })
                        .finally(function () {
                            isBusy = false;
                        });
                }
            });
        }

        /* ------------------------------------------------------------------
           5. Commitment Adjustment + Modal Xác nhận đặc cách (#882:58193)
           ------------------------------------------------------------------ */
        function closeConfirmModal() {
            pendingCommitment = null;
            if (modalRoot) modalRoot.hidden = true;
        }

        function openConfirmModal(metric, delta) {
            if (!currentDrawer || !modalRoot || !modalDesc) return;
            pendingCommitment = { metric: metric, delta: delta };
            const studentName = (currentDrawer.student && currentDrawer.student.name) || 'học viên';
            const label = metric === 'homework' ? 'bài tập' : 'chuyên cần';

            if (modalTitle) {
                modalTitle.textContent = delta < 0 ? 'Xác nhận đặc cách' : 'Xác nhận điều chỉnh';
            }
            if (delta < 0) {
                modalDesc.textContent = 'Bạn xác nhận đặc cách cho học sinh ' + studentName + ' 1 buổi ' + label + ', bạn chắc chắn không?';
            } else {
                modalDesc.textContent = 'Bạn xác nhận cộng thêm 1 buổi thiếu ' + label + ' cho học sinh ' + studentName + ', bạn chắc chắn không?';
            }
            modalRoot.hidden = false;
        }

        commitButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!currentDrawer || isBusy) return;
                const metric = btn.getAttribute('data-commit-metric');
                const delta = parseInt(btn.getAttribute('data-commit-delta') || '0', 10);
                if (!metric || (delta !== -1 && delta !== 1)) return;
                openConfirmModal(metric, delta);
            });
        });

        if (modalCancel) modalCancel.addEventListener('click', closeConfirmModal);
        if (modalBackdrop) modalBackdrop.addEventListener('click', closeConfirmModal);

        if (modalOk) {
            modalOk.addEventListener('click', function () {
                if (!pendingCommitment || !currentDrawer || !currentDrawer.commitment_url || isBusy) return;
                const reqData = {
                    metric: pendingCommitment.metric,
                    delta: pendingCommitment.delta,
                    eval_week: currentDrawer.week ? currentDrawer.week.key : ''
                };
                const url = currentDrawer.commitment_url;
                closeConfirmModal();
                isBusy = true;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(reqData)
                })
                    .then(function (res) {
                        return res.json().then(function (data) {
                            if (!res.ok || !data.ok) {
                                throw new Error((data && data.message) || 'Không thể lưu điều chỉnh.');
                            }
                            return data;
                        });
                    })
                    .then(function (data) {
                        if (data.drawer) renderDrawer(data.drawer);
                        showToast(data.message || 'Đã cập nhật cam kết đầu ra.');
                    })
                    .catch(function (err) {
                        showToast(err.message || 'Lỗi khi cập nhật cam kết đầu ra.', true);
                    })
                    .finally(function () {
                        isBusy = false;
                    });
            });
        }

        /* ------------------------------------------------------------------
           6. Weekly Evaluation Inline Editor (#598:50552 & #1259:30826-34041)
           ------------------------------------------------------------------ */
        if (evalStartEditBtn) {
            evalStartEditBtn.addEventListener('click', function () {
                if (!currentDrawer) return;
                populateEvalForm(currentDrawer, 'edit');
            });
        }

        [selAttitude, selProgress, selParticipation].forEach(function (sel) {
            if (!sel) return;
            sel.addEventListener('change', function () {
                if (sel.parentElement) sel.parentElement.classList.remove('has-error');
                updateEvalSubmitState();
            });
        });

        if (evalEditor) {
            evalEditor.addEventListener('input', updateEditorCharCount);
            evalEditor.addEventListener('keyup', updateEditorCharCount);
        }

        if (evalForm) {
            evalForm.querySelectorAll('.sn-tc-editor-toolbar [data-cmd]').forEach(function (btn) {
                btn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    const cmd = btn.getAttribute('data-cmd');
                    if (evalEditor) evalEditor.focus();
                    try {
                        document.execCommand(cmd, false, null);
                    } catch (e) {
                        // Browser does not support execCommand
                    }
                    updateEditorCharCount();
                });
            });
        }

        function submitDrawerEvaluation(mode) {
            if (!currentDrawer || !currentDrawer.evaluation || !currentDrawer.evaluation.save_url || isBusy) return;
            clearEvalFieldErrors();

            const attitudeVal = selAttitude ? selAttitude.value : '';
            const progressVal = selProgress ? selProgress.value : '';
            const participationVal = selParticipation ? selParticipation.value : '';

            if (mode === 'submit') {
                const missing = [];
                if (!attitudeVal) {
                    missing.push('Thái độ học tập');
                    if (selAttitude && selAttitude.parentElement) selAttitude.parentElement.classList.add('has-error');
                }
                if (!progressVal) {
                    missing.push('Bài tập');
                    if (selProgress && selProgress.parentElement) selProgress.parentElement.classList.add('has-error');
                }
                if (!participationVal) {
                    missing.push('Chuyên cần');
                    if (selParticipation && selParticipation.parentElement) selParticipation.parentElement.classList.add('has-error');
                }
                if (missing.length > 0) {
                    if (evalError) {
                        evalError.textContent = 'Vui lòng chọn đầy đủ tiêu chí đánh giá: ' + missing.join(', ') + '.';
                        evalError.hidden = false;
                    }
                    return;
                }
            }

            isBusy = true;
            if (evalPrimaryBtn) evalPrimaryBtn.disabled = true;
            if (evalSecondaryBtn) evalSecondaryBtn.disabled = true;

            const payload = {
                eval_week: currentDrawer.week ? currentDrawer.week.key : '',
                mode: mode,
                score_attitude: attitudeVal ? parseInt(attitudeVal, 10) : null,
                score_progress: progressVal ? parseInt(progressVal, 10) : null,
                score_participation: participationVal ? parseInt(participationVal, 10) : null,
                comment: evalEditor ? evalEditor.innerHTML.trim() : ''
            };

            fetch(currentDrawer.evaluation.save_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        if (!res.ok || !data.ok) {
                            const firstErr = data && data.errors
                                ? Object.values(data.errors).flat()[0]
                                : null;
                            throw new Error(firstErr || (data && data.message) || 'Không thể lưu đánh giá.');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    if (data.drawer) {
                        renderDrawer(data.drawer);
                        setDrawerTab('evaluation');
                    }
                    showToast(data.message || (mode === 'draft' ? 'Đã lưu nháp đánh giá.' : 'Đã gửi đánh giá thành công.'));
                })
                .catch(function (err) {
                    if (evalError) {
                        evalError.textContent = err.message || 'Không thể lưu đánh giá.';
                        evalError.hidden = false;
                    }
                    showToast(err.message || 'Không thể lưu đánh giá.', true);
                })
                .finally(function () {
                    isBusy = false;
                    updateEvalSubmitState();
                });
        }

        if (evalForm) {
            evalForm.addEventListener('submit', function (ev) {
                ev.preventDefault();
                submitDrawerEvaluation('submit');
            });
        }

        if (evalSecondaryBtn) {
            evalSecondaryBtn.addEventListener('click', function () {
                if (evalMode === 'edit' && currentDrawer) {
                    populateEvalView(currentDrawer);
                } else {
                    submitDrawerEvaluation('draft');
                }
            });
        }

        /* ------------------------------------------------------------------
           7. Global Outside-Click & Escape Handling + Auto-Open Drawer
           ------------------------------------------------------------------ */
        document.addEventListener('click', function (ev) {
            if (!ev.target.closest('[data-tc-dd]')) {
                closeAllDropdowns();
            }
            if (studentWrap && !studentWrap.contains(ev.target)) {
                closeStudentSuggest();
            }
            if (!ev.target.closest('.sn-tc-att-dd')) {
                closeAllAttendanceMenus();
            }
        });

        document.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Escape') return;
            if (modalRoot && !modalRoot.hidden) {
                closeConfirmModal();
                return;
            }
            if (drawerRoot && !drawerRoot.hidden) {
                closeDrawer();
                return;
            }
            closeAllDropdowns();
            closeStudentSuggest();
            closeAllAttendanceMenus();
        });

        const autoOpenUrl = page.getAttribute('data-auto-open-drawer') || '';
        if (autoOpenUrl) {
            openDrawer(autoOpenUrl);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initCourseListPage();
            initCourseDetailPage();
        });
    } else {
        initCourseListPage();
        initCourseDetailPage();
    }
})();
