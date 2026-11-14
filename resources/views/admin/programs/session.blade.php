@extends('layouts.app', ['active' => 'programs'])

@php
    $tabs = [
        'info' => ['label' => 'Thông tin', 'icon' => 'fa-circle-info', 'count' => null],
        'pre' => ['label' => 'Trước buổi', 'icon' => 'fa-book-open', 'count' => count($grouped['pre'] ?? [])],
        'review' => ['label' => 'Kiểm tra bài cũ', 'icon' => 'fa-rotate-left', 'count' => count($grouped['review'] ?? [])],
        'quiz' => ['label' => 'Trắc nghiệm', 'icon' => 'fa-list-check', 'count' => count($grouped['quiz'] ?? [])],
        'post_practice' => ['label' => 'Ôn tập sau buổi', 'icon' => 'fa-lightbulb', 'count' => count($grouped['post_practice'] ?? [])],
    ];
    $lead = trim(strip_tags((string) $session->description));
    $questionTotal = collect($grouped ?? [])->sum(fn ($rows) => count($rows));
@endphp

@section('title', $session->title)

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/program-workspace.css') }}?v=4">
@endpush

@section('subnav')
    <a href="{{ route('admin.programs.index') }}" class="os-tab">Danh sách</a>
    <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels', 'level' => $session->level_id]) }}" class="os-tab">Trình độ &amp; buổi</a>
    <a href="{{ route('admin.programs.sessions.show', [$program->id, $session->id]) }}" class="os-tab is-on">Soạn buổi<span class="pw-tab-count">{{ $sessionNo }}</span></a>
@endsection

