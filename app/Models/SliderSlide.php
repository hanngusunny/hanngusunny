<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SliderSlide extends Model
{
    protected $table = 'slider_slide';

    protected $fillable = ['category_id', 'image_desktop', 'image_mobile', 'content', 'link_url', 'sort_order'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SliderCategory::class, 'category_id');
    }
}
