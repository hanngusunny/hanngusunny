<?php
$question   = $question ?? [];
if ($question !== []) {
    $question = model(\App\Models\StudyProgramSessionQuestionModel::class)->normalizeQuestionRowForEdit($question);
}
$sessionIndex = $sessionIndex ?? 0;
$questionIndex = $questionIndex ?? 0;
$group      = $group ?? 'quiz';
$groupKey   = match ($group) {
    'review'        => 'review_questions',
    'post_practice' => 'post_practice_questions',
    'pre'           => 'pre_questions',
    default         => 'quiz_questions',
};
$isQuizOnly       = $group === 'quiz';
$isReview         = $group === 'review';
$isPostPractice   = $group === 'post_practice';
$isPreSession     = $group === 'pre';
$hasTypeToggle    = $isReview || $isPostPractice || $isPreSession;
$isPracticeGroup  = $isPostPractice || $isPreSession;
$hasExtendedTypes = $hasTypeToggle;
$fillTypeLabel    = $hasExtendedTypes ? 'Tự luận' : 'Điền từ';

$formScope = $formScope ?? 'sessions';
$standalone = $standalone ?? false;
$showPointsField = ! empty($showPointsField);
$questionNoPassed = $questionNo ?? null;

$value = static function (string $field) use ($question, $sessionIndex, $questionIndex, $groupKey, $formScope) {
    if ($formScope === 'question') {
        $old = old('question.' . $field);
    } elseif ($formScope === 'session') {
        $old = old('session.' . $groupKey . '.' . $questionIndex . '.' . $field);
    } else {
        $old = old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.' . $field);
    }

    if ($old !== null) {
        return $old;
    }

    return $question[$field] ?? '';
};

$typeValue = (string) $value('question_type');

if ($isQuizOnly) {
    $typeValue = 'choice';
} elseif ($typeValue === 'text') {
    $typeValue = 'fill';
} elseif ($typeValue === 'essay') {
    $typeValue = 'fill';
} elseif ($hasExtendedTypes && ! in_array($typeValue, ['fill', 'choice', 'multi_select', 'memory_match', 'word_link', 'word_order', 'listening', 'category_sort', 'fill_blanks', 'word_select', 'word_translate', 'phat_am', 'image_hotspot', 'image_label_drop'], true)) {
    $typeValue = 'choice';
}

$nameIndex = is_string($questionIndex) ? $questionIndex : (int) $questionIndex;
$sessionNameIndex = is_string($sessionIndex) ? $sessionIndex : (int) $sessionIndex;

if ($questionNoPassed !== null) {
    $questionNo = (int) $questionNoPassed;
} elseif (is_string($questionIndex) && $questionIndex === '__QINDEX__') {
    $questionNo = '__QNUMBER__';
} else {
    $questionNo = (int) $questionIndex + 1;
}
$previewRaw = trim(html_entity_decode(strip_tags((string) $value('question_text')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
if ($previewRaw === '') {
    $preview = 'Chưa nhập nội dung câu hỏi';
} elseif (mb_strlen($previewRaw) > 100) {
    $preview = mb_substr($previewRaw, 0, 100) . '…';
} else {
    $preview = $previewRaw;
}

$options = [];
$pairBundleModel = model(\App\Models\StudyProgramSessionQuestionModel::class);
$pairBundle      = $pairBundleModel->decodePairOptionsBundle(is_array($question) ? $question : []);
$displayMemoryPairs = $pairBundle['memory_pairs'];
$wordLinkPairs      = $pairBundle['word_link_pairs'];
$wordOrderTokens    = $pairBundle['word_order'];
$listeningConfig    = $pairBundle['listening'];
$listeningText      = trim((string) ($listeningConfig['text'] ?? ''));
$listeningAudio     = trim((string) ($listeningConfig['audio'] ?? ''));
$listeningAudioUrl  = $listeningAudio !== '' ? $pairBundleModel->resolveListeningAudioUrl($listeningAudio) : '';
$listeningBundle    = $pairBundleModel->decodeListeningBundle($question ?? ['options_json' => null]);
$listeningQuestions = $listeningBundle['questions'] ?? [];
$listeningChoiceQuestion = trim((string) ($listeningQuestions[0]['choice_question'] ?? ''));
$listeningAnswerMode = (string) ($listeningQuestions[0]['answer_mode'] ?? 'choice');
$listeningLang      = (string) ($listeningConfig['lang'] ?? 'ko-KR');
$trueFalseItems     = $pairBundle['true_false_items'];
$multiChoiceItems   = $pairBundle['multi_choice_items'];
$categorySortData   = $pairBundle['category_sort'];
$categorySortGroups = $categorySortData['groups'] ?? [];
$categorySortItems  = $categorySortData['items'] ?? [];
$fillBlanksData     = $pairBundle['fill_blanks'];
$fillBlanksTemplate = trim((string) ($fillBlanksData['template'] ?? ''));
$fillBlanksAnswers  = $fillBlanksData['answers'] ?? [];
$wordSelectData     = $pairBundle['word_select'];
$wordSelectTemplate = trim((string) ($wordSelectData['template'] ?? ''));
$wordSelectAnswers  = $wordSelectData['answers'] ?? [];
$wordSelectBank     = $wordSelectData['word_bank'] ?? [];
$wordTranslateData  = $pairBundle['word_translate'];
$wordTranslateItems = $wordTranslateData['items'] ?? [];
$wordTranslateColumns = (int) ($wordTranslateData['columns'] ?? 1);
$wordTranslateLayout  = (string) ($wordTranslateData['layout'] ?? 'vertical');
$phatAmData           = $pairBundle['phat_am'];
$phatAmBundle         = $pairBundleModel->decodePhatAmBundle($question ?? ['options_json' => null]);
$phatAmPrompt         = trim((string) ($phatAmBundle['prompt'] ?? ''));
$phatAmQuestions      = $phatAmBundle['questions'] ?? [];
$phatAmRequireRecording = model(\App\Models\StudyProgramSessionQuestionModel::class)
    ->normalizePhatAmRequireRecording($phatAmBundle['require_recording'] ?? true);
$phatAmChoiceQuestion = trim((string) ($phatAmQuestions[0]['choice_question'] ?? ''));
$phatAmAnswerMode     = (string) ($phatAmQuestions[0]['answer_mode'] ?? 'choice');
$imageHotspotData   = $pairBundle['image_hotspot'];
$imageHotspotImage  = trim((string) ($imageHotspotData['image'] ?? ''));
$imageHotspotTolerance = (float) ($imageHotspotData['tolerance'] ?? 8);
$imageHotspotTargets = $imageHotspotData['targets'] ?? [];
$imageLabelDropData = $pairBundle['image_label_drop'];
$imageLabelDropImage = trim((string) ($imageLabelDropData['image'] ?? ''));
$imageLabelDropRadius = (float) ($imageLabelDropData['zone_radius'] ?? 10);
$imageLabelDropZones = $imageLabelDropData['zones'] ?? [];
$imageLabelDropLabels = $imageLabelDropData['labels'] ?? [];

if (! empty($question['options_json'])) {
    $decoded = json_decode((string) $question['options_json'], true);

    if (is_array($decoded)) {
        if (isset($decoded['options']) && is_array($decoded['options'])) {
            $options = $decoded['options'];
        } elseif (! isset($decoded['pairs']) && ! isset($decoded['memory_pairs']) && ! isset($decoded['word_link_pairs']) && ! isset($decoded['word_order']) && ! isset($decoded['listening']) && ! isset($decoded['category_sort']) && ! isset($decoded['fill_blanks']) && ! isset($decoded['word_select']) && ! isset($decoded['word_translate']) && ! isset($decoded['phat_am']) && ! isset($decoded['image_hotspot']) && ! isset($decoded['image_label_drop'])) {
            $options = $decoded;
        }
    }
}

$oldMemoryPairs = $formScope === 'question'
    ? old('question.memory_pairs')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.memory_pairs')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.memory_pairs'));

if (is_array($oldMemoryPairs)) {
    $displayMemoryPairs = $oldMemoryPairs;
}

$oldWordLinkPairs = $formScope === 'question'
    ? old('question.word_link_pairs')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.word_link_pairs')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.word_link_pairs'));

if (is_array($oldWordLinkPairs)) {
    $wordLinkPairs = $oldWordLinkPairs;
}

$oldMemoryPairsJson = $formScope === 'question'
    ? old('question.memory_pairs_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.memory_pairs_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.memory_pairs_json'));

if (is_string($oldMemoryPairsJson) && $oldMemoryPairsJson !== '') {
    $decodedOldJson = json_decode($oldMemoryPairsJson, true);

    if (is_array($decodedOldJson)) {
        $displayMemoryPairs = $decodedOldJson;
    }
}

$oldWordLinkPairsJson = $formScope === 'question'
    ? old('question.word_link_pairs_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.word_link_pairs_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.word_link_pairs_json'));

if (is_string($oldWordLinkPairsJson) && $oldWordLinkPairsJson !== '') {
    $decodedOldJson = json_decode($oldWordLinkPairsJson, true);

    if (is_array($decodedOldJson)) {
        $wordLinkPairs = $decodedOldJson;
    }
}

$oldWordOrderJson = $formScope === 'question'
    ? old('question.word_order_tokens_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.word_order_tokens_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.word_order_tokens_json'));

if (is_string($oldWordOrderJson) && $oldWordOrderJson !== '') {
    $decodedOldJson = json_decode($oldWordOrderJson, true);

    if (is_array($decodedOldJson)) {
        $wordOrderTokens = $decodedOldJson;
    }
}

$oldCategorySortJson = $formScope === 'question'
    ? old('question.category_sort_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.category_sort_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.category_sort_json'));

if (is_string($oldCategorySortJson) && $oldCategorySortJson !== '') {
    $decodedOldJson = json_decode($oldCategorySortJson, true);

    if (is_array($decodedOldJson)) {
        $categorySortData = $pairBundleModel->normalizeCategorySortData($decodedOldJson);
        $categorySortGroups = $categorySortData['groups'];
        $categorySortItems  = $categorySortData['items'];
    }
}

$oldFillBlanksJson = $formScope === 'question'
    ? old('question.fill_blanks_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.fill_blanks_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.fill_blanks_json'));

if (is_string($oldFillBlanksJson) && $oldFillBlanksJson !== '') {
    $decodedOldJson = json_decode($oldFillBlanksJson, true);

    if (is_array($decodedOldJson)) {
        $fillBlanksData = $pairBundleModel->normalizeFillBlanksData($decodedOldJson);
        $fillBlanksTemplate = trim((string) ($fillBlanksData['template'] ?? ''));
        $fillBlanksAnswers  = $fillBlanksData['answers'] ?? [];
    }
}

$oldWordSelectJson = $formScope === 'question'
    ? old('question.word_select_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.word_select_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.word_select_json'));

if (is_string($oldWordSelectJson) && $oldWordSelectJson !== '') {
    $decodedOldJson = json_decode($oldWordSelectJson, true);

    if (is_array($decodedOldJson)) {
        $wordSelectData = $pairBundleModel->normalizeWordSelectData($decodedOldJson);
        $wordSelectTemplate = trim((string) ($wordSelectData['template'] ?? ''));
        $wordSelectAnswers  = $wordSelectData['answers'] ?? [];
        $wordSelectBank     = $wordSelectData['word_bank'] ?? [];
    }
}

$oldWordTranslateJson = $formScope === 'question'
    ? old('question.word_translate_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.word_translate_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.word_translate_json'));

