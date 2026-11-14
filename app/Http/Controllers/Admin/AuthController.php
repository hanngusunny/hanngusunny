<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\Auth\AdminAuthenticator;
use App\Services\Settings\SiteSettingsReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly SiteSettingsReader $settings,
        private readonly AdminAuthenticator $authenticator,
    ) {
    }

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        $preset = trim($this->settings->get('admin_login_color_preset', 'blue'));
        if ($preset === '' || ! in_array($preset, config('admin.login_presets'), true)) {
            $preset = 'blue';
        }

        $loginTheme = trim($this->settings->get('admin_login_theme', 'light'));
        if ($loginTheme !== 'dark') {
            $loginTheme = 'light';
        }

        $lang = session('admin_language', config('admin.language_default'));
        $locale = config('admin.languages.'.$lang.'.locale', app()->getLocale());

        return view('admin.auth.login', [
            'htmlLang' => $locale,
            'languageSwitch' => $this->languageSwitchRows($lang),
            'loginColorPreset' => $preset,
            'loginDefaultTheme' => $loginTheme,
            'loginError' => session('login_error'),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $result = $this->authenticator->attempt(
            trim((string) $request->validated('username')),
            (string) $request->validated('password'),
            $request->boolean('remember')
        );

        if (! $result['ok']) {
            return redirect()
                ->route('admin.login')
                ->with('login_error', __($result['message_key'] ?? 'admin_login.login_invalid'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authenticator->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * @return list<array{code: string, label: string, url: string, active: bool}>
     */
    private function languageSwitchRows(string $current): array
    {
        $out = [];
        foreach (config('admin.languages') as $code => $meta) {
            $out[] = [
                'code' => $code,
                'label' => __('admin_common.'.$meta['label_key']),
                'url' => route('admin.language.set', ['code' => $code]),
                'active' => $code === $current,
            ];
        }

        return $out;
    }
}
