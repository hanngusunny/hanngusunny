@php
    use App\Models\CourseMonthlyEvaluation;
    $members = $members ?? collect();
    $evalMap = $evalMap ?? [];
    $attendanceMap = $attendanceMap ?? [];
    $scoreLabels = $scoreLabels ?? [];
@endphp

<section class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-star-half-stroke"></i></span>
        <span class="op-panel-title">Đánh giá theo khóa</span>
        <span class="te-side-meta">{{ (int) ($evaluatedCount ?? 0) }}/{{ $members->count() }} đã đánh giá</span>
    </div>
    <div class="op-panel-body" style="padding:16px 20px">
        <form method="get" action="{{ route('admin.courses.show', $course->id) }}" class="mb-4 flex flex-wrap gap-2 items-center">
            <input type="hidden" name="tab" value="evaluations">
            <label class="text-sm">Tuần
                <select name="tuan" class="ml-2 rounded-lg bg-slate-100 px-3 py-2" onchange="this.form.submit()">
                    @foreach ($weekOptions ?? [] as $option)
                        <option value="{{ $option }}" @selected($option === ($evalWeek ?? ''))>{{ CourseMonthlyEvaluation::formatWeekLabel($option) }}</option>
                    @endforeach
                </select>
            </label>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2">Học viên</th>
                        <th>Điểm danh</th>
                        <th>Thái độ</th>
                        <th>Tiến độ</th>
                        <th>Tham gia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        @php
                            $uid = (int) $member->user_id;
                            $eval = $evalMap[$uid] ?? null;
                            $att = $attendanceMap[$uid] ?? ['attended' => 0, 'total' => (int) ($weekSessionCount ?? 0)];
                        @endphp
                        <tr class="border-t border-slate-100">
                            <td class="py-2">{{ $member->user?->name ?: '—' }}</td>
                            <td>{{ (int) $att['attended'] }}/{{ (int) $att['total'] }}</td>
                            <td>{{ $eval?->score_attitude ? ($scoreLabels[$eval->score_attitude] ?? $eval->score_attitude) : '—' }}</td>
                            <td>{{ $eval?->score_progress ? ($scoreLabels[$eval->score_progress] ?? $eval->score_progress) : '—' }}</td>
                            <td>{{ $eval?->score_participation ? ($scoreLabels[$eval->score_participation] ?? $eval->score_participation) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
