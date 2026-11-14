@php
    $course = $course ?? null;
    $disabled = ! empty($disabled);
    $selectedDays = collect($selectedDays ?? []);
    $startDate = old('schedule_start_date', $course?->schedule_start_date?->format('Y-m-d'));
    $startTime = old('default_start_time', $scheduleService->formatTimeForInput($course?->default_start_time));
    $endTime = old('default_end_time', $scheduleService->formatTimeForInput($course?->default_end_time));
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    <label class="block text-sm">Giáo viên đứng lớp *
        <select name="teacher_id" required class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" @disabled($disabled)>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected((int) old('teacher_id', $course?->teacher_id ?? auth()->id()) === (int) $teacher->id)>{{ $teacher->name }}</option>
            @endforeach
        </select>
        <span class="mt-1 block text-xs text-slate-500">Một giáo viên có thể phụ trách nhiều khóa cùng lúc.</span>
    </label>
    <label class="block text-sm">Chương trình học
        <select name="program_id" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" @disabled($disabled)>
            <option value="">— Không chọn —</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected((int) old('program_id', $course?->program_id ?? 0) === (int) $program->id)>{{ $program->title }}</option>
            @endforeach
        </select>
        <span class="mt-1 block text-xs text-slate-500">Gắn chương trình để sao chép buổi học, rồi đặt lịch tuần.</span>
    </label>
    <label class="block text-sm sm:col-span-2">Tên khóa học *
        <input name="title" required minlength="3" maxlength="255" value="{{ old('title', $course?->title ?? '') }}" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" @disabled($disabled)>
    </label>
    <label class="block text-sm sm:col-span-2">Mô tả
        <textarea name="description" rows="3" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" @disabled($disabled)>{{ old('description', $course?->description ?? '') }}</textarea>
    </label>
</div>

<div class="mt-6">
    <p class="text-sm font-semibold text-slate-900">Lịch học mặc định</p>
    <p class="mt-0.5 text-xs text-slate-500">Dùng để xếp ngày cho các buổi sau khi gắn chương trình. Có thể chỉnh từng buổi ở tab Thời gian học.</p>
    <div class="mt-3 grid gap-4 sm:grid-cols-3">
        <label class="block text-sm">Ngày bắt đầu
            <input type="date" name="schedule_start_date" value="{{ $startDate }}" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" @disabled($disabled)>
        </label>
        <label class="block text-sm">Giờ bắt đầu
            <input type="time" name="default_start_time" value="{{ $startTime }}" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" @disabled($disabled)>
        </label>
        <label class="block text-sm">Giờ kết thúc
            <input type="time" name="default_end_time" value="{{ $endTime }}" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" @disabled($disabled)>
        </label>
    </div>
    <div class="mt-3">
        <p class="text-sm">Ngày học trong tuần</p>
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
