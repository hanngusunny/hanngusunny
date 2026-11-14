@php
    $gradingService = $gradingService ?? app(\App\Services\TeacherGradingService::class);
    $tabs = [
        \App\Services\TeacherGradingService::TAB_PRE => [
            'label' => 'Trước buổi học',
            'icon' => 'fa-book-open',
            'tone' => 'pre',
        ],
        \App\Services\TeacherGradingService::TAB_POST => [
            'label' => 'Sau buổi học',
            'icon' => 'fa-user-graduate',
            'tone' => 'post',
        ],
        \App\Services\TeacherGradingService::TAB_KNOWLEDGE_REVIEW => [
            'label' => 'Ôn tập kiến thức bài',
            'icon' => 'fa-lightbulb',
            'tone' => 'kr',
        ],
    ];
@endphp

<article class="op-panel teacher-grading-queue-card">
    <div class="teacher-grading-tabs" role="tablist" aria-label="Loại bài tập">
        @foreach ($tabs as $tabKey => $tabMeta)
            @php
                $count = (int) (($tabCounts ?? [])[$tabKey] ?? 0);
                $isActive = $activeTab === $tabKey;
                $tabUrl = $gradingService->pageUrl((int) $courseId, $tabKey);
            @endphp
            <a
                class="teacher-grading-tab teacher-grading-tab--{{ $tabMeta['tone'] }}{{ $isActive ? ' is-active' : '' }}"
                href="{{ $tabUrl }}"
                role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
            >
                <span class="teacher-grading-tab-icon" aria-hidden="true"><i class="fa-solid {{ $tabMeta['icon'] }}"></i></span>
                <span class="teacher-grading-tab-copy">
                    <strong>{{ $tabMeta['label'] }}</strong>
                    <small>{{ $count }} chưa chấm</small>
                </span>
                @if ($count > 0)
                    <span class="teacher-grading-tab-badge">{{ $count }}</span>
                @endif
            </a>
        @endforeach
    </div>

    @if (($tabItems ?? []) === [])
        <div class="teacher-grading-queue-empty">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <p>Không có câu tự luận, dịch từ hoặc phát âm nào chờ chấm trong mục này.</p>
        </div>
    @else
        <div class="teacher-grading-queue-list">
            @foreach ($tabItems as $item)
                @php
                    $key = (string) ($item['key'] ?? '');
                    $isActive = $key !== '' && $key === $selectedKey;
                    $itemUrl = $gradingService->pageUrl((int) $courseId, $activeTab, $key);
                    $submitted = trim((string) ($item['submitted_at'] ?? ''));
                    $submittedLabel = $submitted !== '' ? date('H:i d/m/Y', strtotime($submitted)) : '';
                @endphp
                <a
                    class="teacher-grading-queue-item{{ $isActive ? ' is-active' : '' }}"
                    href="{{ $itemUrl }}"
                    aria-current="{{ $isActive ? 'page' : 'false' }}"
                >
                    <div class="teacher-grading-queue-item-head">
                        <x-user-avatar
                            class="teacher-grading-queue-avatar"
                            :url="$item['student_avatar'] ?? null"
                            :name="$item['student_name'] ?? ''"
                        />
                        <div class="teacher-grading-queue-item-meta">
                            <strong>{{ $item['student_name'] ?? 'Học viên' }}</strong>
                            <span>{{ $item['assignment_label'] ?? '' }}</span>
                        </div>
                    </div>
                    <p class="teacher-grading-queue-item-question">{{ $item['question_text'] ?? 'Câu hỏi' }}</p>
                    @if ($submittedLabel !== '')
                        <span class="teacher-grading-queue-item-time">
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                            Nộp {{ $submittedLabel }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</article>
