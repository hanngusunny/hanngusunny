<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFormField extends Model
{
    protected $table = 'custom_form_field';

    protected $fillable = [
        'form_id', 'field_key', 'label', 'field_type', 'options',
        'placeholder', 'help_text', 'is_required', 'width', 'sort_order',
    ];

    protected $casts = ['is_required' => 'boolean'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(CustomForm::class, 'form_id');
    }

    /**
     * @return list<string>
     */
    public function optionList(): array
    {
        $raw = trim((string) $this->options);
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
    }
}
