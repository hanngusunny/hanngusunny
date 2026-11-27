@extends('layouts.app', ['active' => 'rules'])

@php
    $rules = $rules ?? null;
    $canEdit = (bool) ($canEdit ?? false);
    $preview = (bool) ($preview ?? false);
    $editor = trim((string) ($rules?->editorName() ?? ''));
    $updated = $rules?->updated_at;
    $rulesTitle = trim((string) ($rules?->title ?? '')) ?: 'Nội quy lớp học';
    $subtitle = $preview
        ? 'Chế độ xem trước · Hiển thị như học viên'
        : 'Áp dụng cho các lớp trực tuyến · Quy định chung của Hàn ngữ SUNNY';

    $metaParts = [];
    if ($editor !== '') {
        $metaParts[] = 'Cập nhật bởi '.$editor;
    } elseif ($updated) {
        $metaParts[] = 'Cập nhật lần cuối';
    }
    if ($updated) {
        $metaParts[] = $updated->format('H:i');
        $metaParts[] = $updated->format('d/m/Y');
    }
    $metaLine = implode(' · ', $metaParts);
    $badgeLabel = $preview ? 'Xem trước' : 'Áp dụng hiện hành';
@endphp

@section('title', $preview ? 'Xem trước nội quy' : $rulesTitle)

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/school-rules.css') }}?v=2">
@endpush

@section('content')
<div class="sn-rules-page">
    <section class="sn-rules-surface">
        {{-- Nhận diện tài liệu chuẩn Figma (#406:28919) --}}
        <header class="sn-rules-head">
            <div class="sn-rules-head-main">
                <span class="sn-rules-icon-circle" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12.37 8.87891H17.62" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6.38 8.87891L7.13 9.62891L9.38 7.37891" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12.37 15.8789H17.62" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6.38 15.8789L7.13 16.6289L9.38 14.3789" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 22H15C20 22 22 20 22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div class="sn-rules-head-copy">
                    <h1 class="sn-rules-title">{{ $rulesTitle }}</h1>
                    <p class="sn-rules-sub">{{ $subtitle }}</p>
                </div>
            </div>

            @if ($canEdit)
                <a class="sn-rules-btn-primary" href="{{ route('school-rules.edit') }}">Chỉnh sửa</a>
            @endif
        </header>

        {{-- Khu vực tài liệu 2 lớp chuẩn Figma (#406:28925 → #406:28926) --}}
        <div class="sn-rules-well">
            <article class="sn-rules-doc">
                @if ($metaLine !== '')
                    <div class="sn-rules-meta">
                        <span class="sn-rules-meta-text">{{ $metaLine }}</span>
                        <span class="sn-rules-version-pill">{{ $badgeLabel }}</span>
                    </div>
                @endif

                @if (\App\Support\HtmlContent::has($rules?->content))
                    <div class="sn-rules-rich">
                        {!! \App\Support\HtmlContent::render($rules?->content) !!}
                    </div>
                @else
                    <div class="sn-rules-empty">
                        <div class="sn-rules-empty-ico" aria-hidden="true">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                                <circle cx="40" cy="40" r="40" fill="#EFF5FD"/>
                                <path d="M27 25C27 22.8 28.8 21 31 21H45L53 29V55C53 57.2 51.2 59 49 59H31C28.8 59 27 57.2 27 55V25Z" fill="#FFFFFF"/>
                                <path d="M33 36H47M33 43H47M33 50H42" stroke="#0D99FF" stroke-width="2.4" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h2 class="sn-rules-empty-title">Chưa có nội quy</h2>
                        <p class="sn-rules-empty-desc">Giáo viên hoặc quản trị viên chưa cập nhật nội dung nội quy.</p>
                    </div>
                @endif
            </article>
        </div>
    </section>
</div>
@endsection
