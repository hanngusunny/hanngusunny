<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PublicUrl
{
    /** @var list<string> */
    private const LEGACY_PREFIXES = [
        'duhocsunnylv',
    ];

    public static function applyFromRequest(?Request $request = null): void
    {
        $request ??= request();
        $base = rtrim((string) $request->getBasePath(), '/');
        $base = (string) preg_replace('#/public$#i', '', $base);
        $root = rtrim($request->getSchemeAndHttpHost().$base, '/');

        URL::forceRootUrl($root);
        config([
            'app.url' => $root,
            'filesystems.disks.public.url' => $root.'/storage',
        ]);

        $assetUrl = (string) config('app.asset_url', '');
        if ($assetUrl === '' || ! self::sameOrigin($assetUrl, $root)) {
            config(['app.asset_url' => null]);
            $url = URL::getFacadeRoot();
            if ($url !== null) {
                \Closure::bind(function () {
                    $this->assetRoot = null;
                }, $url, $url)();
            }
        }

        $forwarded = strtolower((string) $request->header('X-Forwarded-Proto', ''));
        if ($request->secure() || $forwarded === 'https') {
            URL::forceScheme('https');
        }
    }

    public static function to(?string $path): string
    {
        $path = self::rewrite((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
            return $path;
        }

        return url('/'.ltrim($path, '/'));
    }

    public static function rewrite(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $root = rtrim(url('/'), '/');
        $livePath = rtrim((string) parse_url($root, PHP_URL_PATH), '/');
        $legacyGroup = implode('|', self::LEGACY_PREFIXES);

        $value = (string) preg_replace(
            '#https?://(?:localhost|127\.0\.0\.1)(?::\d+)?/(?:'.$legacyGroup.')(?=/|$)#i',
            $root,
            $value
        );

        foreach (self::LEGACY_PREFIXES as $legacy) {
            if ($livePath !== '' && strcasecmp($livePath, '/'.$legacy) === 0) {
                continue;
            }
            $value = (string) preg_replace('#https?://[^/\s"\']+/'.$legacy.'(?=/|$)#i', $root, $value);
            $value = (string) preg_replace('#(?<=["\'(]|^)/'.$legacy.'(?=/|$)#i', $livePath === '' ? '' : $livePath, $value);
        }

        return $value;
    }

    public static function rewriteHtml(string $html): string
    {
        if ($html === '' || (! str_contains($html, 'duhocsunnylv') && ! str_contains($html, 'localhost'))) {
            return $html;
        }

        return self::rewrite($html);
    }

    private static function sameOrigin(string $assetUrl, string $root): bool
    {
        $a = parse_url($assetUrl);
        $b = parse_url($root);

        return strcasecmp((string) ($a['host'] ?? ''), (string) ($b['host'] ?? '')) === 0
            && rtrim((string) ($a['path'] ?? ''), '/') === rtrim((string) ($b['path'] ?? ''), '/');
    }
}
