<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zoom extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_zoom';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $casts = [
        'recurrence_opt' => 'array'
    ];

    protected $fillable = ['id', 'topic', 'password', 'start_time', 'end_time', 'duration'
        , 'pembuat_username', 'confirm_username', 'is_recurrence', 'recurrence_opt'
        , 'zoom_status', 'zoom_akun', 'zoom_meeting_id', 'zoom_start_url', 'zoom_join_url'];

    public function pembuat(){
        return $this->hasOne(User::class, 'username', 'pembuat_username');
    }

    public function confirm(){
        return $this->hasOne(User::class, 'username', 'confirm_username');
    }
}
