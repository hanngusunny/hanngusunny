<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapBuilder;
use App\Services\Settings\SiteSettingsService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SeoController extends Controller
{
    public function __construct(private readonly SitemapBuilder $sitemaps)
    {
    }

    public function robots(SiteSettingsService $settings): Response
    {
        $prefix = rtrim((string) (parse_url((string) config('app.url'), PHP_URL_PATH) ?: ''), '/');
        $paths = ['/admin', '/admin/', '/api', '/api/', '/writable', '/writable/'];
        if ($prefix !== '') {
            $paths = array_map(static fn (string $p) => $prefix.$p, $paths);
        }
        $agents = ['*', 'Googlebot', 'Googlebot-Image', 'Googlebot-News'];
        $lines = ['# robots.txt — tự sinh bởi hệ thống', ''];
        foreach ($agents as $agent) {
            $lines[] = 'User-agent: '.$agent;
            foreach ($paths as $p) {
                $lines[] = 'Disallow: '.$p;
            }
            $lines[] = '';
        }
        $lines[] = 'Sitemap: '.url('/sitemap.xml');
        $extra = trim($settings->get('robots_txt_extra', ''));
        if ($extra !== '') {
            $lines[] = '';
            $lines[] = '# Bổ sung từ Admin → Cài đặt';
            $lines[] = $extra;
        }
        $body = rtrim(implode("\n", $lines))."\n";

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function sitemap(): Response
    {
        return $this->xml(view('sitemap.index', [
            'sitemaps' => $this->sitemaps->index(),
            'xslHref' => url('/main-sitemap.xsl'),
            'generator' => 'Du học SUNNY Laravel',
        ]));
    }

    public function sitemapPages(): Response
    {
        return $this->urlset($this->sitemaps->pages());
    }

    public function sitemapPosts(): Response
    {
        return $this->urlset($this->sitemaps->posts());
    }

    public function sitemapSchools(): Response
    {
        return $this->urlset($this->sitemaps->schools());
    }

    public function sitemapEvents(): Response
    {
        return $this->urlset($this->sitemaps->events());
    }

    /**
     * @param  list<array{loc: string, lastmod: string, changefreq: string, priority: string}>  $urls
     */
    private function urlset(array $urls): Response
    {
        return $this->xml(view('sitemap.urlset', [
            'urls' => $urls,
            'xslHref' => url('/main-sitemap.xsl'),
            'generator' => 'Du học SUNNY Laravel',
        ]));
    }

    private function xml(View $view): Response
    {
        return response($view->render(), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
