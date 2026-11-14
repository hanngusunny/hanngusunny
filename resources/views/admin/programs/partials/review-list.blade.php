<section class="pw-panel">
    <div class="pw-panel-head">
        <div>
            <h2>Ôn tập kiến thức</h2>
            <p>{{ $program->knowledgeReviews->count() }} đề · kéo để đổi thứ tự, bấm tên đề để soạn câu hỏi.</p>
        </div>
        @unless ($program->isArchived())
            <div class="pw-panel-tools">
                <button type="button" class="op-btn js-review-create"><i class="fa-solid fa-plus"></i> Thêm ôn tập</button>
            </div>
        @endunless
    </div>
    <div id="review-list" class="pw-item-list" data-reorder-url="{{ route('admin.programs.reviews.reorder', $program->id) }}">
        @forelse ($program->knowledgeReviews as $review)
            <article class="pw-item js-review-row" data-id="{{ $review->id }}" draggable="{{ $program->isActive() ? 'true' : 'false' }}">
                <span class="js-drag pw-grip" title="Kéo để sắp xếp">
                    @unless ($program->isArchived())
                        <i class="fa-solid fa-grip-vertical"></i>
                    @endunless
                </span>
                <span class="pw-item-ico"><i class="fa-solid fa-book-open"></i></span>
                <a href="{{ route('admin.programs.reviews.show', [$program->id, $review->id]) }}" class="pw-item-body">
                    <strong>{{ $review->title }}</strong>
                    @if ($review->description)
                        <small>{{ \App\Support\HtmlContent::plain($review->description) }}</small>
                    @endif
                    <div class="pw-pills">
                        <span class="pw-chip">{{ $review->durationLabel() }}</span>
                        <span class="pw-chip{{ $review->questions->count() > 0 ? ' is-ready' : '' }}">{{ $review->questions->count() }} câu hỏi</span>
                    </div>
                </a>
                <div class="pw-actions">
                    <a href="{{ route('admin.programs.reviews.show', [$program->id, $review->id]) }}" class="pw-link-btn">Soạn</a>
                    @unless ($program->isArchived())
                        <button type="button" class="pw-icon-btn js-review-edit" title="Sửa ôn tập"
                            data-id="{{ $review->id }}"
                            data-title="{{ $review->title }}"
                            data-description="{{ $review->description }}"
                            data-duration="{{ $review->duration_minutes }}"
                            data-update-url="{{ route('admin.programs.reviews.update', [$program->id, $review->id]) }}">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="post" action="{{ route('admin.programs.reviews.destroy', [$program->id, $review->id]) }}" onsubmit="return confirm('Xóa ôn tập này?');">
                            @csrf
                            <button class="pw-icon-btn is-danger" title="Xóa ôn tập"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    @endunless
                </div>
            </article>
        @empty
            <div class="pw-empty">
                <span><i class="fa-solid fa-book-open"></i></span>
                <p>Chưa có ôn tập kiến thức trong chương trình này.</p>
                @unless ($program->isArchived())
                    <button type="button" class="op-btn js-review-create"><i class="fa-solid fa-plus"></i> Thêm ôn tập đầu tiên</button>
                @endunless
            </div>
        @endforelse
    </div>
</section>
