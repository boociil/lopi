<?php

namespace App\Model\Lopi\Sync_Google;

use Illuminate\Database\Eloquent\Model;

class Calendar_Google_Event extends Model
{
    protected $table = 'siakip2_calendar_google_event';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'parent_tipe', 'parent_id', 'google_calendar_id', 'event_id', 'username'];
}
