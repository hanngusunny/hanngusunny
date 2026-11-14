<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteCustomizeController extends Controller
{
    public function __construct(private readonly SiteSettingsService $settings)
    {
    }

    public function identity(): View
    {
        return view('admin.customize.identity', [
            'pageTitle' => 'Nhận diện',
            'adminNavActive' => 'site_customize',
            'settings' => $this->settings->all(),
        ]);
    }

    public function saveIdentity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'logo_url' => ['nullable', 'string', 'max:500'],
            'toolbar_logo_url' => ['nullable', 'string', 'max:500'],
        ]);
        $this->settings->saveMany($data);

        return back()->with('ok', 'Đã lưu nhận diện.');
    }

    public function topbar(): View
    {
        return view('admin.customize.topbar', [
            'pageTitle' => 'Topbar',
            'adminNavActive' => 'site_customize',
            'settings' => $this->settings->all(),
        ]);
    }

    public function saveTopbar(Request $request): RedirectResponse
    {
        $h = max(32, min(200, (int) $request->input('frontend_topbar_height_px', 56)));
        $this->settings->saveMany([
            'frontend_topbar_enabled' => $request->boolean('frontend_topbar_enabled') ? '1' : '0',
            'frontend_topbar_height_px' => (string) $h,
            'frontend_topbar_bg_color' => (string) $request->input('frontend_topbar_bg_color', ''),
            'frontend_topbar_hotline' => trim((string) $request->input('frontend_topbar_hotline', '')),
            'frontend_topbar_tagline' => trim((string) $request->input('frontend_topbar_tagline', '')),
        ]);

        return back()->with('ok', 'Đã lưu topbar.');
    }
}
