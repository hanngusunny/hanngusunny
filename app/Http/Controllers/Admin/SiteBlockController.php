<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteBlock;
use App\Services\Content\ContentCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteBlockController extends Controller
{
    public function __construct(private readonly ContentCatalogService $catalog)
    {
    }

    public function index(): View
    {
        return view('admin.blocks.index', [
            'pageTitle' => 'Blocks',
            'adminNavActive' => 'blocks_list',
            'rows' => SiteBlock::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.blocks.form', ['pageTitle' => 'Thêm block', 'adminNavActive' => 'blocks_add', 'row' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        SiteBlock::query()->create([
            'name' => $data['name'],
            'slug' => $this->catalog->slugify($data['slug'] !== '' ? $data['slug'] : $data['name']),
            'block_type' => $data['block_type'],
            'config_json' => $this->configFromRequest($data),
            'is_active' => $request->boolean('is_active', true),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.blocks.index')->with('ok', 'Đã lưu.');
    }

    public function edit(SiteBlock $site_block): View
    {
        return view('admin.blocks.form', ['pageTitle' => 'Sửa block', 'adminNavActive' => 'blocks_edit', 'row' => $site_block]);
    }

    public function update(Request $request, SiteBlock $site_block): RedirectResponse
    {
        $data = $this->validated($request);
        $site_block->update([
            'name' => $data['name'],
            'slug' => $this->catalog->slugify($data['slug'] !== '' ? $data['slug'] : $data['name']),
            'block_type' => $data['block_type'],
            'config_json' => $this->configFromRequest($data),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.blocks.index')->with('ok', 'Đã cập nhật.');
    }

    public function destroy(SiteBlock $site_block): RedirectResponse
    {
        $site_block->delete();

        return redirect()->route('admin.blocks.index')->with('ok', 'Đã xóa.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191'],
            'block_type' => ['required', 'string', 'max:64'],
            'config_json' => ['nullable', 'string'],
            'html' => ['nullable', 'string'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function configFromRequest(array $data): string
    {
        $raw = trim((string) ($data['config_json'] ?? ''));
        $html = trim((string) ($data['html'] ?? ''));
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $decoded = $raw !== '' ? ['html' => $raw] : [];
        }
        if ($html !== '') {
            $decoded['html'] = $html;
        }

        return $decoded === [] ? '' : (string) json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
}
