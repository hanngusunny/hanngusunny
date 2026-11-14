<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUiSettingsController extends Controller
{
    public function __construct(private readonly SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        return view('admin.settings.ui', [
            'pageTitle' => __('admin_admin_settings.admin_ui_page_title'),
            'adminNavActive' => 'admin_ui_settings',
            'settings' => $this->settings->all(),
            'languages' => config('admin.languages'),
            'presets' => config('admin.login_presets'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $allowedLang = array_keys(config('admin.languages'));
        $data = $request->validate([
            'admin_default_language' => ['required', 'in:'.implode(',', $allowedLang)],
            'admin_accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_login_theme' => ['required', 'in:light,dark'],
            'admin_login_color_preset' => ['required', 'in:'.implode(',', config('admin.login_presets'))],
        ]);
        $this->settings->saveMany($data);
        $request->session()->put('admin_language', $data['admin_default_language']);

        return redirect()->route('admin.admin-settings.edit')->with('admin_ui_success', __('admin_admin_settings.admin_ui_flash_saved'));
    }
}
