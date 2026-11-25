<?php

namespace App\Support\Questions;

use App\Support\HtmlContent;
use App\Support\MediaUrl;

class QuestionParser
{
    public const TYPES = [
        'fill',
        'choice',
        'essay',
        'multi_select',
        'memory_match',
        'word_link',
        'word_order',
        'listening',
        'category_sort',
        'fill_blanks',
        'word_select',
        'word_translate',
        'phat_am',
        'image_hotspot',
        'image_label_drop',
    ];

    public const GROUPS = [
        'pre' => 'Bài tập trước buổi',
        'review' => 'Kiểm tra bài cũ',
        'quiz' => 'Trắc nghiệm',
        'post_practice' => 'Ôn tập sau buổi',
    ];

    public const GROUP_SLUGS = [
        'pre' => 'truoc-buoi',
        'review' => 'kiem-tra',
        'quiz' => 'trac-nghiem',
        'post_practice' => 'on-tap',
    ];

    public const TYPE_LABELS = [
        'fill' => 'Tự luận',
        'choice' => 'Trắc nghiệm',
        'essay' => 'Tự luận',
        'multi_select' => 'Nhiều đáp án',
        'memory_match' => 'Lật ảnh',
        'word_link' => 'Nối từ',
        'word_order' => 'Sắp xếp câu',
        'listening' => 'Nghe đáp',
        'category_sort' => 'Phân loại',
        'fill_blanks' => 'Nhiều chỗ trống',
        'word_select' => 'Chọn từ',
        'word_translate' => 'Dịch từ',
        'phat_am' => 'Phát âm',
        'image_hotspot' => 'Click ảnh',
        'image_label_drop' => 'Kéo nhãn ảnh',
    ];

