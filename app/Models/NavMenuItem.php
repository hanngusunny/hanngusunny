<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavMenuItem extends Model
{
    protected $table = 'nav_menu_item';

    protected $fillable = [
        'nav_menu_id', 'parent_id', 'sort_order', 'item_type', 'ref_id',
        'label', 'url', 'target_blank', 'is_active',
    ];

    protected $casts = [
        'target_blank' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavMenu::class, 'nav_menu_id');
    }
}
