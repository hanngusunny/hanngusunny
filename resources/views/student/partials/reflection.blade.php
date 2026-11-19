@php
    $rating = (int) ($reflection['session_rating'] ?? 0);
    $comment = (string) ($reflection['student_comment'] ?? '');
    $labels = [1 => 'Chưa hài lòng', 2 => 'Tạm được', 3 => 'Bình thường', 4 => 'Hài lòng', 5 => 'Rất hài lòng'];
    $hasReflection = $rating > 0 || trim($comment) !== '';
@endphp

<article class="session-post-card session-post-card--reflection" id="session-reflection">
    <header class="session-post-card-head">
        <span class="session-post-card-icon session-post-card-icon--amber"><i class="bi bi-star"></i></span>
        <div class="session-post-card-title">
            <h3>Đánh giá & cảm nhận</h3>
            <p>Chia sẻ mức độ hài lòng và cảm nhận sau buổi học</p>
        </div>
        @if ($hasReflection)
            <span class="session-post-reflection-saved"><i class="bi bi-check-circle-fill"></i> Đã lưu</span>
        @endif
    </header>
    <form method="post" action="{{ route('student.session.reflection', $publicCode) }}" class="session-reflection-form">
        @csrf
        @include('pwa.partials.return-field')
        <div class="session-reflection-rating-box">
            <label class="session-reflection-rating-title">Mức độ hài lòng với buổi học</label>
            <div class="session-rating-stars" role="radiogroup" aria-label="Đánh giá buổi học từ 1 đến 5 sao">
                @for ($star = 5; $star >= 1; $star--)
                    <label class="session-rating-star" data-rating-label="{{ $labels[$star] }}">
                        <input type="radio" name="session_rating" value="{{ $star }}" @checked($rating === $star)>
                        <i class="bi bi-star-fill" aria-hidden="true"></i>
                        <span class="visually-hidden">{{ $star }} sao</span>
                    </label>
                @endfor
            </div>
            <p class="session-rating-caption" data-rating-caption>
                {{ $rating > 0 ? ($labels[$rating] ?? '') : 'Chọn số sao để đánh giá buổi học' }}
            </p>
        </div>
        <div class="session-reflection-comment">
            <label for="student-comment">Cảm nhận / tổng kết của bạn</label>
            <textarea
                class="form-control session-reflection-textarea"
                id="student-comment"
                name="student_comment"
                rows="5"
                placeholder="Buổi học hôm nay bạn học được gì? Phần nào còn khó? Mục tiêu cho buổi sau..."
            >{{ $comment }}</textarea>
        </div>
        <div class="session-post-card-foot">
            <x-sunny.button type="submit" icon="bi bi-save" class="session-post-submit">Lưu đánh giá</x-sunny.button>
        </div>
    </form>
</article>
<script>
(() => {
    const form = document.querySelector(".session-reflection-form");
    if (!form) return;
    const caption = form.querySelector("[data-rating-caption]");
    const stars = form.querySelectorAll(".session-rating-star input");
    const updateCaption = () => {
        const checked = form.querySelector(".session-rating-star input:checked");
        if (!caption) return;
        if (!checked) {
            caption.textContent = "Chọn số sao để đánh giá buổi học";
            return;
        }
        caption.textContent = checked.closest(".session-rating-star")?.dataset.ratingLabel || "";
    };
    stars.forEach((input) => input.addEventListener("change", updateCaption));
    updateCaption();
})();
</script>
