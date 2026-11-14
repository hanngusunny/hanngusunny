<div class="js-schedule-autosave flex flex-wrap items-end gap-2 rounded-lg bg-slate-50 p-2" data-autosave-url="{{ route('admin.courses.schedule.assessmentSlots.update', [$course->id, $assessment->id, $slot->id]) }}">
    <label class="text-xs text-slate-500">Ngày
        <input type="date" data-field="slot_date" value="{{ $slot->dateForInput() }}" class="mt-0.5 block min-w-[9.5rem] rounded-lg bg-white px-2 py-1.5">
    </label>
    <label class="text-xs text-slate-500">Bắt đầu
        <input type="time" data-field="start_time" value="{{ $slot->timeForInput($slot->start_time) }}" class="mt-0.5 block rounded-lg bg-white px-2 py-1.5">
    </label>
    <label class="text-xs text-slate-500">Kết thúc
        <input type="time" data-field="end_time" value="{{ $slot->timeForInput($slot->end_time) }}" class="mt-0.5 block rounded-lg bg-white px-2 py-1.5">
    </label>
    <button type="button"
        class="js-assessment-slot-delete mb-0.5 inline-flex h-8 items-center rounded-lg border border-slate-200 px-2 text-xs text-slate-600"
        data-url="{{ route('admin.courses.schedule.assessmentSlots.destroy', [$course->id, $assessment->id, $slot->id]) }}">
        Xóa
    </button>
</div>
