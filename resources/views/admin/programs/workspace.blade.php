@extends('layouts.app', ['active' => 'programs'])

@php
    $levelCount = $program->levels->count();
    $sessionCount = $program->levels->sum(fn ($level) => $level->sessions->count());
    $assessmentCount = $program->assessments->count();
    $reviewCount = $program->knowledgeReviews->count();
    $lead = trim(strip_tags((string) $program->description));
@endphp

@section('title', $program->title)

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/program-workspace.css') }}?v=6">
@endpush

@section('subnav')
    <a href="{{ route('admin.programs.index') }}" class="os-tab">Danh sách</a>
    <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'info']) }}" class="os-tab{{ $tab === 'info' ? ' is-on' : '' }}">Thông tin</a>
    <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels']) }}" class="os-tab{{ $tab === 'levels' ? ' is-on' : '' }}">Trình độ &amp; buổi<span class="pw-tab-count">{{ $sessionCount }}</span></a>
    <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'assessments']) }}" class="os-tab{{ $tab === 'assessments' ? ' is-on' : '' }}">Bài kiểm tra<span class="pw-tab-count">{{ $assessmentCount }}</span></a>
    <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'reviews']) }}" class="os-tab{{ $tab === 'reviews' ? ' is-on' : '' }}">Ôn tập KT<span class="pw-tab-count">{{ $reviewCount }}</span></a>
@endsection

