<?php

namespace App\Model\Lopi;

use App\Observers\JadwalObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([JadwalObserver::class])]
class Jadwal extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_jadwal';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'pembuat_username'
        , 'kegiatan', 'description'
        , 'tipe', 'tanggal', 'berulang_tanggal_awal', 'berulang_tanggal_akhir'
        , 'waktu_awal', 'waktu_akhir', 'status'
        , 'url_daftar_hadir', 'url_notulensi', 'url_dokumentasi', 'url_undangan'
        , 'ruang_status', 'ruang', 'ruang_confirm_username'
        , 'zoom_status', 'zoom_pinjam_id', 'zoom_langsung_join_url'];

    public function peserta(): HasMany
    {
        return $this->hasMany(Jadwal_Peserta::class, 'jadwal_id', 'id');
    }

    public function zoom_pinjam(): HasOne
    {
        return $this->hasOne(Pinjam_Zoom::class, 'id', 'zoom_pinjam_id');
    }
}
