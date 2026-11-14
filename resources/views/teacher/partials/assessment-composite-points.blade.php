@php
    $scoring = app(\App\Services\AssessmentSubmissionService::class);
    $itemIndex = (int) ($itemIndex ?? 0);
    $partIndex = $partIndex ?? null;
    $itemMax = max(0.0, (float) ($itemMax ?? 0));
    $itemEarned = max(0.0, (float) ($itemEarned ?? 0));
    $showCompositeItemGrade = ! empty($showCompositeItemGrade);
    $showItemMaxPoints = ! empty($showItemMaxPoints) || $showCompositeItemGrade;
    $isPart = $partIndex !== null && $partIndex !== '';
    $partIndex = $isPart ? (int) $partIndex : 0;
    $fieldName = $isPart
        ? 'item_grades['.$itemIndex.'][parts]['.$partIndex.'][points]'
        : 'item_grades['.$itemIndex.'][points]';
    $ariaLabel = $isPart
        ? 'Điểm câu '.($itemIndex + 1).'.'.($partIndex + 1)
        : 'Điểm câu hỏi nhỏ '.($itemIndex + 1);
    $readonly = ! empty($readonly);
@endphp

@if ($showItemMaxPoints)
    <div class="session-composite-item-grade{{ $isPart ? ' is-part' : '' }}">
        @if ($showCompositeItemGrade)
            <label>
                <span>{{ $isPart ? 'Điểm câu' : 'Điểm' }}</span>
                <span class="program-question-points-input-wrap">
                    <input
                        type="text"
                        class="form-control program-question-points-input"
                        name="{{ $fieldName }}"
                        value="{{ $scoring->formatPoints($itemEarned) }}"
                        min="0"
                        max="{{ $scoring->formatPoints($itemMax) }}"
                        step="0.01"
                        inputmode="decimal"
                        lang="en"
                        autocomplete="off"
                        @if ($isPart)
                            data-composite-part-points
                            data-composite-item-index="{{ $itemIndex }}"
                        @else
                            data-composite-item-points
                        @endif
                        aria-label="{{ $ariaLabel }}"
                        @if ($readonly) readonly @endif
                        @if (trim((string) ($compositeFormId ?? '')) !== '') form="{{ $compositeFormId }}" @endif
                    >
                    <span class="program-question-points-suffix">/ {{ $scoring->formatPoints($itemMax) }}</span>
                </span>
            </label>
        @else
            <span class="session-composite-item-max">{{ $scoring->formatPoints($itemMax) }} điểm</span>
        @endif
    </div>
@endif
