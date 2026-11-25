<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tạo Voucher Học Viên - Tiếng Hàn Sunny</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>
        tailwind = window.tailwind || {};
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Inter Tight"', 'ui-sans-serif', 'system-ui', 'sans-serif'] } } } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body, button, input, select, textarea { font-family: "Inter Tight", ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <header class="bg-white border-b border-slate-200/80">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Tiếng Hàn Sunny" class="h-10 w-10 rounded-xl object-cover shadow-sm">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-orange-500">Tiếng Hàn Sunny</p>
                    <h1 class="text-lg sm:text-xl font-extrabold text-slate-900">Tạo Voucher Học Viên</h1>
                </div>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-orange-50 text-orange-700 border border-orange-200 text-xs font-bold">
                <i class="fa-solid fa-ticket"></i> Công khai
            </span>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-10 space-y-6">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <p class="font-medium flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ $error }}</span>
                    </p>
                @endforeach
            </div>
        @endif

        <div id="publicToast" class="hidden fixed bottom-5 right-5 z-50 px-4 py-3 rounded-2xl shadow-xl text-sm font-bold text-white flex items-center gap-2"></div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Nhập thông tin tạo Voucher</h2>
                        <p class="text-xs text-slate-500">Điền đầy đủ thông tin bên dưới để nhận mã và ảnh voucher ngay lập tức.</p>
                    </div>
                </div>

                <form id="publicVoucherForm" method="POST" action="{{ route('public.vouchers.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="student_name" class="block text-sm font-semibold text-slate-700 mb-1">
                                Họ và Tên học viên <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <input type="text" name="student_name" id="student_name" value="{{ old('student_name') }}" required maxlength="255"
                                    placeholder="Ví dụ: Nguyễn Văn A"
                                    style="padding-left: 44px;"
                                    class="w-full pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition">
                            </div>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-semibold text-slate-700 mb-1">
                                Số Điện Thoại <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required maxlength="50"
                                    placeholder="Ví dụ: 0912 345 678"
                                    style="padding-left: 44px;"
                                    class="w-full pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition">
                            </div>
                        </div>

                        <div>
                            <label for="dob" class="block text-sm font-semibold text-slate-700 mb-1">
                                Ngày tháng năm sinh <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                                    <i class="fa-solid fa-calendar"></i>
                                </span>
                                <input type="date" name="dob" id="dob" value="{{ old('dob') }}" required
                                    style="padding-left: 44px;"
                                    class="w-full pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition">
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="note" class="block text-sm font-semibold text-slate-700 mb-1">
                                Ghi chú / Khóa học áp dụng (Tùy chọn)
                            </label>
                            <input type="text" name="note" id="note" value="{{ old('note') }}" maxlength="500"
                                placeholder="Ví dụ: Áp dụng khóa Tiếng Hàn sơ cấp 1"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition text-sm">
                        </div>
                    </div>

                    <div class="pt-2 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                            Tạo xong sẽ hiển thị ngay ảnh voucher để bạn tải về hoặc copy.
                        </p>
                        <button type="submit" id="btnSubmitPublicVoucher"
                            class="px-6 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 flex items-center gap-2 transition cursor-pointer">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>Tạo mã & Xuất ảnh</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-lg font-bold">
                                <i class="fa-solid fa-ticket"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">Phôi Voucher chuẩn</h2>
                                <p class="text-xs text-slate-500">Mẫu voucher chính thức của Tiếng Hàn Sunny</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-950 shadow-inner">
                        <img src="{{ $templateUrl }}?v={{ time() }}" alt="Phôi voucher" class="w-full h-auto object-cover block">
                    </div>

                    <div class="mt-4 p-3.5 bg-orange-50/70 rounded-xl border border-orange-100 text-xs text-slate-600 space-y-1.5">
                        <div class="font-bold text-orange-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-info"></i> Thông tin hiển thị trên voucher:
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                            <li><strong>Mã Voucher:</strong> Tự động tạo ngẫu nhiên</li>
                            <li><strong>Họ và tên, SĐT, Ngày sinh:</strong> Tự động in lên phôi ảnh</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal hiển thị ảnh Voucher vừa tạo -->
    <div id="publicVoucherModal" class="hidden fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden border border-slate-100">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                    <div>
                        <h3 class="font-extrabold text-slate-800 text-base">Voucher của bạn đã sẵn sàng</h3>
                        <p class="text-xs text-slate-500">Mã: <strong id="modalCodeBadge" class="text-orange-600 font-mono"></strong></p>
                    </div>
                </div>
                <button type="button" onclick="closePublicVoucherModal()" class="w-8 h-8 rounded-lg hover:bg-slate-200 text-slate-500 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-950">
                    <img id="modalVoucherImg" src="" alt="Voucher Image" class="w-full h-auto object-contain max-h-[380px] mx-auto block">
                </div>

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
                        <span class="text-slate-400 block font-medium">Ngày sinh</span>
                        <strong id="modalDob" class="text-slate-800 font-bold text-sm block"></strong>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <button type="button" onclick="copyPublicVoucherCode()" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-hashtag text-orange-500"></i>
                    <span>Copy mã</span>
                </button>
                <div class="flex items-center gap-2">
                    <a id="btnDownloadVoucher" href="#" download="" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-download text-blue-500"></i>
                        <span>Tải ảnh về máy</span>
                    </a>
                    <button type="button" onclick="copyPublicVoucherImage()" id="btnCopyVoucherImg"
                        class="px-5 py-2.5 rounded-xl text-white font-bold text-sm bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md flex items-center gap-2">
                        <i class="fa-solid fa-copy"></i>
                        <span id="btnCopyImgText">Copy ảnh</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentVoucher = null;

        function showPublicToast(type, msg) {
            const el = document.getElementById('publicToast');
            if (!el) return;
            el.className = 'fixed bottom-5 right-5 z-50 px-4 py-3 rounded-2xl shadow-xl text-sm font-bold text-white flex items-center gap-2 ' +
                (type === 'success' ? 'bg-emerald-600' : 'bg-rose-600');
            el.textContent = msg;
            el.classList.remove('hidden');
            clearTimeout(window._publicToastTimer);
            window._publicToastTimer = setTimeout(() => el.classList.add('hidden'), 3200);
        }

        function openPublicVoucherModal(v) {
            currentVoucher = v;
            document.getElementById('modalCodeBadge').textContent = v.code || '';
            document.getElementById('modalStudentName').textContent = v.student_name || '';
            document.getElementById('modalPhone').textContent = v.phone || '';
            document.getElementById('modalDob').textContent = v.dob || '';

            const imgEl = document.getElementById('modalVoucherImg');
            const dlBtn = document.getElementById('btnDownloadVoucher');
            imgEl.src = v.image_url || '';
            dlBtn.href = v.image_url || '#';
            dlBtn.download = `Voucher_${v.code || 'sunny'}.png`;

            document.getElementById('publicVoucherModal').classList.remove('hidden');
        }

        function closePublicVoucherModal() {
            document.getElementById('publicVoucherModal').classList.add('hidden');
        }

        function copyPublicVoucherCode() {
            if (!currentVoucher?.code || !navigator.clipboard) return;
            navigator.clipboard.writeText(currentVoucher.code).then(() => {
                showPublicToast('success', 'Đã copy mã: ' + currentVoucher.code);
            });
        }

        async function copyPublicVoucherImage() {
            if (!currentVoucher?.image_url) return;
            const label = document.getElementById('btnCopyImgText');
            const orig = label.textContent;
            label.textContent = 'Đang copy...';
            try {
                const res = await fetch(currentVoucher.image_url);
                const blob = await res.blob();
                await navigator.clipboard.write([new ClipboardItem({ [blob.type || 'image/png']: blob })]);
                showPublicToast('success', 'Đã copy ảnh voucher vào bộ nhớ tạm!');
            } catch (e) {
                showPublicToast('error', 'Trình duyệt không hỗ trợ copy ảnh trực tiếp, vui lòng dùng nút Tải ảnh.');
            } finally {
                label.textContent = orig;
            }
        }

        const form = document.getElementById('publicVoucherForm');
        form?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitPublicVoucher');
            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Đang tạo & xuất ảnh...</span>';

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: new FormData(form)
                });
                const data = await res.json();
                if (res.ok && data.success && data.voucher) {
                    form.reset();
                    openPublicVoucherModal(data.voucher);
                    showPublicToast('success', data.message || 'Tạo mã voucher thành công!');
                } else {
                    const errMsg = data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Không thể tạo voucher.');
                    showPublicToast('error', errMsg);
                }
            } catch (err) {
                form.submit();
            } finally {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        });

        @if (session('new_voucher'))
            openPublicVoucherModal(@json(session('new_voucher')));
        @endif
    </script>
</body>
</html>
