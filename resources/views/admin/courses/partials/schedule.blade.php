@php
    $room = $onlineRoom ?? null;
    $shiftSessions = $shiftSessions ?? $sessions;
    $sessionTotal = (int) ($sessionTotal ?? $sessions->count());
    $sessionChunk = (int) ($sessionChunk ?? $sessions->count());
    $sessionRowsUrl = (string) ($sessionRowsUrl ?? '');
@endphp
<section class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-clock"></i></span>
        <span class="op-panel-title">Thời gian buổi học</span>
        @if ($weekdayLabels)
            <span class="text-sm text-slate-500">Lịch tuần: {{ $weekdayLabels }}</span>
        @endif
        <span class="js-schedule-save-status text-sm font-medium text-emerald-700" hidden>Đã lưu</span>
    </div>
    <div class="op-panel-body" style="padding:20px">
        @if ($room)
            <p class="mb-4 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                Phòng online: <strong>{{ $room->title }}</strong>
                · {{ $room->statusLabel() }}
                @if ($room->room_code)
                    · mã <code>{{ $room->room_code }}</code>
                @endif
            </p>
        @endif

        @if ($sessionTotal > 0)
            <form method="post" action="{{ route('admin.courses.schedule.shift', $course->id) }}" class="mb-5 flex flex-wrap items-end gap-2 rounded-xl border border-slate-100 p-3">
                @csrf
                <label class="text-sm">Lùi lịch từ buổi
                    <select name="from_session_id" class="mt-1 block rounded-lg bg-slate-100 px-3 py-2" required>
                        @foreach ($shiftSessions as $session)
                            <option value="{{ $session->id }}">{{ $session->title }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm">Ngày mới
                    <input type="date" name="shift_date" class="mt-1 block rounded-lg bg-slate-100 px-3 py-2" required>
                </label>
                <button class="op-btn" type="submit">Lùi lịch tuần</button>
            </form>
        @endif

        <form method="post" action="{{ route('admin.courses.schedule.save', $course->id) }}">
            @csrf
            <div class="overflow-x-auto rounded-xl border border-slate-100">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-medium">Buổi</th>
                            <th class="px-3 py-3 font-medium">Ngày</th>
                            <th class="px-3 py-3 font-medium">Bắt đầu</th>
                            <th class="px-3 py-3 font-medium">Kết thúc</th>
                            <th class="px-3 py-3 font-medium">Video ghi hình</th>
                            <th class="px-3 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 js-schedule-session-body"
                        data-rows-url="{{ $sessionRowsUrl }}"
                        data-offset="{{ $sessions->count() }}"
                        data-total="{{ $sessionTotal }}"
                        data-limit="{{ $sessionChunk }}"
                    >
                        @if ($sessions->isEmpty())
                            <tr class="js-schedule-empty">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Chưa có buổi học. Gắn chương trình ở tab Thông tin.</td>
                            </tr>
                        @else
                            @include('admin.courses.partials.schedule-session-rows', [
                                'sessions' => $sessions,
                                'course' => $course,
                                'indexStart' => 0,
                            ])
                        @endif
                    </tbody>
                </table>
            </div>
            <p class="js-schedule-more-status mt-2 text-sm text-slate-500" @if ($sessions->count() >= $sessionTotal) hidden @endif>
                Đang tải thêm buổi học… <span class="js-schedule-more-count">{{ $sessions->count() }}</span>/{{ $sessionTotal }}
            </p>

            <h3 class="mb-2 mt-6 text-sm font-semibold text-slate-900">Bài kiểm tra</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-100">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-medium">Bài</th>
                            <th class="px-3 py-3 font-medium">Khung giờ làm bài</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($assessments as $index => $assessment)
                            <tr>
                                <td class="px-3 py-2 align-top">
                                    <input type="hidden" name="assessments[{{ $index }}][id]" value="{{ $assessment->id }}">
                                    <p class="font-medium">{{ $assessment->title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Học viên làm bài khi đang trong một khung giờ.</p>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="js-assessment-slots space-y-2">
                                        @foreach ($assessment->slotModels() as $slot)
                                            @include('admin.courses.partials.schedule-assessment-slot-row', [
                                                'course' => $course,
                                                'assessment' => $assessment,
                                                'slot' => $slot,
                                            ])
                                        @endforeach
                                    </div>
                                    <button type="button"
                                        class="js-assessment-slot-add mt-2 text-sm font-medium text-orange-700"
                                        data-url="{{ route('admin.courses.schedule.assessmentSlots.store', [$course->id, $assessment->id]) }}">
                                        + Thêm khung giờ
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-slate-500">Chưa có bài kiểm tra trong khóa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="submit" class="op-btn">Lưu toàn bộ lịch</button>
            </div>
        </form>
    </div>
</section>
