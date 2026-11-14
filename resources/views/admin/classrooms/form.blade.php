@extends('layouts.app', ['active' => 'classrooms'])

@php
    $isEdit = $room !== null;
    $currentStatus = old('status', $room->status ?? 'scheduled');
    $formPresets = old('presets');
    if (! is_array($formPresets)) {
        $formPresets = $presetQuestions ?? [];
    }
    $dt = static function ($value): string {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d\TH:i');
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);

        return $ts ? date('Y-m-d\TH:i', $ts) : '';
    };
@endphp

@section('title', $isEdit ? 'Sửa phòng học' : 'Thêm phòng học')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin-classrooms.css') }}">
@endpush

@section('subnav')
    <a href="{{ route('admin.classrooms.index') }}" class="os-tab">Danh sách phòng</a>
    <a href="{{ $isEdit ? route('admin.classrooms.edit', $room->id) : route('admin.classrooms.create') }}" class="os-tab is-on">{{ $isEdit ? 'Sửa phòng' : 'Thêm phòng' }}</a>
@endsection

@section('content')
<div class="op-stack cr-page">
    <x-page-title kicker="Lớp học online" icon="fa-video" :title="$isEdit ? 'Sửa phòng học' : 'Thêm phòng học mới'" lead="Thiết lập phòng video call cho học viên tham gia trực tuyến.">
        <x-slot:aside>
            <a class="op-btn-ghost" href="{{ route('admin.classrooms.index') }}"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
        </x-slot:aside>
    </x-page-title>

    @if ($errors->any())
        <div class="cr-errors">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ $isEdit ? route('admin.classrooms.update', $room->id) : route('admin.classrooms.store') }}" class="cr-form">
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
                            <input type="text" name="title" required value="{{ old('title', $room->title ?? '') }}" placeholder="VD: Lớp TOPIK I — Buổi 3">
                        </label>
                        <label class="cr-field is-full">
                            <span>Mô tả buổi học</span>
                            <textarea name="description" rows="4" placeholder="Nội dung buổi học, ghi chú cho học viên...">{{ old('description', $room->description ?? '') }}</textarea>
                        </label>
                        <label class="cr-field">
                            <span>Giáo viên chủ trì <em>*</em></span>
                            <select name="host_id" required>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((int) old('host_id', $room->host_id ?? auth()->id()) === (int) $teacher->id)>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="cr-field">
                            <span>Trạng thái <em>*</em></span>
                            <select name="status" required>
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </section>

                <section class="op-panel">
                    <div class="op-panel-head is-static">
                        <span class="op-panel-ico"><i class="fa-regular fa-calendar"></i></span>
                        <span class="op-panel-title">Lịch học</span>
                    </div>
                    <div class="op-panel-body cr-fields">
                        <label class="cr-field">
                            <span>Thời gian bắt đầu</span>
                            <input type="datetime-local" name="scheduled_start" value="{{ old('scheduled_start', $dt($room->scheduled_start ?? '')) }}">
                        </label>
                        <label class="cr-field">
                            <span>Thời gian kết thúc</span>
                            <input type="datetime-local" name="scheduled_end" value="{{ old('scheduled_end', $dt($room->scheduled_end ?? '')) }}">
                        </label>
                    </div>
                </section>

                <section class="op-panel">
                    <div class="op-panel-head is-static">
                        <span class="op-panel-ico"><i class="fa-solid fa-shield-halved"></i></span>
                        <span class="op-panel-title">Quyền truy cập &amp; bảo mật</span>
                    </div>
                    <div class="op-panel-body cr-fields">
                        <label class="cr-field">
                            <span>Quyền truy cập <em>*</em></span>
                            <select name="access_type" required>
                                @foreach ($accessTypes as $key => $label)
                                    <option value="{{ $key }}" @selected(old('access_type', $room->access_type ?? 'all') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small>Chỉ học viên đã đăng nhập mới vào được phòng.</small>
                        </label>
                        <label class="cr-field">
                            <span>Số người tối đa <em>*</em></span>
                            <input type="number" name="max_participants" min="2" max="200" value="{{ old('max_participants', $room->max_participants ?? 50) }}">
                        </label>
                        <label class="cr-field">
                            <span>Mật khẩu phòng</span>
                            <input type="text" name="room_password" value="{{ old('room_password', '') }}" placeholder="{{ $isEdit ? 'Để trống nếu không đổi' : 'Tùy chọn' }}">
                        </label>
                        <label class="cr-toggle">
                            <input type="checkbox" name="waiting_room" value="1" @checked(old('waiting_room', $room->waiting_room ?? false))>
                            <span>
                                <strong>Giáo viên duyệt trước khi vào</strong>
                                <small>Bật phòng chờ — học viên chờ được duyệt trước khi vào lớp.</small>
                            </span>
                        </label>
                    </div>
                </section>

                <section class="op-panel">
                    <div class="op-panel-head is-static">
                        <span class="op-panel-ico"><i class="fa-solid fa-comments"></i></span>
                        <span class="op-panel-title">Câu hỏi soạn sẵn</span>
                    </div>
                    <div class="op-panel-body">
                        <p class="cr-hint">Admin soạn trước — giáo viên có thể phát trong buổi học.</p>
                        <div id="presetQuestionsContainer">
                            @foreach ($formPresets as $index => $preset)
                                @include('admin.classrooms._preset', [
                                    'index' => $index,
                                    'preset' => is_array($preset) ? $preset : ($preset?->toArray() ?? []),
                                ])
                            @endforeach
                        </div>
                        <button class="op-btn-ghost" type="button" id="addPresetQuestionBtn">
                            <i class="fa-solid fa-plus"></i> Thêm câu hỏi
                        </button>
                        <template id="presetQuestionTemplate">
                            @include('admin.classrooms._preset', ['index' => '__INDEX__', 'preset' => []])
                        </template>
                    </div>
                </section>
            </div>

            <aside class="cr-aside">
                @if ($isEdit)
                    <section class="op-panel">
                        <div class="op-panel-head is-static">
                            <span class="op-panel-ico"><i class="fa-solid fa-circle-info"></i></span>
                            <span class="op-panel-title">Thông tin phòng</span>
                        </div>
                        <div class="op-panel-body">
                            <p class="cr-meta"><span>Trạng thái</span><strong>{{ $statuses[$currentStatus] ?? $currentStatus }}</strong></p>
                            <p class="cr-meta"><span>Mã phòng</span><code>{{ $room->room_code }}</code></p>
                            @if ($room->updated_at)
                                <p class="cr-meta"><span>Cập nhật</span><strong>{{ $room->updated_at->format('d/m/Y H:i') }}</strong></p>
                            @endif
                            <a class="op-btn w-full mt-3" href="{{ route('classroom.lobby', $room->id) }}"><i class="fa-solid fa-right-to-bracket"></i> Vào phòng học</a>
                        </div>
                    </section>
                @else
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
                @endif

                <section class="op-panel">
                    <div class="op-panel-head is-static">
                        <span class="op-panel-ico"><i class="fa-solid fa-list"></i></span>
                        <span class="op-panel-title">Trạng thái phòng</span>
                    </div>
                    <div class="op-panel-body cr-legend">
                        @foreach ($statuses as $key => $label)
                            <span class="cr-pill is-{{ $key }}">{{ $label }}</span>
                        @endforeach
                    </div>
                </section>
            </aside>
        </div>

        <div class="cr-savebar">
            <a class="op-btn-ghost" href="{{ route('admin.classrooms.index') }}">Hủy</a>
            <button class="op-btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> {{ $isEdit ? 'Cập nhật phòng' : 'Tạo phòng học' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/classroom-preset-form.js') }}"></script>
@endpush
