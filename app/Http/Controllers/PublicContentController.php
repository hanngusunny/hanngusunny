<?php

namespace App\Http\Controllers;

use App\Models\ContentCategory;
use App\Models\ContentComment;
use App\Models\ContentPost;
use App\Models\ContentTag;
use App\Services\Content\CmsHtmlService;
use App\Services\Content\SchoolMapService;
use App\Services\Settings\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicContentController extends Controller
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly CmsHtmlService $html,
        private readonly SchoolMapService $schoolMap,
    ) {
    }

    public function post(string $slug): View
    {
        return $this->showPost('post', $slug);
    }

    public function event(string $slug): View
    {
        return $this->showPost('events', $slug);
    }

    public function major(string $slug): View
    {
        return $this->showPost('major', $slug);
    }

    public function school(string $categorySlug, string $postSlug): View
    {
        ContentCategory::query()->inScope('school')->where('slug', $categorySlug)->where('is_active', 1)->firstOrFail();
        $post = ContentPost::query()->inScope('school')->with(['category', 'tags'])->where('slug', $postSlug)->where('status', 'published')->firstOrFail();
        $this->bumpViews($post);

        return view('public.post', $this->articlePayload($post, url('/truong/'.$categorySlug.'/'.$postSlug.'/comment')));
    }

    public function categoryPost(string $categorySlug, string $postSlug): View
    {
        $cat = ContentCategory::query()->inScope('post')->where('slug', $categorySlug)->where('is_active', 1)->firstOrFail();
        $post = ContentPost::query()->inScope('post')->with(['category', 'tags'])
            ->where('slug', $postSlug)
            ->where('category_id', $cat->id)
            ->where('status', 'published')
            ->firstOrFail();
        $this->bumpViews($post);

        return view('public.post', $this->articlePayload($post));
    }

    public function schoolLegacy(string $slug): View
    {
        return $this->showPost('school', $slug);
    }

    public function redirectOldPage(string $slug): RedirectResponse
    {
        return redirect()->to('/'.ltrim($slug, '/'), 301);
    }

    public function tag(string $slug): View
    {
        $tag = ContentTag::query()->where('slug', $slug)->where('is_active', 1)->firstOrFail();
        $posts = $tag->posts()->where('status', 'published')->orderByDesc('published_at')->paginate(15);

        return view('public.tag', [
            'site' => $this->settings->all(),
            'tag' => $tag,
            'posts' => $posts,
            'title' => $tag->meta_title ?: $tag->name,
            'metaDescription' => $tag->meta_description ?: '',
            'canonicalUrl' => url('/tag/'.$tag->slug),
            'breadcrumbs' => [['label' => $tag->name]],
            'loadNewsCardFit' => true,
        ]);
    }

    public function slug(string $slug): View
    {
        foreach (['post', 'events', 'school', 'major', 'page'] as $scope) {
            $row = ContentCategory::query()->inScope($scope)->where('slug', $slug)->where('is_active', 1)->first();
            if ($row) {
                $postScope = $scope === 'page' ? 'post' : $scope;
                $children = in_array($scope, ['school', 'major'], true)
                    ? ContentCategory::query()->inScope($scope)->where('parent_id', $row->id)->where('is_active', 1)->orderBy('sort_order')->get()
                    : collect();
                $postsQuery = ContentPost::query()->inScope($postScope)
                    ->with('category')
                    ->where('category_id', $row->id)
                    ->where('status', 'published')
                    ->orderByDesc('published_at');
                $posts = $scope === 'events'
                    ? $postsQuery->limit(80)->get()
                    : $postsQuery->paginate(15)->withQueryString();
                $payload = [
                    'site' => $this->settings->all(),
                    'page' => $row,
                    'category' => $row,
                    'descriptionHtml' => $this->html->expand((string) $row->description),
                    'posts' => $posts,
                    'children' => $children,
                    'title' => $row->meta_title ?: $row->name,
                    'metaDescription' => $row->meta_description ?: '',
                    'showSchoolMap' => $scope === 'school',
                    'canonicalUrl' => url('/'.$row->slug),
                    'breadcrumbs' => [['label' => $row->name]],
                    'loadNewsCardFit' => in_array($scope, ['post', 'events', 'major'], true),
                ];
                if ($scope === 'post') {
                    $payload['sidebarFeatured'] = $this->sidebarFeatured($row, $postScope);
                    $payload['sidebarTags'] = $this->sidebarTags(14);
                }
                if ($scope === 'major' && ($row->slug ?? '') !== 'cac-nganh-du-hoc-han-quoc') {
                    $article = $this->html->expandArticle((string) $row->description, true);
                    $lead = $article['leadImage'];
                    if (is_array($lead) && ! empty($lead['src'])) {
                        $lead['src'] = \App\Support\PublicUrl::to($lead['src']);
                    }
                    $payload['descriptionHtml'] = $article['html'];
                    $payload['tocItems'] = $article['items'];
                    $payload['leadImage'] = $lead;
                    $payload['loadNewsToc'] = $article['items'] !== [];
                    $payload['relatedMajors'] = $this->relatedMajorCategories($row);
                    $payload['prevMajor'] = $this->adjacentMajor($row, 'prev');
                    $payload['nextMajor'] = $this->adjacentMajor($row, 'next');
                    $payload['breadcrumbs'] = [
                        ['label' => 'Các ngành du học Hàn Quốc', 'url' => url('/cac-nganh-du-hoc-han-quoc')],
                        ['label' => $row->name],
                    ];
                }
                if ($scope === 'school') {
                    $payload['mapItems'] = $this->schoolMap->items($row, $children, $posts);

                    return view('public.school-map', $payload);
                }
                $view = match ($scope) {
                    'page' => 'public.page',
                    'post' => 'public.category-news',
                    'events' => 'public.category-events',
                    'major' => 'public.category-major',
                    default => 'public.category',
                };

                return view($view, $payload);
            }
        }

        abort(404);
    }

    private function showPost(string $scope, string $slug): View
    {
        $post = ContentPost::query()->inScope($scope)->with(['category', 'tags'])->where('slug', $slug)->where('status', 'published')->firstOrFail();
        $this->bumpViews($post);
        $view = $scope === 'events' ? 'public.post-event' : 'public.post';

        return view($view, $this->articlePayload($post));
    }

    /**
     * @return array<string, mixed>
     */
    private function articlePayload(ContentPost $post, ?string $commentAction = null): array
    {
        $post->loadMissing(['category', 'tags']);
        $article = $this->html->expandArticle((string) $post->body, $post->scope === 'post');
        $lead = $article['leadImage'];
        if (is_array($lead) && ! empty($lead['src'])) {
            $lead['src'] = \App\Support\PublicUrl::to($lead['src']);
        }

        return [
            'site' => $this->settings->all(),
            'post' => $post,
            'bodyHtml' => $article['html'],
            'tocItems' => $article['items'],
            'leadImage' => $lead,
            'category' => $post->category,
            'tags' => $post->tags,
            'comments' => $post->comments()->where('status', ContentComment::STATUS_APPROVED)->orderBy('created_at')->get(),
            'commentsEnabled' => $this->settings->get('public_comments_enabled', '1') === '1',
            'commentAction' => $commentAction ?? $this->commentActionFor($post),
            'title' => $post->meta_title ?: $post->title,
            'metaDescription' => $post->meta_description ?: strip_tags((string) $post->excerpt),
            'ogTitle' => $post->og_title ?: ($post->meta_title ?: $post->title),
            'ogImage' => $post->og_image ?: $post->featured_image,
            'canonicalUrl' => $post->publicUrl(),
            'relatedPosts' => $this->relatedPosts($post),
            'prevPost' => $post->scope === 'post' ? $this->adjacentPost($post, 'prev') : null,
            'nextPost' => $post->scope === 'post' ? $this->adjacentPost($post, 'next') : null,
            'sidebarCategories' => $post->scope === 'post' ? $this->sidebarCategories() : collect(),
            'sidebarTags' => $post->scope === 'post' ? $this->sidebarTags(16) : collect(),
            'breadcrumbs' => $this->postBreadcrumbs($post),
            'loadNewsToc' => $article['items'] !== [],
            'loadNewsCardFit' => $post->scope === 'events',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, ContentPost>
     */
    private function sidebarFeatured(ContentCategory $category, string $scope)
    {
        return ContentPost::query()->inScope($scope)
            ->where('category_id', $category->id)
            ->where('status', 'published')
            ->orderByDesc('view_count')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ContentTag>
     */
    private function sidebarTags(int $limit)
    {
        return ContentTag::query()->inScope('post')->where('is_active', 1)->orderBy('sort_order')->limit($limit)->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ContentCategory>
     */
    private function majorSiblings(ContentCategory $row)
    {
        return ContentCategory::query()->inScope('major')
            ->where('parent_id', (int) $row->parent_id)
            ->where('is_active', 1)
            ->where('slug', '!=', 'cac-nganh-du-hoc-han-quoc')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ContentCategory>
     */
    private function relatedMajorCategories(ContentCategory $row)
    {
        return $this->majorSiblings($row)
            ->where('id', '!=', $row->id)
            ->take(5)
            ->values();
    }

    private function adjacentMajor(ContentCategory $row, string $dir): ?ContentCategory
    {
        $siblings = $this->majorSiblings($row)->values();
        $idx = $siblings->search(fn (ContentCategory $item) => (int) $item->id === (int) $row->id);
        if ($idx === false) {
            return null;
        }
        if ($dir === 'prev') {
            return $idx > 0 ? $siblings[$idx - 1] : null;
        }

        return $idx < $siblings->count() - 1 ? $siblings[$idx + 1] : null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, ContentCategory>
     */
    private function sidebarCategories()
    {
        return ContentCategory::query()->inScope('post')->where('is_active', 1)->orderBy('sort_order')->limit(16)->get()
            ->map(function (ContentCategory $cat) {
                $cat->setAttribute('post_count', ContentPost::query()->inScope('post')
                    ->where('category_id', $cat->id)
                    ->where('status', 'published')
                    ->count());

                return $cat;
            });
    }

    private function bumpViews(ContentPost $post): void
    {
        if (! Schema::hasColumn('content_post', 'view_count')) {
            return;
        }
        ContentPost::query()->whereKey($post->id)->increment('view_count');
        $post->view_count = (int) $post->view_count + 1;
    }

    private function commentActionFor(ContentPost $post): string
    {
        return match ($post->scope) {
            'events' => url('/su-kien/'.$post->slug.'/comment'),
            'major' => url('/nganh/'.$post->slug.'/comment'),
            'school' => $post->category && $post->category->slug
                ? url('/truong/'.$post->category->slug.'/'.$post->slug.'/comment')
                : url('/truong/'.$post->slug.'/comment'),
            default => $post->category && $post->category->slug
                ? url('/'.$post->category->slug.'/'.$post->slug.'/comment')
                : url('/post/'.$post->slug.'/comment'),
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, ContentPost>
     */
    private function relatedPosts(ContentPost $post)
    {
        return ContentPost::query()->inScope($post->scope)->with('category')
            ->where('status', 'published')
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
    }

    private function adjacentPost(ContentPost $post, string $dir): ?ContentPost
    {
        $q = ContentPost::query()->inScope($post->scope)->with('category')->where('status', 'published');
        if ($dir === 'prev') {
            return $q->where('id', '<', $post->id)->orderByDesc('id')->first();
        }

        return $q->where('id', '>', $post->id)->orderBy('id')->first();
    }

    /**
     * @return list<array{label: string, url?: string}>
     */
    private function postBreadcrumbs(ContentPost $post): array
    {
        $crumbs = [];
        if ($post->category && $post->category->slug) {
            $crumbs[] = ['label' => $post->category->name, 'url' => url('/'.$post->category->slug)];
        }
        $crumbs[] = ['label' => $post->title];

        return $crumbs;
    }

}
