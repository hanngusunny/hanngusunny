@extends('layouts.app', ['active' => 'courses'])

@section('title', $course->title)

@section('subnav')
    <a href="{{ route('admin.courses.show', ['id' => $course->id, 'tab' => 'info']) }}" class="os-tab{{ $tab === 'info' ? ' is-on' : '' }}">Thông tin khóa</a>
    @if ($course->isActive())
        <a href="{{ route('admin.courses.show', ['id' => $course->id, 'tab' => 'members']) }}" class="os-tab{{ $tab === 'members' ? ' is-on' : '' }}">Học viên</a>
        @if ($course->program_id)
            <a href="{{ route('admin.courses.show', ['id' => $course->id, 'tab' => 'schedule']) }}" class="os-tab{{ $tab === 'schedule' ? ' is-on' : '' }}">Thời gian học</a>
        @endif
    @endif
@endsection

@section('content')
<div class="op-stack">
    <x-page-title :kicker="($course->program?->title ?: 'Khóa học').' · '.($course->teacher?->name ?: '—')" icon="fa-chalkboard" :title="$course->title">
        <x-slot:aside>
            <span class="op-hero-pill">{{ $course->active_members_count ?? 0 }} học viên</span>
            <span class="op-hero-pill">{{ $course->sessions_count ?? 0 }} buổi</span>
            @if ($course->isCancelled())
                <span class="op-hero-pill is-off">Đã hủy — chỉ xem</span>
            @endif
        </x-slot:aside>
    </x-page-title>

    @if ($tab === 'info')
        <section class="op-panel">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-circle-info"></i></span>
                <span class="op-panel-title">Thông tin khóa học</span>
            </div>
            <div class="op-panel-body" style="padding:20px">
                <form method="post" action="{{ route('admin.courses.update', $course->id) }}" class="max-w-3xl">
                    @csrf
                    @include('admin.courses.partials.course-fields', ['disabled' => $course->isCancelled()])
                    @unless ($course->isCancelled())
                        <div class="mt-6 flex flex-wrap gap-2">
                            <button type="submit" class="op-btn">Lưu thông tin</button>
                            <button type="submit" form="course-cancel-form" class="rounded-lg border border-red-100 px-4 py-2 text-sm text-red-600" onclick="return confirm('Hủy khóa học? Học viên sẽ được gỡ và có thể vào khóa khác.');">Hủy khóa</button>
                            @if ($course->hasNoMembers())
                                <button type="submit" form="course-delete-form" class="rounded-lg border border-red-100 px-4 py-2 text-sm text-red-600" onclick="return confirm('Xóa vĩnh viễn khóa học không có học viên?');">Xóa khóa</button>
                            @endif
                        </div>
                    @else
                        <div class="mt-6">
                            <button type="submit" form="course-delete-form" class="rounded-lg border border-red-100 px-4 py-2 text-sm text-red-600" onclick="return confirm('Xóa vĩnh viễn khóa học đã hủy?');">Xóa khóa</button>
                        </div>
                    @endunless
                </form>
                @unless ($course->isCancelled())
                    <form id="course-cancel-form" method="post" action="{{ route('admin.courses.cancel', $course->id) }}">@csrf</form>
                    @if ($course->hasNoMembers())
                        <form id="course-delete-form" method="post" action="{{ route('admin.courses.destroy', $course->id) }}">@csrf</form>
                    @endif
                @else
                    <form id="course-delete-form" method="post" action="{{ route('admin.courses.destroy', $course->id) }}">@csrf</form>
                @endunless
            </div>
        </section>
    @elseif ($tab === 'members')
        @include('admin.courses.partials.members')
    @elseif ($tab === 'schedule')
        @include('admin.courses.partials.schedule')
    @elseif ($tab === 'evaluations')
        @include('admin.courses.partials.evaluations')
    @elseif ($tab === 'practice')
        @include('admin.courses.partials.practice')
    @elseif ($tab === 'assessments')
        @include('admin.courses.partials.assessment-results')
    @elseif ($tab === 'leave-requests')
        @include('admin.courses.partials.leave-requests')
    @elseif ($tab === 'homework')
        @include('admin.courses.partials.homework')
    @endif
