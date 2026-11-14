<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentComment extends Model
{
    protected $table = 'content_comment';

    protected $fillable = [
        'post_id', 'scope', 'author_name', 'author_email', 'body', 'status', 'ip',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SPAM = 'spam';

    public const STATUS_TRASH = 'trash';

    public function post(): BelongsTo
    {
        return $this->belongsTo(ContentPost::class, 'post_id');
    }
}
