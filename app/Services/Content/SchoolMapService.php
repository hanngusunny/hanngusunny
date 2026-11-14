<?php

namespace App\Services\Content;

use App\Models\ContentCategory;
use App\Models\ContentPost;
use Illuminate\Support\Collection;

class SchoolMapService
{
    /**
     * @param  Collection<int, ContentCategory>  $children
     * @param  iterable<int, ContentPost>  $posts
     * @return list<array<string, mixed>>
     */
    public function items(ContentCategory $hub, $children, $posts): array
    {
        $out = [];
        if ($children instanceof Collection && $children->isNotEmpty()) {
            foreach ($children as $child) {
                $slug = trim((string) $child->slug);
                if ($slug === '') {
                    continue;
                }
                $meta = trim((string) ($child->meta_description ?? ''));
                $desc = $meta !== '' ? $meta : strip_tags((string) $child->description);
                $img = trim((string) ($child->logo_url ?: $child->cover_image ?? ''));
                $out[] = [
                    'nameVi' => $child->name !== '' ? $child->name : $slug,
                    'nameKo' => '',
                    'nameEn' => '',
                    'city' => '',
                    'region' => (string) ($child->map_region ?? ''),
                    'lat' => $this->coord($child->map_lat ?? null),
                    'lng' => $this->coord($child->map_lng ?? null),
                    'focus' => $this->clip($desc, 160),
                    'img' => $img,
                    'descVi' => $this->clip($desc, 260),
                    'url' => url('/'.$slug),
                ];
            }

            return $out;
        }
        foreach ($posts as $post) {
            $slug = trim((string) $post->slug);
            if ($slug === '') {
                continue;
            }
            $desc = trim((string) ($post->excerpt ?: $post->meta_description));
            $out[] = [
                'nameVi' => $post->title,
                'nameKo' => '',
                'nameEn' => '',
                'city' => '',
                'region' => '',
                'lat' => null,
                'lng' => null,
                'focus' => $this->clip($desc, 160),
                'img' => (string) $post->featured_image,
                'descVi' => $this->clip(strip_tags($desc), 260),
                'url' => url('/truong/'.$hub->slug.'/'.$slug),
            ];
        }

        return $out;
    }

    private function coord(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (! is_numeric($v)) {
            return null;
        }
        $n = round((float) $v, 7);

        return ($n >= -90 && $n <= 90) || ($n >= -180 && $n <= 180) ? $n : null;
    }

    private function clip(string $s, int $max): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', strip_tags($s)) ?? '');
        if ($s === '') {
            return '';
        }

        return mb_strlen($s, 'UTF-8') > $max ? mb_substr($s, 0, $max, 'UTF-8').'…' : $s;
    }
}
