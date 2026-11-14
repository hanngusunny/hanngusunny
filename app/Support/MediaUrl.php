<?php

namespace App\Support;

class MediaUrl
{
    public static function to(string $value): string
    {
        $value = trim(str_replace('\\', '/', $value));

        if ($value === '' || str_starts_with($value, 'data:') || str_starts_with($value, 'blob:')) {
            return $value;
        }

        if (str_starts_with($value, '#') || str_starts_with($value, 'mailto:') || str_starts_with($value, 'tel:')) {
            return $value;
        }

        if (preg_match('#^(https?:)?//#i', $value) === 1) {
            if (! self::isLocalUrl($value)) {
                return $value;
            }

            $path = (string) parse_url(self::withScheme($value), PHP_URL_PATH);
            $value = $path !== '' ? $path : $value;
        }

        if (preg_match('#(?:^|/)(?:(?:\.\./)|(?:\./))*(?:public/)?(uploads/.+)$#i', $value, $match) === 1) {
            return asset($match[1]);
        }

        $path = ltrim($value, '/');
        $path = (string) preg_replace('#^(?:giaovienhanlvn|giaovienhan|public)/#i', '', $path);
        $path = ltrim($path, './');

        if ($path === '' || str_contains($path, '..')) {
            return '';
        }

        return asset($path);
    }

    public static function rewriteHtml(string $html): string
    {
        if ($html === '' || (! str_contains($html, 'src=') && ! str_contains($html, 'href=') && ! str_contains($html, 'poster='))) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/\b(src|href|poster)=([\'"])([^\'"]+)\2/i',
            static function (array $match): string {
                return $match[1].'='.$match[2].e(self::to($match[3])).$match[2];
            },
            $html
        );
    }

    private static function isLocalUrl(string $url): bool
    {
        $host = strtolower((string) parse_url(self::withScheme($url), PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        return $appHost !== '' && $host === $appHost;
    }

    private static function withScheme(string $url): string
    {
        return str_starts_with($url, '//') ? 'http:'.$url : $url;
    }
}
