<?php

namespace App\Model\Jadwal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jadwal extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_jadwal';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;


    protected $fillable = ['id', 'title', 'description', 'start_date', 'end_date', 'event_type'
        , 'ruang', 'url_daftar_hadir', 'url_notulensi', 'url_dokumentasi', 'url_undangan'];


}
