<?php

namespace App\Model\Jadwal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jadwal extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_jadwal';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'pembuat_username', 'pemilik_tipe_str', 'pemilik_type', 'pemilik_id', 'tanggal'
        , 'title', 'tipe_jadwal', 'description', 'ruang'
        , 'url_zoom', 'url_daftar_hadir', 'url_notulensi', 'url_dokumentasi', 'url_undangan'];

    public function pemilik(): HasMany
    {
        return $this->hasMany(Jadwal_Peserta::class, 'jadwal_id', 'id');
    }
}
