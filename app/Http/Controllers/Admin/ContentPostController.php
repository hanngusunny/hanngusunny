<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\ContentTag;
use App\Models\ContentTemplate;
use App\Services\Content\ContentCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentPostController extends Controller
{
    public function __construct(private readonly ContentCatalogService $catalog)
    {
    }

    public function index(Request $request, string $module): View
    {
        $cfg = $this->module($module);
        $q = trim((string) $request->query('q', ''));
        $categoryId = (int) $request->query('category_id', 0);
        $posts = ContentPost::query()->inScope($cfg['scope'])->orderByDesc('id');
        if ($q !== '') {
            $like = '%'.$q.'%';
            $posts->where(function ($w) use ($like) {
                $w->where('title', 'like', $like)->orWhere('slug', 'like', $like);
            });
        }
        if ($categoryId > 0) {
            $posts->where('category_id', $categoryId);
        }

        return view('admin.content.posts.index', [
            'pageTitle' => $cfg['label'],
            'adminNavActive' => $cfg['nav_posts'],
            'module' => $module,
            'cfg' => $cfg,
            'q' => $q,
            'categoryId' => $categoryId,
            'categories' => ContentCategory::query()->inScope($cfg['scope'])->orderBy('name')->get(),
            'posts' => $posts->paginate(20)->withQueryString(),
        ]);
    }

    public function create(string $module): View
    {
        $cfg = $this->module($module);

        return view('admin.content.posts.form', [
            'pageTitle' => $cfg['label'],
            'adminNavActive' => $cfg['nav_posts'],
            'module' => $module,
            'cfg' => $cfg,
            'post' => null,
            'categories' => ContentCategory::query()->inScope($cfg['scope'])->orderBy('name')->get(),
            'templates' => $this->articleTemplates($cfg['scope']),
            'tags' => ContentTag::query()->inScope($cfg['scope'])->orderBy('name')->get(),
            'selectedTagIds' => [],
        ]);
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $cfg = $this->module($module);
        $data = $this->validated($request);
        $slug = $this->catalog->uniquePostSlug($cfg['scope'], ($data['slug'] ?? '') !== '' ? $data['slug'] : $data['title']);
        $post = ContentPost::query()->create([
            'scope' => $cfg['scope'],
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? '',
            'body' => $data['body'] ?? '',
            'featured_image' => $data['featured_image'] ?? '',
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
            'template_id' => $data['template_id'] ?? null,
            'og_image' => $data['og_image'] ?? '',
            'og_title' => $data['og_title'] ?? '',
            'og_description' => $data['og_description'] ?? '',
            'created_at' => now(),
        ]);
        $this->syncTags($post, $request, $cfg['scope']);

        return redirect()->route('admin.'.$module.'.posts.index')->with('content_success', 'Đã lưu.');
    }

    public function edit(string $module, ContentPost $post): View
    {
        $cfg = $this->module($module);
        abort_unless($post->scope === $cfg['scope'], 404);

        return view('admin.content.posts.form', [
            'pageTitle' => $cfg['label'],
            'adminNavActive' => $cfg['nav_posts'],
            'module' => $module,
            'cfg' => $cfg,
            'post' => $post,
            'categories' => ContentCategory::query()->inScope($cfg['scope'])->orderBy('name')->get(),
            'templates' => $this->articleTemplates($cfg['scope']),
            'tags' => ContentTag::query()->inScope($cfg['scope'])->orderBy('name')->get(),
            'selectedTagIds' => $post->tags()->pluck('content_tag.id')->all(),
        ]);
    }

    public function update(Request $request, string $module, ContentPost $post): RedirectResponse
    {
        $cfg = $this->module($module);
        abort_unless($post->scope === $cfg['scope'], 404);
        $data = $this->validated($request);
        $post->fill([
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'slug' => $this->catalog->uniquePostSlug($cfg['scope'], ($data['slug'] ?? '') !== '' ? $data['slug'] : $data['title'], $post->id),
            'excerpt' => $data['excerpt'] ?? '',
            'body' => $data['body'] ?? '',
            'featured_image' => $data['featured_image'] ?? '',
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? ($post->published_at ?? now()) : $post->published_at,
            'template_id' => $data['template_id'] ?? null,
            'og_image' => $data['og_image'] ?? '',
            'og_title' => $data['og_title'] ?? '',
            'og_description' => $data['og_description'] ?? '',
        ])->save();
        $this->syncTags($post, $request, $cfg['scope']);

        return redirect()->route('admin.'.$module.'.posts.index')->with('content_success', 'Đã cập nhật.');
    }

    public function destroy(string $module, ContentPost $post): RedirectResponse
    {
        $cfg = $this->module($module);
        abort_unless($post->scope === $cfg['scope'], 404);
        $post->delete();

        return redirect()->route('admin.'.$module.'.posts.index')->with('content_success', 'Đã xóa.');
    }

    private function module(string $module): array
    {
        $cfg = config('content.modules.'.$module);
        abort_if(! is_array($cfg), 404);

        return $cfg;
    }

    /**
     * @return array{title: string, slug: string, excerpt: ?string, body: ?string, status: string, category_id: ?int}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:159'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
            'category_id' => ['nullable', 'integer'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
            'template_id' => ['nullable', 'integer'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function syncTags(ContentPost $post, Request $request, string $scope): void
    {
        $ids = array_map('intval', (array) $request->input('tag_ids', []));
        $valid = ContentTag::query()->inScope($scope)->whereIn('id', $ids)->pluck('id')->all();
        $post->tags()->sync($valid);
    }

    private function articleTemplates(string $scope)
    {
        return ContentTemplate::query()->where('scope', $scope)->where('page_type', 'article')->orderBy('name')->get();
    }
}
