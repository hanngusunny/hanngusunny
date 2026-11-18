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

@section('title', $isEdit ? 'Chỉnh sửa phòng học' : 'Tạo phòng học mới')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin-classrooms.css') }}">
    <style>
        .cr-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: #9a4700;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 14px;
            border-radius: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(154, 71, 0, 0.25);
        }
        .cr-btn-primary:hover {
            background: #7c3800;
            box-shadow: 0 4px 14px rgba(154, 71, 0, 0.35);
        }
        .cr-field-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14.5px;
            font-family: inherit;
            background: #ffffff;
            transition: border-color 0.2s;
        }
        .cr-field-input:focus {
            border-color: #9a4700;
            outline: none;
        }
    </style>
@endpush

@section('subnav')
    <a href="{{ route('admin.classrooms.index') }}" class="os-tab">Danh sách phòng</a>
    <a href="{{ $isEdit ? route('admin.classrooms.edit', $room->id) : route('admin.classrooms.create') }}" class="os-tab is-on">{{ $isEdit ? 'Sửa phòng' : 'Thêm phòng mới' }}</a>
@endsection

@section('content')
<div class="op-stack cr-page" style="font-family: 'Nunito', sans-serif; max-width: 1100px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
        <x-page-title kicker="Cấu hình phòng học" icon="fa-video" :title="$isEdit ? 'Chỉnh sửa phòng học' : 'Tạo phòng học mới'" lead="Thiết lập phòng học trực tuyến tương tác cao cho giáo viên và học viên." />
        <a class="op-btn-ghost" href="{{ route('admin.classrooms.index') }}" style="padding: 9px 16px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 13.5px; text-decoration: none; color: #475569; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Quay lại danh sách</span>
        </a>
    </div>

    @if ($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
            <strong style="display: block; margin-bottom: 6px;"><i class="fa-solid fa-circle-exclamation"></i> Vui lòng kiểm tra lại thông tin:</strong>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ $isEdit ? route('admin.classrooms.update', $room->id) : route('admin.classrooms.store') }}" class="cr-form">
        @csrf
        <div class="cr-form-grid">
            <div class="cr-form-main">
                <!-- Section 1: Basic Info -->
                <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
                    <div class="op-panel-head is-static" style="padding: 16px 20px; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                        <span class="op-panel-ico"><i class="fa-solid fa-chalkboard-user" style="color: #9a4700;"></i></span>
                        <span class="op-panel-title" style="font-weight: 800; font-size: 15.5px; color: #0f172a;">Thông tin phòng học</span>
                    </div>
                    <div class="op-panel-body cr-fields" style="padding: 20px;">
                        <label class="cr-field is-full">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13.5px;">Tên phòng học <em style="color: #e11d48;">*</em></span>
                            <input class="cr-field-input" type="text" name="title" required value="{{ old('title', $room->title ?? '') }}" placeholder="Ví dụ: Tiếng Trung Giao Tiếp HSK 3 — Buổi 05">
                        </label>
                        <label class="cr-field is-full">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13.5px;">Mô tả &amp; Ghi chú buổi học</span>
                            <textarea class="cr-field-input" name="description" rows="3" placeholder="Nội dung chính của buổi học, dặn dò học viên trước khi vào lớp...">{{ old('description', $room->description ?? '') }}</textarea>
                        </label>
                        <label class="cr-field">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13.5px;">Giáo viên chủ trì <em style="color: #e11d48;">*</em></span>
                            <select class="cr-field-input" name="host_id" required>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((int) old('host_id', $room->host_id ?? auth()->id()) === (int) $teacher->id)>
                                        {{ $teacher->name }} ({{ $teacher->role_label }})
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="cr-field">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13.5px;">Trạng thái phòng <em style="color: #e11d48;">*</em></span>
                            <select class="cr-field-input" name="status" required>
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </section>

                <!-- Section 2: Schedule -->
                <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
                    <div class="op-panel-head is-static" style="padding: 16px 20px; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                        <span class="op-panel-ico"><i class="fa-regular fa-calendar-check" style="color: #9a4700;"></i></span>
                        <span class="op-panel-title" style="font-weight: 800; font-size: 15.5px; color: #0f172a;">Lịch trình buổi học</span>
                    </div>
                    <div class="op-panel-body cr-fields" style="padding: 20px;">
                        <label class="cr-field">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13.5px;">Thời gian bắt đầu</span>
                            <input class="cr-field-input" type="datetime-local" name="scheduled_start" value="{{ old('scheduled_start', $dt($room->scheduled_start ?? '')) }}">
                        </label>
                        <label class="cr-field">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13.5px;">Thời gian kết thúc (dự kiến)</span>
                            <input class="cr-field-input" type="datetime-local" name="scheduled_end" value="{{ old('scheduled_end', $dt($room->scheduled_end ?? '')) }}">
                        </label>
                    </div>
                </section>

                <!-- Section 3: Access & Security -->
                <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
                    <div class="op-panel-head is-static" style="padding: 16px 20px; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                        <span class="op-panel-ico"><i class="fa-solid fa-shield-halved" style="color: #9a4700;"></i></span>
                        <span class="op-panel-title" style="font-weight: 800; font-size: 15.5px; color: #0f172a;">Quyền truy cập &amp; Bảo mật</span>
                    </div>
                    <div class="op-panel-body cr-fields" style="padding: 20px;">
                        <label class="cr-field">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13.5px;">Đối tượng được vào <em style="color: #e11d48;">*</em></span>
                            <select class="cr-field-input" name="access_type" required>
                                @foreach ($accessTypes as $key => $label)
                                    <option value="{{ $key }}" @selected(old('access_type', $room->access_type ?? 'all') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="cr-field">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13.5px;">Số lượng tham gia tối đa <em style="color: #e11d48;">*</em></span>
                            <input class="cr-field-input" type="number" name="max_participants" min="2" max="200" value="{{ old('max_participants', $room->max_participants ?? 50) }}">
                        </label>
                        <label class="cr-field">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13.5px;">Mật khẩu phòng (nếu cần)</span>
                            <input class="cr-field-input" type="text" name="room_password" value="{{ old('room_password', '') }}" placeholder="{{ $isEdit ? 'Để trống nếu không đổi mật khẩu' : 'Để trống nếu không dùng' }}">
                        </label>
                        <label class="cr-toggle" style="margin-top: 10px;">
                            <input type="checkbox" name="waiting_room" value="1" @checked(old('waiting_room', $room->waiting_room ?? false))>
                            <span>
                                <strong style="color: #1e293b; font-weight: 700;">Bật phòng chờ (Waiting Room)</strong>
                                <small style="color: #64748b;">Học viên chờ duyệt trong danh sách trước khi được giáo viên cho vào lớp.</small>
                            </span>
                        </label>
                    </div>
                </section>

                <!-- Section 4: Preset Questions (Figma Frame 05) -->
                <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
                    <div class="op-panel-head is-static" style="padding: 16px 20px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="op-panel-ico"><i class="fa-solid fa-clipboard-question" style="color: #9a4700;"></i></span>
                            <span class="op-panel-title" style="font-weight: 800; font-size: 15.5px; color: #0f172a;">Bộ câu hỏi chuẩn bị trước</span>
                        </div>
                        <button class="op-btn-ghost" type="button" id="addPresetQuestionBtn" style="padding: 6px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 13px; color: #9a4700;">
                            <i class="fa-solid fa-plus"></i> Thêm câu hỏi
                        </button>
                    </div>
                    <div class="op-panel-body" style="padding: 20px;">
                        <p style="font-size: 13px; color: #64748b; margin-top: 0; margin-bottom: 16px;">
                            Soạn trước các câu hỏi kiểm tra nhanh hoặc trắc nghiệm để giáo viên có thể phát ngay cho học viên trong lúc livestream.
                        </p>
                        <div id="presetQuestionsContainer">
                            @foreach ($formPresets as $index => $preset)
                                @include('admin.classrooms._preset', [
                                    'index' => $index,
                                    'preset' => is_array($preset) ? $preset : ($preset?->toArray() ?? []),
                                ])
                            @endforeach
                        </div>
                        <template id="presetQuestionTemplate">
                            @include('admin.classrooms._preset', ['index' => '__INDEX__', 'preset' => []])
                        </template>
                    </div>
                </section>
            </div>

            <!-- Sidebar Info -->
            <aside class="cr-aside">
                @if ($isEdit)
                    <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 20px; background: #ffffff;">
                        <div style="font-weight: 800; font-size: 15px; color: #0f172a; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-info" style="color: #9a4700;"></i>
                            <span>Thông tin hiện tại</span>
                        </div>
                        <p class="cr-meta"><span>Trạng thái:</span><strong>{{ $statuses[$currentStatus] ?? $currentStatus }}</strong></p>
                        <p class="cr-meta"><span>Mã phòng:</span><code>{{ $room->room_code }}</code></p>
                        @if ($room->updated_at)
                            <p class="cr-meta"><span>Cập nhật:</span><strong>{{ $room->updated_at->format('d/m/Y H:i') }}</strong></p>
                        @endif
                        <a class="cr-btn-primary" style="width: 100%; justify-content: center; margin-top: 14px;" href="{{ route('classroom.lobby', $room->id) }}">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Vào phòng học ngay</span>
                        </a>
                    </section>
                @endif

                <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); background: #ffffff;">
                    <div style="font-weight: 800; font-size: 15px; color: #0f172a; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-regular fa-lightbulb" style="color: #9a4700;"></i>
                        <span>Lưu ý thiết lập</span>
                    </div>
                    <ul style="padding-left: 20px; margin: 0; font-size: 13px; color: #475569; line-height: 1.6;">
                        <li style="margin-bottom: 8px;">Chuyển trạng thái sang <strong>Đang Live</strong> khi bắt đầu phát trực tiếp.</li>
                        <li style="margin-bottom: 8px;">Giáo viên có thể chia sẻ màn hình, bật camera và phát câu hỏi cho học viên.</li>
                        <li>Kết thúc buổi học nên chuyển trạng thái sang <strong>Đã kết thúc</strong> để lưu lịch sử.</li>
                    </ul>
                </section>
            </aside>
        </div>

        <!-- Sticky Save Bar -->
        <div class="cr-savebar" style="margin-top: 24px; display: flex; align-items: center; justify-content: flex-end; gap: 12px; background: #ffffff; padding: 18px 24px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
            <a class="op-btn-ghost" href="{{ route('admin.classrooms.index') }}" style="padding: 10px 20px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 14px; text-decoration: none; color: #475569;">Hủy bỏ</a>
            <button class="cr-btn-primary" type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>{{ $isEdit ? 'Lưu thay đổi' : 'Tạo phòng học' }}</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/classroom-preset-form.js') }}"></script>
@endpush
