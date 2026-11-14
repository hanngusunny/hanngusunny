<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultLead extends Model
{
    public $timestamps = false;

    protected $table = 'consult_lead';

    protected $fillable = [
        'topic_key',
        'topic_label',
        'full_name',
        'phone',
        'province',
        'message',
        'client_ip',
        'user_agent',
        'source_page',
        'form_origin',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public const TOPICS = [
        'tieng' => 'Du học tiếng Hàn',
        'dai-hoc' => 'Du học đại học',
        'sau-dh' => 'Thạc sĩ / Tiến sĩ',
        'visa' => 'Visa & hồ sơ',
        'hoc-bong' => 'Học bổng & chi phí',
        'khac' => 'Khác',
    ];
}
