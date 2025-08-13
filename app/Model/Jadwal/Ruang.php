<?php

namespace App\Model\Jadwal;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ruang extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_ruang';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;


    protected $fillable = ['id', 'topic', 'status', 'ruang'
        , 'pembuat_username', 'confirm_username'];

    public function pembuat(){
        return $this->hasOne(User::class, 'username', 'pembuat_username');
    }

    public function confirm(){
        return $this->hasOne(User::class, 'username', 'confirm_username');
    }
}
