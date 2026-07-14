<?php

namespace App\Model\Lopi\Sync_Google;

use Illuminate\Database\Eloquent\Model;

class Calendar_User extends Model
{
    protected $table = 'siakip2_calendar_user';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'google_calendar_id', 'username'];

}
