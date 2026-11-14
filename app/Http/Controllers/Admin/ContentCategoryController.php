<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentCategory;
use App\Services\Content\ContentCatalogService;
use App\Support\GeoCoords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentCategoryController extends Controller
{
    public function __construct(private readonly ContentCatalogService $catalog)
    {
    }

    public function index(string $module): View
    {
        $cfg = $this->module($module);
        $rows = ContentCategory::query()->inScope($cfg['scope'])->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.content.categories.index', [
            'pageTitle' => $cfg['label'].' — chuyên mục',
            'adminNavActive' => $cfg['nav_categories'],
            'module' => $module,
            'cfg' => $cfg,
            'rows' => $rows,
        ]);
    }

    public function create(string $module): View
    {
        $cfg = $this->module($module);

        return view('admin.content.categories.form', [
            'pageTitle' => $cfg['label'],
            'adminNavActive' => $cfg['nav_categories'],
            'module' => $module,
            'cfg' => $cfg,
            'row' => null,
            'parents' => ContentCategory::query()->inScope($cfg['scope'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $cfg = $this->module($module);
        $data = $this->validated($request);
        $geo = $cfg['scope'] === 'school'
            ? GeoCoords::parse($request->input('map_lat'), $request->input('map_lng'), $request->input('map_region'))
            : ['map_lat' => null, 'map_lng' => null, 'map_region' => ''];
        ContentCategory::query()->create(array_merge([
            'scope' => $cfg['scope'],
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'name' => $data['name'],
            'slug' => $this->catalog->uniqueCategorySlug($cfg['scope'], $data['slug'] !== '' ? $data['slug'] : $data['name']),
            'description' => $data['description'] ?? '',
            'logo_url' => $data['logo_url'] ?? '',
            'cover_image' => $data['cover_image'] ?? '',
            'official_website_url' => $data['official_website_url'] ?? '',
            'is_active' => $request->boolean('is_active', true),
            'created_at' => now(),
        ], $geo));

        return redirect()->route('admin.'.$module.'.categories.index')->with('content_success', 'Đã lưu.');
    }

    public function edit(string $module, ContentCategory $category): View
    {
        $cfg = $this->module($module);
        abort_unless($category->scope === $cfg['scope'], 404);

        return view('admin.content.categories.form', [
            'pageTitle' => $cfg['label'],
            'adminNavActive' => $cfg['nav_categories'],
            'module' => $module,
            'cfg' => $cfg,
            'row' => $category,
            'parents' => ContentCategory::query()->inScope($cfg['scope'])->where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $module, ContentCategory $category): RedirectResponse
    {
        $cfg = $this->module($module);
        abort_unless($category->scope === $cfg['scope'], 404);
        $data = $this->validated($request);
        $payload = [
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'name' => $data['name'],
            'slug' => $this->catalog->uniqueCategorySlug($cfg['scope'], $data['slug'] !== '' ? $data['slug'] : $data['name'], $category->id),
            'description' => $data['description'] ?? '',
            'logo_url' => $data['logo_url'] ?? '',
            'cover_image' => $data['cover_image'] ?? '',
            'official_website_url' => $data['official_website_url'] ?? '',
            'is_active' => $request->boolean('is_active', true),
        ];
        if ($cfg['scope'] === 'school') {
            $payload = array_merge($payload, GeoCoords::parse($request->input('map_lat'), $request->input('map_lng'), $request->input('map_region')));
        }
        $category->fill($payload)->save();

        return redirect()->route('admin.'.$module.'.categories.index')->with('content_success', 'Đã cập nhật.');
    }

    public function destroy(string $module, ContentCategory $category): RedirectResponse
    {
        $cfg = $this->module($module);
        abort_unless($category->scope === $cfg['scope'], 404);
        $category->delete();

        return redirect()->route('admin.'.$module.'.categories.index')->with('content_success', 'Đã xóa.');
    }

    public function moveUp(string $module, ContentCategory $category): RedirectResponse
    {
        return $this->move($module, $category, 'up');
    }

    public function moveDown(string $module, ContentCategory $category): RedirectResponse
    {
        return $this->move($module, $category, 'down');
    }

    private function move(string $module, ContentCategory $category, string $dir): RedirectResponse
    {
        $cfg = $this->module($module);
        abort_unless($category->scope === $cfg['scope'], 404);
        $siblings = ContentCategory::query()->inScope($cfg['scope'])
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort_order')->orderBy('id')->get();
        \App\Support\SortOrderMover::move($siblings, (int) $category->id, $dir);

        return back()->with('content_success', 'Đã đổi thứ tự.');
    }

    private function module(string $module): array
    {
        $cfg = config('content.modules.'.$module);
        abort_if(! is_array($cfg), 404);

        return $cfg;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:159'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'official_website_url' => ['nullable', 'string', 'max:500'],
            'map_lat' => ['nullable', 'string', 'max:32'],
            'map_lng' => ['nullable', 'string', 'max:32'],
            'map_region' => ['nullable', 'string', 'max:64'],
        ]);
    }
}
