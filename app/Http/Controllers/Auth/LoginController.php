<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\UserActivityService;
use App\Support\PwaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = User::findByLogin($request->string('login')->trim()->toString());

        if (! $user || ! Auth::getProvider()->validateCredentials($user, [
            'password' => $request->string('password')->toString(),
        ])) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->with('error', 'Số điện thoại hoặc mật khẩu không đúng.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();
        app(UserActivityService::class)->startLoginSession($user, $request);

        $intended = (string) $request->session()->get('url.intended', '');

        if (PwaClient::active($request) || str_contains($intended, '/app/')) {
            PwaClient::mark($request);

            return redirect()->intended(route('pwa.home'))
                ->with('success', 'Đăng nhập thành công. Chào mừng '.$user->name.'!');
        }

        return redirect()->intended($user->homePath())
            ->with('success', 'Đăng nhập thành công. Chào mừng '.$user->name.'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            app(UserActivityService::class)->endLoginSession($user, $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Bạn đã đăng xuất thành công.');
    }
}
