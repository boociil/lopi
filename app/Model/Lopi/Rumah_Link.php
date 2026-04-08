<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Rumah_Link extends Model
{
    protected $table = 'siakip2_rumah_links';
    protected $primaryKey = 'id';
    public $incrementing = false; // karena pakai string UUID
    protected $keyType = 'string';

    protected $fillable = ['id', 'title', 'url'
        , 'color', 'icon', 'access_type', 'access_id', 'default_pin', 'pembuat_username', 'satuan_kerja_tahun_id'];

}
