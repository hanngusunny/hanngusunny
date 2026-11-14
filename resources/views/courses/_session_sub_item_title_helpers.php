<?php

if (! function_exists('sessionSubItemIsRedundantChoiceQuestion')) {
    function sessionSubItemIsRedundantChoiceQuestion(string $text, string $mode): bool
    {
        if ($text === '') {
            return true;
        }

        require_once __DIR__ . '/_session_quiz_title_helpers.php';

        $normalized   = mb_strtolower(trim($text));
        $defaultTitle = sessionSubItemBuildTitle(1, $mode, '');
        $defaultPlain = mb_strtolower(trim(preg_replace('/^câu hỏi\s+\d+:\s*/iu', '', sessionQuizPlainText($defaultTitle)) ?? sessionQuizPlainText($defaultTitle)));

        return $defaultPlain !== '' && $normalized === $defaultPlain;
    }

    function sessionSubItemBuildTitle(int $no, string $mode, string $choiceQuestion): string
    {
        $prefix = 'Câu hỏi ' . $no;

        if ($choiceQuestion !== '' && ! sessionSubItemIsRedundantChoiceQuestion($choiceQuestion, $mode)) {
            return $prefix . ': ' . $choiceQuestion;
        }

        return match ($mode) {
            'true_false'   => $prefix . ': Chọn đúng hoặc sai cho từng câu bên dưới',
            'multi_choice' => $prefix . ': Chọn đáp án đúng cho từng câu hỏi bên dưới',
            'multi_select' => $prefix . ': Có thể chọn nhiều đáp án',
            'word_link'    => $prefix . ': Nối các cặp tương ứng',
            'fill_blanks'  => $prefix . ': Điền vào chỗ trống',
            default        => $prefix,
        };
    }

    function sessionSubItemRenderTitleHtml(int $no, string $mode, string $choiceQuestion, object $richTextModel): string
    {
        require_once __DIR__ . '/_session_quiz_title_helpers.php';

        $plain = sessionQuizPlainText($choiceQuestion);

        if ($plain !== '' && ! sessionSubItemIsRedundantChoiceQuestion($plain, $mode)) {
            $rendered = $richTextModel->renderComment(sessionQuizPrepareRichContent($choiceQuestion));

            return '<span class="session-quiz-legend-prefix">Câu hỏi ' . esc((string) $no) . ':</span>'
                . '<div class="course-rich-content session-quiz-legend-rich">' . $rendered . '</div>';
        }

        return esc(sessionSubItemBuildTitle($no, $mode, ''));
    }
}