@section('content')
<div class="pw-page">
    <header class="pw-hero">
        <div class="pw-hero-top">
            <div>
                <p class="pw-kicker"><i class="fa-solid fa-graduation-cap"></i> Chương trình học</p>
                <h1>{{ $program->title }}</h1>
                <p class="pw-lead">{{ $lead !== '' ? $lead : 'Mẫu chương trình dùng chung khi mở khóa học.' }}</p>
                <div class="pw-meta">
                    <span><i class="fa-solid fa-user"></i>{{ $program->teacher?->name ?: 'Chưa gán người tạo' }}</span>
                    <span><i class="fa-regular fa-calendar"></i>{{ optional($program->created_at)->format('d/m/Y') ?: '—' }}</span>
                    <span><i class="fa-solid fa-layer-group"></i>{{ $levelCount }} trình độ · {{ $sessionCount }} buổi</span>
                </div>
            </div>
            @if ($program->isArchived())
                <span class="pw-pill is-off"><i class="fa-solid fa-box-archive"></i> Đã lưu trữ — chỉ xem</span>
            @else
                <div class="pw-hero-actions">
                    <span class="pw-pill is-on"><i class="fa-solid fa-circle-check"></i> Đang dùng</span>
                    <form method="post" action="{{ route('admin.programs.refresh', $program->id) }}" onsubmit="return confirm('Cập nhật lại chương trình? Hệ thống sẽ xóa buổi học mất trình độ (do xóa trình độ trước đây) và đồng bộ danh sách buổi sang các khóa học đang gắn chương trình này.');">
                        @csrf
                        <button type="submit" class="op-btn-ghost" title="Dọn buổi còn sót và đồng bộ lại khóa học">
                            <i class="fa-solid fa-arrows-rotate"></i> Cập nhật lại chương trình
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="pw-stats">
            <a class="pw-stat{{ $tab === 'levels' ? ' is-on' : '' }}" href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels']) }}">
                <span class="pw-stat-ico"><i class="fa-solid fa-layer-group"></i></span>
                <span><strong>{{ $levelCount }}</strong><small>Trình độ</small></span>
            </a>
            <a class="pw-stat{{ $tab === 'levels' ? ' is-on' : '' }}" href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels']) }}">
                <span class="pw-stat-ico"><i class="fa-solid fa-chalkboard"></i></span>
                <span><strong>{{ $sessionCount }}</strong><small>Buổi học</small></span>
            </a>
            <a class="pw-stat{{ $tab === 'assessments' ? ' is-on' : '' }}" href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'assessments']) }}">
                <span class="pw-stat-ico"><i class="fa-solid fa-file-pen"></i></span>
                <span><strong>{{ $assessmentCount }}</strong><small>Bài kiểm tra</small></span>
            </a>
            <a class="pw-stat{{ $tab === 'reviews' ? ' is-on' : '' }}" href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'reviews']) }}">
                <span class="pw-stat-ico"><i class="fa-solid fa-book-open"></i></span>
                <span><strong>{{ $reviewCount }}</strong><small>Ôn tập kiến thức</small></span>
            </a>
        </div>
    </header>

    @if ($tab === 'assessments')
        @include('admin.programs.partials.assessment-list')
    @elseif ($tab === 'reviews')
        @include('admin.programs.partials.review-list')
    @elseif ($tab === 'info')
        <div class="pw-info-grid">
            <section class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>Thông tin chương trình</h2>
                        <p>Tên và mô tả hiển thị khi giáo viên gắn chương trình vào khóa học.</p>
                    </div>
                </div>
                <form class="pw-form" method="post" action="{{ route('admin.programs.update', $program->id) }}">
                    @csrf
                    <div class="pw-field">
                        <div class="pw-field-top">
                            <label for="program-title"><i class="fa-solid fa-signature"></i> Tên chương trình</label>
                            <em class="pw-req">Bắt buộc</em>
                        </div>
                        <div class="pw-control">
                            <input id="program-title" name="title" required minlength="3" maxlength="255" value="{{ old('title', $program->title) }}" placeholder="VD: TOPIK I — Lộ trình cơ bản" @disabled($program->isArchived())>
                        </div>
                    </div>
                    <div class="pw-field">
                        <div class="pw-field-top">
                            <label for="program-description"><i class="fa-solid fa-align-left"></i> Mô tả</label>
                        </div>
                        <div class="pw-control">
                            <textarea id="program-description" name="description" rows="7" placeholder="Mục tiêu, đối tượng, ghi chú dùng chung khi mở khóa học..." @disabled($program->isArchived())>{{ old('description', $program->description) }}</textarea>
                        </div>
                    </div>
                    @unless ($program->isArchived())
                        <div class="pw-form-actions">
                            <button type="submit" class="op-btn"><i class="fa-solid fa-floppy-disk"></i> Lưu thông tin</button>
                            <button type="submit" form="program-archive-form" class="op-btn-ghost" onclick="return confirm('Lưu trữ chương trình này? Không thể chỉnh sửa sau khi lưu trữ.');">
                                <i class="fa-solid fa-box-archive"></i> Lưu trữ
                            </button>
                        </div>
                    @endunless
                </form>
                @unless ($program->isArchived())
                    <form id="program-archive-form" method="post" action="{{ route('admin.programs.archive', $program->id) }}">@csrf</form>
                @endunless
            </section>

            <aside class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>Tóm tắt</h2>
                        <p>Trạng thái và bước soạn tiếp theo.</p>
                    </div>
                </div>
                <div class="pw-aside">
                    <div class="pw-aside-card">
                        <strong>Trạng thái</strong>
                        <p>{{ $program->isArchived() ? 'Đã lưu trữ — chỉ xem, không chỉnh sửa.' : 'Đang dùng — có thể gắn vào khóa học mới.' }}</p>
                    </div>
                    <div class="pw-aside-card">
                        <strong>Người tạo</strong>
                        <p>{{ $program->teacher?->name ?: '—' }}</p>
                    </div>
                    @unless ($program->isArchived())
                        <div class="pw-aside-card">
                            <strong>Bước tiếp theo</strong>
                            <p>Mở <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels']) }}">Trình độ &amp; buổi</a> để thêm buổi học, rồi soạn bài kiểm tra và ôn tập.</p>
                        </div>
                    @endunless
                </div>
            </aside>
        </div>
    @else
        <div class="pw-split">
            <section class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>Trình độ</h2>
                        <p>{{ $levelCount }} cấp · kéo để đổi thứ tự</p>
                    </div>
                    @unless ($program->isArchived())
                        <div class="pw-panel-tools">
                            <button type="button" class="op-btn js-level-create"><i class="fa-solid fa-plus"></i> Thêm</button>
                        </div>
                    @endunless
                </div>
                <ul id="level-list" class="pw-level-list" data-reorder-url="{{ route('admin.programs.levels.reorder', $program->id) }}">
                    @forelse ($program->levels as $index => $level)
                        <li class="pw-level js-level-row{{ $selectedLevel && $selectedLevel->id === $level->id ? ' is-on' : '' }}" data-id="{{ $level->id }}" draggable="{{ $program->isActive() ? 'true' : 'false' }}">
                            <div class="pw-level-num">
                                <span>Cấp</span>
                                <strong>{{ $index + 1 }}</strong>
                            </div>
                            <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels', 'level' => $level->id]) }}" class="pw-level-link">
                                <strong>{{ $level->title }}</strong>
                                <small>{{ $level->sessions->count() }} buổi học</small>
                            </a>
                            <div class="pw-tools">
                                @unless ($program->isArchived())
                                    <span class="js-drag pw-grip" title="Kéo để sắp xếp"><i class="fa-solid fa-grip-vertical"></i></span>
                                    <button type="button" class="pw-icon-btn js-level-edit" title="Sửa trình độ" data-id="{{ $level->id }}" data-title="{{ $level->title }}" data-description="{{ $level->description }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    @if ($program->levels->count() > 1)
                                        <form method="post" action="{{ route('admin.programs.levels.destroy', [$program->id, $level->id]) }}" onsubmit="return confirm('Xóa trình độ này? Tất cả buổi học và nội dung trong trình độ cũng sẽ bị xóa vĩnh viễn.');">
                                            @csrf
                                            <button class="pw-icon-btn is-danger" title="Xóa trình độ"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endif
                                @endunless
                            </div>
                        </li>
                    @empty
                        <li class="pw-empty">
                            <span><i class="fa-solid fa-layer-group"></i></span>
                            <p>Chưa có trình độ nào.</p>
                            @unless ($program->isArchived())
                                <button type="button" class="op-btn js-level-create"><i class="fa-solid fa-plus"></i> Thêm trình độ đầu tiên</button>
                            @endunless
                        </li>
                    @endforelse
                </ul>
            </section>

            <section class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>{{ $selectedLevel?->title ?? 'Buổi học' }}</h2>
                        <p>{{ $selectedLevel ? $selectedLevel->sessions->count().' buổi trong trình độ này' : 'Chọn một trình độ bên trái để xem buổi học.' }}</p>
                    </div>
                    @if ($selectedLevel && $program->isActive())
                        <div class="pw-panel-tools">
                            <button type="button" class="op-btn js-session-create" data-level-id="{{ $selectedLevel->id }}"><i class="fa-solid fa-plus"></i> Thêm buổi</button>
                        </div>
                    @endif
                </div>

                @if (! $selectedLevel)
                    <div class="pw-empty">
                        <span><i class="fa-solid fa-chalkboard"></i></span>
                        <p>Chưa có trình độ nào.</p>
                    </div>
                @else
                    @if ($selectedLevel->sessions->count() > 9)
                        @php
                            $totalSes = $selectedLevel->sessions->count();
                            $chunkSize = 9;
                            $numChunks = (int) ceil($totalSes / $chunkSize);
                        @endphp
                        <div class="pw-session-chunk-nav" style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; padding: 4px 0;">
                            <button type="button" class="pw-chunk-tab is-active" data-chunk="all" style="padding: 6px 14px; border-radius: 9999px; font-size: 13px; font-weight: 700; border: 1px solid transparent; background: #9a4700; color: #fff; cursor: pointer; transition: all 0.2s;">
                                Tất cả ({{ $totalSes }})
                            </button>
                            @for ($c = 0; $c < $numChunks; $c++)
                                @php
                                    $startNo = $c * $chunkSize + 1;
                                    $endNo = min(($c + 1) * $chunkSize, $totalSes);
                                @endphp
                                <button type="button" class="pw-chunk-tab" data-chunk="{{ $c }}" style="padding: 6px 14px; border-radius: 9999px; font-size: 13px; font-weight: 700; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; cursor: pointer; transition: all 0.2s;">
                                    Buổi {{ $startNo }} - {{ $endNo }}
                                </button>
                            @endfor
                        </div>
                    @endif

                    <div id="session-list" class="pw-session-list" data-level-id="{{ $selectedLevel->id }}" data-reorder-url="{{ route('admin.programs.sessions.reorder', $program->id) }}">
                        @forelse ($selectedLevel->sessions as $index => $session)
                            @php
                                $pills = [
                                    ['label' => 'Trước', 'count' => (int) ($session->pre_count ?? 0)],
                                    ['label' => 'KT cũ', 'count' => (int) ($session->review_count ?? 0)],
                                    ['label' => 'TN', 'count' => (int) ($session->quiz_count ?? 0)],
                                    ['label' => 'Ôn', 'count' => (int) ($session->post_practice_count ?? 0)],
                                ];
                            @endphp
                            <article class="pw-session js-session-row" data-id="{{ $session->id }}" data-index="{{ $index }}" draggable="{{ $program->isActive() ? 'true' : 'false' }}">
                                <span class="js-drag pw-grip" title="Kéo để sắp xếp">
                                    @unless ($program->isArchived())
                                        <i class="fa-solid fa-grip-vertical"></i>
                                    @endunless
                                </span>
                                <div class="pw-session-num">
                                    <span>Buổi</span>
                                    <strong>{{ $index + 1 }}</strong>
                                </div>
                                <a href="{{ route('admin.programs.sessions.show', [$program->id, $session->id]) }}" class="pw-session-body">
                                    <strong>{{ $session->title }}</strong>
                                    @if ($session->description)
                                        <small>{{ $session->description }}</small>
                                    @endif
                                    <div class="pw-pills">
                                        @foreach ($pills as $pill)
                                            <span class="pw-chip{{ $pill['count'] > 0 ? ' is-ready' : '' }}">{{ $pill['label'] }} {{ $pill['count'] }}</span>
                                        @endforeach
                                    </div>
                                </a>
                                <div class="pw-actions">
                                    <a href="{{ route('admin.programs.sessions.show', [$program->id, $session->id]) }}" class="pw-link-btn">Soạn</a>
                                    @unless ($program->isArchived())
                                        <button type="button" class="pw-icon-btn js-session-edit" title="Sửa buổi"
                                            data-id="{{ $session->id }}"
                                            data-level-id="{{ $selectedLevel->id }}"
                                            data-title="{{ $session->title }}"
                                            data-description="{{ $session->description }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form method="post" action="{{ route('admin.programs.sessions.destroy', [$program->id, $session->id]) }}" onsubmit="return confirm('Xóa buổi học này?');">
                                            @csrf
                                            <button class="pw-icon-btn is-danger" title="Xóa buổi"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endunless
                                </div>
                            </article>
                        @empty
                            <div class="pw-empty">
                                <span><i class="fa-solid fa-chalkboard"></i></span>
                                <p>Chưa có buổi học trong trình độ này.</p>
                                @if ($program->isActive())
                                    <button type="button" class="op-btn js-session-create" data-level-id="{{ $selectedLevel->id }}"><i class="fa-solid fa-plus"></i> Thêm buổi đầu tiên</button>
                                @endif
                            </div>
                        @endforelse
                    </div>
                @endif
            </section>
        </div>
    @endif
