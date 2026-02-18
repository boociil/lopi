<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Arsip_Akses extends Model
{
    protected $table = 'siakip2_arsip_akses';
    public $timestamps = false;

    protected $fillable = [
        'arsip_id',
        'username',
        'status'
    ];

    public function arsip()
    {
        return $this->belongsTo(Arsip_Arsip::class, 'arsip_id');
    }
}
