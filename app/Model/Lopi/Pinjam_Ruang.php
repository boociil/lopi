<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pinjam_Ruang extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_pinjam_ruang';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;


    protected $fillable = [
        'id',
        'topic',
        'ruang',
        'tipe',
        'berulang_tanggal_awal',
        'berulang_tanggal_akhir',
        'tanggal_awal',
        'tanggal_akhir',
        'waktu_awal',
        'waktu_akhir',
        'status',
        'pembuat_username',
        'confirm_username',
    ];

    public function pembuat(){
        return $this->hasOne(User::class, 'username', 'pembuat_username');
    }

    public function confirm(){
        return $this->hasOne(User::class, 'username', 'confirm_username');
    }
}
