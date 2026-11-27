@php
    $scoreLabels = is_array($scoreLabels ?? null)
        ? $scoreLabels
        : \App\Models\CourseMonthlyEvaluation::scoreLabels();
@endphp

{{-- Toast thông báo chuẩn Figma (#1259:33424) --}}
<div class="sn-tc-toast" id="snTcToast" role="status" aria-live="polite" hidden>
    <span class="sn-tc-toast-ico" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.8"/>
            <path d="M6.8 10.2L9 12.4L13.5 7.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <span class="sn-tc-toast-msg" id="snTcToastMsg"></span>
</div>

{{-- Nền dim & Slide-over Drawer Hồ sơ học viên (#602:56064) --}}
<div class="sn-tc-drawer-root" id="snTcDrawerRoot" hidden>
    <div class="sn-tc-drawer-backdrop" id="snTcDrawerBackdrop"></div>

    <aside
        class="sn-tc-drawer"
        id="snTcStudentDrawer"
        role="dialog"
        aria-modal="true"
        aria-labelledby="snTcDrawerName"
    >
        {{-- Đầu drawer (#I669:65394;669:64292) --}}
        <header class="sn-tc-drawer-head">
            <span class="sn-tc-avatar sn-tc-avatar--xl sn-tc-avatar--blue" id="snTcDrawerAvatar">
                <span>HV</span>
            </span>

            <div class="sn-tc-drawer-ident">
                <h2 class="sn-tc-drawer-name" id="snTcDrawerName">Đang tải…</h2>
                <span class="sn-tc-drawer-meta" id="snTcDrawerMeta"></span>
                <div class="sn-tc-drawer-badges">
                    <span class="sn-tc-prog-tag is-purple" id="snTcDrawerTag" hidden></span>
                    <a class="sn-tc-drawer-detail-link" id="snTcDrawerWorkLink" href="#" hidden>Xem chi tiết bài làm →</a>
                </div>
            </div>

            <button
                type="button"
                class="sn-tc-drawer-close"
                id="snTcDrawerClose"
                aria-label="Đóng hồ sơ học viên"
            >
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9.5" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M9.2 9.2L14.8 14.8M14.8 9.2L9.2 14.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
            </button>
        </header>

        {{-- Trạng thái đang tải --}}
        <div class="sn-tc-drawer-loading" id="snTcDrawerLoading" hidden>
            <div class="sn-tc-spinner" aria-hidden="true"></div>
            <p>Đang tải hồ sơ học viên…</p>
        </div>

        {{-- Nội dung drawer (#I669:65394;669:64299) --}}
        <div class="sn-tc-drawer-body" id="snTcDrawerBody" hidden>
            {{-- Khối Cam kết đầu ra / Quỹ vi phạm (#950:4085 / #953:136485 / #953:136488) --}}
            <section class="sn-tc-commit-section" aria-label="Cam kết đầu ra">
                <div class="sn-tc-commit-head">
                    <h3 class="sn-tc-commit-title" id="snTcCommitTitle">Cam kết đầu ra</h3>
                    <span class="sn-tc-commit-sub" id="snTcCommitSub" hidden></span>
                </div>

                <div class="sn-tc-commit-grid">
                    <div class="sn-tc-commit-card">
                        <span class="sn-tc-commit-label">Vắng</span>
                        <div class="sn-tc-commit-ctrl">
                            <button type="button" class="sn-tc-step-btn" data-commit-metric="absence" data-commit-delta="-1" aria-label="Đặc cách giảm 1 buổi vắng">−</button>
                            <strong class="sn-tc-commit-val" id="snTcCommitAbsence">0/16 buổi</strong>
                            <button type="button" class="sn-tc-step-btn" data-commit-metric="absence" data-commit-delta="1" aria-label="Cộng lại 1 buổi vắng">+</button>
                        </div>
                    </div>

                    <div class="sn-tc-commit-card">
                        <span class="sn-tc-commit-label">Thiếu bài tập</span>
                        <div class="sn-tc-commit-ctrl">
                            <button type="button" class="sn-tc-step-btn" data-commit-metric="homework" data-commit-delta="-1" aria-label="Đặc cách giảm 1 buổi thiếu bài tập">−</button>
                            <strong class="sn-tc-commit-val" id="snTcCommitHomework">0/16 buổi</strong>
                            <button type="button" class="sn-tc-step-btn" data-commit-metric="homework" data-commit-delta="1" aria-label="Cộng lại 1 buổi thiếu bài tập">+</button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Khung xám 2 tab: Điểm danh & Đánh giá tuần (#669:64301) --}}
            <section class="sn-tc-drawer-well">
                <div class="sn-tc-drawer-tabs" role="tablist">
                    <button type="button" class="sn-tc-drawer-tab is-active" data-drawer-tab="attendance" role="tab" aria-selected="true">Điểm danh</button>
                    <button type="button" class="sn-tc-drawer-tab" data-drawer-tab="evaluation" role="tab" aria-selected="false">Đánh giá tuần</button>
                </div>

                {{-- Tab 1: Điểm danh tuần (#602:56064 & #888:40596) --}}
                <div class="sn-tc-drawer-pane" data-drawer-pane="attendance">
                    <div class="sn-tc-drawer-week-bar">
                        <strong id="snTcAttWeekHeader">Tuần</strong>
                        <span id="snTcAttWeekCount">0 buổi trong tuần</span>
                    </div>

                    <div class="sn-tc-att-list" id="snTcAttList"></div>
                </div>

                {{-- Tab 2: Đánh giá tuần (#598:50552 & #1259:30826–34041) --}}
                <div class="sn-tc-drawer-pane" data-drawer-pane="evaluation" hidden>
                    {{-- Chế độ Xem (Đã đánh giá · #598:50552) --}}
                    <div class="sn-tc-eval-card" id="snTcEvalViewCard" hidden>
                        <div class="sn-tc-eval-head">
                            <div>
                                <strong class="sn-tc-eval-week-title" id="snTcEvalViewWeek">Tuần</strong>
                                <span class="sn-tc-eval-week-dates">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <rect x="2" y="2.8" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.4"/>
                                        <path d="M5.2 1.6V4M10.8 1.6V4M2 6.4H14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                    </svg>
                                    <span id="snTcEvalViewRange"></span>
                                </span>
                            </div>
                            <span class="sn-tc-eval-badge is-done">Đã đánh giá</span>
                        </div>

                        <span class="sn-tc-eval-subhead">Đánh giá chung</span>

                        <div class="sn-tc-eval-metrics-view">
                            <div class="sn-tc-eval-metric-box">
                                <small>Thái độ học tập</small>
                                <strong id="snTcViewAttitude">—</strong>
                            </div>
                            <div class="sn-tc-eval-metric-box">
                                <small>Bài tập</small>
                                <strong id="snTcViewProgress">—</strong>
                            </div>
                            <div class="sn-tc-eval-metric-box">
                                <small>Chuyên cần</small>
                                <strong id="snTcViewParticipation">—</strong>
                            </div>
                        </div>

                        <div class="sn-tc-eval-comment-view" id="snTcViewComment"></div>

                        <div class="sn-tc-eval-foot">
                            <button type="button" class="sn-tc-btn-outline" id="snTcEvalStartEdit">
                                <span>Chỉnh sửa</span>
                                <svg width="16" height="16" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                                    <path d="M10.5 3.5L14.5 7.5M3.5 14.5L6.8 13.8L15 5.6C15.5 5.1 15.5 4.3 15 3.8L14.2 3C13.7 2.5 12.9 2.5 12.4 3L4.2 11.2L3.5 14.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Chế độ Nhập / Chỉnh sửa (#1259:30826 / #1259:31805 / #1259:34041) --}}
                    <form class="sn-tc-eval-card" id="snTcEvalForm" hidden novalidate>
                        <div class="sn-tc-eval-head">
                            <div>
                                <strong class="sn-tc-eval-week-title" id="snTcEvalFormWeek">Tuần</strong>
                                <span class="sn-tc-eval-week-dates" id="snTcEvalFormRange"></span>
                            </div>
                            <span class="sn-tc-eval-badge is-pending" id="snTcEvalFormBadge">Chưa đánh giá</span>
                        </div>

                        <span class="sn-tc-eval-subhead">Đánh giá chung</span>

                        <div class="sn-tc-eval-error" id="snTcEvalError" hidden></div>

                        <div class="sn-tc-eval-selects">
                            <label class="sn-tc-eval-field">
                                <span>Thái độ học tập</span>
                                <select name="score_attitude" id="snTcSelectAttitude">
                                    <option value="">Chọn mức</option>
                                    @foreach (array_reverse($scoreLabels, true) as $scVal => $scLabel)
                                        <option value="{{ $scVal }}">{{ $scVal }} · {{ $scLabel }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="sn-tc-eval-field">
                                <span>Bài tập</span>
                                <select name="score_progress" id="snTcSelectProgress">
                                    <option value="">Chọn mức</option>
                                    @foreach (array_reverse($scoreLabels, true) as $scVal => $scLabel)
                                        <option value="{{ $scVal }}">{{ $scVal }} · {{ $scLabel }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="sn-tc-eval-field">
                                <span>Chuyên cần</span>
                                <select name="score_participation" id="snTcSelectParticipation">
                                    <option value="">Chọn mức</option>
                                    @foreach (array_reverse($scoreLabels, true) as $scVal => $scLabel)
                                        <option value="{{ $scVal }}">{{ $scVal }} · {{ $scLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="sn-tc-editor-box">
                            <div class="sn-tc-editor-toolbar" role="toolbar" aria-label="Định dạng nhận xét">
                                <button type="button" data-cmd="undo" title="Hoàn tác" aria-label="Hoàn tác">↶</button>
                                <button type="button" data-cmd="redo" title="Làm lại" aria-label="Làm lại">↷</button>
                                <span class="sn-tc-editor-sep"></span>
                                <button type="button" data-cmd="bold" title="In đậm"><strong>B</strong></button>
                                <button type="button" data-cmd="italic" title="In nghiêng"><em>I</em></button>
                                <button type="button" data-cmd="underline" title="Gạch chân"><u>U</u></button>
                                <span class="sn-tc-editor-sep"></span>
                                <button type="button" data-cmd="justifyLeft" title="Căn trái">≡</button>
                                <button type="button" data-cmd="justifyCenter" title="Căn giữa">≣</button>
                                <button type="button" data-cmd="justifyRight" title="Căn phải">≢</button>
                            </div>
                            <div
                                class="sn-tc-editor-area"
                                id="snTcEvalEditor"
                                contenteditable="true"
                                data-placeholder="Nhận xét tuần cho học sinh: thái độ, tiến độ, điểm cần cải thiện..."
                            ></div>
                            <div class="sn-tc-editor-foot">
                                <span>p</span>
                                <span id="snTcEvalCharCount">0 ký tự</span>
                            </div>
                        </div>

                        <div class="sn-tc-eval-foot">
                            <button type="button" class="sn-tc-btn-outline" id="snTcEvalSecondaryBtn">Lưu nháp</button>
                            <button type="submit" class="sn-tc-btn-primary" id="snTcEvalPrimaryBtn">Gửi đánh giá</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </aside>
</div>

{{-- Modal Xác nhận đặc cách (#882:58193) --}}
<div class="sn-tc-modal-root" id="snTcConfirmModal" hidden>
    <div class="sn-tc-modal-backdrop" id="snTcConfirmBackdrop"></div>
    <div class="sn-tc-modal-card" role="dialog" aria-modal="true" aria-labelledby="snTcConfirmTitle">
        <h3 class="sn-tc-modal-title" id="snTcConfirmTitle">Xác nhận đặc cách</h3>
        <p class="sn-tc-modal-desc" id="snTcConfirmDesc"></p>
        <div class="sn-tc-modal-actions">
            <button type="button" class="sn-tc-btn-outline" id="snTcConfirmCancel">Hủy</button>
            <button type="button" class="sn-tc-btn-primary is-ready" id="snTcConfirmOk">Xác nhận</button>
        </div>
    </div>
</div>
