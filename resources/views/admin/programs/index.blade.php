@extends('layouts.app', ['active' => 'programs'])

@section('title', 'Chương trình học')

@section('subnav')
    <a href="{{ route('admin.programs.index') }}" class="os-tab is-on">Chương trình học</a>
@endsection

@section('content')
<div class="op-stack">
    <x-page-title kicker="Quản trị" icon="fa-graduation-cap" title="Chương trình học" />

    <div class="op-stat-grid is-4">
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-blue"><i class="fa-solid fa-layer-group"></i></span><p>Tổng chương trình<small>Toàn bộ mẫu</small></p></span><strong>{{ $stats['total'] }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-emerald"><i class="fa-solid fa-play"></i></span><p>Đang dùng<small>Chưa lưu trữ</small></p></span><strong>{{ $stats['active'] }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-sky"><i class="fa-solid fa-chalkboard"></i></span><p>Buổi học<small>Mọi trình độ</small></p></span><strong>{{ $stats['sessions'] }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-amber"><i class="fa-solid fa-box-archive"></i></span><p>Đã lưu trữ<small>Chỉ xem</small></p></span><strong>{{ $stats['archived'] }}</strong></article>
    </div>

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-graduation-cap"></i></span>
            <span class="op-panel-title">Danh sách chương trình</span>
            <div class="op-panel-tools">
                <form class="flex flex-wrap items-center justify-end gap-2" method="get" action="{{ route('admin.programs.index') }}">
                    <select name="status" class="px-3 py-2 text-sm sm:w-40" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected($filters['status'] === 'active')>Đang dùng</option>
                        <option value="archived" @selected($filters['status'] === 'archived')>Đã lưu trữ</option>
                    </select>
                    <div class="flex">
                        <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm tên chương trình..." class="rounded-r-none px-3 py-2 text-sm sm:w-56">
                        <button class="op-btn rounded-l-none" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </form>
                <button type="button" class="op-btn js-program-create"><i class="fa-solid fa-plus"></i> Tạo chương trình</button>
            </div>
        </div>
        <div class="op-panel-body is-flush">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Chương trình</th>
                            <th class="px-5 py-3 font-medium">Trình độ</th>
                            <th class="px-5 py-3 font-medium">Buổi học</th>
                            <th class="px-5 py-3 font-medium">Người tạo</th>
                            <th class="px-5 py-3 font-medium">Trạng thái</th>
                            <th class="px-5 py-3 text-right font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($programs as $program)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.programs.show', $program->id) }}" class="font-medium text-slate-900 hover:text-orange-600">{{ $program->title }}</a>
                                    @if ($program->description)
                                        <p class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ $program->description }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $program->levels_count }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $program->sessions_count }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $program->teacher?->name ?: '—' }}</td>
                                <td class="px-5 py-3">
                                    @if ($program->isArchived())
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Đã lưu trữ</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Đang dùng</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels']) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600" title="Mở">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                        <form method="post" action="{{ route('admin.programs.copy', $program->id) }}" onsubmit="return confirm('Tạo bản sao chương trình này? Bản mới sẽ có đủ trình độ, buổi học, câu hỏi, bài kiểm tra và ôn tập.');">
                                            @csrf
                                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600" title="Sao chép">
                                                <i class="fa-solid fa-copy"></i>
                                            </button>
                                        </form>
                                        @if ($program->isActive())
                                            <form method="post" action="{{ route('admin.programs.archive', $program->id) }}" onsubmit="return confirm('Lưu trữ chương trình này? Sau khi lưu trữ sẽ không chỉnh sửa được.');">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-100 text-amber-700" title="Lưu trữ">
                                                    <i class="fa-solid fa-box-archive"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">Chưa có chương trình nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($programs->hasPages())
                <div class="os-pager">
                    {{ $programs->onEachSide(1)->links('pagination.office') }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@section('drawer')
<div id="program-form-tpl" hidden>
    <form class="space-y-3 p-5 text-sm" method="post" action="{{ route('admin.programs.store') }}">
        @csrf
        <label class="block">Tên chương trình *
            <input name="title" required minlength="3" maxlength="255" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" value="{{ old('title') }}">
        </label>
        <label class="block">Mô tả
            <textarea name="description" rows="4" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">{{ old('description') }}</textarea>
        </label>
        <button type="submit" class="op-btn">Tạo và mở workspace</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.querySelector('.js-program-create')?.addEventListener('click', () => {
        TemplateUI.openDrawer({ title: 'Tạo chương trình', html: document.getElementById('program-form-tpl').innerHTML });
    });
    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', () => {
            TemplateUI.toast('error', @json($errors->first()));
            TemplateUI.openDrawer({ title: 'Tạo chương trình', html: document.getElementById('program-form-tpl').innerHTML });
        });
    @endif
</script>
@endpush
