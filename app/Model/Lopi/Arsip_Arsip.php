<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Arsip_Arsip extends Model
{
    protected $table = 'siakip2_arsip';

    protected $fillable = [
        'klasifikasi_arsip_id',
        'unit_cipta_arsip',
        'tingkat_perkembangan',
        'media_arsip',
        'kondisi_arsip',
        'uraian_arsip',
        'kurun_waktu_awal',
        'kurun_waktu_akhir',
        'jumlah',
        'lokasi_simpan'
    ];

    public function klasifikasi()
    {
        return $this->belongsTo(Arsip_KlasifikasiArsip::class, 'klasifikasi_arsip_id');
    }

    public function akses()
    {
        return $this->hasMany(Arsip_Akses::class, 'arsip_id');
    }
}
