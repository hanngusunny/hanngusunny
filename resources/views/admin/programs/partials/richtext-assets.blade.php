@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/program-richtext.css') }}">
@endpush

@push('scripts')
    <script>
        document.body.dataset.tinymceBase = @json(asset('assets/tinymce'));
        document.body.dataset.appBase = @json(rtrim(url('/'), '/'));
        @isset($pairUploadUrl)
            document.body.dataset.pairImageUploadUrl = @json($pairUploadUrl);
        @endisset
        @isset($videoUploadUrl)
            document.body.dataset.preVideoUploadUrl = @json($videoUploadUrl);
        @endisset
    </script>
    <script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('assets/js/program-pair-image-editor.js') }}"></script>
    <script src="{{ asset('assets/js/program-pre-video.js') }}"></script>
    <script src="{{ asset('assets/js/program-richtext.js') }}"></script>
@endpush
