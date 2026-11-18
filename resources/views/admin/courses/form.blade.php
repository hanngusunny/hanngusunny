@extends('layouts.app', ['active' => 'courses'])

@php
    $isEdit = !empty($course);
@endphp

@section('title', $isEdit ? 'Chỉnh sửa khóa học' : 'Tạo khóa học')

@section('subnav')
    <a href="{{ route('admin.courses.index') }}" class="os-tab">Danh sách</a>
    <a href="{{ $isEdit ? route('admin.courses.show', $course->id) : route('admin.courses.create') }}" class="os-tab is-on">{{ $isEdit ? 'Chỉnh sửa' : 'Tạo khóa học' }}</a>
@endsection

@section('content')
<div class="op-stack" style="font-family: 'Nunito', sans-serif;">
    <x-page-title kicker="Khóa học" icon="fa-chalkboard" :title="$isEdit ? 'Chỉnh sửa thông tin khóa' : 'Tạo khóa học mới'" lead="Thiết lập thông tin chung, phân công giảng viên và xếp lịch học hàng tuần." />
    <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden;">
        <div class="op-panel-head is-static" style="padding: 18px 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
            <span class="op-panel-ico"><i class="fa-solid fa-chalkboard" style="color: #9a4700;"></i></span>
            <span class="op-panel-title" style="font-weight: 800; font-size: 16px; color: #0f172a;">Thông tin &amp; Lịch học tuần</span>
        </div>
        <div class="op-panel-body" style="padding: 24px;">
            <form method="post" action="{{ $isEdit ? route('admin.courses.update', $course->id) : route('admin.courses.store') }}" class="max-w-3xl">
                @csrf
                @include('admin.courses.partials.course-fields', ['course' => $course ?? null, 'disabled' => false])
                <div style="margin-top: 24px; display: flex; align-items: center; gap: 12px;">
                    <a href="{{ route('admin.courses.index') }}" style="padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 14px; text-decoration: none; color: #475569;">
                        Hủy bỏ
                    </a>
                    <button type="submit" style="padding: 10px 22px; border-radius: 10px; background: #9a4700; color: #ffffff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(154, 71, 0, 0.25);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>{{ $isEdit ? 'Cập nhật khóa học' : 'Tạo khóa học ngay' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
