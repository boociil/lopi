<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Arsip_KlasifikasiArsip extends Model
{
    protected $table = 'siakip2_arsip_klasifikasi';

    protected $fillable = [
        'kode',
        'nama'
    ];

    public function arsip()
    {
        return $this->hasMany(Arsip_Arsip::class, 'klasifikasi_arsip_id');
    }
}
