@extends('layouts.app', ['active' => 'activity'])

@php
    $rows = $rows ?? null;
    $courses = $courses ?? collect();
    $students = $students ?? collect();
    $weekOptions = $weekOptions ?? [];
    $filters = $filters ?? ['course_id' => 0, 'user_id' => 0, 'period' => '', 'q' => ''];
    $stats = $stats ?? ['total' => 0, 'courses' => 0, 'students' => 0, 'this_week' => 0];
@endphp

@section('title', 'Nhận xét của giáo viên')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/activity-logs.css') }}?v=6">
@endpush

@section('subnav')
    @include('activity_logs.partials.subnav', ['activityTab' => 'comments'])
@endsection

@section('content')
<div class="op-stack al-page tc-page" data-ajax-url="{{ route('activity-comments.index') }}">
    <x-page-title
        kicker="Giám sát hệ thống"
        icon="fa-comments"
        title="Nhận xét của giáo viên"
        lead="Xem toàn bộ nhận xét đánh giá tuần của giáo viên cho học viên theo từng khóa học."
    />

    <div class="op-stat-grid is-4" id="tc-stats">
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-emerald"><i class="fa-solid fa-comments"></i></span>
                <p>Tổng nhận xét<small>Có nội dung</small></p>
            </span>
            <strong data-stat="total">{{ (int) $stats['total'] }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-blue"><i class="fa-solid fa-chalkboard"></i></span>
                <p>Khóa học<small>Có nhận xét</small></p>
            </span>
            <strong data-stat="courses">{{ (int) $stats['courses'] }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-sky"><i class="fa-solid fa-users"></i></span>
                <p>Học viên<small>Được nhận xét</small></p>
            </span>
            <strong data-stat="students">{{ (int) $stats['students'] }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-amber"><i class="fa-regular fa-calendar"></i></span>
                <p>Tuần này<small>{{ \App\Models\CourseMonthlyEvaluation::formatWeekLabel(\App\Models\CourseMonthlyEvaluation::currentWeekKey()) }}</small></p>
            </span>
            <strong data-stat="this_week">{{ (int) $stats['this_week'] }}</strong>
        </article>
    </div>

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-filter"></i></span>
            <span class="op-panel-title">Bộ lọc</span>
            <div class="op-panel-tools">
                <form class="al-filter tc-filter" id="tc-filter" method="get" action="{{ route('activity-comments.index') }}">
                    <select name="khoa" class="px-3 py-2 text-sm js-tc-filter">
                        <option value="0">Tất cả khóa học</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected((int) $filters['course_id'] === (int) $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                    <select name="hoc_vien" id="tc-student" class="px-3 py-2 text-sm js-tc-filter">
                        <option value="0">Tất cả học viên</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected((int) $filters['user_id'] === (int) $student->id)>
                                {{ $student->name }}{{ $student->phone ? ' · '.$student->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                    <select name="tuan" class="px-3 py-2 text-sm js-tc-filter">
                        <option value="">Tất cả tuần</option>
                        @foreach ($weekOptions as $week)
                            <option value="{{ $week }}" @selected(($filters['period'] ?? '') === $week)>
                                {{ \App\Models\CourseMonthlyEvaluation::formatWeekLabel($week) }}
                            </option>
                        @endforeach
                    </select>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm học viên / khóa / nội dung" class="px-3 py-2 text-sm tc-search js-tc-search" autocomplete="off">
                    <span class="tc-loading" id="tc-loading" hidden><i class="fa-solid fa-spinner fa-spin"></i></span>
                </form>
            </div>
        </div>
    </section>

    <div id="tc-results">
        @include('activity_logs.comments.partials.list', ['rows' => $rows])
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const page = document.querySelector('.tc-page');
  const form = document.getElementById('tc-filter');
  const results = document.getElementById('tc-results');
  const studentSelect = document.getElementById('tc-student');
  const loading = document.getElementById('tc-loading');
  if (!page || !form || !results) return;

  const ajaxUrl = page.dataset.ajaxUrl || form.action;
  let timer = null;
  let abort = null;

  function paramsFromForm(extra = {}) {
    const data = new FormData(form);
    const params = new URLSearchParams();
    for (const [key, value] of data.entries()) {
      if (String(value || '') === '' && key !== 'q' && key !== 'tuan') continue;
      params.set(key, String(value));
    }
    Object.entries(extra).forEach(([k, v]) => {
      if (v === null || v === undefined || v === '') params.delete(k);
      else params.set(k, String(v));
    });
    return params;
  }

  function fillStudents(items, selectedId) {
    if (!studentSelect) return;
    const current = selectedId != null ? String(selectedId) : studentSelect.value;
    studentSelect.innerHTML = '<option value="0">Tất cả học viên</option>';
    (items || []).forEach((item) => {
      const opt = document.createElement('option');
      opt.value = String(item.id);
      opt.textContent = item.label;
      if (String(item.id) === String(current)) opt.selected = true;
      studentSelect.appendChild(opt);
    });
  }

  function updateStats(stats) {
    if (!stats) return;
    Object.entries(stats).forEach(([key, value]) => {
      const el = document.querySelector('#tc-stats [data-stat="' + key + '"]');
      if (el) el.textContent = String(value ?? 0);
    });
  }

  async function load(extra = {}, pushUrl = true) {
    if (abort) abort.abort();
    abort = new AbortController();
    if (loading) loading.hidden = false;
    results.classList.add('is-loading');

    const params = paramsFromForm(extra);
    const url = ajaxUrl + (params.toString() ? '?' + params.toString() : '');

    try {
      const res = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        signal: abort.signal,
      });
      if (!res.ok) throw new Error('Không tải được dữ liệu.');
      const data = await res.json();
      results.innerHTML = data.html || '';
      fillStudents(data.students || [], params.get('hoc_vien') || '0');
      updateStats(data.stats);
      if (pushUrl) {
        history.replaceState({}, '', url);
      }
    } catch (err) {
      if (err.name === 'AbortError') return;
      if (window.TemplateUI?.toast) TemplateUI.toast('error', err.message || 'Lỗi tải nhận xét.');
    } finally {
      if (loading) loading.hidden = true;
      results.classList.remove('is-loading');
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    load();
  });

  form.querySelectorAll('.js-tc-filter').forEach((el) => {
    el.addEventListener('change', () => {
      if (el.name === 'khoa') {
        // Đổi khóa → reset học viên rồi load.
        if (studentSelect) studentSelect.value = '0';
      }
      load();
    });
  });

  const search = form.querySelector('.js-tc-search');
  if (search) {
    search.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => load(), 320);
    });
  }

  results.addEventListener('click', (e) => {
    const link = e.target.closest('.tc-pager a');
    if (!link) return;
    e.preventDefault();
    const href = link.getAttribute('href');
    if (!href) return;
    const pageParams = new URL(href, window.location.origin).searchParams;
    const page = pageParams.get('page');
    load(page ? { page } : { page: null });
  });
})();
</script>
@endpush
