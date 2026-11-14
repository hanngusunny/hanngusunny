@extends('layouts.app', ['active' => 'rules'])

@php
    $rules = $rules ?? null;
    $editor = $rules?->editorName() ?? '';
    $updated = $rules?->updated_at;
@endphp

@section('title', 'Chỉnh sửa nội quy')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/school-rules.css') }}">
@endpush

@section('content')
<div class="op-stack sr-page">
    <x-page-title kicker="Quản lý" icon="fa-scroll" title="Chỉnh sửa nội quy" lead="Cập nhật nội quy chung — học viên sẽ đọc bản mới nhất trên hệ thống.">
        <x-slot:aside>
            <a class="op-btn-ghost" href="{{ route('school-rules.preview') }}"><i class="fa-solid fa-eye"></i> Xem như học viên</a>
        </x-slot:aside>
    </x-page-title>

    <div class="sr-edit-grid">
        <aside class="op-panel">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-circle-info"></i></span>
                <span class="op-panel-title">Gợi ý</span>
            </div>
            <div class="op-panel-body">
                @if ($editor !== '' || $updated)
                    <p class="sr-aside-meta">
                        @if ($editor !== '') Sửa bởi {{ $editor }}@endif
                        @if ($updated) · {{ $updated->format('d/m/Y H:i') }}@endif
                    </p>
                @endif
                <ul class="sr-tips">
                    <li>Trình bày rõ ràng theo mục hoặc điều khoản.</li>
                    <li>Nêu quy định về giờ học, bài tập, thi cử, ứng xử.</li>
                    <li>Sau khi lưu, học viên thấy ngay bản cập nhật.</li>
                </ul>
            </div>
        </aside>

        <section class="op-panel">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-pen"></i></span>
                <span class="op-panel-title">Nội dung nội quy</span>
            </div>
            <div class="op-panel-body">
                @if ($errors->any())
                    <div class="sr-errors">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <form class="rules-form sr-form" method="post" action="{{ route('school-rules.update') }}">
                    @csrf
                    <label class="sr-label" for="title">Tiêu đề <span>*</span></label>
                    <input id="title" name="title" type="text" maxlength="200" required
                        value="{{ old('title', $rules?->title ?: 'Nội quy lớp học') }}"
                        placeholder="Ví dụ: Nội quy lớp học">

                    <label class="sr-label" for="content">Nội dung <span>*</span></label>
                    <textarea class="js-rules-editor" id="content" name="content" rows="16">{{ old('content', $rules?->content) }}</textarea>

                    <div class="sr-actions">
                        <a class="op-btn-ghost" href="{{ route('school-rules.preview') }}"><i class="fa-solid fa-eye"></i> Xem trước</a>
                        <button class="op-btn" type="submit"><i class="fa-solid fa-check"></i> Lưu nội quy</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.body.dataset.tinymceBase = @json(asset('assets/tinymce'));
</script>
<script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('assets/js/rules-form.js') }}"></script>
@endpush
