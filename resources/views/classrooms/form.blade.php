@extends('layouts.app', ['active' => 'classroom'])

@php
    $dt = static function ($value): string {
        $raw = trim((string) old($value, ''));
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);

        return $ts ? date('Y-m-d\TH:i', $ts) : '';
    };
@endphp

@section('title', 'Tạo phòng học')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin-classrooms.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/classrooms-hub.css') }}">
@endpush

@section('subnav')
    <a href="{{ route('classroom.index') }}" class="os-tab">Phòng đang mở</a>
    <a href="{{ route('classroom.create') }}" class="os-tab is-on">Tạo phòng học</a>
    @if (auth()->user()?->isAdmin())
        <a href="{{ route('admin.classrooms.index') }}" class="os-tab">Quản lý phòng</a>
    @endif
@endsection

@section('content')
<div class="op-stack cr-page">
    <x-page-title kicker="Lớp học online" icon="fa-video" title="Tạo phòng học" lead="Thiết lập phòng video call cho học viên tham gia.">
        <x-slot:aside>
            <a class="op-btn-ghost" href="{{ route('classroom.index') }}"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
        </x-slot:aside>
    </x-page-title>

    @if ($errors->any())
        <div class="cr-errors">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ route('classroom.store') }}" class="cr-form">
        @csrf
        <div class="cr-form-grid">
            <div class="cr-form-main">
                <section class="op-panel">
                    <div class="op-panel-head is-static">
                        <span class="op-panel-ico"><i class="fa-solid fa-chalkboard-user"></i></span>
                        <span class="op-panel-title">Thông tin phòng</span>
                    </div>
                    <div class="op-panel-body cr-fields">
                        <label class="cr-field is-full">
                            <span>Tên phòng học <em>*</em></span>
                            <input type="text" name="title" required value="{{ old('title') }}" placeholder="VD: Lớp TOPIK I — Buổi 3">
                        </label>
                        <label class="cr-field is-full">
                            <span>Mô tả</span>
                            <textarea name="description" rows="3" placeholder="Nội dung buổi học, ghi chú...">{{ old('description') }}</textarea>
                        </label>
                        @if (! empty($isAdmin) && $teachers->isNotEmpty())
                            <label class="cr-field">
                                <span>Giáo viên chủ trì <em>*</em></span>
                                <select name="host_id" required>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" @selected((int) old('host_id', auth()->id()) === (int) $teacher->id)>
                                            {{ $teacher->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <label class="cr-field">
                            <span>Trạng thái <em>*</em></span>
                            <select name="status" required>
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', 'scheduled') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="cr-field">
                            <span>Quyền truy cập <em>*</em></span>
                            <select name="access_type" required>
                                @foreach ($accessTypes as $key => $label)
                                    <option value="{{ $key }}" @selected(old('access_type', 'all') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="cr-field">
                            <span>Số người tối đa <em>*</em></span>
                            <input type="number" name="max_participants" min="2" max="200" value="{{ old('max_participants', 50) }}">
                        </label>
                    </div>
                </section>

                <section class="op-panel">
                    <div class="op-panel-head is-static">
                        <span class="op-panel-ico"><i class="fa-regular fa-calendar"></i></span>
                        <span class="op-panel-title">Lịch học &amp; bảo mật</span>
                    </div>
                    <div class="op-panel-body cr-fields">
                        <label class="cr-field">
                            <span>Thời gian bắt đầu</span>
                            <input type="datetime-local" name="scheduled_start" value="{{ $dt('scheduled_start') }}">
                        </label>
                        <label class="cr-field">
                            <span>Thời gian kết thúc</span>
                            <input type="datetime-local" name="scheduled_end" value="{{ $dt('scheduled_end') }}">
                        </label>
                        <label class="cr-field">
                            <span>Mật khẩu phòng (tùy chọn)</span>
                            <input type="text" name="room_password" value="{{ old('room_password') }}" placeholder="Để trống nếu không cần">
                        </label>
                        <label class="cr-toggle">
                            <input type="checkbox" name="waiting_room" value="1" @checked(old('waiting_room'))>
                            <span>
                                <strong>Giáo viên duyệt trước khi vào</strong>
                                <small>Bật phòng chờ — hỗ trợ nick ngoài và học viên chờ được duyệt.</small>
                            </span>
                        </label>
                    </div>
                </section>
            </div>

            <aside class="cr-aside">
                <section class="op-panel">
                    <div class="op-panel-head is-static">
                        <span class="op-panel-ico"><i class="fa-regular fa-lightbulb"></i></span>
                        <span class="op-panel-title">Gợi ý thiết lập</span>
                    </div>
                    <div class="op-panel-body">
                        <ul class="cr-tips">
                            <li>Đặt trạng thái <strong>Đã lên lịch</strong> để học viên thấy phòng.</li>
                            <li>Giáo viên nên vào phòng trước 5 phút.</li>
                            <li>Mật khẩu phòng là tùy chọn.</li>
                        </ul>
                    </div>
                </section>
            </aside>
        </div>

        <div class="cr-savebar">
            <a class="op-btn-ghost" href="{{ route('classroom.index') }}">Hủy</a>
            <button class="op-btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Tạo phòng học</button>
        </div>
    </form>
</div>
@endsection
