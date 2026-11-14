<?php

namespace App\Services\Api;

use App\Support\Questions\QuestionBankItemModel;
use App\Support\Questions\QuestionParser;

class SessionQuestionApiFormatter
{
    /** @var array<string, string> */
    private const TYPE_LABELS = [
        'choice'           => 'Trắc nghiệm 1 đáp án',
        'multi_select'     => 'Trắc nghiệm nhiều đáp án',
        'fill'             => 'Tự luận / điền từ',
        'essay'            => 'Tự luận',
        'memory_match'     => 'Lật ảnh ghép cặp',
        'word_link'        => 'Nối cặp',
        'word_order'       => 'Sắp xếp từ',
        'listening'        => 'Nghe hiểu',
        'category_sort'    => 'Phân loại',
        'fill_blanks'      => 'Điền vào chỗ trống',
        'word_select'      => 'Chọn từ điền chỗ trống',
        'word_translate'   => 'Dịch từ',
        'phat_am'          => 'Phát âm',
        'image_hotspot'    => 'Chọn điểm trên ảnh',
        'image_label_drop' => 'Kéo nhãn vào ảnh',
    ];

    protected QuestionParser $questionModel;

    protected QuestionBankItemModel $bankModel;

    public function __construct(QuestionParser $questionModel, QuestionBankItemModel $bankModel)
    {
        $this->questionModel = $questionModel;
        $this->bankModel = $bankModel;
    }