    public static function plainTextFromHtml(?string $html): string
    {
        $html = (string) $html;

        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/\s*(p|div|li|tr|h[1-6])\s*>/i', "\n", $html) ?? $html;

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
        $text = trim($text);

        return self::stripLiveQuestionGroupLabel($text);
    }

public static function stripLiveQuestionGroupLabel(string $text): string
    {
        return trim((string) (preg_replace(
            '/^\[(?:Kiểm tra bài cũ|Trắc nghiệm trong buổi|Ôn tập sau buổi|Bài tập trước buổi)\]\s*/u',
            '',
            $text
        ) ?? $text));
    }

public function resolveQuestionFromPost(array $item, string $group, ?array $existingRow = null): array
    {
        $parsed = $this->parseQuestionRow($item, $group, $existingRow);

        if ($parsed !== null) {
            return $parsed;
        }

        return $this->buildPartialQuestionData($item, $group, $existingRow);
    }

private function buildPartialQuestionData(array $item, string $group, ?array $existingRow = null): array
    {
        $type = (string) ($item['question_type'] ?? 'choice');

        if ($group === 'quiz') {
            $type = 'choice';
        }

        if ($type === 'text') {
            $type = 'fill';
        }

        if (! in_array($type, ['fill', 'choice', 'multi_select', 'memory_match', 'word_link', 'word_order', 'listening', 'category_sort', 'fill_blanks', 'word_select', 'word_translate', 'phat_am', 'image_hotspot', 'image_label_drop'], true)) {
            $type = 'choice';
        }

        if ($group === 'quiz' && ($this->isPairMatchType($type) || $this->isWordOrderType($type) || $this->isListeningType($type) || $this->isCategorySortType($type) || $this->isFillBlanksType($type) || $this->isWordSelectType($type) || $this->isWordTranslateType($type) || $this->isPhatAmType($type) || $this->isImageHotspotType($type) || $this->isImageLabelDropType($type))) {
            $type = 'choice';
        }

        $options     = $this->collectOptions($item);
        $pairBundle  = $this->mergePairBundleFromPost($item, $existingRow);
        $optionsJson = $this->resolveOptionsJson($type, $options, $pairBundle, $existingRow);

        $correctAnswer     = trim((string) ($item['correct_answer'] ?? ''));
        $answerExplanation = trim((string) ($item['answer_explanation'] ?? ''));

        $listeningMode = $this->isListeningType($type)
            ? ($pairBundle['listening']['answer_mode'] ?? 'choice')
            : null;
        $phatAmMode = $this->isPhatAmType($type)
            ? ($pairBundle['phat_am']['answer_mode'] ?? 'choice')
            : null;

        if (($type === 'choice'
                || ($this->isListeningType($type) && $listeningMode === 'choice'))
            && in_array($group, ['review', 'post_practice', 'pre'], true)
            && $correctAnswer !== '') {
            $correctAnswer = strtolower($correctAnswer);

            if (! in_array($correctAnswer, ['a', 'b', 'c', 'd'], true)) {
                $correctAnswer = '';
            }
        } elseif ($type === 'multi_select'
            && in_array($group, ['review', 'post_practice', 'pre'], true)
            && $correctAnswer !== '') {
            $correctAnswer = $this->normalizeMultiSelectAnswer($correctAnswer);
        } elseif (($type === 'fill'
                || ($this->isListeningType($type) && $listeningMode === 'fill'))
            && in_array($group, ['review', 'post_practice', 'pre'], true)) {
            // keep essay / fill model answer
        } elseif ($type !== 'fill' || ! in_array($group, ['review', 'post_practice', 'pre'], true)) {
            $correctAnswer = '';
        }

        if ($group === 'quiz') {
            $correctAnswer     = null;
            $answerExplanation = null;
        } else {
            $correctAnswer     = $correctAnswer !== '' ? $correctAnswer : null;
            $answerExplanation = $answerExplanation !== '' ? $answerExplanation : null;
        }

        return [
            'question_text'      => (string) ($item['question_text'] ?? ''),
            'question_type'      => $type,
            'options_json'       => $optionsJson,
            'correct_answer'     => $correctAnswer,
            'answer_explanation' => $answerExplanation,
        ];
    }

private function groupedHasQuestionPayload(array $groupedQuestions): bool
    {
        foreach (['review', 'quiz', 'post_practice', 'pre'] as $group) {
            $items = $groupedQuestions[$group] ?? [];

            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (trim(strip_tags((string) ($item['question_text'] ?? ''))) !== '') {
                    return true;
                }

                if (trim(strip_tags((string) ($item['answer_explanation'] ?? ''))) !== '') {
                    return true;
                }

                if (trim((string) ($item['correct_answer'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['memory_pairs_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['word_order_tokens_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['word_link_pairs_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['listening_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['listening_audio_saved'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['listening_text'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['listening_choice_question'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['listening_true_false_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['listening_multi_choice_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['category_sort_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['fill_blanks_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['word_select_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['word_translate_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['phat_am_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['phat_am_prompt'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['phat_am_choice_question'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['image_hotspot_json'] ?? '')) !== '') {
                    return true;
                }

                if (trim((string) ($item['image_label_drop_json'] ?? '')) !== '') {
                    return true;
                }

                if (isset($item['options']) && is_array($item['options'])) {
                    foreach ($item['options'] as $option) {
                        if (trim((string) $option) !== '') {
                            return true;
                        }
                    }
                }

                if (isset($item['memory_pairs']) && is_array($item['memory_pairs'])) {
                    foreach ($item['memory_pairs'] as $pair) {
                        if (! is_array($pair)) {
                            continue;
                        }

                        if (trim((string) ($pair['image'] ?? '')) !== '' || trim((string) ($pair['label'] ?? '')) !== '') {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

public function parseQuestionRow(array $item, string $group, ?array $existingRow = null): ?array
    {
        $question          = trim((string) ($item['question_text'] ?? ''));
        $questionPlain     = trim(strip_tags($question));
        $answerExplanation = trim((string) ($item['answer_explanation'] ?? ''));
        $correctAnswer     = trim((string) ($item['correct_answer'] ?? ''));
        $options           = $this->collectOptions($item);
        $type              = (string) ($item['question_type'] ?? 'choice');

        if ($group === 'quiz') {
            $type = 'choice';
        }

        if ($type === 'text') {
            $type = 'fill';
        }

        if (! in_array($type, ['fill', 'choice', 'multi_select', 'memory_match', 'word_link', 'word_order', 'listening', 'category_sort', 'fill_blanks', 'word_select', 'word_translate', 'phat_am', 'image_hotspot', 'image_label_drop'], true)) {
            $type = 'choice';
        }

        if ($group === 'quiz' && ($this->isPairMatchType($type) || $this->isWordOrderType($type) || $this->isListeningType($type) || $this->isCategorySortType($type) || $this->isFillBlanksType($type) || $this->isWordSelectType($type) || $this->isWordTranslateType($type) || $this->isPhatAmType($type) || $this->isImageHotspotType($type) || $this->isImageLabelDropType($type))) {
            $type = 'choice';
        }

        $pairBundle = $this->mergePairBundleFromPost($item, $existingRow);

        $hasContent = $questionPlain !== ''
            || $answerExplanation !== ''
            || $correctAnswer !== ''
            || $options !== []
            || $pairBundle['memory_pairs'] !== []
            || $pairBundle['word_link_pairs'] !== []
            || $pairBundle['word_order'] !== []
            || trim((string) ($pairBundle['listening']['audio'] ?? '')) !== ''
            || trim((string) ($pairBundle['listening']['text'] ?? '')) !== ''
            || $this->hasListeningBundleDraftContent($this->normalizeListeningData($pairBundle['listening'] ?? []))
            || $this->hasPlayableTrueFalseItems($pairBundle['true_false_items'])
            || $this->hasPlayableMultiChoiceItems($pairBundle['multi_choice_items'])
            || $this->hasPlayableCategorySort($pairBundle['category_sort'])
            || $this->hasPlayableFillBlanks($pairBundle['fill_blanks'])
            || $this->hasPlayableWordSelect($pairBundle['word_select'])
            || $this->hasWordSelectDraftContent($pairBundle['word_select'])
            || $this->hasPlayableWordTranslate($pairBundle['word_translate'])
            || $this->hasWordTranslateDraftContent($pairBundle['word_translate'])
            || $this->hasPhatAmBundleDraftContent($pairBundle, $options)
            || $this->isPhatAmType($type)
            || $this->hasPlayableImageHotspot($pairBundle['image_hotspot'])
            || $this->hasPlayableImageLabelDrop($pairBundle['image_label_drop'])
            || $this->hasImageLabelDropDraftContent($pairBundle['image_label_drop']);

        if (! $hasContent) {
            return null;
        }

        $optionsJson = $this->resolveOptionsJson($type, $options, $pairBundle, $existingRow);

        if ($group === 'review') {
            $answerExplanation = $answerExplanation !== '' ? $answerExplanation : null;
        } elseif ($group === 'quiz') {
            $correctAnswer     = null;
            $answerExplanation = null;
        } elseif ($group === 'post_practice' || $group === 'pre') {
            $answerExplanation = $answerExplanation !== '' ? $answerExplanation : null;
        }

        $listeningMode = $this->isListeningType($type)
            ? ($pairBundle['listening']['answer_mode'] ?? 'choice')
            : null;
        $phatAmMode = $this->isPhatAmType($type)
            ? ($pairBundle['phat_am']['answer_mode'] ?? 'choice')
            : null;

        if (($type === 'choice'
                || ($this->isListeningType($type) && $listeningMode === 'choice'))
            && in_array($group, ['review', 'post_practice', 'pre'], true)
            && $correctAnswer !== '') {
            $correctAnswer = strtolower($correctAnswer);

            if (! in_array($correctAnswer, ['a', 'b', 'c', 'd'], true)) {
                $correctAnswer = null;
            }
        } elseif ($type === 'multi_select'
            && in_array($group, ['review', 'post_practice', 'pre'], true)
            && $correctAnswer !== '') {
            $normalizedMulti = $this->normalizeMultiSelectAnswer($correctAnswer);
            $correctAnswer = $normalizedMulti !== '' ? $normalizedMulti : null;
        } elseif (($type === 'fill'
                || ($this->isListeningType($type) && $listeningMode === 'fill'))
            && in_array($group, ['review', 'post_practice', 'pre'], true)) {
            $correctAnswer = $correctAnswer !== '' ? $correctAnswer : null;
        } else {
            $correctAnswer = null;
        }

        return [
            'question_text'      => $question,
            'question_type'      => $type,
            'options_json'       => $optionsJson,
            'correct_answer'     => $correctAnswer,
            'answer_explanation' => $answerExplanation !== '' ? $answerExplanation : null,
        ];
    }

private function collectOptions(array $item): array
    {
        $options = [];

        if (! isset($item['options']) || ! is_array($item['options'])) {
            return $options;
        }

        foreach ($item['options'] as $opt) {
            $opt = trim((string) $opt);

            if ($opt !== '') {
                $options[] = $opt;
            }
        }

        return $options;
    }

private function collectMemoryPairs(array $item): array
    {
        $json = trim((string) ($item['memory_pairs_json'] ?? ''));

        if ($json !== '') {
            $decoded = json_decode($json, true);

            if (is_array($decoded)) {
                return $this->normalizeMemoryPairRows($decoded);
            }
        }

        if (! isset($item['memory_pairs']) || ! is_array($item['memory_pairs'])) {
            return [];
        }

        return $this->normalizeMemoryPairRows($item['memory_pairs']);
    }

private function collectWordLinkPairs(array $item): array
    {
        $json = trim((string) ($item['word_link_pairs_json'] ?? ''));

        if ($json !== '') {
            $decoded = json_decode($json, true);

            if (is_array($decoded)) {
                return $this->normalizeWordLinkPairRows($decoded);
            }
        }

        if (! isset($item['word_link_pairs']) || ! is_array($item['word_link_pairs'])) {
            return [];
        }

        return $this->normalizeWordLinkPairRows($item['word_link_pairs']);
    }

public function decodePairOptionsBundle(array $row): array
    {
        $bundle = [
            'memory_pairs'      => [],
            'word_link_pairs'   => [],
            'word_order'        => [],
            'listening'         => $this->defaultListeningData(),
            'true_false_items'  => [],
            'multi_choice_items'=> [],
            'category_sort'     => $this->defaultCategorySortData(),
            'fill_blanks'       => $this->defaultFillBlanksData(),
            'word_select'       => $this->defaultWordSelectData(),
            'word_translate'    => $this->defaultWordTranslateData(),
            'phat_am'           => $this->defaultPhatAmData(),
            'image_hotspot'     => $this->defaultImageHotspotData(),
            'image_label_drop'  => $this->defaultImageLabelDropData(),
        ];

        if (empty($row['options_json'])) {
            return $bundle;
        }

        $decoded = json_decode((string) $row['options_json'], true);

        if (! is_array($decoded)) {
            return $bundle;
        }

        if (isset($decoded['memory_pairs']) && is_array($decoded['memory_pairs'])) {
            $bundle['memory_pairs'] = $this->normalizeMemoryPairRows($decoded['memory_pairs']);
        }

        if (isset($decoded['word_link_pairs']) && is_array($decoded['word_link_pairs'])) {
            $bundle['word_link_pairs'] = $this->normalizeWordLinkPairRows($decoded['word_link_pairs']);
        }

        if (isset($decoded['word_order']) && is_array($decoded['word_order'])) {
            $bundle['word_order'] = $this->normalizeWordOrderTokens($decoded['word_order']);
        }

        if (isset($decoded['listening']) && is_array($decoded['listening'])) {
            $bundle['listening'] = $this->normalizeListeningData($decoded['listening']);
        }

        if (isset($decoded['true_false_items']) && is_array($decoded['true_false_items'])) {
            $bundle['true_false_items'] = $this->normalizeTrueFalseItems($decoded['true_false_items']);
        }

        if (isset($decoded['multi_choice_items']) && is_array($decoded['multi_choice_items'])) {
            $bundle['multi_choice_items'] = $this->normalizeMultiChoiceItems($decoded['multi_choice_items']);
        }

        if (isset($decoded['category_sort']) && is_array($decoded['category_sort'])) {
            $bundle['category_sort'] = $this->normalizeCategorySortData($decoded['category_sort']);
        }

        if (isset($decoded['fill_blanks']) && is_array($decoded['fill_blanks'])) {
            $bundle['fill_blanks'] = $this->normalizeFillBlanksData($decoded['fill_blanks']);
        }

        if (isset($decoded['word_select']) && is_array($decoded['word_select'])) {
            $bundle['word_select'] = $this->normalizeWordSelectData($decoded['word_select']);
        }

        if (isset($decoded['word_translate']) && is_array($decoded['word_translate'])) {
            $bundle['word_translate'] = $this->normalizeWordTranslateData($decoded['word_translate']);
        }

        if (isset($decoded['phat_am']) && is_array($decoded['phat_am'])) {
            $bundle['phat_am'] = $this->normalizePhatAmData($decoded['phat_am']);
        }

        if (isset($decoded['image_hotspot']) && is_array($decoded['image_hotspot'])) {
            $bundle['image_hotspot'] = $this->normalizeImageHotspotData($decoded['image_hotspot']);
        }

        if (isset($decoded['image_label_drop']) && is_array($decoded['image_label_drop'])) {
            $bundle['image_label_drop'] = $this->normalizeImageLabelDropData($decoded['image_label_drop']);
        }

        if ($bundle['memory_pairs'] === [] && $bundle['word_link_pairs'] === [] && $bundle['word_order'] === [] && $bundle['listening']['text'] === '' && trim((string) ($bundle['listening']['audio'] ?? '')) === '' && ! $this->hasPlayableCategorySort($bundle['category_sort']) && ! $this->hasPlayableFillBlanks($bundle['fill_blanks']) && ! $this->hasPlayableWordSelect($bundle['word_select']) && ! $this->hasPlayableImageHotspot($bundle['image_hotspot']) && ! $this->hasPlayableImageLabelDrop($bundle['image_label_drop']) && isset($decoded['pairs']) && is_array($decoded['pairs'])) {
            foreach ($decoded['pairs'] as $pair) {
                if (! is_array($pair)) {
                    continue;
                }

                if (isset($pair['a_type']) || isset($pair['a_value']) || isset($pair['b_type']) || isset($pair['b_value'])) {
                    $bundle['word_link_pairs'][] = $this->normalizeWordLinkPairRow($pair);
                    continue;
                }

                $image = trim((string) ($pair['image'] ?? ''));
                $label = trim((string) ($pair['label'] ?? ''));

                if ($image !== '' || $label !== '') {
                    $bundle['memory_pairs'][] = [
                        'image' => $image,
                        'label' => $label,
                    ];
                }
            }
        }

        return $bundle;
    }

private function mergePairBundleFromPost(array $item, ?array $existingRow = null): array
    {
        $bundle = $existingRow !== null
            ? $this->decodePairOptionsBundle($existingRow)
            : ['memory_pairs' => [], 'word_link_pairs' => [], 'word_order' => [], 'listening' => $this->defaultListeningData(), 'true_false_items' => [], 'multi_choice_items' => [], 'category_sort' => $this->defaultCategorySortData(), 'fill_blanks' => $this->defaultFillBlanksData(), 'word_select' => $this->defaultWordSelectData(), 'word_translate' => $this->defaultWordTranslateData(), 'phat_am' => $this->defaultPhatAmData(), 'image_hotspot' => $this->defaultImageHotspotData(), 'image_label_drop' => $this->defaultImageLabelDropData()];

        $memoryJson = trim((string) ($item['memory_pairs_json'] ?? ''));

        if ($memoryJson !== '') {
            $decoded = json_decode($memoryJson, true);

            if (is_array($decoded)) {
                $bundle['memory_pairs'] = $this->normalizeMemoryPairRows($decoded);
            }
        }

        $wordLinkJson = trim((string) ($item['word_link_pairs_json'] ?? ''));

        if ($wordLinkJson !== '') {
            $decoded = json_decode($wordLinkJson, true);

            if (is_array($decoded)) {
                $bundle['word_link_pairs'] = $this->normalizeWordLinkPairRows($decoded);
            }
        }

        $wordOrderJson = trim((string) ($item['word_order_tokens_json'] ?? ''));

        if ($wordOrderJson !== '') {
            $decoded = json_decode($wordOrderJson, true);

            if (is_array($decoded)) {
                $bundle['word_order'] = $this->normalizeWordOrderTokens($decoded);
            }
        }

        $listeningText = trim((string) ($item['listening_text'] ?? ''));
        $listeningLang = trim((string) ($item['listening_lang'] ?? ''));
        $listeningAudioSaved = trim((string) ($item['listening_audio_saved'] ?? ''));
        $listeningJson = trim((string) ($item['listening_json'] ?? ''));

        if ($listeningJson !== '') {
            $decodedListening = json_decode($listeningJson, true);

            if (is_array($decodedListening)) {
                $bundle['listening'] = $this->normalizeListeningData($decodedListening);
            }
        }

        if ($listeningText !== '' || $listeningLang !== '' || array_key_exists('listening_audio_saved', $item)) {
            if (array_key_exists('listening_audio_saved', $item)) {
                $audio = $this->normalizeListeningAudioPath($listeningAudioSaved);
            } else {
                $audio = trim((string) ($bundle['listening']['audio'] ?? ''));
            }

            $bundle['listening'] = $this->normalizeListeningData(array_merge(
                $bundle['listening'],
                [
                    'text'  => $listeningText !== '' ? $listeningText : ($bundle['listening']['text'] ?? ''),
                    'audio' => $audio,
                    'lang'  => $listeningLang !== '' ? $listeningLang : ($bundle['listening']['lang'] ?? 'ko-KR'),
                ]
            ));
        }

        $listeningTrueFalseJson = trim((string) ($item['listening_true_false_json'] ?? ''));

        if ($listeningTrueFalseJson !== '') {
            $decodedTrueFalse = json_decode($listeningTrueFalseJson, true);

            if (is_array($decodedTrueFalse)) {
                $bundle['true_false_items'] = $this->normalizeTrueFalseItems($decodedTrueFalse);
            }
        }

        $listeningMultiChoiceJson = trim((string) ($item['listening_multi_choice_json'] ?? ''));

        if ($listeningMultiChoiceJson !== '') {
            $decodedMultiChoice = json_decode($listeningMultiChoiceJson, true);

            if (is_array($decodedMultiChoice)) {
                $bundle['multi_choice_items'] = $this->normalizeMultiChoiceItems($decodedMultiChoice);
            }
        }

        $categorySortJson = trim((string) ($item['category_sort_json'] ?? ''));

        if ($categorySortJson !== '') {
            $decoded = json_decode($categorySortJson, true);

            if (is_array($decoded)) {
                $bundle['category_sort'] = $this->normalizeCategorySortData($decoded);
            }
        }

        $fillBlanksJson = trim((string) ($item['fill_blanks_json'] ?? ''));

        if ($fillBlanksJson !== '') {
            $decoded = json_decode($fillBlanksJson, true);

            if (is_array($decoded)) {
                $bundle['fill_blanks'] = $this->normalizeFillBlanksData($decoded);
            }
        }

        $wordSelectJson = trim((string) ($item['word_select_json'] ?? ''));

        if ($wordSelectJson !== '') {
            $decoded = json_decode($wordSelectJson, true);

            if (is_array($decoded)) {
                $bundle['word_select'] = $this->normalizeWordSelectData($decoded);
            }
        }

        $wordTranslateJson = trim((string) ($item['word_translate_json'] ?? ''));

        if ($wordTranslateJson !== '') {
            $decoded = json_decode($wordTranslateJson, true);

            if (is_array($decoded)) {
                $normalized = $this->normalizeWordTranslateData($decoded, true);
                $postHasContent = $this->hasWordTranslateDraftContent($normalized);
                $existingHasContent = $this->hasWordTranslateDraftContent($bundle['word_translate']);

                if ($postHasContent || ! $existingHasContent) {
                    if ($existingHasContent && $this->shouldMergeWordTranslateBundles($bundle['word_translate'], $normalized)) {
                        $bundle['word_translate'] = $this->mergeWordTranslateBundleFromPost($bundle['word_translate'], $normalized);
                    } else {
                        $bundle['word_translate'] = $normalized;
                    }
                }
            }
        }

        $phatAmJson = trim((string) ($item['phat_am_json'] ?? ''));

        if ($phatAmJson !== '') {
            $decoded = json_decode($phatAmJson, true);

            if (is_array($decoded)) {
                $bundle['phat_am'] = $this->normalizePhatAmData($decoded);
            }
        }

        $phatAmPromptDirect         = trim((string) ($item['phat_am_prompt'] ?? ''));
        $phatAmChoiceQuestionDirect = trim((string) ($item['phat_am_choice_question'] ?? ''));
        $phatAmAnswerMode           = trim((string) ($item['phat_am_answer_mode'] ?? ''));

        if ($phatAmPromptDirect !== '' || $phatAmChoiceQuestionDirect !== '' || $phatAmAnswerMode !== '') {
            $bundle['phat_am'] = $this->normalizePhatAmData(array_merge(
                $bundle['phat_am'],
                array_filter([
                    'prompt'          => $phatAmPromptDirect !== '' ? $phatAmPromptDirect : null,
                    'choice_question' => $phatAmChoiceQuestionDirect !== '' ? $phatAmChoiceQuestionDirect : null,
                    'answer_mode'     => $phatAmAnswerMode !== '' ? $phatAmAnswerMode : null,
                ], static fn ($value): bool => $value !== null)
            ));
        }

        $imageHotspotJson = trim((string) ($item['image_hotspot_json'] ?? ''));

        if ($imageHotspotJson !== '') {
            $decoded = json_decode($imageHotspotJson, true);

            if (is_array($decoded)) {
                $bundle['image_hotspot'] = $this->normalizeImageHotspotData($decoded);
            }
        }

        $imageLabelDropJson = trim((string) ($item['image_label_drop_json'] ?? ''));

        if ($imageLabelDropJson !== '') {
            $decoded = json_decode($imageLabelDropJson, true);

            if (is_array($decoded)) {
                $bundle['image_label_drop'] = $this->normalizeImageLabelDropData($decoded);
            }
        }

        return $bundle;
    }

private function hasListeningDraftContent(array $pairBundle, array $options): bool
    {
        return $this->hasListeningBundleDraftContent($this->normalizeListeningData($pairBundle['listening'] ?? []));
    }

private function resolveOptionsJson(string $type, array $options, array $pairBundle, ?array $existingRow = null): ?string
    {
        if ($this->isImageLabelDropType($type)) {
            $normalized = $this->normalizeImageLabelDropData($pairBundle['image_label_drop']);

            if ($this->hasPlayableImageLabelDrop($normalized) || $this->hasImageLabelDropDraftContent($normalized)) {
                return json_encode([
                    'image_label_drop' => $normalized,
                ], JSON_UNESCAPED_UNICODE);
            }

            return null;
        }

        if ($this->isImageHotspotType($type)) {
            if (! $this->hasPlayableImageHotspot($pairBundle['image_hotspot'])) {
                return null;
            }

            return json_encode([
                'image_hotspot' => $pairBundle['image_hotspot'],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->isWordSelectType($type)) {
            $normalized = $this->normalizeWordSelectData($pairBundle['word_select']);

            if ($this->hasPlayableWordSelect($normalized) || $this->hasWordSelectDraftContent($normalized)) {
                return json_encode([
                    'word_select' => $normalized,
                ], JSON_UNESCAPED_UNICODE);
            }

            return null;
        }

        if ($this->isWordTranslateType($type)) {
            $normalized = $this->normalizeWordTranslateData($pairBundle['word_translate']);

            if ($this->hasPlayableWordTranslate($normalized) || $this->hasWordTranslateDraftContent($normalized)) {
                return json_encode([
                    'word_translate' => $normalized,
                ], JSON_UNESCAPED_UNICODE);
            }

            return null;
        }

        if ($this->isPhatAmType($type)) {
            $normalized = $this->normalizePhatAmData($pairBundle['phat_am']);

            if (! $this->hasPhatAmBundleDraftContent($pairBundle, $options)) {
                return null;
            }

            return json_encode([
                'phat_am' => $normalized,
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->isFillBlanksType($type)) {
            if (! $this->hasPlayableFillBlanks($pairBundle['fill_blanks'])) {
                return null;
            }

            return json_encode([
                'fill_blanks' => $pairBundle['fill_blanks'],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->isCategorySortType($type)) {
            if (! $this->hasPlayableCategorySort($pairBundle['category_sort'])) {
                return null;
            }

            return json_encode([
                'category_sort' => $pairBundle['category_sort'],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->isListeningType($type)) {
            $listening = $this->normalizeListeningData($pairBundle['listening']);

            if (! $this->hasListeningBundleDraftContent($listening)) {
                return null;
            }

            return json_encode([
                'listening' => $listening,
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->isWordOrderType($type)) {
            if ($pairBundle['word_order'] === []) {
                return null;
            }

            return json_encode([
                'word_order'      => $pairBundle['word_order'],
                'memory_pairs'    => $pairBundle['memory_pairs'],
                'word_link_pairs' => $pairBundle['word_link_pairs'],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->isPairMatchType($type)) {
            if ($pairBundle['memory_pairs'] === [] && $pairBundle['word_link_pairs'] === []) {
                return null;
            }

            return json_encode([
                'memory_pairs'    => $pairBundle['memory_pairs'],
                'word_link_pairs' => $pairBundle['word_link_pairs'],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($type === 'fill') {
            return null;
        }

        if ($options !== []) {
            return json_encode($options, JSON_UNESCAPED_UNICODE);
        }

        if ($pairBundle['memory_pairs'] !== [] || $pairBundle['word_link_pairs'] !== [] || $pairBundle['word_order'] !== []) {
            return json_encode([
                'memory_pairs'    => $pairBundle['memory_pairs'],
                'word_link_pairs' => $pairBundle['word_link_pairs'],
                'word_order'      => $pairBundle['word_order'],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($existingRow !== null && ! empty($existingRow['options_json'])) {
            $decoded = json_decode((string) $existingRow['options_json'], true);

            if (is_array($decoded) && ! isset($decoded['pairs']) && ! isset($decoded['memory_pairs']) && ! isset($decoded['word_link_pairs']) && ! isset($decoded['word_order'])) {
                return null;
            }
        }

        return null;
    }

private function normalizeMemoryPairRows(array $rows): array
    {
        $pairs = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $image = $this->persistPairImageValue(trim((string) ($row['image'] ?? '')));
            $label = trim((string) ($row['label'] ?? ''));

            if ($image !== '' || $label !== '') {
                $pairs[] = [
                    'image' => $image,
                    'label' => $label,
                ];
            }
        }

        return $pairs;
    }

public function normalizeWordLinkPairRow(array $row): array
    {
        $aType = (string) ($row['a_type'] ?? '');
        $bType = (string) ($row['b_type'] ?? '');

        if ($aType === '' && $bType === '') {
            $image = $this->persistPairImageValue(trim((string) ($row['image'] ?? '')));

            return [
                'a_type'  => 'image',
                'a_value' => $image,
                'b_type'  => 'text',
                'b_value' => trim((string) ($row['label'] ?? '')),
            ];
        }

        $normalized = [
            'a_type'  => in_array($aType, ['image', 'text'], true) ? $aType : 'image',
            'a_value' => trim((string) ($row['a_value'] ?? '')),
            'b_type'  => in_array($bType, ['image', 'text'], true) ? $bType : 'text',
            'b_value' => trim((string) ($row['b_value'] ?? '')),
        ];

        if ($normalized['a_type'] === 'image' && $normalized['a_value'] !== '') {
            $normalized['a_value'] = $this->persistPairImageValue($normalized['a_value']);
        }

        if ($normalized['b_type'] === 'image' && $normalized['b_value'] !== '') {
            $normalized['b_value'] = $this->persistPairImageValue($normalized['b_value']);
        }

        return $normalized;
    }

public function normalizeWordLinkPairRows(array $rows): array
    {
        $pairs = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized = $this->normalizeWordLinkPairRow($row);

            if ($normalized['a_value'] !== '' || $normalized['b_value'] !== '') {
                $pairs[] = $normalized;
            }
        }

        return $pairs;
    }

public function countCompleteWordLinkPairs(array $rows): int
    {
        return count(array_filter(
            $this->normalizeWordLinkPairRows($rows),
            static fn (array $pair): bool => ($pair['a_value'] ?? '') !== '' && ($pair['b_value'] ?? '') !== ''
        ));
    }

public function decodeWordLinkPairs(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['word_link_pairs'];
    }

public function decodePlayableWordLinkPairs(array $row): array
    {
        return $this->decodePlayableWordLinkPairsFromRows($this->decodeWordLinkPairs($row));
    }

public function decodePlayableWordLinkPairsFromRows(array $rows): array
    {
        $pairs = array_values(array_filter(
            $this->normalizeWordLinkPairRows($rows),
            static fn (array $pair): bool => ($pair['a_value'] ?? '') !== '' && ($pair['b_value'] ?? '') !== ''
        ));

        return array_map(function (array $pair): array {
            if (($pair['a_type'] ?? '') === 'image' && ($pair['a_value'] ?? '') !== '') {
                $val = (string) $pair['a_value'];
                if ($this->looksLikeImageReference($val)) {
                    $pair['a_value'] = $this->resolveWordLinkMediaUrl($val);
                } else {
                    $pair['a_type'] = 'text';
                }
            }

            if (($pair['b_type'] ?? '') === 'image' && ($pair['b_value'] ?? '') !== '') {
                $val = (string) $pair['b_value'];
                if ($this->looksLikeImageReference($val)) {
                    $pair['b_value'] = $this->resolveWordLinkMediaUrl($val);
                } else {
                    $pair['b_type'] = 'text';
                }
            }

            return $pair;
        }, $pairs);
    }

private function looksLikeImageReference(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (stripos($value, 'data:image/') !== false || preg_match('#^(https?:)?//#i', $value) === 1) {
            return true;
        }

        return str_contains($value, '/')
            || str_contains($value, '\\')
            || preg_match('/\.(png|jpe?g|gif|webp|svg|bmp|avif)(\?.*)?$/i', $value) === 1;
    }

public function resolveWordLinkMediaUrl(string $value): string
    {
        return MediaUrl::to($this->normalizePairImageReference(trim($value)));
    }

public function normalizePairImageReference(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $dataPos = stripos($value, 'data:image/');

        if ($dataPos !== false && $dataPos > 0) {
            $value = substr($value, $dataPos);
        }

        $base = rtrim((string) url('/'), '/') . '/';

        if (str_starts_with($value, $base)) {
            $value = substr($value, strlen($base));
        }

        return ltrim($value, './');
    }

public function persistPairImageValue(string $value): string
    {
        $value = $this->normalizePairImageReference($value);

        if ($value === '') {
            return '';
        }

        if (! str_starts_with($value, 'data:image/')) {
            return $value;
        }

        if (! preg_match('#^data:image/(jpeg|jpg|png|gif|webp);base64,([A-Za-z0-9+/=\s]+)$#i', $value, $matches)) {
            return '';
        }

        $binary = base64_decode(str_replace(["\r", "\n", ' '], '', $matches[2]), true);

        if ($binary === false || $binary === '') {
            return '';
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            return '';
        }

        $ext = match (strtolower($matches[1])) {
            'jpeg', 'jpg' => 'jpg',
            'png'         => 'png',
            'gif'         => 'gif',
            'webp'        => 'webp',
            default       => 'png',
        };

        return $this->writePairImageBinary($binary, $ext) ?? '';
    }

private function writePairImageBinary(string $binary, string $ext): ?string
    {
        $uploadDir = public_path('uploads/study-programs/pairs');

        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            return null;
        }

        $fileName = 'pair-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;

        if (file_put_contents($uploadDir . DIRECTORY_SEPARATOR . $fileName, $binary) === false) {
            return null;
        }

        return 'uploads/study-programs/pairs/' . $fileName;
    }

public function decodeMemoryPairs(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['memory_pairs'];
    }

public function decodePlayableMemoryPairs(array $row): array
    {
        $pairs = array_values(array_filter(
            $this->decodeMemoryPairs($row),
            static fn (array $pair): bool => ($pair['image'] ?? '') !== '' && ($pair['label'] ?? '') !== ''
        ));

        return array_map(function (array $pair): array {
            if (($pair['image'] ?? '') !== '') {
                $pair['image'] = $this->resolveWordLinkMediaUrl((string) $pair['image']);
            }

            return $pair;
        }, $pairs);
    }

public function isReadyForUse(array $row): bool
    {
        $type = (string) ($row['question_type'] ?? 'choice');

        if ($this->isListeningType($type)) {
            $bundle = $this->decodeListeningBundle($row);
            $hasAudio = trim((string) ($bundle['audio'] ?? '')) !== '';
            $hasText  = trim((string) ($bundle['text'] ?? '')) !== '';
            $hasQuestionText = trim(strip_tags((string) ($row['question_text'] ?? ''))) !== '';

            if (! $hasAudio && ! $hasText && ! $hasQuestionText && count($bundle['questions']) <= 1) {
                return false;
            }

            foreach ($bundle['questions'] as $question) {
                if (! $this->hasListeningQuestionPlayableContent($this->normalizeListeningQuestion($question))) {
                    return false;
                }
            }

            return $bundle['questions'] !== [];
        }

        if ($this->isPhatAmType($type)) {
            $bundle = $this->decodePhatAmBundle($row);

            if (trim((string) ($bundle['prompt'] ?? '')) === '') {
                return false;
            }

            foreach ($bundle['questions'] as $question) {
                if (! $this->hasPhatAmQuestionPlayableContent($question)) {
                    return false;
                }
            }

            return $bundle['questions'] !== [];
        }

        $questionPlain = trim(strip_tags((string) ($row['question_text'] ?? '')));

        if ($questionPlain === '') {
            return false;
        }

        $group = (string) ($row['question_group'] ?? 'quiz');

        if ($type === 'fill') {
            return trim(strip_tags((string) ($row['correct_answer'] ?? ''))) !== '';
        }

        if ($type === 'memory_match') {
            if ($questionPlain === '') {
                return false;
            }

            return count($this->decodePlayableMemoryPairs($row)) >= 2;
        }

        if ($type === 'word_link') {
            if ($questionPlain === '') {
                return false;
            }

            return count($this->decodePlayableWordLinkPairs($row)) >= 2;
        }

        if ($type === 'word_order') {
            if ($questionPlain === '') {
                return false;
            }

            return count($this->decodeWordOrderTokens($row)) >= 2;
        }

        if ($type === 'category_sort') {
            return $this->hasPlayableCategorySort($this->decodeCategorySortData($row));
        }

        if ($type === 'fill_blanks') {
            return $this->hasPlayableFillBlanks($this->decodeFillBlanksData($row));
        }

        if ($type === 'word_select') {
            return $this->hasPlayableWordSelect($this->decodeWordSelectData($row));
        }

        if ($type === 'word_translate') {
            return $this->hasPlayableWordTranslate($this->decodeWordTranslateData($row));
        }

        if ($type === 'image_hotspot') {
            return $this->hasPlayableImageHotspot($this->decodeImageHotspotData($row));
        }

        if ($type === 'image_label_drop') {
            return $this->hasPlayableImageLabelDrop($this->decodeImageLabelDropData($row));
        }

        if ($type === 'multi_select') {
            if ($questionPlain === '') {
                return false;
            }

            $options = $this->decodeChoiceOptions($row);

            if (count($options) < 2) {
                return false;
            }

            return $this->parseMultiSelectLetters((string) ($row['correct_answer'] ?? '')) !== [];
        }

        $options = $this->decodeChoiceOptions($row);

        if (count($options) < 2) {
            return false;
        }

        if (in_array($group, ['review', 'post_practice', 'pre'], true)) {
            if ($type === 'multi_select') {
                return $this->parseMultiSelectLetters((string) ($row['correct_answer'] ?? '')) !== [];
            }

            $correct = strtolower(trim((string) ($row['correct_answer'] ?? '')));

            return in_array($correct, ['a', 'b', 'c', 'd'], true);
        }

        return true;
    }

public function formatAsPreset(array $row, ?string $groupLabel = null): array
    {
        $options = [];

        if (! empty($row['options_json'])) {
            $decoded = json_decode((string) $row['options_json'], true);

            if (is_array($decoded)) {
                $options = array_values(array_filter(array_map('strval', $decoded), static fn ($v) => $v !== ''));
            }
        }

        $type = (string) ($row['question_type'] ?? 'choice');

        if ($type === 'text') {
            $type = 'fill';
        }

        $question = self::plainTextFromHtml((string) ($row['question_text'] ?? ''));

        if ($groupLabel !== null && $groupLabel !== '') {
            $question = '[' . $groupLabel . '] ' . $question;
        }

        $options = array_map(static fn (string $opt): string => self::plainTextFromHtml($opt), $options);

        return [
            'id'                 => -1 * abs((int) ($row['id'] ?? 0)),
            'question'           => $question,
            'type'               => $type,
            'options'            => $options,
            'group'              => (string) ($row['question_group'] ?? ''),
            'source'             => 'program_session',
            'correctAnswer'      => (string) ($row['correct_answer'] ?? ''),
            'answerExplanation'  => (string) ($row['answer_explanation'] ?? ''),
            'ready'              => $this->isReadyForUse($row),
        ];
    }

private function isPairMatchType(string $type): bool
    {
        return in_array($type, ['memory_match', 'word_link'], true);
    }

private function isWordOrderType(string $type): bool
    {
        return $type === 'word_order';
    }

private function isListeningType(string $type): bool
    {
        return $type === 'listening';
    }

private function isCategorySortType(string $type): bool
    {
        return $type === 'category_sort';
    }

private function isFillBlanksType(string $type): bool
    {
        return $type === 'fill_blanks';
    }

private function isWordSelectType(string $type): bool
    {
        return $type === 'word_select';
    }

private function isWordTranslateType(string $type): bool
    {
        return $type === 'word_translate';
    }

private function isPhatAmType(string $type): bool
    {
        return $type === 'phat_am';
    }

public function normalizeQuestionRowForEdit(array $row): array
    {
        $type = strtolower(trim((string) ($row['question_type'] ?? '')));

        if ($type === 'essay' || $type === 'text') {
            $type = 'fill';
        }

        $inferred = $this->inferQuestionTypeFromOptionsJson($row);

        if ($inferred !== null && ($type === '' || $type === 'choice')) {
            $type = $inferred;
        }

        $row['question_type'] = $type !== '' ? $type : 'choice';

        return $row;
    }

private function inferQuestionTypeFromOptionsJson(array $row): ?string
    {
        if (empty($row['options_json'])) {
            return null;
        }

        $decoded = json_decode((string) $row['options_json'], true);

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['image_label_drop'])) {
            return 'image_label_drop';
        }

        if (isset($decoded['image_hotspot'])) {
            return 'image_hotspot';
        }

        if (isset($decoded['word_translate'])) {
            return 'word_translate';
        }

        if (isset($decoded['phat_am'])) {
            return 'phat_am';
        }

        if (isset($decoded['word_select'])) {
            return 'word_select';
        }

        if (isset($decoded['fill_blanks'])) {
            return 'fill_blanks';
        }

        if (isset($decoded['category_sort'])) {
            return 'category_sort';
        }

        if (isset($decoded['listening'])) {
            return 'listening';
        }

        if (isset($decoded['word_order']) && is_array($decoded['word_order']) && $decoded['word_order'] !== []) {
            return 'word_order';
        }

        if (isset($decoded['word_link_pairs']) && is_array($decoded['word_link_pairs']) && $decoded['word_link_pairs'] !== []) {
            return 'word_link';
        }

        if (isset($decoded['memory_pairs']) && is_array($decoded['memory_pairs']) && $decoded['memory_pairs'] !== []) {
            return 'memory_match';
        }

        return null;
    }

private function isImageHotspotType(string $type): bool
    {
        return $type === 'image_hotspot';
    }

private function isImageLabelDropType(string $type): bool
    {
        return $type === 'image_label_drop';
    }

public function isMultiSelectType(string $type): bool
    {
        return $type === 'multi_select';
    }

public function parseMultiSelectLetters(string $value): array
    {
        $parts = preg_split('/[,\s|]+/', strtolower(trim($value))) ?: [];
        $valid = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);

            if ($part !== '' && preg_match('/^[a-h]$/', $part)) {
                $valid[$part] = true;
            }
        }

        $letters = array_keys($valid);
        sort($letters);

        return $letters;
    }

public function normalizeMultiSelectAnswer(string $value): string
    {
        return implode(',', $this->parseMultiSelectLetters($value));
    }

public function gradeMultiSelectAnswer(string $selected, string $expected): bool
    {
        $selectedLetters = $this->parseMultiSelectLetters($selected);
        $expectedLetters = $this->parseMultiSelectLetters($expected);

        if ($selectedLetters === [] || $expectedLetters === []) {
            return false;
        }

        return $selectedLetters === $expectedLetters;
    }

public function defaultFillBlanksData(): array
    {
        return [
            'template' => '',
            'answers'  => [],
        ];
    }

public function normalizeFillBlanksData(array $data): array
    {
        $template = trim((string) ($data['template'] ?? ''));
        $answers  = [];
        $blankCount = $this->countFillBlanksInTemplate($template);

        if (isset($data['answers']) && is_array($data['answers'])) {
            foreach ($data['answers'] as $answer) {
                $answers[] = trim((string) $answer);
            }
        }

        if ($blankCount > count($answers)) {
            $answers = array_pad($answers, $blankCount, '');
        } elseif ($blankCount < count($answers)) {
            $answers = array_slice($answers, 0, $blankCount);
        }

        return [
            'template' => $template,
            'answers'  => array_values($answers),
        ];
    }

public function countFillBlanksInTemplate(string $template): int
    {
        if ($template === '') {
            return 0;
        }

        preg_match_all('/_{3,}/u', $template, $matches);

        return count($matches[0] ?? []);
    }

public function hasPlayableFillBlanks(array $data): bool
    {
        $template = trim((string) ($data['template'] ?? ''));
        $answers  = $data['answers'] ?? [];
        $blankCount = $this->countFillBlanksInTemplate($template);

        if ($blankCount < 1) {
            return false;
        }

        if (count($answers) !== $blankCount) {
            return false;
        }

        foreach ($answers as $answer) {
            if (trim((string) $answer) === '') {
                return false;
            }
        }

        return true;
    }

public function decodeFillBlanksData(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['fill_blanks'];
    }

public function parseFillBlanksParts(string $template): array
    {
        if ($template === '') {
            return [];
        }

        $chunks = preg_split('/(_{3,})/u', $template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (! is_array($chunks)) {
            return [['type' => 'text', 'value' => $template]];
        }

        $parts  = [];
        $blankIndex = 0;

        foreach ($chunks as $chunk) {
            if (preg_match('/^_{3,}$/u', $chunk) === 1) {
                $parts[] = ['type' => 'blank', 'index' => $blankIndex];
                $blankIndex++;
                continue;
            }

            if ($chunk !== '') {
                $parts[] = ['type' => 'text', 'value' => $chunk];
            }
        }

        return $parts;
    }

public function parseFillBlanksLineGroups(string $template): array
    {
        if ($template === '') {
            return [];
        }

        $lines = preg_split('/\R/u', $template);
        $groups = [];
        $blankIndex = 0;

        if (! is_array($lines)) {
            return [$this->parseFillBlanksParts($template)];
        }

        foreach ($lines as $line) {
            $line = (string) $line;
            $chunks = preg_split('/(_{3,})/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            if (! is_array($chunks)) {
                $groups[] = $line !== '' ? [['type' => 'text', 'value' => $line]] : [];
                continue;
            }

            $parts = [];

            foreach ($chunks as $chunk) {
                if (preg_match('/^_{3,}$/u', $chunk) === 1) {
                    $parts[] = ['type' => 'blank', 'index' => $blankIndex];
                    $blankIndex++;
                    continue;
                }

                if ($chunk !== '') {
                    $parts[] = ['type' => 'text', 'value' => $chunk];
                }
            }

            $groups[] = $parts;
        }

        return $groups;
    }

public function gradeFillBlanksSubmission(array $row, string $rawSelected): bool
    {
        $payload = json_decode($rawSelected, true);

        if (! is_array($payload)) {
            return false;
        }

        $submitted = $payload['blanks'] ?? [];

        if (! is_array($submitted)) {
            return false;
        }

        $expected = $this->decodeFillBlanksData($row)['answers'];

        if (count($expected) < 1 || count($submitted) !== count($expected)) {
            return false;
        }

        foreach ($expected as $index => $answer) {
            $student = trim((string) ($submitted[$index] ?? ''));

            if ($student === '') {
                return false;
            }

            if (! $this->isFillBlankAnswerCorrect($student, (string) $answer)) {
                return false;
            }
        }

        return true;
    }

public function isFillBlankAnswerCorrect(string $student, string $expected): bool
    {
        $student  = trim($student);
        $expected = trim($expected);

        if ($student === '' || $expected === '') {
            return false;
        }

        return $this->normalizeFillBlankAnswer($student) === $this->normalizeFillBlankAnswer($expected);
    }

public function fillBlanksCorrectMap(array $expected, array $submitted): array
    {
        $map = [];

        foreach ($expected as $index => $answer) {
            $map[(int) $index] = $this->isFillBlankAnswerCorrect(
                (string) ($submitted[$index] ?? ''),
                (string) $answer
            );
        }

        return $map;
    }

public function scoreFillBlanksSubmission(array $row, string $rawSelected, float $maxPoints): array
    {
        $payload   = json_decode($rawSelected, true);
        $submitted = is_array($payload) ? ($payload['blanks'] ?? []) : [];

        if (! is_array($submitted)) {
            $submitted = [];
        }

        $expected     = $this->decodeFillBlanksData($row)['answers'] ?? [];
        $correctMap   = $this->fillBlanksCorrectMap($expected, $submitted);
        $blankCount   = count($expected);
        $correctCount = 0;

        foreach ($correctMap as $isCorrect) {
            if ($isCorrect) {
                $correctCount++;
            }
        }

        $maxPoints = max(0, $maxPoints);
        $earned    = $blankCount > 0
            ? round(max(0, min($maxPoints, $maxPoints * ($correctCount / $blankCount))), 2)
            : 0.0;

        return [
            'blank_count'   => $blankCount,
            'correct_count' => $correctCount,
            'earned'        => $earned,
            'all_correct'   => $blankCount > 0 && $correctCount === $blankCount,
            'correct_map'   => $correctMap,
        ];
    }

private function normalizeFillBlankAnswer(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

public function defaultWordSelectData(): array
    {
        return [
            'template'  => '',
            'answers'   => [],
            'word_bank' => [],
        ];
    }

public function normalizeWordSelectData(array $data): array
    {
        $base = $this->normalizeFillBlanksData($data);
        $wordBank = [];

        if (isset($data['word_bank']) && is_array($data['word_bank'])) {
            foreach ($data['word_bank'] as $word) {
                $word = trim((string) $word);

                if ($word !== '') {
                    $wordBank[] = $word;
                }
            }
        }

        $wordBank = array_values(array_unique($wordBank));

        return [
            'template'  => $base['template'],
            'answers'   => $base['answers'],
            'word_bank' => $wordBank,
        ];
    }

public function hasPlayableWordSelect(array $data): bool
    {
        if (! $this->hasPlayableFillBlanks($data)) {
            return false;
        }

        $wordBank = $data['word_bank'] ?? [];

        if (! is_array($wordBank) || count($wordBank) < count($data['answers'])) {
            return false;
        }

        $normalizedBank = array_map(fn (string $word): string => $this->normalizeFillBlankAnswer($word), $wordBank);

        foreach ($data['answers'] as $answer) {
            $normalizedAnswer = $this->normalizeFillBlankAnswer((string) $answer);

            if ($normalizedAnswer === '' || ! in_array($normalizedAnswer, $normalizedBank, true)) {
                return false;
            }
        }

        return true;
    }

public function hasWordSelectDraftContent(array $data): bool
    {
        if (trim((string) ($data['template'] ?? '')) !== '') {
            return true;
        }

        foreach ($data['answers'] ?? [] as $answer) {
            if (trim((string) $answer) !== '') {
                return true;
            }
        }

        foreach ($data['word_bank'] ?? [] as $word) {
            if (trim((string) $word) !== '') {
                return true;
            }
        }

        return false;
    }

public function decodeWordSelectData(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['word_select'];
    }

public function gradeWordSelectSubmission(array $row, string $rawSelected): bool
    {
        $payload = json_decode($rawSelected, true);

        if (! is_array($payload)) {
            return false;
        }

        $submitted = $payload['blanks'] ?? [];

        if (! is_array($submitted)) {
            return false;
        }

        $expected = $this->decodeWordSelectData($row)['answers'];

        if (count($expected) < 1 || count($submitted) !== count($expected)) {
            return false;
        }

        foreach ($expected as $index => $answer) {
            $student = trim((string) ($submitted[$index] ?? ''));

            if ($student === '') {
                return false;
            }

            if ($this->normalizeFillBlankAnswer($student) !== $this->normalizeFillBlankAnswer((string) $answer)) {
                return false;
            }
        }

        return true;
    }

public function defaultWordTranslateData(): array
    {
        return [
            'items'   => [],
            'columns' => 1,
            'layout'  => 'vertical',
        ];
    }

public function defaultPhatAmQuestion(): array
    {
        return [
            'answer_mode'        => 'choice',
            'choice_question'    => '',
            'options'            => [],
            'correct'            => 'a',
            'correct_text'       => '',
            'true_false_items'   => [
                ['statement' => '', 'answer' => true],
            ],
            'multi_choice_items' => [
                [
                    'question' => '',
                    'options'  => ['', ''],
                    'correct'  => 'a',
                ],
            ],
        ];
    }

public function defaultPhatAmItem(): array
    {
        return array_merge(['prompt' => ''], $this->defaultPhatAmQuestion());
    }

public function defaultPhatAmData(): array
    {
        return [
            'prompt'             => '',
            'questions'          => [$this->defaultPhatAmQuestion()],
            'require_recording'  => true,
        ];
    }

public function normalizePhatAmRequireRecording(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return true;
    }

public function normalizePhatAmQuestion(array $data): array
    {
        $mode = trim((string) ($data['answer_mode'] ?? 'choice'));

        if ($mode === 'recording') {
            $mode = 'choice';
        }

        if (! in_array($mode, ['choice', 'fill', 'true_false', 'multi_choice', 'multi_select'], true)) {
            $mode = 'choice';
        }

        $options = [];

        if (isset($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $option) {
                $option = trim((string) $option);

                if ($option !== '') {
                    $options[] = $option;
                }
            }
        }

        $correct = strtolower(trim((string) ($data['correct'] ?? 'a')));

        if ($mode === 'multi_select') {
            $correct = $this->normalizeMultiSelectAnswer($correct);
        } elseif (! in_array($correct, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'], true)) {
            $correct = 'a';
        }

        return [
            'answer_mode'        => $mode,
            'choice_question'    => trim((string) ($data['choice_question'] ?? '')),
            'options'            => $options,
            'correct'            => $correct,
            'correct_text'       => trim((string) ($data['correct_text'] ?? ($data['correct'] ?? ''))),
            'true_false_items'   => $this->normalizeTrueFalseItems(is_array($data['true_false_items'] ?? null) ? $data['true_false_items'] : []),
            'multi_choice_items' => $this->normalizeMultiChoiceItems(is_array($data['multi_choice_items'] ?? null) ? $data['multi_choice_items'] : []),
            'points'             => $this->normalizeOptionalItemPoints($data['points'] ?? null),
        ];
    }

public function normalizePhatAmItem(array $data): array
    {
        return array_merge(
            ['prompt' => trim((string) ($data['prompt'] ?? ''))],
            $this->normalizePhatAmQuestion($data)
        );
    }

public function normalizePhatAmData(array $data): array
    {
        $requireRecording = $this->normalizePhatAmRequireRecording($data['require_recording'] ?? true);

        if (isset($data['questions']) && is_array($data['questions'])) {
            $questions = [];

            foreach ($data['questions'] as $question) {
                if (! is_array($question)) {
                    continue;
                }

                $questions[] = $this->normalizePhatAmQuestion($question);
            }

            if ($questions === []) {
                $questions[] = $this->defaultPhatAmQuestion();
            }

            return [
                'prompt'            => trim((string) ($data['prompt'] ?? '')),
                'questions'         => $questions,
                'require_recording' => $requireRecording,
            ];
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $prompt    = trim((string) ($data['prompt'] ?? ''));
            $questions = [];

            foreach ($data['items'] as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $normalized = $this->normalizePhatAmItem($item);

                if ($index === 0 && $prompt === '') {
                    $prompt = trim((string) ($normalized['prompt'] ?? ''));
                }

                unset($normalized['prompt']);
                $questions[] = $this->normalizePhatAmQuestion($normalized);
            }

            if ($questions === []) {
                $questions[] = $this->defaultPhatAmQuestion();
            }

            return [
                'prompt'            => $prompt,
                'questions'         => $questions,
                'require_recording' => $requireRecording,
            ];
        }

        $legacyItem = $this->normalizePhatAmItem($data);
        $prompt     = trim((string) ($legacyItem['prompt'] ?? ''));
        unset($legacyItem['prompt']);

        if ($prompt !== '' || $legacyItem['choice_question'] !== '' || $legacyItem['answer_mode'] !== 'choice' || $legacyItem['options'] !== []) {
            return [
                'prompt'            => $prompt,
                'questions'         => [$this->normalizePhatAmQuestion($legacyItem)],
                'require_recording' => $requireRecording,
            ];
        }

        return $this->defaultPhatAmData();
    }

public function hasPhatAmQuestionDraftContent(array $question): bool
    {
        $mode = (string) ($question['answer_mode'] ?? 'choice');

        if ($mode === 'true_false') {
            return $this->hasPlayableTrueFalseItems($question['true_false_items'] ?? []);
        }

        if ($mode === 'multi_choice') {
            return $this->hasPlayableMultiChoiceItems($question['multi_choice_items'] ?? []);
        }

        if ($mode === 'choice' || $mode === 'multi_select') {
            return count($question['options'] ?? []) >= 2;
        }

        return $mode === 'fill';
    }

public function hasPhatAmQuestionPlayableContent(array $question): bool
    {
        $mode = (string) ($question['answer_mode'] ?? 'choice');

        if ($mode === 'fill') {
            return trim((string) ($question['correct_text'] ?? '')) !== '';
        }

        if ($mode === 'true_false') {
            return $this->hasPlayableTrueFalseItems($question['true_false_items'] ?? []);
        }

        if ($mode === 'multi_choice') {
            return $this->hasPlayableMultiChoiceItems($question['multi_choice_items'] ?? []);
        }

        $options = $question['options'] ?? [];

        if (count($options) < 2) {
            return false;
        }

        $correct = strtolower(trim((string) ($question['correct'] ?? '')));

        if ($mode === 'multi_select') {
            return $this->parseMultiSelectLetters($correct) !== [];
        }

        return in_array($correct, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'], true);
    }

public function hasPhatAmItemDraftContent(array $item): bool
    {
        if (trim((string) ($item['prompt'] ?? '')) === '') {
            return false;
        }

        return $this->hasPhatAmQuestionDraftContent($item);
    }

public function hasPhatAmItemPlayableContent(array $item): bool
    {
        if (trim((string) ($item['prompt'] ?? '')) === '') {
            return false;
        }

        return $this->hasPhatAmQuestionPlayableContent($item);
    }

public function decodePhatAmBundle(array $row): array
    {
        $bundle = $this->decodePairOptionsBundle($row);
        $phatAm = $this->normalizePhatAmData($bundle['phat_am']);
        $prompt = trim((string) ($phatAm['prompt'] ?? ''));
        $questions = $phatAm['questions'] ?? [];

        if ($questions === []) {
            $questions = [$this->defaultPhatAmQuestion()];
        }

        if (count($questions) === 1) {
            $question = $this->normalizePhatAmQuestion($questions[0]);
            $mode     = (string) ($question['answer_mode'] ?? 'choice');

            if ($mode === 'choice' && ($question['options'] ?? []) === []) {
                $question['options'] = $this->decodeChoiceOptions($row);
                $question['correct'] = strtolower(trim((string) ($row['correct_answer'] ?? 'a')));
            } elseif ($mode === 'fill' && trim((string) ($question['correct_text'] ?? '')) === '') {
                $question['correct_text'] = trim(strip_tags((string) ($row['correct_answer'] ?? '')));
            } elseif ($mode === 'true_false' && ! $this->hasPlayableTrueFalseItems($question['true_false_items'] ?? [])) {
                $question['true_false_items'] = $this->decodeTrueFalseItems($row);
            } elseif ($mode === 'multi_choice' && ! $this->hasPlayableMultiChoiceItems($question['multi_choice_items'] ?? [])) {
                $question['multi_choice_items'] = $this->decodeMultiChoiceItems($row);
            }

            $questions = [$question];
        } else {
            $questions = array_values(array_map(
                fn (array $question): array => $this->normalizePhatAmQuestion($question),
                $questions
            ));
        }

        if ($prompt === '') {
            $prompt = trim(html_entity_decode(strip_tags((string) ($row['question_text'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return [
            'prompt'            => $prompt,
            'questions'         => $questions,
            'require_recording' => $this->normalizePhatAmRequireRecording($phatAm['require_recording'] ?? true),
        ];
    }

public function phatAmRequiresRecording(array $row): bool
    {
        return $this->normalizePhatAmRequireRecording(
            $this->decodePhatAmBundle($row)['require_recording'] ?? true
        );
    }

public function decodePhatAmQuestions(array $row): array
    {
        return $this->decodePhatAmBundle($row)['questions'];
    }

public function decodePhatAmItems(array $row): array
    {
        return $this->decodePhatAmQuestions($row);
    }

public function decodePhatAmStudentAnswersPayload(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        if (isset($decoded['answers']) && is_array($decoded['answers'])) {
            return array_values($decoded['answers']);
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $answers = [];

            foreach ($decoded['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $answer = $item;
                unset($answer['recording']);
                $answers[] = $answer;
            }

            return array_values($answers);
        }

        $legacy = $decoded;
        unset($legacy['recording']);

        return [$legacy];
    }

public function decodePhatAmStudentItemsPayload(string $raw): array
    {
        return $this->decodePhatAmStudentAnswersPayload($raw);
    }

public function decodePhatAmStudentRecording(string $raw): string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return '';
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return '';
        }

        if (trim((string) ($decoded['recording'] ?? '')) !== '') {
            return trim((string) $decoded['recording']);
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            foreach ($decoded['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $recording = trim((string) ($item['recording'] ?? ''));

                if ($recording !== '') {
                    return $recording;
                }
            }
        }

        return '';
    }

public function phatAmStudentQuestionIsComplete(array $subAnswer, array $expectedQuestion): bool
    {
        $mode = (string) ($expectedQuestion['answer_mode'] ?? 'choice');

        if ($mode === 'choice') {
            $choice = strtolower(trim((string) ($subAnswer['choice'] ?? '')));

            return in_array($choice, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'], true);
        }

        if ($mode === 'multi_select') {
            return $this->parseMultiSelectLetters((string) ($subAnswer['choice'] ?? '')) !== [];
        }

        if ($mode === 'fill') {
            return trim((string) ($subAnswer['text'] ?? '')) !== '';
        }

        if ($mode === 'true_false') {
            $answers = $subAnswer['answers'] ?? [];

            return is_array($answers)
                && count($answers) === count($expectedQuestion['true_false_items'] ?? []);
        }

        if ($mode === 'multi_choice') {
            $answers = $subAnswer['answers'] ?? [];

            return is_array($answers)
                && count($answers) === count($expectedQuestion['multi_choice_items'] ?? []);
        }

        return true;
    }

public function phatAmStudentItemIsComplete(array $subItem, array $expectedItem): bool
    {
        if (trim((string) ($subItem['recording'] ?? '')) !== '') {
            unset($subItem['recording']);
        }

        return $this->phatAmStudentQuestionIsComplete($subItem, $expectedItem);
    }

public function phatAmStudentSubmissionIsComplete(array $row, string $raw): bool
    {
        if ($this->phatAmRequiresRecording($row) && ! $this->hasPhatAmRecordingInSubmission($raw)) {
            return false;
        }

        $expectedQuestions = $this->decodePhatAmQuestions($row);
        $submittedAnswers  = $this->decodePhatAmStudentAnswersPayload($raw);

        if ($expectedQuestions === [] || count($submittedAnswers) !== count($expectedQuestions)) {
            return false;
        }

        foreach ($expectedQuestions as $index => $expectedQuestion) {
            $subAnswer = is_array($submittedAnswers[$index] ?? null) ? $submittedAnswers[$index] : [];

            if (! $this->phatAmStudentQuestionIsComplete($subAnswer, $expectedQuestion)) {
                return false;
            }
        }

        return true;
    }

public function hasPhatAmBundleDraftContent(array $pairBundle, array $options): bool
    {
        $phatAm = $this->normalizePhatAmData($pairBundle['phat_am'] ?? []);

        if (trim((string) ($phatAm['prompt'] ?? '')) === '') {
            return false;
        }

        foreach ($phatAm['questions'] ?? [] as $question) {
            if (! is_array($question)) {
                continue;
            }

            if ($this->hasPhatAmQuestionDraftContent($this->normalizePhatAmQuestion($question))) {
                return true;
            }
        }

        return false;
    }

public function decodePhatAmAnswerMode(array $row): string
    {
        $items = $this->decodePhatAmItems($row);

        return (string) ($items[0]['answer_mode'] ?? 'choice');
    }

public function decodePhatAmData(array $row): array
    {
        return $this->decodePhatAmBundle($row);
    }

public function decodePhatAmPromptForDisplay(array $row): string
    {
        return $this->decodePhatAmBundle($row)['prompt'];
    }

public function decodePhatAmChoiceQuestionForDisplay(array $row): string
    {
        $items = $this->decodePhatAmItems($row);

        return trim((string) ($items[0]['choice_question'] ?? ''));
    }

public function resolvePhatAmRecordingUrl(string $path): string
    {
        return MediaUrl::to(trim($path));
    }

public function normalizeWordTranslateItem(array $item): array
    {
        $sourceType = (string) ($item['source_type'] ?? '');
        $source     = trim((string) ($item['source'] ?? ''));
        $answer     = trim((string) ($item['answer'] ?? ''));

        if (! in_array($sourceType, ['text', 'image'], true)) {
            $sourceType = 'text';
        }

        if ($sourceType === 'image' && $source !== '') {
            $source = $this->persistPairImageValue($source);
        }

        return [
            'source_type' => $sourceType,
            'source'      => $source,
            'answer'      => $answer,
        ];
    }

public function wordTranslateItemHasSource(array $item): bool
    {
        return trim((string) ($item['source'] ?? '')) !== '';
    }

public function normalizeWordTranslateData(array $data, bool $preserveEmptyItems = false): array
    {
        $items = [];

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $normalized = $this->normalizeWordTranslateItem($item);

                if (! $preserveEmptyItems
                    && ! $this->wordTranslateItemHasSource($normalized)
                    && $normalized['answer'] === '') {
                    continue;
                }

                $items[] = $normalized;
            }
        }

        $columns = (int) ($data['columns'] ?? 1);

        if (! in_array($columns, [1, 2], true)) {
            $columns = 1;
        }

        $layout = (string) ($data['layout'] ?? 'vertical');

        if (! in_array($layout, ['horizontal', 'vertical'], true)) {
            $layout = 'vertical';
        }

        return [
            'items'   => array_values($items),
            'columns' => $columns,
            'layout'  => $layout,
        ];
    }

public function hasPlayableWordTranslate(array $data): bool
    {
        foreach ($data['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalized = $this->normalizeWordTranslateItem($item);

            if ($this->wordTranslateItemHasSource($normalized) && $normalized['answer'] !== '') {
                return true;
            }
        }

        return false;
    }

public function hasWordTranslateDraftContent(array $data): bool
    {
        foreach ($data['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalized = $this->normalizeWordTranslateItem($item);

            if ($this->wordTranslateItemHasSource($normalized) || $normalized['answer'] !== '') {
                return true;
            }
        }

        return false;
    }

private function countWordTranslateItemsWithContent(array $data): int
    {
        $count = 0;

        foreach ($data['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalized = $this->normalizeWordTranslateItem($item);

            if ($this->wordTranslateItemHasSource($normalized) || $normalized['answer'] !== '') {
                $count++;
            }
        }

        return $count;
    }

private function shouldMergeWordTranslateBundles(array $existing, array $incoming): bool
    {
        $existingItems = $existing['items'] ?? [];
        $incomingItems = $incoming['items'] ?? [];

        if ($incomingItems === [] || $existingItems === []) {
            return false;
        }

        if (count($incomingItems) !== count($existingItems)) {
            return false;
        }

        $existingContentCount = $this->countWordTranslateItemsWithContent($existing);
        $incomingContentCount = $this->countWordTranslateItemsWithContent($incoming);

        return $incomingContentCount > 0 && $incomingContentCount < $existingContentCount;
    }

private function mergeWordTranslateBundleFromPost(array $existing, array $incoming): array
    {
        $existingItems = $existing['items'] ?? [];
        $incomingItems = $incoming['items'] ?? [];
        $count         = max(count($existingItems), count($incomingItems));
        $mergedItems   = [];

        for ($index = 0; $index < $count; $index++) {
            $existingItem = $this->normalizeWordTranslateItem(is_array($existingItems[$index] ?? null) ? $existingItems[$index] : []);
            $incomingItem = $this->normalizeWordTranslateItem(is_array($incomingItems[$index] ?? null) ? $incomingItems[$index] : []);

            $mergedItems[] = [
                'source_type' => $incomingItem['source'] !== '' || $incomingItem['answer'] !== ''
                    ? $incomingItem['source_type']
                    : $existingItem['source_type'],
                'source'      => $incomingItem['source'] !== '' ? $incomingItem['source'] : $existingItem['source'],
                'answer'      => $incomingItem['answer'] !== '' ? $incomingItem['answer'] : $existingItem['answer'],
            ];
        }

        $columns = (int) ($incoming['columns'] ?? $existing['columns'] ?? 1);

        if (! in_array($columns, [1, 2], true)) {
            $columns = (int) ($existing['columns'] ?? 1);
        }

        $layout = (string) ($incoming['layout'] ?? $existing['layout'] ?? 'vertical');

        if (! in_array($layout, ['horizontal', 'vertical'], true)) {
            $layout = (string) ($existing['layout'] ?? 'vertical');
        }

        return [
            'items'   => array_values($mergedItems),
            'columns' => $columns,
            'layout'  => $layout,
        ];
    }

public function requiresTeacherGrading(array $question): bool
    {
        $question = $this->normalizeQuestionRowForEdit($question);
        $type = strtolower(trim((string) ($question['question_type'] ?? '')));
        $type = $type === 'text' || $type === 'essay' ? 'fill' : $type;

        if ($type === 'fill' || $type === 'word_translate') {
            return true;
        }

        if ($type === 'phat_am' || $type === 'listening') {
            return $this->hasCompositeFillItems($question);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $question
     */
    public function hasCompositeFillItems(array $question): bool
    {
        return $this->compositeFillItemIndexes($question) !== [];
    }

    /**
     * @param  array<string, mixed>  $question
     * @return list<int>
     */
    public function compositeFillItemIndexes(array $question): array
    {
        $question = $this->normalizeQuestionRowForEdit($question);
        $type = strtolower(trim((string) ($question['question_type'] ?? '')));

        if (! in_array($type, ['listening', 'phat_am'], true)) {
            return [];
        }

        $indexes = [];

        foreach ($this->compositeItemsForRow($question) as $index => $item) {
            if ((string) ($item['answer_mode'] ?? 'choice') === 'fill') {
                $indexes[] = (int) $index;
            }
        }

        return $indexes;
    }

    /**
     * @return array<int, array{is_correct: bool, comment: string}>
     */
    public function decodeCompositeFillItemReviews(string $teacherComment): array
    {
        $teacherComment = trim($teacherComment);

        if ($teacherComment === '') {
            return [];
        }

        $decoded = json_decode($teacherComment, true);

        if (! is_array($decoded)) {
            return [];
        }

        $items = [];

        if (($decoded['type'] ?? '') === 'composite_fill_review' && is_array($decoded['items'] ?? null)) {
            $items = $decoded['items'];
        } elseif (is_array($decoded['fill_items'] ?? null)) {
            $items = $decoded['fill_items'];
        }

        if ($items === []) {
            return [];
        }

        $result = [];

        foreach ($items as $index => $item) {
            if (! is_array($item) || ! array_key_exists('is_correct', $item)) {
                continue;
            }

            $result[(int) $index] = [
                'is_correct' => ! empty($item['is_correct']),
                'comment' => trim((string) ($item['comment'] ?? '')),
            ];
        }

        return $result;
    }

    public function decodeCompositeFillReviewComment(string $teacherComment): string
    {
        $teacherComment = trim($teacherComment);

        if ($teacherComment === '') {
            return '';
        }

        $decoded = json_decode($teacherComment, true);

        if (is_array($decoded)) {
            if (($decoded['type'] ?? '') === 'composite_fill_review') {
                return trim((string) ($decoded['comment'] ?? ''));
            }

            if (($decoded['type'] ?? '') === 'phat_am_review') {
                return trim((string) ($decoded['text'] ?? ''));
            }
        }

        return trim(html_entity_decode(strip_tags($teacherComment), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * @param  array<int, array{is_correct: bool, comment?: string}>  $itemGrades
     */
    public function encodeCompositeFillItemReviews(
        array $itemGrades,
        string $comment = '',
        string $existingComment = '',
        string $recordingPath = ''
    ): string {
        $items = [];

        foreach ($itemGrades as $index => $grade) {
            if (! is_array($grade) || ! array_key_exists('is_correct', $grade)) {
                continue;
            }

            $items[(int) $index] = [
                'is_correct' => ! empty($grade['is_correct']),
                'comment' => trim((string) ($grade['comment'] ?? '')),
            ];
        }

        $existing = json_decode(trim($existingComment), true);
        $recording = trim($recordingPath);
        $text = trim($comment);

        if (is_array($existing) && ($existing['type'] ?? '') === 'phat_am_review') {
            if ($recording === '') {
                $recording = trim((string) ($existing['recording'] ?? ''));
            }

            if ($text === '') {
                $text = trim((string) ($existing['text'] ?? ''));
            }
        }

        if ($recording !== '') {
            return (string) json_encode([
                'type' => 'phat_am_review',
                'text' => $text,
                'recording' => $recording,
                'fill_items' => $items,
            ], JSON_UNESCAPED_UNICODE);
        }

        return (string) json_encode([
            'type' => 'composite_fill_review',
            'comment' => $text,
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, mixed>  $question
     * @param  array<int, array{is_correct: bool, comment?: string}>  $fillGrades
     * @return array<int, bool>
     */
    public function mergeCompositeFillCorrectFlags(array $question, string $rawSelected, array $fillGrades): array
    {
        $question = $this->normalizeQuestionRowForEdit($question);
        $type = strtolower(trim((string) ($question['question_type'] ?? '')));
        $flags = $type === 'phat_am'
            ? $this->evaluatePhatAmItems($question, $rawSelected)
            : $this->evaluateListeningItems($question, $rawSelected);

        foreach ($this->compositeFillItemIndexes($question) as $index) {
            $grade = $fillGrades[$index] ?? $fillGrades[(string) $index] ?? null;
            $flags[$index] = is_array($grade) && ! empty($grade['is_correct']);
        }

        return $flags;
    }

    /**
     * @param  array<int, bool>  $flags
     */
    public function compositeFlagsAllCorrect(array $flags): bool
    {
        return $flags !== [] && ! in_array(false, $flags, true);
    }

    /**
     * @param  array<string, mixed>  $question
     * @return array<int, array<string, mixed>>
     */
    public function compositeFillItemsForReview(array $question): array
    {
        $items = [];

        foreach ($this->compositeItemsForRow($this->normalizeQuestionRowForEdit($question)) as $index => $item) {
            if (! is_array($item) || (string) ($item['answer_mode'] ?? 'choice') !== 'fill') {
                continue;
            }

            $items[(int) $index] = $item;
        }

        return $items;
    }

public function decodeWordTranslateItemReview(string $teacherComment): array
    {
        $teacherComment = trim($teacherComment);

        if ($teacherComment === '') {
            return [];
        }

        $decoded = json_decode($teacherComment, true);

        if (! is_array($decoded) || ($decoded['type'] ?? '') !== 'word_translate_items') {
            return [];
        }

        $items = $decoded['items'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        $result = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $result[(int) $index] = [
                'is_correct' => ! empty($item['is_correct']),
                'comment'    => trim((string) ($item['comment'] ?? '')),
            ];
        }

        return $result;
    }

public function encodeWordTranslateItemReview(array $itemGrades): string
    {
        $items = [];

        foreach ($itemGrades as $index => $grade) {
            $items[(int) $index] = [
                'is_correct' => ! empty($grade['is_correct']) ? 1 : 0,
                'comment'    => HtmlContent::sanitize((string) ($grade['comment'] ?? '')),
            ];
        }

        return json_encode([
            'type'  => 'word_translate_items',
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
    }

public function decodeFillBlanksBlankReview(string $teacherComment): array
    {
        $teacherComment = trim($teacherComment);

        if ($teacherComment === '') {
            return [];
        }

        $decoded = json_decode($teacherComment, true);

        if (! is_array($decoded) || ($decoded['type'] ?? '') !== 'fill_blanks_items') {
            return [];
        }

        $items = $decoded['items'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        $result = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $result[(int) $index] = [
                'is_correct' => ! empty($item['is_correct']),
                'comment'    => trim((string) ($item['comment'] ?? '')),
            ];
        }

        return $result;
    }

public function encodeFillBlanksBlankReview(array $itemGrades): string
    {
        $items = [];

        foreach ($itemGrades as $index => $grade) {
            $items[(int) $index] = [
                'is_correct' => ! empty($grade['is_correct']) ? 1 : 0,
                'comment'    => trim((string) ($grade['comment'] ?? '')),
            ];
        }

        return json_encode([
            'type'  => 'fill_blanks_items',
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
    }

public function decodeCompositeItemPointGrades(string $teacherComment): array
    {
        $teacherComment = trim($teacherComment);

        if ($teacherComment === '') {
            return [];
        }

        $decoded = json_decode($teacherComment, true);

        if (! is_array($decoded)) {
            return [];
        }

        $items = [];

        if (($decoded['type'] ?? '') === 'composite_item_grades' && is_array($decoded['items'] ?? null)) {
            $items = $decoded['items'];
        } elseif (is_array($decoded['item_grades'] ?? null)) {
            $items = $decoded['item_grades'];
        }

        if ($items === []) {
            return [];
        }

        $result = [];

        foreach ($items as $index => $item) {
            $raw = is_array($item) ? ($item['points'] ?? $item['points_earned'] ?? null) : $item;

            if ($raw === null || $raw === '') {
                continue;
            }

            $points = (float) str_replace(',', '.', (string) $raw);
            $result[(int) $index] = round(max(0, $points), 2);
        }

        return $result;
    }

public function resolveCompositeItemGradeState(
        array $row,
        string $rawSelected,
        string $teacherComment,
        float $parentPoints
    ): array {
        $items   = $this->compositeItemsForRow($row);
        $weights = $this->distributeItemPoints($items, $parentPoints > 0 ? $parentPoints : 1.0);
        $type    = (string) ($row['question_type'] ?? '');
        $flags   = $type === 'phat_am'
            ? $this->evaluatePhatAmItems($row, $rawSelected)
            : $this->evaluateListeningItems($row, $rawSelected);
        $saved   = $this->decodeCompositeItemPointGrades($teacherComment);
        $earned  = [];

        foreach ($weights as $index => $max) {
            $max = max(0.0, (float) $max);

            if (array_key_exists($index, $saved)) {
                $earned[$index] = round(max(0, min($max, $saved[$index])), 2);
            } elseif ((string) (($items[$index] ?? [])['answer_mode'] ?? 'choice') === 'fill') {
                $earned[$index] = 0.0;
            } else {
                $earned[$index] = ! empty($flags[$index]) ? $max : 0.0;
            }
        }

        $subPayloads = $this->compositeSubPayloads($row, $rawSelected);
        $savedParts = $this->decodeCompositeItemPartGrades($teacherComment);
        $partWeights = [];
        $parts = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $itemMax = max(0.0, (float) ($weights[$index] ?? 0));
            $partCount = $this->compositeItemPartCount($item);
            $itemPartWeights = $this->distributeEqualPoints($itemMax, $partCount);
            $partWeights[$index] = $itemPartWeights;
            $flags = $this->compositeItemPartFlags(
                $item,
                is_array($subPayloads[$index] ?? null) ? $subPayloads[$index] : [],
                $type
            );

            if (isset($savedParts[$index]) && $savedParts[$index] !== []) {
                $parts[$index] = [];

                foreach ($itemPartWeights as $partIndex => $partMax) {
                    $savedPart = $savedParts[$index][$partIndex] ?? $savedParts[$index][(string) $partIndex] ?? null;
                    $parts[$index][$partIndex] = $savedPart !== null
                        ? round(max(0, min($partMax, (float) $savedPart)), 2)
                        : (! empty($flags[$partIndex]) ? $partMax : 0.0);
                }

                if (! array_key_exists($index, $saved)) {
                    $earned[$index] = round(array_sum($parts[$index]), 2);
                }

                continue;
            }

            if ($partCount > 1) {
                $autoParts = [];

                foreach ($itemPartWeights as $partIndex => $partMax) {
                    $autoParts[$partIndex] = ! empty($flags[$partIndex]) ? $partMax : 0.0;
                }

                if (array_key_exists($index, $saved)) {
                    $autoSum = array_sum($autoParts);
                    $target = $earned[$index];

                    if ($autoSum > 0.001) {
                        $scaled = [];
                        $scaledSum = 0.0;
                        $last = array_key_last($autoParts);

                        foreach ($autoParts as $partIndex => $autoValue) {
                            if ($partIndex === $last) {
                                $scaled[$partIndex] = round(max(0, $target - $scaledSum), 2);
                            } else {
                                $scaled[$partIndex] = round($autoValue * ($target / $autoSum), 2);
                                $scaledSum += $scaled[$partIndex];
                            }
                        }

                        $parts[$index] = $scaled;
                    } else {
                        $parts[$index] = $autoParts;
                        $parts[$index][0] = round(max(0, min($itemMax, $target)), 2);
                    }
                } else {
                    $parts[$index] = $autoParts;
                }
            }
        }

        return [
            'weights'      => $weights,
            'earned'       => $earned,
            'parts'        => $parts,
            'part_weights' => $partWeights,
            'has_saved'    => $saved !== [] || $savedParts !== [],
        ];
    }

    /**
     * @return array<int, array<int, float>>
     */
    public function decodeCompositeItemPartGrades(string $teacherComment): array
    {
        $teacherComment = trim($teacherComment);

        if ($teacherComment === '') {
            return [];
        }

        $decoded = json_decode($teacherComment, true);

        if (! is_array($decoded)) {
            return [];
        }

        $items = [];

        if (($decoded['type'] ?? '') === 'composite_item_grades' && is_array($decoded['items'] ?? null)) {
            $items = $decoded['items'];
        } elseif (is_array($decoded['item_grades'] ?? null)) {
            $items = $decoded['item_grades'];
        }

        if ($items === []) {
            return [];
        }

        $result = [];

        foreach ($items as $index => $item) {
            $rawParts = is_array($item) ? ($item['parts'] ?? null) : null;

            if (! is_array($rawParts)) {
                continue;
            }

            $parts = [];

            foreach ($rawParts as $partIndex => $raw) {
                $value = is_array($raw) ? ($raw['points'] ?? $raw['points_earned'] ?? null) : $raw;

                if ($value === null || $value === '') {
                    continue;
                }

                $parts[(int) $partIndex] = round(max(0, (float) str_replace(',', '.', (string) $value)), 2);
            }

            if ($parts !== []) {
                $result[(int) $index] = $parts;
            }
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function compositeSubPayloads(array $row, string $rawSelected): array
    {
        $decoded = json_decode(trim($rawSelected), true);

        if (! is_array($decoded)) {
            return [];
        }

        if (isset($decoded['answers']) && is_array($decoded['answers'])) {
            return $decoded['answers'];
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            return $decoded['items'];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function compositeItemPartCount(array $item): int
    {
        $mode = (string) ($item['answer_mode'] ?? 'choice');

        if ($mode === 'true_false') {
            return max(1, count($item['true_false_items'] ?? []));
        }

        if ($mode === 'multi_choice') {
            return max(1, count($item['multi_choice_items'] ?? []));
        }

        if ($mode === 'fill_blanks') {
            $data = $this->normalizeFillBlanksData($item['fill_blanks'] ?? []);

            return max(1, count($data['answers'] ?? []));
        }

        return 1;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $subPayload
     * @return array<int, bool>
     */
    public function compositeItemPartFlags(array $item, array $subPayload, string $parentType = 'listening'): array
    {
        $mode = (string) ($item['answer_mode'] ?? 'choice');
        $count = $this->compositeItemPartCount($item);

        if ($mode === 'true_false') {
            $flags = [];
            $submitted = is_array($subPayload['answers'] ?? null) ? $subPayload['answers'] : [];

            foreach (array_values($item['true_false_items'] ?? []) as $index => $tfItem) {
                $studentVal = strtolower(trim((string) ($submitted[$index] ?? '')));
                $expected = ! empty($tfItem['answer']) ? 'true' : 'false';
                $flags[$index] = $studentVal === $expected;
            }

            return $flags;
        }

        if ($mode === 'multi_choice') {
            $flags = [];
            $submitted = is_array($subPayload['answers'] ?? null) ? $subPayload['answers'] : [];

            foreach (array_values($item['multi_choice_items'] ?? []) as $index => $mcItem) {
                $studentVal = strtolower(trim((string) ($submitted[$index] ?? '')));
                $expected = strtolower(trim((string) ($mcItem['correct'] ?? '')));
                $flags[$index] = $studentVal !== '' && $expected !== '' && $studentVal === $expected;
            }

            return $flags;
        }

        if ($mode === 'fill_blanks') {
            $data = $this->normalizeFillBlanksData($item['fill_blanks'] ?? []);
            $expected = $data['answers'] ?? [];
            $submitted = is_array($subPayload['blanks'] ?? null) ? $subPayload['blanks'] : [];
            $map = $this->fillBlanksCorrectMap($expected, $submitted);
            $flags = [];

            foreach (array_keys($expected) as $index) {
                $flags[(int) $index] = ! empty($map[(int) $index]);
            }

            return $flags;
        }

        $ok = $parentType === 'phat_am'
            ? $this->gradePhatAmItemSubmission($item, $subPayload)
            : $this->gradeListeningQuestionSubmission($item, $subPayload);

        return $count > 0 ? [0 => $ok] : [];
    }

    /**
     * @return array<int, float>
     */
    public function distributeEqualPoints(float $parentPoints, int $count): array
    {
        $parentPoints = round(max(0, $parentPoints), 2);
        $count = max(0, $count);

        if ($count <= 0) {
            return [];
        }

        if ($count === 1) {
            return [0 => $parentPoints];
        }

        $base = floor(($parentPoints / $count) * 100) / 100;
        $weights = array_fill(0, $count, $base);
        $remainder = round($parentPoints - ($base * $count), 2);
        $weights[$count - 1] = round($weights[$count - 1] + $remainder, 2);

        if ($weights[$count - 1] < 0) {
            $weights[$count - 1] = 0.0;
        }

        return $weights;
    }

public function encodeCompositeItemPointGrades(array $earnedByIndex, array $partsByIndex = []): string
    {
        $items = [];

        foreach ($earnedByIndex as $index => $points) {
            $index = (int) $index;
            $entry = [
                'points' => round(max(0, (float) $points), 2),
            ];
            $rawParts = is_array($partsByIndex[$index] ?? null) ? $partsByIndex[$index] : [];

            if ($rawParts !== []) {
                $parts = [];

                foreach ($rawParts as $partIndex => $partPoints) {
                    $parts[(int) $partIndex] = [
                        'points' => round(max(0, (float) $partPoints), 2),
                    ];
                }

                $entry['parts'] = $parts;
            }

            $items[$index] = $entry;
        }

        return json_encode([
            'type'  => 'composite_item_grades',
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
    }

public function decodePhatAmTeacherReview(string $teacherComment): array
    {
        $teacherComment = trim($teacherComment);

        if ($teacherComment === '') {
            return ['text' => '', 'recording' => ''];
        }

        $decoded = json_decode($teacherComment, true);

        if (is_array($decoded) && (($decoded['type'] ?? '') === 'phat_am_review' || array_key_exists('recording', $decoded))) {
            return [
                'text'      => trim((string) ($decoded['text'] ?? '')),
                'recording' => trim((string) ($decoded['recording'] ?? '')),
            ];
        }

        if (str_contains($teacherComment, '<')) {
            return [
                'text' => HtmlContent::sanitize($teacherComment),
                'recording' => '',
            ];
        }

        $plain = trim(html_entity_decode(strip_tags($teacherComment), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return ['text' => $plain, 'recording' => ''];
    }

public function encodePhatAmTeacherReview(string $text, string $recordingPath, array $itemGrades = [], array $partsByIndex = []): string
    {
        $text          = trim($text);
        $recordingPath = trim($recordingPath);
        $grades        = [];

        foreach ($itemGrades as $index => $points) {
            $index = (int) $index;
            $entry = [
                'points' => round(max(0, (float) $points), 2),
            ];
            $rawParts = is_array($partsByIndex[$index] ?? null) ? $partsByIndex[$index] : [];

            if ($rawParts !== []) {
                $parts = [];

                foreach ($rawParts as $partIndex => $partPoints) {
                    $parts[(int) $partIndex] = [
                        'points' => round(max(0, (float) $partPoints), 2),
                    ];
                }

                $entry['parts'] = $parts;
            }

            $grades[$index] = $entry;
        }

        if ($text === '' && $recordingPath === '' && $grades === []) {
            return '';
        }

        $payload = [
            'type'      => 'phat_am_review',
            'text'      => $text,
            'recording' => $recordingPath,
        ];

        if ($grades !== []) {
            $payload['item_grades'] = $grades;
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

public function hasPhatAmTeacherReviewContent(string $teacherComment): bool
    {
        $review = $this->decodePhatAmTeacherReview($teacherComment);

        return HtmlContent::has($review['text']) || $review['recording'] !== '';
    }

public function decodePhatAmStudentPayload(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return ['recording' => '', 'answer' => ''];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! array_key_exists('recording', $decoded)) {
            return ['recording' => '', 'answer' => $raw];
        }

        $recording = trim((string) ($decoded['recording'] ?? ''));
        unset($decoded['recording']);

        if ($decoded === []) {
            return ['recording' => $recording, 'answer' => ''];
        }

        if (isset($decoded['choice'])) {
            return [
                'recording' => $recording,
                'answer'    => strtolower(trim((string) $decoded['choice'])),
            ];
        }

        if (isset($decoded['text'])) {
            return [
                'recording' => $recording,
                'answer'    => trim((string) $decoded['text']),
            ];
        }

        if (isset($decoded['answers'])) {
            return [
                'recording' => $recording,
                'answer'    => json_encode(['answers' => $decoded['answers']], JSON_UNESCAPED_UNICODE),
            ];
        }

        return [
            'recording' => $recording,
            'answer'    => json_encode($decoded, JSON_UNESCAPED_UNICODE),
        ];
    }

public function hasPhatAmRecordingInSubmission(string $raw): bool
    {
        $raw = trim($raw);

        if ($raw === '') {
            return false;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return false;
        }

        if (isset($decoded['answers']) && is_array($decoded['answers'])) {
            return trim((string) ($decoded['recording'] ?? '')) !== '';
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            if ($decoded['items'] === []) {
                return false;
            }

            foreach ($decoded['items'] as $item) {
                if (! is_array($item) || trim((string) ($item['recording'] ?? '')) === '') {
                    return false;
                }
            }

            return true;
        }

        return $this->decodePhatAmStudentRecording($raw) !== '';
    }

public function isWordTranslateItemReviewComplete(array $itemGrades, int $itemCount): bool
    {
        if ($itemCount <= 0 || count($itemGrades) !== $itemCount) {
            return false;
        }

        foreach ($itemGrades as $grade) {
            if (! is_array($grade) || ! array_key_exists('is_correct', $grade)) {
                return false;
            }
        }

        return true;
    }

public function computeWordTranslateOverallCorrect(array $itemGrades): bool
    {
        if ($itemGrades === []) {
            return false;
        }

        foreach ($itemGrades as $grade) {
            if (empty($grade['is_correct'])) {
                return false;
            }
        }

        return true;
    }

public function countWordTranslateCorrectItems(array $itemReviews): int
    {
        $count = 0;

        foreach ($itemReviews as $review) {
            if (! empty($review['is_correct'])) {
                $count++;
            }
        }

        return $count;
    }

public function decodeWordTranslateData(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['word_translate'];
    }

public function gradeWordTranslateSubmission(array $row, string $rawSelected): bool
    {
        $payload = json_decode($rawSelected, true);

        if (! is_array($payload)) {
            return false;
        }

        $submitted = $payload['translations'] ?? [];

        if (! is_array($submitted)) {
            return false;
        }

        $expectedItems = $this->decodeWordTranslateData($row)['items'];

        if ($expectedItems === []) {
            return false;
        }

        $hasFilled = false;

        foreach ($expectedItems as $index => $item) {
            $student  = trim((string) ($submitted[$index] ?? ''));
            $expected = trim((string) ($item['answer'] ?? ''));

            if ($student === '') {
                continue;
            }

            $hasFilled = true;

            if ($expected === '') {
                continue;
            }

            if ($this->normalizeFillBlankAnswer($student) !== $this->normalizeFillBlankAnswer($expected)) {
                return false;
            }
        }

        return $hasFilled;
    }

public function defaultImageHotspotData(): array
    {
        return [
            'image'      => '',
            'tolerance'  => 8.0,
            'targets'    => [],
        ];
    }

public function normalizeImageHotspotData(array $data): array
    {
        $image = trim((string) ($data['image'] ?? ''));
        $tolerance = (float) ($data['tolerance'] ?? 8.0);
        $tolerance = max(3.0, min(25.0, round($tolerance, 1)));

        $targets = [];

        if (isset($data['targets']) && is_array($data['targets'])) {
            foreach ($data['targets'] as $index => $target) {
                if (! is_array($target)) {
                    continue;
                }

                $x = round(max(0.0, min(100.0, (float) ($target['x'] ?? 0))), 1);
                $y = round(max(0.0, min(100.0, (float) ($target['y'] ?? 0))), 1);
                $label = trim((string) ($target['label'] ?? ''));

                $targets[] = [
                    'id'    => trim((string) ($target['id'] ?? ('t' . ($index + 1)))) ?: ('t' . ($index + 1)),
                    'label' => $label !== '' ? $label : ('Vị trí ' . ($index + 1)),
                    'x'     => $x,
                    'y'     => $y,
                ];
            }
        }

        return [
            'image'     => $image,
            'tolerance' => $tolerance,
            'targets'   => array_values($targets),
        ];
    }

public function hasPlayableImageHotspot(array $data): bool
    {
        $image = trim((string) ($data['image'] ?? ''));

        if ($image === '') {
            return false;
        }

        $targets = $data['targets'] ?? [];

        return is_array($targets) && count($targets) >= 1;
    }

public function decodeImageHotspotData(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['image_hotspot'];
    }

public function resolveImageHotspotUrl(string $image): string
    {
        return MediaUrl::to(trim($image));
    }

public function gradeImageHotspotSubmission(array $row, string $rawSelected): bool
    {
        $payload = json_decode($rawSelected, true);

        if (! is_array($payload)) {
            return false;
        }

        $clicks = $payload['clicks'] ?? [];

        if (! is_array($clicks)) {
            return false;
        }

        $config   = $this->decodeImageHotspotData($row);
        $targets  = $config['targets'];
        $tolerance = (float) ($config['tolerance'] ?? 8.0);

        if ($targets === [] || count($clicks) !== count($targets)) {
            return false;
        }

        $used = [];

        foreach ($targets as $target) {
            $matched = false;

            foreach ($clicks as $index => $click) {
                if (! is_array($click) || in_array($index, $used, true)) {
                    continue;
                }

                if ($this->imageHotspotDistance($target, $click) <= $tolerance) {
                    $used[]  = $index;
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                return false;
            }
        }

        return true;
    }

private function imageHotspotDistance(array $a, array $b): float
    {
        $dx = (float) ($a['x'] ?? 0) - (float) ($b['x'] ?? 0);
        $dy = (float) ($a['y'] ?? 0) - (float) ($b['y'] ?? 0);

        return sqrt(($dx * $dx) + ($dy * $dy));
    }

public function defaultImageLabelDropData(): array
    {
        return [
            'image'       => '',
            'zone_radius' => 10.0,
            'zones'       => [],
            'labels'      => [],
        ];
    }

public function normalizeImageLabelDropData(array $data): array
    {
        $image = trim((string) ($data['image'] ?? ''));
        $zoneRadius = (float) ($data['zone_radius'] ?? ($data['tolerance'] ?? 10.0));
        $zoneRadius = max(5.0, min(25.0, round($zoneRadius, 1)));

        $zones = [];

        if (isset($data['zones']) && is_array($data['zones'])) {
            foreach ($data['zones'] as $index => $zone) {
                if (! is_array($zone)) {
                    continue;
                }

                $zones[] = [
                    'id'    => trim((string) ($zone['id'] ?? ('z' . ($index + 1)))) ?: ('z' . ($index + 1)),
                    'label' => trim((string) ($zone['label'] ?? '')) !== '' ? trim((string) ($zone['label'] ?? '')) : ('Vùng ' . ($index + 1)),
                    'x'     => round(max(0.0, min(100.0, (float) ($zone['x'] ?? 0))), 1),
                    'y'     => round(max(0.0, min(100.0, (float) ($zone['y'] ?? 0))), 1),
                ];
            }
        }

        $zoneIds = array_map(static fn (array $zone): string => (string) ($zone['id'] ?? ''), $zones);
        $labels  = [];

        if (isset($data['labels']) && is_array($data['labels'])) {
            foreach ($data['labels'] as $index => $labelRow) {
                if (! is_array($labelRow)) {
                    continue;
                }

                $text = trim((string) ($labelRow['text'] ?? ''));

                $zoneId = trim((string) ($labelRow['zone_id'] ?? ''));

                if ($zoneId === '' || ! in_array($zoneId, $zoneIds, true)) {
                    $zoneId = (string) ($zones[0]['id'] ?? '');
                }

                if ($zoneId === '') {
                    continue;
                }

                $labels[] = [
                    'id'      => trim((string) ($labelRow['id'] ?? ('l' . ($index + 1)))) ?: ('l' . ($index + 1)),
                    'text'    => $text,
                    'zone_id' => $zoneId,
                ];
            }
        }

        return [
            'image'       => $image,
            'zone_radius' => $zoneRadius,
            'zones'       => array_values($zones),
            'labels'      => array_values($labels),
        ];
    }

public function hasPlayableImageLabelDrop(array $data): bool
    {
        $image = trim((string) ($data['image'] ?? ''));

        if ($image === '') {
            return false;
        }

        $zones  = $data['zones'] ?? [];
        $labels = $data['labels'] ?? [];

        if (! is_array($zones) || count($zones) < 1) {
            return false;
        }

        if (! is_array($labels) || count($labels) < 1) {
            return false;
        }

        $zoneIds = array_map(static fn (array $zone): string => (string) ($zone['id'] ?? ''), $zones);

        foreach ($labels as $labelRow) {
            if (! is_array($labelRow)) {
                continue;
            }

            if (trim((string) ($labelRow['text'] ?? '')) === '') {
                return false;
            }

            if (! in_array((string) ($labelRow['zone_id'] ?? ''), $zoneIds, true)) {
                return false;
            }
        }

        return true;
    }

public function hasImageLabelDropDraftContent(array $data): bool
    {
        if (trim((string) ($data['image'] ?? '')) !== '') {
            return true;
        }

        $zones = $data['zones'] ?? [];

        if (is_array($zones) && count($zones) > 0) {
            return true;
        }

        $labels = $data['labels'] ?? [];

        if (! is_array($labels)) {
            return false;
        }

        foreach ($labels as $labelRow) {
            if (! is_array($labelRow)) {
                continue;
            }

            if (trim((string) ($labelRow['text'] ?? '')) !== '') {
                return true;
            }

            if (trim((string) ($labelRow['zone_id'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

public function decodeImageLabelDropData(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['image_label_drop'];
    }

public function resolveImageLabelDropUrl(string $image): string
    {
        return $this->resolveImageHotspotUrl($image);
    }

public function gradeImageLabelDropSubmission(array $row, string $rawSelected): bool
    {
        $payload = json_decode($rawSelected, true);

        if (! is_array($payload) || ($payload['status'] ?? '') !== 'completed') {
            return false;
        }

        $assignments = $payload['assignments'] ?? [];

        if (! is_array($assignments)) {
            return false;
        }

        $config = $this->decodeImageLabelDropData($row);
        $labels = $config['labels'] ?? [];

        if ($labels === []) {
            return false;
        }

        $expected = [];

        foreach ($labels as $labelRow) {
            if (! is_array($labelRow)) {
                continue;
            }

            $labelId = trim((string) ($labelRow['id'] ?? ''));

            if ($labelId === '') {
                continue;
            }

            $expected[$labelId] = trim((string) ($labelRow['zone_id'] ?? ''));
        }

        if ($expected === [] || count($assignments) !== count($expected)) {
            return false;
        }

        $submitted = [];

        foreach ($assignments as $assignment) {
            if (! is_array($assignment)) {
                return false;
            }

            $labelId = trim((string) ($assignment['label_id'] ?? ''));

            if ($labelId === '' || ! isset($expected[$labelId])) {
                return false;
            }

            if (isset($submitted[$labelId])) {
                return false;
            }

            $submitted[$labelId] = trim((string) ($assignment['zone_id'] ?? ''));
        }

        foreach ($expected as $labelId => $zoneId) {
            if (! isset($submitted[$labelId]) || $submitted[$labelId] !== $zoneId) {
                return false;
            }
        }

        return true;
    }

public function defaultCategorySortData(): array
    {
        return [
            'groups' => [],
            'items'  => [],
        ];
    }

public function normalizeCategorySortData(array $data): array
    {
        $groups = [];
        $groupRows = isset($data['groups']) && is_array($data['groups']) ? $data['groups'] : [];

        foreach ($groupRows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $groups[] = [
                'id'    => (string) ($row['id'] ?? $index),
                'label' => $label,
            ];
        }

        $groups = array_values($groups);

        foreach ($groups as $index => $group) {
            $groups[$index]['id'] = (string) $index;
        }

        $validGroupIds = array_column($groups, 'id');
        $items         = [];
        $itemRows      = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

        foreach ($itemRows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $text = trim((string) ($row['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $groupId = (string) ($row['group_id'] ?? '');

            if ($groupId === '' || ! in_array($groupId, $validGroupIds, true)) {
                $groupId = (string) ($validGroupIds[0] ?? '0');
            }

            $items[] = [
                'id'       => (string) $index,
                'text'     => $text,
                'group_id' => $groupId,
            ];
        }

        $items = array_values($items);

        foreach ($items as $index => $item) {
            $items[$index]['id'] = (string) $index;
        }

        return [
            'groups' => $groups,
            'items'  => $items,
        ];
    }

public function hasPlayableCategorySort(array $data): bool
    {
        return count($data['groups'] ?? []) >= 2 && count($data['items'] ?? []) >= 2;
    }

public function decodeCategorySortData(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['category_sort'];
    }

public function decodePlayableCategorySort(array $row): array
    {
        $data = $this->decodeCategorySortData($row);

        return [
            'groups' => array_values($data['groups']),
            'items'  => array_values(array_map(static fn (array $item): array => [
                'id'   => (string) ($item['id'] ?? ''),
                'text' => (string) ($item['text'] ?? ''),
            ], $data['items'])),
        ];
    }

public function gradeCategorySortSubmission(array $row, string $rawSelected): bool
    {
        $payload = json_decode($rawSelected, true);

        if (! is_array($payload) || ($payload['status'] ?? '') !== 'completed') {
            return false;
        }

        $assignments = $payload['assignments'] ?? [];

        if (! is_array($assignments)) {
            return false;
        }

        $expectedItems = $this->decodeCategorySortData($row)['items'];

        if (count($expectedItems) < 2 || count($assignments) !== count($expectedItems)) {
            return false;
        }

        $expectedMap = [];

        foreach ($expectedItems as $item) {
            $expectedMap[(string) ($item['id'] ?? '')] = (string) ($item['group_id'] ?? '');
        }

        foreach ($assignments as $assignment) {
            if (! is_array($assignment)) {
                return false;
            }

            $itemId  = (string) ($assignment['item_id'] ?? '');
            $groupId = (string) ($assignment['group_id'] ?? '');

            if ($itemId === '' || $groupId === '' || ! isset($expectedMap[$itemId])) {
                return false;
            }

            if ($expectedMap[$itemId] !== $groupId) {
                return false;
            }
        }

        return true;
    }

public function defaultListeningQuestion(): array
    {
        return [
            'answer_mode'        => 'choice',
            'choice_question'    => '',
            'options'            => [],
            'correct'            => 'a',
            'correct_text'       => '',
            'true_false_items'   => [
                ['statement' => '', 'answer' => true],
            ],
            'multi_choice_items' => [
                [
                    'question' => '',
                    'options'  => ['', ''],
                    'correct'  => 'a',
                ],
            ],
            'word_link_pairs'    => [],
            'fill_blanks'        => $this->defaultFillBlanksData(),
        ];
    }

public function defaultListeningData(): array
    {
        return [
            'text'      => '',
            'audio'     => '',
            'lang'      => 'ko-KR',
            'questions' => [$this->defaultListeningQuestion()],
        ];
    }

public function defaultListeningConfig(): array
    {
        $data = $this->defaultListeningData();
        $first = $data['questions'][0];

        return [
            'text'             => $data['text'],
            'audio'            => $data['audio'],
            'lang'             => $data['lang'],
            'answer_mode'      => (string) ($first['answer_mode'] ?? 'choice'),
            'choice_question'  => (string) ($first['choice_question'] ?? ''),
            'questions'        => $data['questions'],
        ];
    }

public function normalizeListeningQuestion(array $data): array
    {
        $mode = trim((string) ($data['answer_mode'] ?? 'choice'));

        if (! in_array($mode, ['choice', 'fill', 'true_false', 'multi_choice', 'word_link', 'fill_blanks'], true)) {
            $mode = 'choice';
        }

        $base = $this->normalizePhatAmQuestion($data);
        $base['answer_mode'] = $mode;

        return array_merge($base, [
            'word_link_pairs' => $this->normalizeWordLinkPairRows(is_array($data['word_link_pairs'] ?? null) ? $data['word_link_pairs'] : []),
            'fill_blanks'     => $this->normalizeFillBlanksData(is_array($data['fill_blanks'] ?? null) ? $data['fill_blanks'] : []),
        ]);
    }

public function normalizeOptionalItemPoints(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim(str_replace(',', '.', (string) $value));

        if ($raw === '') {
            return null;
        }

        $points = (float) $raw;

        if ($points <= 0) {
            return null;
        }

        return round(min(1000, $points), 2);
    }

public function formatOptionalItemPoints(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

public function distributeItemPoints(array $items, float $parentPoints): array
    {
        $count = count($items);

        if ($count === 0) {
            return [];
        }

        $parentPoints = $parentPoints > 0 ? round(min(1000, $parentPoints), 2) : 1.0;
        $weights = [];
        $emptyIndexes = [];
        $explicitSum = 0.0;
        $explicitCount = 0;

        foreach ($items as $index => $item) {
            $points = $this->normalizeOptionalItemPoints($item['points'] ?? null);

            if ($points === null) {
                $weights[$index] = 0.0;
                $emptyIndexes[] = $index;
            } else {
                $weights[$index] = $points;
                $explicitSum += $points;
                ++$explicitCount;
            }
        }

        if ($explicitCount === $count) {
            return $weights;
        }

        if ($explicitCount === 0) {
            $base = floor(($parentPoints / $count) * 100) / 100;
            $weights = array_fill(0, $count, $base);
            $remainder = round($parentPoints - ($base * $count), 2);
            $weights[$count - 1] = round($weights[$count - 1] + $remainder, 2);

            if ($weights[$count - 1] < 0) {
                $weights[$count - 1] = 0.0;
            }

            return $weights;
        }

        $leftover = round(max(0, $parentPoints - $explicitSum), 2);
        $emptyCount = count($emptyIndexes);

        if ($emptyCount > 0 && $leftover > 0) {
            $base = floor(($leftover / $emptyCount) * 100) / 100;
            $used = 0.0;

            foreach ($emptyIndexes as $emptyIndex => $itemIndex) {
                if ($emptyIndex === $emptyCount - 1) {
                    $weights[$itemIndex] = round(max(0, $leftover - $used), 2);
                } else {
                    $weights[$itemIndex] = $base;
                    $used += $base;
                }
            }
        }

        return $weights;
    }

public function compositeItemsForRow(array $row): array
    {
        $type = (string) ($row['question_type'] ?? '');

        if ($type === 'phat_am') {
            return $this->decodePhatAmQuestions($row);
        }

        if ($type === 'listening') {
            return $this->decodeListeningQuestions($row);
        }

        return [];
    }

public function resolveCompositeParentPoints(array $row, float $postedParent): float
    {
        $items = $this->compositeItemsForRow($row);
        $sum = 0.0;
        $explicitCount = 0;

        foreach ($items as $item) {
            $points = $this->normalizeOptionalItemPoints($item['points'] ?? null);

            if ($points !== null) {
                ++$explicitCount;
                $sum += $points;
            }
        }

        if ($explicitCount > 0 && $explicitCount === count($items) && $sum > 0) {
            return round(min(1000, $sum), 2);
        }

        if ($postedParent > 0) {
            return round(min(1000, $postedParent), 2);
        }

        if ($explicitCount > 0 && $sum > 0) {
            return round(min(1000, $sum), 2);
        }

        return 1.0;
    }

public function scoreCompositeSubmission(array $row, string $rawSelected): array
    {
        $type    = (string) ($row['question_type'] ?? '');
        $items   = $this->compositeItemsForRow($row);
        $parent  = $this->resolveCompositeParentPoints($row, (float) ($row['points'] ?? 1));
        $weights = $this->distributeItemPoints($items, $parent);
        $flags   = $type === 'phat_am'
            ? $this->evaluatePhatAmItems($row, $rawSelected)
            : $this->evaluateListeningItems($row, $rawSelected);

        $earned  = 0.0;
        $correct = 0;

        foreach ($flags as $index => $ok) {
            if ($ok) {
                $earned += $weights[$index] ?? 0.0;
                ++$correct;
            }
        }

        $possible = round(array_sum($weights), 2);
        $total    = count($flags);

        return [
            'earned'        => round($earned, 2),
            'possible'      => $possible > 0 ? $possible : $parent,
            'all_correct'   => $total > 0 && $correct === $total,
            'correct_count' => $correct,
            'total_count'   => $total,
        ];
    }

public function normalizeListeningData(array $data): array
    {
        $lang = trim((string) ($data['lang'] ?? 'ko-KR'));

        if ($lang === '') {
            $lang = 'ko-KR';
        }

        $audio = $this->normalizeListeningAudioPath(trim((string) ($data['audio'] ?? '')));
        $text  = trim((string) ($data['text'] ?? ''));

        if (isset($data['questions']) && is_array($data['questions'])) {
            $questions = [];

            foreach ($data['questions'] as $question) {
                if (! is_array($question)) {
                    continue;
                }

                $questions[] = $this->normalizeListeningQuestion($question);
            }

            if ($questions === []) {
                $questions[] = $this->defaultListeningQuestion();
            }

            return [
                'text'      => $text,
                'audio'     => $audio,
                'lang'      => $lang,
                'questions' => $questions,
            ];
        }

        if (isset($data['answer_mode']) || isset($data['choice_question']) || isset($data['options']) || isset($data['true_false_items']) || isset($data['multi_choice_items'])) {
            return [
                'text'      => $text,
                'audio'     => $audio,
                'lang'      => $lang,
                'questions' => [$this->normalizeListeningQuestion($data)],
            ];
        }

        return [
            'text'      => $text,
            'audio'     => $audio,
            'lang'      => $lang,
            'questions' => [$this->defaultListeningQuestion()],
        ];
    }

public function normalizeListeningConfig(array $config): array
    {
        $data  = $this->normalizeListeningData($config);
        $first = $data['questions'][0] ?? $this->defaultListeningQuestion();

        return [
            'text'             => $data['text'],
            'audio'            => $data['audio'],
            'lang'             => $data['lang'],
            'answer_mode'      => (string) ($first['answer_mode'] ?? 'choice'),
            'choice_question'  => trim((string) ($first['choice_question'] ?? '')),
            'questions'        => $data['questions'],
        ];
    }

public function hasListeningQuestionDraftContent(array $question): bool
    {
        if (trim((string) ($question['choice_question'] ?? '')) !== '') {
            return true;
        }

        $mode = (string) ($question['answer_mode'] ?? 'choice');

        if ($mode === 'word_link') {
            return count($question['word_link_pairs'] ?? []) >= 1;
        }

        if ($mode === 'fill_blanks') {
            return trim((string) ($question['fill_blanks']['template'] ?? '')) !== '';
        }

        if ($mode === 'true_false') {
            foreach ($question['true_false_items'] ?? [] as $tf) {
                if (is_array($tf) && trim((string) ($tf['statement'] ?? '')) !== '') {
                    return true;
                }
            }

            return false;
        }

        if ($mode === 'multi_choice') {
            foreach ($question['multi_choice_items'] ?? [] as $mc) {
                if (! is_array($mc)) {
                    continue;
                }
                if (trim((string) ($mc['question'] ?? '')) !== '') {
                    return true;
                }
                foreach ($mc['options'] ?? [] as $opt) {
                    if (trim((string) $opt) !== '') {
                        return true;
                    }
                }
            }

            return false;
        }

        if ($mode === 'choice' || $mode === 'multi_select') {
            return count($question['options'] ?? []) >= 1;
        }

        if ($mode === 'fill') {
            return trim((string) ($question['correct_text'] ?? '')) !== '';
        }

        return false;
    }

public function hasListeningQuestionPlayableContent(array $question): bool
    {
        $mode = (string) ($question['answer_mode'] ?? 'choice');

        if ($mode === 'word_link') {
            return $this->countCompleteWordLinkPairs($question['word_link_pairs'] ?? []) >= 1;
        }

        if ($mode === 'fill_blanks') {
            return $this->hasPlayableFillBlanks($question['fill_blanks'] ?? []);
        }

        return $this->hasPhatAmQuestionPlayableContent($question);
    }

public function hasListeningBundleDraftContent(array $listening): bool
    {
        $hasAudio = trim((string) ($listening['audio'] ?? '')) !== '';
        $hasText  = trim((string) ($listening['text'] ?? '')) !== '';

        if ($hasAudio || $hasText) {
            return true;
        }

        $questions = $listening['questions'] ?? [];

        if (! is_array($questions)) {
            return false;
        }

        if (count($questions) > 1) {
            return true;
        }

        foreach ($questions as $question) {
            if (! is_array($question)) {
                continue;
            }

            if ($this->hasListeningQuestionDraftContent($this->normalizeListeningQuestion($question))) {
                return true;
            }
        }

        return false;
    }

public function decodeListeningBundle(array $row): array
    {
        $bundle    = $this->decodePairOptionsBundle($row);
        $listening = $this->normalizeListeningData($bundle['listening']);
        $questions = $listening['questions'] ?? [];

        if ($questions === []) {
            $questions = [$this->defaultListeningQuestion()];
        }

        if (count($questions) === 1) {
            $question = $this->normalizeListeningQuestion($questions[0]);
            $mode     = (string) ($question['answer_mode'] ?? 'choice');

            if ($mode === 'choice' && ($question['options'] ?? []) === []) {
                $question['options'] = $this->decodeChoiceOptions($row);
                $question['correct'] = strtolower(trim((string) ($row['correct_answer'] ?? 'a')));
            } elseif ($mode === 'fill' && trim((string) ($question['correct_text'] ?? '')) === '') {
                $question['correct_text'] = trim(strip_tags((string) ($row['correct_answer'] ?? '')));
            } elseif ($mode === 'true_false' && ! $this->hasPlayableTrueFalseItems($question['true_false_items'] ?? [])) {
                $question['true_false_items'] = $this->decodeTrueFalseItems($row);
            } elseif ($mode === 'multi_choice' && ! $this->hasPlayableMultiChoiceItems($question['multi_choice_items'] ?? [])) {
                $question['multi_choice_items'] = $this->decodeMultiChoiceItems($row);
            } elseif ($mode === 'word_link' && count($question['word_link_pairs'] ?? []) === 0) {
                $question['word_link_pairs'] = $this->decodePlayableWordLinkPairs($row);
            } elseif ($mode === 'fill_blanks' && ! $this->hasPlayableFillBlanks($question['fill_blanks'] ?? [])) {
                $question['fill_blanks'] = $this->decodeFillBlanksData($row);
            }

            $questions = [$question];
        } else {
            $questions = array_values(array_map(
                fn (array $question): array => $this->normalizeListeningQuestion($question),
                $questions
            ));
        }

        return [
            'text'      => trim((string) ($listening['text'] ?? '')),
            'audio'     => trim((string) ($listening['audio'] ?? '')),
            'lang'      => (string) ($listening['lang'] ?? 'ko-KR'),
            'questions' => $questions,
        ];
    }

public function decodeListeningQuestions(array $row): array
    {
        return $this->decodeListeningBundle($row)['questions'];
    }

public function decodeListeningStudentAnswersPayload(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        if (isset($decoded['answers']) && is_array($decoded['answers'])) {
            return array_values($decoded['answers']);
        }

        return [];
    }

public function listeningStudentQuestionIsComplete(array $subAnswer, array $expectedQuestion): bool
    {
        $mode = (string) ($expectedQuestion['answer_mode'] ?? 'choice');

        if ($mode === 'word_link') {
            $payload = $subAnswer;

            if (isset($subAnswer['links']) || isset($subAnswer['status'])) {
                return ($payload['status'] ?? '') === 'completed'
                    && is_array($payload['links'] ?? null)
                    && count($payload['links']) === count($this->normalizeWordLinkPairRows($expectedQuestion['word_link_pairs'] ?? []));
            }

            return false;
        }

        if ($mode === 'fill_blanks') {
            $blanks = $subAnswer['blanks'] ?? [];

            if (! is_array($blanks)) {
                return false;
            }

            $expected = $this->normalizeFillBlanksData($expectedQuestion['fill_blanks'] ?? [])['answers'];

            return count($expected) >= 1 && count($blanks) === count($expected);
        }

        return $this->phatAmStudentQuestionIsComplete($subAnswer, $expectedQuestion);
    }

public function listeningStudentSubmissionIsComplete(array $row, string $raw): bool
    {
        $expectedQuestions = $this->decodeListeningQuestions($row);
        $submittedAnswers  = $this->decodeListeningStudentAnswersPayload($raw);

        if ($expectedQuestions === [] || count($submittedAnswers) !== count($expectedQuestions)) {
            return false;
        }

        foreach ($expectedQuestions as $index => $expectedQuestion) {
            $subAnswer = is_array($submittedAnswers[$index] ?? null) ? $submittedAnswers[$index] : [];

            if (! $this->listeningStudentQuestionIsComplete($subAnswer, $expectedQuestion)) {
                return false;
            }
        }

        return true;
    }

public function gradeWordLinkPairsSubmission(array $expectedPairs, array $subPayload): bool
    {
        if (($subPayload['status'] ?? '') !== 'completed') {
            return false;
        }

        $links = $subPayload['links'] ?? [];

        if (! is_array($links)) {
            return false;
        }

        $expectedCount = count($this->normalizeWordLinkPairRows($expectedPairs));

        if ($expectedCount < 2 || count($links) !== $expectedCount) {
            return false;
        }

        foreach ($links as $link) {
            if (! is_array($link)) {
                return false;
            }

            if ((string) ($link['a'] ?? '') !== (string) ($link['b'] ?? '')) {
                return false;
            }
        }

        return true;
    }

public function gradeFillBlanksDataSubmission(array $expectedData, array $subPayload): bool
    {
        $submitted = $subPayload['blanks'] ?? [];

        if (! is_array($submitted)) {
            return false;
        }

        $expected = $this->normalizeFillBlanksData($expectedData)['answers'];

        if (count($expected) < 1 || count($submitted) !== count($expected)) {
            return false;
        }

        foreach ($expected as $index => $answer) {
            $student = trim((string) ($submitted[$index] ?? ''));

            if ($student === '') {
                return false;
            }

            if (! $this->isFillBlankAnswerCorrect($student, (string) $answer)) {
                return false;
            }
        }

        return true;
    }

public function gradeListeningQuestionSubmission(array $question, array $subPayload): bool
    {
        $mode = (string) ($question['answer_mode'] ?? 'choice');

        if ($mode === 'word_link') {
            return $this->gradeWordLinkPairsSubmission($question['word_link_pairs'] ?? [], $subPayload);
        }

        if ($mode === 'fill_blanks') {
            return $this->gradeFillBlanksDataSubmission($question['fill_blanks'] ?? [], $subPayload);
        }

        return $this->gradePhatAmItemSubmission($question, $subPayload);
    }

public function normalizeListeningAudioPath(string $value): string
    {
        $value = trim(str_replace('\\', '/', $value));

        if ($value === '') {
            return '';
        }

        $bankModel = model(QuestionBankItemModel::class);
        $normalized = $bankModel->normalizeMediaReference($value);

        if ($normalized !== '' && $bankModel->isValidStoredAudioPath($normalized)) {
            return $normalized;
        }

        if (preg_match('#^https?://#i', $normalized) || str_starts_with($normalized, '//')) {
            return $normalized;
        }

        return '';
    }

public function resolveListeningAudioUrl(string $path): string
    {
        $path = $this->normalizeListeningAudioPath($path);

        if ($path === '') {
            return '';
        }

        return MediaUrl::to($path);
    }

public function hasListeningPlayback(array $listening): bool
    {
        return trim((string) ($listening['audio'] ?? '')) !== ''
            || trim((string) ($listening['text'] ?? '')) !== '';
    }

public function decodeListeningConfig(array $row): array
    {
        return $this->normalizeListeningConfig($this->decodeListeningBundle($row));
    }

public function decodeChoiceOptions(array $row): array
    {
        if (empty($row['options_json'])) {
            return [];
        }

        $decoded = json_decode((string) $row['options_json'], true);

        if (! is_array($decoded)) {
            return [];
        }

        if (isset($decoded['options']) && is_array($decoded['options'])) {
            return array_values(array_filter(array_map('strval', $decoded['options']), static fn ($v) => $v !== ''));
        }

        if (isset($decoded['pairs']) || isset($decoded['memory_pairs']) || isset($decoded['word_link_pairs']) || isset($decoded['word_order']) || isset($decoded['listening']) || isset($decoded['category_sort']) || isset($decoded['fill_blanks']) || isset($decoded['word_select']) || isset($decoded['word_translate']) || isset($decoded['phat_am']) || isset($decoded['image_hotspot']) || isset($decoded['image_label_drop'])) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded), static fn ($v) => $v !== ''));
    }

public function evaluateListeningItems(array $row, string $rawSelected): array
    {
        $items = $this->decodeListeningQuestions($row);
        $count = count($items);

        if ($count === 0) {
            return [];
        }

        $rawSelected = trim($rawSelected);

        if ($rawSelected === '') {
            return array_fill(0, $count, false);
        }

        $decoded = json_decode($rawSelected, true);

        if (is_array($decoded) && isset($decoded['answers']) && is_array($decoded['answers'])) {
            $flags = [];

            foreach ($items as $index => $question) {
                $subPayload = $decoded['answers'][$index] ?? null;
                $flags[]    = is_array($subPayload) && $this->gradeListeningQuestionSubmission($question, $subPayload);
            }

            return $flags;
        }

        $allCorrect = $this->gradeListeningLegacySubmission($row, $rawSelected, is_array($decoded) ? $decoded : null);

        return array_fill(0, $count, $allCorrect);
    }

private function gradeListeningLegacySubmission(array $row, string $rawSelected, ?array $decoded): bool
    {
        $listening = $this->decodeListeningConfig($row);
        $mode      = (string) ($listening['answer_mode'] ?? 'choice');
        $questions = $this->decodeListeningQuestions($row);
        $question  = $questions[0] ?? $this->defaultListeningQuestion();

        if ($mode === 'fill') {
            $expected = trim((string) ($question['correct_text'] ?? strip_tags((string) ($row['correct_answer'] ?? ''))));
            $selected = trim($rawSelected);

            if ($selected === '' || $expected === '') {
                return false;
            }

            return $this->normalizeListeningEssayAnswer($selected) === $this->normalizeListeningEssayAnswer($expected);
        }

        if (in_array($mode, ['true_false', 'multi_choice', 'word_link', 'fill_blanks'], true)) {
            if (! is_array($decoded)) {
                return false;
            }

            return $this->gradeListeningQuestionSubmission($question, $decoded);
        }

        $selected = strtolower(trim($rawSelected));
        $expected = strtolower(trim((string) ($question['correct'] ?? ($row['correct_answer'] ?? ''))));

        return $selected !== '' && $expected !== '' && $selected === $expected;
    }

public function gradeListeningSubmission(array $row, string $rawSelected): bool
    {
        $flags = $this->evaluateListeningItems($row, $rawSelected);

        return $flags !== [] && ! in_array(false, $flags, true);
    }

public function gradePhatAmItemSubmission(array $item, array $subPayload): bool
    {
        $recording = trim((string) ($subPayload['recording'] ?? ''));
        $mode      = (string) ($item['answer_mode'] ?? 'choice');

        if ($recording !== '') {
            unset($subPayload['recording']);
        }

        if ($recording !== '' && $subPayload === []) {
            return false;
        }

        if ($mode === 'fill') {
            $expected = trim((string) ($item['correct_text'] ?? ''));
            $selected = trim((string) ($subPayload['text'] ?? ($subPayload['answer'] ?? '')));

            if ($selected === '' || $expected === '') {
                return false;
            }

            return $this->normalizeListeningEssayAnswer($selected) === $this->normalizeListeningEssayAnswer($expected);
        }

        if ($mode === 'true_false') {
            $submitted = $subPayload['answers'] ?? [];

            if (! is_array($submitted)) {
                return false;
            }

            $expectedItems = $item['true_false_items'] ?? [];

            if (! $this->hasPlayableTrueFalseItems($expectedItems) || count($submitted) !== count($expectedItems)) {
                return false;
            }

            foreach ($expectedItems as $index => $tfItem) {
                $studentVal = strtolower(trim((string) ($submitted[$index] ?? '')));

                if (! in_array($studentVal, ['true', 'false'], true)) {
                    return false;
                }

                $expectedBool = ! empty($tfItem['answer']);

                if (($studentVal === 'true') !== $expectedBool) {
                    return false;
                }
            }

            return true;
        }

        if ($mode === 'multi_choice') {
            $submitted = $subPayload['answers'] ?? [];

            if (! is_array($submitted)) {
                return false;
            }

            $expectedItems = $item['multi_choice_items'] ?? [];

            if (! $this->hasPlayableMultiChoiceItems($expectedItems) || count($submitted) !== count($expectedItems)) {
                return false;
            }

            foreach ($expectedItems as $index => $mcItem) {
                $studentVal = strtolower(trim((string) ($submitted[$index] ?? '')));
                $expected   = strtolower(trim((string) ($mcItem['correct'] ?? '')));
                $optionKeys = $this->multiChoiceOptionKeys(count($mcItem['options'] ?? []));

                if (! in_array($studentVal, $optionKeys, true) || ! in_array($expected, $optionKeys, true)) {
                    return false;
                }

                if ($studentVal !== $expected) {
                    return false;
                }
            }

            return true;
        }

        if ($mode === 'multi_select') {
            $selected = (string) ($subPayload['choice'] ?? ($subPayload['answer'] ?? ''));
            $expected = (string) ($item['correct'] ?? '');

            return $this->gradeMultiSelectAnswer($selected, $expected);
        }

        $selected = strtolower(trim((string) ($subPayload['choice'] ?? ($subPayload['answer'] ?? ''))));
        $expected = strtolower(trim((string) ($item['correct'] ?? '')));

        return $selected !== '' && $expected !== '' && $selected === $expected;
    }

public function evaluatePhatAmItems(array $row, string $rawSelected): array
    {
        $items = $this->decodePhatAmQuestions($row);
        $count = count($items);

        if ($count === 0) {
            $items = $this->decodePhatAmItems($row);
            $count = count($items);
        }

        if ($count === 0) {
            return [];
        }

        $rawSelected = trim($rawSelected);

        if ($rawSelected === '') {
            return array_fill(0, $count, false);
        }

        $decoded = json_decode($rawSelected, true);

        if (! is_array($decoded)) {
            return array_fill(0, $count, false);
        }

        if (isset($decoded['answers']) && is_array($decoded['answers'])) {
            $flags = [];

            foreach ($items as $index => $item) {
                $subPayload = $decoded['answers'][$index] ?? null;
                $flags[]    = is_array($subPayload) && $this->gradePhatAmItemSubmission($item, $subPayload);
            }

            return $flags;
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $expectedItems = $this->decodePhatAmItems($row);
            $flags         = [];

            foreach ($expectedItems as $index => $item) {
                $subPayload = $decoded['items'][$index] ?? null;
                $flags[]    = is_array($subPayload) && $this->gradePhatAmItemSubmission($item, $subPayload);
            }

            return $flags !== [] ? $flags : array_fill(0, $count, false);
        }

        $allCorrect = $this->gradePhatAmLegacySubmission($row, $rawSelected, $decoded);

        return array_fill(0, $count, $allCorrect);
    }

private function gradePhatAmLegacySubmission(array $row, string $rawSelected, array $decoded): bool
    {
        $legacyPayload = $this->decodePhatAmStudentPayload($rawSelected);
        $items         = $this->decodePhatAmItems($row);

        if ($items === []) {
            return false;
        }

        if ($legacyPayload['recording'] !== '' && $legacyPayload['answer'] === '') {
            return false;
        }

        $subPayload = ['recording' => $legacyPayload['recording']];

        if ($legacyPayload['answer'] !== '') {
            $mode = (string) ($items[0]['answer_mode'] ?? 'choice');

            if ($mode === 'choice') {
                $subPayload['choice'] = $legacyPayload['answer'];
            } elseif ($mode === 'fill') {
                $subPayload['text'] = $legacyPayload['answer'];
            } else {
                $answerDecoded = json_decode($legacyPayload['answer'], true);

                if (is_array($answerDecoded)) {
                    $subPayload = array_merge($subPayload, $answerDecoded);
                }
            }
        }

        return $this->gradePhatAmItemSubmission($items[0], $subPayload);
    }

public function gradePhatAmSubmission(array $row, string $rawSelected): bool
    {
        $flags = $this->evaluatePhatAmItems($row, $rawSelected);

        return $flags !== [] && ! in_array(false, $flags, true);
    }

public function normalizeTrueFalseItems(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $statement = trim((string) ($row['statement'] ?? $row['text'] ?? ''));

            if ($statement === '') {
                continue;
            }

            $answerRaw = $row['answer'] ?? $row['is_true'] ?? true;
            $answer    = filter_var($answerRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($answer === null) {
                $answer = in_array(strtolower(trim((string) $answerRaw)), ['true', '1', 'yes', 'dung', 'đúng'], true);
            }

            $items[] = [
                'statement' => $statement,
                'answer'    => (bool) $answer,
            ];
        }

        return $items;
    }

public function hasPlayableTrueFalseItems(array $items): bool
    {
        return count($items) >= 1;
    }

public function decodeTrueFalseItems(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['true_false_items'];
    }

public function multiChoiceOptionKeys(int $count): array
    {
        $keys = [];

        for ($i = 0; $i < $count && $i < 8; $i++) {
            $keys[] = chr(97 + $i);
        }

        return $keys;
    }

public function normalizeMultiChoiceItems(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $question = trim((string) ($row['question'] ?? $row['statement'] ?? $row['text'] ?? ''));

            if ($question === '') {
                continue;
            }

            $optionsRaw = $row['options'] ?? [];
            $options    = [];

            if (is_array($optionsRaw)) {
                foreach ($optionsRaw as $option) {
                    $text = trim((string) $option);

                    if ($text !== '') {
                        $options[] = $text;
                    }
                }
            }

            if (count($options) < 2) {
                continue;
            }

            $correct = strtolower(trim((string) ($row['correct'] ?? $row['correct_answer'] ?? 'a')));
            $keys    = $this->multiChoiceOptionKeys(count($options));

            if (! in_array($correct, $keys, true)) {
                $correct = $keys[0];
            }

            $items[] = [
                'question' => $question,
                'options'  => $options,
                'correct'  => $correct,
            ];
        }

        return $items;
    }

public function hasPlayableMultiChoiceItems(array $items): bool
    {
        return count($items) >= 1;
    }

public function decodeMultiChoiceItems(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['multi_choice_items'];
    }

private function normalizeListeningEssayAnswer(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

public function normalizeWordOrderTokens(array $rows): array
    {
        $tokens = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $text = trim((string) ($row['text'] ?? $row['value'] ?? ''));
            } else {
                $text = trim((string) $row);
            }

            if ($text !== '') {
                $tokens[] = $text;
            }
        }

        return $tokens;
    }

public function decodeWordOrderTokens(array $row): array
    {
        return $this->decodePairOptionsBundle($row)['word_order'];
    }

public function decodePlayableWordOrderTokens(array $row): array
    {
        $tokens = $this->decodeWordOrderTokens($row);
        $playable = [];

        foreach ($tokens as $index => $text) {
            $playable[] = [
                'id'   => (string) $index,
                'text' => $text,
            ];
        }

        return $playable;
    }

public function formatWordOrderSentence(array $row, string $separator = ' '): string
    {
        return implode($separator, $this->decodeWordOrderTokens($row));
    }

public function gradeWordLinkSubmission(array $row, string $rawSelected): bool
    {
        $payload = json_decode($rawSelected, true);

        if (! is_array($payload) || ($payload['status'] ?? '') !== 'completed') {
            return false;
        }

        $links = $payload['links'] ?? [];

        if (! is_array($links)) {
            return false;
        }

        $expectedCount = count($this->decodePlayableWordLinkPairs($row));

        if ($expectedCount < 2 || count($links) !== $expectedCount) {
            return false;
        }

        foreach ($links as $link) {
            if (! is_array($link)) {
                return false;
            }

            if ((string) ($link['a'] ?? '') !== (string) ($link['b'] ?? '')) {
                return false;
            }
        }

        return true;
    }

public function gradeWordOrderSubmission(array $row, string $rawSelected): bool
    {
        $payload = json_decode($rawSelected, true);

        if (! is_array($payload) || ($payload['status'] ?? '') !== 'completed') {
            return false;
        }

        $order = $payload['order'] ?? [];

        if (! is_array($order)) {
            return false;
        }

        $expected = $this->decodePlayableWordOrderTokens($row);
        $expectedCount = count($expected);

        if ($expectedCount < 2 || count($order) !== $expectedCount) {
            return false;
        }

        foreach ($expected as $index => $token) {
            if ((string) ($order[$index] ?? '') !== (string) ($token['id'] ?? '')) {
                return false;
            }
        }

        return true;
    }

    public function storePairImageUpload(?\Illuminate\Http\UploadedFile $file): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        $mime = strtolower((string) $file->getMimeType());
        $ext  = match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png')  => 'png',
            str_contains($mime, 'gif')  => 'gif',
            str_contains($mime, 'webp') => 'webp',
            default                     => null,
        };

        if ($ext === null) {
            return null;
        }

        $uploadDir = public_path('uploads/study-programs/pairs');

        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            return null;
        }

        $fileName = 'pair-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $file->move($uploadDir, $fileName);

        return 'uploads/study-programs/pairs/' . $fileName;
    }
}