if (is_string($oldWordTranslateJson) && $oldWordTranslateJson !== '') {
    $decodedOldJson = json_decode($oldWordTranslateJson, true);

    if (is_array($decodedOldJson)) {
        $wordTranslateData = $pairBundleModel->normalizeWordTranslateData($decodedOldJson);
        $wordTranslateItems = $wordTranslateData['items'] ?? [];
        $wordTranslateColumns = (int) ($wordTranslateData['columns'] ?? 1);
        $wordTranslateLayout = (string) ($wordTranslateData['layout'] ?? 'vertical');
    }
}

$oldPhatAmJson = $formScope === 'question'
    ? old('question.phat_am_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.phat_am_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.phat_am_json'));

if (is_string($oldPhatAmJson) && $oldPhatAmJson !== '') {
    $decodedOldJson = json_decode($oldPhatAmJson, true);

    if (is_array($decodedOldJson)) {
        $phatAmData = $pairBundleModel->normalizePhatAmData($decodedOldJson);
        $phatAmPrompt = trim((string) ($phatAmData['prompt'] ?? ''));
        $phatAmQuestions = $phatAmData['questions'] ?? [];
        $phatAmRequireRecording = $pairBundleModel->normalizePhatAmRequireRecording($phatAmData['require_recording'] ?? true);
        $phatAmChoiceQuestion = trim((string) ($phatAmQuestions[0]['choice_question'] ?? ''));
        $phatAmAnswerMode = (string) ($phatAmQuestions[0]['answer_mode'] ?? 'choice');
    }
}

$oldPhatAmPrompt = $formScope === 'question'
    ? old('question.phat_am_prompt')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.phat_am_prompt')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.phat_am_prompt'));

if (is_string($oldPhatAmPrompt) && trim($oldPhatAmPrompt) !== '') {
    $phatAmPrompt = trim($oldPhatAmPrompt);
}

$oldPhatAmChoiceQuestion = $formScope === 'question'
    ? old('question.phat_am_choice_question')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.phat_am_choice_question')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.phat_am_choice_question'));

if (is_string($oldPhatAmChoiceQuestion) && trim($oldPhatAmChoiceQuestion) !== '') {
    $phatAmChoiceQuestion = trim($oldPhatAmChoiceQuestion);
    if ($phatAmQuestions !== []) {
        $phatAmQuestions[0]['choice_question'] = $phatAmChoiceQuestion;
    }
}

$oldPhatAmAnswerMode = $formScope === 'question'
    ? old('question.phat_am_answer_mode')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.phat_am_answer_mode')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.phat_am_answer_mode'));

if (is_string($oldPhatAmAnswerMode) && trim($oldPhatAmAnswerMode) !== '') {
    $phatAmAnswerMode = trim($oldPhatAmAnswerMode);
    if ($phatAmQuestions !== []) {
        $phatAmQuestions[0]['answer_mode'] = $phatAmAnswerMode;
    }
}

if ($phatAmQuestions !== []) {
    $phatAmAnswerMode = (string) ($phatAmQuestions[0]['answer_mode'] ?? 'choice');
    $phatAmChoiceQuestion = trim((string) ($phatAmQuestions[0]['choice_question'] ?? ''));
}

$oldListeningChoiceQuestion = $formScope === 'question'
    ? old('question.listening_choice_question')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.listening_choice_question')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.listening_choice_question'));

if (is_string($oldListeningChoiceQuestion) && trim($oldListeningChoiceQuestion) !== '') {
    $listeningChoiceQuestion = trim($oldListeningChoiceQuestion);
}

$oldImageHotspotJson = $formScope === 'question'
    ? old('question.image_hotspot_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.image_hotspot_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.image_hotspot_json'));

if (is_string($oldImageHotspotJson) && $oldImageHotspotJson !== '') {
    $decodedOldJson = json_decode($oldImageHotspotJson, true);

    if (is_array($decodedOldJson)) {
        $imageHotspotData = $pairBundleModel->normalizeImageHotspotData($decodedOldJson);
        $imageHotspotImage = trim((string) ($imageHotspotData['image'] ?? ''));
        $imageHotspotTolerance = (float) ($imageHotspotData['tolerance'] ?? 8);
        $imageHotspotTargets = $imageHotspotData['targets'] ?? [];
    }
}

$oldImageLabelDropJson = $formScope === 'question'
    ? old('question.image_label_drop_json')
    : ($formScope === 'session'
        ? old('session.' . $groupKey . '.' . $questionIndex . '.image_label_drop_json')
        : old('sessions.' . $sessionIndex . '.' . $groupKey . '.' . $questionIndex . '.image_label_drop_json'));

if (is_string($oldImageLabelDropJson) && $oldImageLabelDropJson !== '') {
    $decodedOldJson = json_decode($oldImageLabelDropJson, true);

    if (is_array($decodedOldJson)) {
        $imageLabelDropData = $pairBundleModel->normalizeImageLabelDropData($decodedOldJson);
        $imageLabelDropImage = trim((string) ($imageLabelDropData['image'] ?? ''));
        $imageLabelDropRadius = (float) ($imageLabelDropData['zone_radius'] ?? 10);
        $imageLabelDropZones = $imageLabelDropData['zones'] ?? [];
        $imageLabelDropLabels = $imageLabelDropData['labels'] ?? [];
    }
}

if ($displayMemoryPairs === [] && $hasExtendedTypes) {
    $displayMemoryPairs = [
        ['image' => '', 'label' => ''],
        ['image' => '', 'label' => ''],
    ];
}

if ($wordLinkPairs === [] && $hasExtendedTypes) {
    $wordLinkPairs = [
        ['a_type' => 'image', 'a_value' => '', 'b_type' => 'text', 'b_value' => ''],
        ['a_type' => 'image', 'a_value' => '', 'b_type' => 'text', 'b_value' => ''],
    ];
}

if ($wordOrderTokens === [] && $hasExtendedTypes && $typeValue === 'word_order') {
    $wordOrderTokens = ['', ''];
}

if ($categorySortGroups === [] && $hasExtendedTypes && $typeValue === 'category_sort') {
    $categorySortGroups = [
        ['id' => '0', 'label' => 'Danh từ'],
        ['id' => '1', 'label' => 'Động từ'],
    ];
}

if ($categorySortItems === [] && $hasExtendedTypes && $typeValue === 'category_sort') {
    $categorySortItems = [
        ['id' => '0', 'text' => '', 'group_id' => '0'],
        ['id' => '1', 'text' => '', 'group_id' => '1'],
    ];
}

if ($fillBlanksTemplate === '' && $hasExtendedTypes && $typeValue === 'fill_blanks') {
    $fillBlanksTemplate = 'Tôi ___ đi học ___ giờ mỗi ngày.';
    $fillBlanksAnswers  = ['', ''];
}

if ($wordSelectTemplate === '' && $hasExtendedTypes && $typeValue === 'word_select') {
    $wordSelectTemplate = 'Tôi ___ đi học lúc ___ giờ mỗi ngày.';
    $wordSelectAnswers  = ['đã', '7'];
    $wordSelectBank     = ['đã', 'sẽ', '7', '8', '9'];
}

if (($wordTranslateItems ?? []) === [] && $hasExtendedTypes && $typeValue === 'word_translate') {
    $wordTranslateItems = [
        ['source_type' => 'text', 'source' => '', 'answer' => ''],
        ['source_type' => 'text', 'source' => '', 'answer' => ''],
    ];
}

$memoryPairsJson = json_encode(array_values(array_map(static function ($pair) {
    return [
        'image' => trim((string) ($pair['image'] ?? '')),
        'label' => trim((string) ($pair['label'] ?? '')),
    ];
}, array_filter($displayMemoryPairs, static fn ($pair) => is_array($pair)))), JSON_UNESCAPED_UNICODE);

$wordLinkPairsJson = json_encode(array_values(array_map(static function ($pair) use ($pairBundleModel) {
    return $pairBundleModel->normalizeWordLinkPairRow(is_array($pair) ? $pair : []);
}, array_filter($wordLinkPairs, static fn ($pair) => is_array($pair)))), JSON_UNESCAPED_UNICODE);

$wordOrderTokensJson = json_encode(array_values(array_map(static function ($token) use ($pairBundleModel) {
    if (is_array($token)) {
        return trim((string) ($token['text'] ?? $token['value'] ?? ''));
    }

    return trim((string) $token);
}, array_filter($wordOrderTokens, static fn ($token) => trim(is_array($token) ? (string) ($token['text'] ?? $token['value'] ?? '') : (string) $token) !== ''))), JSON_UNESCAPED_UNICODE);

$categorySortJson = json_encode($pairBundleModel->normalizeCategorySortData([
    'groups' => $categorySortGroups,
    'items'  => $categorySortItems,
]), JSON_UNESCAPED_UNICODE);

$fillBlanksJson = json_encode($pairBundleModel->normalizeFillBlanksData([
    'template' => $fillBlanksTemplate,
    'answers'  => $fillBlanksAnswers,
]), JSON_UNESCAPED_UNICODE);

$wordSelectJson = json_encode($pairBundleModel->normalizeWordSelectData([
    'template'  => $wordSelectTemplate,
    'answers'   => $wordSelectAnswers,
    'word_bank' => $wordSelectBank,
]), JSON_UNESCAPED_UNICODE);

$wordTranslateJson = json_encode([
    'items'   => array_values(array_map(
        static fn ($item): array => $pairBundleModel->normalizeWordTranslateItem(is_array($item) ? $item : []),
        $wordTranslateItems
    )),
    'columns' => $wordTranslateColumns,
    'layout'  => $wordTranslateLayout,
], JSON_UNESCAPED_UNICODE);

$phatAmJson = json_encode($pairBundleModel->normalizePhatAmData([
    'prompt'            => $phatAmPrompt,
    'questions'         => $phatAmQuestions,
    'require_recording' => $phatAmRequireRecording,
]), JSON_UNESCAPED_UNICODE);

$listeningJson = json_encode($pairBundleModel->normalizeListeningData([
    'text'      => $listeningText,
    'audio'     => $listeningAudio,
    'lang'      => $listeningLang,
    'questions' => $listeningQuestions,
]), JSON_UNESCAPED_UNICODE);

if ($imageLabelDropZones === [] && $hasExtendedTypes && $typeValue === 'image_label_drop') {
    $imageLabelDropZones = [
        ['id' => 'z1', 'label' => 'Vùng 1', 'x' => 35, 'y' => 40],
        ['id' => 'z2', 'label' => 'Vùng 2', 'x' => 65, 'y' => 55],
    ];
    $imageLabelDropLabels = [
        ['id' => 'l1', 'text' => '머리', 'zone_id' => 'z1'],
        ['id' => 'l2', 'text' => '손', 'zone_id' => 'z2'],
    ];
}

$imageHotspotJson = json_encode($pairBundleModel->normalizeImageHotspotData([
    'image'     => $imageHotspotImage,
    'tolerance' => $imageHotspotTolerance,
    'targets'   => $imageHotspotTargets,
]), JSON_UNESCAPED_UNICODE);

$imageLabelDropJson = json_encode($pairBundleModel->normalizeImageLabelDropData([
    'image'       => $imageLabelDropImage,
    'zone_radius' => $imageLabelDropRadius,
    'zones'       => $imageLabelDropZones,
    'labels'      => $imageLabelDropLabels,
]), JSON_UNESCAPED_UNICODE);

$imageHotspotDisplayUrl = $imageHotspotImage !== ''
    ? $pairBundleModel->resolveImageHotspotUrl($imageHotspotImage)
    : '';

$imageLabelDropDisplayUrl = $imageLabelDropImage !== ''
    ? $pairBundleModel->resolveImageLabelDropUrl($imageLabelDropImage)
    : '';

