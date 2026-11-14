<?php

namespace App\Support;

class HtmlContent
{
    public static function has(?string $html): bool
    {
        $plain = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $plain) ?? '') !== '';
    }

    public static function render(?string $html): string
    {
        return MediaUrl::rewriteHtml(self::sanitize($html));
    }

    public static function sanitize(?string $html): string
    {
        $html = trim(self::decode((string) $html));

        if ($html === '') {
            return '';
        }

        $allowed = '<p><br><strong><b><em><i><u><s><ul><ol><li><a><h1><h2><h3><h4><h5><h6><img><span><div><table><thead><tbody><tr><td><th><blockquote><hr><video><source><iframe><pre><code><sub><sup><figure><figcaption>';

        return strip_tags($html, $allowed);
    }

    public static function plain(?string $html): string
    {
        $text = strip_tags(self::decode((string) $html));
        $text = str_replace("\xc2\xa0", ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private static function decode(string $html): string
    {
        $decoded = $html;

        for ($i = 0; $i < 8; $i++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($next === $decoded) {
                break;
            }

            $decoded = $next;
        }

        return $decoded;
    }
}
