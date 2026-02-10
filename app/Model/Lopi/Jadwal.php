<?php

namespace App\Model\Lopi;

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

    protected $fillable = ['id', 'pembuat_username', 'pemilik_tipe_str', 'pemilik_type', 'pemilik_id'
        , 'kegiatan', 'description'
        , 'tipe', 'tanggal', 'berulang_tanggal_awal', 'berulang_tanggal_akhir'
        , 'tanggal_awal', 'tanggal_akhir', 'waktu_awal', 'waktu_akhir', 'status'
        , 'url_daftar_hadir', 'url_notulensi', 'url_dokumentasi', 'url_undangan'
        , 'ruang_status', 'ruang', 'ruang_confirm_username'
        , 'zoom_status', 'zoom_pinjam_id' ];

    public function peserta(): HasMany
    {
        return $this->hasMany(Jadwal_Peserta::class, 'jadwal_id', 'id');
    }
}
