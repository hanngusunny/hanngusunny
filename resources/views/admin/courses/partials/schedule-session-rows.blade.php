@php
    $sessions = $sessions ?? collect();
    $course = $course ?? null;
    $indexStart = (int) ($indexStart ?? 0);
@endphp
@foreach ($sessions as $offset => $session)
    @php $index = $indexStart + (int) $offset; @endphp
    <tr class="js-schedule-autosave" data-autosave-type="session" data-autosave-url="{{ route('admin.courses.schedule.session.update', [$course->id, $session->id]) }}">
        <td class="px-3 py-2">
            <input type="hidden" name="sessions[{{ $index }}][id]" value="{{ $session->id }}">
            <p class="font-medium text-slate-900">{{ $session->title }}</p>
        </td>
        <td class="px-3 py-2"><input type="date" name="sessions[{{ $index }}][session_date]" data-field="session_date" value="{{ $session->dateForInput() }}" class="w-full min-w-[9.5rem] rounded-lg bg-slate-100 px-2 py-1.5"></td>
        <td class="px-3 py-2"><input type="time" name="sessions[{{ $index }}][start_time]" data-field="start_time" value="{{ $session->timeForInput($session->start_time) }}" class="w-full rounded-lg bg-slate-100 px-2 py-1.5"></td>
        <td class="px-3 py-2"><input type="time" name="sessions[{{ $index }}][end_time]" data-field="end_time" value="{{ $session->timeForInput($session->end_time) }}" class="w-full rounded-lg bg-slate-100 px-2 py-1.5"></td>
        <td class="px-3 py-2"><input type="url" name="sessions[{{ $index }}][record_video_url]" data-field="record_video_url" value="{{ $session->record_video_url }}" placeholder="https://…" class="w-full min-w-[12rem] rounded-lg bg-slate-100 px-2 py-1.5"></td>
        <td class="px-3 py-2">
            <button type="button" class="js-session-edit inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600"
                data-id="{{ $session->id }}"
                data-title="{{ $session->title }}"
                data-date="{{ $session->dateForInput() }}"
                data-start="{{ $session->timeForInput($session->start_time) }}"
                data-end="{{ $session->timeForInput($session->end_time) }}"
                data-record="{{ $session->record_video_url }}"
                data-action="{{ route('admin.courses.schedule.session.update', [$course->id, $session->id]) }}">
                <i class="fa-solid fa-pen"></i>
            </button>
        </td>
    </tr>
@endforeach
