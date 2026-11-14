<?php

namespace App\Support;

class PublicFilePath
{
    public static function resolve(string $rel): ?string
    {
        $rel = str_replace('\\', '/', $rel);
        $rel = ltrim($rel, '/');
        if ($rel === '' || str_contains($rel, '..')) {
            return null;
        }
        $roots = [
            public_path(),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public',
        ];
        foreach ($roots as $root) {
            $rootReal = realpath($root);
            if ($rootReal === false) {
                continue;
            }
            $full = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
            $real = realpath($full);
            if ($real === false || ! is_file($real)) {
                continue;
            }
            if (str_starts_with($real, $rootReal)) {
                return $real;
            }
        }

        return null;
    }
}
