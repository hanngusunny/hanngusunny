<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavMenu extends Model
{
    protected $table = 'nav_menu';

    protected $fillable = ['name', 'sort_order'];

    public function items(): HasMany
    {
        return $this->hasMany(NavMenuItem::class, 'nav_menu_id')->orderBy('sort_order');
    }
}
