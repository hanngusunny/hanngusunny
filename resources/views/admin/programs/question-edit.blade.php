@extends('layouts.app', ['active' => 'programs'])

@section('title', 'Soạn câu hỏi')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/program-question.css') }}?v=9">
@endpush

@section('subnav')
    <a href="{{ route('admin.programs.index') }}" class="os-tab">Danh sách</a>
    <a href="{{ $listUrl }}" class="os-tab">{{ $groupLabel }}</a>
    <a href="#" class="os-tab is-on">Câu {{ $questionNo }}</a>
@endsection

@section('content')
@php
    $cloneUrl = null;
    if (!empty($session)) {
        $cloneUrl = route('admin.programs.sessions.questions.clone', [$program->id, $session->id, $question->id]);
    } elseif (!empty($assessment)) {
        $cloneUrl = route('admin.programs.assessments.questions.clone', [$program->id, $assessment->id, $question->id]);
    } elseif (!empty($review)) {
        $cloneUrl = route('admin.programs.reviews.questions.clone', [$program->id, $review->id, $question->id]);
    }
@endphp

<div class="op-stack program-question-page">
    <x-page-title :kicker="$groupLabel" icon="fa-pen" lead="Chọn loại câu, soạn nội dung và đáp án. Thay đổi được lưu tự động.">
        Câu {{ $questionNo }}@if ($questionTotal) <span class="text-slate-400">/ {{ $questionTotal }}</span>@endif
        <x-slot:aside>
            <p id="programQuestionAutosaveStatus" aria-live="polite"></p>
            <div class="program-question-edit-pager" role="group" aria-label="Chuyển câu hỏi">
                @if (!empty($prevQuestionId) && !empty($session))
                    <a class="program-question-edit-pager-btn" href="{{ route('admin.programs.sessions.questions.edit', [$program->id, $session->id, $prevQuestionId]) }}" title="Câu trước"><i class="fa-solid fa-chevron-left"></i></a>
                @elseif (!empty($prevQuestionUrl))
                    <a class="program-question-edit-pager-btn" href="{{ $prevQuestionUrl }}" title="Câu trước"><i class="fa-solid fa-chevron-left"></i></a>
                @else
                    <span class="program-question-edit-pager-btn is-disabled" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>
                @endif
                <span class="program-question-edit-pager-label">
                    Câu {{ $questionNo }}
                    @if ($questionTotal)
                        <span class="program-question-edit-pager-total">/ {{ $questionTotal }}</span>
                    @endif
                </span>
                @if (!empty($nextQuestionId) && !empty($session))
                    <a class="program-question-edit-pager-btn" href="{{ route('admin.programs.sessions.questions.edit', [$program->id, $session->id, $nextQuestionId]) }}" title="Câu sau"><i class="fa-solid fa-chevron-right"></i></a>
                @elseif (!empty($nextQuestionUrl))
                    <a class="program-question-edit-pager-btn" href="{{ $nextQuestionUrl }}" title="Câu sau"><i class="fa-solid fa-chevron-right"></i></a>
                @else
                    <span class="program-question-edit-pager-btn is-disabled" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                @endif
            </div>
        </x-slot:aside>
    </x-page-title>

    @if ($program->isArchived())
        <section class="op-panel"><div class="op-panel-body" style="padding:20px"><p class="text-sm text-slate-500">Chương trình đã lưu trữ — không thể chỉnh sửa câu hỏi.</p></div></section>
    @else
        <form id="programQuestionEditForm" method="post" action="{{ $saveUrl }}" class="course-admin-form program-question-edit-form" data-question-form-mode="edit" data-autosave-url="{{ $autosaveUrl }}" data-prev-question-url="{{ $prevQuestionUrl ?? '' }}" data-next-question-url="{{ $nextQuestionUrl ?? '' }}">
            @csrf
            <section class="op-panel">
                <div class="op-panel-body" style="padding:22px 24px">
                    <div id="questionEditRoot" class="program-question-edit-root">
                        @include('study_programs._session_question', [
                            'question' => $questionRow,
                            'group' => $group,
                            'formScope' => 'question',
                            'standalone' => true,
                            'sessionIndex' => 0,
                            'questionIndex' => 0,
                            'questionNo' => $questionNo,
                            'showPointsField' => $showPointsField ?? false,
                        ])
                    </div>
                </div>
            </section>
        </form>
        <div class="program-question-edit-actions">
            <div style="display: flex; align-items: center; gap: 10px;">
                <form class="program-question-edit-delete-form" method="post" action="{{ $deleteUrl }}" onsubmit="return confirm('Xóa câu hỏi này?');">
                    @csrf
                    <button type="submit" class="btn btn-outline pq-btn-danger"><i class="fa-solid fa-trash"></i> Xóa câu này</button>
                </form>
                @if ($cloneUrl)
                    <form method="post" action="{{ $cloneUrl }}">
                        @csrf
                        <button type="submit" class="btn btn-outline" style="border-color: #cbd5e1; color: #0284c7; padding: 8px 14px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-copy"></i> Nhân bản câu hỏi
                        </button>
                    </form>
                @endif
            </div>
            <div class="program-question-edit-actions-main">
                <a class="op-btn-ghost" href="{{ $listUrl }}"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách câu</a>
                <button class="op-btn" type="submit" form="programQuestionEditForm"><i class="fa-solid fa-floppy-disk"></i> Lưu câu hỏi</button>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.body.dataset.tinymceBase = @json(asset('assets/tinymce'));
    document.body.dataset.appBase = @json(rtrim(url('/'), '/'));
    document.body.dataset.pairImageUploadUrl = @json($pairUploadUrl);
    document.body.dataset.listeningAudioUploadUrl = @json($audioUploadUrl);
    document.body.dataset.preVideoUploadUrl = @json($videoUploadUrl);
</script>
<script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('assets/js/program-pair-image-editor.js') }}"></script>
<script src="{{ asset('assets/js/program-mp3-upload.js') }}"></script>
<script src="{{ asset('assets/js/program-image-hotspot-panel.js') }}"></script>
<script src="{{ asset('assets/js/program-image-label-drop-panel.js') }}"></script>
<script src="{{ asset('assets/js/program-input-helpers.js') }}"></script>
<script src="{{ asset('assets/js/Sortable.min.js') }}"></script>
<script src="{{ asset('assets/js/program-listening-items.js') }}?v=3"></script>
<script src="{{ asset('assets/js/program-phat-am-items.js') }}?v=3"></script>
<script src="{{ asset('assets/js/program-listening-multi-choice.js') }}"></script>
<script src="{{ asset('assets/js/program-question-form.js') }}?v=4"></script>
@endpush
