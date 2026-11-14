<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SliderCategory extends Model
{
    protected $table = 'slider_category';

    protected $fillable = ['name', 'slug', 'sort_order'];

    public function slides(): HasMany
    {
        return $this->hasMany(SliderSlide::class, 'category_id')->orderBy('sort_order');
    }
}
