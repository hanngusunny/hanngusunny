<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisitLog extends Model
{
    public $timestamps = false;

    protected $table = 'site_visit_log';

    protected $fillable = [
        'visitor_id', 'session_id', 'is_new_visitor', 'is_bot', 'request_path', 'query_string',
        'page_url', 'http_method', 'status_code', 'referrer', 'referrer_host', 'source_type',
        'search_keyword', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'client_ip', 'user_agent', 'device_type', 'browser', 'os', 'accept_language', 'created_at',
    ];

    protected $casts = [
        'is_new_visitor' => 'boolean',
        'is_bot' => 'boolean',
        'created_at' => 'datetime',
    ];
}