$isWordOrderType = $typeValue === 'word_order';
$isMultiSelectType = $typeValue === 'multi_select';
$isListeningType = $typeValue === 'listening';
$isCategorySortType = $typeValue === 'category_sort';
$isFillBlanksType = $typeValue === 'fill_blanks';
$isWordSelectType = $typeValue === 'word_select';
$isWordTranslateType = $typeValue === 'word_translate';
$isPhatAmType = $typeValue === 'phat_am';
$isImageHotspotType = $typeValue === 'image_hotspot';
$isImageLabelDropType = $typeValue === 'image_label_drop';
$showChoiceFields = ($typeValue === 'choice' || $isMultiSelectType) && ! $isPhatAmType && ! $isListeningType;
$showFillFields   = ($typeValue === 'fill') && ! $isPhatAmType && ! $isListeningType;
$showTrueFalseFields = false;
$showMultiChoiceFields = false;

if ($trueFalseItems === []) {
    $trueFalseItems = [
        ['statement' => '', 'answer' => true],
        ['statement' => '', 'answer' => false],
    ];
}

$listeningTrueFalseJson = json_encode($trueFalseItems, JSON_UNESCAPED_UNICODE);

if ($multiChoiceItems === []) {
    $multiChoiceItems = [
        [
            'question' => '',
            'options'  => ['', ''],
            'correct'  => 'a',
        ],
    ];
}

$listeningMultiChoiceJson = json_encode($multiChoiceItems, JSON_UNESCAPED_UNICODE);
$wordOrderPreview = implode(' ', array_values(array_filter(array_map(static function ($token) {
    if (is_array($token)) {
        return trim((string) ($token['text'] ?? $token['value'] ?? ''));
    }

    return trim((string) $token);
}, $wordOrderTokens), static fn ($text) => $text !== '')));
$isMemoryMatchType = $typeValue === 'memory_match';
$isWordLinkType    = $typeValue === 'word_link';
$correctAnswer   = strtolower(trim((string) $value('correct_answer')));
$correctLetters  = $isMultiSelectType
    ? $pairBundleModel->parseMultiSelectLetters($correctAnswer)
    : [$correctAnswer];
$answerLabels    = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
$choiceSlotCount = min(max(4, count($options)), 8);

$editorId = is_string($questionIndex) && $questionIndex === '__QINDEX__'
    ? ($isQuizOnly
        ? 'quiz-explanation-__SINDEX__-__QINDEX__'
        : ($isPostPractice
            ? 'post-practice-explanation-__SINDEX__-__QINDEX__'
            : ($isPreSession
                ? 'pre-practice-explanation-__SINDEX__-__QINDEX__'
                : 'review-explanation-__SINDEX__-__QINDEX__')))
    : ($isQuizOnly
        ? 'quiz-explanation-' . $sessionNameIndex . '-' . $nameIndex
        : ($isPostPractice
            ? 'post-practice-explanation-' . $sessionNameIndex . '-' . $nameIndex
            : ($isPreSession
                ? 'pre-practice-explanation-' . $sessionNameIndex . '-' . $nameIndex
                : 'review-explanation-' . $sessionNameIndex . '-' . $nameIndex)));

$questionEditorId = is_string($questionIndex) && $questionIndex === '__QINDEX__'
    ? match ($group) {
        'review'        => 'review-question-__SINDEX__-__QINDEX__',
        'post_practice' => 'post-practice-question-__SINDEX__-__QINDEX__',
        'pre'           => 'pre-practice-question-__SINDEX__-__QINDEX__',
        default         => 'quiz-question-__SINDEX__-__QINDEX__',
    }
    : match ($group) {
        'review'        => 'review-question-' . $sessionNameIndex . '-' . $nameIndex,
        'post_practice' => 'post-practice-question-' . $sessionNameIndex . '-' . $nameIndex,
        'pre'           => 'pre-practice-question-' . $sessionNameIndex . '-' . $nameIndex,
        default         => 'quiz-question-' . $sessionNameIndex . '-' . $nameIndex,
    };

$modelAnswerEditorId = is_string($questionIndex) && $questionIndex === '__QINDEX__'
    ? ($isPreSession ? 'pre-practice-model-__SINDEX__-__QINDEX__' : 'post-practice-model-__SINDEX__-__QINDEX__')
    : ($isPreSession ? 'pre-practice-model-' . $sessionNameIndex . '-' . $nameIndex : 'post-practice-model-' . $sessionNameIndex . '-' . $nameIndex);

$fieldPrefix = $formScope === 'question'
    ? 'question'
    : ($formScope === 'session'
        ? 'session[' . esc($groupKey) . '][' . esc((string) $nameIndex) . ']'
        : 'sessions[' . esc((string) $sessionNameIndex) . '][' . esc($groupKey) . '][' . esc((string) $nameIndex) . ']');

$fieldName = static function (string $field) use ($fieldPrefix, $formScope): string {
    if ($formScope === 'question') {
        return 'question[' . $field . ']';
    }

    return $fieldPrefix . '[' . $field . ']';
};

