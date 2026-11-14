@php
    $createBase = $createBase ?? '#';
    $reorderUrl = $reorderUrl ?? ($session ? route('admin.programs.sessions.questions.reorder', [$program->id, $session->id]) : '#');
    $editRoute = $editRoute ?? null;
    $groupLabel = $groups[$group] ?? 'Câu hỏi';
@endphp

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/program-workspace.css') }}?v=4">
@endpush

<section class="pw-panel">
    <div class="pw-panel-head">
        <div>
            <h2>{{ $groupLabel }}</h2>
            <p>{{ count($questions) }} câu · kéo để đổi thứ tự, bấm tên câu để soạn nội dung.</p>
        </div>
        @unless ($program->isArchived())
            <div class="pw-panel-tools">
                @if (($group ?? '') === 'quiz')
                    <a class="op-btn" href="{{ $createBase }}"><i class="fa-solid fa-plus"></i> Thêm câu</a>
                @else
                    <details class="pw-type-pop">
                        <summary class="op-btn"><i class="fa-solid fa-plus"></i> Thêm câu</summary>
                        <div class="pw-type-menu">
                            @foreach ($questionTypes as $type)
                                <a href="{{ $createBase }}/{{ $type['slug'] }}">
                                    <i class="fa-solid {{ $type['icon'] }}"></i>
                                    <span>
                                        <strong>{{ $type['label'] }}</strong>
                                        <small>{{ $type['desc'] }}</small>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        @endunless
    </div>
    <div class="js-question-list pw-item-list" data-group="{{ $group }}" data-reorder-url="{{ $reorderUrl }}">
        @forelse ($questions as $index => $question)
            @php
                $preview = \App\Support\Questions\QuestionParser::plainTextFromHtml($question->question_text);
                $editUrl = $editRoute
                    ? $editRoute($question)
                    : route('admin.programs.sessions.questions.edit', [$program->id, $session->id, $question->id]);
            @endphp
            <article class="pw-item" data-id="{{ $question->id }}" draggable="{{ $program->isActive() ? 'true' : 'false' }}">
                <span class="js-drag pw-grip" title="Kéo để sắp xếp">
                    @unless ($program->isArchived())
                        <i class="fa-solid fa-grip-vertical"></i>
                    @endunless
                </span>
                <div class="pw-session-num">
                    <span>Câu</span>
                    <strong>{{ $index + 1 }}</strong>
                </div>
                <a href="{{ $editUrl }}" class="pw-item-body">
                    <strong>{{ $preview !== '' ? $preview : 'Chưa nhập nội dung câu hỏi' }}</strong>
                    <div class="pw-pills">
                        <span class="pw-chip is-ready">{{ $typeLabels[$question->question_type] ?? $question->question_type }}</span>
                    </div>
                </a>
                <div class="pw-actions">
                    <a href="{{ $editUrl }}" class="pw-link-btn">Sửa</a>
                </div>
            </article>
        @empty
            <div class="pw-empty">
                <span><i class="fa-solid fa-circle-question"></i></span>
                <p>Chưa có câu hỏi trong nhóm này.</p>
                @unless ($program->isArchived())
                    @if (($group ?? '') === 'quiz')
                        <a class="op-btn" href="{{ $createBase }}"><i class="fa-solid fa-plus"></i> Thêm câu đầu tiên</a>
                    @endif
                @endunless
            </div>
        @endforelse
    </div>
</section>

@push('scripts')
<script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        document.querySelectorAll('.js-question-list').forEach((list) => {
            const rows = list.querySelectorAll('[draggable="true"]');
            if (rows.length < 2) return;
            let dragged = null;
            rows.forEach((row) => {
                row.addEventListener('dragstart', () => { dragged = row; row.classList.add('opacity-50'); });
                row.addEventListener('dragend', () => { dragged = null; row.classList.remove('opacity-50'); });
                row.addEventListener('dragover', (event) => event.preventDefault());
                row.addEventListener('drop', (event) => {
                    event.preventDefault();
                    if (!dragged || dragged === row) return;
                    const all = [...list.querySelectorAll('[draggable="true"]')];
                    if (all.indexOf(dragged) < all.indexOf(row)) row.after(dragged); else row.before(dragged);
                    fetch(list.dataset.reorderUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ order: [...list.querySelectorAll('[draggable="true"]')].map((el) => el.dataset.id), group: list.dataset.group }),
                    }).then((res) => { if (!res.ok) TemplateUI.toast('error', 'Không sắp xếp được.'); });
                });
            });
        });
    })();
</script>
@endpush
