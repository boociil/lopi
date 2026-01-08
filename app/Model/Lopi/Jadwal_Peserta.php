<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Jadwal_Peserta extends Model
{
    protected $table = 'siakip2_jadwal_peserta';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'jadwal_id', 'username'];

}
