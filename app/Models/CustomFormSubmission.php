<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFormSubmission extends Model
{
    public $timestamps = false;

    protected $table = 'custom_form_submission';

    protected $fillable = ['form_id', 'payload_json', 'ip', 'user_agent', 'created_at'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(CustomForm::class, 'form_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $decoded = json_decode((string) $this->payload_json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
