<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavMenu;
use App\Models\NavMenuItem;
use App\Services\Nav\NavMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NavMenuController extends Controller
{
    public function __construct(private readonly NavMenuService $nav)
    {
    }

    public function index(): View
    {
        return view('admin.menus.index', [
            'pageTitle' => 'Menu',
            'adminNavActive' => 'nav_menus',
            'rows' => NavMenu::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        NavMenu::query()->create(['name' => $data['name'], 'created_at' => now()]);

        return back()->with('ok', 'Đã tạo menu.');
    }

    public function update(Request $request, NavMenu $nav_menu): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $nav_menu->update([
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? $nav_menu->sort_order),
        ]);

        return back()->with('ok', 'Đã cập nhật menu.');
    }

    public function destroy(NavMenu $nav_menu): RedirectResponse
    {
        $nav_menu->items()->delete();
        $nav_menu->delete();

        return back()->with('ok', 'Đã xóa.');
    }

    public function builder(NavMenu $nav_menu): View
    {
        return view('admin.menus.builder', [
            'pageTitle' => 'Menu — '.$nav_menu->name,
            'adminNavActive' => 'nav_menus',
            'menu' => $nav_menu,
            'items' => $nav_menu->items,
            'tree' => $this->nav->tree($nav_menu),
        ]);
    }

    public function storeItem(Request $request, NavMenu $nav_menu): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'item_type' => ['required', 'in:custom,page,post,category'],
            'parent_id' => ['nullable', 'integer'],
            'ref_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        NavMenuItem::query()->create([
            'nav_menu_id' => $nav_menu->id,
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'ref_id' => (int) ($data['ref_id'] ?? 0),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'label' => $data['label'],
            'url' => $data['url'] ?? '',
            'item_type' => $data['item_type'],
            'target_blank' => $request->boolean('target_blank'),
            'is_active' => 1,
            'created_at' => now(),
        ]);

        return back()->with('ok', 'Đã thêm mục.');
    }

    public function updateItem(Request $request, NavMenuItem $nav_menu_item): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
            'item_type' => ['nullable', 'in:custom,page,post,category'],
        ]);
        $nav_menu_item->update([
            'label' => $data['label'],
            'url' => $data['url'] ?? '',
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'sort_order' => (int) ($data['sort_order'] ?? $nav_menu_item->sort_order),
            'item_type' => $data['item_type'] ?? $nav_menu_item->item_type,
            'target_blank' => $request->boolean('target_blank'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('ok', 'Đã cập nhật.');
    }

    public function destroyItem(NavMenuItem $nav_menu_item): RedirectResponse
    {
        NavMenuItem::query()->where('parent_id', $nav_menu_item->id)->update(['parent_id' => 0]);
        $nav_menu_item->delete();

        return back()->with('ok', 'Đã xóa.');
    }

    public function saveTree(Request $request, NavMenu $nav_menu): JsonResponse
    {
        $tree = $request->input('tree');
        if (! is_array($tree)) {
            return response()->json(['ok' => false, 'error' => 'invalid_tree'], 422);
        }
        $this->nav->saveTree((int) $nav_menu->id, $tree);

        return response()->json(['ok' => true]);
    }
}
