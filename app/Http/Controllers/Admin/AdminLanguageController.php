<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLanguageController extends Controller
{
    public function set(Request $request, string $code): RedirectResponse
    {
        $allowed = array_keys(config('admin.languages'));
        if (! in_array($code, $allowed, true)) {
            $code = config('admin.language_default');
        }

        $request->session()->put('admin_language', $code);

        return redirect()
            ->back()
            ->cookie('admin_language', $code, 60 * 24 * 365, '/');
    }
}
