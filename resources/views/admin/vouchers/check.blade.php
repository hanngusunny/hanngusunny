@extends('layouts.app', ['active' => 'vouchers'])

@section('title', 'Kiểm tra & Áp dụng Voucher')

@section('subnav')
    @if (auth()->user()?->isAdmin())
        <a href="{{ route('admin.vouchers.index') }}" class="os-tab"><i class="fa-solid fa-ticket"></i> Danh sách & Tạo Voucher</a>
    @endif
    <a href="{{ route('admin.vouchers.check') }}" class="os-tab is-on"><i class="fa-solid fa-qrcode"></i> Kiểm tra & Áp dụng mã</a>
@endsection

@section('content')
<div class="op-stack space-y-6 max-w-4xl mx-auto">
    <x-page-title kicker="Quản lý" icon="fa-qrcode" title="Kiểm tra & Áp dụng Voucher">
        <x-slot:lead>
            Nhập mã voucher học viên để xác thực thông tin (Họ tên, SĐT, Ngày sinh) và khóa mã khi áp dụng.
        </x-slot:lead>
        <x-slot:aside>
            @if (auth()->user()?->isAdmin())
                <a href="{{ route('admin.vouchers.index') }}" class="op-btn flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Về danh sách voucher
                </a>
            @endif
        </x-slot:aside>
    </x-page-title>

    <!-- Hộp tìm kiếm / Nhập mã voucher -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-8">
        <label class="block text-sm font-bold text-slate-700 uppercase tracking-wider mb-2">
            Nhập mã Voucher cần kiểm tra:
        </label>
        <form id="checkVoucherForm" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-orange-500 text-xl font-bold">
                    <i class="fa-solid fa-ticket"></i>
                </span>
                <input type="text" id="voucherCodeInput" name="code" autofocus autocomplete="off"
                    placeholder="Ví dụ: SN-98765"
                    class="w-full pl-12 pr-4 py-4 rounded-2xl border-2 border-slate-200 bg-slate-50/50 text-xl font-mono font-black text-slate-800 placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-100 transition uppercase">
            </div>
            <button type="submit" id="btnCheckSubmit"
                class="px-8 py-4 rounded-2xl font-black text-white text-base bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-lg shadow-orange-500/20 flex items-center justify-center gap-2 transition transform active:scale-95 whitespace-nowrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Kiểm tra mã</span>
            </button>
        </form>
        <p class="text-xs text-slate-400 mt-2.5 flex items-center gap-1.5">
            <i class="fa-solid fa-circle-info text-blue-500"></i>
            Bạn có thể gõ mã rồi nhấn phím <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border text-slate-600 font-mono text-[11px]">Enter</kbd> để kiểm tra nhanh.
        </p>
    </div>

    <!-- KHU VỰC HIỂN THỊ KẾT QUẢ CHECK MÃ -->
    <div id="checkResultContainer">
        <!-- Trạng thái ban đầu: Hướng dẫn -->
        <div id="stateInitial" class="bg-white rounded-3xl border border-dashed border-slate-200 p-12 text-center text-slate-400">
            <div class="w-16 h-16 rounded-3xl bg-slate-50 text-slate-300 flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fa-solid fa-barcode"></i>
            </div>
            <h4 class="font-bold text-slate-600 text-base mb-1">Chưa có mã nào được kiểm tra</h4>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                Hãy nhập mã voucher học viên cung cấp vào ô phía trên để kiểm tra tính hợp lệ và xem thông tin xác thực.
            </p>
        </div>

        <!-- Trạng thái Loading -->
        <div id="stateLoading" class="hidden bg-white rounded-3xl border border-slate-100 p-12 text-center text-slate-500">
            <div class="w-12 h-12 rounded-full border-4 border-orange-200 border-t-orange-500 animate-spin mx-auto mb-4"></div>
            <p class="font-bold text-slate-700">Đang tra cứu hệ thống voucher...</p>
        </div>

        <!-- Trạng thái 1: CHƯA SỬ DỤNG (Hợp lệ) -->
        <div id="stateUnused" class="hidden bg-white rounded-3xl border-2 border-emerald-500 shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Header Báo chữ xanh CHƯA SỬ DỤNG -->
            <div class="bg-emerald-50 border-b border-emerald-100 px-6 sm:px-8 py-5 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-2xl shadow-md shadow-emerald-500/30">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 block">Trạng thái voucher:</span>
                        <h2 class="text-2xl sm:text-3xl font-black text-emerald-600 tracking-wide flex items-center gap-2">
                            CHƯA SỬ DỤNG
                        </h2>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block font-medium">Mã voucher</span>
                    <span id="unusedCodeDisplay" class="font-mono text-xl sm:text-2xl font-black text-slate-800 bg-white px-3 py-1 rounded-xl border border-emerald-200 inline-block"></span>
                </div>
            </div>

            <!-- Body: Hiển thị Tên, SĐT, Ngày sinh để Quản lý check xem đúng người không -->
            <div class="p-6 sm:p-8 space-y-6">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-user-check text-emerald-500"></i>
                        Thông tin học viên được cấp voucher (Quản lý đối chiếu thông tin)
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium mb-1">Họ và Tên</span>
                            <strong id="unusedStudentName" class="text-slate-900 text-lg sm:text-xl font-bold block truncate"></strong>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium mb-1">Số điện thoại</span>
                            <strong id="unusedPhone" class="text-slate-900 text-lg sm:text-xl font-mono font-bold block"></strong>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium mb-1">Ngày tháng năm sinh</span>
                            <strong id="unusedDob" class="text-slate-900 text-lg sm:text-xl font-bold block text-emerald-700"></strong>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 text-xs text-slate-500 bg-slate-50/70 p-4 rounded-2xl">
                    <div>
                        <span class="font-semibold text-slate-700">Ngày phát hành:</span>
                        <span id="unusedCreatedAt"></span>
                    </div>
                    <div id="unusedNoteWrapper" class="hidden">
                        <span class="font-semibold text-slate-700">Ghi chú:</span>
                        <span id="unusedNote" class="italic text-slate-600"></span>
                    </div>
                    <div id="unusedImageThumbWrapper" class="hidden">
                        <button type="button" id="btnPreviewVoucherImg" class="text-orange-600 hover:text-orange-700 font-semibold underline flex items-center gap-1">
                            <i class="fa-solid fa-image"></i> Xem phôi ảnh đã xuất
                        </button>
                    </div>
                </div>

                <!-- NÚT ÁP DỤNG VOUCHER -->
                <div class="pt-2">
                    <button type="button" id="btnApplyVoucher"
                        class="w-full py-4 px-6 rounded-2xl font-black text-white text-lg bg-emerald-600 hover:bg-emerald-700 shadow-xl shadow-emerald-600/30 flex items-center justify-center gap-3 transition transform active:scale-95">
                        <i class="fa-solid fa-lock text-xl"></i>
                        <span>Áp dụng Voucher (Khóa mã ngay)</span>
                    </button>
                    <p class="text-center text-xs text-slate-400 mt-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500 mr-1"></i>
                        Sau khi bấm "Áp dụng Voucher", mã sẽ lập tức bị khóa và chuyển trạng thái sang <strong>ĐÃ SỬ DỤNG</strong>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Trạng thái 2: ĐÃ SỬ DỤNG -->
        <div id="stateUsed" class="hidden bg-white rounded-3xl border-2 border-rose-500 shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Header Báo chữ đỏ ĐÃ SỬ DỤNG -->
            <div class="bg-rose-50 border-b border-rose-100 px-6 sm:px-8 py-5 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center text-2xl shadow-md shadow-rose-500/30">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </span>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-700 block">Trạng thái voucher:</span>
                        <h2 class="text-2xl sm:text-3xl font-black text-rose-600 tracking-wide flex items-center gap-2">
                            ĐÃ SỬ DỤNG
                        </h2>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block font-medium">Mã voucher</span>
                    <span id="usedCodeDisplay" class="font-mono text-xl sm:text-2xl font-black text-rose-600 bg-white px-3 py-1 rounded-xl border border-rose-200 inline-block"></span>
                </div>
            </div>

            <div class="p-6 sm:p-8 space-y-6">
                <!-- Cảnh báo mã đã bị khóa -->
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3">
                    <i class="fa-solid fa-ban text-rose-500 text-xl mt-0.5"></i>
                    <div>
                        <strong class="font-bold block">Mã này đã bị khóa và không thể sử dụng lại!</strong>
                        <p class="text-xs text-rose-700 mt-0.5">
                            Thời gian áp dụng: <span id="usedAtDisplay" class="font-bold"></span>
                            <span id="usedByWrapper"> | Người xác nhận: <span id="usedByDisplay" class="font-bold"></span></span>
                        </p>
                    </div>
                </div>

                <!-- Thông tin học viên ban đầu -->
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                        Thông tin học viên sở hữu mã này
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium mb-1">Họ và Tên</span>
                            <strong id="usedStudentName" class="text-slate-800 text-base font-bold block truncate"></strong>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium mb-1">Số điện thoại</span>
                            <strong id="usedPhone" class="text-slate-800 text-base font-mono font-bold block"></strong>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium mb-1">Ngày sinh</span>
                            <strong id="usedDob" class="text-slate-800 text-base font-bold block"></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trạng thái 3: MÃ SAI HOẶC KHÔNG TỒN TẠI (Báo chữ đỏ ĐÃ SỬ DỤNG / KHÔNG TỒN TẠI) -->
        <div id="stateNotFound" class="hidden bg-white rounded-3xl border-2 border-rose-500 shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <div class="bg-rose-50 border-b border-rose-100 px-6 sm:px-8 py-5 flex items-center gap-3">
                <span class="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center text-2xl shadow-md shadow-rose-500/30">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </span>
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-700 block">Thông báo hệ thống:</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-rose-600 tracking-wide">
                        ĐÃ SỬ DỤNG / KHÔNG HỢP LỆ
                    </h2>
                </div>
            </div>
            <div class="p-8 text-center space-y-4">
                <p id="notFoundMsg" class="text-base text-slate-700 font-medium">
                    Mã voucher không tồn tại hoặc đã bị khóa/hết hạn.
                </p>
                <p class="text-xs text-slate-400">
                    Vui lòng yêu cầu học sinh kiểm tra lại chính xác từng ký tự trên ảnh voucher đã được cấp.
                </p>
                <button type="button" onclick="document.getElementById('voucherCodeInput').focus(); document.getElementById('voucherCodeInput').select();"
                    class="px-6 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-rotate-left"></i> Nhập lại mã khác
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem ảnh voucher (nếu cần xem nhanh) -->
<div id="imgPreviewModal" class="fixed inset-0 z-[110] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="closeImgPreview()"></div>
    <div class="relative bg-white rounded-2xl max-w-xl w-full p-4 z-10 border shadow-2xl">
        <div class="flex justify-between items-center pb-2 mb-2 border-b">
            <span class="font-bold text-slate-800 text-sm">Ảnh phôi Voucher của học sinh</span>
            <button type="button" onclick="closeImgPreview()" class="text-slate-400 hover:text-slate-700 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <img id="previewModalImg" src="" alt="Voucher" class="w-full rounded-xl">
    </div>
