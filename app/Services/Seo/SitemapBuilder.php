<?php

namespace App\Services\Seo;

use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\CustomForm;
use App\Services\Settings\SiteSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class SitemapBuilder
{
    private const TZ = 'Asia/Ho_Chi_Minh';

    /** @var array<string, array{loc: string, lastmod: string, changefreq: string, priority: string}> */
    private array $entries = [];

    private int $homePageId = 0;

    public function __construct(private readonly SiteSettingsService $settings)
    {
        $raw = trim($this->settings->get('home_page_id', ''));
        $this->homePageId = $raw !== '' && ctype_digit($raw) ? (int) $raw : 0;
    }

    /**
     * @return list<array{loc: string, lastmod: string, name: string, count: int}>
     */
    public function index(): array
    {
        $groups = [
            ['loc' => url('/page-sitemap.xml'), 'name' => 'Trang tĩnh', 'urls' => $this->pages()],
            ['loc' => url('/post-sitemap.xml'), 'name' => 'Tin tức & ngành', 'urls' => $this->posts()],
            ['loc' => url('/school-sitemap.xml'), 'name' => 'Trường đại học', 'urls' => $this->schools()],
            ['loc' => url('/event-sitemap.xml'), 'name' => 'Sự kiện', 'urls' => $this->events()],
        ];

        $index = [];
        foreach ($groups as $group) {
            $index[] = [
                'loc' => $this->normalizeLoc((string) $group['loc']),
                'lastmod' => $this->maxLastmod($group['urls']),
                'name' => (string) $group['name'],
                'count' => count($group['urls']),
            ];
        }

        return $index;
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public function pages(): array
    {
        $this->entries = [];
        $this->addUrl(url('/'), null, 'daily', '1.0');
        $this->addUrl(url('/du-hoc-han-quoc-2026'), null, 'weekly', '0.9');
        $this->addUrl(url('/dang-ky-tu-van-thanh-cong'), null, 'monthly', '0.3');
        $this->addCategories(['page'], '0.9');
        $this->addForms();

        return $this->sorted();
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public function posts(): array
    {
        $this->entries = [];
        $this->addCategories(['post', 'major'], '0.8');
        $this->addPosts(['post', 'major']);

        return $this->sorted();
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public function schools(): array
    {
        $this->entries = [];
        $this->addCategories(['school'], '0.8');
        $this->addPosts(['school']);

        return $this->sorted();
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public function events(): array
    {
        $this->entries = [];
        $this->addCategories(['events'], '0.8');
        $this->addPosts(['events']);

        return $this->sorted();
    }

    /**
     * @param  list<string>  $scopes
     */
    private function addCategories(array $scopes, string $priority): void
    {
        if (! Schema::hasTable('content_category')) {
            return;
        }

        foreach ($scopes as $scope) {
            $rows = ContentCategory::query()->inScope($scope)
                ->where('is_active', 1)
                ->orderBy('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            foreach ($this->walkCategoryTree($rows) as $row) {
                if (! $this->isIndexable($row->seo_robots ?? null, true)) {
                    continue;
                }
                if ($scope === 'page' && $this->homePageId > 0 && (int) $row->id === $this->homePageId) {
                    continue;
                }
                $slug = trim((string) $row->slug);
                if ($slug === '') {
                    continue;
                }
                $this->addUrl(url('/'.$slug), $this->lastmodFromModel($row), 'weekly', $priority);
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ContentCategory>  $rows
     * @return list<ContentCategory>
     */
    private function walkCategoryTree($rows): array
    {
        $byParent = [];
        foreach ($rows as $row) {
            $pid = (int) ($row->parent_id ?? 0);
            $byParent[$pid][] = $row;
        }

        $out = [];
        $walk = function (int $parentId) use (&$walk, &$out, $byParent): void {
            foreach ($byParent[$parentId] ?? [] as $row) {
                $out[] = $row;
                $walk((int) $row->id);
            }
        };
        $walk(0);

        foreach ($rows as $row) {
            if (! in_array($row, $out, true)) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $scopes
     */
    private function addPosts(array $scopes): void
    {
        if (! Schema::hasTable('content_post') || $scopes === []) {
            return;
        }

        $query = ContentPost::query()->with('category')
            ->where('status', 'published')
            ->whereIn('scope', $scopes)
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        foreach ($query->cursor() as $row) {
            if (! $this->isIndexable($row->seo_robots ?? null, true)) {
                continue;
            }
            $slug = trim((string) $row->slug);
            if ($slug === '') {
                continue;
            }
            $url = $row->publicUrl();
            if ($url === '') {
                continue;
            }
            $this->addUrl($url, $this->lastmodFromModel($row), 'weekly', '0.6');
        }
    }

    private function addForms(): void
    {
        if (! Schema::hasTable('custom_form')) {
            return;
        }

        CustomForm::query()->where('is_active', 1)->orderBy('name')->each(function (CustomForm $form): void {
            $slug = trim((string) $form->slug);
            if ($slug === '') {
                return;
            }
            $this->addUrl(url('/form/'.$slug), $this->lastmodFromModel($form), 'monthly', '0.4');
        });
    }

    private function addUrl(string $loc, ?string $lastmod, string $changefreq, string $priority): void
    {
        $loc = $this->normalizeLoc($loc);
        if ($loc === '') {
            return;
        }

        $entry = [
            'loc' => $loc,
            'lastmod' => $lastmod ?? $this->formatLastmod(null),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];

        if (isset($this->entries[$loc])) {
            $existing = $this->entries[$loc];
            if ($entry['lastmod'] > $existing['lastmod']) {
                $existing['lastmod'] = $entry['lastmod'];
            }
            if ((float) $entry['priority'] > (float) $existing['priority']) {
                $existing['priority'] = $entry['priority'];
            }
            $this->entries[$loc] = $existing;

            return;
        }

        $this->entries[$loc] = $entry;
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function sorted(): array
    {
        $list = array_values($this->entries);
        usort($list, static function (array $a, array $b): int {
            $p = strcmp($b['priority'], $a['priority']);

            return $p !== 0 ? $p : strcmp($a['loc'], $b['loc']);
        });

        return $list;
    }

    /**
     * @param  list<array{loc: string, lastmod: string, changefreq: string, priority: string}>  $urls
     */
    private function maxLastmod(array $urls): string
    {
        $max = '';
        foreach ($urls as $url) {
            $lastmod = (string) ($url['lastmod'] ?? '');
            if ($lastmod > $max) {
                $max = $lastmod;
            }
        }

        return $max !== '' ? $max : $this->formatLastmod(null);
    }

    private function lastmodFromModel(object $row): string
    {
        foreach (['updated_at', 'published_at', 'created_at'] as $field) {
            $value = $row->{$field} ?? null;
            if ($value instanceof Carbon) {
                return $this->formatLastmod($value);
            }
            $raw = is_string($value) ? trim($value) : '';
            if ($raw === '' || $raw === '0000-00-00 00:00:00') {
                continue;
            }

            return $this->formatLastmod($raw);
        }

        return $this->formatLastmod(null);
    }

    private function formatLastmod(Carbon|string|null $raw): string
    {
        $tz = self::TZ;
        if ($raw instanceof Carbon) {
            return $raw->clone()->timezone($tz)->format('Y-m-d\TH:i:sP');
        }
        if (is_string($raw) && $raw !== '') {
            try {
                return Carbon::parse($raw, $tz)->timezone($tz)->format('Y-m-d\TH:i:sP');
            } catch (\Throwable) {
            }
        }

        return Carbon::now($tz)->startOfDay()->format('Y-m-d\TH:i:sP');
    }

    private function normalizeLoc(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return preg_replace('#(?<!:)//+#', '/', $url) ?? $url;
    }

    private function isIndexable(?string $robots, bool $active): bool
    {
        if (! $active) {
            return false;
        }
        $norm = strtolower(str_replace(' ', '', (string) ($robots ?: 'index,follow')));

        return ! str_contains($norm, 'noindex');
    }
}