@section('content')
<div class="pw-page">
    <header class="pw-hero">
        <div class="pw-hero-top">
            <div>
                <p class="pw-kicker"><i class="fa-solid fa-chalkboard"></i> {{ $program->title }} · {{ $session->level?->title ?: 'Buổi học' }}</p>
                <h1>{{ $session->title }}</h1>
                <p class="pw-lead">{{ $lead !== '' ? $lead : 'Soạn tài liệu, video và câu hỏi cho buổi học này.' }}</p>
                <div class="pw-meta">
                    <span><i class="fa-solid fa-hashtag"></i>Buổi {{ $sessionNo }}</span>
                    <span><i class="fa-solid fa-circle-question"></i>{{ $questionTotal }} câu hỏi</span>
                    @if ($program->isArchived())
                        <span><i class="fa-solid fa-box-archive"></i>Chỉ xem</span>
                    @endif
                </div>
            </div>
            <div class="pw-session-nav">
                <a class="pw-nav-btn{{ $prevSession ? '' : ' is-off' }}" href="{{ $prevSession ? route('admin.programs.sessions.show', [$program->id, $prevSession->id, 'tab' => $tab]) : '#' }}" title="Buổi trước">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <span class="pw-session-nav-pos">Buổi {{ $sessionNo }}</span>
                <a class="pw-nav-btn{{ $nextSession ? '' : ' is-off' }}" href="{{ $nextSession ? route('admin.programs.sessions.show', [$program->id, $nextSession->id, 'tab' => $tab]) : '#' }}" title="Buổi sau">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div class="pw-stats is-5">
            @foreach ($tabs as $key => $meta)
                <a class="pw-stat{{ $tab === $key ? ' is-on' : '' }}" href="{{ route('admin.programs.sessions.show', [$program->id, $session->id, 'tab' => $key]) }}">
                    <span class="pw-stat-ico"><i class="fa-solid {{ $meta['icon'] }}"></i></span>
                    <span>
                        <strong>{{ $meta['count'] === null ? 'Soạn' : $meta['count'] }}</strong>
                        <small>{{ $meta['label'] }}</small>
                    </span>
                </a>
            @endforeach
        </div>
    </header>

    @if ($tab === 'info')
        <form class="pw-session-form" method="post" action="{{ route('admin.programs.sessions.update', [$program->id, $session->id]) }}" enctype="multipart/form-data">
            @csrf

            <section class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>Thông tin buổi học</h2>
                        <p>Tên hiển thị cho giáo viên và học viên khi vào buổi.</p>
                    </div>
                </div>
                <div class="pw-form">
                    <div class="pw-field">
                        <div class="pw-field-top">
                            <label for="session-title"><i class="fa-solid fa-signature"></i> Tên buổi học</label>
                            <em class="pw-req">Bắt buộc</em>
                        </div>
                        <div class="pw-control">
                            <input id="session-title" name="title" required maxlength="255" value="{{ old('title', $session->title) }}" placeholder="VD: Buổi 1 — Chào hỏi" @disabled($program->isArchived())>
                        </div>
                    </div>
                    <div class="pw-field">
                        <div class="pw-field-top">
                            <label for="session-description"><i class="fa-solid fa-align-left"></i> Mô tả ngắn</label>
                        </div>
                        <div class="pw-control">
                            <textarea id="session-description" name="description" rows="4" placeholder="Mục tiêu buổi học, ghi chú soạn bài..." @disabled($program->isArchived())>{{ old('description', $session->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </section>

            <section class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>Trước buổi</h2>
                        <p>Tài liệu và video học viên xem trước khi đến lớp.</p>
                    </div>
                </div>
                <div class="pw-form">
                    <div class="rt-field">
                        <label class="rt-label" for="pre_session_materials"><i class="fa-solid fa-book-open"></i> Tài liệu trước buổi</label>
                        <p class="rt-hint">Soạn như Word: tiêu đề, danh sách, ảnh, video, bảng, nhúng Google Drive.</p>
                        <textarea id="pre_session_materials" name="pre_session_materials" rows="8" class="js-richtext" data-editor-height="380" @disabled($program->isArchived())>{{ old('pre_session_materials', $session->pre_session_materials) }}</textarea>
                    </div>
                    <div class="pw-field">
                        <div class="pw-field-top">
                            <label for="pre_session_video_url"><i class="fa-solid fa-play"></i> Link video trước buổi</label>
                        </div>
                        <div class="pw-control">
                            <input id="pre_session_video_url" name="pre_session_video_url" value="{{ old('pre_session_video_url', $session->pre_session_video_url) }}" placeholder="Dán link YouTube, Drive hoặc đường dẫn đã tải lên" @disabled($program->isArchived())>
                        </div>
                    </div>
                    @unless ($program->isArchived())
                        <div class="pw-field">
                            <div class="pw-field-top">
                                <label><i class="fa-solid fa-cloud-arrow-up"></i> Tải video lên máy chủ</label>
                            </div>
                            <label class="pw-file">
                                <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime,.m4v,.ogg" class="js-pre-video" data-upload-url="{{ route('admin.programs.sessions.upload.video', [$program->id, $session->id]) }}">
                                <span class="pw-file-box">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <strong>Chọn file video</strong>
                                    <small>MP4, WEBM, MOV · tối đa 200MB — sau khi tải xong, link sẽ điền vào ô phía trên.</small>
                                </span>
                            </label>
                        </div>
                    @endunless
                </div>
            </section>

            <section class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>Trong buổi</h2>
                        <p>Nội dung giáo viên dùng khi dạy trên lớp.</p>
                    </div>
                </div>
                <div class="pw-form">
                    <div class="rt-field">
                        <label class="rt-label" for="session_materials"><i class="fa-solid fa-chalkboard-user"></i> Tài liệu trong buổi</label>
                        <textarea id="session_materials" name="materials" rows="6" class="js-richtext" data-editor-height="300" @disabled($program->isArchived())>{{ old('materials', $session->materials) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>Sau buổi</h2>
                        <p>Tóm tắt và ôn tập kiến thức gắn với buổi này.</p>
                    </div>
                </div>
                <div class="pw-form">
                    <div class="rt-field">
                        <label class="rt-label" for="session_summary"><i class="fa-solid fa-clipboard-list"></i> Tóm tắt buổi</label>
                        <textarea id="session_summary" name="session_summary" rows="5" class="js-richtext" data-editor-height="240" @disabled($program->isArchived())>{{ old('session_summary', $session->session_summary) }}</textarea>
                    </div>
                    <div class="pw-field">
                        <div class="pw-field-top">
                            <label for="program_knowledge_review_id"><i class="fa-solid fa-lightbulb"></i> Gắn ôn tập kiến thức</label>
                        </div>
                        <div class="pw-control">
                            <select id="program_knowledge_review_id" name="program_knowledge_review_id" @disabled($program->isArchived())>
                                <option value="">— Không gắn —</option>
                                @foreach ($program->knowledgeReviews as $review)
                                    <option value="{{ $review->id }}" @selected((int) old('program_knowledge_review_id', $session->program_knowledge_review_id) === (int) $review->id)>{{ $review->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            @unless ($program->isArchived())
                <div class="pw-savebar">
                    <p>Lưu để đồng bộ nội dung buổi sang các khóa đang dùng chương trình này.</p>
                    <button type="submit" class="op-btn"><i class="fa-solid fa-floppy-disk"></i> Lưu buổi học</button>
                </div>
            @endunless
        </form>
    @else
        @include('admin.programs.partials.question-group', [
            'questions' => $grouped[$tab] ?? [],
            'group' => $tab,
            'createBase' => route('admin.programs.sessions.questions.create', [$program->id, $session->id, \App\Support\Questions\QuestionParser::GROUP_SLUGS[$tab] ?? $tab]),
        ])
    @endif
</div>
@endsection

@if ($tab === 'info')
    @include('admin.programs.partials.richtext-assets', [
        'pairUploadUrl' => route('admin.programs.sessions.upload.image', [$program->id, $session->id]),
        'videoUploadUrl' => route('admin.programs.sessions.upload.video', [$program->id, $session->id]),
    ])
@endif

@push('scripts')
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const videoInput = document.querySelector('.js-pre-video');
    videoInput?.addEventListener('change', async () => {
        if (!videoInput.files?.[0]) return;
        const body = new FormData();
        body.append('video', videoInput.files[0]);
        const res = await fetch(videoInput.dataset.uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body });
        const data = await res.json();
        if (data.ok && data.path) {
            const input = document.querySelector('input[name="pre_session_video_url"]');
            if (input) input.value = data.path;
            TemplateUI.toast('success', 'Đã tải video.');
        } else {
            TemplateUI.toast('error', data.message || 'Không tải được video.');
        }
    });
</script>
@endpush
