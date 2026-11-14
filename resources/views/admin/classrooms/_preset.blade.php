@php
    $preset = is_array($preset ?? null) ? $preset : [];
    $index = $index ?? 0;
    $typeValue = (string) old('presets.'.$index.'.question_type', $preset['question_type'] ?? 'fill');
    if ($typeValue === 'text') {
        $typeValue = 'fill';
    }
    $nameIndex = is_string($index) ? $index : (int) $index;
    $presetNo = is_string($index) && $index === '__INDEX__' ? '__NUMBER__' : ((int) $index + 1);
    $questionText = old('presets.'.$index.'.question_text', $preset['question_text'] ?? '');
    $preview = trim((string) $questionText);
    $preview = $preview !== '' ? $preview : 'Chưa nhập nội dung câu hỏi';
    $options = [];
    if (! empty($preset['options_json'])) {
        $decoded = json_decode((string) $preset['options_json'], true);
        $options = is_array($decoded) ? $decoded : [];
    } elseif (! empty($preset['options']) && is_array($preset['options'])) {
        $options = $preset['options'];
    }
@endphp
<article class="cr-preset is-collapsed" data-preset-item>
    <div class="cr-preset-head">
        <button class="cr-preset-toggle" type="button" aria-expanded="false">
            <i class="fa-solid fa-chevron-down"></i>
            <strong>Câu <span data-preset-number>{{ $presetNo }}</span></strong>
            <span data-preset-preview>{{ $preview }}</span>
        </button>
        <button class="cr-preset-remove btn-remove-preset" type="button" title="Xóa"><i class="fa-solid fa-trash"></i></button>
    </div>
    <div class="cr-preset-body">
        <div class="cr-type">
            <label>
                <input type="radio" name="presets[{{ $nameIndex }}][question_type]" value="fill" @checked($typeValue !== 'choice') data-preset-type-radio>
                <span>Điền từ</span>
            </label>
            <label>
                <input type="radio" name="presets[{{ $nameIndex }}][question_type]" value="choice" @checked($typeValue === 'choice') data-preset-type-radio>
                <span>Trắc nghiệm</span>
            </label>
        </div>
        <label class="cr-field is-full">
            <span>Nội dung câu hỏi</span>
            <textarea name="presets[{{ $nameIndex }}][question_text]" rows="2" data-preset-question-input placeholder="Điền từ: dùng ___ cho chỗ trống. Trắc nghiệm: nhập câu hỏi + đáp án bên dưới.">{{ $questionText }}</textarea>
        </label>
        <div class="cr-preset-options" data-preset-options @if ($typeValue !== 'choice') hidden @endif>
            <span class="cr-label">Các lựa chọn</span>
            @for ($i = 0; $i < 4; $i++)
                <input type="text" name="presets[{{ $nameIndex }}][options][]" value="{{ $options[$i] ?? '' }}" placeholder="Lựa chọn {{ chr(65 + $i) }}">
            @endfor
        </div>
    </div>
</article>
