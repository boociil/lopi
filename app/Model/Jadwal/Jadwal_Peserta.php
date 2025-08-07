<?php

namespace App\Model\Jadwal;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webpatser\Uuid\Uuid;

class Jadwal_Peserta extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_jadwal_peserta';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'jadwal_id', 'tambah_sendiri', 'tipe_jadwal'
        , 'peserta_satuan_kerja_tahun_id', 'peserta_tim_id', 'peserta_username'];


}
