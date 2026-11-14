<link rel="stylesheet" href="{{ asset('assets/css/session-quiz.css') }}?v=4">
<link rel="stylesheet" href="{{ asset('assets/css/student-quiz-office.css') }}?v=4">
<script>
    document.body.dataset.appBase = @json(rtrim(url('/'), '/'));
    document.body.dataset.pairImageUploadUrl = @json(route('student.media.image'));
    document.body.dataset.phatAmUploadUrl = @json(route('student.media.recording'));
    document.body.dataset.tinymceBase = @json(asset('assets/tinymce'));
</script>
<script src="{{ asset('assets/js/session-memory-match.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-word-link.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-word-order.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-listening.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-listening-true-false.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-listening-multi-choice.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-listening-answer.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-category-sort.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-fill-blanks.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-word-select.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-word-translate.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-phat-am.js') }}?v=5"></script>
<script src="{{ asset('assets/js/session-phat-am-answer.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-image-hotspot.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-image-label-drop.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-multi-select.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-quiz-submit-guard.js') }}?v=3"></script>
<script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('assets/js/program-pair-image-editor.js') }}?v=2"></script>
<script src="{{ asset('assets/js/session-quiz-essay-editor.js') }}?v=2"></script>
<script src="{{ asset('assets/js/student-quiz.js') }}?v=2"></script>
<script src="{{ asset('assets/js/quiz-answer-autosave.js') }}?v=3"></script>
<script src="{{ asset('assets/js/session-knowledge-review-timer.js') }}?v=3"></script>
