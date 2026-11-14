@extends('layouts.app', ['active' => 'programs'])

@php
    $tab = $tab ?? 'info';
    $reviewNo = $reviewNo ?? 1;
    $prevReview = $prevReview ?? null;
    $nextReview = $nextReview ?? null;
    $questionCount = $review->questions->count();
    $lead = \App\Support\HtmlContent::plain((string) $review->description);
    $tabs = [
        'info' => ['label' => 'Thông tin bài ôn', 'icon' => 'fa-circle-info', 'count' => null],
        'questions' => ['label' => 'Câu hỏi', 'icon' => 'fa-list-check', 'count' => $questionCount],
    ];
@endphp

@section('title', $review->title)

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/program-workspace.css') }}?v=6">
@endpush

@section('subnav')
    <a href="{{ route('admin.programs.index') }}" class="os-tab">Danh sách</a>
    <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'reviews']) }}" class="os-tab">Ôn tập KT</a>
    <a href="{{ route('admin.programs.reviews.show', [$program->id, $review->id]) }}" class="os-tab is-on">Soạn ôn tập<span class="pw-tab-count">{{ $reviewNo }}</span></a>
@endsection

@section('content')
<div class="pw-page">
    <header class="pw-hero">
        <div class="pw-hero-top">
            <div>
                <p class="pw-kicker"><i class="fa-solid fa-book-open"></i> {{ $program->title }} · Ôn tập kiến thức</p>
                <h1>{{ $review->title }}</h1>
                <p class="pw-lead">{{ $lead !== '' ? $lead : 'Soạn hướng dẫn và câu hỏi ôn tập cho chương trình này.' }}</p>
                <div class="pw-meta">
                    <span><i class="fa-solid fa-hashtag"></i>Bài {{ $reviewNo }}</span>
                    <span><i class="fa-solid fa-clock"></i>{{ $review->durationLabel() }}</span>
                    <span><i class="fa-solid fa-circle-question"></i>{{ $questionCount }} câu hỏi</span>
                    @if ($program->isArchived())
                        <span><i class="fa-solid fa-box-archive"></i>Chỉ xem</span>
                    @endif
                </div>
            </div>
            <div class="pw-session-nav">
                <a class="pw-nav-btn{{ $prevReview ? '' : ' is-off' }}" href="{{ $prevReview ? route('admin.programs.reviews.show', [$program->id, $prevReview->id, 'tab' => $tab]) : '#' }}" title="Bài trước">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <span class="pw-session-nav-pos">Bài {{ $reviewNo }}</span>
                <a class="pw-nav-btn{{ $nextReview ? '' : ' is-off' }}" href="{{ $nextReview ? route('admin.programs.reviews.show', [$program->id, $nextReview->id, 'tab' => $tab]) : '#' }}" title="Bài sau">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div class="pw-stats is-2">
            @foreach ($tabs as $key => $meta)
                <a class="pw-stat{{ $tab === $key ? ' is-on' : '' }}" href="{{ route('admin.programs.reviews.show', [$program->id, $review->id, 'tab' => $key]) }}">
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
        <form class="pw-session-form" method="post" action="{{ route('admin.programs.reviews.update', [$program->id, $review->id]) }}">
            @csrf

            <section class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>Thông tin bài ôn</h2>
                        <p>Tên và hướng dẫn học viên thấy trước khi làm bài.</p>
                    </div>
                </div>
                <div class="pw-form">
                    <div class="pw-field">
                        <div class="pw-field-top">
                            <label for="review-title"><i class="fa-solid fa-signature"></i> Tên ôn tập</label>
                            <em class="pw-req">Bắt buộc</em>
                        </div>
                        <div class="pw-control">
                            <input id="review-title" name="title" required maxlength="255" value="{{ old('title', $review->title) }}" placeholder="VD: Ôn tập tuần 4" @disabled($program->isArchived())>
                        </div>
                    </div>
                    <div class="rt-field">
                        <label class="rt-label" for="review-description"><i class="fa-solid fa-align-left"></i> Mô tả / hướng dẫn</label>
                        <p class="rt-hint">Ghi chú, yêu cầu, lưu ý trước khi học viên bắt đầu làm bài.</p>
                        <textarea id="review-description" name="description" rows="6" class="js-richtext" data-editor-height="280" data-editor-compact="1" @disabled($program->isArchived())>{{ old('description', $review->description) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="pw-panel">
                <div class="pw-panel-head">
                    <div>
                        <h2>Thời gian làm bài</h2>
                        <p>Để trống nếu không giới hạn thời lượng.</p>
                    </div>
                </div>
                <div class="pw-form">
                    <div class="pw-field">
                        <div class="pw-field-top">
                            <label for="review-duration"><i class="fa-solid fa-clock"></i> Thời lượng (phút)</label>
                        </div>
                        <div class="pw-control">
                            <input id="review-duration" type="number" name="duration_minutes" min="1" max="600" value="{{ old('duration_minutes', $review->duration_minutes) }}" placeholder="Để trống nếu không giới hạn" @disabled($program->isArchived())>
                        </div>
                        <span class="hint">Học viên thấy đồng hồ đếm ngược khi có thời lượng.</span>
                    </div>
                </div>
            </section>

            @unless ($program->isArchived())
                <div class="pw-savebar">
                    <p>Lưu để cập nhật tên, hướng dẫn và thời lượng cho các khóa đang dùng chương trình này.</p>
                    <button type="submit" class="op-btn"><i class="fa-solid fa-floppy-disk"></i> Lưu bài ôn</button>
                </div>
            @endunless
        </form>
    @else
        @include('admin.programs.partials.question-group', [
            'questions' => $review->questions,
            'group' => 'post_practice',
            'groups' => ['post_practice' => 'Câu hỏi ôn tập'],
            'createBase' => route('admin.programs.reviews.questions.create', [$program->id, $review->id]),
            'reorderUrl' => route('admin.programs.reviews.questions.reorder', [$program->id, $review->id]),
            'editRoute' => fn ($question) => route('admin.programs.reviews.questions.edit', [$program->id, $review->id, $question->id]),
            'session' => null,
        ])
    @endif
</div>
@endsection

@if ($tab === 'info')
    @include('admin.programs.partials.richtext-assets', [
        'pairUploadUrl' => route('admin.programs.reviews.upload.image', [$program->id, $review->id]),
    ])
@endif
