<?php

namespace App\Support\Questions;

class QuestionBankItemModel
{
    public function normalizeMediaReference(string $value): string
    {
        $value = trim(str_replace('\\', '/', $value));

        if ($value === '') {
            return '';
        }

        $dataPos = stripos($value, 'data:image/');

        if ($dataPos !== false && $dataPos > 0) {
            $value = substr($value, $dataPos);
        }

        $base = rtrim((string) url('/'), '/').'/';

        if (str_starts_with($value, $base)) {
            $value = substr($value, strlen($base));
        }

        $value = ltrim($value, '/');

        if (str_starts_with($value, 'public/')) {
            $value = substr($value, 7);
        }

        return ltrim($value, './');
    }

    public function resolveMediaUrl(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));

        if ($path === '') {
            return '';
        }

        $url = \App\Support\MediaUrl::to($path);

        return $url !== '' ? $url : $path;
    }

    public function isOptionImage(mixed $value): bool
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return false;
        }

        if (str_starts_with($value, 'data:image/') || str_starts_with($value, 'blob:')) {
            return true;
        }

        if (preg_match('/^<img\b/i', $value) === 1) {
            return true;
        }

        $ref = $this->normalizeMediaReference($value);

        if ($ref === '') {
            return false;
        }

        if (preg_match('#\.(jpe?g|png|gif|webp|svg|avif|bmp)(\?|$)#i', $ref) === 1) {
            return true;
        }

        if (preg_match('#^uploads/.+\.(jpe?g|png|gif|webp|svg|avif|bmp)(\?|$)#i', $ref) === 1) {
            return true;
        }

        if (preg_match('#^https?://#i', $ref) || str_starts_with($ref, '//')) {
            return (bool) preg_match('#\.(jpe?g|png|gif|webp|svg|avif|bmp)(\?|$)#i', $ref)
                || str_contains($ref, '/uploads/');
        }

        return false;
    }

    public function renderOptionContent(string $optText, string $alt = ''): string
    {
        $optText = trim($optText);

        if ($optText === '') {
            return '';
        }

        if ($this->isOptionImage($optText)) {
            if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $optText, $m)) {
                $url = \App\Support\MediaUrl::to($m[1]);
            } else {
                $url = \App\Support\MediaUrl::to($optText);
            }

            return '<img src="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '" class="session-quiz-option-img" style="max-width:100%;max-height:180px;object-fit:contain;border-radius:6px;display:block;margin-top:4px;">';
        }

        if (str_contains($optText, '<')) {
            return \App\Support\HtmlContent::render($optText);
        }

        return htmlspecialchars($optText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function renderQuestionText(mixed $value): string
    {
        return \App\Support\HtmlContent::render((string) ($value ?? ''));
    }

    public function renderExplanation(mixed $value): string
    {
        return $this->renderQuestionText($value);
    }

    public function isValidStoredAudioPath(string $path): bool
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        if ($path === '' || str_contains($path, '..')) {
            return false;
        }

        if (! str_starts_with($path, 'uploads/')) {
            return false;
        }

        return is_file(public_path($path));
    }
}
