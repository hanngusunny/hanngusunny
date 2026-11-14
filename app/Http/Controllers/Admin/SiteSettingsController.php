<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSiteSettingsRequest;
use App\Models\ContentCategory;
use App\Services\Settings\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function __construct(private readonly SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        return view('admin.settings.site', [
            'pageTitle' => __('admin_settings.settings_page_title'),
            'adminNavActive' => 'site_settings',
            'settings' => $this->settings->all(),
            'timezoneOptions' => [
                'UTC' => 'UTC',
                'Asia/Ho_Chi_Minh' => 'Asia/Ho_Chi_Minh',
                'Asia/Bangkok' => 'Asia/Bangkok',
                'Asia/Singapore' => 'Asia/Singapore',
                'Asia/Seoul' => 'Asia/Seoul',
                'Asia/Tokyo' => 'Asia/Tokyo',
            ],
            'eventCategories' => ContentCategory::query()->inScope('events')->orderBy('name')->get(),
        ]);
    }

    public function update(SaveSiteSettingsRequest $request): RedirectResponse
    {
        $current = $this->settings->all();
        $keys = [
            'site_name', 'site_tagline', 'site_description', 'site_keywords',
            'contact_email', 'contact_phone', 'contact_address', 'business_hours',
            'meta_title_default', 'meta_description_default', 'footer_text',
            'mail_from_name', 'mail_from_email', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_user',
            'google_analytics_id', 'google_tag_manager_id', 'robots_txt_extra',
            'timezone', 'items_per_page_admin', 'coming_soon_until',
            'home_events_category_id', 'public_comments_enabled',
        ];
        $payload = $current;
        foreach ($keys as $key) {
            if ($request->exists($key)) {
                $payload[$key] = (string) $request->input($key, '');
            }
        }
        $payload['maintenance_mode'] = $request->boolean('maintenance_mode') ? '1' : '0';
        $payload['coming_soon_enabled'] = $request->boolean('coming_soon_enabled') ? '1' : '0';
        $payload['public_comments_enabled'] = $request->boolean('public_comments_enabled') ? '1' : '0';
        $this->settings->saveMany($payload);

        return redirect()->route('admin.settings.edit')->with('settings_success', __('admin_settings.settings_flash_saved'));
    }
}
