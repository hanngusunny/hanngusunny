<?php

namespace App\Services\Nav;

use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\NavMenu;
use App\Models\NavMenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class NavMenuService
{
    /**
     * @param  list<array<string, mixed>>  $tree
     */
    public function saveTree(int $menuId, array $tree): bool
    {
        $ids = NavMenuItem::query()->where('nav_menu_id', $menuId)->pluck('id')->all();
        $allowed = array_fill_keys($ids, true);
        $order = 0;
        $this->walkSave($menuId, $tree, 0, $order, $allowed);

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tree(NavMenu $menu): array
    {
        $items = $menu->items()->get();

        return $this->build($items, 0, false);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publicNav(): array
    {
        if (! Schema::hasTable('nav_menu_item')) {
            return [];
        }
        $menu = NavMenu::query()->orderBy('sort_order')->orderBy('id')->first();
        if ($menu === null) {
            return [];
        }

        return $this->build($menu->items()->get(), 0, true);
    }

    public function itemUrl(NavMenuItem $item): string
    {
        $type = (string) $item->item_type;
        $ref = (int) $item->ref_id;
        if ($type === 'custom') {
            return trim((string) $item->url);
        }
        if ($type === 'post' && $ref > 0) {
            $p = ContentPost::query()->with('category')->find($ref);
            if ($p && $p->slug) {
                return $p->publicUrl();
            }
        }
        if (in_array($type, ['page', 'category'], true) && $ref > 0) {
            $c = ContentCategory::query()->find($ref);
            if ($c && $c->slug) {
                return url('/'.$c->slug);
            }
        }

        return trim((string) $item->url);
    }

    /**
     * @param  Collection<int, NavMenuItem>  $items
     * @return list<array<string, mixed>>
     */
    private function build(Collection $items, int $parentId, bool $activeOnly = false): array
    {
        $out = [];
        foreach ($items as $item) {
            if ((int) $item->parent_id !== $parentId) {
                continue;
            }
            if ($activeOnly && ! $item->is_active) {
                continue;
            }
            $node = [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $this->itemUrl($item),
                'target_blank' => (bool) $item->target_blank,
                'is_active' => (bool) $item->is_active,
                'children' => $this->build($items, (int) $item->id, $activeOnly),
            ];
            $out[] = $node;
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  array<int, true>  $allowed
     */
    private function walkSave(int $menuId, array $nodes, int $parentId, int &$order, array $allowed): void
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            $id = (int) ($node['id'] ?? 0);
            if ($id < 1 || ! isset($allowed[$id])) {
                continue;
            }
            NavMenuItem::query()->where('id', $id)->where('nav_menu_id', $menuId)->update([
                'parent_id' => $parentId,
                'sort_order' => $order++,
            ]);
            $children = $node['children'] ?? [];
            if (is_array($children) && $children !== []) {
                $this->walkSave($menuId, $children, $id, $order, $allowed);
            }
        }
    }
}
