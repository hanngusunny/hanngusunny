<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use App\Models\FaqItem;
use App\Services\Content\ContentCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __construct(private readonly ContentCatalogService $catalog)
    {
    }

    public function index(): View
    {
        return view('admin.faq.categories', [
            'pageTitle' => 'FAQ',
            'adminNavActive' => 'faq_categories',
            'rows' => FaqCategory::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        FaqCategory::query()->create([
            'name' => $data['name'],
            'slug' => $this->catalog->slugify($data['name']),
            'is_active' => 1,
            'created_at' => now(),
        ]);

        return back()->with('ok', 'Đã thêm nhóm FAQ.');
    }

    public function updateCategory(Request $request, FaqCategory $faq_category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $faq_category->update(['name' => $data['name']]);

        return back()->with('ok', 'Đã cập nhật.');
    }

    public function destroyCategory(FaqCategory $faq_category): RedirectResponse
    {
        $faq_category->items()->delete();
        $faq_category->delete();

        return back()->with('ok', 'Đã xóa.');
    }

    public function items(FaqCategory $faq_category): View
    {
        return view('admin.faq.items', [
            'pageTitle' => 'FAQ — '.$faq_category->name,
            'adminNavActive' => 'faq_items',
            'category' => $faq_category,
            'items' => $faq_category->items,
        ]);
    }

    public function storeItem(Request $request, FaqCategory $faq_category): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['nullable', 'string'],
        ]);
        FaqItem::query()->create([
            'category_id' => $faq_category->id,
            'question' => $data['question'],
            'answer' => $data['answer'] ?? '',
            'sort_order' => (int) FaqItem::query()->where('category_id', $faq_category->id)->max('sort_order') + 1,
            'is_active' => 1,
            'created_at' => now(),
        ]);

        return back()->with('ok', 'Đã thêm câu hỏi.');
    }

    public function updateItem(Request $request, FaqItem $faq_item): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['nullable', 'string'],
        ]);
        $faq_item->update($data);

        return back()->with('ok', 'Đã cập nhật.');
    }

    public function destroyItem(FaqItem $faq_item): RedirectResponse
    {
        $faq_item->delete();

        return back()->with('ok', 'Đã xóa.');
    }

    public function moveItemUp(FaqItem $faq_item): RedirectResponse
    {
        return $this->moveItem($faq_item, 'up');
    }

    public function moveItemDown(FaqItem $faq_item): RedirectResponse
    {
        return $this->moveItem($faq_item, 'down');
    }

    private function moveItem(FaqItem $faq_item, string $dir): RedirectResponse
    {
        $siblings = FaqItem::query()->where('category_id', $faq_item->category_id)->orderBy('sort_order')->orderBy('id')->get();
        \App\Support\SortOrderMover::move($siblings, (int) $faq_item->id, $dir);

        return back()->with('ok', 'Đã đổi thứ tự.');
    }
}
