@extends('layouts.app', ['active' => 'rules'])

@php
    $rules = $rules ?? null;
    $editor = $rules?->editorName() ?? '';
    $updated = $rules?->updated_at;
@endphp

@section('title', 'Chỉnh sửa nội quy')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/school-rules.css') }}">
    <style>
        .sr-badge-clean {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 600;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            transition: all 0.25s ease;
        }
        .sr-badge-dirty {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 600;
            background: #fff7ed;
            color: #9a4700;
            border: 1px solid #ffedd5;
            transition: all 0.25s ease;
        }
        .sr-save-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .sr-save-btn.is-active {
            background: #9a4700;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(154, 71, 0, 0.25);
        }
        .sr-save-btn.is-active:hover {
            background: #7c3800;
            box-shadow: 0 4px 14px rgba(154, 71, 0, 0.35);
        }
        .sr-save-btn.is-disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }
        .sr-toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .sr-toast {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            animation: srToastSlideIn 0.3s ease forwards;
            pointer-events: auto;
            max-width: 420px;
        }
        .sr-toast.is-success {
            background: #ffffff;
            color: #15803d;
            border-left: 5px solid #16a34a;
        }
        .sr-toast.is-error {
            background: #ffffff;
            color: #b91c1c;
            border-left: 5px solid #dc2626;
        }
        @keyframes srToastSlideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        /* Modal Unsaved Warning */
        .sr-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 99998;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .sr-modal-overlay.is-open {
            opacity: 1;
            pointer-events: auto;
        }
        .sr-modal-box {
            background: #ffffff;
            border-radius: 20px;
            max-width: 460px;
            width: 90%;
            padding: 32px 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            text-align: center;
            transform: scale(0.95);
            transition: transform 0.2s ease;
        }
        .sr-modal-overlay.is-open .sr-modal-box {
            transform: scale(1);
        }
        .sr-modal-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #fff7ed;
            color: #9a4700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 20px;
        }
        .sr-btn-stay {
            padding: 10px 20px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .sr-btn-stay:hover {
            background: #e2e8f0;
        }
        .sr-btn-leave {
            padding: 10px 20px;
            border-radius: 10px;
            background: #dc2626;
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .sr-btn-leave:hover {
            background: #b91c1c;
        }
    </style>
@endpush

@section('content')
<div class="op-stack sr-page" style="max-width: 1080px; margin: 0 auto; padding: 24px 16px;">
    <!-- Top Action Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <a href="{{ route('school-rules.show') }}" class="op-btn-ghost" style="padding: 8px 14px; border-radius: 8px; text-decoration: none; color: #64748b; font-size: 13.5px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Xem nội quy</span>
            </a>
            <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0;">Chỉnh sửa nội quy</h1>
            
            <!-- Dynamic State Badge -->
            <span id="dirtyBadge" class="sr-badge-clean">
                <i class="fa-solid fa-circle-check" style="font-size: 11px;"></i>
                <span id="dirtyBadgeText">Chưa có thay đổi</span>
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <a class="op-btn-ghost" href="{{ route('school-rules.preview') }}" target="_blank" style="padding: 9px 16px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 13.5px; text-decoration: none; color: #475569; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Xem trước</span>
            </a>

            <button type="button" id="btnSaveRules" class="sr-save-btn is-disabled" disabled>
                <i class="fa-solid fa-floppy-disk" id="btnSaveIcon"></i>
                <span id="btnSaveText">Lưu nội quy</span>
            </button>
        </div>
    </div>

    <!-- Main Grid: Left Tips & Right Editor -->
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 24px; align-items: start;" class="sr-grid-layout">
        <!-- Sidebar Tips & Info -->
        <aside style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px; color: #9a4700; font-weight: 700; font-size: 15px;">
                <i class="fa-solid fa-circle-info"></i>
                <span>Thông tin & Hướng dẫn</span>
            </div>

            <div style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;" id="metaInfoBlock">
                @if ($editor !== '')
                    <div><i class="fa-solid fa-user-pen" style="width: 18px; color: #94a3b8;"></i> Sửa bởi: <strong style="color: #334155;">{{ $editor }}</strong></div>
                @endif
                @if ($updated)
                    <div style="margin-top: 4px;"><i class="fa-regular fa-clock" style="width: 18px; color: #94a3b8;"></i> Lần cuối: <strong style="color: #334155;">{{ $updated->format('d/m/Y H:i') }}</strong></div>
                @endif
            </div>

            <div style="font-size: 13.5px; font-weight: 600; color: #1e293b; margin-bottom: 10px;">Lưu ý khi soạn thảo:</div>
            <ul style="padding-left: 20px; margin: 0; font-size: 13px; color: #475569; line-height: 1.6;">
                <li style="margin-bottom: 8px;">Trình bày rõ ràng theo từng mục hoặc điều khoản đánh số.</li>
                <li style="margin-bottom: 8px;">Nêu cụ thể quy định về giờ học, bài tập, kiểm tra và tác phong.</li>
                <li style="margin-bottom: 8px;">Nút <strong>Lưu nội quy</strong> chỉ kích hoạt khi bạn có chỉnh sửa thực tế.</li>
                <li>Sau khi lưu, học viên sẽ nhìn thấy ngay nội quy mới trên cổng học tập.</li>
            </ul>
        </aside>

        <!-- Editor Form Card -->
        <section style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 26px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04);">
            @if ($errors->any())
                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
                    <strong style="display: block; margin-bottom: 4px;"><i class="fa-solid fa-circle-exclamation"></i> Có lỗi xảy ra:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="rulesForm" class="rules-form sr-form" method="post" action="{{ route('school-rules.update') }}">
                @csrf
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 8px;" for="title">
                        Tiêu đề nội quy <span style="color: #e11d48;">*</span>
                    </label>
                    <input id="title" name="title" type="text" maxlength="200" required
                        value="{{ old('title', $rules?->title ?: 'Nội quy lớp học') }}"
                        placeholder="Ví dụ: Nội quy lớp học Hán ngữ Sunny"
                        style="width: 100%; padding: 11px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 15px; font-family: inherit; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='#9a4700'; this.style.outline='none';"
                        onblur="this.style.borderColor='#cbd5e1';">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 8px;" for="content">
                        Nội dung chi tiết <span style="color: #e11d48;">*</span>
                    </label>
                    <textarea class="js-rules-editor" id="content" name="content" rows="18">{{ old('content', $rules?->content) }}</textarea>
                </div>
            </form>
        </section>
    </div>
</div>

<!-- Toast Container -->
<div class="sr-toast-container" id="toastContainer"></div>

<!-- Modal: Confirm Leave Page with Unsaved Changes -->
<div class="sr-modal-overlay" id="unsavedModal">
    <div class="sr-modal-box">
        <div class="sr-modal-icon-wrap">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 10px;">Bạn có thay đổi chưa lưu</h3>
        <p style="font-size: 14.5px; color: #64748b; line-height: 1.6; margin: 0 0 24px;">
            Nội dung nội quy vừa chỉnh sửa sẽ bị mất nếu bạn rời trang mà không bấm <strong>Lưu nội quy</strong>.
        </p>
        <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
            <button type="button" class="sr-btn-stay" id="btnModalStay">Tiếp tục chỉnh sửa</button>
            <button type="button" class="sr-btn-leave" id="btnModalLeave">Rời trang</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.body.dataset.tinymceBase = @json(asset('assets/tinymce'));
</script>
<script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
<script>
(() => {
    const form = document.getElementById("rulesForm");
    const titleInput = document.getElementById("title");
    const btnSave = document.getElementById("btnSaveRules");
    const btnSaveIcon = document.getElementById("btnSaveIcon");
    const btnSaveText = document.getElementById("btnSaveText");
    const dirtyBadge = document.getElementById("dirtyBadge");
    const dirtyBadgeText = document.getElementById("dirtyBadgeText");
    const toastContainer = document.getElementById("toastContainer");
    const unsavedModal = document.getElementById("unsavedModal");
    const btnModalStay = document.getElementById("btnModalStay");
    const btnModalLeave = document.getElementById("btnModalLeave");

    let isDirty = false;
    let isSaving = false;
    let initialTitle = titleInput ? titleInput.value.trim() : "";
    let initialContent = "";
    let pendingNavigationUrl = null;

    function showToast(message, isSuccess = true) {
        const toast = document.createElement("div");
        toast.className = `sr-toast ${isSuccess ? "is-success" : "is-error"}`;
        toast.innerHTML = `
            <i class="fa-solid ${isSuccess ? "fa-circle-check" : "fa-triangle-exclamation"}" style="font-size: 18px;"></i>
            <span>${message}</span>
        `;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transition = "opacity 0.3s ease";
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    function setDirtyState(dirty) {
        isDirty = dirty;
        if (dirty) {
            dirtyBadge.className = "sr-badge-dirty";
            dirtyBadge.innerHTML = `<i class="fa-solid fa-circle-exclamation" style="font-size: 11px;"></i><span>Có thay đổi chưa lưu</span>`;
            btnSave.className = "sr-save-btn is-active";
            btnSave.disabled = false;
        } else {
            dirtyBadge.className = "sr-badge-clean";
            dirtyBadge.innerHTML = `<i class="fa-solid fa-circle-check" style="font-size: 11px;"></i><span>Chưa có thay đổi</span>`;
            btnSave.className = "sr-save-btn is-disabled";
            btnSave.disabled = true;
        }
    }

    function checkDirty() {
        if (isSaving) return;
        const currentTitle = titleInput.value.trim();
        let currentContent = "";
        if (typeof tinymce !== "undefined" && tinymce.get("content")) {
            currentContent = tinymce.get("content").getContent().trim();
        } else {
            const contentEl = document.getElementById("content");
            currentContent = contentEl ? contentEl.value.trim() : "";
        }

        const changed = (currentTitle !== initialTitle) || (currentContent !== initialContent);
        if (changed !== isDirty) {
            setDirtyState(changed);
        }
    }

    const tinymceBaseUrl = document.body.dataset.tinymceBase || "/assets/tinymce";
    if (typeof tinymce !== "undefined") {
        tinymce.init({
            base_url: tinymceBaseUrl,
            suffix: ".min",
            language: "vi",
            language_url: tinymceBaseUrl + "/langs/vi.js",
            selector: ".js-rules-editor",
            height: 520,
            min_height: 380,
            menubar: false,
            plugins: "lists link table code autoresize",
            toolbar: "undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | link table | removeformat | code",
            block_formats: "Paragraph=p; Heading 2=h2; Heading 3=h3; Blockquote=blockquote",
            content_style: 'body{font-family:"Nunito",sans-serif;font-size:15px;line-height:1.75;color:#1e293b}',
            branding: false,
            promotion: false,
            statusbar: true,
            resize: true,
            setup(editor) {
                editor.on("init", () => {
                    initialContent = editor.getContent().trim();
                });
                editor.on("change input undo redo keyup NodeChange", () => {
                    editor.save();
                    checkDirty();
                });
            },
        });
    }

    if (titleInput) {
        titleInput.addEventListener("input", checkDirty);
    }

    // Save action via AJAX
    btnSave.addEventListener("click", async () => {
        if (!isDirty || isSaving) return;

        if (typeof tinymce !== "undefined" && tinymce.get("content")) {
            tinymce.get("content").save();
        }

        const titleVal = titleInput.value.trim();
        const contentVal = document.getElementById("content").value.trim();

        if (!titleVal) {
            showToast("Vui lòng nhập tiêu đề nội quy!", false);
            titleInput.focus();
            return;
        }

        isSaving = true;
        btnSave.disabled = true;
        btnSaveIcon.className = "fa-solid fa-spinner fa-spin";
        btnSaveText.textContent = "Đang lưu...";

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                body: formData,
            });

            if (response.ok) {
                const data = await response.json();
                showToast(data.message || "Lưu nội quy thành công!", true);

                // Update initial references
                initialTitle = titleVal;
                if (typeof tinymce !== "undefined" && tinymce.get("content")) {
                    initialContent = tinymce.get("content").getContent().trim();
                }
                setDirtyState(false);
            } else {
                let errText = "Lưu nội quy thất bại. Vui lòng kiểm tra lại!";
                try {
                    const errJson = await response.json();
                    if (errJson.message) errText = errJson.message;
                } catch(e) {}
                showToast(errText, false);
                btnSave.disabled = false;
            }
        } catch (error) {
            showToast("Có lỗi kết nối mạng. Vui lòng thử lại!", false);
            btnSave.disabled = false;
        } finally {
            isSaving = false;
            btnSaveIcon.className = "fa-solid fa-floppy-disk";
            btnSaveText.textContent = "Lưu nội quy";
            if (isDirty) {
                btnSave.className = "sr-save-btn is-active";
                btnSave.disabled = false;
            } else {
                btnSave.className = "sr-save-btn is-disabled";
                btnSave.disabled = true;
            }
        }
    });

    // Native browser unload confirmation
    window.addEventListener("beforeunload", (e) => {
        if (isDirty && !isSaving) {
            e.preventDefault();
            e.returnValue = "Bạn có thay đổi chưa lưu";
            return e.returnValue;
        }
    });

    // Custom in-page link interception for Figma modal warning
    document.addEventListener("click", (e) => {
        if (!isDirty || isSaving) return;

        const anchor = e.target.closest("a");
        if (anchor && anchor.href && !anchor.href.startsWith("javascript:") && anchor.target !== "_blank") {
            const currentOrigin = window.location.origin;
            if (anchor.href.startsWith(currentOrigin) || anchor.href.startsWith("/")) {
                e.preventDefault();
                pendingNavigationUrl = anchor.href;
                unsavedModal.classList.add("is-open");
            }
        }
    });

    btnModalStay.addEventListener("click", () => {
        unsavedModal.classList.remove("is-open");
        pendingNavigationUrl = null;
    });

    btnModalLeave.addEventListener("click", () => {
        isDirty = false;
        unsavedModal.classList.remove("is-open");
        if (pendingNavigationUrl) {
            window.location.href = pendingNavigationUrl;
        }
    });
})();
</script>
@endpush
