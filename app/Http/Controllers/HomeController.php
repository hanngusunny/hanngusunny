<?php

namespace App\Http\Controllers;

use App\Models\ContentCategory;
use App\Services\Content\CmsHtmlService;
use App\Services\Settings\SiteSettingsService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly CmsHtmlService $html,
    ) {
    }

    public function index(): View
    {
        if ($this->settings->comingSoonActive()) {
            return view('public.coming-soon', [
                'siteName' => $this->settings->get('site_name', 'Website'),
                'siteTagline' => $this->settings->get('site_tagline'),
                'untilIso' => date('c', strtotime($this->settings->get('coming_soon_until'))),
            ]);
        }

        $homeId = (int) $this->settings->get('home_page_id', '');
        $page = null;
        if ($homeId > 0) {
            $page = ContentCategory::query()->inScope('page')->where('id', $homeId)->where('is_active', 1)->first();
        }

        return view('public.page', [
            'site' => $this->settings->all(),
            'page' => $page,
            'descriptionHtml' => $page ? $this->html->expand((string) $page->description) : '',
            'isHome' => true,
            'title' => $page?->meta_title ?: $this->settings->get('meta_title_default', $this->settings->get('site_name')),
            'metaDescription' => $page?->meta_description ?: $this->settings->get('meta_description_default'),
            'canonicalUrl' => url('/'),
        ]);
    }
}
