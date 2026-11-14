<section class="pw-panel">
    <div class="pw-panel-head">
        <div>
            <h2>Bài kiểm tra</h2>
            <p>{{ $program->assessments->count() }} bài · kéo để đổi thứ tự, bấm tên bài để soạn câu hỏi.</p>
        </div>
        @unless ($program->isArchived())
            <div class="pw-panel-tools">
                <button type="button" class="op-btn js-assessment-create"><i class="fa-solid fa-plus"></i> Thêm bài</button>
            </div>
        @endunless
    </div>
    <div id="assessment-list" class="pw-item-list" data-reorder-url="{{ route('admin.programs.assessments.reorder', $program->id) }}">
        @forelse ($program->assessments as $assessment)
            <article class="pw-item js-assessment-row" data-id="{{ $assessment->id }}" draggable="{{ $program->isActive() ? 'true' : 'false' }}">
                <span class="js-drag pw-grip" title="Kéo để sắp xếp">
                    @unless ($program->isArchived())
                        <i class="fa-solid fa-grip-vertical"></i>
                    @endunless
                </span>
                <span class="pw-item-ico"><i class="fa-solid fa-file-pen"></i></span>
                <a href="{{ route('admin.programs.assessments.show', [$program->id, $assessment->id]) }}" class="pw-item-body">
                    <strong>{{ $assessment->title }}</strong>
                    @if ($assessment->description)
                        <small>{{ \App\Support\HtmlContent::plain($assessment->description) }}</small>
                    @endif
                    <div class="pw-pills">
                        <span class="pw-chip">{{ $assessment->durationLabel() }}</span>
                        <span class="pw-chip{{ $assessment->questions->count() > 0 ? ' is-ready' : '' }}">{{ $assessment->questions->count() }} câu hỏi</span>
                    </div>
                </a>
                <div class="pw-actions">
                    <a href="{{ route('admin.programs.assessments.show', [$program->id, $assessment->id]) }}" class="pw-link-btn">Soạn</a>
                    @unless ($program->isArchived())
                        <button type="button" class="pw-icon-btn js-assessment-edit" title="Sửa bài"
                            data-id="{{ $assessment->id }}"
                            data-title="{{ $assessment->title }}"
                            data-description="{{ $assessment->description }}"
                            data-duration="{{ $assessment->duration_minutes }}"
                            data-update-url="{{ route('admin.programs.assessments.update', [$program->id, $assessment->id]) }}">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="post" action="{{ route('admin.programs.assessments.destroy', [$program->id, $assessment->id]) }}" onsubmit="return confirm('Xóa bài kiểm tra này?');">
                            @csrf
                            <button class="pw-icon-btn is-danger" title="Xóa bài"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    @endunless
                </div>
            </article>
        @empty
            <div class="pw-empty">
                <span><i class="fa-solid fa-file-pen"></i></span>
                <p>Chưa có bài kiểm tra trong chương trình này.</p>
                @unless ($program->isArchived())
                    <button type="button" class="op-btn js-assessment-create"><i class="fa-solid fa-plus"></i> Thêm bài đầu tiên</button>
                @endunless
            </div>
        @endforelse
    </div>
</section>
