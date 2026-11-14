<?php

namespace App\View\Composers;

use App\Services\Nav\NavMenuService;
use App\Services\Seo\JsonLdBuilder;
use App\Services\Settings\SiteSettingsService;
use Illuminate\View\View;

class PublicLayoutComposer
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly NavMenuService $nav,
        private readonly JsonLdBuilder $jsonLd,
    ) {
    }

    public function compose(View $view): void
    {
        $site = $this->settings->all();
        $hotline = trim((string) ($site['frontend_topbar_hotline'] ?? '')) ?: trim((string) ($site['contact_phone'] ?? ''));
        $hotlineDigits = preg_replace('/\D+/', '', $hotline) ?? '';
        $path = trim(request()->path(), '/');
        $pageSlug = $path === '' ? '' : basename($path);
        $logo = \App\Support\PublicUrl::to((string) ($site['logo_url'] ?? ''));
        if ($logo === '') {
            $logo = asset('assets/img/logo.png');
        }
        $favicon = \App\Support\PublicUrl::to((string) ($site['favicon_url'] ?? ''));

        $view->with([
            'site' => $site,
            'publicNavTree' => $this->nav->publicNav(),
            'publicHotline' => $hotline,
            'publicHotlineTel' => $hotlineDigits !== '' ? 'tel:'.$hotlineDigits : '',
            'pageSlug' => $pageSlug,
            'navIsHome' => $path === '' || $path === '/',
            'publicLogoUrl' => $logo,
            'publicFaviconUrl' => $favicon,
            'assetPath' => rtrim(asset('assets'), '/'),
            'jsonLd' => $this->jsonLd->fromViewData($view->getData() + [
                'isHome' => $path === '' || $path === '/',
            ]),
        ]);
    }
}
