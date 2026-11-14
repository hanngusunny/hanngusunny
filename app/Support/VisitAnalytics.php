<?php

namespace App\Support;

class VisitAnalytics
{
    public static function isBotUserAgent(string $ua): bool
    {
        $ua = strtolower(trim($ua));
        if ($ua === '') {
            return false;
        }
        foreach (['bot', 'spider', 'crawl', 'slurp', 'mediapartners', 'facebookexternalhit', 'bingpreview', 'pingdom', 'monitor', 'headless', 'lighthouse', 'curl/', 'wget/', 'python-requests', 'go-http-client', 'semrush', 'ahrefs', 'petalbot'] as $p) {
            if (str_contains($ua, $p)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{host: string, type: string}
     */
    public static function classifyReferrer(string $referrer, string $currentHost): array
    {
        $referrer = trim($referrer);
        if ($referrer === '') {
            return ['host' => '', 'type' => 'direct'];
        }
        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        if ($host === '') {
            return ['host' => '', 'type' => 'direct'];
        }
        $currentHost = strtolower(trim($currentHost));
        if ($currentHost !== '' && ($host === $currentHost || str_ends_with($host, '.'.$currentHost))) {
            return ['host' => $host, 'type' => 'internal'];
        }
        foreach (['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'baidu.', 'yandex.', 'coccoc.'] as $needle) {
            if (str_contains($host, $needle)) {
                return ['host' => $host, 'type' => 'search'];
            }
        }
        foreach (['facebook.com', 'zalo.me', 'tiktok.com', 'youtube.com', 'instagram.com', 'twitter.com', 'x.com', 'linkedin.com'] as $needle) {
            if (str_contains($host, $needle)) {
                return ['host' => $host, 'type' => 'social'];
            }
        }

        return ['host' => $host, 'type' => 'external'];
    }

    public static function extractSearchKeyword(string $referrer): string
    {
        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        if (! str_contains($host, 'google.')) {
            return '';
        }
        $query = parse_url($referrer, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return '';
        }
        parse_str($query, $params);
        $q = trim((string) ($params['q'] ?? $params['oq'] ?? $params['query'] ?? ''));

        return $q === '' ? '' : mb_substr(preg_replace('/\s+/u', ' ', $q) ?? $q, 0, 200);
    }

    /**
     * @return array{device: string, browser: string, os: string}
     */
    public static function parseUserAgent(string $ua): array
    {
        $ua = trim($ua);
        if ($ua === '') {
            return ['device' => 'unknown', 'browser' => '', 'os' => ''];
        }
        $device = 'desktop';
        if (preg_match('/mobile|android.*mobile|iphone|ipod|windows phone/i', $ua)) {
            $device = 'mobile';
        } elseif (preg_match('/ipad|tablet|android(?!.*mobile)/i', $ua)) {
            $device = 'tablet';
        }
        $browser = 'Khác';
        if (preg_match('/Edg\//i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/OPR\/|Opera/i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/Chrome\//i', $ua) && ! preg_match('/Edg|OPR/i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\//i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\//i', $ua) && ! preg_match('/Chrome/i', $ua)) {
            $browser = 'Safari';
        }
        $os = 'Khác';
        if (preg_match('/Windows NT/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/Android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        return ['device' => $device, 'browser' => $browser, 'os' => $os];
    }

    public static function shouldSkipPath(string $path): bool
    {
        $path = strtolower(trim($path, '/'));
        if ($path === '') {
            return false;
        }
        $first = explode('/', $path)[0] ?? '';
        if (in_array($first, ['admin', 'api', 'writable', 'vendor', 'public', 'laravel'], true)) {
            return true;
        }
        if (str_starts_with($first, 'media')) {
            return true;
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return $ext !== '' && in_array($ext, ['css', 'js', 'map', 'json', 'xml', 'txt', 'ico', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'woff', 'woff2', 'ttf', 'pdf'], true);
    }
}
