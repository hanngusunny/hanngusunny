@php
    $typeIcons = [];
    foreach ($questionTypes as $type) {
        $typeIcons[$type['type']] = $type['icon'];
    }
    $typeIcons['essay'] = $typeIcons['essay'] ?? 'fa-pencil';
    $typeIcons['fill'] = $typeIcons['fill'] ?? 'fa-pencil';
@endphp

<section class="pw-panel">
    <div class="pw-panel-head">
        <div>
            <h2>Danh sách câu hỏi</h2>
            <p>{{ $questions->count() }} câu · kéo tay cầm để đổi thứ tự, bấm nội dung để soạn.</p>
        </div>
        @unless ($program->isArchived())
            <div class="pw-panel-tools">
                <details class="pw-type-pop">
                    <summary class="op-btn"><i class="fa-solid fa-plus"></i> Thêm câu</summary>
                    <div class="pw-type-menu">
                        @foreach ($questionTypes as $type)
                            <a href="{{ route('admin.programs.assessments.questions.create', [$program->id, $assessment->id, $type['slug']]) }}">
                                <i class="fa-solid {{ $type['icon'] }}"></i>
                                <span>
                                    <strong>{{ $type['label'] }}</strong>
                                    <small>{{ $type['desc'] }}</small>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </details>
            </div>
        @endunless
    </div>

    <div
        class="js-question-list pw-qlist"
        data-group="post_practice"
        data-reorder-url="{{ route('admin.programs.assessments.questions.reorder', [$program->id, $assessment->id]) }}"
    >
        @forelse ($questions as $index => $question)
            @php
                $preview = \App\Support\Questions\QuestionParser::plainTextFromHtml($question->question_text);
                $isReady = $preview !== '';
                $type = (string) $question->question_type;
                $typeLabel = $typeLabels[$type] ?? $type;
                $typeIcon = $typeIcons[$type] ?? 'fa-circle-question';
                $editUrl = route('admin.programs.assessments.questions.edit', [$program->id, $assessment->id, $question->id]);
                $pointsLabel = $formatPoints((float) ($question->points ?? 1));
            @endphp
            <article
                class="pw-qrow{{ $isReady ? '' : ' is-draft' }}"
                data-id="{{ $question->id }}"
                draggable="{{ $program->isActive() ? 'true' : 'false' }}"
            >
                <span class="js-drag pw-grip pw-qrow-grip" title="Kéo để sắp xếp">
                    @unless ($program->isArchived())
                        <i class="fa-solid fa-grip-vertical"></i>
                    @endunless
                </span>
                <div class="pw-qrow-num" aria-hidden="true">
                    <span>Câu</span>
                    <strong data-q-num>{{ $index + 1 }}</strong>
                </div>
                <a href="{{ $editUrl }}" class="pw-qrow-body">
                    <strong class="pw-qrow-title">{{ $isReady ? $preview : 'Chưa nhập nội dung câu hỏi' }}</strong>
                    <div class="pw-qrow-meta">
                        <span class="pw-qrow-type">
                            <i class="fa-solid {{ $typeIcon }}"></i>
                            {{ $typeLabel }}
                        </span>
                        <span class="pw-chip{{ $isReady ? ' is-ready' : '' }}">{{ $isReady ? 'Đã soạn' : 'Chưa soạn' }}</span>
                    </div>
                </a>
                <div class="pw-qrow-side">
                    <div class="pw-qrow-points">
                        <strong>{{ $pointsLabel }}</strong>
                        <span>điểm</span>
                    </div>
                    <a href="{{ $editUrl }}" class="pw-link-btn">Sửa</a>
                </div>
            </article>
        @empty
            <div class="pw-empty">
                <span><i class="fa-solid fa-circle-question"></i></span>
                <p>Chưa có câu hỏi trong đề này. Thêm câu đầu tiên để bắt đầu soạn.</p>
                @unless ($program->isArchived())
                    <details class="pw-type-pop">
                        <summary class="op-btn"><i class="fa-solid fa-plus"></i> Thêm câu đầu tiên</summary>
                        <div class="pw-type-menu">
                            @foreach ($questionTypes as $type)
                                <a href="{{ route('admin.programs.assessments.questions.create', [$program->id, $assessment->id, $type['slug']]) }}">
                                    <i class="fa-solid {{ $type['icon'] }}"></i>
                                    <span>
                                        <strong>{{ $type['label'] }}</strong>
                                        <small>{{ $type['desc'] }}</small>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endunless
            </div>
        @endforelse
    </div>
</section>

@push('scripts')
<script>
    (function () {
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var csrf = tokenMeta ? tokenMeta.getAttribute('content') : '';

        document.querySelectorAll('.js-question-list').forEach(function (list) {
            var rows = list.querySelectorAll('[draggable="true"]');
            if (rows.length < 2) {
                return;
            }

            var dragged = null;

            function renumber() {
                list.querySelectorAll('[data-q-num]').forEach(function (el, index) {
                    el.textContent = String(index + 1);
                });
            }

            rows.forEach(function (row) {
                row.addEventListener('dragstart', function () {
                    dragged = row;
                    row.classList.add('is-dragging');
                });
                row.addEventListener('dragend', function () {
                    dragged = null;
                    row.classList.remove('is-dragging');
                    list.querySelectorAll('.pw-qrow.is-over').forEach(function (item) {
                        item.classList.remove('is-over');
                    });
                });
                row.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    if (!dragged || dragged === row) {
                        return;
                    }
                    row.classList.add('is-over');
                });
                row.addEventListener('dragleave', function () {
                    row.classList.remove('is-over');
                });
                row.addEventListener('drop', function (event) {
                    event.preventDefault();
                    row.classList.remove('is-over');
                    if (!dragged || dragged === row) {
                        return;
                    }
                    var all = Array.prototype.slice.call(list.querySelectorAll('[draggable="true"]'));
                    if (all.indexOf(dragged) < all.indexOf(row)) {
                        row.after(dragged);
                    } else {
                        row.before(dragged);
                    }
                    renumber();
                    fetch(list.dataset.reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            order: Array.prototype.slice.call(list.querySelectorAll('[draggable="true"]')).map(function (el) {
                                return el.dataset.id;
                            }),
                            group: list.dataset.group
                        })
                    }).then(function (res) {
                        if (!res.ok && window.TemplateUI) {
                            TemplateUI.toast('error', 'Không sắp xếp được.');
                        }
                    });
                });
            });
        });
    })();
</script>
@endpush