</div>
@endsection

@section('drawer')
<div id="session-edit-tpl" hidden>
    <form class="space-y-3 p-5 text-sm" method="post" action="">
        @csrf
        <p class="js-edit-title font-medium text-slate-900"></p>
        <label class="block">Ngày
            <input type="date" name="session_date" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <label class="block">Bắt đầu
            <input type="time" name="start_time" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <label class="block">Kết thúc
            <input type="time" name="end_time" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <label class="block">Video ghi hình
            <input type="url" name="record_video_url" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <button type="submit" class="op-btn">Lưu buổi học</button>
    </form>
</div>
<div id="assessment-edit-tpl" hidden>
    <form class="space-y-3 p-5 text-sm" method="post" action="">
        @csrf
        <p class="js-edit-title font-medium text-slate-900"></p>
        <label class="block">Ngày
            <input type="date" name="assessment_date" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <label class="block">Bắt đầu
            <input type="time" name="start_time" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <label class="block">Kết thúc
            <input type="time" name="end_time" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <button type="submit" class="op-btn">Lưu bài kiểm tra</button>
    </form>
</div>
@endsection

@push('scripts')
@if ($tab === 'members')
<script>
    const searchUrl = @json(route('admin.courses.members.search', $course->id));
    const q = document.querySelector('.js-member-q');
    const idInput = document.querySelector('.js-member-id');
    const results = document.querySelector('.js-member-results');
    const picked = document.querySelector('.js-member-picked');
    let timer = null;

    const hideResults = () => results?.classList.add('hidden');

    q?.addEventListener('input', () => {
        idInput.value = '';
        picked.classList.add('hidden');
        clearTimeout(timer);
        const value = q.value.trim();
        if (value.length < 2) { hideResults(); return; }
        timer = setTimeout(async () => {
            const res = await fetch(searchUrl + '?q=' + encodeURIComponent(value), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            results.innerHTML = '';
            (data.items || []).forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-orange-50 ' + ((item.busy || item.in_course) ? 'opacity-60' : '');
                btn.disabled = item.busy || item.in_course;
                btn.textContent = item.name + (item.student_code ? ' · ' + item.student_code : '');
                if (item.busy) btn.textContent += ' — đang học ' + (item.busy_title || 'khóa khác');
                if (item.in_course) btn.textContent += ' — đã trong khóa';
                btn.addEventListener('click', () => {
                    idInput.value = item.id;
                    q.value = item.name;
                    picked.textContent = 'Đã chọn: ' + item.name;
                    picked.classList.remove('hidden');
                    hideResults();
                });
                results.appendChild(btn);
            });
            if (!data.items?.length) {
                results.innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">Không tìm thấy học viên trống lớp.</p>';
            }
            results.classList.remove('hidden');
        }, 220);
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.js-member-add')) hideResults();
    });
