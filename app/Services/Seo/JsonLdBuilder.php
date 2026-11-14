<?php

namespace App\Services\Seo;

use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\ContentTag;
use App\Models\CustomForm;
use App\Models\FaqCategory;
use App\Services\Settings\SiteSettingsService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class JsonLdBuilder
{
    private const LANG = 'vi';

    /** @var array<string, string> */
    private const PAGE_TYPES = [
        'gioi-thieu' => 'AboutPage',
        'lien-he' => 'ContactPage',
        'thong-tin-lien-he' => 'ContactPage',
    ];

    /** @var list<string> */
    private const GUIDE_SLUGS = [
        'du-hoc-han-quoc-tong-the',
        'chi-phi-du-hoc-han-quoc-2026',
        'dieu-kien-du-hoc-han-quoc-2026',
        'chon-truong-du-hoc-han-quoc-2026',
        'hoc-bong-du-hoc-han-quoc-2026',
        'hoc-bong',
    ];

    public function __construct(private readonly SiteSettingsService $settings)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function fromViewData(array $data): array
    {
        $html = (string) ($data['bodyHtml'] ?? $data['descriptionHtml'] ?? '');

        if (! empty($data['isHome'])) {
            $page = $data['page'] ?? null;

            return $this->forHome($page instanceof ContentCategory ? $page : null, $html);
        }

        if (($data['post'] ?? null) instanceof ContentPost) {
            return $this->forPost($data['post'], $html, is_array($data['breadcrumbs'] ?? null) ? $data['breadcrumbs'] : []);
        }

        if (($data['tag'] ?? null) instanceof ContentTag) {
            return $this->forTag($data['tag'], $data['posts'] ?? null);
        }

        if (array_key_exists('q', $data) && array_key_exists('posts', $data) && empty($data['category']) && empty($data['page'])) {
            return $this->forSearch((string) $data['q']);
        }

        if (($data['form'] ?? null) instanceof CustomForm) {
            return $this->forForm($data['form']);
        }

        $category = $data['category'] ?? $data['page'] ?? null;
        if ($category instanceof ContentCategory) {
            return $this->forCategory(
                $category,
                $html,
                $data['posts'] ?? null,
                $data['children'] ?? null,
                is_array($data['breadcrumbs'] ?? null) ? $data['breadcrumbs'] : [],
            );
        }

        return $this->forGeneric(
            (string) ($data['title'] ?? $this->siteName()),
            (string) ($data['metaDescription'] ?? $this->settings->get('meta_description_default')),
            (string) ($data['canonicalUrl'] ?? url()->current()),
            is_array($data['breadcrumbs'] ?? null) ? $data['breadcrumbs'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forHome(?ContentCategory $page, string $html = ''): array
    {
        $url = url('/');
        $name = $page?->meta_title ?: $this->settings->get('meta_title_default', $this->siteName());
        $desc = $page?->meta_description ?: $this->settings->get('meta_description_default', $this->settings->get('site_tagline'));
        $image = $this->absoluteUrl($page?->og_image ?: $page?->cover_image) ?: $this->defaultImage();

        return $this->graph([
            $this->organizationNode(),
            $this->websiteNode(),
            $this->webPageNode($url, 'WebPage', $name, $desc, $image, $page?->updated_at, $page?->created_at),
            $this->breadcrumbNode($url, [['label' => 'Trang chủ', 'url' => $url]]),
            ...$this->faqNodes($url, $html, $page?->description),
        ]);
    }

    /**
     * @param  list<array{label: string, url?: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public function forPost(ContentPost $post, string $html = '', array $breadcrumbs = []): array
    {
        $url = $post->publicUrl();
        $name = $post->meta_title ?: $post->title;
        $desc = $post->meta_description ?: strip_tags((string) $post->excerpt);
        $image = $this->absoluteUrl($post->og_image ?: $post->featured_image) ?: $this->defaultImage();
        $crumbs = $this->withHome($breadcrumbs, $url, $post->title);

        $graph = [
            $this->organizationNode(),
            $this->websiteNode(),
            $this->webPageNode($url, 'WebPage', $name, $desc, $image, $post->updated_at, $post->published_at ?: $post->created_at),
            $this->breadcrumbNode($url, $crumbs),
        ];

        $graph[] = match ($post->scope) {
            'events' => $this->eventNode($post, $url, $name, $desc, $image),
            'school' => $this->collegeNode($post, $url, $name, $desc, $image),
            'major' => $this->courseNode($post, $url, $name, $desc, $image),
            default => $this->articleNode($post, $url, $name, $desc, $image, 'NewsArticle'),
        };

        return $this->graph([...$graph, ...$this->faqNodes($url, $html, $post->body)]);
    }

    /**
     * @param  list<array{label: string, url?: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public function forCategory(ContentCategory $category, string $html = '', mixed $posts = null, mixed $children = null, array $breadcrumbs = []): array
    {
        $url = url('/'.$category->slug);
        $name = $category->meta_title ?: $category->name;
        $desc = $category->meta_description ?: $this->plain($category->description);
        $image = $this->absoluteUrl($category->og_image ?: $category->cover_image) ?: $this->defaultImage();
        $pageType = $this->webPageTypeForCategory($category);
        $crumbs = $this->withHome($breadcrumbs, $url, $category->name);
        $items = $this->listItems($category, $posts, $children);

        $graph = [
            $this->organizationNode(),
            $this->websiteNode(),
            $this->webPageNode($url, $pageType, $name, $desc, $image, $category->updated_at ?? null, $category->created_at ?? null),
            $this->breadcrumbNode($url, $crumbs),
        ];

        if ($this->isGuidePage($category->slug)) {
            $graph[] = $this->guideArticleNode($category, $url, $name, $desc, $image);
        } elseif ($this->isProgramPage($category->slug)) {
            $graph[] = $this->programNode($category, $url, $name, $desc);
        } elseif ($items !== []) {
            $graph[] = $this->itemListNode($url, $items);
        }

        if ($category->scope === 'school' && $this->hasGeo($category)) {
            $graph[] = $this->placeNode($category, $url);
        }

        return $this->graph([...$graph, ...$this->faqNodes($url, $html, $category->description)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forTag(ContentTag $tag, mixed $posts = null): array
    {
        $url = url('/tag/'.$tag->slug);
        $name = $tag->meta_title ?: $tag->name;
        $desc = $tag->meta_description ?: $this->plain($tag->description);
        $image = $this->absoluteUrl($tag->og_image) ?: $this->defaultImage();
        $items = [];
        foreach ($this->asPosts($posts) as $i => $post) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => $post->publicUrl(),
                'name' => $post->title,
            ];
        }

        $graph = [
            $this->organizationNode(),
            $this->websiteNode(),
            $this->webPageNode($url, 'CollectionPage', $name, $desc, $image, $tag->updated_at ?? null, $tag->created_at ?? null),
            $this->breadcrumbNode($url, $this->withHome([['label' => $tag->name]], $url, $tag->name)),
        ];
        if ($items !== []) {
            $graph[] = $this->itemListNode($url, $items);
        }

        return $this->graph($graph);
    }

    /**
     * @return array<string, mixed>
     */
    public function forSearch(string $query): array
    {
        $url = url('/tim-kiem');
        $name = $query !== '' ? 'Tìm kiếm: '.$query : 'Tìm kiếm';

        return $this->graph([
            $this->organizationNode(),
            $this->websiteNode(),
            $this->webPageNode($url, 'SearchResultsPage', $name, 'Kết quả tìm kiếm trên '.$this->siteName(), $this->defaultImage()),
            $this->breadcrumbNode($url, $this->withHome([['label' => 'Tìm kiếm', 'url' => $url]], $url, 'Tìm kiếm')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forForm(CustomForm $form): array
    {
        $url = url('/form/'.$form->slug);
        $name = $form->name;
        $desc = $this->plain($form->description);

        return $this->graph([
            $this->organizationNode(),
            $this->websiteNode(),
            $this->webPageNode($url, 'WebPage', $name, $desc, $this->defaultImage()),
            $this->breadcrumbNode($url, $this->withHome([['label' => $form->name, 'url' => $url]], $url, $form->name)),
        ]);
    }

    /**
     * @param  list<array{label: string, url?: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public function forGeneric(string $title, string $description, string $url, array $breadcrumbs = []): array
    {
        return $this->graph([
            $this->organizationNode(),
            $this->websiteNode(),
            $this->webPageNode($url, 'WebPage', $title, $description, $this->defaultImage()),
            $this->breadcrumbNode($url, $this->withHome($breadcrumbs, $url, $title)),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return array<string, mixed>
     */
    private function graph(array $nodes): array
    {
        $nodes = array_values(array_filter($nodes));

        return [
            '@context' => 'https://schema.org',
            '@graph' => $nodes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function organizationNode(): array
    {
        $home = $this->homeUrl();
        $name = $this->siteName();
        $node = [
            '@type' => ['EducationalOrganization', 'LocalBusiness'],
            '@id' => $home.'#organization',
            'name' => $name,
            'url' => $home,
            'inLanguage' => self::LANG,
        ];

        $logo = $this->absoluteUrl($this->settings->get('logo_url')) ?: asset('assets/img/logo.png');
        $node['logo'] = [
            '@type' => 'ImageObject',
            'url' => $logo,
        ];
        $node['image'] = $logo;

        $phone = trim($this->settings->get('frontend_topbar_hotline') ?: $this->settings->get('contact_phone'));
        if ($phone !== '') {
            $node['telephone'] = $phone;
        }
        $email = trim($this->settings->get('contact_email'));
        if ($email !== '') {
            $node['email'] = $email;
        }

        $address = trim($this->settings->get('company_address') ?: $this->settings->get('contact_address'));
        if ($address !== '') {
            $node['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressCountry' => 'VN',
            ];
        }

        $sameAs = array_values(array_filter([
            $this->settings->get('facebook_url') ?: $this->settings->get('frontend_topbar_social_facebook'),
            $this->settings->get('youtube_url') ?: $this->settings->get('frontend_topbar_social_youtube'),
            $this->settings->get('zalo_url') ?: $this->settings->get('frontend_topbar_social_zalo'),
            $this->settings->get('tiktok_url') ?: $this->settings->get('frontend_topbar_social_tiktok'),
            $this->settings->get('linkedin_url') ?: $this->settings->get('frontend_topbar_social_linkedin'),
        ], fn ($v) => is_string($v) && $v !== ''));
        if ($sameAs !== []) {
            $node['sameAs'] = $sameAs;
        }

        $hours = trim($this->settings->get('business_hours'));
        if ($hours !== '') {
            $node['openingHours'] = $hours;
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function websiteNode(): array
    {
        $home = $this->homeUrl();

        return [
            '@type' => 'WebSite',
            '@id' => $home.'#website',
            'name' => $this->siteName(),
            'url' => $home,
            'inLanguage' => self::LANG,
            'publisher' => ['@id' => $home.'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/tim-kiem').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function webPageNode(
        string $url,
        string $type,
        string $name,
        string $description,
        string $image,
        mixed $modified = null,
        mixed $published = null,
    ): array {
        $home = $this->homeUrl();
        $node = [
            '@type' => $type,
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $name,
            'description' => $this->plain($description),
            'inLanguage' => self::LANG,
            'isPartOf' => ['@id' => $home.'#website'],
            'about' => ['@id' => $home.'#organization'],
            'breadcrumb' => ['@id' => $url.'#breadcrumb'],
        ];
        if ($published) {
            $node['datePublished'] = $this->iso($published);
        }
        if ($modified) {
            $node['dateModified'] = $this->iso($modified);
        }
        if ($image !== '') {
            $node['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => $image,
            ];
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function articleNode(ContentPost $post, string $url, string $name, string $description, string $image, string $type = 'Article'): array
    {
        $home = $this->homeUrl();
        $node = [
            '@type' => $type,
            '@id' => $url.'#article',
            'headline' => $post->title,
            'name' => $name,
            'description' => $this->plain($description),
            'inLanguage' => self::LANG,
            'url' => $url,
            'mainEntityOfPage' => ['@id' => $url.'#webpage'],
            'author' => ['@id' => $home.'#organization'],
            'publisher' => ['@id' => $home.'#organization'],
        ];
        if ($post->published_at) {
            $node['datePublished'] = $this->iso($post->published_at);
        }
        if ($post->updated_at) {
            $node['dateModified'] = $this->iso($post->updated_at);
        }
        if ($image !== '') {
            $node['image'] = $image;
        }
        $keywords = trim((string) $post->meta_keywords);
        if ($keywords !== '') {
            $node['keywords'] = $keywords;
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function guideArticleNode(ContentCategory $category, string $url, string $name, string $description, string $image): array
    {
        $home = $this->homeUrl();
        $node = [
            '@type' => 'Article',
            '@id' => $url.'#article',
            'headline' => $category->name,
            'name' => $name,
            'description' => $this->plain($description),
            'inLanguage' => self::LANG,
            'url' => $url,
            'mainEntityOfPage' => ['@id' => $url.'#webpage'],
            'author' => ['@id' => $home.'#organization'],
            'publisher' => ['@id' => $home.'#organization'],
        ];
        if (! empty($category->created_at)) {
            $node['datePublished'] = $this->iso($category->created_at);
        }
        if (! empty($category->updated_at)) {
            $node['dateModified'] = $this->iso($category->updated_at);
        }
        if ($image !== '') {
            $node['image'] = $image;
        }
        $keywords = trim((string) $category->meta_keywords);
        if ($keywords !== '') {
            $node['keywords'] = $keywords;
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function eventNode(ContentPost $post, string $url, string $name, string $description, string $image): array
    {
        $home = $this->homeUrl();
        $node = [
            '@type' => 'Event',
            '@id' => $url.'#event',
            'name' => $post->title,
            'description' => $this->plain($description),
            'url' => $url,
            'inLanguage' => self::LANG,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'organizer' => ['@id' => $home.'#organization'],
            'mainEntityOfPage' => ['@id' => $url.'#webpage'],
        ];
        if ($post->published_at) {
            $node['startDate'] = $this->iso($post->published_at);
        }
        if ($image !== '') {
            $node['image'] = $image;
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function collegeNode(ContentPost $post, string $url, string $name, string $description, string $image): array
    {
        $category = $post->category;
        $node = [
            '@type' => 'CollegeOrUniversity',
            '@id' => $url.'#college',
            'name' => $post->title,
            'description' => $this->plain($description),
            'url' => $url,
            'mainEntityOfPage' => ['@id' => $url.'#webpage'],
        ];
        if ($image !== '') {
            $node['image'] = $image;
        }
        if ($category && trim((string) $category->official_website_url) !== '') {
            $node['sameAs'] = [(string) $category->official_website_url];
        }
        if ($category && $this->hasGeo($category)) {
            $node['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $category->map_lat,
                'longitude' => (float) $category->map_lng,
            ];
            if (trim((string) $category->map_region) !== '') {
                $node['address'] = [
                    '@type' => 'PostalAddress',
                    'addressLocality' => (string) $category->map_region,
                    'addressCountry' => 'KR',
                ];
            }
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function courseNode(ContentPost $post, string $url, string $name, string $description, string $image): array
    {
        $home = $this->homeUrl();
        $node = [
            '@type' => 'Course',
            '@id' => $url.'#course',
            'name' => $post->title,
            'description' => $this->plain($description) ?: $post->title,
            'url' => $url,
            'inLanguage' => self::LANG,
            'provider' => ['@id' => $home.'#organization'],
            'mainEntityOfPage' => ['@id' => $url.'#webpage'],
        ];
        if ($image !== '') {
            $node['image'] = $image;
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function programNode(ContentCategory $category, string $url, string $name, string $description): array
    {
        $home = $this->homeUrl();

        return [
            '@type' => 'EducationalOccupationalProgram',
            '@id' => $url.'#program',
            'name' => $category->name,
            'description' => $this->plain($description) ?: $name,
            'url' => $url,
            'inLanguage' => self::LANG,
            'provider' => ['@id' => $home.'#organization'],
            'mainEntityOfPage' => ['@id' => $url.'#webpage'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function placeNode(ContentCategory $category, string $url): array
    {
        $node = [
            '@type' => 'Place',
            '@id' => $url.'#place',
            'name' => $category->name,
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $category->map_lat,
                'longitude' => (float) $category->map_lng,
            ],
        ];
        if (trim((string) $category->map_region) !== '') {
            $node['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => (string) $category->map_region,
                'addressCountry' => 'KR',
            ];
        }

        return $node;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function itemListNode(string $url, array $items): array
    {
        return [
            '@type' => 'ItemList',
            '@id' => $url.'#itemlist',
            'numberOfItems' => count($items),
            'itemListElement' => $items,
        ];
    }

    /**
     * @param  list<array{label: string, url?: string}>  $crumbs
     * @return array<string, mixed>
     */
    private function breadcrumbNode(string $pageUrl, array $crumbs): array
    {
        $elements = [];
        foreach (array_values($crumbs) as $i => $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => (string) ($crumb['label'] ?? ''),
            ];
            $loc = (string) ($crumb['url'] ?? '');
            if ($loc !== '') {
                $item['item'] = $loc;
            } elseif ($i === count($crumbs) - 1) {
                $item['item'] = $pageUrl;
            }
            $elements[] = $item;
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $pageUrl.'#breadcrumb',
            'itemListElement' => $elements,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function faqNodes(string $url, string $html, ?string $rawHtml = null): array
    {
        $faqs = $this->extractFaqs($html.' '.(string) $rawHtml);
        if ($faqs === []) {
            return [];
        }

        return [[
            '@type' => 'FAQPage',
            '@id' => $url.'#faq',
            'mainEntity' => array_map(static fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'],
                ],
            ], $faqs),
        ]];
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    private function extractFaqs(string $html): array
    {
        $out = [];
        $seen = [];

        if (preg_match_all(
            '/<h3[^>]*class="[^"]*news-faq-item__question[^"]*"[^>]*>(.*?)<\/h3>\s*<div[^>]*class="[^"]*news-faq-item__answer[^"]*"[^>]*>(.*?)<\/div>/is',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $row) {
                $this->pushFaq($out, $seen, $row[1], $row[2]);
            }
        }

        if (preg_match_all('/<summary>(.*?)<\/summary>\s*<div>(.*?)<\/div>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $row) {
                $this->pushFaq($out, $seen, $row[1], $row[2]);
            }
        }

        if (preg_match_all('/data-bun-faq="(\d+)"/i', $html, $ids)) {
            foreach (array_unique(array_map('intval', $ids[1])) as $id) {
                foreach ($this->faqItemsFromCategory($id) as $item) {
                    $this->pushFaq($out, $seen, $item['q'], $item['a']);
                }
            }
        }

        return $out;
    }

    /**
     * @param  list<array{q: string, a: string}>  $out
     * @param  array<string, true>  $seen
     */
    private function pushFaq(array &$out, array &$seen, string $question, string $answer): void
    {
        $q = trim(html_entity_decode(strip_tags($question), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $a = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($answer), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        if ($q === '' || $a === '') {
            return;
        }
        $key = Str::lower($q);
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $out[] = ['q' => $q, 'a' => $a];
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    private function faqItemsFromCategory(int $id): array
    {
        if ($id < 1 || ! Schema::hasTable('faq_item')) {
            return [];
        }
        $cat = FaqCategory::query()->with(['items' => fn ($q) => $q->where('is_active', 1)])->find($id);
        if ($cat === null || ! $cat->is_active) {
            return [];
        }

        $rows = [];
        foreach ($cat->items as $item) {
            $rows[] = ['q' => (string) $item->question, 'a' => (string) $item->answer];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listItems(ContentCategory $category, mixed $posts, mixed $children): array
    {
        $items = [];
        $position = 1;

        foreach ($this->asCategories($children) as $child) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => url('/'.$child->slug),
                'name' => $child->name,
            ];
        }

        foreach ($this->asPosts($posts) as $post) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => $post->publicUrl(),
                'name' => $post->title,
            ];
            if ($position > 20) {
                break;
            }
        }

        return $items;
    }

    /**
     * @return list<ContentPost>
     */
    private function asPosts(mixed $posts): array
    {
        if ($posts instanceof Paginator) {
            $posts = $posts->items();
        } elseif ($posts instanceof Collection) {
            $posts = $posts->all();
        }
        if (! is_array($posts)) {
            return [];
        }

        return array_values(array_filter($posts, static fn ($row) => $row instanceof ContentPost));
    }

    /**
     * @return list<ContentCategory>
     */
    private function asCategories(mixed $rows): array
    {
        if ($rows instanceof Collection) {
            $rows = $rows->all();
        }
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, static fn ($row) => $row instanceof ContentCategory));
    }

    /**
     * @param  list<array{label: string, url?: string}>  $crumbs
     * @return list<array{label: string, url?: string}>
     */
    private function withHome(array $crumbs, string $pageUrl, string $fallbackLabel): array
    {
        $home = ['label' => 'Trang chủ', 'url' => $this->homeUrl()];
        if ($crumbs === []) {
            return [$home, ['label' => $fallbackLabel, 'url' => $pageUrl]];
        }
        if (($crumbs[0]['label'] ?? '') !== 'Trang chủ') {
            array_unshift($crumbs, $home);
        }
        $last = count($crumbs) - 1;
        if ($last >= 0 && empty($crumbs[$last]['url'])) {
            $crumbs[$last]['url'] = $pageUrl;
        }

        return $crumbs;
    }

    private function webPageTypeForCategory(ContentCategory $category): string
    {
        $slug = (string) $category->slug;
        if (isset(self::PAGE_TYPES[$slug])) {
            return self::PAGE_TYPES[$slug];
        }
        if ($this->isGuidePage($slug) || $this->isProgramPage($slug) || $category->scope === 'page') {
            return 'WebPage';
        }

        return 'CollectionPage';
    }

    private function isGuidePage(string $slug): bool
    {
        return in_array($slug, self::GUIDE_SLUGS, true);
    }

    private function isProgramPage(string $slug): bool
    {
        return (bool) preg_match('/(^|-)(he-tieng|he-dai-hoc|he-cao-dang|he-thac-si|d4-?1|d2-[123]|dao-tao-tieng-han)(-|$)/i', $slug);
    }

    private function hasGeo(ContentCategory $category): bool
    {
        return $category->map_lat !== null && $category->map_lat !== ''
            && $category->map_lng !== null && $category->map_lng !== '';
    }

    private function siteName(): string
    {
        $name = trim($this->settings->get('company_name') ?: $this->settings->get('site_name'));

        return $name !== '' ? $name : 'SUNNY Du học Hàn Quốc';
    }

    private function homeUrl(): string
    {
        return rtrim(url('/'), '/').'/';
    }

    private function defaultImage(): string
    {
        return $this->absoluteUrl($this->settings->get('og_image_url'))
            ?: $this->absoluteUrl($this->settings->get('logo_url'))
            ?: asset('assets/img/logo.png');
    }

    private function absoluteUrl(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        return \App\Support\PublicUrl::to($path);
    }

    private function plain(?string $html): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        return Str::limit($text, 300, '…');
    }

    private function iso(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->timezone('Asia/Ho_Chi_Minh')->toIso8601String();
        }

        $ts = strtotime((string) $value);

        return $ts ? date('c', $ts) : (string) $value;
    }
}
