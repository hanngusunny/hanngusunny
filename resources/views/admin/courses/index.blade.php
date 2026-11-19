@extends('layouts.app', ['active' => 'courses'])

@section('title', 'Khóa học')

@section('subnav')
    <a href="{{ route('admin.courses.index') }}" class="os-tab is-on">Danh sách</a>
    <a href="{{ route('admin.courses.create') }}" class="os-tab">Tạo khóa học</a>
@endsection

@section('content')
<div class="op-stack">
    <x-page-title kicker="Quản trị" icon="fa-chalkboard" title="Khóa học" />

    <div class="op-stat-grid is-4">
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-blue"><i class="fa-solid fa-chalkboard"></i></span><p>Tổng khóa<small>Toàn bộ lớp</small></p></span><strong>{{ $stats['total'] }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-emerald"><i class="fa-solid fa-play"></i></span><p>Đang mở<small>Chưa hủy</small></p></span><strong>{{ $stats['active'] }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-sky"><i class="fa-solid fa-users"></i></span><p>Học viên<small>Đang học</small></p></span><strong>{{ $stats['members'] }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-amber"><i class="fa-solid fa-clock"></i></span><p>Buổi học<small>Mọi khóa</small></p></span><strong>{{ $stats['sessions'] }}</strong></article>
    </div>

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-chalkboard"></i></span>
            <span class="op-panel-title">Danh sách khóa học</span>
            <div class="op-panel-tools">
                <form class="flex flex-wrap items-center justify-end gap-2" method="get" action="{{ route('admin.courses.index') }}">
                    <select name="status" class="px-3 py-2 text-sm sm:w-40" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected($filters['status'] === 'active')>Đang mở</option>
                        <option value="cancelled" @selected($filters['status'] === 'cancelled')>Đã hủy</option>
                    </select>
                    <div class="flex">
                        <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm tên khóa..." class="rounded-r-none px-3 py-2 text-sm sm:w-56">
                        <button class="op-btn rounded-l-none" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </form>
                @if (($emptyCourseCount ?? 0) > 0)
                    <form method="post" action="{{ route('admin.courses.destroy-empty') }}" onsubmit="return confirm('Xóa {{ $emptyCourseCount }} khóa học không có học viên? Buổi học và dữ liệu khóa sẽ bị xóa vĩnh viễn.');">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-100 px-3 py-2 text-sm text-red-600">
                            <i class="fa-solid fa-trash"></i> Xóa khóa không có học viên ({{ $emptyCourseCount }})
                        </button>
                    </form>
                @endif
                <x-button href="{{ route('admin.courses.create') }}" icon="fa-solid fa-plus">Tạo khóa học</x-button>
            </div>
        </div>
        <div class="op-panel-body is-flush">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Khóa học</th>
                            <th class="px-5 py-3 font-medium">Giáo viên</th>
                            <th class="px-5 py-3 font-medium">Chương trình</th>
                            <th class="px-5 py-3 font-medium">Học viên</th>
                            <th class="px-5 py-3 font-medium">Buổi</th>
                            <th class="px-5 py-3 font-medium">Trạng thái</th>
                            <th class="px-5 py-3 text-right font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($courses as $course)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.courses.show', ['id' => $course->id, 'tab' => $course->isActive() ? 'members' : 'info']) }}" class="font-medium text-slate-900 hover:text-orange-600">{{ $course->title }}</a>
                                    @if ($course->description)
                                        <p class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ $course->description }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $course->teacher?->name ?: '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $course->program?->title ?: '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $course->active_members_count }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $course->sessions_count }}</td>
                                <td class="px-5 py-3">
                                    @if ($course->isCancelled())
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Đã hủy</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Đang mở</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="{{ route('admin.courses.show', ['id' => $course->id, 'tab' => $course->isActive() ? 'members' : 'info']) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600" title="Mở">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                        @if ($course->canBeDeleted())
                                            <form method="post" action="{{ route('admin.courses.destroy', $course->id) }}" onsubmit="return confirm('{{ $course->hasNoMembers() ? 'Xóa vĩnh viễn khóa học không có học viên? Toàn bộ buổi học và dữ liệu khóa sẽ bị xóa.' : 'Xóa vĩnh viễn khóa học đã hủy? Toàn bộ buổi học và dữ liệu liên quan sẽ bị xóa.' }}');">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-100 text-red-600" title="Xóa">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">Chưa có khóa học. Bấm <strong>Tạo khóa học</strong> để bắt đầu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($courses->hasPages())
                <div class="os-pager">
                    {{ $courses->onEachSide(1)->links('pagination.office') }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
