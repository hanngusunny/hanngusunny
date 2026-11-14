<?php

namespace App\View\Composers;

use App\Services\Settings\SiteSettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLayoutComposer
{
    public function __construct(private readonly SiteSettingsService $settings)
    {
    }

    public function compose(View $view): void
    {
        $user = Auth::guard('admin')->user();
        $name = trim((string) ($user?->name ?? ''));
        $username = (string) ($user?->username ?? '');
        $display = $name !== '' ? $name : $username;
        $hex = $this->settings->get('admin_accent_color', '#3b82f6');
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            $hex = '#3b82f6';
        }
        $d = ltrim($hex, '#');
        $r = hexdec(substr($d, 0, 2));
        $g = hexdec(substr($d, 2, 2));
        $b = hexdec(substr($d, 4, 2));
        $soft = 'rgba('.$r.','.$g.','.$b.',0.15)';

        $lang = session('admin_language', config('admin.language_default'));
        $switch = [];
        foreach (config('admin.languages') as $code => $meta) {
            $switch[] = [
                'code' => $code,
                'label' => __('admin_common.'.$meta['label_key']),
                'url' => route('admin.language.set', ['code' => $code]),
                'active' => $code === $lang,
            ];
        }

        $view->with([
            'adminAssets' => rtrim(admin_asset(''), '/').'/',
            'adminDisplayName' => $display,
            'adminInitials' => admin_initials($display, $username),
            'adminAvatarUrl' => $user && $user->avatar ? asset($user->avatar) : '',
            'adminIsSuper' => (bool) ($user?->is_super_admin),
            'adminLanguageSwitch' => $switch,
            'adminHtmlLang' => config('admin.languages.'.$lang.'.locale', 'vi'),
            'adminThemeInline' => '<style id="admin-theme-vars">:root{--dash-accent:'.e($hex).';--dash-accent-soft:'.e($soft).';}</style>',
        ]);
    }
}