</div>
@endsection

@section('drawer')
<div id="level-form-tpl" hidden>
    <form class="pw-form" method="post" action="{{ route('admin.programs.levels.save', $program->id) }}">
        @csrf
        <input type="hidden" name="level_id" value="">
        <div class="pw-field">
            <div class="pw-field-top">
                <label><i class="fa-solid fa-layer-group"></i> Tên trình độ</label>
                <em class="pw-req">Bắt buộc</em>
            </div>
            <div class="pw-control">
                <input name="title" required maxlength="255" placeholder="VD: Sơ cấp 1">
            </div>
        </div>
        <div class="pw-field">
            <div class="pw-field-top">
                <label><i class="fa-solid fa-align-left"></i> Mô tả</label>
            </div>
            <div class="pw-control">
                <textarea name="description" rows="4" placeholder="Ghi chú ngắn cho trình độ này"></textarea>
            </div>
        </div>
        <div class="pw-form-actions">
            <button type="submit" class="op-btn">Lưu trình độ</button>
        </div>
    </form>
</div>
<div id="session-form-tpl" hidden>
    <form class="pw-form" method="post" action="{{ route('admin.programs.sessions.save', $program->id) }}">
        @csrf
        <input type="hidden" name="session_id" value="">
        <input type="hidden" name="level_id" value="{{ $selectedLevel?->id }}">
        <div class="pw-field">
            <div class="pw-field-top">
                <label><i class="fa-solid fa-chalkboard"></i> Tên buổi học</label>
                <em class="pw-req">Bắt buộc</em>
            </div>
            <div class="pw-control">
                <input name="title" required maxlength="255" placeholder="VD: Buổi 1 — Chào hỏi">
            </div>
        </div>
        <div class="pw-field">
            <div class="pw-field-top">
                <label><i class="fa-solid fa-align-left"></i> Mô tả</label>
            </div>
            <div class="pw-control">
                <textarea name="description" rows="4" placeholder="Mục tiêu buổi học, ghi chú soạn bài"></textarea>
            </div>
        </div>
        <div class="pw-form-actions">
            <button type="submit" class="op-btn">Lưu buổi học</button>
        </div>
    </form>
