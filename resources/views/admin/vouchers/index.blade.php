@extends('layouts.app', ['active' => 'vouchers'])

@section('title', 'Quản lý Voucher')

@section('subnav')
    <a href="{{ route('admin.vouchers.index') }}" class="os-tab is-on"><i class="fa-solid fa-ticket"></i> Danh sách & Tạo Voucher</a>
    <a href="{{ route('admin.vouchers.check') }}" class="os-tab"><i class="fa-solid fa-qrcode"></i> Kiểm tra & Áp dụng mã</a>
@endsection

@section('content')
<div class="op-stack space-y-6">
    <x-page-title kicker="Quản trị" icon="fa-ticket" title="Quản lý Voucher">
        <x-slot:lead>
            Tạo mã voucher ngẫu nhiên gắn với học viên, tự động xuất ảnh đè lên phôi và hỗ trợ copy ảnh để gửi qua Zalo / Facebook.
        </x-slot:lead>
        <x-slot:aside>
            <a href="{{ route('admin.vouchers.check') }}" class="op-btn is-primary flex items-center gap-2">
                <i class="fa-solid fa-magnifying-glass"></i> Đi đến trang check mã
            </a>
        </x-slot:aside>
    </x-page-title>

    <!-- Thống kê tổng quan -->
    <div class="op-stat-grid is-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <article class="op-stat bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs uppercase font-bold text-slate-400 tracking-wider">Tổng mã phát hành</p>
                <strong class="text-2xl font-black text-slate-800">{{ number_format($stats['total']) }}</strong>
            </div>
            <span class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-ticket"></i>
            </span>
        </article>

        <article class="op-stat bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs uppercase font-bold text-emerald-600 tracking-wider">Chưa sử dụng</p>
                <strong class="text-2xl font-black text-emerald-600">{{ number_format($stats['unused']) }}</strong>
            </div>
            <span class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </span>
        </article>

        <article class="op-stat bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs uppercase font-bold text-rose-500 tracking-wider">Đã sử dụng</p>
                <strong class="text-2xl font-black text-rose-600">{{ number_format($stats['used']) }}</strong>
            </div>
            <span class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-xmark"></i>
            </span>
        </article>

        <article class="op-stat bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs uppercase font-bold text-amber-500 tracking-wider">Tỷ lệ sử dụng</p>
                <strong class="text-2xl font-black text-amber-600">
                    {{ $stats['total'] > 0 ? round(($stats['used'] / $stats['total']) * 100, 1) : 0 }}%
                </strong>
            </div>
            <span class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-chart-pie"></i>
            </span>
        </article>
    </div>

    <!-- Khu vực Tạo mã & Cài đặt phôi -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Form Tạo mã Voucher mới (7 cols) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-5">
                <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Tạo mã Voucher học viên mới</h2>
                    <p class="text-xs text-slate-500">Mã ngẫu nhiên 8 ký tự (ví dụ: SN-98765) được tạo và tự động xuất thành ảnh voucher gửi học viên.</p>
                </div>
            </div>

            <form id="createVoucherForm" method="POST" action="{{ route('admin.vouchers.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                            Họ và Tên học viên <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input type="text" name="student_name" id="student_name" required maxlength="255"
                                placeholder="Ví dụ: Nguyễn Văn A"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                            Số Điện Thoại <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <input type="tel" name="phone" id="phone" required maxlength="50"
                                placeholder="Ví dụ: 0912 345 678"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                            Ngày tháng năm sinh <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-solid fa-calendar"></i>
                            </span>
                            <input type="date" name="dob" id="dob" required
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                            Ghi chú / Khóa học áp dụng (Tùy chọn)
                        </label>
                        <input type="text" name="note" id="note" maxlength="500"
                            placeholder="Ví dụ: Áp dụng khóa Tiếng Hàn sơ cấp 1, giảm 500.000đ"
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition text-sm">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <p class="text-xs text-slate-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                        Ảnh voucher tạo xong sẽ tự động hiện lên với nút <strong>Copy ảnh</strong>.
                    </p>
                    <button type="submit" id="btnSubmitCreate"
                        class="px-6 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 flex items-center gap-2 transition transform active:scale-95">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>Tạo mã & Xuất ảnh</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Quản lý Phôi Voucher (5 cols) -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg font-bold">
                            <i class="fa-solid fa-image"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Phôi ảnh Voucher</h2>
                            <p class="text-xs text-slate-500">Phôi mẫu dùng để chèn đè chữ (Mã, Tên, SĐT)</p>
                        </div>
                    </div>
                    @if ($hasCustomTemplate)
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Phôi riêng
                        </span>
                    @else
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                            Phôi chuẩn
                        </span>
                    @endif
                </div>

                <!-- Phôi Preview -->
                <div class="relative rounded-xl overflow-hidden border border-slate-200 group bg-slate-900 shadow-inner">
                    <img src="{{ $templateUrl }}?v={{ time() }}" alt="Phôi voucher" class="w-full h-44 object-cover object-center group-hover:scale-105 transition duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent flex items-end p-3">
                        <span class="text-xs text-white/90 font-medium">
                            <i class="fa-solid fa-circle-info mr-1 text-amber-400"></i> {{ $hasCustomTemplate ? 'Đang dùng phôi bạn đã tải lên' : 'Đang dùng phôi mặc định SUNNY' }}
                        </span>
                    </div>
                </div>

                <!-- Form Upload phôi mới -->
                <form method="POST" action="{{ route('admin.vouchers.template.upload') }}" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                        Tải lên phôi mới (PNG hoặc JPG, khuyến nghị 1200x600 px):
                    </label>
                    <div class="flex gap-2">
                        <input type="file" name="template_file" accept="image/png, image/jpeg" required
                            class="flex-1 text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer border border-slate-200 rounded-xl px-2 py-1">
                        <button type="submit" class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-800 text-white hover:bg-slate-900 transition flex items-center gap-1.5 whitespace-nowrap">
                            <i class="fa-solid fa-upload"></i> Cập nhật
                        </button>
                    </div>
                </form>
            </div>

            @if ($hasCustomTemplate)
                <div class="pt-3 border-t border-slate-100 mt-4 flex justify-between items-center">
                    <span class="text-xs text-slate-400">Bạn có thể quay lại phôi mẫu gốc</span>
                    <form method="POST" action="{{ route('admin.vouchers.template.revert') }}" onsubmit="return confirm('Bạn có chắc muốn dùng lại phôi mặc định?');">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                            <i class="fa-solid fa-rotate-left"></i> Khôi phục phôi mặc định
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Bảng danh sách Voucher gần đây -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-list-check"></i>
                </span>
                <div>
                    <h3 class="font-bold text-slate-800">Danh sách Voucher đã phát hành</h3>
                    <p class="text-xs text-slate-400">Hiển thị các mã vừa tạo và trạng thái sử dụng</p>
                </div>
            </div>

            <!-- Bộ lọc tìm kiếm -->
            <form method="GET" action="{{ route('admin.vouchers.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="status" class="px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="unused" @selected($filters['status'] === 'unused')>CHƯA SỬ DỤNG</option>
                    <option value="used" @selected($filters['status'] === 'used')>ĐÃ SỬ DỤNG</option>
                </select>

                <div class="flex">
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Tìm mã, Tên, SĐT..."
                        class="px-3 py-2 text-sm rounded-l-xl border border-r-0 border-slate-200 bg-slate-50/50 w-44 sm:w-60 focus:bg-white focus:outline-none">
                    <button type="submit" class="px-3.5 py-2 text-sm rounded-r-xl border border-slate-200 bg-slate-100 text-slate-600 hover:bg-slate-200">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
                @if ($filters['q'] || $filters['status'])
                    <a href="{{ route('admin.vouchers.index') }}" class="px-3 py-2 text-sm rounded-xl text-slate-500 hover:text-slate-800" title="Bỏ lọc">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Mã Voucher</th>
                        <th class="px-5 py-3.5 font-semibold">Học viên</th>
                        <th class="px-5 py-3.5 font-semibold">Số điện thoại</th>
                        <th class="px-5 py-3.5 font-semibold">Ngày sinh</th>
                        <th class="px-5 py-3.5 font-semibold">Trạng thái</th>
                        <th class="px-5 py-3.5 font-semibold">Ngày tạo / Sử dụng</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($vouchers as $v)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5 font-mono">
                                <span class="px-2.5 py-1 rounded-lg bg-orange-50 font-bold text-orange-600 border border-orange-200 text-sm inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-ticket text-xs"></i>
                                    {{ $v->code }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-800">
                                {{ $v->student_name }}
                                @if ($v->note)
                                    <p class="text-xs font-normal text-slate-400 truncate max-w-xs">{{ $v->note }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-600">
                                {{ $v->phone }}
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">
                                {{ $v->dob ? $v->dob->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($v->isUnused())
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        CHƯA SỬ DỤNG
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-rose-50 text-rose-600 border border-rose-200">
                                        <i class="fa-solid fa-lock text-[10px]"></i>
                                        ĐÃ SỬ DỤNG
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                <div><i class="fa-regular fa-clock text-slate-400 mr-1"></i> Tạo: {{ $v->created_at->format('d/m/Y H:i') }}</div>
                                @if ($v->isUsed())
                                    <div class="text-rose-600 mt-0.5"><i class="fa-solid fa-check text-slate-400 mr-1"></i> Dùng: {{ $v->used_at ? $v->used_at->format('d/m/Y H:i') : '' }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button"
                                        onclick="openVoucherModal({
                                            code: '{{ $v->code }}',
                                            name: '{{ addslashes($v->student_name) }}',
                                            phone: '{{ $v->phone }}',
                                            dob: '{{ $v->dob ? $v->dob->format('d/m/Y') : '' }}',
                                            status: '{{ $v->status }}',
                                            status_text: '{{ $v->statusText() }}',
                                            imageUrl: '{{ $v->imageUrl() }}'
                                        })"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-orange-50 text-orange-600 hover:bg-orange-100 transition inline-flex items-center gap-1"
                                        title="Xem ảnh và copy gửi học viên">
                                        <i class="fa-solid fa-image"></i> Xem / Copy ảnh
                                    </button>

                                    @if ($v->imageUrl())
                                        <a href="{{ $v->imageUrl() }}" download="Voucher_{{ $v->code }}.png"
                                            class="p-1.5 rounded-lg text-xs text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                                            title="Tải ảnh về máy">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('admin.vouchers.destroy', $v->id) }}" onsubmit="return confirm('Bạn có chắc muốn xóa voucher {{ $v->code }}?');" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg text-xs text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Xóa voucher">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-ticket-simple text-4xl mb-3 text-slate-300 block"></i>
                                Chưa có voucher nào được tạo. Hãy điền form phía trên để cấp mã đầu tiên!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($vouchers->hasPages())
            <div class="p-4 border-t border-slate-100 flex justify-end">
                {{ $vouchers->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ================= MODAL HIỂN THỊ ẢNH VOUCHER & NÚT COPY ẢNH ================= -->
<div id="voucherModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div id="voucherModalBackdrop" class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity"></div>

    <!-- Modal Box -->
    <div class="relative bg-white rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden z-10 border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-orange-50 via-white to-amber-50">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-orange-500 text-white flex items-center justify-center text-lg font-bold shadow-md shadow-orange-500/30">
                    <i class="fa-solid fa-gift"></i>
                </span>
                <div>
                    <h3 class="font-black text-slate-800 text-lg flex items-center gap-2">
                        Voucher Học Viên: <span id="modalCodeBadge" class="font-mono text-orange-600 bg-white px-2.5 py-0.5 rounded-lg border border-orange-200 text-base font-extrabold"></span>
                    </h3>
                    <p class="text-xs text-slate-500">Ảnh đã được hệ thống tự động chèn chữ đè lên phôi</p>
                </div>
            </div>
            <button type="button" onclick="closeVoucherModal()" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Body: Ảnh voucher lớn -->
        <div class="p-6 space-y-4">
            <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-950 shadow-inner group">
                <img id="modalVoucherImg" src="" alt="Voucher Image" class="w-full h-auto object-contain max-h-[380px] mx-auto block transition group-hover:scale-[1.01]">
                <div id="modalImgLoading" class="absolute inset-0 bg-slate-900/60 flex items-center justify-center text-white text-sm font-semibold hidden">
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i> Đang tải ảnh voucher...
                </div>
            </div>

            <!-- Thông tin tóm tắt học sinh -->
            <div class="grid grid-cols-3 gap-2 bg-slate-50 p-3.5 rounded-xl border border-slate-100 text-xs">
                <div>
                    <span class="text-slate-400 block font-medium">Học viên</span>
                    <strong id="modalStudentName" class="text-slate-800 font-bold text-sm truncate block"></strong>
                </div>
                <div>
                    <span class="text-slate-400 block font-medium">Số điện thoại</span>
                    <strong id="modalPhone" class="text-slate-800 font-mono font-bold text-sm block"></strong>
                </div>
                <div>
                    <span class="text-slate-400 block font-medium">Trạng thái</span>
                    <span id="modalStatusBadge" class="font-bold"></span>
                </div>
            </div>
        </div>

        <!-- Footer Actions: COPY ẢNH, TẢI VỀ, COPY MÃ -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <button type="button" onclick="copyVoucherCode()" id="btnCopyCode"
                class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-hashtag text-orange-500"></i>
                <span id="btnCopyCodeText">Copy mã</span>
            </button>

            <div class="flex items-center gap-2">
                <a id="btnDownloadVoucher" href="#" download=""
                    class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-download text-blue-500"></i>
                    <span>Tải ảnh về máy</span>
                </a>

                <!-- NÚT QUAN TRỌNG NHẤT: COPY ẢNH VÀO CLIPBOARD -->
                <button type="button" onclick="copyVoucherImageToClipboard()" id="btnCopyVoucherImg"
                    class="px-6 py-2.5 rounded-xl text-white font-black text-sm bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/30 flex items-center gap-2 transition transform active:scale-95">
                    <i class="fa-solid fa-copy text-base"></i>
                    <span id="btnCopyImgText">Copy ảnh (Paste vào Zalo / FB)</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentModalVoucher = null;

    // Toast helper
    function showAppToast(type, msg) {
        if (window.TemplateUI && typeof TemplateUI.toast === 'function') {
            TemplateUI.toast(type, msg);
        } else {
            alert(msg);
        }
    }

    // Xử lý submit form tạo voucher bằng AJAX để mượt mà hiển thị modal ảnh
    const createForm = document.getElementById('createVoucherForm');
    createForm?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitBtn = document.getElementById('btnSubmitCreate');
        const origBtnHtml = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang tạo & xuất ảnh...';

        try {
            const formData = new FormData(createForm);
            const res = await fetch(createForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json();

            if (res.ok && data.success) {
                showAppToast('success', data.message || 'Tạo mã voucher thành công!');
                createForm.reset();

                // Mở Modal hiển thị ảnh voucher với nút Copy ảnh
                openVoucherModal({
                    code: data.voucher.code,
                    name: data.voucher.student_name,
                    phone: data.voucher.phone,
                    dob: data.voucher.dob,
                    status: data.voucher.status,
                    status_text: data.voucher.status_text,
                    imageUrl: data.voucher.image_url
                });

                // Tự động tải lại trang sau khi đóng modal để cập nhật bảng, hoặc reload nhẹ
                window._shouldReloadOnClose = true;
            } else {
                const errorMsg = data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Có lỗi xảy ra khi tạo voucher');
                showAppToast('error', errorMsg);
            }
        } catch (err) {
            console.error('Error creating voucher:', err);
            // Fallback: nếu lỗi AJAX thì submit form truyền thống
            createForm.submit();
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }
    });

    // Mở modal
    function openVoucherModal(v) {
        currentModalVoucher = v;
        document.getElementById('modalCodeBadge').textContent = v.code;
        document.getElementById('modalStudentName').textContent = v.name;
        document.getElementById('modalPhone').textContent = v.phone;

        const statusBadge = document.getElementById('modalStatusBadge');
        if (v.status === 'unused') {
            statusBadge.className = 'text-emerald-600 font-extrabold';
            statusBadge.textContent = 'CHƯA SỬ DỤNG';
        } else {
            statusBadge.className = 'text-rose-600 font-extrabold';
            statusBadge.textContent = 'ĐÃ SỬ DỤNG';
        }

        const imgEl = document.getElementById('modalVoucherImg');
        const dlBtn = document.getElementById('btnDownloadVoucher');
        imgEl.src = v.imageUrl;
        dlBtn.href = v.imageUrl;
        dlBtn.download = `Voucher_${v.code}.png`;

        document.getElementById('voucherModal').classList.remove('hidden');
    }

    // Đóng modal
    function closeVoucherModal() {
        document.getElementById('voucherModal').classList.add('hidden');
        if (window._shouldReloadOnClose) {
            window.location.reload();
        }
    }

    document.getElementById('voucherModalBackdrop')?.addEventListener('click', closeVoucherModal);

    // Copy mã voucher dạng text
    function copyVoucherCode() {
        if (!currentModalVoucher) return;
        navigator.clipboard.writeText(currentModalVoucher.code).then(() => {
            const btnText = document.getElementById('btnCopyCodeText');
            const orig = btnText.textContent;
            btnText.textContent = 'Đã chép!';
            setTimeout(() => { btnText.textContent = orig; }, 2000);
            showAppToast('success', `Đã sao chép mã ${currentModalVoucher.code} vào clipboard!`);
        });
    }

    // COPY ẢNH VOUCHER VÀO CLIPBOARD (HỖ TRỢ PASTE VÀO ZALO / MESSENGER / FACEBOOK)
    async function copyVoucherImageToClipboard() {
        if (!currentModalVoucher || !currentModalVoucher.imageUrl) {
            showAppToast('error', 'Chưa tìm thấy ảnh voucher!');
            return;
        }

        const btn = document.getElementById('btnCopyVoucherImg');
        const btnText = document.getElementById('btnCopyImgText');
        const origHtml = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang sao chép ảnh...';

        try {
            // Fetch ảnh từ URL thành Blob
            const response = await fetch(currentModalVoucher.imageUrl, { cache: 'no-cache' });
            if (!response.ok) throw new Error('Không thể tải file ảnh.');
            const blob = await response.blob();

            // Đảm bảo định dạng là image/png để ClipboardItem của Chromium chấp nhận
            let pngBlob = blob;
            if (blob.type !== 'image/png') {
                pngBlob = await convertBlobToPng(blob);
            }

            // Ghi trực tiếp vào clipboard dưới dạng ảnh
            await navigator.clipboard.write([
                new ClipboardItem({
                    'image/png': pngBlob
                })
            ]);

            btn.innerHTML = '<i class="fa-solid fa-check"></i> Đã sao chép ảnh!';
            showAppToast('success', 'ĐÃ COPY ẢNH VÀO BỘ NHỚ TẠM! Bạn chỉ cần nhấn phím Ctrl + V (hoặc Dán) vào ô chat Zalo, Facebook Messenger để gửi.');

            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }, 3000);
        } catch (err) {
            console.error('Lỗi khi copy ảnh vào clipboard:', err);
            btn.disabled = false;
            btn.innerHTML = origHtml;

            // Fallback: nếu clipboard image bị chặn (ví dụ do iframe hoặc bảo mật)
            showAppToast('info', 'Trình duyệt yêu cầu cấp quyền hoặc không cho chép ảnh tự động. Bạn có thể nhấn nút "Tải ảnh về máy" hoặc click chuột phải vào ảnh chọn "Sao chép hình ảnh".');
        }
    }

    // Hàm chuyển Blob bất kỳ sang PNG blob qua canvas nếu cần
    function convertBlobToPng(blob) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth || img.width;
                canvas.height = img.naturalHeight || img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                canvas.toBlob((b) => {
                    if (b) resolve(b);
                    else reject(new Error('Canvas toBlob failed'));
                }, 'image/png');
            };
            img.onerror = reject;
            img.src = URL.createObjectURL(blob);
        });
    }

    // Nếu vừa tạo xong từ session flash truyền thống
    @if (session('new_voucher_id'))
        @php
            $newV = \App\Models\Voucher::find(session('new_voucher_id'));
        @endphp
        @if ($newV)
            document.addEventListener('DOMContentLoaded', () => {
                openVoucherModal({
                    code: '{{ $newV->code }}',
                    name: '{{ addslashes($newV->student_name) }}',
                    phone: '{{ $newV->phone }}',
                    dob: '{{ $newV->dob ? $newV->dob->format('d/m/Y') : '' }}',
                    status: '{{ $newV->status }}',
                    status_text: '{{ $newV->statusText() }}',
                    imageUrl: '{{ $newV->imageUrl() }}'
                });
            });
        @endif
    @endif
</script>
@endpush
