<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SliderCategory;
use App\Models\SliderSlide;
use App\Services\Content\ContentCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SliderController extends Controller
{
    public function __construct(private readonly ContentCatalogService $catalog)
    {
    }

    public function index(): View
    {
        return view('admin.slider.categories', [
            'pageTitle' => 'Slider',
            'adminNavActive' => 'slider',
            'rows' => SliderCategory::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        SliderCategory::query()->create([
            'name' => $data['name'],
            'slug' => $this->catalog->slugify($data['name']),
            'created_at' => now(),
        ]);

        return back()->with('ok', 'Đã thêm nhóm slider.');
    }

    public function updateCategory(Request $request, SliderCategory $slider_category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $slider_category->update([
            'name' => $data['name'],
            'slug' => $this->catalog->slugify($data['name']),
        ]);

        return back()->with('ok', 'Đã cập nhật nhóm.');
    }

    public function destroyCategory(SliderCategory $slider_category): RedirectResponse
    {
        $slider_category->slides()->delete();
        $slider_category->delete();

        return back()->with('ok', 'Đã xóa.');
    }

    public function slides(SliderCategory $slider_category): View
    {
        return view('admin.slider.slides', [
            'pageTitle' => 'Slide — '.$slider_category->name,
            'adminNavActive' => 'slider',
            'category' => $slider_category,
            'slides' => $slider_category->slides,
        ]);
    }

    public function storeSlide(Request $request, SliderCategory $slider_category): RedirectResponse
    {
        $data = $request->validate([
            'image_desktop' => ['nullable', 'string', 'max:500'],
            'image_mobile' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'link_url' => ['nullable', 'string', 'max:2000'],
        ]);
        SliderSlide::query()->create([
            'category_id' => $slider_category->id,
            'image_desktop' => $data['image_desktop'] ?? '',
            'image_mobile' => $data['image_mobile'] ?? '',
            'content' => $data['content'] ?? '',
            'link_url' => $data['link_url'] ?? '',
            'sort_order' => (int) SliderSlide::query()->where('category_id', $slider_category->id)->max('sort_order') + 1,
            'created_at' => now(),
        ]);

        return back()->with('ok', 'Đã thêm slide.');
    }

    public function updateSlide(Request $request, SliderSlide $slider_slide): RedirectResponse
    {
        $data = $request->validate([
            'image_desktop' => ['nullable', 'string', 'max:500'],
            'image_mobile' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'link_url' => ['nullable', 'string', 'max:2000'],
        ]);
        $slider_slide->update($data);

        return back()->with('ok', 'Đã cập nhật.');
    }

    public function destroySlide(SliderSlide $slider_slide): RedirectResponse
    {
        $slider_slide->delete();

        return back()->with('ok', 'Đã xóa.');
    }

    public function moveSlideUp(SliderSlide $slider_slide): RedirectResponse
    {
        return $this->moveSlide($slider_slide, 'up');
    }

    public function moveSlideDown(SliderSlide $slider_slide): RedirectResponse
    {
        return $this->moveSlide($slider_slide, 'down');
    }

    private function moveSlide(SliderSlide $slider_slide, string $dir): RedirectResponse
    {
        $siblings = SliderSlide::query()->where('category_id', $slider_slide->category_id)->orderBy('sort_order')->orderBy('id')->get();
        \App\Support\SortOrderMover::move($siblings, (int) $slider_slide->id, $dir);

        return back()->with('ok', 'Đã đổi thứ tự.');
    }
}
