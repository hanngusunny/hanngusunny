<section class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-list-check"></i></span>
        <span class="op-panel-title">Kết quả bài kiểm tra cả lớp</span>
        <span class="te-side-meta">{{ (int) ($doneCount ?? 0) }} đã nộp · {{ (int) ($pendingCount ?? 0) }} chưa nộp</span>
    </div>
    <div class="op-panel-body" style="padding:16px 20px">
        @if (($assessmentOptions ?? []) === [])
            <p class="text-sm text-slate-600">Khóa chưa có bài kiểm tra có câu hỏi.</p>
        @else
            <form method="get" class="mb-4">
                <input type="hidden" name="tab" value="assessments">
                <label class="text-sm">Bài
                    <select name="bai" class="ml-2 rounded-lg bg-slate-100 px-3 py-2" onchange="this.form.submit()">
                        @foreach ($assessmentOptions as $option)
                            <option value="{{ $option['id'] }}" @selected((int) $option['id'] === (int) ($selectedAssessmentId ?? 0))>
                                {{ $option['title'] }}
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
                        @php
                            $uid = (int) $member->user_id;
                            $summary = $submissionMap[$uid] ?? [];
                            $draft = ! empty($draftUserIds[$uid]) && empty($summary['submitted']);
                        @endphp
                        <tr class="border-t border-slate-100">
                            <td class="py-2">{{ $member->user?->name ?: '—' }}</td>
                            <td>
                                @if (! empty($summary['submitted'])) Đã nộp
                                @elseif ($draft) Đang làm
                                @else Chưa làm
                                @endif
                            </td>
                            <td>{{ ! empty($summary['submitted']) ? ((int) ($summary['score'] ?? 0)).'%' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</section>
