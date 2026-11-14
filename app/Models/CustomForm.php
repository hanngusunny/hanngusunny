<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomForm extends Model
{
    protected $table = 'custom_form';

    protected $fillable = ['name', 'slug', 'description', 'success_message', 'is_active', 'style_json'];

    protected $casts = ['is_active' => 'boolean'];

    public const FIELD_TYPES = [
        'text', 'email', 'tel', 'url', 'number',
        'textarea', 'select', 'checkbox', 'date', 'datetime-local',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(CustomFormField::class, 'form_id')->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CustomFormSubmission::class, 'form_id')->orderByDesc('id');
    }
}