</div>
<div id="assessment-form-tpl" hidden>
    <form class="pw-form" method="post" action="{{ route('admin.programs.assessments.store', $program->id) }}">
        @csrf
        <input type="hidden" name="assessment_id" value="">
        <div class="pw-field">
            <div class="pw-field-top">
                <label><i class="fa-solid fa-file-pen"></i> Tên bài kiểm tra</label>
                <em class="pw-req">Bắt buộc</em>
            </div>
            <div class="pw-control">
                <input name="title" required maxlength="255" placeholder="VD: Kiểm tra giữa khóa">
            </div>
        </div>
        <div class="rt-field">
            <label class="rt-label"><i class="fa-solid fa-align-left"></i> Mô tả / hướng dẫn</label>
            <textarea name="description" rows="6" class="js-richtext" data-editor-height="260" data-editor-compact="1"></textarea>
        </div>
        <div class="pw-field">
            <div class="pw-field-top">
                <label><i class="fa-solid fa-clock"></i> Thời lượng (phút)</label>
            </div>
            <div class="pw-control">
                <input type="number" name="duration_minutes" min="1" max="600" placeholder="Để trống nếu không giới hạn">
            </div>
            <span class="hint">Học viên thấy đồng hồ đếm ngược khi có thời lượng.</span>
        </div>
        <div class="pw-form-actions">
            <button type="submit" class="op-btn">Lưu bài kiểm tra</button>
        </div>
    </form>
