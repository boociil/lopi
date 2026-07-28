<?php

namespace App\Model\Lopi;

use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;

class Jadwal_Peserta extends Model
{
    protected $table = 'siakip2_jadwal_peserta';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'jadwal_id', 'peserta_type', 'peserta_id'];

    public function satuan_kerja_tahun()
    {
        return $this->hasOne(Satuan_Kerja_Tahun::class, 'id', 'peserta_id');
    }

    public function tim()
    {
        return $this->hasOne(Tim::class, 'id', 'peserta_id');
    }
    public function user()
    {
        return $this->hasOne(User::class, 'username', 'peserta_id');
    }

}
