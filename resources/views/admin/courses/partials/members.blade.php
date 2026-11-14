<section class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-users"></i></span>
        <span class="op-panel-title">Học viên đang học</span>
        <span class="text-sm text-slate-500">{{ $activeMembers->count() }} học viên</span>
    </div>
    <div class="op-panel-body" style="padding:20px">
        <form method="post" action="{{ route('admin.courses.members.store', $course->id) }}" class="js-member-add mb-5 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
            @csrf
            <div class="relative">
                <label class="block text-sm">Thêm học viên
                    <input type="search" class="js-member-q mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" placeholder="Tìm tên, mã SN, SĐT..." autocomplete="off">
                </label>
                <input type="hidden" name="student_id" class="js-member-id" value="">
                <div class="js-member-results absolute z-20 mt-1 hidden w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"></div>
                <p class="js-member-picked mt-1 hidden text-xs text-emerald-700"></p>
            </div>
            <button type="submit" class="op-btn">Thêm vào khóa</button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Học viên</th>
                        <th class="px-4 py-3 font-medium">Mã / SĐT</th>
                        <th class="px-4 py-3 font-medium">Vào lớp</th>
                        <th class="px-4 py-3 text-right font-medium">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($activeMembers as $member)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ $member->user?->name }}</p>
                                <p class="text-xs text-slate-400">{{ $member->user?->username }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $member->user?->student_code ?: '—' }}<br><span class="text-xs text-slate-400">{{ $member->user?->phone }}</span></td>
                            <td class="px-4 py-3 text-slate-500">{{ $member->joined_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    <form method="post" action="{{ route('admin.courses.members.complete', [$course->id, $member->user_id]) }}" onsubmit="return confirm('Đánh dấu học viên đã hoàn thành khóa?');">
                                        @csrf
                                        <button class="inline-flex h-8 items-center rounded-lg border border-emerald-100 px-2.5 text-xs font-medium text-emerald-700">Hoàn thành</button>
                                    </form>
                                    @if ($transferCourses->isNotEmpty())
                                        <form method="post" action="{{ route('admin.courses.members.transfer', [$course->id, $member->user_id]) }}" class="flex items-center gap-1">
                                            @csrf
                                            <select name="target_course_id" class="h-8 rounded-lg bg-slate-100 px-2 text-xs" required>
                                                <option value="">Chuyển khóa…</option>
                                                @foreach ($transferCourses as $target)
                                                    <option value="{{ $target->id }}">{{ $target->title }}</option>
                                                @endforeach
                                            </select>
                                            <button class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2 text-xs text-slate-700">Chuyển</button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('admin.courses.members.destroy', [$course->id, $member->user_id]) }}" onsubmit="return confirm('Gỡ học viên khỏi khóa? Học viên có thể vào khóa khác.');">
                                        @csrf
                                        <button class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-100 text-red-600"><i class="fa-solid fa-user-minus"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">Chưa có học viên. Tìm và thêm học viên ở trên.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@if ($pastMembers->isNotEmpty())
    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-clock-rotate-left"></i></span>
            <span class="op-panel-title">Đã rời / hoàn thành</span>
        </div>
        <div class="op-panel-body is-flush">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Học viên</th>
                        <th class="px-4 py-3 font-medium">Trạng thái</th>
                        <th class="px-4 py-3 font-medium">Thời điểm</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($pastMembers as $member)
                        <tr>
                            <td class="px-4 py-3">{{ $member->user?->name }}</td>
                            <td class="px-4 py-3">{{ $member->statusLabel() }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ ($member->completed_at ?? $member->left_at)?->format('d/m/Y H:i') ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
