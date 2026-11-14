(() => {
    document.documentElement.classList.remove('os-css-pending');

    const hideBrokenAvatar = (img) => {
        if (!(img instanceof HTMLImageElement) || !img.hasAttribute('data-user-avatar-img')) {
            return;
        }

        img.hidden = true;
        img.removeAttribute('src');
        img.closest('[data-user-avatar]')?.classList.add('is-fallback');
    };

    document.addEventListener('error', (event) => {
        hideBrokenAvatar(event.target);
    }, true);

    document.querySelectorAll('[data-user-avatar-img]').forEach((img) => {
        if (img instanceof HTMLImageElement && img.getAttribute('src') && img.complete && img.naturalWidth === 0) {
            hideBrokenAvatar(img);
        }
    });

    const toastRoot = () => document.getElementById('toast-root');
    const drawer = () => document.getElementById('drawer');
    const panel = () => document.getElementById('drawer-panel');
    const body = () => document.getElementById('drawer-body');
    const title = () => document.getElementById('drawer-title');

    const tones = {
        success: 'border-emerald-200 bg-white text-emerald-800',
        error: 'border-red-200 bg-white text-red-800',
        warning: 'border-amber-200 bg-white text-amber-800',
        info: 'border-sky-200 bg-white text-sky-800',
    };
    const icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info',
    };
    const iconTones = {
        success: 'bg-emerald-50 text-emerald-600',
        error: 'bg-red-50 text-red-600',
        warning: 'bg-amber-50 text-amber-600',
        info: 'bg-sky-50 text-sky-600',
    };

    function toast(type, message, heading) {
        if (!toastRoot()) {
            return;
        }
        const headings = {
            success: heading || 'Thành công',
            error: heading || 'Có lỗi xảy ra',
            warning: heading || 'Cảnh báo',
            info: heading || 'Thông báo',
        };
        const item = document.createElement('div');
        item.className = `pointer-events-auto flex gap-3 rounded-2xl border px-4 py-3 shadow-xl shadow-slate-900/10 ${tones[type] || tones.info}`;
        item.innerHTML = `
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${iconTones[type] || iconTones.info}">
                <i class="fa-solid ${icons[type] || icons.info}"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold">${headings[type]}</p>
                <p class="mt-0.5 text-sm opacity-80">${message}</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;
        item.querySelector('button').addEventListener('click', () => item.remove());
        toastRoot().appendChild(item);
        setTimeout(() => item.remove(), 4200);
    }

    function openDrawer(options) {
        if (!drawer() || !panel() || !body()) {
            return;
        }
        if (title()) {
            title().textContent = options.title || 'Chi tiết';
        }
        body().innerHTML = options.html || '';
        drawer().classList.remove('hidden');
        requestAnimationFrame(() => panel().classList.remove('translate-x-full'));
        document.body.classList.add('overflow-hidden');
    }

    function closeDrawer() {
        if (!drawer() || !panel()) {
            return;
        }
        panel().classList.add('translate-x-full');
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => drawer().classList.add('hidden'), 200);
    }

    document.addEventListener('click', (event) => {
        if (event.target.closest('.js-drawer-close') || event.target.closest('#drawer-backdrop')) {
            closeDrawer();
            return;
        }
        const openBtn = event.target.closest('[data-open-drawer]');
        if (openBtn) {
            openDrawer({
                title: openBtn.dataset.openDrawer || 'Chi tiết',
                html: document.getElementById(openBtn.dataset.drawerTarget)?.innerHTML || '<div class="p-5 text-sm text-slate-600">Nội dung mẫu.</div>',
            });
            return;
        }
        const toastBtn = event.target.closest('[data-toast]');
        if (toastBtn) {
            toast(toastBtn.dataset.toast || 'success', toastBtn.dataset.message || 'Thao tác mẫu đã chạy.');
        }
    });

    document.addEventListener('click', (event) => {
        const tab = event.target.closest('.js-tab');
        if (tab) {
            const group = tab.dataset.tabGroup;
            document.querySelectorAll(`.js-tab[data-tab-group="${group}"]`).forEach((el) => {
                el.classList.toggle('is-on', el === tab);
                el.classList.toggle('border-transparent', el !== tab);
            });
            return;
        }

        const statusTab = event.target.closest('.js-task-status-tab');
        if (statusTab) {
            document.querySelectorAll('.js-task-status-tab').forEach((el) => {
                el.classList.remove('border-indigo-600', 'text-indigo-600', 'border-blue-600', 'text-blue-600', 'border-amber-600', 'text-amber-600', 'border-emerald-600', 'text-emerald-600', 'border-slate-700', 'text-slate-800');
                el.classList.add('border-transparent', 'text-slate-500');
            });
            const tones = {
                todo: ['border-indigo-600', 'text-indigo-600'],
                processing: ['border-blue-600', 'text-blue-600'],
                pending_review: ['border-amber-600', 'text-amber-600'],
                completed: ['border-emerald-600', 'text-emerald-600'],
                archived: ['border-slate-700', 'text-slate-800'],
            };
            statusTab.classList.remove('border-transparent', 'text-slate-500');
            (tones[statusTab.dataset.status] || tones.processing).forEach((cls) => statusTab.classList.add(cls));
            return;
        }

        const chip = event.target.closest('.js-work-view-tab');
        if (chip) {
            document.querySelectorAll('.js-work-view-tab').forEach((el) => {
                el.classList.toggle('bg-blue-600', el === chip);
                el.classList.toggle('text-white', el === chip);
                el.classList.toggle('shadow-sm', el === chip);
                el.classList.toggle('bg-slate-100', el !== chip);
                el.classList.toggle('text-slate-600', el !== chip);
            });
            return;
        }

        const kcTab = event.target.closest('.kc-status-tab');
        if (kcTab) {
            document.querySelectorAll('.kc-status-tab').forEach((el) => el.classList.toggle('is-on', el === kcTab));
            return;
        }

        const ccType = event.target.closest('.cc-type');
        if (ccType) {
            document.querySelectorAll('.cc-type').forEach((el) => el.classList.toggle('is-on', el === ccType));
        }
    });

    window.TemplateUI = { toast, openDrawer, closeDrawer };
})();
