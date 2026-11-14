<?php

namespace App\Support;

class VideoEmbed
{
    /**
     * @return array{type: string, src: string}|null
     */
    public static function parse(?string $url): ?array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([a-zA-Z0-9_-]{11})/', $url, $match) === 1) {
            return [
                'type' => 'youtube',
                'src' => 'https://www.youtube.com/embed/'.$match[1],
            ];
        }

        if (preg_match('/\.(mp4|webm|ogg)(\?|$)/i', $url) === 1) {
            return [
                'type' => 'file',
                'src' => $url,
            ];
        }

        return [
            'type' => 'link',
            'src' => $url,
        ];
    }
}
