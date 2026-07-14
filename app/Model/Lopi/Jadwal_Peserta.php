<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Jadwal_Peserta extends Model
{
    protected $table = 'siakip2_jadwal_peserta';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'jadwal_id', 'peserta_type', 'peserta_id'];

}
