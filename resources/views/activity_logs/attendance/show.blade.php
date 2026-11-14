@extends('layouts.app', ['active' => 'activity'])

@php
    $course = $course ?? null;
    $students = $students ?? [];
    $selectedStudent = $selectedStudent ?? null;
    $sessionRows = $sessionRows ?? [];
    $sessionCount = (int) ($sessionCount ?? 0);
    $selectedId = (int) ($selectedStudent['id'] ?? 0);

    $attendedCount = 0;
    $absentCount = 0;
    $remainingCount = 0;
    foreach ($sessionRows as $row) {
        if (! empty($row['attended'])) {
            $attendedCount++;
        } elseif (($row['phase'] ?? '') === 'upcoming') {
            $remainingCount++;
        } else {
            $absentCount++;
        }
    }
@endphp

@section('title', 'Check điểm danh · '.($course?->title ?? 'Khóa học'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/activity-logs.css') }}?v=3">
@endpush

@section('subnav')
    @include('activity_logs.partials.subnav', ['activityTab' => 'attendance'])
@endsection

@section('content')
<div class="op-stack al-page ac-page"
     data-course-id="{{ (int) $course?->id }}"
     data-toggle-tpl="{{ route('activity-attendance.toggle', ['courseId' => $course?->id ?: 0, 'sessionId' => '__SID__', 'userId' => '__UID__']) }}">
    <x-page-title
        kicker="Check điểm danh"
        icon="fa-user-check"
        :title="$course?->title"
        :lead="'Giáo viên: '.($course?->teacher?->name ?: '—').' · '.$sessionCount.' buổi · '.count($students).' học viên'"
    >
        <x-slot:aside>
            <a class="op-btn-ghost" href="{{ route('activity-attendance.index') }}"><i class="fa-solid fa-arrow-left"></i> Chọn khóa khác</a>
        </x-slot:aside>
    </x-page-title>

    @if ($selectedStudent)
        <div class="op-stat-grid is-3 ac-stats">
            <article class="op-stat">
                <span class="op-stat-top">
                    <span class="op-stat-ico is-emerald"><i class="fa-solid fa-user-check"></i></span>
                    <p>Số buổi điểm danh<small>{{ $selectedStudent['name'] }}</small></p>
                </span>
                <strong class="js-ac-stat-attended">{{ $attendedCount }}</strong>
            </article>
            <article class="op-stat">
                <span class="op-stat-top">
                    <span class="op-stat-ico is-rose"><i class="fa-solid fa-user-xmark"></i></span>
                    <p>Số buổi vắng<small>Đã qua, chưa điểm danh</small></p>
                </span>
                <strong class="js-ac-stat-absent">{{ $absentCount }}</strong>
            </article>
            <article class="op-stat">
                <span class="op-stat-top">
                    <span class="op-stat-ico is-sky"><i class="fa-regular fa-calendar"></i></span>
                    <p>Số buổi còn lại<small>Chưa diễn ra</small></p>
                </span>
                <strong class="js-ac-stat-remaining">{{ $remainingCount }}</strong>
            </article>
        </div>
    @endif

    <div class="ac-layout">
        <section class="op-panel ac-students">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-users"></i></span>
                <span class="op-panel-title">Học viên</span>
            </div>
            <div class="op-panel-body is-flush">
                @forelse ($students as $student)
                    @php $isOn = (int) $student['id'] === $selectedId; @endphp
                    <a href="{{ route('activity-attendance.show', ['courseId' => $course->id, 'hoc_vien' => $student['id']]) }}"
                       class="ac-student{{ $isOn ? ' is-on' : '' }}">
                        <span class="ac-student-main">
                            <strong>{{ $student['name'] }}</strong>
                            <small>{{ $student['phone'] !== '' ? $student['phone'] : 'Chưa có SĐT' }}</small>
                        </span>
                        <span class="ac-student-meta">
                            <strong>{{ $student['attended'] }}/{{ $student['total'] }}</strong>
                            <em>{{ $student['pct'] }}%</em>
                        </span>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-slate-500">Khóa học chưa có học viên.</div>
                @endforelse
            </div>
        </section>

        <section class="op-panel ac-sessions">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-clipboard-check"></i></span>
                <span class="op-panel-title">
                    @if ($selectedStudent)
                        Điểm danh · {{ $selectedStudent['name'] }}
                    @else
                        Điểm danh theo buổi
                    @endif
                </span>
                @if ($selectedStudent)
                    <div class="op-panel-tools">
                        <form method="post" action="{{ route('activity-attendance.mark-all', ['courseId' => $course->id, 'userId' => $selectedStudent['id']]) }}"
                              onsubmit="return confirm('Điểm danh CÓ MẶT cho tất cả buổi còn thiếu của học viên này?');">
                            @csrf
                            <button type="submit" class="op-btn-ghost"><i class="fa-solid fa-check-double"></i> Có mặt tất cả</button>
                        </form>
                    </div>
                @endif
            </div>
            <div class="op-panel-body is-flush">
                @if (! $selectedStudent)
                    <div class="px-5 py-8 text-center text-slate-500">Chọn một học viên bên trái để check điểm danh.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm ac-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Buổi học</th>
                                    <th>Ngày</th>
                                    <th>Trạng thái</th>
                                    <th>Thời gian điểm danh</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sessionRows as $row)
                                    <tr class="ac-row{{ $row['attended'] ? ' is-yes' : ' is-no' }}"
                                        data-session-id="{{ $row['id'] }}"
                                        data-user-id="{{ $selectedId }}"
                                        data-attended="{{ $row['attended'] ? '1' : '0' }}"
                                        data-phase="{{ $row['phase'] ?? '' }}">
                                        <td>{{ $row['index'] }}</td>
                                        <td>
                                            <strong>{{ $row['title'] }}</strong>
                                            @if ($row['time_label'] !== '')
                                                <div class="ac-muted">{{ $row['time_label'] }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $row['date_label'] }}</td>
                                        <td>
                                            <span class="ac-status">{{ $row['status_label'] }}</span>
                                        </td>
                                        <td class="ac-joined">
                                            @if ($row['joined_at'])
                                                {{ date('H:i d/m/Y', strtotime($row['joined_at']) ?: time()) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="ac-toggle op-btn{{ $row['attended'] ? '' : '-ghost' }} js-ac-toggle">
                                                @if ($row['attended'])
                                                    <i class="fa-solid fa-user-xmark"></i> Đánh vắng
                                                @else
                                                    <i class="fa-solid fa-user-check"></i> Có mặt
                                                @endif
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const page = document.querySelector('.ac-page');
  if (!page) return;

  const tpl = page.dataset.toggleTpl || '';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content
    || document.querySelector('input[name="_token"]')?.value
    || '';

  function refreshStats() {
    let attended = 0;
    let absent = 0;
    let remaining = 0;

    page.querySelectorAll('.ac-row').forEach((row) => {
      const isAttended = row.dataset.attended === '1';
      const phase = row.dataset.phase || '';
      if (isAttended) attended += 1;
      else if (phase === 'upcoming') remaining += 1;
      else absent += 1;
    });

    const attendedEl = page.querySelector('.js-ac-stat-attended');
    const absentEl = page.querySelector('.js-ac-stat-absent');
    const remainingEl = page.querySelector('.js-ac-stat-remaining');
    if (attendedEl) attendedEl.textContent = String(attended);
    if (absentEl) absentEl.textContent = String(absent);
    if (remainingEl) remainingEl.textContent = String(remaining);

    const active = document.querySelector('.ac-student.is-on .ac-student-meta strong');
    if (active) {
      const parts = String(active.textContent || '').split('/');
      const total = Number(parts[1] || page.querySelectorAll('.ac-row').length || 0);
      active.textContent = attended + '/' + total;
      const pct = active.parentElement?.querySelector('em');
      if (pct && total > 0) pct.textContent = Math.round((attended / total) * 100) + '%';
    }
  }

  async function toggleRow(row) {
    const sessionId = row.dataset.sessionId;
    const userId = row.dataset.userId;
    const currently = row.dataset.attended === '1';
    const next = currently ? '0' : '1';
    const url = tpl.replace('__SID__', sessionId).replace('__UID__', userId);
    const btn = row.querySelector('.js-ac-toggle');
    if (btn) btn.disabled = true;

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ attended: next }),
      });
      const data = await res.json();
      if (!res.ok || !data.ok) {
        throw new Error(data.message || 'Không cập nhật được.');
      }

      const attended = !!data.attended;
      const phase = row.dataset.phase || '';
      row.dataset.attended = attended ? '1' : '0';
      row.classList.toggle('is-yes', attended);
      row.classList.toggle('is-no', !attended);

      const status = row.querySelector('.ac-status');
      if (status) {
        status.textContent = attended
          ? 'Có mặt'
          : (phase === 'upcoming' ? 'Chưa diễn ra' : 'Vắng');
      }

      const joined = row.querySelector('.ac-joined');
      if (joined) {
        if (attended && data.joined_at) {
          const d = new Date(String(data.joined_at).replace(' ', 'T'));
          joined.textContent = Number.isNaN(d.getTime())
            ? data.joined_at
            : d.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' });
        } else {
          joined.textContent = '—';
        }
      }

      if (btn) {
        btn.className = 'ac-toggle op-btn' + (attended ? '' : '-ghost') + ' js-ac-toggle';
        btn.innerHTML = attended
          ? '<i class="fa-solid fa-user-xmark"></i> Đánh vắng'
          : '<i class="fa-solid fa-user-check"></i> Có mặt';
      }

      refreshStats();

      if (window.TemplateUI?.toast) {
        TemplateUI.toast('success', data.message || 'Đã cập nhật.');
      }
    } catch (err) {
      if (window.TemplateUI?.toast) {
        TemplateUI.toast('error', err.message || 'Lỗi cập nhật điểm danh.');
      } else {
        alert(err.message || 'Lỗi cập nhật điểm danh.');
      }
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  page.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-ac-toggle');
    if (!btn) return;
    const row = btn.closest('.ac-row');
    if (row) toggleRow(row);
  });
})();
</script>
@endpush
