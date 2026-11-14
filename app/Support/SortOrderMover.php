<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SortOrderMover
{
    /**
     * @param  Collection<int, Model>  $ordered
     */
    public static function move(Collection $ordered, int $id, string $dir): void
    {
        $items = $ordered->values();
        foreach ($items as $idx => $row) {
            if ((int) $row->sort_order !== $idx) {
                $row->sort_order = $idx;
                $row->save();
            }
        }
        $i = $items->search(fn (Model $row) => (int) $row->getKey() === $id);
        if ($i === false) {
            return;
        }
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($j < 0 || $j >= $items->count()) {
            return;
        }
        $a = $items[$i];
        $b = $items[$j];
        $tmp = $a->sort_order;
        $a->sort_order = $b->sort_order;
        $b->sort_order = $tmp;
        $a->save();
        $b->save();
    }
}
