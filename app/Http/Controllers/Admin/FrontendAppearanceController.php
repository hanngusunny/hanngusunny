<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontendAppearanceController extends Controller
{
    public function edit(Request $request): View
    {
        $files = config('appearance.files');
        $key = (string) $request->query('file', array_key_first($files) ?: 'layout');
        if (! isset($files[$key])) {
            $key = (string) array_key_first($files);
        }
        $absolute = $this->resolvePath($files[$key]);
        $content = is_readable($absolute) ? (string) file_get_contents($absolute) : '';

        return view('admin.appearance.edit', [
            'pageTitle' => 'Giao diện frontend',
            'adminNavActive' => 'frontend_appearance',
            'files' => $files,
            'fileKey' => $key,
            'content' => $content,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless((bool) auth('admin')->user()?->isSuperAdmin(), 403);
        $files = config('appearance.files');
        $key = (string) $request->input('file');
        abort_unless(isset($files[$key]), 404);
        $content = (string) $request->input('content', '');
        $absolute = $this->resolvePath($files[$key]);
        file_put_contents($absolute, $content);

        return redirect()->route('admin.appearance.edit', ['file' => $key])->with('ok', 'Đã lưu file giao diện.');
    }

    private function resolvePath(string $relative): string
    {
        $base = realpath(base_path('resources/views/public'));
        $target = realpath(base_path($relative));
        $norm = static fn (string $p) => strtolower(str_replace('\\', '/', $p));
        abort_unless($base && $target && str_starts_with($norm($target), $norm($base)), 404);

        return $target;
    }
}
