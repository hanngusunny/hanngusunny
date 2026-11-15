<?php

if (! function_exists('sessionQuizPlainText')) {
    function sessionQuizPlainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    function sessionQuizDefaultTypeLabel(string $questionType): string
    {
        return match ($questionType) {
            'fill'              => 'Tự luận',
            'memory_match'      => 'Lật ảnh',
            'word_link'         => 'Nối từ',
            'word_order'        => 'Sắp xếp câu',
            'listening'         => 'Nghe đáp',
            'category_sort'     => 'Phân loại',
            'fill_blanks'       => 'Điền chỗ trống',
            'word_select'       => 'Chọn từ',
            'word_translate'    => 'Dịch từ',
            'phat_am'           => 'Phát âm',
            'image_hotspot'     => 'Click ảnh',
            'image_label_drop'  => 'Kéo nhãn ảnh',
            'multi_select'      => 'Nhiều đáp án',
            default             => '',
        };
    }

    function sessionQuizIsRedundantQuestionText(string $plainText, string $questionType): bool
    {
        if ($plainText === '') {
            return true;
        }

        $normalized = mb_strtolower($plainText);
        $default    = mb_strtolower(sessionQuizDefaultTypeLabel($questionType));

        return $default !== '' && $default === $normalized;
    }

    function sessionQuizDecodeRichHtmlEntities(string $raw): string
    {
        $decoded = trim($raw);

        if ($decoded === '') {
            return '';
        }

        for ($i = 0; $i < 8; $i++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($next === $decoded) {
                break;
            }

            $decoded = $next;
        }

        return $decoded;
    }

    function sessionQuizRenderRichHtml(string $raw, object $richTextModel): string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return '';
        }

        return $richTextModel->renderComment(sessionQuizPrepareRichContent($raw));
    }

    function sessionQuizPrepareRichContent(string $raw): string
    {
        $decoded = sessionQuizDecodeRichHtmlEntities($raw);

        if ($decoded === '') {
            return '';
        }

        if (! str_contains($decoded, '<')) {
            return nl2br(esc($decoded), false);
        }

        return $decoded;
    }

    function sessionQuizBuildTitle(int $no, string $questionType, string $questionTextRaw): string
    {
        $plain  = sessionQuizPlainText($questionTextRaw);
        $prefix = 'Câu hỏi ' . $no;

        if ($plain !== '' && ! sessionQuizIsRedundantQuestionText($plain, $questionType)) {
            return $prefix . ': ' . $plain;
        }

        $default = sessionQuizDefaultTypeLabel($questionType);

        return $default !== '' ? $prefix . ': ' . $default : $prefix;
    }

    function sessionQuizRenderTitleHtml(int $no, string $questionType, string $questionTextRaw, object $richTextModel): string
    {
        $plain     = sessionQuizPlainText($questionTextRaw);
        $hasMedia  = preg_match('/<(img|audio|video|iframe|table|ul|ol|figure)\b/i', $questionTextRaw) === 1;
        $hasCustom = ($plain !== '' && ! sessionQuizIsRedundantQuestionText($plain, $questionType)) || $hasMedia;

        if ($hasCustom) {
            $rendered = $richTextModel->renderComment(sessionQuizPrepareRichContent($questionTextRaw));

            return '<span class="session-quiz-legend-prefix">Câu hỏi ' . esc((string) $no) . ':</span>'
                . '<div class="course-rich-content session-quiz-legend-rich">' . $rendered . '</div>';
        }

        return esc(sessionQuizBuildTitle($no, $questionType, ''));
    }

    /**
     * @return array{band:string,badge:string,title:string,message:string,stickers:string}
     */
    function quizScorePraise(int $scorePct): array
    {
        if ($scorePct >= 90) {
            return [
                'band'     => 'excellent',
                'badge'    => 'great',
                'title'    => 'Quá xuất sắc!',
                'message'  => '100 điểm không có nhưng!',
                'stickers' => '🎉🔥',
            ];
        }

        if ($scorePct >= 80) {
            return [
                'band'     => 'great',
                'badge'    => 'great',
                'title'    => 'Giỏi quá!',
                'message'  => 'Cứ giữ vững phong độ này nhé!',
                'stickers' => '🌟👏',
            ];
        }

        if ($scorePct >= 65) {
            return [
                'band'     => 'good',
                'badge'    => 'ok',
                'title'    => 'Tốt lắm!',
                'message'  => 'Ráng xíu nữa là điểm cao đỉnh chóp rồi!',
                'stickers' => '💪✨',
            ];
        }

        if ($scorePct >= 50) {
            return [
                'band'     => 'pass',
                'badge'    => 'ok',
                'title'    => 'Pass rồi nè!',
                'message'  => 'Ôn lại bài chút xíu là nữa nhé!',
                'stickers' => '👍📚',
            ];
        }

        return [
            'band'     => 'retry',
            'badge'    => 'low',
            'title'    => 'Không sao đâu, sai đâu sửa đó.',
            'message'  => 'Lần sau làm tốt hơn nhé!',
            'stickers' => '🌱💪',
        ];
    }
}
