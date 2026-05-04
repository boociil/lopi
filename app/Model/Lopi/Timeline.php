<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    protected $table = 'siakip2_timeline';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'dependencies' => 'array',
    ];

    protected $fillable = ['id', 'name', 'startDate', 'endDate', 'color'
       , 'access_type', 'access_id', 'parent_type', 'parent_id', 'dependencies', 'pembuat_username', 'satuan_kerja_tahun_id'];

}
