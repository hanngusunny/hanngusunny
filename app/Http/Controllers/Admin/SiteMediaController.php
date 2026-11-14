<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteMedia;
use App\Models\SiteMediaCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteMediaController extends Controller
{
    public function index(): View
    {
        return view('admin.media.index', [
            'pageTitle' => 'Thư viện ảnh',
            'adminNavActive' => 'media',
            'items' => SiteMedia::query()->orderByDesc('id')->paginate(24),
            'categories' => SiteMediaCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function json(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $rows = SiteMedia::query()->orderByDesc('id');
        if ($q !== '') {
            $rows->where(function ($w) use ($q) {
                $w->where('title', 'like', '%'.$q.'%')->orWhere('original_name', 'like', '%'.$q.'%');
            });
        }
        $items = $rows->limit(40)->get()->map(fn (SiteMedia $m) => [
            'id' => $m->id,
            'title' => $m->title,
            'url' => asset($m->path),
            'path' => $m->path,
        ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:8192', 'mimes:jpg,jpeg,png,gif,webp,svg']]);
        $file = $request->file('file');
        $size = (int) $file->getSize();
        $mime = (string) $file->getMimeType();
        $orig = (string) $file->getClientOriginalName();
        $dir = public_path('uploads/site_media');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'm_'.Str::random(16).'.'.strtolower($file->getClientOriginalExtension());
        $file->move($dir, $name);
        $rel = 'uploads/site_media/'.$name;
        SiteMedia::query()->create([
            'category_id' => (int) $request->input('category_id', 1) ?: 1,
            'path' => $rel,
            'original_name' => $orig,
            'title' => pathinfo($orig, PATHINFO_FILENAME),
            'mime' => $mime,
            'file_size' => $size,
            'created_at' => now(),
        ]);

        return back()->with('ok', 'Đã tải lên.');
    }

    public function destroy(SiteMedia $site_media): RedirectResponse
    {
        $full = public_path($site_media->path);
        if (is_file($full)) {
            @unlink($full);
        }
        $site_media->delete();

        return back()->with('ok', 'Đã xóa.');
    }

    public function storeFromUrl(Request $request): JsonResponse
    {
        $raw = trim((string) $request->input('url', ''));
        if ($raw === '') {
            return response()->json(['ok' => false, 'error' => 'empty_url', 'message' => 'Nhập URL ảnh.'], 400);
        }
        if (strlen($raw) > 2048) {
            return response()->json(['ok' => false, 'error' => 'url_too_long'], 400);
        }
        if (! str_contains($raw, '://')) {
            $raw = 'https://'.$raw;
        }
        $parsed = parse_url($raw);
        if (! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            return response()->json(['ok' => false, 'error' => 'bad_url', 'message' => 'URL không hợp lệ.'], 400);
        }
        $scheme = strtolower((string) $parsed['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return response()->json(['ok' => false, 'error' => 'bad_scheme'], 400);
        }
        if (! $this->remoteUrlSafe($raw)) {
            return response()->json(['ok' => false, 'error' => 'unsafe_url', 'message' => 'URL không được phép.'], 400);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'smu_');
        if ($tmp === false) {
            return response()->json(['ok' => false, 'error' => 'temp'], 500);
        }
        $ctx = stream_context_create([
            'http' => ['timeout' => 12, 'follow_location' => 0, 'header' => "User-Agent: duhocsunny-media\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $data = @file_get_contents($raw, false, $ctx, 0, 8 * 1024 * 1024 + 1);
        if ($data === false || strlen($data) > 8 * 1024 * 1024) {
            @unlink($tmp);

            return response()->json(['ok' => false, 'error' => 'download', 'message' => 'Không tải được ảnh.'], 400);
        }
        file_put_contents($tmp, $data);
        $info = @getimagesize($tmp);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $extMap[$mime] ?? '';
        if ($ext === '') {
            @unlink($tmp);

            return response()->json(['ok' => false, 'error' => 'not_image', 'message' => 'Không phải ảnh hợp lệ.'], 400);
        }
        $dir = public_path('uploads/site_media');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'm_'.Str::random(16).'.'.$ext;
        $rel = 'uploads/site_media/'.$name;
        rename($tmp, $dir.DIRECTORY_SEPARATOR.$name);
        $row = SiteMedia::query()->create([
            'category_id' => (int) $request->input('category_id', 1) ?: 1,
            'path' => $rel,
            'original_name' => basename((string) ($parsed['path'] ?? $name)),
            'title' => pathinfo((string) ($parsed['path'] ?? $name), PATHINFO_FILENAME) ?: $name,
            'mime' => $mime,
            'file_size' => strlen($data),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true, 'id' => $row->id, 'url' => asset($rel), 'path' => $rel]);
    }

    private function remoteUrlSafe(string $url): bool
    {
        $p = parse_url($url);
        if (! is_array($p) || empty($p['host'])) {
            return false;
        }
        $host = strtolower((string) $p['host']);
        if ($host === 'localhost' || str_starts_with($host, '127.')) {
            return false;
        }
        $ip = gethostbyname($host);
        if ($ip === '' || $ip === $host) {
            if (! filter_var($host, FILTER_VALIDATE_IP)) {
                return false;
            }
            $ip = $host;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return false;
    }
}