</div>
<div id="review-form-tpl" hidden>
    <form class="pw-form" method="post" action="{{ route('admin.programs.reviews.store', $program->id) }}">
        @csrf
        <input type="hidden" name="review_id" value="">
        <div class="pw-field">
            <div class="pw-field-top">
                <label><i class="fa-solid fa-book-open"></i> Tên ôn tập</label>
                <em class="pw-req">Bắt buộc</em>
            </div>
            <div class="pw-control">
                <input name="title" required maxlength="255" placeholder="VD: Ôn tập tuần 4">
            </div>
        </div>
        <div class="rt-field">
            <label class="rt-label"><i class="fa-solid fa-align-left"></i> Mô tả / hướng dẫn</label>
            <textarea name="description" rows="6" class="js-richtext" data-editor-height="260" data-editor-compact="1"></textarea>
        </div>
        <div class="pw-field">
            <div class="pw-field-top">
                <label><i class="fa-solid fa-clock"></i> Thời lượng (phút)</label>
            </div>
            <div class="pw-control">
                <input type="number" name="duration_minutes" min="1" max="600" placeholder="Để trống nếu không giới hạn">
            </div>
        </div>
        <div class="pw-form-actions">
            <button type="submit" class="op-btn">Lưu ôn tập</button>
        </div>
    </form>
</div>
@endsection

@if (in_array($tab, ['assessments', 'reviews'], true))
    @include('admin.programs.partials.richtext-assets')
@endif

