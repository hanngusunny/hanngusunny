@extends('layouts.app', ['active' => 'database'])

@section('title', 'Cập nhật CSDL')

@section('subnav')
    <a href="{{ route('admin.database.index') }}" class="os-tab is-on">Cập nhật CSDL</a>
@endsection

@section('content')
<div class="op-stack">
    <x-page-title kicker="Quản trị" icon="fa-database" title="Cập nhật CSDL" />

    <div class="op-stat-grid is-3">
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-blue"><i class="fa-solid fa-table"></i></span><p>Cột theo dõi<small>Câu hỏi / đáp án</small></p></span><strong>{{ count($columns) }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-amber"><i class="fa-solid fa-triangle-exclamation"></i></span><p>Cần cập nhật<small>Chưa phải LONGTEXT</small></p></span><strong>{{ $pending }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-emerald"><i class="fa-solid fa-circle-check"></i></span><p>Đã sẵn sàng<small>Đã LONGTEXT</small></p></span><strong>{{ count($columns) - $pending }}</strong></article>
    </div>

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-database"></i></span>
            <span class="op-panel-title">Nâng cấp cột chứa ảnh base64</span>
        </div>
        <div class="op-panel-body" style="padding:20px">
            <p class="text-sm text-slate-600">MySQL kiểu <code>TEXT</code> chỉ chứa khoảng 64KB. Câu hỏi dán ảnh base64 sẽ lỗi <strong>1406 Data too long for column 'question_text'</strong>. Nút bên dưới chạy đúng bộ <code>ALTER TABLE ... MODIFY ... LONGTEXT</code> trên database hiện tại.</p>

            <form class="mt-4" method="post" action="{{ route('admin.database.expand-question-text') }}" onsubmit="return confirm('Chạy SQL nâng cấp cột sang LONGTEXT trên database này?');">
                @csrf
                <button type="submit" class="op-btn" @disabled($pending === 0)>
                    <i class="fa-solid fa-play"></i>
                    {{ $pending > 0 ? 'Chạy cập nhật LONGTEXT' : 'Đã cập nhật xong' }}
                </button>
            </form>
        </div>
    </section>

    @if (! empty($results))
        <section class="op-panel">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-list-check"></i></span>
                <span class="op-panel-title">Kết quả lần chạy vừa rồi</span>
            </div>
            <div class="op-panel-body" style="padding:0; overflow:auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-4 py-2 font-medium">Bảng</th>
                            <th class="px-4 py-2 font-medium">Cột</th>
                            <th class="px-4 py-2 font-medium">Kết quả</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $row)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-2 font-mono text-xs">{{ $row['table'] }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $row['column'] }}</td>
                                <td class="px-4 py-2">
                                    @if ($row['action'] === 'updated')
                                        <span class="text-emerald-700">{{ $row['message'] }}</span>
                                    @elseif ($row['action'] === 'error')
                                        <span class="text-rose-700">{{ $row['message'] }}</span>
                                    @else
                                        <span class="text-slate-500">{{ $row['message'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-table-list"></i></span>
            <span class="op-panel-title">Trạng thái cột hiện tại</span>
        </div>
        <div class="op-panel-body" style="padding:0; overflow:auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-2 font-medium">Bảng</th>
                        <th class="px-4 py-2 font-medium">Cột</th>
                        <th class="px-4 py-2 font-medium">Kiểu hiện tại</th>
                        <th class="px-4 py-2 font-medium">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($columns as $row)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-2 font-mono text-xs">{{ $row['table'] }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $row['column'] }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $row['data_type'] ?: '—' }}</td>
                            <td class="px-4 py-2">
                                @if ($row['status'] === 'ok')
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $row['label'] }}</span>
                                @elseif ($row['status'] === 'needs_update')
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">{{ $row['label'] }}</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $row['label'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
