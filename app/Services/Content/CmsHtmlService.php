<?php

namespace App\Services\Content;

use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\FaqCategory;
use App\Models\SiteBlock;
use App\Models\SliderCategory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class CmsHtmlService
{
    public function expand(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }
        $html = $this->replaceBlocks($html);
        $html = $this->replaceSliders($html);
        $html = $this->replaceFaqs($html);
        $html = $this->normalizeLegacyLinks($html);
        $html = $this->rewriteLegacyUtilityClasses($html);
        $html = $this->rewriteLegacyWidgetAttrs($html);
        $html = $this->lazyLoadMedia($html);

        return $html;
    }

    /**
     * @return array{html: string, items: list<array{id: string, text: string}>, leadImage: array{src: string, alt: string}|null}
     */
    public function expandArticle(?string $html, bool $extractLead = true): array
    {
        $html = $this->expand($html);
        $lead = $extractLead ? $this->extractLeadImage($html) : ['html' => $html, 'image' => null];
        $toc = $this->bodyWithToc($lead['html']);

        return [
            'html' => $toc['html'],
            'items' => $toc['items'],
            'leadImage' => $lead['image'],
        ];
    }

    /**
     * @return array{html: string, items: list<array{id: string, text: string}>}
     */
    public function bodyWithToc(string $html): array
    {
        if ($html === '') {
            return ['html' => '', 'items' => []];
        }
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $wrap = '<?xml encoding="UTF-8"><!DOCTYPE html><html><head><meta charset="UTF-8"/></head><body>'
            .'<div id="__post_article_root">'.$html.'</div></body></html>';
        @$dom->loadHTML($wrap);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $dom->getElementById('__post_article_root');
        if (! $root) {
            return ['html' => $html, 'items' => []];
        }
        $h2s = $root->getElementsByTagName('h2');
        $items = [];
        $n = 0;
        $used = [];
        for ($i = 0; $i < $h2s->length; $i++) {
            $node = $h2s->item($i);
            if (! ($node instanceof \DOMElement)) {
                continue;
            }
            $text = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
            if ($text === '') {
                continue;
            }
            ++$n;
            $id = trim($node->getAttribute('id'));
            if ($id === '') {
                $id = 'post-h2-'.$n;
                $node->setAttribute('id', $id);
            }
            if (isset($used[$id])) {
                $id .= '-'.$n;
                $node->setAttribute('id', $id);
            }
            $used[$id] = true;
            $items[] = ['id' => $id, 'text' => $text];
        }
        $newHtml = '';
        foreach ($root->childNodes as $child) {
            $newHtml .= $dom->saveHTML($child);
        }

        return ['html' => $newHtml, 'items' => $items];
    }

    /**
     * @return array{html: string, image: array{src: string, alt: string}|null}
     */
    public function extractLeadImage(string $html): array
    {
        if (trim($html) === '') {
            return ['html' => '', 'image' => null];
        }
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $wrap = '<?xml encoding="UTF-8"><!DOCTYPE html><html><head><meta charset="UTF-8"/></head><body>'
            .'<div id="__post_article_root">'.$html.'</div></body></html>';
        @$dom->loadHTML($wrap);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $dom->getElementById('__post_article_root');
        if (! $root) {
            return ['html' => $html, 'image' => null];
        }
        $xpath = new \DOMXPath($dom);
        $imgs = $xpath->query('.//img', $root);
        if ($imgs === false) {
            return ['html' => $html, 'image' => null];
        }
        for ($i = 0; $i < $imgs->length; $i++) {
            $img = $imgs->item($i);
            if (! ($img instanceof \DOMElement)) {
                continue;
            }
            $figure = $img;
            while ($figure !== null && ! ($figure instanceof \DOMElement && $figure->tagName === 'figure')) {
                $figure = $figure->parentNode;
            }
            if ($figure instanceof \DOMElement) {
                foreach (['data-bun-block', 'data-bun-slider', 'data-bun-faq'] as $attr) {
                    if ($figure->hasAttribute($attr)) {
                        continue 2;
                    }
                }
            }
            $src = trim($img->getAttribute('src'));
            if ($src === '') {
                continue;
            }
            $alt = trim($img->getAttribute('alt'));
            $remove = $figure instanceof \DOMElement ? $figure : $img;
            if ($remove->parentNode) {
                $remove->parentNode->removeChild($remove);
            }
            $newHtml = '';
            foreach ($root->childNodes as $child) {
                $newHtml .= $dom->saveHTML($child);
            }

            return ['html' => $newHtml, 'image' => ['src' => $src, 'alt' => $alt]];
        }

        return ['html' => $html, 'image' => null];
    }

    public function replaceBlocks(string $html): string
    {
        if (! str_contains($html, 'data-bun-block')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<figure\b[^>]*\bdata-bun-block="([^"]+)"[^>]*>[\s\S]*?<\/figure>/i',
            function (array $m): string {
                $slug = trim($m[1]);
                if ($slug === '') {
                    return '';
                }
                $exported = $this->loadExportedBlock($slug);
                if ($exported !== null) {
                    return $this->renderExportedBlock($exported);
                }
                if (! Schema::hasTable('site_block')) {
                    return '';
                }
                $block = SiteBlock::query()->where('slug', $slug)->where('is_active', 1)->first();
                if ($block === null) {
                    return '';
                }

                return $this->wrapBlockHtml($slug, (string) $block->block_type, $this->htmlFromDatabaseBlock($block));
            },
            $html
        );
    }

    public function replaceSliders(string $html): string
    {
        if (! str_contains($html, 'data-bun-slider') || ! Schema::hasTable('slider_slide')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<figure\b[^>]*\bdata-bun-slider="(\d+)"[^>]*>[\s\S]*?<\/figure>/i',
            function (array $m): string {
                $id = (int) $m[1];
                $cat = SliderCategory::query()->with('slides')->find($id);
                if ($cat === null) {
                    return '';
                }

                return View::make('public.partials.embed-slider', ['category' => $cat])->render();
            },
            $html
        );
    }

    public function replaceFaqs(string $html): string
    {
        if (! str_contains($html, 'data-bun-faq') || ! Schema::hasTable('faq_item')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<figure\b[^>]*\bdata-bun-faq="(\d+)"[^>]*>[\s\S]*?<\/figure>/i',
            function (array $m): string {
                $id = (int) $m[1];
                $cat = FaqCategory::query()->with(['items' => fn ($q) => $q->where('is_active', 1)])->find($id);
                if ($cat === null || ! $cat->is_active) {
                    return '';
                }

                return View::make('public.partials.embed-faq', ['category' => $cat])->render();
            },
            $html
        );
    }

    public function normalizeLegacyLinks(string $html): string
    {
        $html = \App\Support\PublicUrl::rewriteHtml($html);
        $html = $this->normalizeLegacyExportedPhpEchoes($html);
        $home = rtrim(url('/'), '/');
        $html = (string) preg_replace('/href=(["\'])index\.php(#[\w\-]*)?\1/i', 'href=$1'.$home.'$2$1', $html);
        $html = (string) preg_replace_callback(
            '/href=(["\'])\/(#[\w\-]*)?\1/i',
            static function (array $m) use ($home): string {
                return 'href='.$m[1].$home.($m[2] ?? '').$m[1];
            },
            $html
        );
        $html = (string) preg_replace_callback(
            '/href=(["\'])(?!https?:\/\/|\/|mailto:|tel:|javascript:|#)([a-z0-9][a-z0-9\-]*\.php)(#[\w\-]*)?\1/i',
            static function (array $m): string {
                $slug = (string) preg_replace('/\.php$/i', '', $m[2]);

                return 'href='.$m[1].url('/'.$slug).($m[3] ?? '').$m[1];
            },
            $html
        );
        $assetBase = rtrim(asset('assets'), '/');
        $html = (string) preg_replace(
            '/\b(src|href)=(["\'])(?:\.\.\/|\.\/)*assets\//i',
            '$1=$2'.$assetBase.'/',
            $html
        );
        $html = (string) preg_replace(
            '/url\((["\']?)(?:\.\.\/|\.\/)*assets\//i',
            'url($1'.$assetBase.'/',
            $html
        );
        $html = (string) preg_replace_callback(
            '/\b(src|href)=(["\'])(?:\.\/)?public\/([^"\']+)\2/i',
            static function (array $m): string {
                return $m[1].'='.$m[2].url('/'.$m[3]).$m[2];
            },
            $html
        );

        return $html;
    }

    /**
     * Map leftover Bootstrap utility class names in CMS HTML to Tailwind.
     * Component names (btn, form-control, modal, accordion) stay as Tailwind @layer classes.
     */
    private function rewriteLegacyUtilityClasses(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'class=')) {
            return $html;
        }

        $pairs = [
            'ratio ratio-16x9' => 'relative w-full aspect-video',
            'ratio ratio-21x9' => 'relative w-full aspect-[21/9]',
            'ratio ratio-1x1' => 'relative w-full aspect-square',
        ];
        foreach ($pairs as $from => $to) {
            $html = str_replace($from, $to, $html);
        }

        $tokens = [
            'list-unstyled' => 'list-none p-0',
            'img-fluid' => 'max-w-full h-auto',
            'text-muted' => 'text-bs-secondary',
            'w-100' => 'w-full',
            'h-100' => 'h-full',
            'd-flex' => 'flex',
            'd-none' => 'hidden',
            'd-block' => 'block',
            'd-grid' => 'grid',
            'd-inline' => 'inline',
            'd-inline-block' => 'inline-block',
            'd-inline-flex' => 'inline-flex',
            'flex-column' => 'flex-col',
            'justify-content-between' => 'justify-between',
            'justify-content-center' => 'justify-center',
            'justify-content-end' => 'justify-end',
            'justify-content-start' => 'justify-start',
            'align-items-center' => 'items-center',
            'align-items-start' => 'items-start',
            'align-items-end' => 'items-end',
            'align-items-stretch' => 'items-stretch',
            'border-top' => 'border-t',
            'border-bottom' => 'border-b',
        ];

        $rewritten = preg_replace_callback(
            '/\bclass=(["\'])([^"\']*)\1/',
            static function (array $m) use ($tokens): string {
                $classes = $m[2];
                foreach ($tokens as $from => $to) {
                    $classes = (string) preg_replace(
                        '/(?:^|\s)'.preg_quote($from, '/').'(?=\s|$)/',
                        ' '.$to,
                        $classes
                    );
                }

                return 'class='.$m[1].trim((string) preg_replace('/\s+/', ' ', $classes)).$m[1];
            },
            $html
        );

        return is_string($rewritten) ? $rewritten : $html;
    }

    private function rewriteLegacyWidgetAttrs(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-bs-')) {
            return $html;
        }

        return str_replace(
            [
                'data-bs-toggle',
                'data-bs-target',
                'data-bs-dismiss',
                'data-bs-parent',
                'data-bs-ride',
                'data-bs-interval',
                'data-bs-touch',
                'data-bs-slide-to',
                'data-bs-slide',
            ],
            [
                'data-tw-toggle',
                'data-tw-target',
                'data-tw-dismiss',
                'data-tw-parent',
                'data-tw-ride',
                'data-tw-interval',
                'data-tw-touch',
                'data-tw-slide-to',
                'data-tw-slide',
            ],
            $html
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadExportedBlock(string $slug): ?array
    {
        $slug = strtolower(preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?? '');
        $slug = trim($slug, '-');
        if ($slug === '') {
            return null;
        }
        $path = resource_path('frontend-blocks/'.$slug.'.php');
        if (! is_file($path)) {
            return null;
        }
        if (! defined('FRONTEND_BLOCK')) {
            define('FRONTEND_BLOCK', true);
        }
        $data = include $path;

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $exported
     */
    private function renderExportedBlock(array $exported): string
    {
        if (empty($exported['is_active'])) {
            return '';
        }
        $slug = (string) ($exported['slug'] ?? '');
        $type = (string) ($exported['block_type'] ?? 'flex');
        $config = isset($exported['config']) && is_array($exported['config']) ? $exported['config'] : [];
        $html = isset($config['html']) && is_string($config['html']) ? $config['html'] : '';
        $html = $this->replaceFieldTokens($html, $config);

        return $this->wrapBlockHtml($slug, $type, $html);
    }

    private function htmlFromDatabaseBlock(SiteBlock $block): string
    {
        $raw = trim((string) $block->config_json);
        if ($raw === '') {
            return '';
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach (['html', 'content', 'body'] as $k) {
                if (! empty($decoded[$k]) && is_string($decoded[$k])) {
                    return $this->replaceFieldTokens((string) $decoded[$k], $decoded);
                }
            }
            $parts = [];
            foreach ($decoded as $v) {
                if (is_string($v) && str_contains($v, '<')) {
                    $parts[] = $v;
                }
            }

            return implode('', $parts);
        }

        return $raw;
    }

    private function wrapBlockHtml(string $slug, string $type, string $html): string
    {
        $html = $this->normalizeLegacyExportedPhpEchoes($html);
        $html = $this->replaceSiteUrlTokens($html);
        if ($slug === 'trang-chu') {
            $html = $this->injectHomeDynamicSections($html);
        }
        $type = preg_replace('/[^a-z0-9_\-]/', '', strtolower($type)) ?: 'flex';
        $allowed = ['flex', 'intro', 'generic', 'custom'];
        if (! in_array($type, $allowed, true)) {
            $type = 'flex';
        }

        return '<section class="site-block site-block--'.$type.'" data-block-slug="'.e($slug).'">'.$html.'</section>';
    }

    private function injectHomeDynamicSections(string $html): string
    {
        if (str_contains($html, 'id="hoat-dong"')) {
            $inj = View::make('public.partials.home-hoat-dong', [
                'posts' => $this->homeEventPosts(),
                'eventsUrl' => url('/su-kien'),
            ])->render();
            if (trim($inj) !== '') {
                $new = (string) preg_replace(
                    '/(?:\s*<!--\s*Hoạt động Sunny\s*-->)?\s*<section id="hoat-dong"[^>]*>[\s\S]*?<\/section>/iu',
                    "\n".$inj."\n",
                    $html,
                    1
                );
                if ($new !== '') {
                    $html = $new;
                }
            }
        }
        if (str_contains($html, 'id="tin-tuc"')) {
            $news = $this->homeNewsPayload();
            if ($news !== null) {
                $inj = View::make('public.partials.home-tin-tuc', $news)->render();
                if (trim($inj) !== '') {
                    $new = (string) preg_replace(
                        '/(?:\s*<!--\s*Tin tức\s*-->)?\s*<section id="tin-tuc"[^>]*>[\s\S]*?<\/section>/iu',
                        "\n".$inj."\n",
                        $html,
                        1
                    );
                    if ($new !== '') {
                        $html = $new;
                    }
                }
            }
        }

        return $html;
    }

    /**
     * @return \Illuminate\Support\Collection<int, ContentPost>
     */
    private function homeEventPosts()
    {
        if (! Schema::hasTable('content_post')) {
            return collect();
        }
        $catId = 0;
        if (Schema::hasTable('site_settings')) {
            $raw = trim((string) app(\App\Services\Settings\SiteSettingsService::class)->get('home_events_category_id', ''));
            if ($raw !== '' && ctype_digit($raw)) {
                $catId = (int) $raw;
            }
        }
        if ($catId < 1 && Schema::hasTable('content_category')) {
            $row = ContentCategory::query()->inScope('events')->where('slug', 'su-kien')->where('is_active', 1)->first();
            $catId = $row ? (int) $row->id : 0;
        }
        if ($catId < 1) {
            return collect();
        }

        return ContentPost::query()->inScope('events')->where('category_id', $catId)
            ->where('status', 'published')->orderByDesc('published_at')->limit(6)->get();
    }

    /**
     * @return array{posts: \Illuminate\Support\Collection<int, ContentPost>, listUrl: string}|null
     */
    private function homeNewsPayload(): ?array
    {
        if (! Schema::hasTable('content_category') || ! Schema::hasTable('content_post')) {
            return null;
        }
        $cat = ContentCategory::query()->inScope('post')->where('slug', 'tin-tuc')->where('is_active', 1)->first();
        if ($cat === null) {
            return null;
        }
        $posts = ContentPost::query()->inScope('post')->with('category')->where('category_id', $cat->id)
            ->where('status', 'published')->orderByDesc('published_at')->limit(8)->get();
        if ($posts->isEmpty()) {
            return null;
        }

        return [
            'posts' => $posts,
            'listUrl' => url('/'.ltrim((string) $cat->slug, '/')),
        ];
    }

    private function normalizeLegacyExportedPhpEchoes(string $html): string
    {
        if ($html === '' || ! str_contains($html, '<?=')) {
            return $html;
        }
        $assetBase = rtrim(asset('assets'), '/');
        $html = (string) preg_replace('/<\?=\s*\$e\s*\(\s*\$assetPath\s*\)\s*\?>/i', $assetBase, $html);
        $html = (string) preg_replace_callback(
            '/<\?=\s*\$e\s*\(\s*\$linkPrefix\s*\)\s*\?>\s*([a-z0-9][a-z0-9\-]*\.php)(#[\w\-]*)?/iu',
            static function (array $m): string {
                $slug = (string) preg_replace('/\.php$/i', '', $m[1]);

                return url('/'.$slug).(! empty($m[2]) ? $m[2] : '');
            },
            $html
        );
        $html = (string) preg_replace('/<\?=\s*\$e\s*\(\s*\$linkPrefix\s*\)\s*\?>/i', '', $html);
        $html = (string) preg_replace_callback(
            '/<\?=\s*\$e\s*\(\s*\'([^\']*)\'\s*\)\s*\?>/u',
            static function (array $m): string {
                return e($m[1]);
            },
            $html
        );
        $html = (string) preg_replace_callback(
            '/<\?=\s*\$e\s*\(\s*"([^"]*)"\s*\)\s*\?>/u',
            static function (array $m): string {
                return e($m[1]);
            },
            $html
        );

        return $html;
    }

    public function lazyLoadMedia(string $html): string
    {
        if ($html === '' || (! str_contains($html, '<img') && ! str_contains($html, '<video') && ! str_contains($html, '<iframe'))) {
            return $html;
        }

        $html = (string) preg_replace_callback(
            '/<img\b([^>]*?)(\s*\/?)>/i',
            fn (array $m): string => '<img'.$this->withLazyImgAttrs($m[1]).$m[2].'>',
            $html
        );
        $html = (string) preg_replace_callback(
            '/<video\b([^>]*?)>/i',
            fn (array $m): string => '<video'.$this->withLazyVideoAttrs($m[1]).'>',
            $html
        );
        $html = (string) preg_replace_callback(
            '/<iframe\b([^>]*?)>/i',
            fn (array $m): string => '<iframe'.$this->withLazyIframeAttrs($m[1]).'>',
            $html
        );

        return $html;
    }

    private function withLazyImgAttrs(string $attrs): string
    {
        $eager = $this->attrIs($attrs, 'fetchpriority', 'high') || $this->attrIs($attrs, 'loading', 'eager');
        if (! $this->hasAttr($attrs, 'decoding')) {
            $attrs .= ' decoding="async"';
        }
        if (! $this->hasAttr($attrs, 'loading')) {
            $attrs .= $eager ? ' loading="eager"' : ' loading="lazy"';
        }
        if (! $eager && ! $this->hasAttr($attrs, 'fetchpriority')) {
            $attrs .= ' fetchpriority="low"';
        }

        return $attrs;
    }

    private function withLazyVideoAttrs(string $attrs): string
    {
        if ($this->hasAttr($attrs, 'preload')) {
            $attrs = (string) preg_replace('/\bpreload\s*=\s*(?:\'[^\']*\'|"[^"]*"|[^\s>]+)/i', 'preload="none"', $attrs);
        } else {
            $attrs .= ' preload="none"';
        }
        if (! $this->hasAttr($attrs, 'playsinline')) {
            $attrs .= ' playsinline';
        }

        return $attrs;
    }

    private function withLazyIframeAttrs(string $attrs): string
    {
        if (! $this->hasAttr($attrs, 'loading')) {
            $attrs .= ' loading="lazy"';
        }
        if ($this->hasAttr($attrs, 'data-src') || ! $this->hasAttr($attrs, 'src')) {
            return $attrs;
        }
        if (! preg_match('/\bsrc\s*=\s*([\'"])(.*?)\1/i', $attrs, $m)) {
            return $attrs;
        }
        $src = $m[2];
        if (! preg_match('/youtube\.com|youtu\.be|vimeo\.com|google\.com\/maps|facebook\.com\/plugins/i', $src)) {
            return $attrs;
        }
        $attrs = (string) preg_replace('/\s+src\s*=\s*([\'"]).*?\1/i', '', $attrs);

        return $attrs.' data-src="'.$src.'"';
    }

    private function hasAttr(string $attrs, string $name): bool
    {
        return (bool) preg_match('/\b'.preg_quote($name, '/').'\s*=/i', $attrs);
    }

    private function attrIs(string $attrs, string $name, string $value): bool
    {
        return (bool) preg_match(
            '/\b'.preg_quote($name, '/').'\s*=\s*(?:[\'"]'.preg_quote($value, '/').'[\'"]|'.preg_quote($value, '/').'\b)/i',
            $attrs
        );
    }

    private function replaceSiteUrlTokens(string $html): string
    {
        if (! str_contains($html, '{{site_url:')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/\{\{site_url:([a-z0-9\-]+)\}\}/',
            static function (array $m): string {
                return url('/'.$m[1]);
            },
            $html
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function replaceFieldTokens(string $html, array $config): string
    {
        return (string) preg_replace_callback('/\{\{([a-z0-9_]+)\}\}/', function (array $m) use ($config) {
            $k = $m[1];
            if (! isset($config[$k]) || is_array($config[$k])) {
                return $m[0];
            }

            return e($this->resolveLegacyMediaPath((string) $config[$k]));
        }, $html);
    }

    private function resolveLegacyMediaPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        $rewritten = \App\Support\PublicUrl::rewrite($path);
        if (preg_match('#^(https?:)?//#i', $rewritten)) {
            return $rewritten;
        }
        if (str_starts_with($rewritten, 'public/')) {
            return \App\Support\PublicUrl::to(substr($rewritten, 7));
        }
        $rewritten = (string) preg_replace('#^(?:\.\./|\./)+#', '', $rewritten);
        if (str_starts_with($rewritten, 'assets/')) {
            return asset($rewritten);
        }
        if (str_starts_with($rewritten, '/')) {
            return \App\Support\PublicUrl::to($rewritten);
        }

        return $rewritten;
    }
}