</script>
@endif
@if ($tab === 'schedule')
<script>
    (() => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const status = document.querySelector('.js-schedule-save-status');
        const sessionBody = document.querySelector('.js-schedule-session-body');
        const moreStatus = document.querySelector('.js-schedule-more-status');
        const moreCount = document.querySelector('.js-schedule-more-count');
        let statusTimer = null;
        const urlTimers = new WeakMap();

        const showStatus = (text, isError) => {
            if (!status) return;
            status.hidden = false;
            status.textContent = text;
            status.classList.toggle('text-red-600', Boolean(isError));
            status.classList.toggle('text-emerald-700', !isError);
            clearTimeout(statusTimer);
            statusTimer = setTimeout(() => { status.hidden = true; }, 2500);
        };

        const saveRow = async (row) => {
            const url = row.dataset.autosaveUrl;
            if (!url) return;
            const body = new URLSearchParams();
            body.set('_token', csrf);
            row.querySelectorAll('input[data-field]').forEach((input) => {
                body.set(input.dataset.field, input.value || '');
            });
            showStatus('Đang lưu…', false);
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.ok === false) {
                    showStatus(data.message || 'Không lưu được.', true);
                    if (window.TemplateUI) TemplateUI.toast('error', data.message || 'Không lưu được.');
                    return;
                }
                showStatus(data.message || 'Đã lưu', false);
            } catch (e) {
                showStatus('Không lưu được.', true);
            }
        };

        const openSessionDrawer = (btn) => {
            TemplateUI.openDrawer({ title: 'Sửa buổi học', html: document.getElementById('session-edit-tpl').innerHTML });
            const form = document.querySelector('#drawer-body form');
            form.action = btn.dataset.action;
            form.querySelector('.js-edit-title').textContent = btn.dataset.title || '';
            form.session_date.value = btn.dataset.date || '';
            form.start_time.value = btn.dataset.start || '';
            form.end_time.value = btn.dataset.end || '';
            form.record_video_url.value = btn.dataset.record || '';
        };

        document.addEventListener('change', (event) => {
            const input = event.target.closest('.js-schedule-autosave input[data-field]');
            if (!input) return;
            const row = input.closest('.js-schedule-autosave');
            if (row) saveRow(row);
        });

        document.addEventListener('blur', (event) => {
            const input = event.target.closest?.('.js-schedule-autosave input[data-field][type="url"]');
            if (!input) return;
            const row = input.closest('.js-schedule-autosave');
            if (!row) return;
            clearTimeout(urlTimers.get(row));
            urlTimers.set(row, setTimeout(() => saveRow(row), 80));
        }, true);

        document.addEventListener('click', (event) => {
            const sessionBtn = event.target.closest('.js-session-edit');
            if (sessionBtn) {
                openSessionDrawer(sessionBtn);
                return;
            }
            const addSlotBtn = event.target.closest('.js-assessment-slot-add');
            if (addSlotBtn) {
                event.preventDefault();
                const wrap = addSlotBtn.closest('td')?.querySelector('.js-assessment-slots');
                const url = addSlotBtn.dataset.url;
                if (!wrap || !url) return;
                fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({ _token: csrf }),
                }).then((res) => res.json().then((data) => ({ res, data }))).then(({ res, data }) => {
                    if (!res.ok || data.ok === false) {
                        showStatus(data.message || 'Không thêm được khung giờ.', true);
                        return;
                    }
                    wrap.insertAdjacentHTML('beforeend', data.html || '');
                    showStatus(data.message || 'Đã thêm khung giờ.', false);
                }).catch(() => showStatus('Không thêm được khung giờ.', true));
                return;
            }
            const delSlotBtn = event.target.closest('.js-assessment-slot-delete');
            if (!delSlotBtn) return;
            event.preventDefault();
            const url = delSlotBtn.dataset.url;
            if (!url) return;
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({ _token: csrf }),
            }).then((res) => res.json().then((data) => ({ res, data }))).then(({ res, data }) => {
                if (!res.ok || data.ok === false) {
                    showStatus(data.message || 'Không xóa được khung giờ.', true);
                    return;
                }
                delSlotBtn.closest('.js-schedule-autosave')?.remove();
                showStatus(data.message || 'Đã xóa khung giờ.', false);
            }).catch(() => showStatus('Không xóa được khung giờ.', true));
        });

        const loadMoreSessions = async () => {
            if (!sessionBody) return;
            const url = sessionBody.dataset.rowsUrl;
            let offset = Number(sessionBody.dataset.offset || 0);
            const total = Number(sessionBody.dataset.total || 0);
            const limit = Number(sessionBody.dataset.limit || 30);
            if (!url || offset >= total) {
                if (moreStatus) moreStatus.hidden = true;
                return;
            }

            while (offset < total) {
                const res = await fetch(url + '?offset=' + encodeURIComponent(String(offset)) + '&limit=' + encodeURIComponent(String(limit)), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) break;
                sessionBody.insertAdjacentHTML('beforeend', data.html || '');
                offset = Number(data.nextOffset || offset);
                sessionBody.dataset.offset = String(offset);
                if (moreCount) moreCount.textContent = String(offset);
                if (data.done) break;
            }

            if (moreStatus) moreStatus.hidden = true;
        };

        loadMoreSessions();
    })();
</script>
@endif
@endpush
