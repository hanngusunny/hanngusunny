(function () {
    // 1. Giữ tương thích cuộn tới buổi học đang chọn (dùng cả cho Teacher & Student)
    function centerActiveSessionItem() {
        const active = document.getElementById('student-course-session-active');
        const list = document.getElementById('student-course-session-list');
        if (active && list) {
            const top = active.offsetTop - list.offsetTop - (list.clientHeight / 2) + (active.clientHeight / 2);
            list.scrollTop = Math.max(0, top);
        }
    }
    centerActiveSessionItem();

    const coursePage = document.querySelector('.sn-course-page');
    if (!coursePage) {
        return;
    }

    // 2. Dropdown "Danh sách buổi học": click-outside đóng & tự cuộn tới buổi đang học khi mở
    const sessionDd = document.getElementById('studentCourseSessionDropdown');
    if (sessionDd) {
        sessionDd.addEventListener('toggle', function () {
            if (sessionDd.open) {
                window.requestAnimationFrame(centerActiveSessionItem);
            }
        });
        document.addEventListener('click', function (e) {
            if (sessionDd.open && !sessionDd.contains(e.target)) {
                sessionDd.removeAttribute('open');
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sessionDd.open) {
                sessionDd.removeAttribute('open');
            }
        });
    }

    // 3. Cấu trúc hóa HTML pre_session_materials thành nhóm Figma (#317:12150) & Mục lục TOC (#317:12189)
    const tocCard = document.getElementById('snCourseTocCard');
    let hasBuiltToc = false;

    function buildPreMaterialsAndToc() {
        const stage = document.querySelector('[data-sn-pre-materials]');
        const source = stage ? stage.querySelector('[data-sn-materials-source]') : null;
        if (!stage || !source) {
            return;
        }

        const rawBlocks = Array.from(source.querySelectorAll('.sn-materials-raw'));
        if (rawBlocks.length === 0) {
            return;
        }

        // Chuẩn hóa danh sách node tuần tự, tách trường hợp TinyMCE gộp <iframe> và <strong> tiêu đề vào cùng 1 <p>
        const flatNodes = [];
        rawBlocks.forEach(function (raw) {
            Array.from(raw.childNodes).forEach(function (child) {
                if (child.nodeType === Node.TEXT_NODE) {
                    const txt = child.textContent.replace(/\u00a0/g, ' ').trim();
                    if (txt) {
                        const p = document.createElement('p');
                        p.textContent = txt;
                        flatNodes.push(p);
                    }
                    return;
                }
                if (child.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }

                const iframe = child.querySelector ? child.querySelector('iframe') : null;
                if (iframe && child.tagName === 'P') {
                    // Tách các node nằm trước và sau iframe nếu có tiêu đề mục đi kèm
                    const parts = Array.from(child.childNodes);
                    let beforeNodes = [];
                    let afterNodes = [];
                    let seenIframe = false;
                    parts.forEach(function (part) {
                        if (part === iframe || (part.nodeType === Node.ELEMENT_NODE && part.contains(iframe))) {
                            seenIframe = true;
                        } else if (!seenIframe) {
                            beforeNodes.push(part);
                        } else {
                            afterNodes.push(part);
                        }
                    });

                    const beforeText = beforeNodes.map(function (n) { return n.textContent || ''; }).join('').replace(/\u00a0/g, ' ').trim();
                    if (beforeText) {
                        const pBefore = document.createElement('p');
                        beforeNodes.forEach(function (n) { pBefore.appendChild(n.cloneNode(true)); });
                        flatNodes.push(pBefore);
                    }

                    const videoWrap = document.createElement('div');
                    videoWrap.className = 'sn-video-frame';
                    const clonedIframe = iframe.cloneNode(true);
                    clonedIframe.removeAttribute('style');
                    clonedIframe.setAttribute('loading', 'lazy');
                    videoWrap.appendChild(clonedIframe);
                    flatNodes.push(videoWrap);

                    const afterText = afterNodes.map(function (n) { return n.textContent || ''; }).join('').replace(/\u00a0/g, ' ').trim();
                    if (afterText) {
                        const pAfter = document.createElement('p');
                        afterNodes.forEach(function (n) { pAfter.appendChild(n.cloneNode(true)); });
                        flatNodes.push(pAfter);
                    }
                    return;
                }

                const cleanText = (child.textContent || '').replace(/\u00a0/g, ' ').trim();
                const hasMedia = Boolean(child.querySelector && child.querySelector('img, iframe, video, audio, table'));
                if (!cleanText && !hasMedia) {
                    return;
                }
                flatNodes.push(child.cloneNode(true));
            });
        });

        if (flatNodes.length === 0) {
            return;
        }

        const groups = [];
        let currentGroup = null;
        let currentSub = null;
        let autoGroupNum = 0;

        function ensureGroup(num, title) {
            const g = {
                id: 'sn-mat-group-' + (groups.length + 1),
                num: num,
                title: title.replace(/[:：\s]+$/, '').trim(),
                nodes: [],
                subs: [],
            };
            groups.push(g);
            currentGroup = g;
            currentSub = null;
            return g;
        }

        function appendContentNode(node) {
            if (!currentGroup) {
                autoGroupNum += 1;
                ensureGroup(String(autoGroupNum), 'Tài liệu chuẩn bị');
            }
            if (currentSub) {
                currentSub.nodes.push(node);
            } else {
                currentGroup.nodes.push(node);
            }
        }

        flatNodes.forEach(function (node) {
            const tag = node.tagName ? node.tagName.toUpperCase() : '';
            const text = (node.textContent || '').replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();

            if (node.classList && node.classList.contains('sn-video-frame')) {
                appendContentNode(node);
                return;
            }

            // Bỏ qua tiêu đề tổng dạng "BÀI 1: ..." nếu nằm ngay đầu trước khi có mục 1.
            if (groups.length === 0 && tag === 'P' && /^BÀI\s+/i.test(text) && text.length < 90) {
                return;
            }

            // Nhận diện <ol><li>Từ vựng</li></ol> (mục số thứ tự TinyMCE)
            if (tag === 'OL') {
                const lis = Array.from(node.children).filter(function (el) { return el.tagName === 'LI'; });
                if (lis.length === 1) {
                    const liText = (lis[0].textContent || '').replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
                    const hasNestedMedia = Boolean(lis[0].querySelector('iframe, video, img, table'));
                    if (liText && !hasNestedMedia && liText.length <= 120) {
                        const startAttr = parseInt(node.getAttribute('start') || '', 10);
                        autoGroupNum = Number.isFinite(startAttr) && startAttr > 0 ? startAttr : (autoGroupNum + 1);
                        ensureGroup(String(autoGroupNum), liText);
                        return;
                    }
                }
            }

            // Nhận diện tiểu mục "2.1. Ngữ pháp ..." hoặc "2.1 Ngữ pháp ..."
            const subMatch = (tag === 'P' || /^H[1-6]$/.test(tag)) ? text.match(/^(\d+\.\d+)\.?\s+(.+)$/) : null;
            if (subMatch && text.length <= 160 && !node.querySelector('iframe, video, img')) {
                if (!currentGroup) {
                    autoGroupNum += 1;
                    ensureGroup(String(autoGroupNum), 'Nội dung bài học');
                }
                const subId = currentGroup.id + '-sub-' + (currentGroup.subs.length + 1);
                currentSub = {
                    id: subId,
                    label: subMatch[1] + ' ' + subMatch[2].replace(/[:：\s]+$/, '').trim(),
                    fullTitle: text,
                    nodes: [],
                };
                currentGroup.subs.push(currentSub);
                return;
            }

            // Nhận diện mục lớn "1. Từ vựng" / "2. Ngữ pháp:"
            const majorMatch = (tag === 'P' || /^H[1-6]$/.test(tag)) ? text.match(/^(\d+)\.\s+(.+)$/) : null;
            if (majorMatch && text.length <= 120 && !node.querySelector('iframe, video, img')) {
                const parsedNum = parseInt(majorMatch[1], 10);
                autoGroupNum = Number.isFinite(parsedNum) ? parsedNum : (autoGroupNum + 1);
                ensureGroup(String(autoGroupNum), majorMatch[2]);
                return;
            }

            appendContentNode(node);
        });

        if (groups.length === 0) {
            return;
        }

        // Render lại các nhóm chuẩn Figma (#317:12150)
        const frag = document.createDocumentFragment();
        groups.forEach(function (group) {
            const card = document.createElement('article');
            card.className = 'sn-material-group';
            card.id = group.id;

            const head = document.createElement('div');
            head.className = 'sn-material-group-head';
            head.innerHTML =
                '<span class="sn-material-num">' + group.num + '</span>' +
                '<h3 class="sn-material-group-title"></h3>';
            head.querySelector('.sn-material-group-title').textContent = group.title;
            card.appendChild(head);

            const body = document.createElement('div');
            body.className = 'sn-material-group-body';

            function renderNodeList(container, list) {
                let richWrap = null;
                list.forEach(function (n) {
                    if (n.classList && n.classList.contains('sn-video-frame')) {
                        richWrap = null;
                        container.appendChild(n);
                    } else {
                        if (!richWrap) {
                            richWrap = document.createElement('div');
                            richWrap.className = 'sn-material-rich-card sw-rich';
                            container.appendChild(richWrap);
                        }
                        richWrap.appendChild(n);
                    }
                });
            }

            if (group.nodes.length > 0) {
                renderNodeList(body, group.nodes);
            }

            group.subs.forEach(function (sub) {
                const subWrap = document.createElement('div');
                subWrap.className = 'sn-material-subitem';
                subWrap.id = sub.id;

                const subTitle = document.createElement('h4');
                subTitle.className = 'sn-material-subitem-title';
                subTitle.textContent = sub.fullTitle;
                subWrap.appendChild(subTitle);

                if (sub.nodes.length > 0) {
                    renderNodeList(subWrap, sub.nodes);
                }
                body.appendChild(subWrap);
            });

            card.appendChild(body);
            frag.appendChild(card);
        });

        source.replaceWith(frag);

        // Build Mục lục tự động bên dưới Course Outline (#317:12189)
        const totalTocEntries = groups.length + groups.reduce(function (acc, g) { return acc + g.subs.length; }, 0);
        if (tocCard && totalTocEntries >= 2) {
            tocCard.innerHTML = '';
            groups.forEach(function (group) {
                const gEl = document.createElement('div');
                gEl.className = 'sn-toc-group';

                const row = document.createElement('div');
                row.className = 'sn-toc-head-row';

                const link = document.createElement('a');
                link.className = 'sn-toc-link';
                link.href = '#' + group.id;
                link.textContent = group.num + '. ' + group.title;
                row.appendChild(link);

                if (group.subs.length > 0) {
                    const toggleBtn = document.createElement('button');
                    toggleBtn.type = 'button';
                    toggleBtn.className = 'sn-toc-toggle';
                    toggleBtn.setAttribute('aria-label', 'Thu gọn / mở rộng mục');
                    toggleBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 15L12 9L18 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    toggleBtn.addEventListener('click', function () {
                        gEl.classList.toggle('is-collapsed');
                    });
                    row.appendChild(toggleBtn);
                }

                gEl.appendChild(row);

                if (group.subs.length > 0) {
                    const childrenWrap = document.createElement('div');
                    childrenWrap.className = 'sn-toc-children';
                    group.subs.forEach(function (sub, sIdx) {
                        const subLink = document.createElement('a');
                        subLink.className = 'sn-toc-sublink' + (sIdx === 0 && groups.length > 1 ? ' is-active' : '');
                        subLink.href = '#' + sub.id;
                        subLink.textContent = sub.label;
                        subLink.addEventListener('click', function () {
                            tocCard.querySelectorAll('.sn-toc-link, .sn-toc-sublink').forEach(function (el) {
                                el.classList.remove('is-active');
                            });
                            subLink.classList.add('is-active');
                        });
                        childrenWrap.appendChild(subLink);
                    });
                    gEl.appendChild(childrenWrap);
                }

                link.addEventListener('click', function () {
                    tocCard.querySelectorAll('.sn-toc-link, .sn-toc-sublink').forEach(function (el) {
                        el.classList.remove('is-active');
                    });
                    link.classList.add('is-active');
                });

                tocCard.appendChild(gEl);
            });

            hasBuiltToc = true;
            syncTocVisibility();
        }
    }

    function syncTocVisibility() {
        if (!tocCard) {
            return;
        }
        if (!hasBuiltToc) {
            tocCard.hidden = true;
            return;
        }
        const subtabsWrap = coursePage.querySelector('[data-course-subtabs]');
        if (!subtabsWrap) {
            tocCard.hidden = false;
            return;
        }
        const activeBtn = subtabsWrap.querySelector('[data-subtab-btn].is-active');
        const activeKey = activeBtn ? activeBtn.getAttribute('data-subtab-btn') : 'materials';
        tocCard.hidden = activeKey !== 'materials';
    }

    buildPreMaterialsAndToc();

    // 4. Pill Sub-tabs 0ms Switching (Tài liệu chuẩn bị <-> Bài tập trước buổi / Video record <-> Bài tập sau buổi)
    const subtabsWrap = coursePage.querySelector('[data-course-subtabs]');
    if (subtabsWrap) {
        const buttons = Array.from(subtabsWrap.querySelectorAll('[data-subtab-btn]'));
        const panes = Array.from(subtabsWrap.querySelectorAll('[data-subtab-pane]'));
        const quizHash = subtabsWrap.getAttribute('data-quiz-hash') || '';

        function activateSubtab(key) {
            buttons.forEach(function (btn) {
                const isOn = btn.getAttribute('data-subtab-btn') === key;
                btn.classList.toggle('is-active', isOn);
                btn.setAttribute('aria-selected', isOn ? 'true' : 'false');
            });
            panes.forEach(function (pane) {
                pane.hidden = pane.getAttribute('data-subtab-pane') !== key;
            });
            syncTocVisibility();
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const key = btn.getAttribute('data-subtab-btn');
                if (key) {
                    activateSubtab(key);
                }
            });
        });

        // Nếu backend redirect về #session-pre-quiz hoặc #session-quiz sau khi nộp bài / bấm Làm lại
        if (quizHash && window.location.hash === quizHash) {
            activateSubtab('quiz');
        } else if (window.location.hash === '#session-reflection' || window.location.hash === '#session-record-video') {
            activateSubtab('video');
        } else {
            syncTocVisibility();
        }
    }

    // 5. Hệ thống 3 Modal Xác Nhận Chuẩn Figma (#317:12508, #317:12537, #317:12566)
    const modalBackdrop = document.getElementById('snQuizModalBackdrop');
    const modalTitle = document.getElementById('snQuizModalTitle');
    const modalDesc = document.getElementById('snQuizModalDesc');
    const modalImg = document.getElementById('snQuizModalImg');
    const modalPrimaryBtn = document.getElementById('snQuizModalPrimaryBtn');
    const modalPrimaryLabel = document.getElementById('snQuizModalPrimaryLabel');
    const modalPrimaryIcon = document.getElementById('snQuizModalPrimaryIcon');
    const modalSecondaryBtn = document.getElementById('snQuizModalSecondaryBtn');
    const modalSecondaryLabel = document.getElementById('snQuizModalSecondaryLabel');
    const modalSecondaryIcon = document.getElementById('snQuizModalSecondaryIcon');

    const ICON_ARROW_CIRCLE =
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFFFFF"/><path d="M10 8.5L13.8 12L10 15.5" stroke="#FF7614" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    const ICON_EXIT_BOX =
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M14 5H19V10M19 5L11 13M10 6H7C5.9 6 5 6.9 5 8V17C5 18.1 5.9 19 7 19H16C17.1 19 18 18.1 18 17V14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    const ICON_ARROW_UP_RIGHT =
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    const ICON_UNDO =
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 14L4 9L9 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 20V13C20 10.7909 18.2091 9 16 9H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    let activePrimaryAction = null;
    let activeSecondaryAction = null;

    function closeQuizModal() {
        if (!modalBackdrop) return;
        modalBackdrop.hidden = true;
        activePrimaryAction = null;
        activeSecondaryAction = null;
    }

    function openQuizModal(config) {
        if (!modalBackdrop) return;
        modalTitle.textContent = config.title || '';
        modalDesc.textContent = config.desc || '';
        if (config.img) {
            modalImg.src = config.img;
        }
        modalPrimaryLabel.textContent = config.primaryLabel || 'Tiếp tục';
        modalPrimaryIcon.innerHTML = config.primaryIcon || '';
        modalSecondaryLabel.textContent = config.secondaryLabel || 'Đóng';
        modalSecondaryIcon.innerHTML = config.secondaryIcon || '';
        activePrimaryAction = typeof config.onPrimary === 'function' ? config.onPrimary : null;
        activeSecondaryAction = typeof config.onSecondary === 'function' ? config.onSecondary : null;
        modalBackdrop.hidden = false;
        modalPrimaryBtn.focus();
    }

    if (modalPrimaryBtn) {
        modalPrimaryBtn.addEventListener('click', function () {
            const fn = activePrimaryAction;
            closeQuizModal();
            if (fn) fn();
        });
    }

    if (modalSecondaryBtn) {
        modalSecondaryBtn.addEventListener('click', function () {
            const fn = activeSecondaryAction;
            closeQuizModal();
            if (fn) fn();
        });
    }

    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', function (e) {
            if (e.target === modalBackdrop) {
                closeQuizModal();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modalBackdrop.hidden) {
                closeQuizModal();
            }
        });
    }

    // Đăng ký handler submit chuẩn Figma cho session-quiz-submit-guard.js
    window.snCourseQuizSubmitHandler = function (ctx) {
        if (!modalBackdrop) {
            return false;
        }
        const incompleteCount = Number(ctx.incompleteCount || 0);
        if (incompleteCount > 0) {
            openQuizModal({
                title: 'Bạn vẫn muốn nộp bài?',
                desc: 'Bạn chưa trả lời hết các câu hỏi (' + incompleteCount + ' câu chưa xong). Bạn muốn tiếp tục làm hay nộp bài ngay?',
                img: modalBackdrop.getAttribute('data-img-incomplete'),
                primaryLabel: 'Tiếp tục làm',
                primaryIcon: ICON_ARROW_CIRCLE,
                secondaryLabel: 'Nộp bài',
                secondaryIcon: ICON_ARROW_UP_RIGHT,
                onPrimary: ctx.onContinueIncomplete,
                onSecondary: ctx.onConfirmSubmit,
            });
        } else {
            openQuizModal({
                title: 'Xác nhận nộp bài?',
                desc: 'Bạn chắc chắn muốn nộp bài? Sau khi nộp, bạn không thể chỉnh sửa câu trả lời.',
                img: modalBackdrop.getAttribute('data-img-submit'),
                primaryLabel: 'Nộp bài',
                primaryIcon: ICON_ARROW_CIRCLE,
                secondaryLabel: 'Tiếp tục làm bài',
                secondaryIcon: ICON_UNDO,
                onPrimary: ctx.onConfirmSubmit,
                onSecondary: function () {},
            });
        }
        return true;
    };

    // 6. Điều khiển luồng Bài tập: Lobby <-> Focus Quiz Taking <-> Result + Navigator 1..N realtime
    const levelTitle = coursePage.getAttribute('data-course-level-title') || '';
    const sessionTitle = coursePage.getAttribute('data-course-session-title') || '';
    const courseSubline = coursePage.getAttribute('data-course-subline') || '';

    coursePage.querySelectorAll('[data-sn-quiz-flow]').forEach(function (flow) {
        const views = Array.from(flow.querySelectorAll('[data-quiz-view]'));
        const takingView = flow.querySelector('[data-quiz-view="taking"]');
        const form = flow.querySelector('form[data-sn-figma-quiz-form]');

        // Đồng bộ tiêu đề buổi học vào header của Focus Quiz Taking
        const mirrorLevel = flow.querySelector('[data-quiz-mirror-level]');
        const mirrorSession = flow.querySelector('[data-quiz-mirror-session]');
        const mirrorSub = flow.querySelector('[data-quiz-mirror-sub]');
        if (mirrorLevel && levelTitle) mirrorLevel.textContent = levelTitle;
        if (mirrorSession && sessionTitle) mirrorSession.textContent = sessionTitle;
        if (mirrorSub) mirrorSub.textContent = courseSubline;

        function setQuizView(viewName) {
            views.forEach(function (v) {
                v.hidden = v.getAttribute('data-quiz-view') !== viewName;
            });
            const isTaking = viewName === 'taking' && Boolean(takingView);
            document.body.style.overflow = isTaking ? 'hidden' : '';
            if (isTaking) {
                if (takingView) takingView.scrollTop = 0;
                refreshProgress();
            }
        }

        // Khởi tạo trạng thái ban đầu (hỗ trợ trường hợp hash redirect sau nộp bài)
        const initialView = flow.getAttribute('data-initial-view') || 'lobby';
        const isSubmitted = flow.getAttribute('data-submitted') === '1';
        if (isSubmitted && window.location.hash === '#' + flow.id) {
            setQuizView('result');
        } else {
            setQuizView(initialView);
        }

        flow.querySelectorAll('[data-quiz-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const target = btn.getAttribute('data-quiz-open');
                if (target) {
                    setQuizView(target);
                }
            });
        });

        const closeBtn = flow.querySelector('[data-quiz-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                openQuizModal({
                    title: 'Bạn muốn dừng làm bài?',
                    desc: 'Tiến độ làm bài sẽ được lưu để bạn có thể tiếp tục vào lần sau.',
                    img: modalBackdrop ? modalBackdrop.getAttribute('data-img-pause') : '',
                    primaryLabel: 'Tiếp tục làm',
                    primaryIcon: ICON_ARROW_CIRCLE,
                    secondaryLabel: 'Lưu & thoát',
                    secondaryIcon: ICON_EXIT_BOX,
                    onPrimary: function () {},
                    onSecondary: function () {
                        setQuizView('lobby');
                    },
                });
            });
        }

        // Realtime Navigator 1..N & Progress Bar
        const navButtons = Array.from(flow.querySelectorAll('[data-quiz-jump]'));
        const progressText = flow.querySelector('[data-quiz-progress-text]');
        const progressFill = flow.querySelector('[data-quiz-progress-fill]');

        function setCurrentQuestionNo(no) {
            navButtons.forEach(function (b) {
                b.classList.toggle('is-current', String(b.getAttribute('data-quiz-jump')) === String(no));
            });
        }

        function refreshProgress() {
            if (!form || navButtons.length === 0) {
                return;
            }
            const statuses = typeof window.snEvaluateQuizCompletion === 'function'
                ? window.snEvaluateQuizCompletion(form)
                : [];
            let answeredCount = 0;
            const statusMap = {};
            statuses.forEach(function (st) {
                statusMap[String(st.questionNo)] = Boolean(st.complete);
                if (st.complete) answeredCount += 1;
            });

            navButtons.forEach(function (btn) {
                const no = String(btn.getAttribute('data-quiz-jump'));
                btn.classList.toggle('is-answered', Boolean(statusMap[no]));
            });

            const total = navButtons.length;
            if (progressText) {
                progressText.textContent = answeredCount + '/' + total + ' câu';
            }
            if (progressFill) {
                const pct = total > 0 ? Math.round((answeredCount / total) * 100) : 0;
                progressFill.style.width = pct + '%';
            }
        }

        navButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const no = btn.getAttribute('data-quiz-jump');
                if (!form || !no) return;
                const item = form.querySelector('.session-quiz-item[data-question-no="' + no + '"]');
                if (item) {
                    setCurrentQuestionNo(no);
                    item.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        if (form) {
            let progressTimer = null;
            function scheduleProgressRefresh() {
                window.clearTimeout(progressTimer);
                progressTimer = window.setTimeout(refreshProgress, 60);
            }

            form.addEventListener('input', scheduleProgressRefresh);
            form.addEventListener('change', scheduleProgressRefresh);
            form.addEventListener('click', function (e) {
                const item = e.target.closest('.session-quiz-item');
                if (item && item.dataset.questionNo) {
                    setCurrentQuestionNo(item.dataset.questionNo);
                }
                scheduleProgressRefresh();
            });
            form.addEventListener('focusin', function (e) {
                const item = e.target.closest('.session-quiz-item');
                if (item && item.dataset.questionNo) {
                    setCurrentQuestionNo(item.dataset.questionNo);
                }
            });

            // Đồng bộ lần đầu (bao gồm các câu đã được autosave draft trước đó)
            window.setTimeout(refreshProgress, 250);
            window.setTimeout(refreshProgress, 900);
        }
    });
})();