@push('scripts')
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function openForm(title, tplId, fill) {
        TemplateUI.openDrawer({ title, html: document.getElementById(tplId).innerHTML });
        const form = document.querySelector('#drawer-body form');
        fill(form);
        const body = document.getElementById('drawer-body');
        window.ProgramRichtext?.widenDrawerIfNeeded(body);
        window.ProgramRichtext?.init(body);
    }

    document.querySelectorAll('.js-level-create').forEach((btn) => {
        btn.addEventListener('click', () => {
            openForm('Thêm trình độ', 'level-form-tpl', (form) => {
                form.level_id.value = '';
                form.title.value = '';
                form.description.value = '';
            });
        });
    });

    document.querySelectorAll('.js-level-edit').forEach((btn) => {
        btn.addEventListener('click', () => {
            openForm('Sửa trình độ', 'level-form-tpl', (form) => {
                form.level_id.value = btn.dataset.id;
                form.title.value = btn.dataset.title || '';
                form.description.value = btn.dataset.description || '';
            });
        });
    });

    document.querySelectorAll('.js-session-create').forEach((btn) => {
        btn.addEventListener('click', () => {
            const levelId = btn.dataset.levelId;
            openForm('Thêm buổi học', 'session-form-tpl', (form) => {
                form.session_id.value = '';
                form.level_id.value = levelId;
                form.title.value = '';
                form.description.value = '';
            });
        });
    });

    document.querySelectorAll('.js-session-edit').forEach((btn) => {
        btn.addEventListener('click', () => {
            openForm('Sửa buổi học', 'session-form-tpl', (form) => {
                form.session_id.value = btn.dataset.id;
                form.level_id.value = btn.dataset.levelId;
                form.title.value = btn.dataset.title || '';
                form.description.value = btn.dataset.description || '';
            });
        });
    });

    function bindSortable(list, extra = {}) {
        if (!list || list.querySelectorAll('[draggable="true"]').length < 2) {
            return;
        }
        let dragged = null;
        list.querySelectorAll('[draggable="true"]').forEach((row) => {
            row.addEventListener('dragstart', () => { dragged = row; row.classList.add('opacity-50'); });
            row.addEventListener('dragend', () => { dragged = null; row.classList.remove('opacity-50'); });
            row.addEventListener('dragover', (event) => { event.preventDefault(); });
            row.addEventListener('drop', (event) => {
                event.preventDefault();
                if (!dragged || dragged === row) return;
                const rows = [...list.querySelectorAll('[draggable="true"]')];
                const from = rows.indexOf(dragged);
                const to = rows.indexOf(row);
                if (from < to) row.after(dragged); else row.before(dragged);
                const order = [...list.querySelectorAll('[draggable="true"]')].map((el) => el.dataset.id);
                fetch(list.dataset.reorderUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ order, ...extra }),
                }).then((res) => {
                    if (!res.ok) TemplateUI.toast('error', 'Không sắp xếp được.');
                });
            });
        });
    }

    bindSortable(document.getElementById('level-list'));
    const sessionList = document.getElementById('session-list');
    if (sessionList) {
        bindSortable(sessionList, { level_id: Number(sessionList.dataset.levelId) });
    }
    bindSortable(document.getElementById('assessment-list'));
    bindSortable(document.getElementById('review-list'));

    document.querySelectorAll('.js-assessment-create').forEach((btn) => {
        btn.addEventListener('click', () => {
            openForm('Thêm bài kiểm tra', 'assessment-form-tpl', (form) => {
                form.action = @json(route('admin.programs.assessments.store', $program->id));
                form.assessment_id.value = '';
                form.title.value = '';
                form.description.value = '';
                form.duration_minutes.value = '';
            });
        });
    });

    document.querySelectorAll('.js-assessment-edit').forEach((btn) => {
        btn.addEventListener('click', () => {
            openForm('Sửa bài kiểm tra', 'assessment-form-tpl', (form) => {
                form.action = btn.dataset.updateUrl;
                form.assessment_id.value = btn.dataset.id;
                form.title.value = btn.dataset.title || '';
                form.description.value = btn.dataset.description || '';
                form.duration_minutes.value = btn.dataset.duration || '';
            });
        });
    });

    document.querySelectorAll('.js-review-create').forEach((btn) => {
        btn.addEventListener('click', () => {
            openForm('Thêm ôn tập kiến thức', 'review-form-tpl', (form) => {
                form.action = @json(route('admin.programs.reviews.store', $program->id));
                form.review_id.value = '';
                form.title.value = '';
                form.description.value = '';
                form.duration_minutes.value = '';
            });
        });
    });

    document.querySelectorAll('.js-review-edit').forEach((btn) => {
        btn.addEventListener('click', () => {
            openForm('Sửa ôn tập kiến thức', 'review-form-tpl', (form) => {
                form.action = btn.dataset.updateUrl;
                form.review_id.value = btn.dataset.id;
                form.title.value = btn.dataset.title || '';
                form.description.value = btn.dataset.description || '';
                form.duration_minutes.value = btn.dataset.duration || '';
            });
        });
    });
    document.querySelectorAll('.pw-chunk-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pw-chunk-tab').forEach(b => {
                b.style.background = '#f8fafc';
                b.style.color = '#475569';
                b.style.borderColor = '#e2e8f0';
            });
            btn.style.background = '#9a4700';
            btn.style.color = '#fff';
            btn.style.borderColor = 'transparent';

            const chunk = btn.dataset.chunk;
            const rows = document.querySelectorAll('#session-list .pw-session');
            rows.forEach(row => {
                if (chunk === 'all') {
                    row.style.display = '';
                } else {
                    const idx = parseInt(row.dataset.index, 10);
                    const c = parseInt(chunk, 10);
                    const start = c * 9;
                    const end = start + 9;
                    row.style.display = (idx >= start && idx < end) ? '' : 'none';
                }
            });
        });
    });
</script>
@endpush
