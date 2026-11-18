@extends('layouts.app', ['active' => 'rules'])

@php
    $rules = $rules ?? null;
    $canEdit = (bool) ($canEdit ?? false);
    $preview = (bool) ($preview ?? false);
    $editor = $rules?->editorName() ?? '';
    $updated = $rules?->updated_at;
@endphp

@section('title', $preview ? 'Xem trước nội quy' : (string) ($rules?->title ?: 'Nội quy trung tâm'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/school-rules.css') }}">
    <style>
        .sr-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }
        .sr-card-header {
            padding: 24px 32px;
            background: linear-gradient(180deg, #fbfcfe 0%, #f6f8fc 100%);
            border-bottom: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .sr-card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sr-card-title i {
            color: #9a4700;
        }
        .sr-meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
        }
        .sr-meta-badge i {
            color: #9a4700;
        }
        .sr-content-body {
            padding: 36px 40px;
            color: #334155;
            line-height: 1.8;
            font-size: 15.5px;
        }
        .sr-content-body h1, .sr-content-body h2, .sr-content-body h3 {
            color: #0f172a;
            font-weight: 700;
            margin-top: 1.5em;
            margin-bottom: 0.6em;
        }
        .sr-content-body h2 {
            font-size: 20px;
            border-left: 4px solid #9a4700;
            padding-left: 12px;
        }
        .sr-content-body h3 {
            font-size: 17px;
        }
        .sr-content-body ul, .sr-content-body ol {
            padding-left: 24px;
            margin: 12px 0;
        }
        .sr-content-body li {
            margin-bottom: 6px;
        }
        .sr-content-body p {
            margin-bottom: 12px;
        }
        .sr-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #9a4700;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(154, 71, 0, 0.25);
            border: none;
        }
        .sr-btn-primary:hover {
            background: #7c3800;
            box-shadow: 0 4px 12px rgba(154, 71, 0, 0.35);
            transform: translateY(-1px);
        }
        .sr-empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .sr-empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #fff7ed;
            color: #9a4700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }
    </style>
@endpush

@section('content')
<div class="op-stack sr-page" style="max-width: 960px; margin: 0 auto; padding: 24px 16px;">
    @if (session('success'))
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500;">
            <i class="fa-solid fa-circle-check" style="color: #22c55e; font-size: 18px;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; color: #9a4700; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">
                <i class="fa-solid fa-scroll"></i>
                <span>{{ $preview ? 'Chế độ xem trước' : 'Quy định trung tâm' }}</span>
            </div>
            <h1 style="font-size: 26px; font-weight: 800; color: #0f172a; margin: 0;">{{ $rules?->title ?: 'Nội quy học tập & trung tâm' }}</h1>
            <p style="color: #64748b; font-size: 14px; margin: 4px 0 0 0;">Quy định và nguyên tắc cần tuân thủ trong quá trình học tập tại Hán ngữ Sunny.</p>
        </div>
        <div>
            @if ($canEdit)
                <a class="sr-btn-primary" href="{{ route('school-rules.edit') }}">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Chỉnh sửa nội quy</span>
                </a>
            @endif
        </div>
    </div>

    <article class="sr-card">
        <div class="sr-card-header">
            <h2 class="sr-card-title">
                <i class="fa-solid fa-file-contract"></i>
                <span>{{ $rules?->title ?: 'Nội quy lớp học' }}</span>
            </h2>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                @if ($editor !== '')
                    <span class="sr-meta-badge">
                        <i class="fa-solid fa-user-pen"></i>
                        <span>Cập nhật: <strong>{{ $editor }}</strong></span>
                    </span>
                @endif
                @if ($updated)
                    <span class="sr-meta-badge">
                        <i class="fa-regular fa-clock"></i>
                        <span>{{ $updated->format('d/m/Y H:i') }}</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="sr-content-body">
            @if (\App\Support\HtmlContent::has($rules?->content))
                <div class="sr-rich">
                    {!! \App\Support\HtmlContent::render($rules?->content) !!}
                </div>
            @else
                <div class="sr-empty-state">
                    <div class="sr-empty-icon">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Chưa có nội dung nội quy</h3>
                    <p style="color: #64748b; font-size: 14px; max-width: 440px; margin: 0 auto 20px;">
                        Quản trị viên hoặc giáo viên chưa cập nhật nội dung nội quy. Vui lòng bấm vào nút bên dưới để cập nhật.
                    </p>
                    @if ($canEdit)
                        <a class="sr-btn-primary" href="{{ route('school-rules.edit') }}">
                            <i class="fa-solid fa-plus"></i>
                            <span>Thêm nội dung nội quy ngay</span>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </article>
</div>
@endsection