</div>
@endsection

@push('scripts')
<script>
    const checkForm = document.getElementById('checkVoucherForm');
    const codeInput = document.getElementById('voucherCodeInput');
    const checkSubmitBtn = document.getElementById('btnCheckSubmit');
    const btnApply = document.getElementById('btnApplyVoucher');

    const stateInitial = document.getElementById('stateInitial');
    const stateLoading = document.getElementById('stateLoading');
    const stateUnused = document.getElementById('stateUnused');
    const stateUsed = document.getElementById('stateUsed');
    const stateNotFound = document.getElementById('stateNotFound');

    let currentCheckedVoucher = null;

    function hideAllStates() {
        stateInitial.classList.add('hidden');
        stateLoading.classList.add('hidden');
        stateUnused.classList.add('hidden');
        stateUsed.classList.add('hidden');
        stateNotFound.classList.add('hidden');
    }

    function showToast(type, msg) {
        if (window.TemplateUI && typeof TemplateUI.toast === 'function') {
            TemplateUI.toast(type, msg);
        } else {
            alert(msg);
        }
    }

    // Xử lý gửi tra cứu mã
    checkForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const code = codeInput.value.trim().toUpperCase();

        if (!code) {
            showToast('error', 'Vui lòng nhập mã voucher!');
            codeInput.focus();
            return;
        }

        hideAllStates();
        stateLoading.classList.remove('hidden');
        checkSubmitBtn.disabled = true;

        try {
            const res = await fetch("{{ route('admin.vouchers.check.ajax') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: code })
            });

            const data = await res.json();
            hideAllStates();

            if (!res.ok || !data.found) {
                // Mã không tồn tại hoặc sai: Báo chữ đỏ
                stateNotFound.classList.remove('hidden');
                document.getElementById('notFoundMsg').textContent = data.message || 'Mã voucher sai hoặc không tồn tại trên hệ thống.';
                return;
            }

            currentCheckedVoucher = data.voucher;

            if (data.is_unused) {
                // Trạng thái: CHƯA SỬ DỤNG (Báo chữ xanh)
                document.getElementById('unusedCodeDisplay').textContent = data.voucher.code;
                document.getElementById('unusedStudentName').textContent = data.voucher.student_name;
                document.getElementById('unusedPhone').textContent = data.voucher.phone;
                document.getElementById('unusedDob').textContent = data.voucher.dob || '—';
                document.getElementById('unusedCreatedAt').textContent = data.voucher.created_at;

                if (data.voucher.note) {
                    document.getElementById('unusedNote').textContent = data.voucher.note;
                    document.getElementById('unusedNoteWrapper').classList.remove('hidden');
                } else {
                    document.getElementById('unusedNoteWrapper').classList.add('hidden');
                }

                if (data.voucher.image_url) {
                    document.getElementById('unusedImageThumbWrapper').classList.remove('hidden');
                    document.getElementById('btnPreviewVoucherImg').onclick = function () {
                        document.getElementById('previewModalImg').src = data.voucher.image_url;
                        document.getElementById('imgPreviewModal').classList.remove('hidden');
                    };
                } else {
                    document.getElementById('unusedImageThumbWrapper').classList.add('hidden');
                }

                btnApply.disabled = false;
                btnApply.innerHTML = '<i class="fa-solid fa-lock text-xl"></i><span>Áp dụng Voucher (Khóa mã ngay)</span>';

                stateUnused.classList.remove('hidden');
            } else {
                // Trạng thái: ĐÃ SỬ DỤNG (Báo chữ đỏ)
                document.getElementById('usedCodeDisplay').textContent = data.voucher.code;
                document.getElementById('usedStudentName').textContent = data.voucher.student_name;
                document.getElementById('usedPhone').textContent = data.voucher.phone;
                document.getElementById('usedDob').textContent = data.voucher.dob || '—';
                document.getElementById('usedAtDisplay').textContent = data.voucher.used_at || '—';

                if (data.voucher.used_by_name) {
                    document.getElementById('usedByDisplay').textContent = data.voucher.used_by_name;
                    document.getElementById('usedByWrapper').classList.remove('hidden');
                } else {
                    document.getElementById('usedByWrapper').classList.add('hidden');
                }

                stateUsed.classList.remove('hidden');
            }
        } catch (err) {
            console.error('Check error:', err);
            hideAllStates();
            stateNotFound.classList.remove('hidden');
            document.getElementById('notFoundMsg').textContent = 'Có lỗi kết nối hệ thống. Vui lòng thử lại.';
        } finally {
            checkSubmitBtn.disabled = false;
        }
    });

    // Xử lý Áp dụng Voucher (Khóa mã chuyển sang ĐÃ SỬ DỤNG)
    btnApply?.addEventListener('click', async function () {
        if (!currentCheckedVoucher) return;

        const confirmApply = confirm(`XÁC NHẬN ÁP DỤNG VOUCHER:\n- Mã: ${currentCheckedVoucher.code}\n- Học viên: ${currentCheckedVoucher.student_name}\n- SĐT: ${currentCheckedVoucher.phone}\n- Ngày sinh: ${currentCheckedVoucher.dob}\n\nSau khi áp dụng, mã này sẽ lập tức bị KHÓA và chuyển thành ĐÃ SỬ DỤNG. Bạn có muốn tiếp tục?`);
        if (!confirmApply) return;

        btnApply.disabled = true;
        btnApply.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xl"></i><span>Đang khóa mã...</span>';

        try {
            const res = await fetch("{{ route('admin.vouchers.redeem') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: currentCheckedVoucher.code, voucher_id: currentCheckedVoucher.id })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                showToast('success', data.message || 'Áp dụng voucher thành công!');

                // Ngay lập tức chuyển giao diện sang trạng thái ĐÃ SỬ DỤNG (Báo chữ đỏ)
                hideAllStates();
                document.getElementById('usedCodeDisplay').textContent = currentCheckedVoucher.code;
                document.getElementById('usedStudentName').textContent = currentCheckedVoucher.student_name;
                document.getElementById('usedPhone').textContent = currentCheckedVoucher.phone;
                document.getElementById('usedDob').textContent = currentCheckedVoucher.dob;
                document.getElementById('usedAtDisplay').textContent = data.voucher?.used_at || 'Vừa xong';
                document.getElementById('usedByDisplay').textContent = data.voucher?.used_by_name || 'Quản lý';
                document.getElementById('usedByWrapper').classList.remove('hidden');

                stateUsed.classList.remove('hidden');
            } else {
                showToast('error', data.message || 'Không thể áp dụng voucher!');
                btnApply.disabled = false;
                btnApply.innerHTML = '<i class="fa-solid fa-lock text-xl"></i><span>Áp dụng Voucher (Khóa mã ngay)</span>';
            }
        } catch (err) {
            console.error('Redeem error:', err);
            showToast('error', 'Lỗi khi gửi yêu cầu khóa mã voucher.');
            btnApply.disabled = false;
            btnApply.innerHTML = '<i class="fa-solid fa-lock text-xl"></i><span>Áp dụng Voucher (Khóa mã ngay)</span>';
        }
    });

    function closeImgPreview() {
        document.getElementById('imgPreviewModal').classList.add('hidden');
    }
</script>
@endpush
