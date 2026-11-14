@extends('layouts.app', ['active' => 'courses'])

@section('title', 'Tạo khóa học')

@section('subnav')
    <a href="{{ route('admin.courses.index') }}" class="os-tab">Danh sách</a>
    <a href="{{ route('admin.courses.create') }}" class="os-tab is-on">Tạo khóa học</a>
@endsection

@section('content')
<div class="op-stack">
    <x-page-title kicker="Khóa học" icon="fa-plus" title="Tạo khóa học mới" />
    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-plus"></i></span>
            <span class="op-panel-title">Thông tin &amp; lịch tuần</span>
        </div>
        <div class="op-panel-body" style="padding:20px">
            <form method="post" action="{{ route('admin.courses.store') }}" class="max-w-3xl">
                @csrf
                @include('admin.courses.partials.course-fields', ['course' => null, 'disabled' => false])
                <div class="mt-6">
                    <button type="submit" class="op-btn">Tạo khóa học</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
