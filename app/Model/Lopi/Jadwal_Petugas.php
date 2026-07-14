<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Jadwal_Petugas extends Model
{
    protected $table = 'siakip2_petugas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'kegiatan_tipe', 'kegiatan_id', 'username'];
}