    /**
     * @param list<array<string, mixed>> $questions
     * @param array<int, array<string, mixed>> $answerMap
     *
     * @return list<array<string, mixed>>
     */
    public function formatQuestions(array $questions, array $answerMap, bool $showResults): array
    {
        $answerLabels = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
        $out          = [];

        foreach ($questions as $index => $question) {
            $question     = $this->questionModel->normalizeQuestionRowForEdit($question);
            $questionId   = (int) ($question['id'] ?? 0);
            $questionType = (string) ($question['question_type'] ?? 'choice');

            if ($questionType === 'text') {
                $questionType = 'fill';
            }

            $answerRow   = $answerMap[$questionId] ?? [];
            $options     = $this->decodeOptionsJson($question['options_json'] ?? null);
            $choiceOpts  = $this->questionModel->decodeChoiceOptions($question);
            $hasAnswer   = $answerRow !== [];
            $rawSelected = trim((string) ($answerRow['selected_answer'] ?? ''));

            $item = [
                'id'                  => $questionId,
                'sort_order'          => (int) ($question['sort_order'] ?? $index),
                'question_type'       => $questionType,
                'question_type_label' => self::TYPE_LABELS[$questionType] ?? $questionType,
                'question_text'       => (string) ($question['question_text'] ?? ''),
                'question_plain'      => QuestionParser::plainTextFromHtml((string) ($question['question_text'] ?? '')),
                'options'             => $options,
                'choice_options'      => $choiceOpts,
                'has_answer'          => $hasAnswer,
            ];

            $this->appendChoiceOptionsLabeled($item, $choiceOpts, $answerLabels);
            $this->appendTypeConfig($item, $question, $questionType, $showResults);

            if ($hasAnswer) {
                $this->appendStudentAnswer($item, $question, $questionType, $rawSelected, $answerLabels);
                $item['is_correct'] = ! empty($answerRow['is_correct']);
                $this->appendTeacherFeedback($item, $question, $questionType, $answerRow);
            }

            if ($showResults && $hasAnswer) {
                $this->appendCorrectAnswer($item, $question, $questionType, $answerLabels);
                $item['explanation'] = (string) ($question['answer_explanation'] ?? '');
            }

            $this->resolveQuestionItemMedia($item, $showResults);
            $this->enrichPlayableDetails($item, $questionType, $showResults);
            $this->appendDisplayMeta($item, $questionType);
            $this->appendAnswerFormat($item, $questionType);
            $this->sanitizeQuestionForStudent($item, $questionType, $showResults);

            $out[] = $item;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $item
     * @param list<string> $choiceOpts
     * @param array<string, string> $answerLabels
     */
    private function appendChoiceOptionsLabeled(array &$item, array $choiceOpts, array $answerLabels): void
    {
        if ($choiceOpts === []) {
            return;
        }

        $labeled = [];

        foreach ($choiceOpts as $index => $value) {
            $key = chr(ord('a') + $index);

            if ($index >= count($answerLabels)) {
                break;
            }

            $labeled[] = array_merge([
                'key'   => $key,
                'label' => $answerLabels[$key] ?? strtoupper($key),
                'value' => $value,
            ], $this->describeOptionMedia((string) $value));
        }

        $item['choice_options_labeled'] = $labeled;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $question
     */
    private function appendTypeConfig(array &$item, array $question, string $questionType, bool $showResults): void
    {
        switch ($questionType) {
            case 'listening':
                $item['listening'] = $this->buildListeningPlayable($question, $showResults);
                $item['listening_questions'] = $item['listening']['sub_items'] ?? [];
                break;

            case 'phat_am':
                $item['phat_am'] = $this->buildPhatAmPlayable($question, $showResults);
                break;

            case 'word_translate':
                $item['word_translate'] = $this->buildWordTranslatePlayable($question, $showResults);
                break;

            case 'word_link':
                $item['word_link'] = $this->buildWordLinkPlayable($question);
                break;

            case 'word_order':
                $item['word_order'] = [
                    'tokens'       => $this->questionModel->decodePlayableWordOrderTokens($question),
                    'token_count'  => count($this->questionModel->decodeWordOrderTokens($question)),
                ];
                break;

            case 'category_sort':
                $item['category_sort'] = $this->questionModel->decodePlayableCategorySort($question);
                break;

            case 'fill_blanks':
                $item['fill_blanks'] = $this->buildFillBlanksPlayable($question, $showResults);
                break;

            case 'word_select':
                $item['word_select'] = $this->buildWordSelectPlayable($question, $showResults);
                break;

            case 'memory_match':
                $item['memory_match'] = $this->buildMemoryMatchPlayable($question);
                break;

            case 'image_hotspot':
                $item['image_hotspot'] = $this->buildImageHotspotPlayable($question, $showResults);
                break;

            case 'image_label_drop':
                $item['image_label_drop'] = $this->buildImageLabelDropPlayable($question, $showResults);
                break;
        }
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function buildWordLinkPlayable(array $question): array
    {
        return $this->buildWordLinkPlayableFromPairs(
            $this->questionModel->decodePlayableWordLinkPairs($question)
        );
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function buildMemoryMatchPlayable(array $question): array
    {
        $pairs = $this->questionModel->decodePlayableMemoryPairs($question);
        $cards = [];

        foreach ($pairs as $index => $pair) {
            $pairId = (string) $index;
            $image  = (string) ($pair['image'] ?? '');
            $label  = (string) ($pair['label'] ?? '');

            if ($image === '' || $label === '') {
                continue;
            }

            $cards[] = [
                'card_id'    => 'card-' . (count($cards)),
                'pair_id'    => $pairId,
                'kind'       => 'image',
                'face'       => 'image',
                'image'      => $image,
                'image_url'  => $image,
                'label'      => $label,
                'label_text' => $label,
                'back_text'  => '?',
            ];
            $cards[] = [
                'card_id'    => 'card-' . (count($cards)),
                'pair_id'    => $pairId,
                'kind'       => 'text',
                'face'       => 'text',
                'image'      => $image,
                'image_url'  => $image,
                'label'      => $label,
                'label_text' => $label,
                'back_text'  => '?',
            ];
        }

        return [
            'pair_count'  => count($pairs),
            'pairs'       => $pairs,
            'cards'       => $cards,
            'total_cards' => count($cards),
            'shuffle'     => true,
        ];
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function buildFillBlanksPlayable(array $question, bool $showResults): array
    {
        $data     = $this->questionModel->decodeFillBlanksData($question);
        $template = (string) ($data['template'] ?? '');

        $out = [
            'template'    => $template,
            'line_groups' => $this->questionModel->parseFillBlanksLineGroups($template),
            'blank_count' => count($data['answers'] ?? []),
        ];
        $out['blank_slots'] = $this->buildBlankSlots($out['line_groups']);

        if ($showResults) {
            $out['answers'] = $data['answers'] ?? [];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function buildWordSelectPlayable(array $question, bool $showResults): array
    {
        $data     = $this->questionModel->decodeWordSelectData($question);
        $template = (string) ($data['template'] ?? '');

        $out = [
            'template'    => $template,
            'line_groups' => $this->questionModel->parseFillBlanksLineGroups($template),
            'word_bank'   => array_values($data['word_bank'] ?? []),
            'blank_count' => count($data['answers'] ?? []),
        ];
        $out['blank_slots'] = $this->buildBlankSlots($out['line_groups']);
        $out['chips']       = array_map(static fn (string $word): array => [
            'id'   => md5($word),
            'text' => $word,
        ], $out['word_bank']);

        if ($showResults) {
            $out['answers'] = $data['answers'] ?? [];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function buildWordTranslatePlayable(array $question, bool $showResults): array
    {
        $data  = $this->questionModel->decodeWordTranslateData($question);
        $items = [];

        foreach ($data['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [
                'source_type' => (string) ($item['source_type'] ?? 'text'),
                'source'      => (string) ($item['source'] ?? ''),
            ];

            if ($showResults) {
                $row['answer'] = (string) ($item['answer'] ?? '');
            }

            $items[] = $row;
        }

        return [
            'items'   => $items,
            'columns' => (int) ($data['columns'] ?? 1),
            'layout'  => (string) ($data['layout'] ?? 'vertical'),
        ];
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function buildImageHotspotPlayable(array $question, bool $showResults): array
    {
        $hotspot = $this->questionModel->decodeImageHotspotData($question);
        $image   = (string) ($hotspot['image'] ?? '');
        $targets = $hotspot['targets'] ?? [];

        if ($image !== '') {
            $resolved = $this->questionModel->resolveImageHotspotUrl($image);
            $hotspot['image']     = $resolved;
            $hotspot['image_url'] = $resolved;
        }

        $out = [
            'image'         => (string) ($hotspot['image'] ?? ''),
            'image_url'     => (string) ($hotspot['image_url'] ?? ''),
            'tolerance'     => (float) ($hotspot['tolerance'] ?? 8.0),
            'target_count'  => count($targets),
        ];

        if ($showResults) {
            $out['targets'] = $targets;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function buildImageLabelDropPlayable(array $question, bool $showResults): array
    {
        $labelDrop = $this->questionModel->decodeImageLabelDropData($question);
        $image     = (string) ($labelDrop['image'] ?? '');

        if ($image !== '') {
            $resolved = $this->questionModel->resolveImageLabelDropUrl($image);
            $labelDrop['image']     = $resolved;
            $labelDrop['image_url'] = $resolved;
        }

        $zones = array_map(static fn (array $zone): array => [
            'id'    => (string) ($zone['id'] ?? ''),
            'label' => (string) ($zone['label'] ?? ''),
            'x'     => (float) ($zone['x'] ?? 0),
            'y'     => (float) ($zone['y'] ?? 0),
        ], $labelDrop['zones'] ?? []);

        $labels = [];

        foreach ($labelDrop['labels'] ?? [] as $labelRow) {
            if (! is_array($labelRow)) {
                continue;
            }

            $entry = [
                'id'   => (string) ($labelRow['id'] ?? ''),
                'text' => (string) ($labelRow['text'] ?? ''),
            ];

            if ($showResults) {
                $entry['zone_id'] = (string) ($labelRow['zone_id'] ?? '');
            }

            $labels[] = $entry;
        }

        return [
            'image'       => (string) ($labelDrop['image'] ?? ''),
            'image_url'   => (string) ($labelDrop['image_url'] ?? ''),
            'zone_radius' => (float) ($labelDrop['zone_radius'] ?? 10.0),
            'zones'       => $zones,
            'labels'      => $labels,
            'label_count' => count($labels),
            'zone_count'  => count($zones),
        ];
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function buildListeningPlayable(array $question, bool $showResults): array
    {
        $config    = $this->questionModel->decodeListeningConfig($question);
        $rawItems  = $this->questionModel->decodeListeningQuestions($question);
        $text      = trim((string) ($config['text'] ?? ''));
        $audio     = trim((string) ($config['audio'] ?? ''));
        $audioUrl  = $audio !== '' ? $this->questionModel->resolveListeningAudioUrl($audio) : '';
        $playerMode = $audioUrl !== '' ? 'audio' : ($text !== '' ? 'tts' : 'none');
        $subItems  = [];

        foreach ($rawItems as $index => $subQuestion) {
            $subItems[] = $this->buildInteractiveSubItem(is_array($subQuestion) ? $subQuestion : [], $index, $showResults);
        }

        return [
            'player' => [
                'mode'      => $playerMode,
                'lang'      => (string) ($config['lang'] ?? 'ko-KR'),
                'text'      => $text,
                'audio'     => $audioUrl,
                'audio_url' => $audioUrl,
                'has_audio' => $audioUrl !== '',
                'has_text'  => $text !== '',
            ],
            'question_count' => count($subItems),
            'sub_items'      => $subItems,
            'text'           => $text,
            'audio'          => $audioUrl,
            'audio_url'      => $audioUrl,
            'lang'           => (string) ($config['lang'] ?? 'ko-KR'),
            'answer_mode'    => (string) ($config['answer_mode'] ?? 'choice'),
        ];
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function buildPhatAmPlayable(array $question, bool $showResults): array
    {
        $bundle   = $this->questionModel->decodePhatAmBundle($question);
        $subItems = [];

        foreach ($bundle['questions'] ?? [] as $index => $subQuestion) {
            $subItems[] = $this->buildInteractiveSubItem(is_array($subQuestion) ? $subQuestion : [], $index, $showResults);
        }

        return [
            'prompt'            => (string) ($bundle['prompt'] ?? ''),
            'require_recording' => (bool) ($bundle['require_recording'] ?? true),
            'question_count'    => count($subItems),
            'sub_items'         => $subItems,
            'questions'         => $subItems,
        ];
    }

    /**
     * @param array<string, mixed> $subQuestion
     *
     * @return array<string, mixed>
     */
    private function buildInteractiveSubItem(array $subQuestion, int $index, bool $showResults): array
    {
        $mode       = (string) ($subQuestion['answer_mode'] ?? 'choice');
        $modeLabels = [
            'choice'       => 'Trắc nghiệm',
            'fill'         => 'Tự luận ngắn',
            'true_false'   => 'Đúng / Sai',
            'multi_choice' => 'Nhiều câu trắc nghiệm',
            'word_link'    => 'Nối cặp',
            'fill_blanks'  => 'Điền chỗ trống',
        ];

        $choiceQuestion = trim((string) ($subQuestion['choice_question'] ?? ''));
        $item           = [
            'index'              => $index,
            'number'             => $index + 1,
            'answer_mode'        => $mode,
            'answer_mode_label'  => $modeLabels[$mode] ?? $mode,
            'choice_question'    => $choiceQuestion,
            'playable_type'      => $mode,
        ];

        switch ($mode) {
            case 'fill':
                $item['fill'] = [
                    'placeholder' => 'Viết lại / trả lời theo nội dung bạn nghe được...',
                    'multiline'   => true,
                    'rows'        => 4,
                ];
                break;

            case 'true_false':
                $statements = [];

                foreach ($subQuestion['true_false_items'] ?? [] as $tfIndex => $tfRow) {
                    if (! is_array($tfRow)) {
                        continue;
                    }

                    $statement = (string) ($tfRow['statement'] ?? '');
                    $row       = [
                        'index'          => (int) $tfIndex,
                        'number'         => (int) $tfIndex + 1,
                        'statement'      => $statement,
                        'statement_html' => $this->bankModel->renderQuestionText($statement),
                        'choices'        => [
                            ['key' => 'true', 'label' => 'Đúng'],
                            ['key' => 'false', 'label' => 'Sai'],
                        ],
                    ];

                    if ($showResults && array_key_exists('answer', $tfRow)) {
                        $row['correct'] = (bool) ($tfRow['answer'] ?? false);
                    }

                    $statements[] = $row;
                }

                $item['true_false'] = [
                    'item_count' => count($statements),
                    'items'      => $statements,
                ];
                break;

            case 'multi_choice':
                $mcItems = [];

                foreach ($subQuestion['multi_choice_items'] ?? [] as $mcIndex => $mcRow) {
                    if (! is_array($mcRow)) {
                        continue;
                    }

                    $mcQuestion = trim((string) ($mcRow['question'] ?? ''));
                    $options    = is_array($mcRow['options'] ?? null) ? array_values($mcRow['options']) : [];
                    $labeled    = $this->buildOptionsLabeled($options);
                    $row        = [
                        'index'          => (int) $mcIndex,
                        'number'         => (int) $mcIndex + 1,
                        'question'       => $mcQuestion,
                        'question_html'  => $this->bankModel->renderQuestionText($mcQuestion),
                        'options'        => array_column($labeled, 'value'),
                        'options_labeled'=> $labeled,
                    ];

                    if ($showResults && isset($mcRow['correct'])) {
                        $row['correct'] = (string) $mcRow['correct'];
                    }

                    $mcItems[] = $row;
                }

                $item['multi_choice'] = [
                    'item_count' => count($mcItems),
                    'items'      => $mcItems,
                ];
                break;

            case 'word_link':
                $pairs = $this->normalizeWordLinkPairsForPlay(
                    is_array($subQuestion['word_link_pairs'] ?? null) ? $subQuestion['word_link_pairs'] : []
                );
                $item['word_link'] = $this->buildWordLinkPlayableFromPairs($pairs);
                break;

            case 'fill_blanks':
                $fbData = is_array($subQuestion['fill_blanks'] ?? null)
                    ? $subQuestion['fill_blanks']
                    : ['template' => '', 'answers' => []];
                $template = (string) ($fbData['template'] ?? '');
                $lineGroups = $this->questionModel->parseFillBlanksLineGroups($template);
                $item['fill_blanks'] = [
                    'template'    => $template,
                    'line_groups' => $lineGroups,
                    'blank_count' => count($fbData['answers'] ?? []),
                    'blank_slots' => $this->buildBlankSlots($lineGroups),
                ];

                if ($showResults) {
                    $item['fill_blanks']['answers'] = $fbData['answers'] ?? [];
                }
                break;

            default:
                $options = is_array($subQuestion['options'] ?? null) ? array_values($subQuestion['options']) : [];
                $labeled = $this->buildOptionsLabeled($options);
                $item['choice'] = [
                    'option_count'     => count($labeled),
                    'options'          => array_column($labeled, 'value'),
                    'options_labeled'  => $labeled,
                ];

                if ($showResults) {
                    if (isset($subQuestion['correct'])) {
                        $item['choice']['correct'] = (string) $subQuestion['correct'];
                    }

                    if (isset($subQuestion['correct_text'])) {
                        $item['choice']['correct_text'] = (string) $subQuestion['correct_text'];
                    }
                }
                break;
        }

        return $item;
    }

    /**
     * @param list<string> $options
     *
     * @return list<array<string, mixed>>
     */
    private function buildOptionsLabeled(array $options): array
    {
        $labels = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
        $out    = [];

        foreach ($options as $index => $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $key = chr(ord('a') + $index);

            if ($index >= 4) {
                break;
            }

            $out[] = array_merge([
                'key'   => $key,
                'label' => $labels[$key] ?? strtoupper($key),
                'value' => $value,
            ], $this->describeOptionMedia($value));
        }

        return $out;
    }

    /**
     * @return array{is_image: bool, image_url: string|null, text: string, text_plain: string}
     */
    private function describeOptionMedia(string $rawValue): array
    {
        $rawValue = trim($rawValue);
        $isImage  = $rawValue !== '' && $this->bankModel->isOptionImage($rawValue);
        $resolved = $isImage ? $this->resolveOptionValue($rawValue) : $rawValue;

        return [
            'is_image'   => $isImage,
            'image_url'  => $isImage ? $resolved : null,
            'text'       => $isImage ? '' : $resolved,
            'text_plain' => $isImage ? '' : QuestionParser::plainTextFromHtml($resolved),
        ];
    }

    /**
     * @param list<array<string, mixed>> $pairs
     *
     * @return list<array<string, mixed>>
     */
    private function normalizeWordLinkPairsForPlay(array $pairs): array
    {
        $normalized = [];

        foreach ($pairs as $pair) {
            if (! is_array($pair)) {
                continue;
            }

            $normalized[] = [
                'a_type'  => (string) ($pair['a_type'] ?? 'text'),
                'a_value' => (string) ($pair['a_value'] ?? ''),
                'b_type'  => (string) ($pair['b_type'] ?? 'text'),
                'b_value' => (string) ($pair['b_value'] ?? ''),
            ];
        }

        return array_values(array_filter(
            $normalized,
            static fn (array $pair): bool => ($pair['a_value'] ?? '') !== '' && ($pair['b_value'] ?? '') !== ''
        ));
    }

    /**
     * @param list<array{a_type: string, a_value: string, b_type: string, b_value: string}> $pairs
     *
     * @return array<string, mixed>
     */
    private function buildWordLinkPlayableFromPairs(array $pairs): array
    {
        $left  = [];
        $right = [];

        foreach ($pairs as $index => $pair) {
            $pairId = (string) $index;

            if (($pair['a_type'] ?? '') === 'image' && ($pair['a_value'] ?? '') !== '') {
                $pair['a_value'] = $this->questionModel->resolveWordLinkMediaUrl((string) $pair['a_value']);
            }

            if (($pair['b_type'] ?? '') === 'image' && ($pair['b_value'] ?? '') !== '') {
                $pair['b_value'] = $this->questionModel->resolveWordLinkMediaUrl((string) $pair['b_value']);
            }

            $left[] = array_merge([
                'id'    => $pairId,
                'side'  => 'left',
                'slot'  => 'a',
                'type'  => (string) ($pair['a_type'] ?? 'text'),
                'value' => (string) ($pair['a_value'] ?? ''),
            ], $this->describeSideMedia((string) ($pair['a_type'] ?? 'text'), (string) ($pair['a_value'] ?? '')));

            $right[] = array_merge([
                'id'    => $pairId,
                'side'  => 'right',
                'slot'  => 'b',
                'type'  => (string) ($pair['b_type'] ?? 'text'),
                'value' => (string) ($pair['b_value'] ?? ''),
            ], $this->describeSideMedia((string) ($pair['b_type'] ?? 'text'), (string) ($pair['b_value'] ?? '')));
        }

        return [
            'pair_count'  => count($pairs),
            'pairs'       => $pairs,
            'left_items'  => $left,
            'right_items' => $right,
            'columns'     => [
                ['id' => 'left', 'title' => 'Bên trái', 'items' => $left],
                ['id' => 'right', 'title' => 'Bên phải', 'items' => $right],
            ],
            'match_rule'  => 'links[].a và links[].b cùng pair_id khi nối đúng',
        ];
    }

    /**
     * @return array{is_image: bool, image_url: string|null, text: string, text_plain: string}
     */
    private function describeSideMedia(string $type, string $value): array
    {
        if ($type === 'image') {
            return [
                'is_image'   => true,
                'image_url'  => $value,
                'text'       => '',
                'text_plain' => '',
            ];
        }

        return [
            'is_image'   => false,
            'image_url'  => null,
            'text'       => $value,
            'text_plain' => $value,
        ];
    }

    /**
     * @param list<list<array{type: string, value?: string, index?: int}>> $lineGroups
     *
     * @return list<array<string, mixed>>
     */
    private function buildBlankSlots(array $lineGroups): array
    {
        $slots = [];

        foreach ($lineGroups as $lineIndex => $parts) {
            foreach ($parts as $partIndex => $part) {
                if (! is_array($part) || ($part['type'] ?? '') !== 'blank') {
                    continue;
                }

                $blankIndex = (int) ($part['index'] ?? count($slots));
                $slots[]    = [
                    'blank_index' => $blankIndex,
                    'line_index'  => $lineIndex,
                    'part_index'  => $partIndex,
                    'label'       => 'Ô ' . ($blankIndex + 1),
                ];
            }
        }

        return $slots;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function enrichPlayableDetails(array &$item, string $questionType, bool $showResults): void
    {
        if (! empty($item['choice_options_labeled']) && is_array($item['choice_options_labeled'])) {
            foreach ($item['choice_options_labeled'] as &$opt) {
                if (! is_array($opt)) {
                    continue;
                }

                $opt = array_merge($opt, $this->describeOptionMedia((string) ($opt['value'] ?? '')));
            }
            unset($opt);
        }

        if ($questionType === 'fill_blanks' && isset($item['fill_blanks']['line_groups'])) {
            $item['fill_blanks']['blank_slots'] = $this->buildBlankSlots($item['fill_blanks']['line_groups']);
        }

        if ($questionType === 'word_select' && isset($item['word_select']['line_groups'])) {
            $item['word_select']['blank_slots'] = $this->buildBlankSlots($item['word_select']['line_groups']);
            $item['word_select']['chips']       = array_map(static fn (string $word): array => [
                'text' => $word,
                'id'   => md5($word),
            ], $item['word_select']['word_bank'] ?? []);
        }

        if ($questionType === 'word_order' && isset($item['word_order']['tokens'])) {
            $tokens = $item['word_order']['tokens'];
            $item['word_order']['bank_label']     = 'Ngân hàng từ';
            $item['word_order']['sentence_label'] = 'Câu của bạn';
            $item['word_order']['min_tokens']     = 2;
            $item['word_order']['tokens']         = array_map(static fn (array $token, int $i): array => [
                'id'         => (string) ($token['id'] ?? $i),
                'text'       => (string) ($token['text'] ?? ''),
                'text_plain' => (string) ($token['text'] ?? ''),
            ], $tokens, array_keys($tokens));
        }

        if ($questionType === 'memory_match' && isset($item['memory_match']['cards'])) {
            $item['memory_match']['total_cards'] = count($item['memory_match']['cards']);
            $item['memory_match']['shuffle']     = true;
        }

        if ($questionType === 'word_link' && isset($item['word_link']['pairs'])) {
            $item['word_link'] = $this->buildWordLinkPlayableFromPairs($item['word_link']['pairs']);
        }

        if ($questionType === 'category_sort' && isset($item['category_sort']['groups'])) {
            foreach ($item['category_sort']['groups'] as $gi => &$group) {
                if (! is_array($group)) {
                    continue;
                }

                $group['color_index'] = (int) $gi % 4;
                $group['column_title'] = (string) ($group['label'] ?? 'Nhóm');
            }
            unset($group);

            $item['category_sort']['bank_label'] = 'Thẻ chưa phân loại';
            $item['category_sort']['item_count'] = count($item['category_sort']['items'] ?? []);
        }

        if ($questionType === 'word_translate' && isset($item['word_translate']['items'])) {
            foreach ($item['word_translate']['items'] as $ti => &$row) {
                if (! is_array($row)) {
                    continue;
                }

                $row['index']  = $ti;
                $row['number'] = $ti + 1;

                if (($row['source_type'] ?? '') === 'text') {
                    $row['source_html']  = $this->bankModel->renderQuestionText((string) ($row['source'] ?? ''));
                    $row['source_plain'] = QuestionParser::plainTextFromHtml((string) ($row['source'] ?? ''));
                } else {
                    $row['source_html']  = '';
                    $row['source_plain'] = '';
                }
            }
            unset($row);

            $cols = (int) ($item['word_translate']['columns'] ?? 1);
            $item['word_translate']['grid_class'] = $cols === 2 ? 'cols-2' : 'cols-1';
        }

        if ($questionType === 'image_hotspot' && isset($item['image_hotspot'])) {
            $item['image_hotspot']['coordinate_unit'] = 'percent';
            $item['image_hotspot']['click_limit']     = (int) ($item['image_hotspot']['target_count'] ?? 0);
            $item['image_hotspot']['allow_reset']     = true;
        }

        if ($questionType === 'image_label_drop' && isset($item['image_label_drop'])) {
            $item['image_label_drop']['coordinate_unit'] = 'percent';
            $item['image_label_drop']['bank_label']      = 'Nhãn cần kéo';
            $item['image_label_drop']['shuffle_labels']  = true;

            foreach ($item['image_label_drop']['zones'] ?? [] as &$zone) {
                if (! is_array($zone)) {
                    continue;
                }

                $zone['radius'] = (float) ($item['image_label_drop']['zone_radius'] ?? 10.0);
            }
            unset($zone);
        }

        if ($questionType === 'listening' && isset($item['listening']['sub_items'])) {
            foreach ($item['listening']['sub_items'] as &$sub) {
                if (! is_array($sub)) {
                    continue;
                }

                if ($choiceQuestion = (string) ($sub['choice_question'] ?? '')) {
                    $sub['title_html'] = $this->bankModel->renderQuestionText($choiceQuestion);
                }
            }
            unset($sub);
        }

        if ($questionType === 'phat_am' && isset($item['phat_am']['sub_items'])) {
            foreach ($item['phat_am']['sub_items'] as &$sub) {
                if (! is_array($sub)) {
                    continue;
                }

                if ($choiceQuestion = (string) ($sub['choice_question'] ?? '')) {
                    $sub['title_html'] = $this->bankModel->renderQuestionText($choiceQuestion);
                }
            }
            unset($sub);
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private function appendDisplayMeta(array &$item, string $questionType): void
    {
        $meta = [
            'choice' => [
                'component'   => 'QuizChoice',
                'interaction' => 'single_select',
                'hint'        => 'Chọn một đáp án A, B, C hoặc D.',
            ],
            'multi_select' => [
                'component'   => 'QuizMultiSelect',
                'interaction' => 'multi_select',
                'hint'        => 'Chọn nhiều đáp án (tick nhiều ô).',
            ],
            'fill' => [
                'component'   => 'QuizTextarea',
                'interaction' => 'text_input',
                'hint'        => 'Viết câu trả lời của bạn.',
            ],
            'memory_match' => [
                'component'   => 'MemoryMatchBoard',
                'interaction' => 'flip_match',
                'hint'        => 'Lật 2 thẻ mỗi lượt để ghép ảnh với chữ.',
            ],
            'word_link' => [
                'component'   => 'WordLinkBoard',
                'interaction' => 'connect_pairs',
                'hint'        => 'Nối từng mục bên trái với mục tương ứng bên phải.',
            ],
            'word_order' => [
                'component'   => 'WordOrderBoard',
                'interaction' => 'drag_reorder',
                'hint'        => 'Kéo thả hoặc bấm từ để ghép thành câu hoàn chỉnh.',
            ],
            'listening' => [
                'component'   => 'ListeningBoard',
                'interaction' => 'audio_then_answer',
                'hint'        => 'Nghe audio (hoặc đọc text) rồi trả lời các câu con bên dưới.',
            ],
            'category_sort' => [
                'component'   => 'CategorySortBoard',
                'interaction' => 'drag_to_group',
                'hint'        => 'Kéo từng thẻ vào đúng nhóm.',
            ],
            'fill_blanks' => [
                'component'   => 'FillBlanksSentence',
                'interaction' => 'inline_text_input',
                'hint'        => 'Gõ từ vào từng ô trống trong câu.',
            ],
            'word_select' => [
                'component'   => 'WordSelectSentence',
                'interaction' => 'pick_from_bank',
                'hint'        => 'Bấm ô trống rồi chọn từ trong danh sách bên dưới.',
            ],
            'word_translate' => [
                'component'   => 'WordTranslateGrid',
                'interaction' => 'text_input_per_item',
                'hint'        => 'Dịch từng mục sang tiếng Việt (hoặc ngôn ngữ đích).',
            ],
            'phat_am' => [
                'component'   => 'PhatAmBoard',
                'interaction' => 'record_and_answer',
                'hint'        => 'Nghe prompt, ghi âm phát âm và trả lời câu hỏi kèm theo.',
            ],
            'image_hotspot' => [
                'component'   => 'ImageHotspotCanvas',
                'interaction' => 'tap_image_points',
                'hint'        => 'Bấm lên ảnh để chọn đủ số điểm yêu cầu.',
            ],
            'image_label_drop' => [
                'component'   => 'ImageLabelDropBoard',
                'interaction' => 'drag_label_to_zone',
                'hint'        => 'Kéo từng nhãn từ danh sách vào đúng vùng trên hình.',
            ],
        ];

        if (isset($meta[$questionType])) {
            $item['display'] = $meta[$questionType];
        }
    }

    /**
     * @param list<array<string, mixed>> $questions
     *
     * @return list<array<string, mixed>>
     */
    private function sanitizeInteractiveQuestions(array $questions, bool $showResults): array
    {
        return array_map(
            fn (array $question): array => $this->sanitizeInteractiveQuestion($question, $showResults),
            $questions
        );
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function sanitizeInteractiveQuestion(array $question, bool $showResults): array
    {
        if ($showResults) {
            return $question;
        }

        unset($question['correct'], $question['correct_text']);

        if (isset($question['true_false_items']) && is_array($question['true_false_items'])) {
            $question['true_false_items'] = array_map(static function (array $row): array {
                unset($row['answer']);

                return $row;
            }, $question['true_false_items']);
        }

        if (isset($question['multi_choice_items']) && is_array($question['multi_choice_items'])) {
            $question['multi_choice_items'] = array_map(static function (array $row): array {
                unset($row['correct']);

                return $row;
            }, $question['multi_choice_items']);
        }

        if (isset($question['fill_blanks']) && is_array($question['fill_blanks'])) {
            $template = (string) ($question['fill_blanks']['template'] ?? '');
            $question['fill_blanks'] = [
                'template'    => $template,
                'line_groups' => $this->questionModel->parseFillBlanksLineGroups($template),
                'blank_count' => count($question['fill_blanks']['answers'] ?? []),
            ];
        }

        return $question;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function appendAnswerFormat(array &$item, string $questionType): void
    {
        $formats = [
            'choice' => [
                'value_type'  => 'string',
                'description' => 'Một chữ cái đáp án: a, b, c hoặc d',
                'example'     => 'a',
            ],
            'multi_select' => [
                'value_type'  => 'string',
                'description' => 'Các chữ cái đáp án, phân tách bằng dấu phẩy',
                'example'     => 'a,c',
            ],
            'fill' => [
                'value_type'  => 'string',
                'description' => 'Nội dung câu trả lời (HTML/text)',
                'example'     => 'Câu trả lời của học viên',
            ],
            'essay' => [
                'value_type'  => 'string',
                'description' => 'Bài luận (HTML/text). Có thể kèm ảnh qua upload-anh-cau-tra-loi',
                'example'     => '<p>Nội dung bài luận</p>',
                'uploads'     => ['answer_image' => 'POST /api/v1/hoc-vien/upload-anh-cau-tra-loi'],
            ],
            'memory_match' => [
                'value_type'  => 'json',
                'description' => 'JSON khi ghép đủ cặp',
                'example'     => ['status' => 'completed', 'moves' => 12, 'pairs' => 4],
            ],
            'word_link' => [
                'value_type'  => 'json',
                'description' => 'JSON khi nối đủ cặp. links[].a và links[].b là pair_id (cùng index = đúng)',
                'example'     => [
                    'status' => 'completed',
                    'links'  => [['a' => '0', 'b' => '0'], ['a' => '1', 'b' => '1']],
                ],
            ],
            'word_order' => [
                'value_type'  => 'json',
                'description' => 'JSON thứ tự token id sau khi xếp đủ',
                'example'     => ['status' => 'completed', 'order' => ['0', '1', '2']],
            ],
            'listening' => [
                'value_type'  => 'json',
                'description' => 'JSON mảng answers theo thứ tự câu con trong listening_questions',
                'example'     => [
                    'answers' => [
                        ['choice' => 'a'],
                        ['text' => 'đáp án nghe'],
                        ['status' => 'completed', 'links' => [['a' => '0', 'b' => '0']]],
                    ],
                ],
            ],
            'category_sort' => [
                'value_type'  => 'json',
                'description' => 'JSON phân loại thẻ vào nhóm',
                'example'     => [
                    'status'      => 'completed',
                    'assignments' => [['item_id' => 'item-1', 'group_id' => 'group-a']],
                ],
            ],
            'fill_blanks' => [
                'value_type'  => 'json',
                'description' => 'JSON các ô trống theo thứ tự blank index',
                'example'     => ['blanks' => ['từ 1', 'từ 2']],
            ],
            'word_select' => [
                'value_type'  => 'json',
                'description' => 'JSON chọn từ từ word_bank theo thứ tự blank',
                'example'     => ['blanks' => ['danh từ', 'động từ']],
            ],
            'word_translate' => [
                'value_type'  => 'json',
                'description' => 'JSON bản dịch theo thứ tự items (chờ giáo viên chấm)',
                'example'     => ['translations' => ['xin chào', 'cảm ơn']],
            ],
            'phat_am' => [
                'value_type'  => 'json',
                'description' => 'JSON ghi âm + đáp án. Upload file qua upload-ghi-am trước',
                'example'     => [
                    'recording' => 'uploads/recordings/abc.webm',
                    'answers'   => [['choice' => 'a']],
                ],
                'uploads' => ['recording' => 'POST /api/v1/hoc-vien/upload-ghi-am'],
            ],
            'image_hotspot' => [
                'value_type'  => 'json',
                'description' => 'JSON tọa độ % trên ảnh (x, y từ 0–100)',
                'example'     => ['clicks' => [['x' => 42.5, 'y' => 61.0]]],
            ],
            'image_label_drop' => [
                'value_type'  => 'json',
                'description' => 'JSON gán nhãn vào vùng',
                'example'     => [
                    'status'      => 'completed',
                    'assignments' => [['label_id' => 'label-1', 'zone_id' => 'zone-a']],
                ],
            ],
        ];

        if (isset($formats[$questionType])) {
            $item['answer_format'] = $formats[$questionType];
        }
    }

    /**
     * Ẩn đáp án / metadata nhạy cảm trước khi nộp bài.
     *
     * @param array<string, mixed> $item
     */
    private function sanitizeQuestionForStudent(array &$item, string $questionType, bool $showResults): void
    {
        if ($showResults) {
            return;
        }

        if (in_array($questionType, ['choice', 'multi_select', 'fill'], true)) {
            return;
        }

        if ($questionType === 'listening' && isset($item['listening']['questions'])) {
            $item['listening']['questions'] = $this->sanitizeInteractiveQuestions(
                $item['listening']['questions'],
                false
            );
        }
    }

    /**
     * Chuẩn hóa URL ảnh/audio/HTML — thêm domain đầy đủ cho app mobile.
     *
     * @param array<string, mixed> $item
     */
    private function resolveQuestionItemMedia(array &$item, bool $showResults): void
    {
        $rawText = (string) ($item['question_text'] ?? '');
        $item['question_html'] = $this->bankModel->renderQuestionText($rawText);

        if (isset($item['explanation']) && (string) $item['explanation'] !== '') {
            $item['explanation_html'] = $this->bankModel->renderExplanation((string) $item['explanation']);
        }

        if (! empty($item['choice_options']) && is_array($item['choice_options'])) {
            $item['choice_options'] = array_map(
                fn (mixed $opt): string => $this->resolveOptionValue((string) $opt),
                $item['choice_options']
            );
        }

        if (! empty($item['choice_options_labeled']) && is_array($item['choice_options_labeled'])) {
            foreach ($item['choice_options_labeled'] as &$labeled) {
                if (! is_array($labeled)) {
                    continue;
                }

                $labeled['value'] = $this->resolveOptionValue((string) ($labeled['value'] ?? ''));
            }
            unset($labeled);
        }

        if (isset($item['listening']) && is_array($item['listening'])) {
            $item['listening'] = $this->resolveListeningPlayable($item['listening'], $showResults);
            $item['listening_questions'] = $item['listening']['sub_items'] ?? [];
        }

        if (isset($item['phat_am']) && is_array($item['phat_am'])) {
            $item['phat_am'] = $this->resolvePhatAmPlayable($item['phat_am'], $showResults);
        }

        if (isset($item['word_translate']['items']) && is_array($item['word_translate']['items'])) {
            foreach ($item['word_translate']['items'] as &$translateItem) {
                if (! is_array($translateItem)) {
                    continue;
                }

                if (($translateItem['source_type'] ?? '') === 'image') {
                    $source = (string) ($translateItem['source'] ?? '');

                    if ($source !== '') {
                        $translateItem['source'] = $this->questionModel->resolveWordLinkMediaUrl($source);
                    }
                }
            }
            unset($translateItem);
        }

        if (isset($item['word_link']) && is_array($item['word_link'])) {
            $item['word_link'] = $this->resolveWordLinkPlayable($item['word_link']);
        }

        if (isset($item['memory_match']['pairs']) && is_array($item['memory_match']['pairs'])) {
            $item['memory_match']['pairs'] = array_map(function (array $pair): array {
                if (($pair['image'] ?? '') !== '') {
                    $pair['image'] = $this->questionModel->resolveWordLinkMediaUrl((string) $pair['image']);
                }

                return $pair;
            }, $item['memory_match']['pairs']);

            if (isset($item['memory_match']['cards']) && is_array($item['memory_match']['cards'])) {
                foreach ($item['memory_match']['cards'] as &$card) {
                    if (! is_array($card) || ($card['image'] ?? '') === '') {
                        continue;
                    }

                    $card['image'] = $this->questionModel->resolveWordLinkMediaUrl((string) $card['image']);
                }
                unset($card);
            }
        }
    }

    /**
     * @param array<string, mixed> $playable
     *
     * @return array<string, mixed>
     */
    private function resolveWordLinkPlayable(array $playable): array
    {
        foreach (['left_items', 'right_items'] as $side) {
            if (! isset($playable[$side]) || ! is_array($playable[$side])) {
                continue;
            }

            foreach ($playable[$side] as &$entry) {
                if (! is_array($entry) || ($entry['type'] ?? '') !== 'image') {
                    continue;
                }

                $value = (string) ($entry['value'] ?? '');

                if ($value !== '') {
                    $entry['value'] = $this->questionModel->resolveWordLinkMediaUrl($value);
                }
            }
            unset($entry);
        }

        if (isset($playable['pairs']) && is_array($playable['pairs'])) {
            $playable['pairs'] = array_map(function (array $pair): array {
                if (($pair['a_type'] ?? '') === 'image' && ($pair['a_value'] ?? '') !== '') {
                    $pair['a_value'] = $this->questionModel->resolveWordLinkMediaUrl((string) $pair['a_value']);
                }

                if (($pair['b_type'] ?? '') === 'image' && ($pair['b_value'] ?? '') !== '') {
                    $pair['b_value'] = $this->questionModel->resolveWordLinkMediaUrl((string) $pair['b_value']);
                }

                return $pair;
            }, $playable['pairs']);
        }

        return $playable;
    }

    private function resolveOptionValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if ($this->bankModel->isOptionImage($value)) {
            return $this->bankModel->resolveMediaUrl($this->bankModel->normalizeMediaReference($value));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $listening
     *
     * @return array<string, mixed>
     */
    private function resolveListeningBundle(array $listening, bool $showResults): array
    {
        $text = (string) ($listening['text'] ?? '');

        if ($text !== '') {
            $listening['text_html'] = $this->bankModel->renderQuestionText($text);
        }

        $audio = (string) ($listening['audio'] ?? '');

        if ($audio !== '') {
            $listening['audio_url'] = $this->questionModel->resolveListeningAudioUrl($audio);
            $listening['audio']     = $listening['audio_url'];
        }

        $choiceQuestion = (string) ($listening['choice_question'] ?? '');

        if ($choiceQuestion !== '') {
            $listening['choice_question_html'] = $this->bankModel->renderQuestionText($choiceQuestion);
        }

        if (isset($listening['questions']) && is_array($listening['questions'])) {
            $listening['questions'] = array_map(
                fn (array $question): array => $this->resolveInteractiveQuestion($question, $showResults),
                $listening['questions']
            );
        }

        return $listening;
    }

    /**
     * @param array<string, mixed> $bundle
     *
     * @return array<string, mixed>
     */
    private function resolvePhatAmBundle(array $bundle, bool $showResults): array
    {
        $prompt = (string) ($bundle['prompt'] ?? '');

        if ($prompt !== '') {
            $bundle['prompt_html'] = $this->bankModel->renderQuestionText($prompt);
        }

        if (isset($bundle['questions']) && is_array($bundle['questions'])) {
            $bundle['questions'] = array_map(
                fn (array $question): array => $this->resolveInteractiveQuestion($question, $showResults),
                $bundle['questions']
            );
        }

        return $bundle;
    }

    /**
     * @param array<string, mixed> $listening
     *
     * @return array<string, mixed>
     */
    private function resolveListeningPlayable(array $listening, bool $showResults): array
    {
        if (isset($listening['player']) && is_array($listening['player'])) {
            $text = (string) ($listening['player']['text'] ?? '');

            if ($text !== '') {
                $listening['player']['text_html'] = $this->bankModel->renderQuestionText($text);
            }
        }

        if (isset($listening['sub_items']) && is_array($listening['sub_items'])) {
            foreach ($listening['sub_items'] as &$sub) {
                if (! is_array($sub)) {
                    continue;
                }

                $this->resolveInteractiveSubItemMedia($sub, $showResults);
            }
            unset($sub);
        }

        $listening['text_html'] = $listening['player']['text_html'] ?? null;

        return $listening;
    }

    /**
     * @param array<string, mixed> $bundle
     *
     * @return array<string, mixed>
     */
    private function resolvePhatAmPlayable(array $bundle, bool $showResults): array
    {
        $prompt = (string) ($bundle['prompt'] ?? '');

        if ($prompt !== '') {
            $bundle['prompt_html'] = $this->bankModel->renderQuestionText($prompt);
        }

        foreach (['sub_items', 'questions'] as $key) {
            if (! isset($bundle[$key]) || ! is_array($bundle[$key])) {
                continue;
            }

            foreach ($bundle[$key] as &$sub) {
                if (! is_array($sub)) {
                    continue;
                }

                $this->resolveInteractiveSubItemMedia($sub, $showResults);
            }
            unset($sub);
        }

        return $bundle;
    }

    /**
     * @param array<string, mixed> $sub
     */
    private function resolveInteractiveSubItemMedia(array &$sub, bool $showResults): void
    {
        if ($choiceQuestion = (string) ($sub['choice_question'] ?? '')) {
            $sub['title_html'] = $this->bankModel->renderQuestionText($choiceQuestion);
        }

        if (isset($sub['choice']['options_labeled']) && is_array($sub['choice']['options_labeled'])) {
            foreach ($sub['choice']['options_labeled'] as &$opt) {
                if (! is_array($opt)) {
                    continue;
                }

                $opt['value'] = $this->resolveOptionValue((string) ($opt['value'] ?? ''));
                $opt          = array_merge($opt, $this->describeOptionMedia((string) ($opt['value'] ?? '')));
            }
            unset($opt);
        }

        if (isset($sub['multi_choice']['items']) && is_array($sub['multi_choice']['items'])) {
            foreach ($sub['multi_choice']['items'] as &$mc) {
                if (! is_array($mc) || ! isset($mc['options_labeled'])) {
                    continue;
                }

                foreach ($mc['options_labeled'] as &$opt) {
                    if (! is_array($opt)) {
                        continue;
                    }

                    $opt['value'] = $this->resolveOptionValue((string) ($opt['value'] ?? ''));
                    $opt          = array_merge($opt, $this->describeOptionMedia((string) ($opt['value'] ?? '')));
                }
                unset($opt);
            }
            unset($mc);
        }

        if (isset($sub['word_link']) && is_array($sub['word_link'])) {
            $sub['word_link'] = $this->resolveWordLinkPlayable($sub['word_link']);
        }
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function resolveInteractiveQuestion(array $question, bool $showResults): array
    {
        $choiceQuestion = (string) ($question['choice_question'] ?? '');

        if ($choiceQuestion !== '') {
            $question['choice_question_html'] = $this->bankModel->renderQuestionText($choiceQuestion);
        }

        if (! empty($question['options']) && is_array($question['options'])) {
            $question['options'] = array_map(
                fn (mixed $opt): string => $this->resolveOptionValue((string) $opt),
                $question['options']
            );

            $labels = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
            $labeled = [];

            foreach ($question['options'] as $index => $value) {
                $key = chr(ord('a') + $index);

                if ($index >= 4) {
                    break;
                }

                $labeled[] = ['key' => $key, 'label' => $labels[$key] ?? strtoupper($key), 'value' => $value];
            }

            $question['options_labeled'] = $labeled;
        }

        if (! empty($question['word_link_pairs']) && is_array($question['word_link_pairs'])) {
            $question['word_link_pairs'] = array_map(function (array $pair): array {
                if (($pair['a_type'] ?? '') === 'image' && ($pair['a_value'] ?? '') !== '') {
                    $pair['a_value'] = $this->questionModel->resolveWordLinkMediaUrl((string) $pair['a_value']);
                }

                if (($pair['b_type'] ?? '') === 'image' && ($pair['b_value'] ?? '') !== '') {
                    $pair['b_value'] = $this->questionModel->resolveWordLinkMediaUrl((string) $pair['b_value']);
                }

                return $pair;
            }, $question['word_link_pairs']);
        }

        if (! empty($question['fill_blanks']) && is_array($question['fill_blanks'])) {
            $template = (string) ($question['fill_blanks']['template'] ?? '');
            $question['fill_blanks']['line_groups'] = $this->questionModel->parseFillBlanksLineGroups($template);
        }

        if (! empty($question['multi_choice_items']) && is_array($question['multi_choice_items'])) {
            foreach ($question['multi_choice_items'] as &$multiItem) {
                if (! is_array($multiItem)) {
                    continue;
                }

                $multiQuestion = (string) ($multiItem['question'] ?? '');

                if ($multiQuestion !== '') {
                    $multiItem['question_html'] = $this->bankModel->renderQuestionText($multiQuestion);
                }

                if (! empty($multiItem['options']) && is_array($multiItem['options'])) {
                    $multiItem['options'] = array_map(
                        fn (mixed $opt): string => $this->resolveOptionValue((string) $opt),
                        $multiItem['options']
                    );
                }

                if (! $showResults) {
                    unset($multiItem['correct']);
                }
            }
            unset($multiItem);
        }

        if (! $showResults) {
            unset($question['correct'], $question['correct_text']);

            if (isset($question['true_false_items']) && is_array($question['true_false_items'])) {
                foreach ($question['true_false_items'] as &$tfItem) {
                    if (is_array($tfItem)) {
                        unset($tfItem['answer']);
                    }
                }
                unset($tfItem);
            }

            if (isset($question['fill_blanks']['answers'])) {
                unset($question['fill_blanks']['answers']);
            }
        }

        return $question;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $question
     * @param list<string> $answerLabels
     */
    private function appendStudentAnswer(
        array &$item,
        array $question,
        string $questionType,
        string $rawSelected,
        array $answerLabels
    ): void {
        if ($rawSelected === '') {
            return;
        }

        switch ($questionType) {
            case 'essay':
            case 'fill':
                $item['student_answer']      = $rawSelected;
                $item['student_answer_text'] = $rawSelected;
                break;

            case 'multi_select':
                $letters = $this->questionModel->parseMultiSelectLetters($rawSelected);
                $item['student_answer']        = $rawSelected;
                $item['student_answer_letters']  = $letters;
                $item['student_answer_labels']   = array_values(array_filter(array_map(
                    static fn (string $letter): ?string => $answerLabels[strtolower($letter)] ?? null,
                    $letters
                )));
                break;

            case 'phat_am':
                $item['student_answer']     = $rawSelected;
                $item['phat_am_submission'] = [
                    'answers'   => $this->questionModel->decodePhatAmStudentAnswersPayload($rawSelected),
                    'recording' => $this->questionModel->decodePhatAmStudentRecording($rawSelected),
                    'payload'   => $this->questionModel->decodePhatAmStudentPayload($rawSelected),
                ];
                break;

            case 'word_translate':
                $item['student_answer'] = $rawSelected;
                $decoded                = json_decode($rawSelected, true);
                $item['word_translate_submission'] = is_array($decoded) ? $decoded : null;
                break;

            case 'listening':
            case 'word_link':
            case 'word_order':
            case 'category_sort':
            case 'fill_blanks':
            case 'word_select':
            case 'memory_match':
            case 'image_hotspot':
            case 'image_label_drop':
                $item['student_answer'] = $rawSelected;
                $decoded                = json_decode($rawSelected, true);
                $item['student_answer_payload'] = is_array($decoded) ? $decoded : null;
                break;

            default:
                $selected = strtolower($rawSelected);
                $item['student_answer']       = $selected !== '' ? $selected : null;
                $item['student_answer_label'] = $answerLabels[$selected] ?? null;
                break;
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $question
     * @param array<string, mixed> $answerRow
     */
    private function appendTeacherFeedback(array &$item, array $question, string $questionType, array $answerRow): void
    {
        $teacherComment = trim((string) ($answerRow['teacher_comment'] ?? ''));
        $teacherGradedAt = trim((string) ($answerRow['teacher_graded_at'] ?? ''));
        $teacherGraded   = $teacherGradedAt !== '';

        if ($teacherGraded) {
            $item['teacher_graded']    = true;
            $item['teacher_graded_at'] = $teacherGradedAt;
        }

        if ($teacherComment === '' && ! $teacherGraded) {
            return;
        }

        if ($questionType === 'phat_am') {
            $review = $this->questionModel->decodePhatAmTeacherReview($teacherComment);

            if ($review['text'] !== '' || $review['recording'] !== '') {
                $item['teacher_comment'] = $review['text'];
                $item['teacher_review']  = [
                    'text'      => $review['text'],
                    'recording' => $review['recording'],
                ];
            }

            return;
        }

        if ($questionType === 'word_translate') {
            $itemReviews = $this->questionModel->decodeWordTranslateItemReview($teacherComment);

            if ($itemReviews !== []) {
                $item['teacher_review'] = [
                    'items'       => $itemReviews,
                    'total_items' => count($this->questionModel->decodeWordTranslateData($question)['items'] ?? []),
                ];
            }

            if ($teacherComment !== '' && empty($item['teacher_review'])) {
                $item['teacher_comment'] = $teacherComment;
            }

            return;
        }

        if ($teacherComment !== '') {
            $item['teacher_comment'] = $teacherComment;
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $question
     * @param list<string> $answerLabels
     */
    private function appendCorrectAnswer(array &$item, array $question, string $questionType, array $answerLabels): void
    {
        if (in_array($questionType, ['fill', 'essay', 'phat_am', 'word_translate'], true)) {
            return;
        }

        $correct = strtolower(trim((string) ($question['correct_answer'] ?? '')));

        if ($correct === '') {
            return;
        }

        if ($questionType === 'multi_select') {
            $letters = $this->questionModel->parseMultiSelectLetters($correct);
            $item['correct_answer']        = $correct;
            $item['correct_answer_letters'] = $letters;
            $item['correct_answer_labels']  = array_values(array_filter(array_map(
                static fn (string $letter): ?string => $answerLabels[strtolower($letter)] ?? null,
                $letters
            )));

            return;
        }

        $item['correct_answer']       = $correct;
        $item['correct_answer_label'] = $answerLabels[$correct] ?? null;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    private function decodeOptionsJson(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
