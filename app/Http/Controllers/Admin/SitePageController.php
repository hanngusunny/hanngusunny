<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentCategory;
use App\Services\Content\ContentCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SitePageController extends Controller
{
    public function __construct(private readonly ContentCatalogService $catalog)
    {
    }

    public function index(): View
    {
        $rows = ContentCategory::query()->inScope('page')->orderByDesc('id')->get();

        return view('admin.pages.index', [
            'pageTitle' => 'Trang',
            'adminNavActive' => 'site_pages',
            'rows' => $rows,
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.form', [
            'pageTitle' => 'Thêm trang',
            'adminNavActive' => 'site_pages_add',
            'row' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:159'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        ContentCategory::query()->create([
            'scope' => 'page',
            'parent_id' => 0,
            'name' => $data['name'],
            'slug' => $this->catalog->uniqueCategorySlug('page', ($data['slug'] ?? '') !== '' ? $data['slug'] : $data['name']),
            'description' => $data['description'] ?? '',
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'is_active' => $request->boolean('is_active', true),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.site-pages.index')->with('content_success', 'Đã lưu.');
    }

    public function edit(ContentCategory $page): View
    {
        abort_unless($page->scope === 'page', 404);

        return view('admin.pages.form', [
            'pageTitle' => 'Sửa trang',
            'adminNavActive' => 'site_pages_edit',
            'row' => $page,
        ]);
    }

    public function update(Request $request, ContentCategory $page): RedirectResponse
    {
        abort_unless($page->scope === 'page', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:159'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $page->fill([
            'name' => $data['name'],
            'slug' => $this->catalog->uniqueCategorySlug('page', ($data['slug'] ?? '') !== '' ? $data['slug'] : $data['name'], $page->id),
            'description' => $data['description'] ?? '',
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'is_active' => $request->boolean('is_active', true),
        ])->save();

        return redirect()->route('admin.site-pages.index')->with('content_success', 'Đã cập nhật.');
    }

    public function destroy(ContentCategory $page): RedirectResponse
    {
        abort_unless($page->scope === 'page', 404);
        $page->delete();

        return redirect()->route('admin.site-pages.index')->with('content_success', 'Đã xóa.');
    }

    public function setHomepage(ContentCategory $page, \App\Services\Settings\SiteSettingsService $settings): RedirectResponse
    {
        abort_unless($page->scope === 'page', 404);
        $settings->saveMany(['home_page_id' => (string) $page->id]);

        return redirect()->route('admin.site-pages.index')->with('content_success', 'Đã đặt trang chủ.');
    }
}
