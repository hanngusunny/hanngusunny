<section class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-chart-column"></i></span>
        <span class="op-panel-title">Thống kê ôn tập sau buổi</span>
        <span class="te-side-meta">{{ (int) ($doneCount ?? 0) }} đã nộp · {{ (int) ($pendingCount ?? 0) }} chưa nộp</span>
    </div>
    <div class="op-panel-body" style="padding:16px 20px">
        @if (($sessionOptions ?? []) === [])
            <p class="text-sm text-slate-600">Khóa chưa có buổi nào gắn câu hỏi ôn tập sau buổi.</p>
        @else
            <form method="get" class="mb-4">
                <input type="hidden" name="tab" value="practice">
                <label class="text-sm">Buổi
                    <select name="buoi" class="ml-2 rounded-lg bg-slate-100 px-3 py-2" onchange="this.form.submit()">
                        @foreach ($sessionOptions as $option)
                            <option value="{{ $option['id'] }}" @selected((int) $option['id'] === (int) ($selectedSessionId ?? 0))>
                                {{ $option['title'] }} · {{ $phaseLabels[$option['phase'] ?? ''] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </form>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2">Học viên</th>
                        <th>Trạng thái</th>
                        <th>Điểm</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members ?? [] as $member)
                        @php $summary = $submissionMap[(int) $member->user_id] ?? []; @endphp
                        <tr class="border-t border-slate-100">
                            <td class="py-2">{{ $member->user?->name ?: '—' }}</td>
                            <td>{{ ! empty($summary['submitted']) ? 'Đã nộp' : 'Chưa nộp' }}</td>
                            <td>{{ ! empty($summary['submitted']) ? ((int) ($summary['score'] ?? 0)).'%' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</section>
