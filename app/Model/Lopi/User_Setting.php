<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class User_Setting extends Model
{
    protected $table = 'siakip2_user_setting';
    protected $primaryKey = 'id';
    public $incrementing = false; // karena pakai string UUID
    protected $keyType = 'string';

    protected $fillable = ['id', 'username', 'category', 'key', 'data', 'satuan_kerja_tahun_id'];

    protected $casts = [
        'data' => 'array', // auto encode/decode JSON
    ];
}
