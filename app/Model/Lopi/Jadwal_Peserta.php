<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jadwal_Peserta extends Model
{
    protected $table = 'siakip2_jadwal_peserta';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'jadwal_id', 'tipe'
        , 'peserta_type', 'peserta_id'];

     public function peserta(): MorphTo
    {
        return $this->morphTo();
    }
}
