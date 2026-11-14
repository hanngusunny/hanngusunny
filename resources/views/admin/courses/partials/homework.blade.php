<section class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-file-lines"></i></span>
        <span class="op-panel-title">Bài tập gắn khóa</span>
    </div>
    <div class="op-panel-body" style="padding:16px 20px">
        @if (empty($ready))
            <p class="text-sm text-slate-600">Chưa có dữ liệu bài tập trên khóa này.</p>
        @elseif (($rows ?? collect())->isEmpty())
            <p class="text-sm text-slate-600">Chưa có bài tập gắn khóa này.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2">Tiêu đề</th>
                        <th>Hạn nộp</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-t border-slate-100">
                            <td class="py-2">{{ $row->title }}</td>
                            <td>{{ ! empty($row->due_at) ? date('d/m/Y H:i', strtotime((string) $row->due_at)) : '—' }}</td>
                            <td>{{ $statusLabels[$row->status] ?? $row->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</section>
