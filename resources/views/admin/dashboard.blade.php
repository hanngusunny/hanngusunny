@extends('layouts.app', ['active' => 'home'])

@section('title', 'Tổng quan')

@section('content')
<div class="op-stack">
    <x-page-title kicker="Quản trị" icon="fa-house" title="Tổng quan" />

    <div class="op-stat-grid is-4">
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-blue"><i class="fa-solid fa-users"></i></span><p>Thành viên<small>Toàn hệ thống</small></p></span><strong>{{ $totalUsers }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-emerald"><i class="fa-solid fa-user-check"></i></span><p>Đang hoạt động<small>Tài khoản mở</small></p></span><strong>{{ $activeUsers }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-sky"><i class="fa-solid fa-shield-halved"></i></span><p>Quản trị<small>Quyền admin</small></p></span><strong>{{ $adminUsers }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-amber"><i class="fa-solid fa-sun"></i></span><p>Xin chào<small>{{ auth()->user()->name }}</small></p></span><strong>{{ now()->format('d/m') }}</strong></article>
    </div>
    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-house"></i></span>
            <span class="op-panel-title">Bắt đầu làm việc</span>
        </div>
        <div class="op-panel-body" style="padding:20px">
            <p class="text-sm text-slate-600">Hệ thống đã chuyển sang Laravel. Trước tiên bạn có thể quản lý tài khoản thành viên.</p>
            @if (auth()->user()->isAdmin())
                <p class="mt-4 flex flex-wrap gap-2">
                    <a class="op-btn" href="{{ route('admin.programs.index') }}"><i class="fa-solid fa-graduation-cap"></i> Chương trình học</a>
                    <a class="op-btn" href="{{ route('admin.users.index') }}"><i class="fa-solid fa-users"></i> Quản lý thành viên</a>
                    <a class="op-btn" href="{{ route('admin.database.index') }}"><i class="fa-solid fa-database"></i> Cập nhật CSDL</a>
                </p>
            @endif
        </div>
    </section>
</div>
@endsection
