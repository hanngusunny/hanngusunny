@php
    $course = $course ?? null;
    $disabled = ! empty($disabled);
    $selectedDays = collect($selectedDays ?? []);
    $startDate = old('schedule_start_date', $course?->schedule_start_date?->format('Y-m-d'));
    $startTime = old('default_start_time', $scheduleService->formatTimeForInput($course?->default_start_time));
    $endTime = old('default_end_time', $scheduleService->formatTimeForInput($course?->default_end_time));
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    <div class="sn-form-group">
        <label class="sn-label">Giáo viên đứng lớp <span class="required">*</span></label>
        <select name="teacher_id" required class="sn-select" @disabled($disabled)>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected((int) old('teacher_id', $course?->teacher_id ?? auth()->id()) === (int) $teacher->id)>{{ $teacher->name }}</option>
            @endforeach
        </select>
        <span class="sn-hint">Một giáo viên có thể phụ trách nhiều khóa cùng lúc.</span>
    </div>
    <div class="sn-form-group">
        <label class="sn-label">Chương trình học</label>
        <select name="program_id" class="sn-select" @disabled($disabled)>
            <option value="">— Không chọn —</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected((int) old('program_id', $course?->program_id ?? 0) === (int) $program->id)>{{ $program->title }}</option>
            @endforeach
        </select>
        <span class="sn-hint">Gắn chương trình để sao chép buổi học, rồi đặt lịch tuần.</span>
    </div>
    <div class="sn-form-group sm:col-span-2">
        <x-sunny.input name="title" label="Tên khóa học" :required="true" minlength="3" maxlength="255" :value="$course?->title ?? ''" :disabled="$disabled" />
    </div>
    <div class="sn-form-group sm:col-span-2">
        <x-sunny.textarea name="description" label="Mô tả" :rows="3" :value="$course?->description ?? ''" :disabled="$disabled" />
    </div>
</div>

<div class="mt-6">
    <p class="text-sm font-semibold text-slate-900">Lịch học mặc định</p>
    <p class="mt-0.5 text-xs text-slate-500">Dùng để xếp ngày cho các buổi sau khi gắn chương trình. Có thể chỉnh từng buổi ở tab Thời gian học.</p>
    <div class="mt-3 grid gap-4 sm:grid-cols-3">
        <x-sunny.input type="date" name="schedule_start_date" label="Ngày bắt đầu" :value="$startDate" :disabled="$disabled" />
        <x-sunny.input type="time" name="default_start_time" label="Giờ bắt đầu" :value="$startTime" :disabled="$disabled" />
        <x-sunny.input type="time" name="default_end_time" label="Giờ kết thúc" :value="$endTime" :disabled="$disabled" />
    </div>
    <div class="mt-3">
        <label class="sn-label">Ngày học trong tuần</label>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($weekdayOptions as $key => $label)
                <label class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm {{ $disabled ? 'opacity-60' : 'cursor-pointer hover:border-orange-200' }}">
                    <input type="checkbox" name="weekly_days[]" value="{{ $key }}" @checked($selectedDays->contains((string) $key) || $selectedDays->contains((int) $key)) @disabled($disabled)>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
</div>
