<?php

namespace App\Model\Jadwal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zoom extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_jadwal';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $casts = [
        'recurrence_opt' => 'array'
    ];

    protected $fillable = ['id', 'agenda', 'topic', 'password', 'start_time', 'duration'
        , 'pembuat_username', 'approve_username', 'is_recurrence', 'recurrence_opt'
        , 'zoom_status', 'zoom_akun', 'zoom_meeting_id', 'zoom_start_url', 'zoom_join_url', 'jadwal_id'];

}
