@extends('layouts.app', ['active' => 'rules'])

@php
    $rules = $rules ?? null;
    $canEdit = (bool) ($canEdit ?? false);
    $preview = (bool) ($preview ?? false);
    $editor = $rules?->editorName() ?? '';
    $updated = $rules?->updated_at;
@endphp

@section('title', $preview ? 'Xem trước nội quy' : (string) ($rules?->title ?: 'Nội quy'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/school-rules.css') }}">
@endpush

@section('content')
<div class="op-stack sr-page">
    <x-page-title :kicker="$preview ? 'Xem trước' : 'Thông tin'" icon="fa-scroll" :title="$rules?->title ?: 'Nội quy'" lead="Quy định và nguyên tắc cần tuân thủ trong quá trình học tại trung tâm.">
        @if ($canEdit)
            <x-slot:aside>
                <a class="op-btn" href="{{ route('school-rules.edit') }}"><i class="fa-solid fa-pen"></i> Chỉnh sửa</a>
            </x-slot:aside>
        @endif
    </x-page-title>

    <article class="op-panel sr-document">
        <div class="op-panel-body">
            @if ($editor !== '' || $updated)
                <div class="sr-meta">
                    @if ($editor !== '')
                        <span><i class="fa-solid fa-user-pen"></i> Cập nhật bởi {{ $editor }}</span>
                    @endif
                    @if ($updated)
                        <span><i class="fa-regular fa-clock"></i> {{ $updated->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
            @endif

            @if (\App\Support\HtmlContent::has($rules?->content))
                <div class="sr-rich">
                    {!! \App\Support\HtmlContent::render($rules?->content) !!}
                </div>
            @else
                <div class="sr-empty">
                    <i class="fa-solid fa-book"></i>
                    <h2>Chưa có nội quy</h2>
                    <p>Giáo viên hoặc quản trị viên chưa cập nhật nội dung nội quy.</p>
                </div>
            @endif
        </div>
    </article>
</div>
@endsection
