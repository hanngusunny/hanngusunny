<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentTag;
use App\Services\Content\ContentCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentTagController extends Controller
{
    public function __construct(private readonly ContentCatalogService $catalog)
    {
    }

    public function index(string $module): View
    {
        $cfg = $this->module($module);
        $rows = ContentTag::query()->inScope($cfg['scope'])->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.content.tags.index', [
            'pageTitle' => $cfg['label'].' — tags',
            'adminNavActive' => $cfg['nav_tags'],
            'module' => $module,
            'cfg' => $cfg,
            'rows' => $rows,
        ]);
    }

    public function create(string $module): View
    {
        $cfg = $this->module($module);

        return view('admin.content.tags.form', [
            'pageTitle' => $cfg['label'],
            'adminNavActive' => $cfg['nav_tags'],
            'module' => $module,
            'cfg' => $cfg,
            'row' => null,
        ]);
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $cfg = $this->module($module);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:159'],
        ]);
        ContentTag::query()->create([
            'scope' => $cfg['scope'],
            'name' => $data['name'],
            'slug' => $this->catalog->uniqueTagSlug($cfg['scope'], $data['slug'] !== '' ? $data['slug'] : $data['name']),
            'is_active' => 1,
            'created_at' => now(),
        ]);

        return redirect()->route('admin.'.$module.'.tags.index')->with('content_success', 'Đã lưu.');
    }

    public function edit(string $module, ContentTag $tag): View
    {
        $cfg = $this->module($module);
        abort_unless($tag->scope === $cfg['scope'], 404);

        return view('admin.content.tags.form', [
            'pageTitle' => $cfg['label'],
            'adminNavActive' => $cfg['nav_tags'],
            'module' => $module,
            'cfg' => $cfg,
            'row' => $tag,
        ]);
    }

    public function update(Request $request, string $module, ContentTag $tag): RedirectResponse
    {
        $cfg = $this->module($module);
        abort_unless($tag->scope === $cfg['scope'], 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:159'],
        ]);
        $tag->fill([
            'name' => $data['name'],
            'slug' => $this->catalog->uniqueTagSlug($cfg['scope'], $data['slug'] !== '' ? $data['slug'] : $data['name'], $tag->id),
        ])->save();

        return redirect()->route('admin.'.$module.'.tags.index')->with('content_success', 'Đã cập nhật.');
    }

    public function destroy(string $module, ContentTag $tag): RedirectResponse
    {
        $cfg = $this->module($module);
        abort_unless($tag->scope === $cfg['scope'], 404);
        $tag->delete();

        return redirect()->route('admin.'.$module.'.tags.index')->with('content_success', 'Đã xóa.');
    }

    public function moveUp(string $module, ContentTag $tag): RedirectResponse
    {
        return $this->move($module, $tag, 'up');
    }

    public function moveDown(string $module, ContentTag $tag): RedirectResponse
    {
        return $this->move($module, $tag, 'down');
    }

    private function move(string $module, ContentTag $tag, string $dir): RedirectResponse
    {
        $cfg = $this->module($module);
        abort_unless($tag->scope === $cfg['scope'], 404);
        $siblings = ContentTag::query()->inScope($cfg['scope'])->orderBy('sort_order')->orderBy('id')->get();
        \App\Support\SortOrderMover::move($siblings, (int) $tag->id, $dir);

        return back()->with('content_success', 'Đã đổi thứ tự.');
    }

    private function module(string $module): array
    {
        $cfg = config('content.modules.'.$module);
        abort_if(! is_array($cfg), 404);

        return $cfg;
    }
}
