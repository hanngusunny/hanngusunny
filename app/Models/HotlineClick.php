<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotlineClick extends Model
{
    public $timestamps = false;

    protected $table = 'hotline_click';

    protected $fillable = ['source_page', 'client_ip', 'user_agent', 'created_at'];
}