$optionsFieldName = $fieldName('options') . '[]';
$showChoiceMarks  = $hasTypeToggle;
if ($showChoiceMarks && ! $isMultiSelectType && ! in_array($correctAnswer, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'], true)) {
    $correctAnswer = 'a';
}
?>
<article class="question-card classroom-preset-card<?= $standalone ? ' question-card--standalone' : ' is-collapsed' ?>" data-session-question-item data-question-group="<?= esc($group) ?>" data-question-index="<?= esc((string) $nameIndex) ?>">
  <?php if (! $standalone): ?>
  <div class="question-card-head">
    <button class="question-card-toggle" type="button" aria-expanded="false">
      <i class="bi bi-chevron-down question-card-toggle-icon" aria-hidden="true"></i>
      <span class="question-card-summary">
        <strong>Câu <span data-session-question-number><?= esc((string) $questionNo) ?></span></strong>
        <span class="question-card-preview" data-session-question-preview><?= esc($preview) ?></span>
      </span>
    </button>
    <button class="btn sunny-mini btn-delete-user btn-with-icon btn-remove-session-question" type="button" data-confirm="Xóa câu hỏi này?" data-confirm-title="Xác nhận xóa câu hỏi">
      <i class="bi bi-trash" aria-hidden="true"></i>
      <span>Xóa</span>
    </button>
  </div>
  <?php else: ?>
  <p class="program-question-edit-form-lead">Chọn loại câu hỏi và nhập nội dung bên dưới.</p>
  <?php endif ?>

  <div class="question-card-body">
    <?php if ($showPointsField): ?>
      <?php
        $pointsValue = $value('points');
        $pointsValue = $pointsValue !== '' && $pointsValue !== null ? (float) $pointsValue : 1;
        if ($pointsValue < 0) {
            $pointsValue = 0.01;
        }
        $pointsHint = 'Giáo viên chấm trong thang điểm này khi chấm bài kiểm tra. Với Nghe đáp và Phát âm, nhập điểm từng câu nhỏ — điểm câu lớn = tổng các câu đó. Chỉ để trống hết câu nhỏ thì hệ thống mới chia đều.';
      ?>
      <div class="mb-3 program-question-points-field">
        <div class="program-question-type-head">
          <label class="form-label" for="assessmentQuestionPoints">Điểm câu hỏi</label>
          <span class="pq-info-tip" tabindex="0" aria-label="<?= esc($pointsHint) ?>">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <span class="pq-info-tip-text" role="tooltip"><?= esc($pointsHint) ?></span>
          </span>
        </div>
        <div class="program-question-points-input-wrap">
          <input
            type="number"
            class="form-control program-question-points-input"
            id="assessmentQuestionPoints"
            name="<?= esc($fieldName('points')) ?>"
            value="<?= esc((string) $pointsValue) ?>"
            min="0.01"
            max="1000"
            step="0.01"
            inputmode="decimal"
            required
          >
          <span class="program-question-points-suffix">điểm</span>
        </div>
      </div>
    <?php endif ?>
    <?php if ($hasTypeToggle): ?>
      <div class="mb-3">
        <label class="form-label">Loại câu hỏi</label>
        <div class="classroom-preset-type-toggle<?= $hasExtendedTypes ? ' classroom-preset-type-toggle--dec' : '' ?>">
          <?php
            $typeOptions = [
                'fill' => ['label' => $fillTypeLabel, 'icon' => 'bi-pencil-square'],
                'choice' => ['label' => 'Trắc nghiệm', 'icon' => 'bi-ui-radios'],
                'multi_select' => ['label' => 'Trắc nghiệm (nhiều đáp án)', 'icon' => 'bi-ui-checks'],
            ];
            if ($hasExtendedTypes) {
                $typeOptions += [
                    'memory_match' => ['label' => 'Lật ảnh', 'icon' => 'bi-grid-3x2'],
                    'word_link' => ['label' => 'Nối từ', 'icon' => 'bi-link-45deg'],
                    'word_order' => ['label' => 'Sắp xếp câu', 'icon' => 'bi-sort-alpha-down'],
                    'listening' => ['label' => 'Nghe đáp', 'icon' => 'bi-headphones'],
                    'category_sort' => ['label' => 'Phân loại', 'icon' => 'bi-folder2'],
                    'fill_blanks' => ['label' => 'Nhiều chỗ trống', 'icon' => 'bi-input-cursor-text'],
                    'word_select' => ['label' => 'Chọn từ', 'icon' => 'bi-tags'],
                    'word_translate' => ['label' => 'Dịch từ', 'icon' => 'bi-translate'],
                    'phat_am' => ['label' => 'Phát âm', 'icon' => 'bi-mic'],
                    'image_hotspot' => ['label' => 'Click ảnh', 'icon' => 'bi-bullseye'],
                    'image_label_drop' => ['label' => 'Kéo nhãn ảnh', 'icon' => 'bi-bounding-box'],
                ];
            }
          ?>
          <?php foreach ($typeOptions as $typeKey => $typeOption): ?>
            <label class="classroom-preset-type-option" data-qtype="<?= esc($typeKey) ?>">
              <input type="radio" name="<?= esc($fieldName('question_type')) ?>" value="<?= esc($typeKey) ?>"
                <?= $typeValue === $typeKey ? 'checked' : '' ?> data-session-question-type-radio>
              <span>
                <em class="pq-type-ico"><i class="bi <?= esc($typeOption['icon']) ?>" aria-hidden="true"></i></em>
                <?= esc($typeOption['label']) ?>
              </span>
            </label>
          <?php endforeach ?>
        </div>
      </div>
    <?php else: ?>
      <input type="hidden" name="<?= esc($fieldName('question_type')) ?>" value="choice">
    <?php endif ?>

    <div class="mb-3" data-session-question-text-wrap>
      <label class="form-label" for="<?= esc($questionEditorId) ?>">Nội dung câu hỏi</label>
      <textarea
        class="form-control richtext-question"
        id="<?= esc($questionEditorId) ?>"
        name="<?= esc($fieldName('question_text')) ?>"
        rows="6"
        data-session-question-input
        placeholder="<?= $isQuizOnly ? 'Nhập câu hỏi trắc nghiệm và các đáp án bên dưới.' : ($isPhatAmType ? 'Nhập câu hỏi hoặc hướng dẫn cho học viên (hiển thị phía trên câu mẫu).' : 'Hướng dẫn học sinh: tự luận, trắc nghiệm, lật ảnh hoặc nối từ ghép cặp.') ?>"
      ><?= esc($value('question_text')) ?></textarea>
    </div>

    <?php if ($hasExtendedTypes): ?>
      <?= view('study_programs/_session_phat_am_items_panel', [
          'phatAmPrompt'           => $phatAmPrompt,
          'phatAmQuestions'        => $phatAmQuestions,
          'phatAmRequireRecording' => $phatAmRequireRecording,
          'isPhatAmType'           => $isPhatAmType,
          'phatAmJson'             => $phatAmJson,
          'nameIndex'              => $nameIndex,
          'fieldName'              => $fieldName,
      ]) ?>
    <?php endif ?>

    <?php if ($hasExtendedTypes): ?>
      <div
        class="session-listening-panel session-listening-panel--under-question mb-3"
        data-session-listening-panel
        <?= $isListeningType ? '' : 'hidden' ?>
      >
        <div class="session-listening-mp3-panel-card">
          <div class="session-listening-mp3-panel-head">
            <span class="session-listening-mp3-panel-icon" aria-hidden="true"><i class="bi bi-file-earmark-music"></i></span>
            <div>
              <h3 class="session-listening-mp3-panel-title">File MP3 cho học sinh nghe</h3>
              <p class="course-field-hint session-listening-hint mb-0">
                Tải MP3 từ máy hoặc link — học sinh bấm nghe kèm câu hỏi phía trên.
              </p>
            </div>
          </div>

          <div class="session-listening-mp3-block" data-session-listening-mp3-upload>
            <input
              type="hidden"
              name="<?= esc($fieldName('listening_audio_saved')) ?>"
              value="<?= esc($listeningAudio, 'attr') ?>"
              data-qb-audio-saved
              data-original-name="<?= esc($fieldName('listening_audio_saved')) ?>"
              <?= $isListeningType ? '' : 'disabled' ?>
            >

            <div class="session-listening-mp3-grid">
              <div class="session-listening-mp3-card">
                <h4><i class="bi bi-upload"></i> Tải từ máy</h4>
                <input
                  class="form-control form-control-sm"
                  type="file"
                  accept="audio/mpeg,.mp3,audio/mp3"
                  data-qb-audio-file
                  <?= $isListeningType ? '' : 'disabled' ?>
                >
                <p class="field-hint">Tối đa 10MB · MP3</p>
              </div>

              <div class="session-listening-mp3-divider" aria-hidden="true"><span>hoặc</span></div>

              <div class="session-listening-mp3-card">
                <h4><i class="bi bi-link-45deg"></i> Tải từ link MP3</h4>
                <div class="session-listening-mp3-url-row">
                  <input
                    class="form-control form-control-sm"
                    type="url"
                    placeholder="https://example.com/audio/bai-nghe.mp3"
                    data-qb-audio-url
                    <?= $isListeningType ? '' : 'disabled' ?>
                  >
                  <button class="btn btn-outline btn-sm" type="button" data-qb-audio-fetch <?= $isListeningType ? '' : 'disabled' ?>>
                    <i class="bi bi-cloud-download"></i> Tải về
                  </button>
                </div>
                <p class="field-hint">Link MP3 công khai (http/https)</p>
                <p class="session-listening-mp3-fetch-status" data-qb-audio-fetch-status aria-live="polite"></p>
              </div>
            </div>

            <div class="session-listening-mp3-preview<?= ($listeningAudioUrl === '' && $listeningAudio === '') ? ' is-hidden' : '' ?>" data-qb-audio-preview-wrap<?= ($listeningAudioUrl === '' && $listeningAudio === '') ? ' hidden' : '' ?>>
              <span class="session-listening-mp3-preview-label"><i class="bi bi-file-earmark-music"></i> <span data-qb-audio-preview-label><?= $listeningAudioUrl !== '' ? 'MP3 hiện tại' : 'MP3 đã tải' ?></span></span>
              <audio controls preload="metadata" data-qb-audio-preview src="<?= esc($listeningAudioUrl, 'attr') ?>"></audio>
              <button class="btn btn-outline btn-sm text-danger" type="button" data-qb-audio-clear <?= $isListeningType ? '' : 'disabled' ?>>
                <i class="bi bi-trash"></i> Xóa MP3
              </button>
            </div>
          </div>
        </div>
      </div>

      <?= view('study_programs/_session_listening_items_panel', [
          'listeningAudio'     => $listeningAudio,
          'listeningQuestions' => $listeningQuestions,
          'isListeningType'    => $isListeningType,
          'listeningJson'      => $listeningJson,
          'nameIndex'          => $nameIndex,
          'fieldName'          => $fieldName,
          'pairBundleModel'    => $pairBundleModel,
      ]) ?>
    <?php endif ?>

    <div class="classroom-preset-options" data-session-question-options <?= ($isQuizOnly || $showChoiceFields) ? '' : 'hidden' ?>>
      <label class="form-label">
        Các lựa chọn<?= $isQuizOnly ? ' (bắt buộc ít nhất 2)' : '' ?>
        <?php if ($showChoiceMarks): ?>
          <?php if ($isMultiSelectType): ?>
            <span class="session-input-hint">— bấm nhiều chữ cái để chọn các đáp án đúng · Enter sang ô kế · dán nhiều dòng cùng lúc</span>
          <?php else: ?>
            <span class="session-input-hint">— bấm chữ cái để chọn đáp án đúng · Enter sang ô kế · dán nhiều dòng cùng lúc</span>
          <?php endif ?>
        <?php else: ?>
          <span class="session-input-hint">— Enter sang ô kế · dán nhiều dòng để điền nhanh A, B, C…</span>
        <?php endif ?>
      </label>
      <div
        class="session-choice-options-list"
        data-session-choice-options-list
        data-options-name="<?= esc($optionsFieldName, 'attr') ?>"
        data-choice-multi="<?= $isMultiSelectType ? '1' : '0' ?>"
        <?= $showChoiceMarks ? 'data-correct-field-name="' . esc($fieldName('correct_answer'), 'attr') . '"' : '' ?>
      >
        <?php for ($i = 0; $i < $choiceSlotCount; $i++): ?>
          <?php $optionKey = chr(97 + $i); ?>
          <div class="session-choice-option-row" data-session-choice-option-row data-option-key="<?= esc($optionKey) ?>">
            <?php if ($showChoiceMarks): ?>
              <button
                type="button"
                class="session-choice-option-mark<?= in_array($optionKey, $correctLetters, true) ? ' is-correct' : '' ?>"
                data-session-choice-mark="<?= esc($optionKey) ?>"
                title="<?= $isMultiSelectType ? 'Chọn/bỏ chọn đáp án đúng' : 'Đánh dấu đáp án đúng' ?>"
              ><?= esc(strtoupper($optionKey)) ?></button>
            <?php else: ?>
              <span class="session-choice-option-letter" aria-hidden="true"><?= esc(strtoupper($optionKey)) ?></span>
            <?php endif ?>
            <input
              class="form-control form-control-sm session-choice-option-input"
              type="text"
              name="<?= esc($optionsFieldName) ?>"
              value="<?= esc((string) ($options[$i] ?? '')) ?>"
              placeholder="Lựa chọn <?= esc(strtoupper($optionKey)) ?>"
              data-session-question-option
            >
            <button
              class="btn sunny-mini session-choice-option-remove"
              type="button"
              data-session-choice-remove
              title="Xóa lựa chọn"
              <?= $i < 2 ? 'hidden' : '' ?>
            >
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
          </div>
        <?php endfor ?>
      </div>
      <?php if ($choiceSlotCount < 8): ?>
        <button class="btn btn-outline btn-sm session-choice-add-btn mt-2" type="button" data-session-choice-add>
          <i class="bi bi-plus-lg" aria-hidden="true"></i>
          Thêm lựa chọn
        </button>
      <?php endif ?>
      <?php if ($showChoiceMarks): ?>
        <input
          type="hidden"
          name="<?= esc($fieldName('correct_answer'), 'attr') ?>"
          value="<?= esc($correctAnswer !== '' ? ($isMultiSelectType ? implode(',', $correctLetters) : $correctAnswer) : 'a') ?>"
          data-original-name="<?= esc($fieldName('correct_answer')) ?>"
          data-session-choice-correct
        >
      <?php endif ?>
    </div>

    <?php if ($hasExtendedTypes): ?>
      <div
        class="session-listening-true-false-panel mt-1"
        data-session-listening-true-false-panel
        <?= $showTrueFalseFields ? '' : 'hidden' ?>
      >
        <input
          type="hidden"
          name="<?= esc($fieldName('listening_true_false_json')) ?>"
          value="<?= esc($listeningTrueFalseJson) ?>"
          data-session-listening-true-false-json
          data-original-name="<?= esc($fieldName('listening_true_false_json')) ?>"
          <?= $showTrueFalseFields ? '' : 'disabled' ?>
        >
        <label class="form-label">Danh sách câu đúng / sai</label>
        <p class="course-field-hint session-listening-true-false-hint mb-2">
          <i class="bi bi-info-circle"></i>
          <?php if ($isPhatAmType): ?>
            Mỗi dòng là một câu — chọn đáp án đúng là <strong>Đúng</strong> hay <strong>Sai</strong>. Học viên đọc câu mẫu rồi chọn từng câu.
          <?php else: ?>
            Mỗi dòng là một câu phát — chọn đáp án đúng là <strong>Đúng</strong> hay <strong>Sai</strong>. Học sinh nghe MP3 rồi chọn từng câu.
          <?php endif ?>
        </p>
        <ol class="session-listening-true-false-list" data-session-listening-true-false-list>
          <?php foreach ($trueFalseItems as $tfIndex => $tfItem): ?>
            <?php
              $tfStatement = trim((string) ($tfItem['statement'] ?? ''));
              $tfAnswer    = ! empty($tfItem['answer']);
              $tfRowId     = 'listening-tf-' . $nameIndex . '-' . $tfIndex;
            ?>
            <li class="session-listening-true-false-item" data-session-listening-true-false-item>
              <span class="session-listening-true-false-badge"><?= esc((string) ((int) $tfIndex + 1)) ?></span>
              <input
                type="text"
                class="form-control form-control-sm session-listening-true-false-statement"
                data-session-listening-true-false-statement
                value="<?= esc($tfStatement) ?>"
                placeholder="VD: Học sinh đi học lúc 8 giờ sáng."
                <?= $showTrueFalseFields ? '' : 'disabled' ?>
              >
              <div class="session-listening-true-false-answer-toggle" role="group" aria-label="Đáp án đúng cho câu <?= esc((string) ((int) $tfIndex + 1)) ?>">
                <label class="session-listening-true-false-answer-option">
                  <input
                    type="radio"
                    name="<?= esc($tfRowId) ?>"
                    value="true"
                    data-session-listening-true-false-answer
                    <?= $tfAnswer ? 'checked' : '' ?>
                    <?= $showTrueFalseFields ? '' : 'disabled' ?>
                  >
                  <span>Đúng</span>
                </label>
                <label class="session-listening-true-false-answer-option">
                  <input
                    type="radio"
                    name="<?= esc($tfRowId) ?>"
                    value="false"
                    data-session-listening-true-false-answer
                    <?= ! $tfAnswer ? 'checked' : '' ?>
                    <?= $showTrueFalseFields ? '' : 'disabled' ?>
                  >
                  <span>Sai</span>
                </label>
              </div>
              <button
                class="btn sunny-mini session-listening-true-false-remove"
                type="button"
                data-session-listening-true-false-remove
                title="Xóa câu"
                <?= $showTrueFalseFields ? '' : 'disabled' ?>
                <?= count($trueFalseItems) <= 1 ? 'hidden' : '' ?>
              >
                <i class="bi bi-x-lg" aria-hidden="true"></i>
              </button>
            </li>
          <?php endforeach ?>
        </ol>
        <button class="btn btn-outline btn-sm session-listening-true-false-add mt-2" type="button" data-session-listening-true-false-add <?= $showTrueFalseFields ? '' : 'disabled' ?>>
          <i class="bi bi-plus-lg" aria-hidden="true"></i>
          Thêm câu
        </button>
      </div>

      <div
        class="session-listening-multi-choice-panel mt-1"
        data-session-listening-multi-choice-panel
        <?= $showMultiChoiceFields ? '' : 'hidden' ?>
      >
        <input
          type="hidden"
          name="<?= esc($fieldName('listening_multi_choice_json')) ?>"
          value="<?= esc($listeningMultiChoiceJson) ?>"
          data-session-listening-multi-choice-json
          data-original-name="<?= esc($fieldName('listening_multi_choice_json')) ?>"
          <?= $showMultiChoiceFields ? '' : 'disabled' ?>
        >
        <label class="form-label">Nhiều câu hỏi trắc nghiệm</label>
        <p class="course-field-hint session-listening-multi-choice-hint mb-2">
          <i class="bi bi-info-circle"></i>
          Mỗi khối = 1 câu hỏi kèm các đáp án (tối thiểu <strong>2</strong>, không bắt buộc 4). Bấm chữ cái để chọn đáp án đúng.
        </p>
        <ol class="session-listening-multi-choice-list" data-session-listening-multi-choice-list>
          <?php foreach ($multiChoiceItems as $mcIndex => $mcItem): ?>
            <?php
              $mcQuestion = trim((string) ($mcItem['question'] ?? ''));
              $mcOptions  = is_array($mcItem['options'] ?? null) ? array_values($mcItem['options']) : ['', ''];
              $mcCorrect  = strtolower(trim((string) ($mcItem['correct'] ?? 'a')));
              $mcOptionCount = min(max(2, count($mcOptions)), 8);
            ?>
            <li class="session-listening-multi-choice-item" data-session-listening-multi-choice-item>
              <div class="session-listening-multi-choice-item-head">
                <span class="session-listening-multi-choice-badge"><?= esc((string) ((int) $mcIndex + 1)) ?></span>
                <input
                  type="text"
                  class="form-control form-control-sm session-listening-multi-choice-question"
                  data-session-listening-multi-choice-question
                  value="<?= esc($mcQuestion) ?>"
                  placeholder="Nội dung câu hỏi con..."
                  <?= $showMultiChoiceFields ? '' : 'disabled' ?>
                >
                <button
                  class="btn sunny-mini session-listening-multi-choice-remove-item"
                  type="button"
                  data-session-listening-multi-choice-remove-item
                  title="Xóa câu"
                  <?= $showMultiChoiceFields ? '' : 'disabled' ?>
                  <?= count($multiChoiceItems) <= 1 ? 'hidden' : '' ?>
                >
                  <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
              </div>
              <div class="session-listening-multi-choice-options" data-session-listening-multi-choice-options>
                <?php for ($oi = 0; $oi < $mcOptionCount; $oi++): ?>
                  <?php
                    $optionKey = chr(97 + $oi);
                    $optionText = trim((string) ($mcOptions[$oi] ?? ''));
                  ?>
                  <div class="session-choice-option-row session-listening-mc-option-row" data-session-listening-mc-option-row data-option-key="<?= esc($optionKey) ?>">
                    <button
                      type="button"
                      class="session-choice-option-mark<?= $mcCorrect === $optionKey ? ' is-correct' : '' ?>"
                      data-session-listening-mc-mark="<?= esc($optionKey) ?>"
                      title="Đánh dấu đáp án đúng"
                      <?= $showMultiChoiceFields ? '' : 'disabled' ?>
                    ><?= esc(strtoupper($optionKey)) ?></button>
                    <input
                      class="form-control form-control-sm session-choice-option-input session-listening-mc-option-input"
                      type="text"
                      value="<?= esc($optionText) ?>"
                      placeholder="Lựa chọn <?= esc(strtoupper($optionKey)) ?>"
                      data-session-listening-mc-option
                      <?= $showMultiChoiceFields ? '' : 'disabled' ?>
                    >
                    <button
                      class="btn sunny-mini session-choice-option-remove session-listening-mc-option-remove"
                      type="button"
                      data-session-listening-mc-option-remove
                      title="Xóa lựa chọn"
                      <?= $showMultiChoiceFields ? '' : 'disabled' ?>
                      <?= $oi < 2 ? 'hidden' : '' ?>
                    >
                      <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                  </div>
                <?php endfor ?>
              </div>
              <?php if ($mcOptionCount < 8): ?>
                <button class="btn btn-outline btn-sm session-listening-mc-add-option mt-1" type="button" data-session-listening-mc-add-option <?= $showMultiChoiceFields ? '' : 'disabled' ?>>
                  <i class="bi bi-plus-lg" aria-hidden="true"></i>
                  Thêm lựa chọn
                </button>
              <?php endif ?>
            </li>
          <?php endforeach ?>
        </ol>
        <button class="btn btn-outline btn-sm session-listening-multi-choice-add mt-2" type="button" data-session-listening-multi-choice-add <?= $showMultiChoiceFields ? '' : 'disabled' ?>>
          <i class="bi bi-plus-lg" aria-hidden="true"></i>
          Thêm câu hỏi
        </button>
      </div>
    <?php endif ?>

    <?php if ($hasTypeToggle): ?>
      <div class="row g-3 mt-1" data-session-review-fill-answer <?= $showFillFields ? '' : 'hidden' ?>>
        <div class="col-12">
          <label class="form-label">
            <?= $isPracticeGroup ? 'Đáp án mẫu' : 'Đáp án đúng' ?>
            <span class="text-danger">*</span>
          </label>
          <?php if ($isPracticeGroup): ?>
            <textarea
              class="form-control richtext-model-answer"
              id="<?= esc($modelAnswerEditorId) ?>"
              rows="4"
              <?= $showFillFields ? 'name="' . esc($fieldName('correct_answer'), 'attr') . '"' : 'disabled' ?>
              data-original-name="<?= esc($fieldName('correct_answer')) ?>"
              placeholder="Nhập đáp án mẫu để học sinh đối chiếu sau khi nộp bài"
              data-session-fill-correct
            ><?= esc($showFillFields ? (string) $value('correct_answer') : '') ?></textarea>
          <?php else: ?>
            <input
              class="form-control form-control-sm"
              type="text"
              <?= $showFillFields ? 'name="' . esc($fieldName('correct_answer'), 'attr') . '"' : 'disabled' ?>
              data-original-name="<?= esc($fieldName('correct_answer')) ?>"
              value="<?= esc($showFillFields ? (string) $value('correct_answer') : '') ?>"
              placeholder="Nhập đáp án đúng cho câu điền từ"
              data-session-fill-correct
            >
          <?php endif ?>
        </div>
      </div>

      <?php if ($hasExtendedTypes): ?>
        <div class="session-memory-pairs-panel mt-1" data-session-memory-pairs-panel <?= $isMemoryMatchType ? '' : 'hidden' ?>>
          <input
            type="hidden"
            name="<?= esc($fieldName('memory_pairs_json')) ?>"
            value="<?= esc($memoryPairsJson) ?>"
            data-session-memory-pairs-json
            data-original-name="<?= esc($fieldName('memory_pairs_json')) ?>"
            <?= $isMemoryMatchType ? '' : 'disabled' ?>
          >
          <label class="form-label">Cặp lật ảnh – chữ</label>
          <p class="course-field-hint session-memory-pairs-hint mb-2">
            <i class="bi bi-info-circle"></i>
            <strong>Mỗi dòng = 1 cặp gồm 2 thẻ khác nhau:</strong> một thẻ <strong>ảnh</strong> (dán trực tiếp hoặc chèn ảnh) và một thẻ <strong>chữ giải thích</strong>.
            Học sinh lật bài và ghép đúng ảnh với chữ tương ứng. Cần tối thiểu <strong>2 cặp</strong> (4 thẻ trên bàn).
          </p>
          <div
            class="session-memory-pairs-list"
            data-session-memory-pairs-list
            data-session-pair-list="memory"
          >
            <?php foreach ($displayMemoryPairs as $pairIndex => $pair): ?>
              <?php
                $pairImage = trim((string) ($pair['image'] ?? ''));
                $pairLabel = trim((string) ($pair['label'] ?? ''));
              ?>
              <div class="session-memory-pair-item" data-session-memory-pair-item>
                <div class="session-memory-pair-row">
                  <span class="session-memory-pair-label" data-session-memory-pair-label>Cặp <?= esc((string) ((int) $pairIndex + 1)) ?></span>
                  <div class="session-memory-pair-fields">
                    <div class="session-memory-pair-field">
                      <label class="session-memory-pair-field-label">Ảnh</label>
                      <div class="session-pair-image-input-wrap" data-session-pair-image-wrap>
                        <div class="session-pair-image-input-row">
                          <input
                            type="text"
                            class="form-control form-control-sm session-pair-image-url session-memory-pair-input"
                            data-session-memory-pair-image
                            value="<?= esc($pairImage !== '' ? $pairBundleModel->resolveWordLinkMediaUrl($pairImage) : '') ?>"
                            placeholder="Dán ảnh (Ctrl+V) — tự lưu file và điền link"
                          >
                          <label class="btn btn-outline btn-sm session-pair-image-file-btn" title="Chọn ảnh từ máy">
                            <i class="bi bi-upload" aria-hidden="true"></i>
                            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="session-pair-image-file" hidden>
                          </label>
                        </div>
                        <p class="session-pair-image-hint" data-session-pair-image-status aria-live="polite"></p>
                      </div>
                    </div>
                    <div class="session-memory-pair-field">
                      <label class="session-memory-pair-field-label">Chữ / giải thích</label>
                      <input
                        class="form-control form-control-sm session-memory-pair-input"
                        type="text"
                        value="<?= esc($pairLabel) ?>"
                        placeholder="VD: Quả táo / Apple / Danh từ chỉ đồ vật..."
                        data-session-memory-pair-label-input
                      >
                    </div>
                  </div>
                  <div class="session-memory-pair-preview" data-session-memory-pair-preview<?= ($pairImage === '' && $pairLabel === '') ? ' hidden' : '' ?> title="Xem trước 2 thẻ của cặp">
                    <span class="session-memory-pair-preview-card session-memory-pair-preview-card--image<?= $pairImage === '' ? ' is-empty' : '' ?>">
                      <?php if ($pairImage !== ''): ?>
                        <img src="<?= esc($pairBundleModel->resolveWordLinkMediaUrl($pairImage)) ?>" alt="">
                      <?php else: ?>
                        <i class="bi bi-image" aria-hidden="true"></i>
                      <?php endif ?>
                    </span>
                    <span class="session-memory-pair-preview-card session-memory-pair-preview-card--text<?= $pairLabel === '' ? ' is-empty' : '' ?>">
                      <?= $pairLabel !== '' ? esc($pairLabel) : 'Chữ' ?>
                    </span>
                  </div>
                  <button class="btn sunny-mini btn-delete-user btn-remove-memory-pair" type="button" title="Xóa cặp">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                  </button>
                </div>
              </div>
            <?php endforeach ?>
          </div>
          <button
            class="btn sunny-mini btn-with-icon mt-2"
            type="button"
            data-add-memory-pair
            data-session-pair-add="memory"
          >
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Thêm cặp ảnh – chữ</span>
          </button>
        </div>

        <div class="session-word-link-pairs-panel mt-1" data-session-word-link-pairs-panel <?= $isWordLinkType ? '' : 'hidden' ?>>
          <input
            type="hidden"
            name="<?= esc($fieldName('word_link_pairs_json')) ?>"
            value="<?= esc($wordLinkPairsJson) ?>"
            data-session-word-link-pairs-json
            data-original-name="<?= esc($fieldName('word_link_pairs_json')) ?>"
            <?= $isWordLinkType ? '' : 'disabled' ?>
          >
          <label class="form-label">Cặp nối từ – ảnh và chữ</label>
          <p class="course-field-hint session-memory-pairs-hint mb-2">
            <i class="bi bi-info-circle"></i>
            <strong>Mỗi dòng = 1 cặp gồm 2 ô.</strong> Chọn từng ô là <strong>Ảnh</strong> (dán trực tiếp hoặc chèn ảnh) hoặc <strong>Chữ</strong>.
          </p>
          <div
            class="session-word-link-pairs-list"
            data-session-word-link-pairs-list
            data-session-pair-list="word_link"
          >
            <?php foreach ($wordLinkPairs as $pairIndex => $pair): ?>
              <?= view('study_programs/_session_word_link_pair_row', [
                'pair'      => is_array($pair) ? $pair : [],
                'pairIndex' => $pairIndex,
              ]) ?>
            <?php endforeach ?>
          </div>
          <button
            class="btn sunny-mini btn-with-icon mt-2"
            type="button"
            data-add-word-link-pair
            data-session-pair-add="word_link"
          >
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Thêm cặp nối từ</span>
          </button>
        </div>
      <?php endif ?>

      <?php if ($hasExtendedTypes): ?>
        <div class="session-word-order-panel mt-1" data-session-word-order-panel <?= $isWordOrderType ? '' : 'hidden' ?>>
          <input
            type="hidden"
            name="<?= esc($fieldName('word_order_tokens_json')) ?>"
            value="<?= esc($wordOrderTokensJson) ?>"
            data-session-word-order-json
            data-original-name="<?= esc($fieldName('word_order_tokens_json')) ?>"
            <?= $isWordOrderType ? '' : 'disabled' ?>
          >
          <label class="form-label">Các từ / cụm từ (đúng thứ tự câu)</label>
          <p class="course-field-hint session-word-order-hint mb-2">
            <i class="bi bi-info-circle"></i>
            Nhập từng từ hoặc cụm từ theo <strong>thứ tự câu đúng</strong>. Dán nhiều từ cùng lúc (cách nhau bằng <strong>/</strong> hoặc xuống dòng) · <strong>Enter</strong> để thêm nhanh.
          </p>
          <div class="session-word-order-builder">
            <div class="session-word-order-add-row">
              <input
                type="text"
                class="form-control form-control-sm session-word-order-input"
                data-session-word-order-input
                placeholder="VD: 저는 / 학생 / 입니다"
              >
              <button class="btn btn-outline btn-sm session-word-order-add-btn" type="button" data-session-word-order-add>
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Thêm từ
              </button>
            </div>
            <ol class="session-word-order-token-list" data-session-word-order-list>
              <?php foreach ($wordOrderTokens as $tokenIndex => $tokenText): ?>
                <?php
                  $tokenText = is_array($tokenText)
                      ? trim((string) ($tokenText['text'] ?? $tokenText['value'] ?? ''))
                      : trim((string) $tokenText);
                ?>
                <li class="session-word-order-token-item" data-session-word-order-item>
                  <span class="session-word-order-token-index"><?= esc((string) ((int) $tokenIndex + 1)) ?></span>
                  <input
                    type="text"
                    class="form-control form-control-sm session-word-order-token-text"
                    data-session-word-order-token-text
                    value="<?= esc($tokenText) ?>"
                    placeholder="Từ / cụm từ"
                  >
                  <div class="session-word-order-token-actions">
                    <button class="btn sunny-mini btn-outline" type="button" data-session-word-order-move="up" title="Lên"><i class="bi bi-arrow-up"></i></button>
                    <button class="btn sunny-mini btn-outline" type="button" data-session-word-order-move="down" title="Xuống"><i class="bi bi-arrow-down"></i></button>
                    <button class="btn sunny-mini btn-delete-user" type="button" data-session-word-order-remove title="Xóa"><i class="bi bi-trash"></i></button>
                  </div>
                </li>
              <?php endforeach ?>
            </ol>
            <div class="session-word-order-preview-box" data-session-word-order-preview<?= $wordOrderTokens === [] ? ' hidden' : '' ?>>
              <span class="session-word-order-preview-label">Xem trước câu đúng:</span>
              <p class="session-word-order-preview-text" data-session-word-order-preview-text><?= esc($wordOrderPreview) ?></p>
            </div>
          </div>
        </div>
      <?php endif ?>

      <?php if ($hasExtendedTypes): ?>
        <div class="session-category-sort-panel mt-1" data-session-category-sort-panel <?= $isCategorySortType ? '' : 'hidden' ?>>
          <input
            type="hidden"
            name="<?= esc($fieldName('category_sort_json')) ?>"
            value="<?= esc($categorySortJson) ?>"
            data-session-category-sort-json
            data-original-name="<?= esc($fieldName('category_sort_json')) ?>"
            <?= $isCategorySortType ? '' : 'disabled' ?>
          >
          <label class="form-label">Phân loại — kéo thẻ vào nhóm</label>
          <p class="course-field-hint session-category-sort-hint mb-2">
            <i class="bi bi-info-circle"></i>
            Tạo ít nhất <strong>2 nhóm</strong> (VD: Danh từ, Động từ) và <strong>2 thẻ</strong>. Chọn nhóm đúng cho từng thẻ — học sinh sẽ kéo thả để phân loại.
          </p>
          <div class="session-category-sort-builder">
            <div class="session-category-sort-section">
              <div class="session-category-sort-section-head">
                <strong>Nhóm phân loại</strong>
                <button class="btn btn-outline btn-sm" type="button" data-session-category-sort-add-group>
                  <i class="bi bi-plus-lg"></i> Thêm nhóm
                </button>
              </div>
              <ol class="session-category-sort-groups-list" data-session-category-sort-groups-list>
                <?php foreach ($categorySortGroups as $groupIndex => $groupRow): ?>
                  <li class="session-category-sort-group-item" data-session-category-sort-group-item data-group-id="<?= esc((string) ($groupRow['id'] ?? $groupIndex)) ?>">
                    <span class="session-category-sort-group-badge"><?= esc((string) ((int) $groupIndex + 1)) ?></span>
                    <input
                      type="text"
                      class="form-control form-control-sm session-category-sort-group-label"
                      data-session-category-sort-group-label
                      value="<?= esc((string) ($groupRow['label'] ?? '')) ?>"
                      placeholder="VD: Danh từ / Động từ / Tính từ"
                    >
                    <button class="btn sunny-mini btn-delete-user" type="button" data-session-category-sort-remove-group title="Xóa nhóm"><i class="bi bi-trash"></i></button>
                  </li>
                <?php endforeach ?>
              </ol>
            </div>
            <div class="session-category-sort-section">
              <div class="session-category-sort-section-head">
                <strong>Thẻ cần phân loại</strong>
                <button class="btn btn-outline btn-sm" type="button" data-session-category-sort-add-item>
                  <i class="bi bi-plus-lg"></i> Thêm thẻ
                </button>
              </div>
              <ol class="session-category-sort-items-list" data-session-category-sort-items-list>
                <?php foreach ($categorySortItems as $itemIndex => $itemRow): ?>
                  <li class="session-category-sort-item-row" data-session-category-sort-item>
                    <span class="session-category-sort-item-badge"><?= esc((string) ((int) $itemIndex + 1)) ?></span>
                    <input
                      type="text"
                      class="form-control form-control-sm session-category-sort-item-text"
                      data-session-category-sort-item-text
                      value="<?= esc((string) ($itemRow['text'] ?? '')) ?>"
                      placeholder="VD: 학교 / 가다"
                    >
                    <select class="form-select form-select-sm session-category-sort-item-group" data-session-category-sort-item-group>
                      <?php foreach ($categorySortGroups as $groupRow): ?>
                        <option value="<?= esc((string) ($groupRow['id'] ?? '')) ?>" <?= (string) ($itemRow['group_id'] ?? '') === (string) ($groupRow['id'] ?? '') ? 'selected' : '' ?>>
                          <?= esc((string) ($groupRow['label'] ?? 'Nhóm')) ?>
                        </option>
                      <?php endforeach ?>
                    </select>
                    <button class="btn sunny-mini btn-delete-user" type="button" data-session-category-sort-remove-item title="Xóa thẻ"><i class="bi bi-trash"></i></button>
                  </li>
                <?php endforeach ?>
              </ol>
            </div>
          </div>
        </div>
      <?php endif ?>

      <?php if ($hasExtendedTypes): ?>
        <div class="session-fill-blanks-panel mt-1" data-session-fill-blanks-panel <?= $isFillBlanksType ? '' : 'hidden' ?>>
          <input
            type="hidden"
            name="<?= esc($fieldName('fill_blanks_json')) ?>"
            value="<?= esc($fillBlanksJson) ?>"
            data-session-fill-blanks-json
            data-original-name="<?= esc($fieldName('fill_blanks_json')) ?>"
            <?= $isFillBlanksType ? '' : 'disabled' ?>
          >
          <label class="form-label">Câu có nhiều chỗ trống</label>
          <p class="course-field-hint session-fill-blanks-hint mb-2">
            <i class="bi bi-info-circle"></i>
            Gõ câu hoàn chỉnh và dùng <strong>___</strong> (3 gạch dưới) cho mỗi chỗ trống. Bấm <strong>F2</strong> hoặc nút bên cạnh để chèn chỗ trống — hệ thống tự tạo ô đáp án.
          </p>
          <div class="session-fill-blanks-template-row">
            <textarea
              class="form-control form-control-sm session-fill-blanks-template"
              rows="3"
              data-session-fill-blanks-template
              placeholder="VD: Tôi ___ đi học lúc ___ giờ mỗi ngày."
              <?= $isFillBlanksType ? '' : 'disabled' ?>
            ><?= esc($fillBlanksTemplate) ?></textarea>
            <button class="btn btn-outline btn-sm session-fill-blanks-insert" type="button" data-session-fill-blanks-insert <?= $isFillBlanksType ? '' : 'disabled' ?>>
              <i class="bi bi-plus-lg"></i> Chèn ___
            </button>
          </div>
          <div class="session-fill-blanks-answers-wrap mt-2" data-session-fill-blanks-answers-wrap>
            <label class="form-label form-label-sm mb-1">Đáp án từng ô (theo thứ tự ___)</label>
            <ol class="session-fill-blanks-answers-list" data-session-fill-blanks-answers-list>
              <?php
                $blankCount = $pairBundleModel->countFillBlanksInTemplate($fillBlanksTemplate);
                if ($blankCount < 1) {
                    $blankCount = max(count($fillBlanksAnswers), 2);
                }
                for ($blankIndex = 0; $blankIndex < $blankCount; $blankIndex++):
              ?>
                <li class="session-fill-blanks-answer-item" data-session-fill-blanks-answer-item>
                  <span class="session-fill-blanks-answer-no">Ô <?= esc((string) ($blankIndex + 1)) ?></span>
                  <input
                    type="text"
                    class="form-control form-control-sm session-fill-blanks-answer-input"
                    data-session-fill-blanks-answer-input
                    value="<?= esc((string) ($fillBlanksAnswers[$blankIndex] ?? '')) ?>"
                    placeholder="Đáp án cho ô <?= esc((string) ($blankIndex + 1)) ?>"
                    <?= $isFillBlanksType ? '' : 'disabled' ?>
                  >
                </li>
              <?php endfor ?>
            </ol>
          </div>
          <div class="session-fill-blanks-preview-box mt-2" data-session-fill-blanks-preview>
            <span class="session-fill-blanks-preview-label">Xem trước (học sinh sẽ thấy ô trống):</span>
            <p class="session-fill-blanks-preview-text" data-session-fill-blanks-preview-text></p>
          </div>
        </div>

        <div class="session-word-select-panel mt-1" data-session-word-select-panel <?= $isWordSelectType ? '' : 'hidden' ?>>
          <input
            type="hidden"
            name="<?= esc($fieldName('word_select_json')) ?>"
            value="<?= esc($wordSelectJson) ?>"
            data-session-word-select-json
            data-original-name="<?= esc($fieldName('word_select_json')) ?>"
            <?= $isWordSelectType ? '' : 'disabled' ?>
          >
          <label class="form-label">Chọn từ trong danh sách</label>
          <p class="course-field-hint session-word-select-hint mb-2">
            <i class="bi bi-info-circle"></i>
            Gõ câu với <strong>___</strong> cho mỗi chỗ trống. Bấm <strong>F2</strong> để chèn chỗ trống nhanh. Học sinh chọn từ trong danh sách — không gõ tự do.
          </p>
          <div class="session-word-select-template-row">
            <textarea
              class="form-control form-control-sm session-word-select-template"
              rows="3"
              data-session-word-select-template
              placeholder="VD: Tôi ___ đi học lúc ___ giờ mỗi ngày."
              <?= $isWordSelectType ? '' : 'disabled' ?>
            ><?= esc($wordSelectTemplate) ?></textarea>
            <button class="btn btn-outline btn-sm session-word-select-insert" type="button" data-session-word-select-insert <?= $isWordSelectType ? '' : 'disabled' ?>>
              <i class="bi bi-plus-lg"></i> Chèn ___
            </button>
          </div>
          <div class="session-word-select-answers-wrap mt-2" data-session-word-select-answers-wrap>
            <label class="form-label form-label-sm mb-1">Đáp án đúng từng ô (theo thứ tự ___)</label>
            <ol class="session-word-select-answers-list" data-session-word-select-answers-list>
              <?php
                $wordSelectBlankCount = $pairBundleModel->countFillBlanksInTemplate($wordSelectTemplate);
                if ($wordSelectBlankCount < 1) {
                    $wordSelectBlankCount = max(count($wordSelectAnswers), 2);
                }
                for ($blankIndex = 0; $blankIndex < $wordSelectBlankCount; $blankIndex++):
              ?>
                <li class="session-word-select-answer-item" data-session-word-select-answer-item>
                  <span class="session-word-select-answer-no">Ô <?= esc((string) ($blankIndex + 1)) ?></span>
                  <input
                    type="text"
                    class="form-control form-control-sm session-word-select-answer-input"
                    data-session-word-select-answer-input
                    value="<?= esc((string) ($wordSelectAnswers[$blankIndex] ?? '')) ?>"
                    placeholder="Đáp án cho ô <?= esc((string) ($blankIndex + 1)) ?>"
                    <?= $isWordSelectType ? '' : 'disabled' ?>
                  >
                </li>
              <?php endfor ?>
            </ol>
          </div>
          <div class="session-word-select-bank-wrap mt-2" data-session-word-select-bank-wrap>
            <div class="session-word-select-bank-head">
              <label class="form-label form-label-sm mb-0">Danh sách từ cho học sinh chọn</label>
              <div class="session-word-select-bank-actions">
                <button class="btn btn-outline btn-sm" type="button" data-session-word-select-sync-bank <?= $isWordSelectType ? '' : 'disabled' ?>>
                  <i class="bi bi-arrow-repeat"></i> Lấy từ đáp án
                </button>
                <button class="btn btn-outline btn-sm" type="button" data-session-word-select-add-word <?= $isWordSelectType ? '' : 'disabled' ?>>
                  <i class="bi bi-plus-lg"></i> Thêm từ
                </button>
              </div>
            </div>
            <p class="course-field-hint session-word-select-bank-hint mb-2">Thêm đáp án đúng và từ nhiễu — học sinh chỉ chọn trong danh sách này.</p>
            <ul class="session-word-select-bank-list" data-session-word-select-bank-list>
              <?php if ($wordSelectBank === []): ?>
                <li class="session-word-select-bank-item" data-session-word-select-bank-item>
                  <input type="text" class="form-control form-control-sm session-word-select-bank-input" data-session-word-select-bank-input value="" placeholder="Từ trong danh sách" <?= $isWordSelectType ? '' : 'disabled' ?>>
                  <button class="btn sunny-mini btn-delete-user" type="button" data-session-word-select-remove-word title="Xóa từ"><i class="bi bi-trash"></i></button>
                </li>
              <?php else: ?>
                <?php foreach ($wordSelectBank as $bankWord): ?>
                  <li class="session-word-select-bank-item" data-session-word-select-bank-item>
                    <input type="text" class="form-control form-control-sm session-word-select-bank-input" data-session-word-select-bank-input value="<?= esc((string) $bankWord) ?>" placeholder="Từ trong danh sách" <?= $isWordSelectType ? '' : 'disabled' ?>>
                    <button class="btn sunny-mini btn-delete-user" type="button" data-session-word-select-remove-word title="Xóa từ"><i class="bi bi-trash"></i></button>
                  </li>
                <?php endforeach ?>
              <?php endif ?>
            </ul>
          </div>
          <div class="session-word-select-preview-box mt-2" data-session-word-select-preview>
            <span class="session-word-select-preview-label">Xem trước:</span>
            <p class="session-word-select-preview-text" data-session-word-select-preview-text></p>
            <div class="session-word-select-preview-bank" data-session-word-select-preview-bank></div>
          </div>
        </div>

        <div class="session-word-translate-panel mt-1" data-session-word-translate-panel <?= $isWordTranslateType ? '' : 'hidden' ?>>
          <input
            type="hidden"
            name="<?= esc($fieldName('word_translate_json')) ?>"
            value="<?= esc($wordTranslateJson) ?>"
            data-session-word-translate-json
            data-original-name="<?= esc($fieldName('word_translate_json')) ?>"
            <?= $isWordTranslateType ? '' : 'disabled' ?>
          >
          <label class="form-label">Dịch từ</label>
          <p class="course-field-hint session-word-translate-hint mb-2">
            <i class="bi bi-info-circle"></i>
            Thêm nhiều mục cần dịch. Mỗi dòng gồm <strong>từ gốc</strong> (chữ hoặc ảnh) và <strong>đáp án mẫu</strong> — giáo viên sẽ chấm bài dịch của học viên.
          </p>

          <div class="session-word-translate-layout-options">
            <div class="session-word-translate-layout-group">
              <span class="session-word-translate-layout-label">Hiển thị</span>
              <label class="session-word-translate-layout-option">
                <input type="radio" name="word_translate_columns_<?= esc((string) $nameIndex) ?>" value="1" data-session-word-translate-columns-radio <?= $wordTranslateColumns === 1 ? 'checked' : '' ?> <?= $isWordTranslateType ? '' : 'disabled' ?>>
                <span>1 cột</span>
              </label>
              <label class="session-word-translate-layout-option">
                <input type="radio" name="word_translate_columns_<?= esc((string) $nameIndex) ?>" value="2" data-session-word-translate-columns-radio <?= $wordTranslateColumns === 2 ? 'checked' : '' ?> <?= $isWordTranslateType ? '' : 'disabled' ?>>
                <span>2 cột</span>
              </label>
            </div>
            <div class="session-word-translate-layout-group">
              <span class="session-word-translate-layout-label">Ô dịch</span>
              <label class="session-word-translate-layout-option">
                <input type="radio" name="word_translate_layout_<?= esc((string) $nameIndex) ?>" value="horizontal" data-session-word-translate-layout-radio <?= $wordTranslateLayout === 'horizontal' ? 'checked' : '' ?> <?= $isWordTranslateType ? '' : 'disabled' ?>>
                <span>Nằm ngang</span>
              </label>
              <label class="session-word-translate-layout-option">
                <input type="radio" name="word_translate_layout_<?= esc((string) $nameIndex) ?>" value="vertical" data-session-word-translate-layout-radio <?= $wordTranslateLayout === 'vertical' ? 'checked' : '' ?> <?= $isWordTranslateType ? '' : 'disabled' ?>>
                <span>Trên / dưới</span>
              </label>
            </div>
          </div>

          <ol class="session-word-translate-items-list" data-session-word-translate-items-list>
            <?php foreach ($wordTranslateItems as $itemIndex => $itemRow): ?>
              <?= view('study_programs/_session_word_translate_item_row', [
                'item'       => $itemRow,
                'itemIndex'  => $itemIndex,
                'disabled'   => ! $isWordTranslateType,
              ]) ?>
            <?php endforeach ?>
          </ol>

          <div class="session-word-translate-actions mt-2">
            <button class="btn btn-outline btn-sm" type="button" data-session-word-translate-add-item <?= $isWordTranslateType ? '' : 'disabled' ?>>
              <i class="bi bi-plus-lg"></i> Thêm từ
            </button>
          </div>

          <div class="session-word-translate-preview-box mt-2" data-session-word-translate-preview>
            <span class="session-word-translate-preview-label">Xem trước:</span>
            <div class="session-word-translate-preview-grid" data-session-word-translate-preview-grid></div>
          </div>
        </div>

        <div class="session-image-hotspot-panel mt-1" data-session-image-hotspot-panel <?= $isImageHotspotType ? '' : 'hidden' ?>>
          <input
            type="hidden"
            name="<?= esc($fieldName('image_hotspot_json')) ?>"
            value="<?= esc($imageHotspotJson) ?>"
            data-session-image-hotspot-json
            data-original-name="<?= esc($fieldName('image_hotspot_json')) ?>"
            <?= $isImageHotspotType ? '' : 'disabled' ?>
          >
          <label class="form-label">Click điểm trên ảnh</label>
          <p class="course-field-hint session-image-hotspot-hint mb-2">
            <i class="bi bi-info-circle"></i>
            Tải ảnh, rồi <strong>bấm lên ảnh</strong> để đánh dấu vị trí đúng. Tọa độ lưu theo <strong>%</strong> (0–100). Học sinh cũng bấm chọn trên ảnh.
          </p>
          <div class="session-image-hotspot-image-field session-pair-image-input-wrap" data-session-pair-image-wrap data-session-image-hotspot-image-wrap>
            <div class="session-pair-image-input-row">
              <input
                type="text"
                class="form-control form-control-sm session-pair-image-url"
                data-session-image-hotspot-image
                value="<?= esc($imageHotspotDisplayUrl) ?>"
                placeholder="Dán ảnh (Ctrl+V) — tự lưu file và điền link"
                <?= $isImageHotspotType ? '' : 'disabled' ?>
              >
              <label class="btn btn-outline btn-sm session-pair-image-file-btn" title="Chọn ảnh từ máy">
                <i class="bi bi-upload" aria-hidden="true"></i>
                <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="session-pair-image-file" hidden <?= $isImageHotspotType ? '' : 'disabled' ?>>
              </label>
            </div>
            <p class="session-pair-image-hint" data-session-pair-image-status aria-live="polite"></p>
          </div>
          <div class="session-image-hotspot-meta mt-2">
            <label class="form-label form-label-sm" for="image-hotspot-tolerance-<?= esc((string) $nameIndex) ?>">Sai số cho phép (%)</label>
            <div class="session-image-hotspot-tolerance-row">
              <input
                type="range"
                class="form-range session-image-hotspot-tolerance"
                id="image-hotspot-tolerance-<?= esc((string) $nameIndex) ?>"
                data-session-image-hotspot-tolerance
                min="3"
                max="25"
                step="0.5"
                value="<?= esc((string) $imageHotspotTolerance) ?>"
                <?= $isImageHotspotType ? '' : 'disabled' ?>
              >
              <output class="session-image-hotspot-tolerance-value" data-session-image-hotspot-tolerance-value><?= esc((string) $imageHotspotTolerance) ?>%</output>
            </div>
          </div>
          <div class="session-image-hotspot-canvas-wrap mt-2">
            <div class="session-image-hotspot-canvas<?= $imageHotspotDisplayUrl === '' ? ' is-empty' : '' ?>" data-session-image-hotspot-canvas>
              <img
                class="session-image-hotspot-preview-img"
                data-session-image-hotspot-preview-img
                src="<?= esc($imageHotspotDisplayUrl) ?>"
                alt="Ảnh câu hỏi click điểm"
                <?= $imageHotspotDisplayUrl === '' ? 'hidden' : '' ?>
              >
              <div class="session-image-hotspot-overlay" data-session-image-hotspot-overlay></div>
              <p class="session-image-hotspot-canvas-empty" data-session-image-hotspot-empty>Tải ảnh để bắt đầu đánh dấu vị trí</p>
            </div>
          </div>
          <div class="session-image-hotspot-targets-wrap mt-2">
            <div class="session-image-hotspot-targets-head">
              <label class="form-label form-label-sm mb-0">Các vị trí đúng (tọa độ %)</label>
              <button class="btn btn-outline btn-sm" type="button" data-session-image-hotspot-clear-targets <?= $isImageHotspotType ? '' : 'disabled' ?>>
                <i class="bi bi-x-lg"></i> Xóa hết điểm
              </button>
            </div>
            <ol class="session-image-hotspot-target-list" data-session-image-hotspot-target-list>
              <?php if ($imageHotspotTargets === []): ?>
                <li class="session-image-hotspot-target-empty muted">Chưa có điểm — bấm lên ảnh để thêm.</li>
              <?php else: ?>
                <?php foreach ($imageHotspotTargets as $targetIndex => $targetRow): ?>
                  <li class="session-image-hotspot-target-item" data-session-image-hotspot-target-item>
                    <span class="session-image-hotspot-target-no"><?= esc((string) ($targetIndex + 1)) ?></span>
                    <input
                      type="text"
                      class="form-control form-control-sm session-image-hotspot-target-label"
                      data-session-image-hotspot-target-label
                      value="<?= esc((string) ($targetRow['label'] ?? ('Vị trí ' . ($targetIndex + 1)))) ?>"
                      <?= $isImageHotspotType ? '' : 'disabled' ?>
                    >
                    <span class="session-image-hotspot-target-coords"><?= esc((string) ($targetRow['x'] ?? 0)) ?>%, <?= esc((string) ($targetRow['y'] ?? 0)) ?>%</span>
                    <button class="btn sunny-mini btn-delete-user" type="button" data-session-image-hotspot-remove-target title="Xóa điểm"><i class="bi bi-trash"></i></button>
                  </li>
                <?php endforeach ?>
              <?php endif ?>
            </ol>
          </div>
        </div>

        <div class="session-image-label-drop-panel mt-1" data-session-image-label-drop-panel <?= $isImageLabelDropType ? '' : 'hidden' ?>>
          <input
            type="hidden"
            name="<?= esc($fieldName('image_label_drop_json')) ?>"
            value="<?= esc($imageLabelDropJson) ?>"
            data-session-image-label-drop-json
            data-original-name="<?= esc($fieldName('image_label_drop_json')) ?>"
            <?= $isImageLabelDropType ? '' : 'disabled' ?>
          >
          <label class="form-label">Kéo nhãn vào vùng trên ảnh</label>
          <p class="course-field-hint session-image-label-drop-hint mb-2">
            <i class="bi bi-info-circle"></i>
            Tải ảnh, <strong>bấm lên ảnh</strong> để thêm vùng thả, rồi thêm nhãn chữ và chọn vùng đúng cho từng nhãn. Học sinh sẽ kéo nhãn vào vùng trên ảnh.
          </p>
          <div class="session-image-label-drop-image-field session-pair-image-input-wrap" data-session-pair-image-wrap data-session-image-label-drop-image-wrap>
            <div class="session-pair-image-input-row">
              <input
                type="text"
                class="form-control form-control-sm session-pair-image-url"
                data-session-image-label-drop-image
                value="<?= esc($imageLabelDropDisplayUrl) ?>"
                placeholder="Dán ảnh (Ctrl+V) — tự lưu file và điền link"
                <?= $isImageLabelDropType ? '' : 'disabled' ?>
              >
              <label class="btn btn-outline btn-sm session-pair-image-file-btn" title="Chọn ảnh từ máy">
                <i class="bi bi-upload" aria-hidden="true"></i>
                <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="session-pair-image-file" hidden <?= $isImageLabelDropType ? '' : 'disabled' ?>>
              </label>
            </div>
            <p class="session-pair-image-hint" data-session-pair-image-status aria-live="polite"></p>
          </div>
          <div class="session-image-label-drop-meta mt-2">
            <label class="form-label form-label-sm" for="image-label-drop-radius-<?= esc((string) $nameIndex) ?>">Kích thước vùng thả (%)</label>
            <div class="session-image-label-drop-radius-row">
              <input
                type="range"
                class="form-range session-image-label-drop-radius"
                id="image-label-drop-radius-<?= esc((string) $nameIndex) ?>"
                data-session-image-label-drop-radius
                min="5"
                max="25"
                step="0.5"
                value="<?= esc((string) $imageLabelDropRadius) ?>"
                <?= $isImageLabelDropType ? '' : 'disabled' ?>
              >
              <output class="session-image-label-drop-radius-value" data-session-image-label-drop-radius-value><?= esc((string) $imageLabelDropRadius) ?>%</output>
            </div>
          </div>
          <div class="session-image-label-drop-canvas-wrap mt-2">
            <div class="session-image-label-drop-canvas<?= $imageLabelDropDisplayUrl === '' ? ' is-empty' : '' ?>" data-session-image-label-drop-canvas>
              <img
                class="session-image-label-drop-preview-img"
                data-session-image-label-drop-preview-img
                src="<?= esc($imageLabelDropDisplayUrl) ?>"
                alt="Ảnh câu hỏi kéo nhãn"
                <?= $imageLabelDropDisplayUrl === '' ? 'hidden' : '' ?>
              >
              <div class="session-image-label-drop-overlay" data-session-image-label-drop-overlay></div>
              <p class="session-image-label-drop-canvas-empty" data-session-image-label-drop-empty>Tải ảnh rồi bấm để thêm vùng thả</p>
            </div>
          </div>
          <div class="session-image-label-drop-zones-wrap mt-2">
            <div class="session-image-label-drop-section-head">
              <label class="form-label form-label-sm mb-0">Các vùng trên ảnh</label>
              <button class="btn btn-outline btn-sm" type="button" data-session-image-label-drop-clear-zones <?= $isImageLabelDropType ? '' : 'disabled' ?>>
                <i class="bi bi-x-lg"></i> Xóa hết vùng
              </button>
            </div>
            <ol class="session-image-label-drop-zone-list" data-session-image-label-drop-zone-list>
              <?php if ($imageLabelDropZones === []): ?>
                <li class="session-image-label-drop-zone-empty muted">Chưa có vùng — bấm lên ảnh để thêm.</li>
              <?php else: ?>
                <?php foreach ($imageLabelDropZones as $zoneIndex => $zoneRow): ?>
                  <li class="session-image-label-drop-zone-item" data-session-image-label-drop-zone-item>
                    <span class="session-image-label-drop-zone-no"><?= esc((string) ($zoneIndex + 1)) ?></span>
                    <input
                      type="text"
                      class="form-control form-control-sm session-image-label-drop-zone-label"
                      data-session-image-label-drop-zone-label
                      value="<?= esc((string) ($zoneRow['label'] ?? ('Vùng ' . ($zoneIndex + 1)))) ?>"
                      <?= $isImageLabelDropType ? '' : 'disabled' ?>
                    >
                    <span class="session-image-label-drop-zone-coords"><?= esc((string) ($zoneRow['x'] ?? 0)) ?>%, <?= esc((string) ($zoneRow['y'] ?? 0)) ?>%</span>
                    <button class="btn sunny-mini btn-delete-user" type="button" data-session-image-label-drop-remove-zone title="Xóa vùng"><i class="bi bi-trash"></i></button>
                  </li>
                <?php endforeach ?>
              <?php endif ?>
            </ol>
          </div>
          <div class="session-image-label-drop-labels-wrap mt-2">
            <div class="session-image-label-drop-section-head">
              <label class="form-label form-label-sm mb-0">Nhãn chữ (kéo vào vùng)</label>
              <button class="btn btn-outline btn-sm" type="button" data-session-image-label-drop-add-label <?= $isImageLabelDropType ? '' : 'disabled' ?>>
                <i class="bi bi-plus-lg"></i> Thêm nhãn
              </button>
            </div>
            <ol class="session-image-label-drop-label-list" data-session-image-label-drop-label-list>
              <?php if ($imageLabelDropLabels === []): ?>
                <li class="session-image-label-drop-label-empty muted">Thêm nhãn chữ để học sinh kéo vào vùng trên ảnh.</li>
              <?php else: ?>
                <?php foreach ($imageLabelDropLabels as $labelIndex => $labelRow): ?>
                  <li class="session-image-label-drop-label-item" data-session-image-label-drop-label-item>
                    <span class="session-image-label-drop-label-no"><?= esc((string) ($labelIndex + 1)) ?></span>
                    <input
                      type="text"
                      class="form-control form-control-sm session-image-label-drop-label-text"
                      data-session-image-label-drop-label-text
                      value="<?= esc((string) ($labelRow['text'] ?? '')) ?>"
                      placeholder="Nhãn chữ"
                      <?= $isImageLabelDropType ? '' : 'disabled' ?>
                    >
                    <select class="form-select form-select-sm session-image-label-drop-label-zone" data-session-image-label-drop-label-zone <?= $isImageLabelDropType ? '' : 'disabled' ?>>
                      <?php foreach ($imageLabelDropZones as $zonePickIndex => $zonePickRow): ?>
                        <option value="<?= esc((string) ($zonePickRow['id'] ?? '')) ?>" <?= (string) ($labelRow['zone_id'] ?? '') === (string) ($zonePickRow['id'] ?? '') ? 'selected' : '' ?>>
                          <?= esc((string) ($zonePickIndex + 1)) ?>. <?= esc((string) ($zonePickRow['label'] ?? ('Vùng ' . ($zonePickIndex + 1)))) ?>
                        </option>
                      <?php endforeach ?>
                    </select>
                    <button class="btn sunny-mini btn-delete-user" type="button" data-session-image-label-drop-remove-label title="Xóa nhãn"><i class="bi bi-trash"></i></button>
                  </li>
                <?php endforeach ?>
              <?php endif ?>
            </ol>
          </div>
        </div>
      <?php endif ?>

      <div class="mb-0 mt-3" data-session-review-explanation <?= $isPhatAmType ? 'hidden' : '' ?>>
        <label class="form-label">
          Giải thích đáp án<?= $isPracticeGroup ? '' : ' đúng' ?>
          <span class="text-muted">(tuỳ chọn)</span>
        </label>
        <textarea
          class="form-control richtext-explanation"
          id="<?= esc($editorId) ?>"
          name="<?= esc($fieldName('answer_explanation')) ?>"
          rows="6"
          data-original-name="<?= esc($fieldName('answer_explanation')) ?>"
          placeholder="<?= $isPracticeGroup ? 'Giải thích chi tiết — học sinh xem sau khi nộp bài để đối chiếu.' : 'Giải thích chi tiết giúp học sinh hiểu vì sao đáp án này đúng...' ?>"
          <?= $isPhatAmType ? 'disabled' : '' ?>
        ><?= esc($value('answer_explanation')) ?></textarea>
      </div>
    <?php endif ?>
  </div>
</article>
